<?php

namespace App\Console\Commands\Referrals;

use App\Enums\OrderStatusEnum;
use App\Models\Tenant\Order;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Services\ReferralService;
use Faker\Factory as Faker;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTestReferrals extends Command
{
    protected $signature = 'test:referrals
                            {referral_code : Реферальный код пользователя-реферера}
                            {count=10 : Количество тестовых пользователей для создания}
                            {--tenant= : Slug тенанта (например: coffee-place) или его ID}
                            {--with-orders : Создать тестовые заказы для проверки начисления бонусов}
                            {--order-amount=1000 : Сумма каждого тестового заказа}
                            {--status=0 : Статус заказа (0=новый, 1=в доставке, 2=завершён)}
                            {--show-codes : Показать реферальные коды всех созданных пользователей}';

    protected $description = 'Создаёт тестовых пользователей и привязывает их к рефереру по коду';

    private ReferralService $referralService;
    private $faker;

    public function __construct(ReferralService $referralService)
    {
        parent::__construct();
        $this->referralService = $referralService;
        $this->faker = Faker::create('ru_RU');
    }

    public function handle(): int
    {
        $referralCode = $this->argument('referral_code');
        $count = (int) $this->argument('count');
        $tenantIdentifier = $this->option('tenant');
        $withOrders = $this->option('with-orders');
        $orderAmount = (float) $this->option('order-amount');
        $orderStatus = (int) $this->option('status');
        $showCodes = $this->option('show-codes');

        // ==========================================
        // 1. Валидация входных данных
        // ==========================================
        if ($count <= 0 || $count > 1000) {
            $this->error('❌ Количество должно быть от 1 до 1000');
            return Command::FAILURE;
        }

        // ==========================================
        // 2. Поиск тенанта по slug или ID
        // ==========================================
        $tenant = $this->findTenant($tenantIdentifier);
        if (!$tenant) {
            $this->error('❌ Тенант не найден');
            $this->showAvailableTenants();
            return Command::FAILURE;
        }

        // 🆕 КРИТИЧЕСКИ ВАЖНО: Регистрируем тенант в контейнере
        app()->instance('tenant', $tenant);

        $this->info("🏢 Тенант: {$tenant->name}");
        $this->info("   ├─ Slug: {$tenant->slug}");
        $this->info("   └─ ID: {$tenant->id}");
        $this->newLine();

        // ==========================================
        // 3. Поиск реферера
        // ==========================================
        $referrer = TenantUser::where('referral_code', $referralCode)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$referrer) {
            $this->error("❌ Реферер с кодом '{$referralCode}' не найден в тенанте '{$tenant->slug}'");
            $this->showAvailableReferralCodes($tenant);
            return Command::FAILURE;
        }

        $referrer->load('tenant');

        $this->info("👤 Реферер: {$referrer->name} (ID: {$referrer->id})");
        $this->info("   ├─ Код: {$referrer->referral_code}");
        $this->info("   ├─ Рефералов: {$referrer->referrals_count}");
        $this->info("   └─ Кэшбэк: " . number_format($referrer->cashback_balance ?? 0, 2));
        $this->newLine();

        // ==========================================
        // 4. Подтверждение
        // ==========================================
        $confirmMessage = "Создать {$count} тестовых пользователей для реферера '{$referrer->name}'";
        if ($withOrders) {
            $confirmMessage .= " с заказами по {$orderAmount} ₽";
        }
        $confirmMessage .= "?";

        if (!$this->confirm($confirmMessage, true)) {
            $this->warn('⚠️ Отменено пользователем');
            return Command::SUCCESS;
        }

        // ==========================================
        // 5. Создание тестовых пользователей
        // ==========================================
        $this->info("🚀 Начинаем создание {$count} тестовых пользователей...");
        $this->newLine();

        $created = 0;
        $failed = 0;
        $ordersCreated = 0;

        // 🆕 Массив для хранения созданных пользователей
        $createdUsers = [];

        $initialReferralsCount = $referrer->referrals_count ?? 0;
        $initialCashback = $referrer->cashback_balance ?? 0;
        $initialEarnings = $referrer->total_referral_earnings ?? 0;

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            try {
                $newUser = $this->createTestUser($tenant);

                $success = $this->referralService->registerReferral($newUser, $referralCode);

                if ($success) {
                    $created++;

                    // 🆕 Сохраняем данные пользователя
                    $createdUsers[] = [
                        'id' => $newUser->id,
                        'name' => $newUser->name,
                        'referral_code' => $newUser->referral_code,
                    ];

                    if ($withOrders) {
                        $this->createTestOrder($newUser, $orderAmount, $orderStatus);
                        $ordersCreated++;
                    }
                } else {
                    $failed++;
                }

                $bar->advance();

            } catch (\Exception $e) {
                $failed++;
                $bar->clear();
                $this->newLine();
                $this->error("❌ Ошибка при создании пользователя #{$i}: " . $e->getMessage());
                $bar->display();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // ==========================================
        // 6. Вывод статистики
        // ==========================================
        $this->info('✅ Статистика операции:');
        $this->table(
            ['Показатель', 'Значение'],
            [
                ['Создано пользователей', $created],
                ['Ошибок', $failed],
                ['Создано заказов', $ordersCreated],
                ['Общая сумма заказов', number_format($ordersCreated * $orderAmount, 2) . ' ₽'],
            ]
        );

        $referrer->refresh();

        $this->newLine();
        $this->info('📊 Статистика реферера:');
        $this->table(
            ['Показатель', 'До', 'После', 'Изменение'],
            [
                [
                    'Рефералы',
                    $initialReferralsCount,
                    $referrer->referrals_count,
                    '+' . ($referrer->referrals_count - $initialReferralsCount),
                ],
                [
                    'Кэшбэк',
                    number_format($initialCashback, 2) . ' ₽',
                    number_format($referrer->cashback_balance ?? 0, 2) . ' ₽',
                    '+' . number_format(($referrer->cashback_balance ?? 0) - $initialCashback, 2) . ' ₽',
                ],
                [
                    'Заработок',
                    number_format($initialEarnings, 2) . ' ₽',
                    number_format($referrer->total_referral_earnings ?? 0, 2) . ' ₽',
                    '+' . number_format(($referrer->total_referral_earnings ?? 0) - $initialEarnings, 2) . ' ₽',
                ],
            ]
        );

        // ==========================================
        // 🆕 7. Вывод созданных пользователей с кодами
        // ==========================================
        if (!empty($createdUsers)) {
            $this->newLine();
            $this->info('🔗 Созданные пользователи:');

            // Если пользователей много и флаг --show-codes не указан
            if (count($createdUsers) > 20 && !$showCodes) {
                // Показываем только первые 10 и последние 5
                $firstUsers = array_slice($createdUsers, 0, 10);
                $lastUsers = array_slice($createdUsers, -5);

                $this->table(
                    ['ID', 'Имя', 'Реферальный код'],
                    array_map(fn($u) => [$u['id'], $u['name'], $u['referral_code']], $firstUsers)
                );

                $this->comment('   ... ' . (count($createdUsers) - 15) . ' пользователей пропущено ...');
                $this->newLine();

                $this->table(
                    ['ID', 'Имя', 'Реферальный код'],
                    array_map(fn($u) => [$u['id'], $u['name'], $u['referral_code']], $lastUsers)
                );

                $this->newLine();
                $this->comment('💡 Используйте --show-codes чтобы увидеть всех ' . count($createdUsers) . ' пользователей');
            } else {
                // Показываем всех пользователей
                $this->table(
                    ['ID', 'Имя', 'Реферальный код'],
                    array_map(fn($u) => [$u['id'], $u['name'], $u['referral_code']], $createdUsers)
                );
            }

            // 🆕 Дополнительно: экспорт в файл при большом количестве
            if (count($createdUsers) > 20 && $showCodes) {
                $exportPath = storage_path('app/test_referrals_' . date('Y-m-d_H-i-s') . '.csv');
                $this->exportToCSV($createdUsers, $exportPath);
                $this->newLine();
                $this->info("💾 Список экспортирован в: {$exportPath}");
            }
        }

        if ($withOrders && $ordersCreated > 0) {
            $this->newLine();
            $statusName = $this->getOrderStatusName($orderStatus);
            $this->info("💡 Заказов создано: {$ordersCreated} (статус: {$statusName})");

            if ($orderStatus === OrderStatusEnum::Completed->value || $orderStatus === OrderStatusEnum::InDelivery->value) {
                $this->info("💰 Бонусы должны были быть начислены автоматически");
            } else {
                $this->warn("⚠️ Статус заказа не предусматривает начисления бонусов");
                $this->info("💡 Используйте --status=2 для начисления бонусов сразу");
            }
        }

        $this->newLine();
        $this->info('🎉 Готово!');

        return Command::SUCCESS;
    }

    /**
     * 🆕 Экспорт пользователей в CSV файл
     */
    private function exportToCSV(array $users, string $path): void
    {
        $fp = fopen($path, 'w');

        // Заголовки
        fputcsv($fp, ['ID', 'Имя', 'Реферальный код']);

        // Данные
        foreach ($users as $user) {
            fputcsv($fp, [$user['id'], $user['name'], $user['referral_code']]);
        }

        fclose($fp);
    }

    /**
     * Найти тенант по slug или ID
     */
    private function findTenant(?string $identifier): ?Tenant
    {
        if (empty($identifier)) {
            return Tenant::first();
        }

        $tenant = Tenant::where('slug', $identifier)->first();
        if ($tenant) {
            return $tenant;
        }

        if (is_numeric($identifier)) {
            $tenant = Tenant::find((int) $identifier);
            if ($tenant) {
                $this->warn("⚠️ Тенант найден по ID, а не по slug. Рекомендуется использовать slug.");
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Показать список доступных тенантов
     */
    private function showAvailableTenants(): void
    {
        $tenants = Tenant::select('id', 'slug', 'name')->limit(10)->get();

        if ($tenants->isEmpty()) {
            $this->warn('В системе нет ни одного тенанта');
            return;
        }

        $this->newLine();
        $this->info('📋 Доступные тенанты:');
        $this->table(
            ['ID', 'Slug', 'Название'],
            $tenants->map(fn($t) => [$t->id, $t->slug, $t->name])->toArray()
        );

        if (Tenant::count() > 10) {
            $this->comment('  ... и ещё ' . (Tenant::count() - 10) . ' тенантов');
        }

        $this->newLine();
        $this->info("💡 Используйте: php artisan test:referrals CODE --tenant=<slug>");
    }

    /**
     * Показать существующие реферальные коды
     */
    private function showAvailableReferralCodes(Tenant $tenant): void
    {
        $users = TenantUser::where('tenant_id', $tenant->id)
            ->whereNotNull('referral_code')
            ->select('id', 'name', 'referral_code')
            ->limit(5)
            ->get();

        if ($users->isEmpty()) {
            $this->warn('В этом тенанте нет пользователей с реферальными кодами');
            return;
        }

        $this->newLine();
        $this->info('📋 Примеры существующих кодов в тенанте:');
        $this->table(
            ['ID', 'Имя', 'Код'],
            $users->map(fn($u) => [$u->id, $u->name, $u->referral_code])->toArray()
        );
    }

    /**
     * Создать тестового пользователя
     */
    private function createTestUser(Tenant $tenant): TenantUser
    {
        $name = $this->faker->firstName() . ' ' . $this->faker->lastName();
        $referralCode = TenantUser::generateReferralCode();

        try {
            $email = $this->faker->unique()->safeEmail;
            $phone = $this->faker->unique()->phoneNumber;
        } catch (\OverflowException $e) {
            $this->faker->unique(true);
            $email = $this->faker->unique()->safeEmail;
            $phone = $this->faker->unique()->phoneNumber;
        }

        return TenantUser::create([
            'tenant_id' => $tenant->id,
            'name' => "Тест • {$name}",
            'uuid' => (string) Str::uuid(),
            'email' => $email,
            'phone' => $phone,
            'referral_code' => $referralCode,
            'is_active' => true,
            'cashback' => 0,
            'referrals_count' => 0,
            'total_referral_earnings' => 0,
            'meta' => [
                'test_user' => true,
                'created_by' => 'test:referrals command',
                'created_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Создать тестовый заказ
     */
    private function createTestOrder(TenantUser $user, float $amount, int $status): void
    {
        Order::create([
            'tenant_id' => $user->tenant_id,
            'tenant_user_id' => $user->id,
            'status' => $status,
            'summary_price' => $amount,
            'delivery_price' => 0,
            'final_price' => $amount,
            'delivery_type' => 'pickup',
            'payment_type' => 'cash',
            'address' => $this->faker->address,
            'comment' => 'Тестовый заказ (создан командой test:referrals)',
            'meta' => [
                'test_order' => true,
            ],
        ]);
    }

    /**
     * Получить название статуса заказа
     */
    private function getOrderStatusName(int $status): string
    {
        return match($status) {
            OrderStatusEnum::NewOrder->value => 'Новый',
            OrderStatusEnum::InDelivery->value => 'В доставке',
            OrderStatusEnum::Completed->value => 'Завершён',
            OrderStatusEnum::Decline->value => 'Отменён',
            OrderStatusEnum::ReadyForDelivery->value => 'Готов к доставке',
            OrderStatusEnum::StartsCooking->value => 'Готовится',
            default => "Неизвестный ({$status})",
        };
    }
}
