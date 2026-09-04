<?php

return [

    // ==========================================
    //  БАЗОВЫЕ META-ТЕГИ
    // ==========================================
    'meta' => [
        // Заголовок страницы (до 60-70 символов)
        'title' => '',

        // Описание страницы (до 150-160 символов)
        'description' => '',

        // Ключевые слова (через запятую)
        'keywords' => '',

        // Автор страницы
        'author' => '',

        // Язык контента (ru, en, uk и т.д.)
        'language' => 'ru',

        // Кодировка
        'charset' => 'UTF-8',

        // Viewport (для мобильных)
        'viewport' => 'width=device-width, initial-scale=1.0',

        // Robots: index/noindex, follow/nofollow
        // Варианты: 'index, follow' | 'noindex, nofollow' | 'noindex, follow'
        'robots' => 'index, follow',

        // Canonical URL (главное зеркало страницы)
        'canonical' => '',

        // H1 заголовок (если отличается от title)
        'h1' => '',
    ],

    // ==========================================
    // 📘 OPEN GRAPH (Facebook, VK, Telegram)
    // ==========================================
    'og' => [
        'title'       => '',
        'description' => '',
        'type'        => 'website', // website | article | product | business.business
        'url'         => '',
        'image'       => '',        // URL обложки (min 1200x630 px)
        'image_width' => 1200,
        'image_height'=> 630,
        'site_name'   => '',
        'locale'      => 'ru_RU',
    ],

    // ==========================================
    // 🐦 TWITTER CARD
    // ==========================================
    'twitter' => [
        'card'        => 'summary_large_image', // summary | summary_large_image
        'title'       => '',
        'description' => '',
        'image'       => '',
        'site'        => '',        // @username сайта
        'creator'     => '',        // @username автора
    ],

    // ==========================================
    //  СТРУКТУРИРОВАННЫЕ ДАННЫЕ (JSON-LD)
    // ==========================================
    // Schema.org тип сущности: LocalBusiness, Restaurant, Store, FoodEstablishment
    'schema' => [
        '@context'    => 'https://schema.org',
        '@type'       => 'LocalBusiness',

        // Основные поля
        'name'        => '',
        'description' => '',
        'url'         => '',
        'telephone'   => '',
        'email'       => '',
        'image'       => '',
        'logo'        => '',
        'priceRange'  => '', // например: "$$", "₽₽"

        // Адрес
        'address' => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => '',
            'addressLocality' => '',
            'addressRegion'   => '',
            'postalCode'      => '',
            'addressCountry'  => 'RU',
        ],

        // Координаты
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => '',
            'longitude' => '',
        ],

        // Часы работы (формат ISO 8601)
        'openingHoursSpecification' => [
            // Пример:
            // [
            //     '@type' => 'OpeningHoursSpecification',
            //     'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            //     'opens' => '10:00',
            //     'closes' => '18:30',
            // ],
        ],

        // Способы оплаты
        'paymentAccepted' => '', // Cash, CreditCard, etc.

        // Рейтинг (если есть)
        'aggregateRating' => [
            '@type'       => 'AggregateRating',
            'ratingValue' => '',
            'reviewCount' => '',
        ],
    ],

    // ==========================================
    // 🗺️ SITEMAP НАСТРОЙКИ
    // ==========================================
    'sitemap' => [
        // Приоритет страницы (0.0 - 1.0)
        'priority' => 0.8,

        // Частота обновления
        // always | hourly | daily | weekly | monthly | yearly | never
        'changefreq' => 'weekly',

        // Включать ли страницу в sitemap.xml
        'include_in_sitemap' => true,
    ],

    // ==========================================
    // 🔗 СОЦИАЛЬНЫЕ СЕТИ (для микроразметки и ссылок)
    // ==========================================
    'social' => [
        'vk'       => '',
        'telegram' => '',
        'instagram'=> '',
        'youtube'  => '',
        'facebook' => '',
        'twitter'  => '',
        'whatsapp' => '',
    ],

    // ==========================================
    // 🖼️ ИЗОБРАЖЕНИЯ (SEO-оптимизация)
    // ==========================================
    'images' => [
        // Alt-текст по умолчанию для обложки
        'cover_alt' => '',

        // Alt-текст для логотипа
        'logo_alt' => '',

        // Favicon
        'favicon' => '/favicon.ico',

        // Apple Touch Icon
        'apple_touch_icon' => '/apple-touch-icon.png',

        // Тема браузера (color)
        'theme_color' => '#ffffff',
    ],

    // ==========================================
    //  ЛОКАЛЬНОЕ SEO (для LocalBusiness)
    // ==========================================
    'local' => [
        // ИНН / ОГРН (для доверия поисковиков)
        'inn'  => '',
        'ogrn' => '',

        // Регион / город
        'city'    => '',
        'region'  => '',
        'country' => 'RU',

        // Часовой пояс
        'timezone' => 'Europe/Moscow',

        // Ближайшие города (для расширения охвата)
        'nearest_cities' => [],
    ],

    // ==========================================
    // 🛡️ ДОПОЛНИТЕЛЬНЫЕ НАСТРОЙКИ
    // ==========================================
    'advanced' => [
        // Запрет индексации (глобальный переключатель)
        'noindex' => false,

        // Запрет перехода по ссылкам
        'nofollow' => false,

        // Запрет кеширования (для динамических страниц)
        'noarchive' => false,

        // Запрет индексации изображений
        'noimageindex' => false,

        // Запрет перевода страницы Google
        'notranslate' => false,

        // Hreflang (альтернативные языковые версии)
        // ['en' => 'https://example.com/en', 'uk' => 'https://example.com/uk']
        'hreflang' => [],
    ],

];
