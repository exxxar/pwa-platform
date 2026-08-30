<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantPermission;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantUser;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create {url : Ссылка вида donmak-uj.mypwa.ru или https://donmak-uj.mypwa.ru}';
    protected $description = 'Создание тенанта с автоматической настройкой прав, роли super_admin и созданием администратора';

    public function handle()
    {
        $url = $this->argument('url');

        // 1. Нормализация URL
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        // 2. Извлечение slug
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            $this->error("❌ Не удалось распознать URL. Проверьте корректность введенных данных.");
            return Command::FAILURE;
        }

        $parts = explode('.', $host);
        $slug = $parts[0];

        if (empty($slug) || $slug === 'www') {
            $slug = $this->ask('Не удалось автоматически определить slug. Введите slug вручную', 'new-tenant');
        }

        $this->info("🔍 Извлеченный slug: {$slug}");

        // 3. Проверка на существование
        $existingTenant = Tenant::where('slug', $slug)->first();
        if ($existingTenant) {
            if (!$this->confirm("⚠️ Тенант со slug '{$slug}' уже существует. Хотите обновить его данные и пересоздать админа при необходимости?")) {
                $this->info("Операция отменена.");
                return Command::SUCCESS;
            }
        }

        // 4. Запрос имени тенанта
        $defaultName = Str::headline($slug);
        $name = $this->ask('Введите название тенанта', $defaultName);

        // 5. Базовая конфигурация meta
        $baseMeta = [
            'sbp' => [
                'sber' => [],
                'tinkoff' => [
                    'tax' => 'osn',
                    'vat' => 'none',
                    'terminal_key' => '1739195568603',
                    'terminal_password' => '_tou#gb2VC6so^Vt',
                ],
                'selected_sbp_bank' => 'tinkoff',
            ],
            'icons' => [
                ['slug' => 'profile', 'title' => 'Профиль', 'has_icon' => true, 'image_url' => 'profile.png', 'is_visible' => true],
                ['slug' => 'shop', 'title' => 'Заказать Доставку', 'has_icon' => true, 'image_url' => 'shop.png', 'is_visible' => true],
                ['slug' => 'basket', 'title' => 'Корзина', 'has_icon' => true, 'image_url' => 'basket.png', 'is_visible' => true],
                ['slug' => 'history', 'title' => 'История заказов', 'has_icon' => true, 'image_url' => 'history.png', 'is_visible' => true],
                ['slug' => 'events', 'title' => 'Розыгрыши', 'has_icon' => true, 'image_url' => 'events.png', 'is_visible' => true],
                ['slug' => 'about', 'title' => 'О Нас & Контакты', 'has_icon' => true, 'image_url' => 'contacts.png', 'is_visible' => true],
                ['slug' => 'wheel_of_fortune_btn', 'title' => 'Колесо фортуны', 'has_icon' => true, 'image_url' => 'profile.png', 'is_visible' => true],
                ['slug' => 'friends_btn', 'title' => 'Друзья', 'has_icon' => true, 'image_url' => 'profile.png', 'is_visible' => true],
                ['slug' => 'main_menu_btn', 'title' => 'Главное меню', 'is_visible' => true, 'has_icon' => false, 'image_url' => 'booking.png'],
                ['slug' => 'booking', 'title' => 'Бронирование столика', 'image_url' => 'booking.png', 'is_visible' => true, 'has_icon' => true],
            ],
            'coffee' => [],
            'kanban' => ['is_active' => false, 'board_uuid' => null, 'token' => null],
            'manager' => ['link' => 'https://t.me/EgorShipilov', 'title' => 'Написать'],
            'interval' => 1,
            'map_tiler' => 'l7t0HU7CqsgOKgS9rtvU',
            'min_price' => 800,
            'max_tables' => 10,
            'can_use_sbp' => true,
            'is_disabled' => false,
            'can_use_card' => false,
            'can_use_cash' => false,
            'shop_coords' => '45.070734, 39.037108',
            'price_per_km' => 70,
            'min_base_delivery_price' => 100,
            'free_shipping_starts_from' => 0,
            'subscriptions' => [
                'text' => 'Подпишись на каналы ниже и получи доступ к проекту',
                'channels' => [['id' => -1001947900076, 'link' => '@gastro_pub_yoj', 'title' => 'Канал Ежа']],
                'is_active' => true,
            ],
            'tables_variants' => [
                ['id' => 11, 'edit' => true, 'image' => '11.png', 'seats' => 4, 'number' => 1, 'description' => 'Прямоугольный стол с диваном на 4 мест'],
                ['id' => 11, 'edit' => true, 'image' => '11.png', 'seats' => 4, 'number' => 2, 'description' => 'Прямоугольный стол с диваном на 4 мест'],
            ],
            'init_certificate' => ['type' => 'cashback', 'title' => 'Подарочный сертификат', 'amount' => 500, 'is_active' => true, 'description' => '500 рублей на CashBack'],
            'need_promo_code' => true,
            'need_table_list' => true,
            'need_person_counter' => true,
            'need_category_by_page' => true,
            'need_health_restrictions' => true,
            'need_prizes_from_wheel_of_fortune' => true,
            'need_hide_delivery_period' => false,
            'need_hide_disabled_products' => true,
            'need_automatic_delivery_request' => true,
            'need_pay_after_call' => false,
            'can_buy_after_closing' => false,
            'can_use_booking' => false,
            'can_work_in_marketplace' => null,
            'min_price_for_cashback' => 2000,
        ];

        $tenantData = [
            'uuid' => (string) Str::uuid(),
            'slug' => $slug,
            'name' => $name,
            'description' => 'Тенант ' . $name,
            'image' => null,
            'icon' => null,
            'theme_color' => '#3490dc',
            'app_type' => 'partner',
            'order_channel' => 'web',
            'balance' => 1000,
            'tax_per_day' => 5,
            'meta' => $baseMeta,
            'is_active' => true,
            'welcome_message' => 'Добро пожаловать!',
            'maintenance_message' => 'Ведутся технические работы',
            'blocked_message' => 'Аккаунт заблокирован',
            'long_description' => 'Описание тенанта ' . $name,
            'short_description' => 'Короткое описание',
            'cashback_fire_percent' => 10,
            'cashback_fire_period' => 7,
            'vk_shop_link' => 'https://vk.com/test_shop',
            'level_1' => 1.5,
            'level_2' => 3.0,
            'level_3' => 5.0,
        ];

        // 6. Создаем или обновляем тенанта БЕЗ вызова событий (чтобы избежать ошибки Observer с pivot)
        $this->info("💾 Сохранение данных тенанта...");
        $tenant = Tenant::withoutEvents(function () use ($slug, $tenantData) {
            return Tenant::updateOrCreate(
                ['slug' => $slug],
                $tenantData
            );
        });

        // 7. Запускаем процедуру настройки прав, ролей и админа
        $this->provisionTenant($tenant);

        $this->newLine();
        $this->info("✅ Тенант полностью готов к работе!");
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['ID', $tenant->id],
                ['UUID', $tenant->uuid],
                ['Slug', $tenant->slug],
                ['Название', $tenant->name],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * "Обустраивает" один тенант: права → роль → админ
     */
    private function provisionTenant(Tenant $tenant): void
    {
        $this->info("⚙️ Настройка прав, ролей и администратора...");

        // 1. Создаём/находим все права для этого тенанта из конфига
        $permissionsMap = config('permissions.map', []);
        $permissionIds = [];


        foreach ($permissionsMap as $name => $label) {
            $perm = TenantPermission::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                ['label' => $label]
            );
            $permissionIds[] = $perm->id;
        }

        // 2. Создаём/находим роль super_admin
        $role = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'super_admin'],
            ['label' => 'Суперадмин']
        );

        // 3. Синхронизируем права с передачей tenant_id в pivot (ИСПРАВЛЕНИЕ ОШИБКИ)
        $permissionsSyncData = [];
        foreach ($permissionIds as $permId) {
            $permissionsSyncData[$permId] = ['tenant_id' => $tenant->id];
        }
        $role->permissions()->sync($permissionsSyncData);

        // 4. Формируем email админа
        $safeSlug = Str::slug($tenant->slug ?: $tenant->name, '_');
        $adminEmail = "admin_{$safeSlug}@mypwa.ru";

        // 5. Проверяем, существует ли уже админ
        $existingAdmin = TenantUser::where('tenant_id', $tenant->id)
            ->where('email', $adminEmail)
            ->first();

        if ($existingAdmin) {
            $hasSuperAdminRole = $existingAdmin->roles()
                ->where('tenant_roles.id', $role->id)
                ->wherePivot('tenant_id', $tenant->id)
                ->exists();

            if (!$hasSuperAdminRole) {
                $existingAdmin->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
            }
            $this->info("👤 Администратор уже существует: {$adminEmail}");
            $this->info("🔑 Пароль: admin123");
            return;
        }

        // 6. Создаём нового админа
        $adminUser = TenantUser::create([
            'tenant_id' => $tenant->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Администратор',
            'email' => $adminEmail,
            'phone' => '+79494320661',
            'password' => bcrypt('admin123'), // ВАЖНО: хешируем пароль
            'is_active' => true,
            'is_vip' => true,
            'referral_code' => method_exists(TenantUser::class, 'generateReferralCode')
                ? TenantUser::generateReferralCode()
                : Str::random(8), // Фоллбек, если метода вдруг нет
        ]);

        // Привязываем роль с tenant_id в pivot
        $adminUser->roles()->attach($role->id, ['tenant_id' => $tenant->id]);

        $this->info("👤 Создан новый администратор: {$adminEmail}");
        $this->info("🔑 Пароль по умолчанию: admin123");
    }
}
