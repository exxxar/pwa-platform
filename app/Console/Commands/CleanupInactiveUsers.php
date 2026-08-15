<?php

namespace App\Console\Commands;

use App\Models\Tenant\CashBack;
use App\Models\Tenant\Order;
use App\Models\Tenant\ReferralReward;
use App\Models\Tenant\Review;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\TenantUserAddress;
use App\Models\Tenant\UserFriend;
use App\Models\Tenant\UserReferral;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupInactiveUsers extends Command
{
    protected $signature = 'users:cleanup-inactive
                            {--days=90 : Количество дней неактивности}
                            {--tenant= : ID конкретного тенанта (опционально)}
                            {--dry-run : Только показать, что будет удалено, без удаления}
                            {--force : Пропустить подтверждение}
                            {--with-orders : Удалять даже с оплаченными заказами (ОПАСНО)}';

    protected $description = 'Удаление неактивных пользователей без кэшбэка и заказов';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $tenantId = $this->option('tenant');
        $dryRun = (bool) $this->option('dry-run');
        $withOrders = (bool) $this->option('with-orders');
        $force = (bool) $this->option('force');

        if ($days < 30) {
            $this->error('❌ Минимальный порог — 30 дней');
            return self::FAILURE;
        }

        $this->info("🔍 Поиск пользователей неактивных более {$days} дней");
        $this->info("📊 Условия: обновлялся > {$days} дней назад, cashback = 0, не VIP");

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN режим — ничего не будет удалено');
        }

        // ==========================================
        // ПОСТРОЕНИЕ ЗАПРОСА
        // ==========================================
        $cutoffDate = now()->subDays($days);

        $query = TenantUser::query()
            // Неактивность: обновлялся давно
            ->where('updated_at', '<', $cutoffDate)
            // Не VIP
            ->where('is_vip', false)
            // Нет кэшбэка (проверяем через сумму cashbacks)
            ->whereDoesntHave('cashbacks', function ($q) {
                $q->where('amount', '!=', 0);
            })
            // Нет оплаченных заказов (если не указан --with-orders)
            ->when(!$withOrders, function ($q) {
                $q->whereDoesntHave('orders', function ($oq) {
                    $oq->whereNotNull('payed_at');
                });
            })
            // Не админ (нет роли super_admin)
            ->whereDoesntHave('roles', function ($rq) {
                $rq->where('name', 'super_admin');
            })
            // Активен (не заблокирован вручную)
            ->where('is_active', true)
            ->whereNull('blocked_at');

        // Фильтр по тенанту
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
            $tenant = Tenant::find($tenantId);
            $this->info("🏢 Тенант: " . ($tenant?->name ?? "ID {$tenantId}"));
        }

        $totalCandidates = $query->count();

        if ($totalCandidates === 0) {
            $this->info('✅ Пользователей для удаления не найдено');
            return self::SUCCESS;
        }

        $this->info("📋 Найдено кандидатов на удаление: {$totalCandidates}");

        // ==========================================
        // ПОДТВЕРЖДЕНИЕ
        // ==========================================
        if (!$dryRun && !$force) {
            if (!$this->confirm("Удалить {$totalCandidates} пользователей?")) {
                $this->info('Отменено');
                return self::SUCCESS;
            }
        }

        // ==========================================
        // УДАЛЕНИЕ
        // ==========================================
        $deleted = 0;
        $failed = 0;
        $stats = [
            'cashbacks' => 0,
            'addresses' => 0,
            'dialogs' => 0,
            'messages' => 0,
            'reviews' => 0,
            'friends' => 0,
            'referrals' => 0,
            'rewards' => 0,
            'orders' => 0,
        ];

        $bar = $this->output->createProgressBar($totalCandidates);
        $bar->start();

        $query->chunk(100, function ($users) use (
            $dryRun, &$deleted, &$failed, &$stats, $bar, $withOrders
        ) {
            foreach ($users as $user) {
                try {
                    if ($dryRun) {
                        $this->showUserInfo($user);
                    } else {
                        $userStats = $this->deleteUserWithRelations($user, $withOrders);
                        foreach ($userStats as $key => $count) {
                            $stats[$key] += $count;
                        }
                    }
                    $deleted++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('[CleanupInactiveUsers] Ошибка удаления', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->newLine();
                    $this->error("❌ Ошибка при удалении #{$user->id}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // ==========================================
        // СТАТИСТИКА
        // ==========================================
        if ($dryRun) {
            $this->info("📋 DRY-RUN: было бы удалено пользователей: {$deleted}");
        } else {
            $this->info("✅ Удалено пользователей: {$deleted}");
            $this->table(
                ['Сущность', 'Удалено записей'],
                [
                    ['Адреса', $stats['addresses']],
                    ['Кэшбэки', $stats['cashbacks']],
                    ['Диалоги', $stats['dialogs']],
                    ['Сообщения', $stats['messages']],
                    ['Отзывы', $stats['reviews']],
                    ['Дружеские связи', $stats['friends']],
                    ['Реферальные связи', $stats['referrals']],
                    ['Реферальные награды', $stats['rewards']],
                    ['Заказы', $stats['orders']],
                ]
            );
        }

        if ($failed > 0) {
            $this->error("❌ Ошибок: {$failed}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Удаляет пользователя и все связанные сущности в транзакции
     */
    private function deleteUserWithRelations(TenantUser $user, bool $withOrders): array
    {
        $stats = [
            'cashbacks' => 0,
            'addresses' => 0,
            'dialogs' => 0,
            'messages' => 0,
            'reviews' => 0,
            'friends' => 0,
            'referrals' => 0,
            'rewards' => 0,
            'orders' => 0,
        ];

        DB::transaction(function () use ($user, $withOrders, &$stats) {
            // 1. Удаляем push-подписки
            $user->pushSubscriptions()->delete();

            // 2. Удаляем кэшбэки
            $stats['cashbacks'] = CashBack::where('tenant_user_id', $user->id)->delete();

            // 3. Удаляем адреса
            $stats['addresses'] = TenantUserAddress::where('tenant_user_id', $user->id)->delete();

            // 4. Удаляем отзывы
            $stats['reviews'] = Review::where('tenant_user_id', $user->id)->delete();

            // 5. Удаляем сообщения (сначала, т.к. зависит от dialog_id)
            $dialogIds = TenantDialog::where('tenant_user_id', $user->id)->pluck('id');
            if ($dialogIds->isNotEmpty()) {
                $stats['messages'] = TenantMessage::whereIn('dialog_id', $dialogIds)->delete();
                $stats['dialogs'] = TenantDialog::whereIn('id', $dialogIds)->delete();
            }

            // 6. Дружеские связи (pivot таблицы)
            $stats['friends'] = UserFriend::where('initiator_id', $user->id)
                ->orWhere('friend_id', $user->id)
                ->delete();

            // 7. Реферальные связи
            $stats['referrals'] = UserReferral::where('referrer_id', $user->id)
                ->orWhere('referred_id', $user->id)
                ->delete();

            // 8. Реферальные награды
            $stats['rewards'] = ReferralReward::where('user_id', $user->id)
                ->orWhere('referrer_id', $user->id)
                ->delete();

            // 9. Связь ролей (pivot)
            $user->roles()->detach();

            // 10. Orders — только если явно разрешено
            if ($withOrders) {
                $stats['orders'] = Order::where('tenant_user_id', $user->id)->delete();
            }

            // 11. Очищаем parent_id у детей (чтобы не сломать FK)
            TenantUser::where('parent_id', $user->id)->update(['parent_id' => null]);

            // 12. Сбрасываем referred_by у рефералов
            TenantUser::where('referred_by', $user->id)->update(['referred_by' => null]);

            // 13. Сам пользователь
            $user->delete();
        });

        return $stats;
    }

    /**
     * Вывод информации о пользователе в dry-run режиме
     */
    private function showUserInfo(TenantUser $user): void
    {
        $this->newLine();
        $daysSinceUpdate = now()->diffInDays($user->updated_at);
        $this->line(sprintf(
            '👤 #%d %s | Обновлено: %d дн. назад | Кэшбэк: %.0f | Заказов: %d',
            $user->id,
            $user->name,
            $daysSinceUpdate,
            $user->cashback_balance,
            $user->orders_count
        ));
    }
}
