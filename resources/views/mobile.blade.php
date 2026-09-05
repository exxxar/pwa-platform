@php
    $tenant = \Illuminate\Support\Facades\Session::get("tenant");

    $seo = $tenant["settings"]["seo"] ?? [];
@endphp

    <!DOCTYPE html>
<html lang="{{ $seo['meta']['language'] ?? 'ru' }}">
<head>

    {{-- 1. Базовые технические мета-теги --}}
    <meta charset="{{ $seo['meta']['charset'] ?? 'UTF-8' }}">
    <meta name="viewport"
          content="{{ $seo['meta']['viewport'] ?? 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover' }}">
    <meta name="theme-color" content="{{ $seo['images']['theme_color'] ?? '#ffffff' }}">


    <!-- 2. Разрешает запуск в полноэкранном режиме (как нативное приложение) -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- 3. Стиль строки состояния (status bar).
         Варианты: default (серый), black (черный), black-translucent (прозрачный, контент заходит под челку) -->
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- 4. Название приложения на домашнем экране iOS (берем из ваших настроек или фоллбэк) -->
    <meta name="apple-mobile-web-app-title"
          content="{{ $pwa['short_name'] ?? $tenant->short_name ?? 'Мое Приложение' }}">

    <!-- 5. Иконка для iOS (Apple Touch Icon) -->
    <!-- iOS не использует manifest.json для иконок на старых версиях, ей нужна эта ссылка -->
    <link rel="apple-touch-icon"
          href="{{ $manifestIcons[0]['src'] ?? url('/storage/defaults/icons/icon-192x192.png') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ $seo['meta']['title']  ?? $tenant->name ?? config('app.name') }}</title>


    {{-- 3. Основные SEO мета-теги --}}
    @if(!empty($seo['meta']['description']))
        <meta name="description" content="{{ $seo['meta']['description'] }}">
    @endif

    @if(!empty($seo['meta']['keywords']))
        <meta name="keywords" content="{{ $seo['meta']['keywords'] }}">
    @endif

    <meta name="robots" content="{{ $seo['meta']['robots'] ?? 'index, follow' }}">

    @if(!empty($seo['meta']['canonical']))
        <link rel="canonical" href="{{ $seo['meta']['canonical'] }}">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif

    @if(!empty($seo['meta']['author']))
        <meta name="author" content="{{ $seo['meta']['author'] }}">
    @endif

    {{-- 4. Иконки (Favicons) --}}
    <link rel="icon" href="{{ $seo['images']['favicon'] ?? '/favicon.ico' }}" type="image/x-icon">
    @if(!empty($seo['images']['apple_touch_icon']))
        <link rel="apple-touch-icon" href="{{ $seo['images']['apple_touch_icon'] }}">
    @endif

    {{-- 5. Open Graph (Facebook, ВКонтакте, Telegram, WhatsApp) --}}
    @if(!empty($seo['og']['title']) || !empty($seo['meta']['title']))
        <meta property="og:title" content="{{ $seo['og']['title'] ?? $seo['meta']['title'] }}">
    @endif

    @if(!empty($seo['og']['description']) || !empty($seo['meta']['description']))
        <meta property="og:description" content="{{ $seo['og']['description'] ?? $seo['meta']['description'] }}">
    @endif

    <meta property="og:type" content="{{ $seo['og']['type'] ?? 'website' }}">
    <meta property="og:url" content="{{ $seo['og']['url'] ?? url()->current() }}">
    <meta property="og:locale" content="{{ $seo['og']['locale'] ?? 'ru_RU' }}">

    @if(!empty($seo['og']['site_name']))
        <meta property="og:site_name" content="{{ $seo['og']['site_name'] }}">
    @endif

    @if(!empty($seo['og']['image']))
        <meta property="og:image" content="{{ $seo['og']['image'] }}">
        @if(!empty($seo['og']['image_width']))
            <meta property="og:image:width" content="{{ $seo['og']['image_width'] }}">
        @endif
        @if(!empty($seo['og']['image_height']))
            <meta property="og:image:height" content="{{ $seo['og']['image_height'] }}">
        @endif
    @endif

    {{-- 6. Twitter Cards --}}
    @if(!empty($seo['twitter']['card']))
        <meta name="twitter:card" content="{{ $seo['twitter']['card'] }}">
    @endif
    @if(!empty($seo['twitter']['title']))
        <meta name="twitter:title" content="{{ $seo['twitter']['title'] }}">
    @endif
    @if(!empty($seo['twitter']['description']))
        <meta name="twitter:description" content="{{ $seo['twitter']['description'] }}">
    @endif
    @if(!empty($seo['twitter']['image']))
        <meta name="twitter:image" content="{{ $seo['twitter']['image'] }}">
    @endif
    @if(!empty($seo['twitter']['site']))
        <meta name="twitter:site" content="{{ $seo['twitter']['site'] }}">
    @endif

    {{-- 7. Hreflang (Мультиязычность, если используется) --}}
    @if(!empty($seo['advanced']['hreflang']) && is_array($seo['advanced']['hreflang']))
        @foreach($seo['advanced']['hreflang'] as $lang => $url)
            <link rel="alternate" hreflang="{{ $lang }}" href="{{ $url }}">
        @endforeach
    @endif

    {{-- 8. Дополнительные запреты (из advanced настроек) --}}
    @if(!empty($seo['advanced']['noarchive']))
        <meta name="robots" content="noarchive">
    @endif
    @if(!empty($seo['advanced']['notranslate']))
        <meta name="google" content="notranslate">
    @endif

    {{-- 9. JSON-LD Микроразметка Schema.org --}}
    @if(!empty($seo['schema']))
        <script type="application/ld+json">
            {!! json_encode($seo['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
    @endif


    <link rel="manifest" href="{{ secure_url('/manifest.json') }}">


    <meta name="theme-color" content="{{ $tenant->theme_color ?? '#fff' }}">

    <script>
        window.Tenant = @json($tenant);
        @if (auth('tenant')->user())
            window.TenantUser = @json(auth('tenant')->user()?->load(['roles.permissions']));
        @else
            window.TenantUser = null
        @endif
    </script>
    <!-- Scripts -->
    @routes

    @vite(['resources/js/MobileClient/app.js','resources/css/MobileClient/app.css',
    "resources/js/MobileClient/Pages/{$page['component']}.vue"])
    @inertiaHead

    <style>
        html, body {
            overscroll-behavior-y: contain;
            touch-action: manipulation;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
    </style>
</head>

<body class="font-sans antialiased">
@inertia


<script>

    window.onload = () => {

        const tenant = window.Tenant

        let theme = localStorage.getItem("mypwa_global_client_theme_" + tenant.uuid)

        if (theme) {
            let changeTheme = document.querySelector("#theme")
            changeTheme.href = theme
        }

        // Запрет pull-to-refresh на старых браузерах
        let touchStartY = 0;
        document.addEventListener('touchstart', e => {
            touchStartY = e.touches[0].clientY;
        }, {passive: false});

        document.addEventListener('touchmove', e => {
            // Pull-to-refresh
            if (e.touches[0].clientY - touchStartY > 0 && window.scrollY === 0) {
                e.preventDefault();
            }
            // Pinch-to-zoom
            if (e.touches.length >= 2) {
                e.preventDefault();
            }
        }, {passive: false});

// iOS gestures
        ['gesturestart', 'gesturechange', 'gestureend'].forEach(evt => {
            document.addEventListener(evt, e => e.preventDefault());
        });

// Ctrl+wheel на десктопе
        document.addEventListener('wheel', e => {
            if (e.ctrlKey) e.preventDefault();
        }, {passive: false});

    }
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js')
                .then(function (registration) {
                    console.log('SW registered:', registration);
                })
                .catch(function (error) {
                    console.log('SW registration failed:', error);
                });
        });
    }
</script>
</body>
</html>
