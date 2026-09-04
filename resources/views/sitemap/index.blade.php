@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <!-- Главная страница -->
    <url>
        <loc>{{ $baseUrl }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Страницы тенантов -->
    @foreach($tenants as $tenant)
        @php
            $seo = $tenant->settings['sitemap'] ?? [];
        @endphp
        <url>
            <loc>{{ $baseUrl }}/shop/{{ $tenant->slug }}</loc>
            <changefreq>{{ $seo['changefreq'] ?? 'weekly' }}</changefreq>
            <priority>{{ $seo['priority'] ?? 0.8 }}</priority>
        </url>
    @endforeach

</urlset>
