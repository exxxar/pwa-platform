<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $tenant = \Illuminate\Support\Facades\Session::get("tenant")
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ $tenant->name ?? 'pwa' }}</title>


    <link rel="manifest" href="/manifest.json">


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
