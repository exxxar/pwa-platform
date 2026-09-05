@php
    $tenant = \Illuminate\Support\Facades\Session::get("tenant");

    $seo = $tenant["settings"]["seo"] ?? [];
@endphp

    <!DOCTYPE html>
<html lang="{{ $seo['meta']['language'] ?? 'ru' }}">
<head>

    {{-- 1. Базовые технические мета-теги --}}
    <meta charset="{{ $seo['meta']['charset'] ?? 'UTF-8' }}">
    <meta name="viewport" content="{{ $seo['meta']['viewport'] ?? 'width=device-width, initial-scale=1.0' }}">
    <meta name="theme-color" content="{{ $seo['images']['theme_color'] ?? '#ffffff' }}">

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


    <link rel="manifest" href="/manifest.json">

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

</body>
</html>
