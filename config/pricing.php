<?php

return [
    'plans' => [
        [
            'title' => 'Старт',
            'slug' => 'start',
            'description' => 'Для небольших магазинов и начинающих предпринимателей',
            'price' => 990,
            'old_price' => null,
            'period' => 'month',
            'price_note' => 'или 9 900 ₽/год (экономия 17%)',
            'features' => [
                'До 100 товаров',
                'Базовая корзина и оплата',
                'Уведомления о заказах',
                'Базовая аналитика',
                'Email-поддержка',
            ],
            'badge' => null,
            'is_featured' => false,
            'button_text' => 'Выбрать',
            'button_url' => '#cta',
        ],

        [
            'title' => 'Бизнес',
            'slug' => 'business',
            'description' => 'Для растущих магазинов с активной клиентской базой',
            'price' => 4990,
            'old_price' =>5990,
            'period' => 'month',
            'price_note' => 'или 29 900 ₽/год (экономия 17%)',
            'features' => [
                'Безлимит товаров',
                'Все виды оплаты (СБП, карты)',
                'Кэшбэк и бонусные программы',
                'Встроенный чат с клиентами',
                'CRM и Kanban-доска',
                'Приоритетная поддержка 24/7',
            ],
            'badge' => 'Популярный',
            'is_featured' => true,
            'button_text' => 'Выбрать',
            'button_url' => '#cta',
        ],

        [
            'title' => 'Премиум',
            'slug' => 'premium',
            'description' => 'Для крупных сетей и франшиз с индивидуальными требованиями',
            'price' => 7990,
            'old_price' => null,
            'period' => 'month',
            'price_note' => 'или 79 900 ₽/год (экономия 17%)',
            'features' => [
                'Всё из тарифа "Бизнес"',
                'Неограниченно партнёров',
                'Белый лейбл (свой домен)',
                'API-интеграции',
                'Персональный менеджер',
                'SLA 99.9%',
            ],
            'badge' => null,
            'is_featured' => false,
            'button_text' => 'Выбрать',
            'button_url' => '#cta',
        ],
    ],

    // Настройки отображения
    'display' => [
        'show_old_price' => true,
        'show_price_note' => true,
        'show_badge' => true,
    ],
];
