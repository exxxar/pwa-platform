<?php

return [
    // --- Существующие параметры ---
    'is_disabled' => false,
    'has_booking' => false,
    'is_edit_mode' => false,
    'default_theme_scheme' => false, // или null, в зависимости от вашей логики
    'disabled_text' => "Магазин временно не работает",
    'main_menu_btn' => 'К магазинам',
    'shop_display_type' => 0,
    'is_product_list' => false,
    'need_hide_disabled_products' => true,
    'shop_coords' => '0,0',
    'pick_up_type' => 0,
    'schedule' => [],
    'map_tiler' => null,

    // --- 🆕 ДОБАВЛЕНО: Основные параметры ---
    'can_buy_after_closing' => false,

    // --- 🆕 ДОБАВЛЕНО: Способы получения ---
    'allow_delivery' => true,
    'allow_pickup' => true,

    // --- 🆕 ДОБАВЛЕНО: Зоны доставки и сервисы (массивы) ---
    'delivery_zones' => [],
    'delivery_services' => [],

    // --- 🆕 ДОБАВЛЕНО: Теги ---
    'venue_tags' => '',

    // --- 🆕 ДОБАВЛЕНО: Детали доставки ---
    'min_price' => 0,
    'delivery_price_text' => '',
    'need_automatic_delivery_request' => false,
    'min_base_delivery_price' => 0,
    'price_per_km' => 0,
    'free_shipping_starts_from' => 0,
    'address' => '',
    'nearest_cities' => '',

    // --- 🆕 ДОБАВЛЕНО: Оплата ---
    'payment_info' => '',
    'need_pay_after_call' => false,
    'can_use_cash' => true,
    'can_use_qr' => true,
    'sbp_banks' => [], // Можно инициализировать конкретными банками, например: ['tinkoff' => ['enabled' => false, 'terminal_key' => '', 'terminal_password' => '', 'tax' => 'osn', 'vat' => 'none']]

    // --- 🆕 ДОБАВЛЕНО: Секции корзины ---
    'need_promo_code' => true,
    'need_bonuses_section' => true,
    'need_person_counter' => false,
    'need_health_restrictions' => false,

    // --- 🆕 ДОБАВЛЕНО: Контактное лицо (Менеджер) ---
    'manager' => [
        'name' => '',
        'phone' => '',
        'email' => '',
        'social_link' => '',
    ],
];
