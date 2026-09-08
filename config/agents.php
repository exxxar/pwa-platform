<?php
// config/agents.php

return [
    // Минимальная сумма выплаты
    'min_payout' => env('AGENT_MIN_PAYOUT', 1000),

    // Бонус за реферала
    'referral_bonus' => env('AGENT_REFERRAL_BONUS', 500),

    // Процент агента от продажи
    'commission_rate' => env('AGENT_COMMISSION_RATE', 0.30), // 30%

    // Максимальный размер файла документов (в байтах)
    'max_document_size' => 10 * 1024 * 1024, // 10 МБ

    // Допустимые форматы документов
    'allowed_document_types' => ['pdf', 'jpg', 'jpeg', 'png'],
];
