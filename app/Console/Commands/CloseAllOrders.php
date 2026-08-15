<?php

namespace App\Console\Commands;

use App\Enums\OrderStatusEnum;
use App\Models\Tenant\Order;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Models\Tenant\ReferralReward;
use App\Services\ReferralService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloseAllOrders extends Command
{
    protected $signature = 'orders:close-all
                        {--tenant= : Slug тенанта}
                        {--limit= : Максимум заказов для обработки}
                        {--dry-run : Только показать что будет сделано}
                        {--close-dialogs : Также закрывать диалоги}
                        {--force : Без подтверждения}
                        {--detailed : Детальный лог по каждому заказу}';

    protected $description = 'Закрывает незакрытые заказы и начисляет реферальные бонусы';

    public function __construct(
        private ReferralService $referralService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantSlug = $this->option('tenant');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $dryRun = $this->option('dry-run');
        $closeDialogs = $this->option('close-dialogs');
        $force = $this->option('force');
        $verbose = $this->option('detailed');

        // 1. Поиск тенанта
        $tenant = null;
        if ($tenantSlug) {
            $tenant = Tenant::where('slug', $tenantSlug)->first();
            if (!$tenant) {
                $this->error("❌ Тенант '{$tenantSlug}' не найден");
                return Command::FAILURE;
            }
            app()->instance('tenant', $tenant);
            $this->info("🏢 Тенант: {$tenant->name}");
        }

        // 2. Получаем заказы
        $query = Order::query()
            ->with(['tenantUser', 'referralRewards']) // 🆕 Предзагрузка!
            ->whereNotIn('status', [
                OrderStatusEnum::Decline->value,
                OrderStatusEnum::Completed->value,
            ]);

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $totalOrders = $query->count();

        if ($totalOrders === 0) {
            $this->info('✅ Нет заказов для закрытия');
            return Command::SUCCESS;
        }

        $this->info("📦 Найдено заказов: {$totalOrders}");

        // 🆕 Показываем детальную разбивку
        $this->showOrderBreakdown($query->clone());

        if ($dryRun) {
            $this->warn('🔍 DRY-RUN режим');
        } elseif (!$force && !$this->confirm("Закрыть {$totalOrders} заказов?", false)) {
            return Command::SUCCESS;
        }

        // 3. Обработка
        $stats = [
            'closed' => 0,
            'new_rewards' => 0,          // Новые бонусы (начислены сейчас)
            'existing_rewards' => 0,     // Уже были начислены ранее
            'no_referrer' => 0,          // У покупателя нет реферера
            'no_buyer' => 0,             // Нет покупателя
            'zero_amount' => 0,          // Сумма заказа = 0
            'dialogs_closed' => 0,
            'errors' => 0,
            'total_rewards_amount' => 0,
        ];

        $bar = $this->output->createProgressBar($totalOrders);
        $bar->start();

        $query->clone()->chunkById(50, function ($orders) use (
            &$stats, $bar, $dryRun, $closeDialogs, $tenant, $verbose
        ) {
            foreach ($orders as $order) {
                $result = $this->closeOrder($order, $dryRun, $closeDialogs, $tenant, $verbose);

                $stats['closed'] += $result['closed'] ? 1 : 0;
                $stats['new_rewards'] += $result['new_rewards_count'];
                $stats['existing_rewards'] += $result['existing_rewards_count'];
                $stats['no_referrer'] += $result['no_referrer'] ? 1 : 0;
                $stats['no_buyer'] += $result['no_buyer'] ? 1 : 0;
                $stats['zero_amount'] += $result['zero_amount'] ? 1 : 0;
                $stats['dialogs_closed'] += $result['dialog_closed'] ? 1 : 0;
                $stats['errors'] += $result['error'] ? 1 : 0;
                $stats['total_rewards_amount'] += $result['new_rewards_amount'];

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // 4. Статистика
        $this->info('📊 Результаты:');
        $this->table(
            ['Показатель', 'Значение'],
            [
                ['✅ Закрыто заказов', $stats['closed']],
                ['', ''],
                ['💰 НОВЫХ бонусов начислено', $stats['new_rewards']],
                ['💵 Сумма новых бонусов', number_format($stats['total_rewards_amount'], 2) . ' ₽'],
                ['', ''],
                ['📋 Уже были начислены ранее', $stats['existing_rewards']],
                ['👤 Нет реферера у покупателя', $stats['no_referrer']],
                ['❓ Нет покупателя', $stats['no_buyer']],
                ['💸 Сумма заказа = 0', $stats['zero_amount']],
                ['', ''],
                ['💬 Закрыто диалогов', $stats['dialogs_closed']],
                ['❌ Ошибок', $stats['errors']],
            ]
        );

        // 🆕 Анализ причин отсутствия бонусов
        if ($stats['new_rewards'] === 0 && $stats['closed'] > 0) {
            $this->newLine();
            $this->warn('⚠️ Бонусы не начислены. Возможные причины:');

            if ($stats['existing_rewards'] > 0) {
                $this->line("   • {$stats['existing_rewards']} заказов уже имели бонусы (начислены ранее)");
            }
            if ($stats['no_referrer'] > 0) {
                $this->line("   • {$stats['no_referrer']} покупателей не имеют реферера");
            }
            if ($stats['no_buyer'] > 0) {
                $this->line("   • {$stats['no_buyer']} заказов без привязанного покупателя");
            }
            if ($stats['zero_amount'] > 0) {
                $this->line("   • {$stats['zero_amount']} заказов с суммой 0 ₽");
            }
        }

        $this->newLine();
        $this->info('🎉 Готово!');

        return Command::SUCCESS;
    }

    /**
     * 🆕 Разбор заказов по статусам и наличию бонусов
     */
    private function showOrderBreakdown($query): void
    {
        $orders = $query->get();

        $byStatus = [];
        $withRewards = 0;
        $withoutRewards = 0;
        $withReferrer = 0;
        $withoutReferrer = 0;

        foreach ($orders as $order) {
            $statusName = $this->getStatusName($order->status);
            $byStatus[$statusName] = ($byStatus[$statusName] ?? 0) + 1;

            if ($order->referralRewards->count() > 0) {
                $withRewards++;
            } else {
                $withoutRewards++;
            }

            if ($order->tenantUser && $order->tenantUser->referred_by) {
                $withReferrer++;
            } else {
                $withoutReferrer++;
            }
        }

        $this->newLine();
        $this->info('📈 Анализ заказов:');

        // По статусам
        $statusRows = collect($byStatus)->map(fn($count, $status) => [$status, $count])->values()->toArray();
        $this->table(['Статус', 'Количество'], $statusRows);

        // По бонусам
        $this->table(
            ['Проверка', 'Есть', 'Нет'],
            [
                ['Реферальные бонусы', $withRewards, $withoutRewards],
                ['Реферер у покупателя', $withReferrer, $withoutReferrer],
            ]
        );

        if ($withoutRewards === 0) {
            $this->comment('💡 Все заказы уже имеют начисленные бонусы');
        }
        if ($withoutReferrer > 0) {
            $this->comment("💡 У {$withoutReferrer} заказов покупатели без реферера — бонусы не начислятся");
        }
    }

    /**
     * Закрыть один заказ
     */
    private function closeOrder(Order $order, bool $dryRun, bool $closeDialogs, ?Tenant $tenant, bool $verbose): array
    {
        $result = [
            'closed' => false,
            'new_rewards_count' => 0,
            'new_rewards_amount' => 0,
            'existing_rewards_count' => 0,
            'dialog_closed' => false,
            'no_referrer' => false,
            'no_buyer' => false,
            'zero_amount' => false,
            'error' => null,
        ];

        // Переключаем tenant
        if (!$tenant && $order->tenant) {
            app()->instance('tenant', $order->tenant);
        }

        // 🆕 Считаем бонусы ДО обновления
        $rewardsBefore = $order->referralRewards->count();
        $amountBefore = $order->referralRewards->sum('amount');
        $result['existing_rewards_count'] = $rewardsBefore;

        // 🆕 Диагностика
        $buyer = $order->tenantUser;
        if (!$buyer) {
            $result['no_buyer'] = true;
            if ($verbose) {
                $this->newLine();
                $this->comment("   Заказ #{$order->id}: нет покупателя");
            }
        } elseif (!$buyer->referred_by) {
            $result['no_referrer'] = true;
            if ($verbose) {
                $this->newLine();
                $this->comment("   Заказ #{$order->id}: покупатель #{$buyer->id} не имеет реферера");
            }
        }

        if (($order->summary_price ?? 0) <= 0) {
            $result['zero_amount'] = true;
            if ($verbose) {
                $this->newLine();
                $this->comment("   Заказ #{$order->id}: сумма = 0");
            }
        }

        if ($dryRun) {
            $result['closed'] = true;
            return $result;
        }

        try {
            // Обновляем статус (сработает Observer)
            $order->update(['status' => OrderStatusEnum::Completed->value]);

            // 🆕 ВАЖНО: перезагружаем модель, чтобы получить свежие связи
            $order->refresh();
            $order->load('referralRewards');

            // Считаем бонусы ПОСЛЕ обновления
            $rewardsAfter = $order->referralRewards->count();
            $amountAfter = $order->referralRewards->sum('amount');

            $result['closed'] = true;
            $result['new_rewards_count'] = max(0, $rewardsAfter - $rewardsBefore);
            $result['new_rewards_amount'] = max(0, $amountAfter - $amountBefore);

            if ($verbose && $result['new_rewards_count'] > 0) {
                $this->newLine();
                $this->info("   ✅ Заказ #{$order->id}: начислено {$result['new_rewards_count']} бонусов на " .
                    number_format($result['new_rewards_amount'], 2) . " ₽");
            }

            // Закрываем диалог
            if ($closeDialogs && $order->dialog_id) {
                $result['dialog_closed'] = $this->closeDialog($order);
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error("Ошибка заказа #{$order->id}: " . $e->getMessage());
        }

        return $result;
    }

    private function closeDialog(Order $order): bool
    {
        try {
            $dialog = TenantDialog::find($order->dialog_id);
            if (!$dialog || $dialog->is_closed) return false;

            $dialog->update(['is_closed' => true]);
            TenantMessage::create([
                'dialog_id' => $dialog->id,
                'tenant_id' => $order->tenant_id,
                'tenant_user_id' => null,
                'message' => "Заказ #{$order->id} завершён. Диалог закрыт.",
                'is_read' => true,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getStatusName(int $status): string
    {
        return match($status) {
            OrderStatusEnum::NewOrder->value => '🆕 Новый',
            OrderStatusEnum::InDelivery->value => '🚚 В доставке',
            OrderStatusEnum::Completed->value => '✅ Завершён',
            OrderStatusEnum::Decline->value => '❌ Отменён',
            OrderStatusEnum::ReadyForDelivery->value => '📦 Готов к доставке',
            OrderStatusEnum::StartsCooking->value => '👨‍🍳 Готовится',
            default => "❓ ({$status})",
        };
    }
}
