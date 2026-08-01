<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Конфигурация Tenant
    |--------------------------------------------------------------------------
    |
    | Все настройки тенанта вынесены в отдельные файлы в директории tenant/
    | Это позволяет легче поддерживать и находить нужные параметры
    |
    */

    'config_path' => base_path('config/tenant'),

    'sections' => [
        'general',
        'delivery',
        'payment',
        'referral',
        'pwa',
        'menu',
        'calculators',
        'games',
        'features',
        'kanban',
        'coffee',
        'cashback',
        'landing',
        'misc',
        'main_menu',
        'telegram'
    ],
];
