<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Facades\PartnerService;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        DB::table('tenants')->insert([
            'uuid' => (string)Str::uuid(),
            'slug' => 'test',
            'name' => 'Test Tenant',
            'description' => 'Тестовый тенант для разработки',
            'image' => null,
            'icon' => null,
            'theme_color' => '#3490dc',
            'app_type' => 'shop',

            'order_channel' => 'web',
            'balance' => 1000,
            'tax_per_day' => 5,
            'meta' => json_encode([
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

                'theme' => 'https://your-cashman.com/theme6.bootstrap.min.css',

                'coffee' => [],

                'kanban' => [
                    'is_active' => false,
                    'board_uuid' => null,
                    'token' => null,
                ],

                'themes' => [
                    ['href' => '/theme1.bootstrap.min.css', 'title' => 'Тема 1'],
                    ['href' => '/theme2.bootstrap.min.css', 'title' => 'Тема 2'],
                    ['href' => '/theme3.bootstrap.min.css', 'title' => 'Тема 3'],
                    ['href' => '/theme4.bootstrap.min.css', 'title' => 'Тема 4'],
                    ['href' => '/theme5.bootstrap.min.css', 'title' => 'Тема 5'],
                    ['href' => '/theme6.bootstrap.min.css', 'title' => 'Тема 6'],
                    ['href' => '/theme7.bootstrap.min.css', 'title' => 'Тема 7'],
                    ['href' => '/theme8.bootstrap.min.css', 'title' => 'Тема 8'],
                    ['href' => '/theme9.bootstrap.min.css', 'title' => 'Тема 9'],
                    ['href' => '/theme10.bootstrap.min.css', 'title' => 'Тема 10'],
                ],

                'manager' => [
                    'link' => 'https://t.me/EgorShipilov',
                    'title' => 'Написать',
                ],

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
                    'channels' => [
                        [
                            'id' => -1001947900076,
                            'link' => '@gastro_pub_yoj',
                            'title' => 'Канал Ежа',
                        ]
                    ],
                    'is_active' => true,
                ],

                'tables_variants' => [
                    [
                        'id' => 11,
                        'edit' => true,
                        'image' => '11.png',
                        'seats' => 4,
                        'number' => 1,
                        'description' => 'Прямоугольный стол с диваном на 4 мест',
                    ],
                    [
                        'id' => 11,
                        'edit' => true,
                        'image' => '11.png',
                        'seats' => 4,
                        'number' => 2,
                        'description' => 'Прямоугольный стол с диваном на 4 мест',
                    ],
                ],

                'init_certificate' => [
                    'type' => 'cashback',
                    'title' => 'Подарочный сертификат',
                    'amount' => 500,
                    'is_active' => true,
                    'description' => '500 рублей на CashBack',
                ],

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
            ]),

            'is_active' => true,

            'welcome_message' => 'Добро пожаловать!',
            'maintenance_message' => 'Ведутся технические работы',
            'blocked_message' => 'Аккаунт заблокирован',
            'long_description' => 'Это длинное описание тестового тенанта',
            'short_description' => 'Короткое описание',

            'cashback_fire_percent' => 10,
            'cashback_fire_period' => 7,

            'vk_shop_link' => 'https://vk.com/test_shop',

            'level_1' => 1.5,
            'level_2' => 3.0,
            'level_3' => 5.0,

            'created_at' => now(),
            'updated_at' => now(),
        ]);


        DB::table('tenants')->insert([
            'uuid' => (string)Str::uuid(),
            'slug' => 'fastoran',
            'name' => 'Fastoran',
            'description' => 'Тестовый тенант для разработки',
            'image' => null,
            'icon' => null,
            'theme_color' => '#3490dc',
            'app_type' => 'shop',

            'order_channel' => 'web',
            'balance' => 1000,
            'tax_per_day' => 5,
            'meta' => json_encode([
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

                'theme' => 'https://your-cashman.com/theme6.bootstrap.min.css',

                'coffee' => [],

                'kanban' => [
                    'is_active' => false,
                    'board_uuid' => null,
                    'token' => null,
                ],

                'themes' => [
                    ['href' => '/theme1.bootstrap.min.css', 'title' => 'Тема 1'],
                    ['href' => '/theme2.bootstrap.min.css', 'title' => 'Тема 2'],
                    ['href' => '/theme3.bootstrap.min.css', 'title' => 'Тема 3'],
                    ['href' => '/theme4.bootstrap.min.css', 'title' => 'Тема 4'],
                    ['href' => '/theme5.bootstrap.min.css', 'title' => 'Тема 5'],
                    ['href' => '/theme6.bootstrap.min.css', 'title' => 'Тема 6'],
                    ['href' => '/theme7.bootstrap.min.css', 'title' => 'Тема 7'],
                    ['href' => '/theme8.bootstrap.min.css', 'title' => 'Тема 8'],
                    ['href' => '/theme9.bootstrap.min.css', 'title' => 'Тема 9'],
                    ['href' => '/theme10.bootstrap.min.css', 'title' => 'Тема 10'],
                ],

                'manager' => [
                    'link' => 'https://t.me/EgorShipilov',
                    'title' => 'Написать',
                ],

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
                    'channels' => [
                        [
                            'id' => -1001947900076,
                            'link' => '@gastro_pub_yoj',
                            'title' => 'Канал Ежа',
                        ]
                    ],
                    'is_active' => true,
                ],

                'tables_variants' => [
                    [
                        'id' => 11,
                        'edit' => true,
                        'image' => '11.png',
                        'seats' => 4,
                        'number' => 1,
                        'description' => 'Прямоугольный стол с диваном на 4 мест',
                    ],
                    [
                        'id' => 11,
                        'edit' => true,
                        'image' => '11.png',
                        'seats' => 4,
                        'number' => 2,
                        'description' => 'Прямоугольный стол с диваном на 4 мест',
                    ],
                ],

                'init_certificate' => [
                    'type' => 'cashback',
                    'title' => 'Подарочный сертификат',
                    'amount' => 500,
                    'is_active' => true,
                    'description' => '500 рублей на CashBack',
                ],

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
            ]),

            'is_active' => true,

            'welcome_message' => 'Добро пожаловать!',
            'maintenance_message' => 'Ведутся технические работы',
            'blocked_message' => 'Аккаунт заблокирован',
            'long_description' => 'Это длинное описание тестового тенанта',
            'short_description' => 'Короткое описание',

            'cashback_fire_percent' => 10,
            'cashback_fire_period' => 7,

            'vk_shop_link' => 'https://vk.com/test_shop',

            'level_1' => 1.5,
            'level_2' => 3.0,
            'level_3' => 5.0,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->call(TestDataSeeder::class);
        $this->call(StoriesSeeder::class);
        $this->call(PartnerSeeder::class);

    }
}
