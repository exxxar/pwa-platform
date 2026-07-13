<?php

namespace Database\Seeders;

use App\Models\Tenant\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Базовая конфигурация meta (оставляем как есть, она отличная)
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

        // 2. Массив тенантов с разделением по ролям
        $tenants = [
            // --- Системные / Тестовые ---
            ['slug' => 'test', 'name' => 'Test Tenant', 'app_type' => 'shop'],
            ['slug' => 'job', 'name' => 'Manager Job', 'app_type' => 'service'], // Сервис для менеджеров по продажам

            // --- Главный хаб (агрегатор) ---
            ['slug' => 'fastoran', 'name' => 'Fastoran', 'app_type' => 'shop'],

            // --- Заведения-партнёры (будут привязаны к fastoran) ---
            ['slug' => 'cheburekmi', 'name' => 'Чебурек ми', 'app_type' => 'partner'],
            ['slug' => 'donmak-pl', 'name' => 'Дон мак площадь', 'app_type' => 'partner'],
            ['slug' => 'chillsushi', 'name' => 'Чилл суши', 'app_type' => 'partner'],
            ['slug' => 'big-shashlyk', 'name' => 'Большой шашлык', 'app_type' => 'partner'],
            ['slug' => 'bigjohn', 'name' => 'Большой Джон', 'app_type' => 'partner'],
            ['slug' => 'shaurmen', 'name' => 'Шаурмен', 'app_type' => 'partner'],
            ['slug' => 'avkd-sushi', 'name' => 'AVKD суши', 'app_type' => 'partner'],
            ['slug' => 'polnaya-chasha', 'name' => 'Полная чаша', 'app_type' => 'partner'],
            ['slug' => 'donmak-kr', 'name' => 'Дон мак крытый', 'app_type' => 'partner'],
            ['slug' => 'vuper-burgers', 'name' => 'Вупер бургерс', 'app_type' => 'partner'],
            ['slug' => 'labirint', 'name' => 'Лабиринт', 'app_type' => 'partner'],
            ['slug' => 'shaurma-john', 'name' => 'Шаурма от Джона', 'app_type' => 'partner'],
            ['slug' => 'chacha-puri', 'name' => 'Чача пури', 'app_type' => 'partner'],
            ['slug' => 'schastie-est', 'name' => 'Счастье есть', 'app_type' => 'partner'],
        ];

        // 3. Формируем данные для вставки
        $insertData = [];
        foreach ($tenants as $tenant) {
            $insertData[] = [
                'uuid' => (string) Str::uuid(),
                'slug' => $tenant['slug'],
                'name' => $tenant['name'],
                'description' => 'Тенант ' . $tenant['name'],
                'image' => null,
                'icon' => null,
                'theme_color' => '#3490dc',
                'app_type' => $tenant['app_type'], // 🆕 Используем роль
                'order_channel' => 'web',
                'balance' => 1000,
                'tax_per_day' => 5,
                'meta' => json_encode($baseMeta),
                'is_active' => true,
                'welcome_message' => 'Добро пожаловать!',
                'maintenance_message' => 'Ведутся технические работы',
                'blocked_message' => 'Аккаунт заблокирован',
                'long_description' => 'Описание тенанта ' . $tenant['name'],
                'short_description' => 'Короткое описание',
                'cashback_fire_percent' => 10,
                'cashback_fire_period' => 7,
                'vk_shop_link' => 'https://vk.com/test_shop',
                'level_1' => 1.5,
                'level_2' => 3.0,
                'level_3' => 5.0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 4. Вставляем все тенанты (игнорируем дубликаты по slug, если уже есть)
        foreach ($insertData as $data) {
            Tenant::updateOrCreate(['slug' => $data['slug']], $data);
        }

        // Вызов остальных сидеров
        $this->command->info("✅ Тенанты созданы/обновлены.");
        $this->call(PartnerSeeder::class);
    }
}
