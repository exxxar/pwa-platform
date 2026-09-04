<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        // Кэшируем результат на 24 часа, чтобы не делать запросы к БД при каждом заходе робота
        $xml = Cache::remember('sitemap_xml', 86400, function () {
            $tenants = Tenant::where('is_active', true)
                ->get()
                ->filter(fn($t) => !empty($t->settings['sitemap']['include_in_sitemap']));

            return view('sitemap.index', [
                'tenants' => $tenants,
                'baseUrl' => config('app.url')
            ])->render();
        });

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    // Метод для очистки кэша при сохранении настроек SEO
    public function clearCache()
    {
        Cache::forget('sitemap_xml');
    }
}
