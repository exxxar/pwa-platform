<?php

namespace App\Console\Commands;

use App\Models\Tenant\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class CheckTenantBalances extends Command
{
    /**
     * Порог низкого баланса (в днях тарифа).
     * Если баланс меньше этого значения — отправляем уведомление админу.
     */
    const LOW_BALANCE_DAYS_THRESHOLD = 3;

    /**
     * Имя и сигнатура консольной команды.
     */
    protected $signature = 'tenants:check-balance
                            {--dry-run : Только проверить, не списывать и не отключать}
                            {--tenant= : Проверить только конкретного тенанта по ID}
                            {--force : Игнорировать статус is_active, проверять всех}';

    /**
     * Описание консольной команды.
     */
    protected $description = 'Проверка баланса тенантов и списание дневного тарифа';

    /**
     * Статистика выполнения команды.
     */
    private array $stats = [
        'total' => 0,
        'charged' => 0,
        'disabled' => 0,
        'already_disabled' => 0,
        'no_tariff' => 0,
        'errors' => 0,
        'total_charged_amount' => 0,
        'low_balance_warnings' => 0,
    ];

    /**
     * Список тенантов, требующих уведомления админу.
     */
    private array $notificationsQueue = [
        'disabled' => [],
        'low_balance' => [],
    ];

    /**
     * Выполнение команды.
     */
    public function handle(): int
    {
        $this->printHeader();

        // Получаем тенантов
        $tenants = $this->getTenants();
        $this->stats['total'] = $tenants->count();

        $this->info("📦 Найдено тенантов: <info>{$this->stats['total']}</info>");
        $this->newLine();

        if ($tenants->isEmpty()) {
            $this->warn('Тенанты не найдены');
            return self::SUCCESS;
        }

        // Обрабатываем каждого тенанта
        $tableData = [];
        $bar = $this->output->createProgressBar($tenants->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $bar->start();

        foreach ($tenants as $tenant) {
            $tableData[] = $this->processTenant($tenant);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Выводим таблицу результатов
        $this->table(
            ['ID', 'Slug', 'Название', 'Баланс до', 'Тариф/день', 'Баланс после', 'Статус'],
            $tableData
        );

        // Логируем сводку
        $this->printSummary();

        // TODO: Отправка уведомлений админу
        $this->processNotifications();

        $this->newLine();
        $this->info('✅ Проверка завершена успешно');

        return $this->stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Получение списка тенантов для обработки.
     */
    private function getTenants()
    {
        $query = Tenant::query();

        // Если указан конкретный тенант
        if ($tenantId = $this->option('tenant')) {
            $query->where('id', $tenantId);
        }
        // Иначе — только активные (если не --force)
        elseif (!$this->option('force')) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Обработка одного тенанта.
     */
    private function processTenant(Tenant $tenant): array
    {
        $balanceBefore = (float) $tenant->balance;
        $dailyTax = (float) $tenant->tax_per_day;
        $slug = $tenant->slug;
        $name = $tenant->name;

        try {
            // Тариф не установлен — пропускаем
            if ($dailyTax <= 0) {
                $this->stats['no_tariff']++;
                Log::channel('tenants')->info("Тенант [{$slug}] не имеет дневного тарифа, пропущен");

                return $this->buildRow($tenant, $balanceBefore, $dailyTax, $balanceBefore, '⚪ Нет тарифа');
            }

            // Уже отключен
            if (!$tenant->is_active && !$this->option('force')) {
                $this->stats['already_disabled']++;
                return $this->buildRow($tenant, $balanceBefore, $dailyTax, $balanceBefore, '🔴 Уже отключен');
            }

            // Достаточно средств — списываем
            if ($balanceBefore >= $dailyTax) {
                if (!$this->option('dry-run')) {
                    DB::transaction(function () use ($tenant, $dailyTax) {
                        $tenant->decrement('balance', $dailyTax);
                    });
                    $tenant->refresh();
                }

                $this->stats['charged']++;
                $this->stats['total_charged_amount'] += $dailyTax;

                Log::channel('tenants')->info(
                    "Тенант [{$slug}]: списано {$dailyTax}₽. " .
                    "Остаток: {$tenant->balance}₽"
                );

                // Проверяем низкий баланс
                $daysLeft = $dailyTax > 0 ? floor($tenant->balance / $dailyTax) : 0;
                $status = $this->getStatusByDaysLeft($daysLeft);

                if ($daysLeft < self::LOW_BALANCE_DAYS_THRESHOLD) {
                    $this->stats['low_balance_warnings']++;
                    $this->notificationsQueue['low_balance'][] = [
                        'tenant_id' => $tenant->id,
                        'slug' => $slug,
                        'name' => $name,
                        'balance' => $tenant->balance,
                        'days_left' => $daysLeft,
                    ];
                }

                return $this->buildRow($tenant, $balanceBefore, $dailyTax, $tenant->balance, $status);
            }

            // Недостаточно средств — отключаем
            if (!$this->option('dry-run')) {
                DB::transaction(function () use ($tenant) {
                    $tenant->update(['is_active' => false]);
                });
            }

            $this->stats['disabled']++;
            $this->notificationsQueue['disabled'][] = [
                'tenant_id' => $tenant->id,
                'slug' => $slug,
                'name' => $name,
                'balance' => $balanceBefore,
                'daily_tax' => $dailyTax,
            ];

            Log::channel('tenants')->warning(
                "Тенант [{$slug}] ОТКЛЮЧЕН: недостаточно средств. " .
                "Баланс: {$balanceBefore}₽, требуется: {$dailyTax}₽"
            );

            return $this->buildRow($tenant, $balanceBefore, $dailyTax, $balanceBefore, '🔴 ОТКЛЮЧЕН');

        } catch (Throwable $e) {
            $this->stats['errors']++;

            Log::channel('tenants')->error(
                "Ошибка обработки тенанта [{$slug}]: " . $e->getMessage(),
                ['exception' => $e]
            );

            return $this->buildRow($tenant, $balanceBefore, $dailyTax, null, '❌ Ошибка');
        }
    }

    /**
     * Формирование строки для таблицы.
     */
    private function buildRow(Tenant $tenant, float $before, float $tax, ?float $after, string $status): array
    {
        return [
            $tenant->id,
            $tenant->slug,
            \Illuminate\Support\Str::limit($tenant->name, 25),
            $this->formatMoney($before),
            $tax > 0 ? $this->formatMoney($tax) : '—',
            $after !== null ? $this->formatMoney($after) : '—',
            $status,
        ];
    }

    /**
     * Определение статуса по количеству оставшихся дней.
     */
    private function getStatusByDaysLeft(int $daysLeft): string
    {
        if ($daysLeft >= 7) {
            return "🟢 OK ({$daysLeft} дн.)";
        }
        if ($daysLeft >= self::LOW_BALANCE_DAYS_THRESHOLD) {
            return "🟡 Внимание ({$daysLeft} дн.)";
        }
        return "🟠 Критично ({$daysLeft} дн.)";
    }

    /**
     * Вывод сводки в консоль и лог.
     */
    private function printSummary(): void
    {
        $summary = [
            'Всего обработано' => $this->stats['total'],
            'Успешно списано' => $this->stats['charged'],
            'Отключено (недостаточно средств)' => $this->stats['disabled'],
            'Уже отключено ранее' => $this->stats['already_disabled'],
            'Без тарифа (пропущено)' => $this->stats['no_tariff'],
            'Предупреждений (низкий баланс)' => $this->stats['low_balance_warnings'],
            'Ошибок обработки' => $this->stats['errors'],
            'Общая сумма списаний' => $this->formatMoney($this->stats['total_charged_amount']),
        ];

        Log::channel('tenants')->info('📊 Сводка проверки балансов', $summary);

        $this->newLine();
        $this->info('📊 Сводка по результатам:');
        foreach ($summary as $label => $value) {
            $this->line("  • {$label}: <info>{$value}</info>");
        }
    }

    /**
     * Вывод заголовка команды.
     */
    private function printHeader(): void
    {
        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════╗');
        $this->info('║   🚀 Проверка балансов и списание тарифов       ║');
        $this->info('╚═══════════════════════════════════════════════════╝');
        $this->info('⏰ Время: ' . Carbon::now()->format('d.m.Y H:i:s'));

        if ($this->option('dry-run')) {
            $this->warn('⚠️  Режим DRY-RUN: изменения НЕ будут сохранены');
        }
        $this->newLine();
    }

    /**
     * ==========================================
     * TODO: БЛОК УВЕДОМЛЕНИЙ АДМИНУ
     * ==========================================
     *
     * Здесь нужно реализовать отправку уведомлений.
     *
     * Варианты реализации:
     *
     * 1. Email-уведомления через Laravel Notifications:
     *    - Создать Notification-классы: TenantDisabledAlert, LowBalanceAlert, DailyReport
     *    - Отправлять на email админа из config('app.admin_email')
     *
     * 2. Telegram-бот для админов:
     *    - Использовать пакет notification-channel/telegram
     *    - Отправлять в специальный чат админов
     *
     * 3. Slack / Discord webhook:
     *    - Отправлять в канал #billing-alerts
     *
     * 4. Push-уведомления в админку:
     *    - Сохранять в таблицу admin_notifications
     *    - Показывать в админ-панели
     *
     * 5. SMS для критических случаев (отключение крупных клиентов):
     *    - Использовать сервисы типа SMS.ru, Twilio
     */
    private function processNotifications(): void
    {
        $hasDisabled = count($this->notificationsQueue['disabled']) > 0;
        $hasLowBalance = count($this->notificationsQueue['low_balance']) > 0;

        if (!$hasDisabled && !$hasLowBalance) {
            return;
        }

        $this->newLine();
        $this->warn('🔔 Обнаружены события, требующие уведомления администратора:');

        // ========================================
        // TODO: Уведомление об отключенных тенантах
        // ========================================
        if ($hasDisabled) {
            $this->warn("   🔴 Отключено тенантов: " . count($this->notificationsQueue['disabled']));

            // TODO: Реализовать отправку
            // Пример:
            // foreach ($this->notificationsQueue['disabled'] as $tenant) {
            //     Notification::route('mail', config('app.admin_email'))
            //         ->notify(new TenantDisabledAlert($tenant));
            //
            //     TelegramNotification::send(
            //         config('services.telegram.admin_chat_id'),
            //         "🔴 Тенант [{$tenant['slug']}] отключен из-за нехватки средств"
            //     );
            // }
        }

        // ========================================
        // TODO: Уведомление о низком балансе
        // ========================================
        if ($hasLowBalance) {
            $this->warn("   🟠 Тенантов с низким балансом: " . count($this->notificationsQueue['low_balance']));

            // TODO: Реализовать отправку
            // Пример:
            // Notification::route('mail', config('app.admin_email'))
            //     ->notify(new LowBalanceAlert($this->notificationsQueue['low_balance']));
        }

        // ========================================
        // TODO: Ежедневная сводка админу
        // ========================================
        // Notification::route('mail', config('app.admin_email'))
        //     ->notify(new DailyBalanceReport($this->stats));

        $this->newLine();
        $this->warn('💡 Реализуйте метод processNotifications() для отправки реальных уведомлений');
    }

    /**
     * Форматирование суммы в деньги.
     */
    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, '.', ' ') . ' ₽';
    }
}
