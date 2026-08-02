<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $tenant = \Illuminate\Support\Facades\Session::get("tenant")
    @endphp
    <meta charset="utf-8">
    <!-- 1. Обязательный viewport для iOS (запрет зума и учет "челки" iPhone) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

    <!-- 2. Разрешает запуск в полноэкранном режиме (как нативное приложение) -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- 3. Стиль строки состояния (status bar).
         Варианты: default (серый), black (черный), black-translucent (прозрачный, контент заходит под челку) -->
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- 4. Название приложения на домашнем экране iOS (берем из ваших настроек или фоллбэк) -->
    <meta name="apple-mobile-web-app-title" content="{{ $pwa['short_name'] ?? $tenant->short_name ?? 'Мое Приложение' }}">

    <!-- 5. Иконка для iOS (Apple Touch Icon) -->
    <!-- iOS не использует manifest.json для иконок на старых версиях, ей нужна эта ссылка -->
    <link rel="apple-touch-icon" href="{{ $manifestIcons[0]['src'] ?? url('/storage/defaults/icons/icon-192x192.png') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ $tenant->name ?? 'pwa' }}</title>


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

    @vite(['resources/js/MobileClient/app.js?version'.env('APP_VERSION'),'resources/css/MobileClient/app.css?version'.env('APP_VERSION'),
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
        }, { passive: false });

        document.addEventListener('touchmove', e => {
            // Pull-to-refresh
            if (e.touches[0].clientY - touchStartY > 0 && window.scrollY === 0) {
                e.preventDefault();
            }
            // Pinch-to-zoom
            if (e.touches.length >= 2) {
                e.preventDefault();
            }
        }, { passive: false });

// iOS gestures
        ['gesturestart', 'gesturechange', 'gestureend'].forEach(evt => {
            document.addEventListener(evt, e => e.preventDefault());
        });

// Ctrl+wheel на десктопе
        document.addEventListener('wheel', e => {
            if (e.ctrlKey) e.preventDefault();
        }, { passive: false });

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
