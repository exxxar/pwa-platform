<?php

return [
    'tables' => [
        'max_tables' => 0,
        'need_table_list' => false,
    ],

    'crm' => [
        'is_active' => false,
        'board_uuid' => null,
        'token' => null,
    ],

    'manager' => [
        'link' => null,
        'title' => 'Написать',
    ],

    'partners' => [
        'is_active' => false,
        'display_self' => false,
    ],

    'threads' => [],
    'icons' => [],

    'init_certificate' => [
        'title' => 'Подарочный сертификат',
        'description' => '500 рублей на CashBack',
        'amount' => 500,
        'type' => 'cashback',
        'is_active' => false,
    ],
];
