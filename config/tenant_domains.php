<?php

return [
    /*
     * Связка "кастомный домен" => "slug тенанта в базе данных"
     * Ключом является точный хост из запроса (request->getHost())
     */
    'domain_mapping' => [
        // Пример: когда приходит запрос на fastoran.ru, мы отдаем тенанта с slug 'fatoran'
        'fastoran.ru' => 'fastoran',
        'www.fastoran.ru' => 'fastoran', // Обязательно добавляем www-версию
        'fastoran.com' => 'fastoran',
        'www.fastoran.com' => 'fastoran', // Обязательно добавляем www-версию

        // В будущем можно добавлять другие домены:
        // 'another-client.com' => 'client_slug',
    ],
];
