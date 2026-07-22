<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Карта разрешений (Permissions Map)
    |--------------------------------------------------------------------------
    |
    | Единый источник истины для всех прав доступа в системе.
    | - Ключ (key): используется в коде (Vue-компоненты, middleware, проверки).
    | - Значение (value): человекопонятное название для интерфейса админки.
    |
    */

    'map' => [
        'manage_all'          => 'Полный доступ (Суперадмин)',
        'manage_products'     => 'Товары и категории',
        'manage_orders'       => 'Заказы',
        'manage_users'        => 'Клиенты и пользователи',
        'manage_partners'     => 'Партнёры',
        'manage_broadcasts'   => 'Рассылки',
        'manage_stories'      => 'Истории (Stories)',
        'manage_tables'       => 'Столики и бронирование',
        'manage_promos'       => 'Промокоды и скидки',
        'manage_achievements' => 'Достижения и система наград', // 🆕 Добавлено
        'manage_crm'          => 'CRM и воронки',
        'view_statistics'     => 'Статистика и аналитика',
        'manage_landing'      => 'Лендинг',
        'manage_taplink'      => 'Tap-link',
        'manage_utm'          => 'UTM-метки',
        'manage_invoices'     => 'Счета',
        'manage_settings'     => 'Настройки магазина',
        'manage_chat'         => 'Чат с клиентами',
    ],

    /*
    |--------------------------------------------------------------------------
    | Шаблоны ролей (Role Templates)
    |--------------------------------------------------------------------------
    |
    | Удобный способ задать набор прав для стандартных ролей.
    | Используйте этот массив в вашем DatabaseSeeder для автоматического
    | создания ролей при развертывании проекта.
    |
    */

    'default_roles' => [
        'super_admin' => [
            'manage_all',
        ],
        'manager' => [
            'manage_orders',
            'manage_users',
            'manage_chat',
            'manage_achievements', // Менеджер может видеть прогресс и выдавать награды вручную
            'view_statistics',
        ],
        'content_manager' => [
            'manage_products',
            'manage_stories',
            'manage_landing',
            'manage_taplink',
            'manage_achievements', // Контент-менеджер создает и редактирует условия ачивок
        ],
        'marketer' => [
            'manage_broadcasts',
            'manage_promos',
            'manage_utm',
            'manage_crm',
            'view_statistics',
        ],
    ],
];
