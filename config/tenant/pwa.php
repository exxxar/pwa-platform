<?php

return [
    'name' => null,
    'short_name' => null,
    'description' => null,
    'theme_color' => '#ff8a00',
    'background_color' => '#ffffff',
    'orientation' => 'portrait',
    'display' => 'standalone',
    'lang' => 'ru',
    'categories' => ['shopping', 'food', 'business'],

    'icons' => [
        'icon_192' => null,
        'icon_512' => null,
        'icon_192_maskable' => null,
        'icon_512_maskable' => null,
    ],

    'screenshots' => [
        'mobile' => null,
        'desktop' => null,
    ],

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
];
