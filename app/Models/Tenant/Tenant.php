<?php

namespace App\Models\Tenant;

use App\Enums\IntegrationTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Tenant extends Model
{
    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'short_name',
        'description',
        'icon',
        'theme_color',
        'background_color',
        'app_type',
        'meta'
    ];

    protected $casts = [
        "meta" => "array"
    ];

    protected $appends = ['settings', 'topics'];
    protected $with = ['partners'];

    public function pages()
    {
        return $this->hasMany(TenantPage::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function users()
    {
        return $this->hasMany(TenantUser::class);
    }

    public function roles()
    {
        return $this->hasMany(TenantRole::class);
    }

    public function permissions()
    {
        return $this->hasMany(TenantPermission::class);
    }

    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    public function partners(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }


    public function integrationsByType(IntegrationTypeEnum $type)
    {
        return $this->integrations()
            ->where('type', $type->value)
            ->where('is_active', true);
    }

    public function __call($method, $args)
    {
        $type = IntegrationTypeEnum::fromMethod($method);

        if ($type !== null) {
            return $this->integrationsByType($type);
        }

        return parent::__call($method, $args);
    }

    public static function defaultSettings(): array
    {
        return [
            // ==========================================
            // ОСНОВНЫЕ
            // ==========================================
            'is_disabled' => false,
            'is_edit_mode' => false,
            'disabled_text' => "Магазин временно не работает",
            'main_menu_btn' => 'К магазинам',
            'shop_display_type' => 0,
            'is_product_list' => false,
            'need_hide_disabled_products' => true,
            'shop_coords' => '0,0',
            'pick_up_type' => 0,
            'schedule' => [],
            'map_tiler' => null,
            'kanban' => [
                'enabled' => false,
                'is_active' => false,
                'base_url' => 'https://crm.mypwa.ru/api/v1',
                'token' => null,
                'board_uuid' => null,
                'order_thread' => 0,
                'auto_create_client' => true,
            ],
            'pwa' => [
                'name' => null,              // Название приложения (если null — берётся tenant->name)
                'short_name' => null,        // Короткое название
                'description' => null,       // Описание для манифеста
                'theme_color' => '#ff8a00',  // Цвет темы
                'background_color' => '#ffffff', // Фоновый цвет
                'orientation' => 'portrait', // portrait | landscape | any
                'display' => 'standalone',   // standalone | fullscreen | minimal-ui | browser
                'lang' => 'ru',
                'categories' => ['shopping', 'food', 'business'],

                // 🆕 Иконки (пути к файлам в storage)
                'icons' => [
                    'icon_192' => null,
                    'icon_512' => null,
                    'icon_192_maskable' => null,
                    'icon_512_maskable' => null,
                ],

                // 🆕 Скриншоты
                'screenshots' => [
                    'mobile' => null,   // 375x667
                    'desktop' => null,  // 1920x1080
                ],

                // 🆕 Шорткаты
                'shortcuts' => [
                    'menu' => [
                        'enabled' => true,
                        'name' => 'Меню',
                        'short_name' => 'Меню',
                        'url' => '/pwa/#/menu',
                        'icon' => null,
                    ],
                    'cart' => [
                        'enabled' => true,
                        'name' => 'Корзина',
                        'short_name' => 'Корзина',
                        'url' => '/pwa/#/cart',
                        'icon' => null,
                    ],
                    'cashback' => [
                        'enabled' => true,
                        'name' => 'Кэшбэк',
                        'short_name' => 'Кэшбэк',
                        'url' => '/pwa/#/cashback',
                        'icon' => null,
                    ],
                    'wheel' => [
                        'enabled' => true,
                        'name' => 'Колесо',
                        'short_name' => 'Колесо',
                        'url' => '/pwa/#/wheel-classic',
                        'icon' => null,
                    ],
                ],
            ],
            // ==========================================
            // ДОСТАВКА
            // ==========================================
            'delivery' => [
                'min_base_delivery_price' => 0,
                'price_per_km' => 80,
                'min_price' => 80,
                'free_shipping_from' => 0,
                'delivery_price_text' => null,
                'need_automatic_delivery_request' => true,
                'need_hide_delivery_period' => false,
            ],

            // ==========================================
            // ПЛАТЕЖИ
            // ==========================================
            'payment_info' => null,
            'need_pay_after_call' => false,
            'payment_token' => null,

            // ==========================================
            // СЕКЦИИ КОРЗИНЫ
            // ==========================================
            'cart_sections' => [
                'need_promo_code' => true,
                'need_bonuses_section' => true,
                'need_person_counter' => true,
                'need_health_restrictions' => true,
            ],

            // ==========================================
            // ФИЧИ
            // ==========================================
            'features' => [
                'can_use_sbp' => false,
                'can_use_card' => true,
                'can_use_cash' => true,
                'can_use_booking' => false,
                'can_buy_after_closing' => false,
            ],

            // ==========================================
            // 🆕 ПУНКТЫ МЕНЮ
            // ==========================================
            'menu_items' => [
                'catalog' => ['is_visible' => true, 'title' => 'Каталог', 'icon' => 'fa-store', 'order' => 1],
                'grocery_order' => ['is_visible' => true, 'title' => 'Заказать продукты', 'icon' => 'fa-leaf', 'order' => 2],
                'food_calculator' => ['is_visible' => true, 'title' => 'Собери сам', 'icon' => 'fa-hive', 'order' => 3],
                'cart' => ['is_visible' => true, 'title' => 'Корзина', 'icon' => 'fa-cart-shopping', 'order' => 4],
                'orders' => ['is_visible' => true, 'title' => 'Мои заказы', 'icon' => 'fa-bag-shopping', 'order' => 5],
                'cashback' => ['is_visible' => true, 'title' => 'Мои бонусы', 'icon' => 'fa-coins', 'order' => 6],
                'games' => ['is_visible' => true, 'title' => 'Бонус-игры', 'icon' => 'fa-dice', 'order' => 7],
                'cashback_shop' => ['is_visible' => true, 'title' => 'Магазин бонусов', 'icon' => 'fa-shirt', 'order' => 8],
                'profile' => ['is_visible' => true, 'title' => 'Профиль', 'icon' => 'fa-user', 'order' => 9],
                'chat' => ['is_visible' => true, 'title' => 'Чат поддержки', 'icon' => 'fa-comments', 'order' => 10],
                'feedback' => ['is_visible' => true, 'title' => 'Обратная связь', 'icon' => 'fa-comment-dots', 'order' => 11],
            ],

            // ==========================================
            // 🆕 КАЛЬКУЛЯТОРЫ ЕДЫ
            // ==========================================
            'food_calculators' => [
                'pizza' => [
                    'is_visible' => true, 'title' => 'Калькулятор пиццы',
                    'description' => 'Собери свою идеальную пиццу из свежих ингредиентов',
                    'emoji' => '🍕', 'iconBg' => 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)',
                    'gradient' => 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 50%, #c0392b 100%)',
                    'ingredientsCount' => 25, 'time' => '20-30 мин', 'badge' => 'popular',
                    'route' => 'PizzaCalculator', 'category' => 'main',
                ],
                'coffee' => [
                    'is_visible' => true, 'title' => 'Калькулятор кофе',
                    'description' => 'Выбери зёрна, молоко и сиропы для идеального кофе',
                    'emoji' => '☕', 'iconBg' => 'linear-gradient(135deg, #6f4e37 0%, #a0826d 100%)',
                    'gradient' => 'linear-gradient(135deg, #6f4e37 0%, #a0826d 50%, #c4a77d 100%)',
                    'ingredientsCount' => 18, 'time' => '5-10 мин', 'badge' => 'new',
                    'route' => 'CoffeeCalculator', 'category' => 'drinks',
                ],
                'waffles' => [
                    'is_visible' => true, 'title' => 'Гонконгские вафли',
                    'description' => 'Собери вафлю с начинкой и топпингами',
                    'emoji' => '🧇', 'iconBg' => 'linear-gradient(135deg, #ffd700 0%, #ff9800 100%)',
                    'gradient' => 'linear-gradient(135deg, #ffd700 0%, #ff9800 50%, #ff5722 100%)',
                    'ingredientsCount' => 20, 'time' => '10-15 мин', 'badge' => 'soon',
                    'route' => 'WafflesCalculator', 'category' => 'desserts',
                ],
                'sushi' => [
                    'is_visible' => true, 'title' => 'Суши и роллы',
                    'description' => 'Собери свои суши из свежих ингредиентов',
                    'emoji' => '🍣', 'iconBg' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                    'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 50%, #43e97b 100%)',
                    'ingredientsCount' => 30, 'time' => '25-35 мин', 'badge' => 'new',
                    'route' => 'SushiCalculator', 'category' => 'main',
                ],
                'pancakes' => [
                    'is_visible' => true, 'title' => 'Блинчики',
                    'description' => 'Выбери начинку и соусы для блинчиков',
                    'emoji' => '🥞', 'iconBg' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #ff6b6b 100%)',
                    'ingredientsCount' => 22, 'time' => '15-20 мин', 'badge' => 'soon',
                    'route' => 'PancakesCalculator', 'category' => 'desserts',
                ],
                'burger' => [
                    'is_visible' => true, 'title' => 'Бургеры',
                    'description' => 'Собери свой бургер',
                    'emoji' => '🍔', 'iconBg' => 'linear-gradient(135deg, #000000 0%, #8b0000 100%)',
                    'gradient' => 'linear-gradient(135deg, #000000 0%, #8b0000 50%, #ff0000 100%)',
                    'ingredientsCount' => 25, 'time' => '15-20 мин', 'badge' => 'new',
                    'route' => 'BurgerCalculator', 'category' => 'main',
                ],
            ],

            // ==========================================
            // 🆕 БОНУС-ИГРЫ
            // ==========================================
            'bonus_games' => [
                'wheel-of-fortune' => [
                    'is_visible' => true, 'title' => 'Колесо Фортуны',
                    'description' => 'Крути колесо и выигрывай призы каждый день!',
                    'icon' => 'fa-solid fa-dharmachakra',
                    'iconBg' => 'linear-gradient(135deg, #ffd700 0%, #ff9800 100%)',
                    'gradient' => 'linear-gradient(135deg, #9a1717 0%, #c0392b 50%, #e74c3c 100%)',
                    'prize' => 'до 1000 бонусов', 'attempts' => '1 попытка/день',
                    'badge' => 'hot', 'route' => 'WheelOfFortune', 'category' => 'daily',
                ],
                'card-game' => [
                    'is_visible' => true, 'title' => 'Карточная игра',
                    'description' => 'Выбери карту и получи гарантированный бонус!',
                    'icon' => 'fa-solid fa-layer-group',
                    'iconBg' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'prize' => 'до 500 бонусов', 'attempts' => '1 попытка/день',
                    'badge' => 'new', 'route' => 'CashbackCardGame', 'category' => 'daily',
                ],
                'scratch-card' => [
                    'is_visible' => true, 'title' => 'Скретч-карта',
                    'description' => 'Стирай защитный слой и узнай свой приз!',
                    'icon' => 'fa-solid fa-credit-card',
                    'iconBg' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    'prize' => 'до 300 бонусов', 'attempts' => '1 попытка/день',
                    'badge' => 'soon', 'route' => 'ScratchCardGame', 'category' => 'instant',
                ],
                'daily-bonus' => [
                    'is_visible' => true, 'title' => 'Ежедневный бонус',
                    'description' => 'Заходи каждый день и получай бонусы за серию!',
                    'icon' => 'fa-solid fa-calendar-check',
                    'iconBg' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                    'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                    'prize' => 'до 100 бонусов', 'attempts' => '1 попытка/день',
                    'badge' => 'new', 'route' => 'DailyBonusGame', 'category' => 'daily',
                ],
                'quiz' => [
                    'is_visible' => true, 'title' => 'Викторина',
                    'description' => 'Отвечай на вопросы и зарабатывай бонусы!',
                    'icon' => 'fa-solid fa-question',
                    'iconBg' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
                    'gradient' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
                    'prize' => 'до 400 бонусов', 'attempts' => '1 попытка/день',
                    'badge' => 'new', 'route' => 'QuizGame', 'category' => 'puzzle',
                ],
            ],

            // ==========================================
            // КОФЕЙНАЯ ПРОГРАММА
            // ==========================================
            'coffee' => [
                "max" => 7,
                "rules" => "1. За каждую покупку кофе — 1 отметка.\n2. После 7 кружек — 1 кофе бесплатно.\n3. Отметки действуют 30 дней.\n4. Бесплатный кофе нельзя обменять на деньги.",
                "enabled" => true,
            ],

            // ==========================================
            // 🆕 ПОДАРОЧНЫЙ СЕРТИФИКАТ
            // ==========================================
            'init_certificate' => [
                'title' => 'Подарочный сертификат',
                'description' => '500 рублей на CashBack',
                'amount' => 500,
                'type' => 'cashback',
                'is_active' => false,
            ],

            // ==========================================
            // 🆕 КЭШБЭК
            // ==========================================
            'max_cashback_use_percent' => 15,
            'level_1' => 0,
            'level_2' => 0,
            'level_3' => 0,
            'cashback_config' => [],
            'warnings' => [],

            // ==========================================
            // 🆕 СТОЛИКИ
            // ==========================================
            'tables' => [
                'max_tables' => 0,
                'need_table_list' => false,
            ],

            // ==========================================
            // CRM
            // ==========================================
            'crm' => [
                'is_active' => false,
                'board_uuid' => null,
                'token' => null,
            ],

            // ==========================================
            // МЕНЕДЖЕР
            // ==========================================
            'manager' => [
                'link' => null,
                'title' => 'Написать',
            ],

            // ==========================================
            // ПАРТНЁРЫ
            // ==========================================
            'partners' => [
                "is_active" => false,
                'display_self' => false,
            ],

            // ==========================================
            // ТРЕДЫ
            // ==========================================
            'threads' => [],

            // ==========================================
            // ИКОНКИ
            // ==========================================
            'icons' => [],

            // ==========================================
            // СБП
            // ==========================================
            'sbp' => [
                'sber' => [],
                'tinkoff' => [
                    'tax' => 'osn',
                    'vat' => 'none',
                    'terminal_key' => null,
                    'terminal_password' => null,
                ],
                'selected_sbp_bank' => 'tinkoff',
            ],
        ];
    }

    protected function settings(): Attribute
    {
        return Attribute::make(
            get: fn() => array_replace_recursive(
                self::defaultSettings(),
                $this->meta ?? []
            )
        );
    }

    public function topics(): Attribute
    {
        return Attribute::make(
            get: function () {
                $threads = $this->settings['threads'] ?? null;

                if (is_null($threads)) {
                    return null;
                }

                return Collection::make($threads ?? [])
                    ->mapWithKeys(function ($message) {
                        $key = $message['key'] ?? $message->key ?? null;
                        $value = $message['value'] ?? $message->value ?? null;

                        return $key ? [$key => $value] : [];
                    })
                    ->toArray();
            }
        );
    }

    /**
     * Ссылки Taplink для этого тенанта
     */
    public function tapLinks(): HasMany
    {
        return $this->hasMany(TenantTapLink::class)->orderBy('sort_order', 'asc');
    }

    /**
     * Получение только активных ссылок (удобный хелпер)
     */
    public function getActiveTapLinksAttribute()
    {
        return $this->tapLinks()->where('is_active', true)->get();
    }
}
