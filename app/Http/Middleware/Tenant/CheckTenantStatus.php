<?php

namespace App\Http\Middleware\Tenant;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Получаем список служебных доменов
        $systemDomains = config('tenant_domains.system_domains', []);

        // ---------------------------------------------------------
        // 0. ПРОВЕРКА СЛУЖЕБНЫХ ДОМЕНОВ
        // Для служебных доменов tenant не определяется,
        // поэтому просто пропускаем запрос дальше.
        // ---------------------------------------------------------
        if (in_array($host, $systemDomains)) {
            return $next($request);
        }

        // ---------------------------------------------------------
        // 1. ПРОВЕРКА TENANT
        // Получаем tenant только если он был зарегистрирован
        // TenantResolver'ом.
        // ---------------------------------------------------------
        if (!app()->bound('tenant')) {
            return $next($request);
        }

        $tenant = app('tenant');

        // ---------------------------------------------------------
        // 2. ПРОВЕРКА СТАТУСА TENANT
        // Если tenant отключен — отправляем на maintenance.
        // ---------------------------------------------------------
        if ($tenant && !$tenant->is_active) {
            return redirect()->route('maintenance');
        }

        return $next($request);
    }
}
