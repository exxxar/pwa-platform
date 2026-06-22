<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $tenant = \Illuminate\Support\Facades\Session::get("tenant")
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ $tenant->name ?? 'pwa' }}</title>


    <link rel="manifest" href="{{ secure_url('/manifest.json') }}">


    <meta name="theme-color" content="{{ $tenant->theme_color ?? '#fff' }}">

    <link rel="stylesheet" id="theme" href="/themes/theme6.bootstrap.min.css">
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
