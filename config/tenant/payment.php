<?php

return [
    'payment_info' => null,
    'need_pay_after_call' => false,
    'payment_token' => null,

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
