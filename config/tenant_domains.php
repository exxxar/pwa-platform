<?php

return [
    /*
     * Связка "кастомный домен" => "slug тенанта в базе данных"
     * Ключом является точный хост из запроса (request->getHost())
     */
    'domain_mapping' => [
        // Пример: когда приходит запрос на fastoran.ru, мы отдаем тенанта с slug 'fastoran'
        'fastoran.ru' => 'fastoran',
        'www.fastoran.ru' => 'fastoran', // Обязательно добавляем www-версию
        'bolshoyjohn.ru' => 'bigjohn', // Обязательно добавляем www-версию
        'www.bolshoyjohn.ru' => 'bigjohn', // Обязательно добавляем www-версию

        // В будущем можно добавлять другие домены:
        // 'another-client.com' => 'client_slug',
    ],

    /*
     * Служебные (системные) домены, которые нужно пропускать.
     * Для этих доменов поиск тенанта не производится, middleware просто пропускает запрос дальше.
     */
    'system_domains' => [
        'mypwa.ru',
        // Основные домены приложения (лендинг, админка, API и т.д.)
        'example.com',
        'www.example.com',
        // 'admin.example.com',
        // 'api.example.com',

        // Локальные домены для разработки
        'localhost',
        '127.0.0.1',
    ],
];
