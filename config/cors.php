<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie','widget.js'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://xn--80aacbuczbw9a6a.xn--p1ai', // Старый-шансон (Punycode)
        'http://xn--80aacbuczbw9a6a.xn--p1ai',
        'http://localhost',          // Для тестов с localhost
        'http://sms.local',          // Для тестов с localhost
        'http://127.0.0.1',          // Для тестов с 127.0.0.1
        'http://localhost:8000',          // Для тестов с 127.0.0.1
        'http://pwa-platform.test',  // ВАШ локальный домен OpenServer (замените на свой, если он другой)
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
