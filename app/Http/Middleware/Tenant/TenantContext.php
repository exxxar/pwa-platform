<?php

namespace App\Http\Middleware\Tenant;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;


class TenantContext
{
    public function handle(Request $request, Closure $next)
    {
        // Получаем текущий хост (например, test.mypwa.ru)
        $host = $request->getHost();

        // Принудительно HTTPS
        URL::forceScheme('https');

        // Устанавливаем динамический базовый URL
        URL::forceRootUrl("https://{$host}");

        return $next($request);
    }
}
