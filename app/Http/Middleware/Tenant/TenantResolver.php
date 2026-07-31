<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\Tenant;
use Closure;
use Illuminate\Support\Facades\Log;

class TenantResolver
{
    public function handle($request, Closure $next)
    {
        if (env("APP_DEBUG") ?? false) {
            $tenantName = env("TEST_PWA_NAME") ?? 'test';
            // Если роутинг ожидает параметр tenant
            if ($request->route()) {
                $request->route()->setParameter('tenant', $tenantName);
            }
            $tenant = Tenant::where('slug', $tenantName)->first();
        } else {
            $tenant = $this->resolveTenant($request);
        }

        if (!$tenant) {
            Log::info("step 1".print_r($tenant, true));
            abort(404, 'Tenant not found');
        }

        if ($request->route()) {
            $request->route()->setParameter('tenant', $tenant);
        }

        // Сохраняем tenant в контейнер
        app()->instance('tenant', $tenant);

        // Делаем доступным через request
        $request->tenant = $tenant;

        return $next($request);
    }

    private function resolveTenant($request): ?Tenant
    {
        $host = $request->getHost();

        Log::info("step 2".$host);

        // ---------------------------------------------------------
        // 1. ПРОВЕРКА КАСТОМНЫХ ДОМЕНОВ ИЗ КОНФИГА (Новая логика)
        // ---------------------------------------------------------
        $customDomains = config('tenant_domains.domain_mapping', []);

        if (array_key_exists($host, $customDomains)) {
            $slug = $customDomains[$host];
            Log::info("step 3".$slug);
            $tenant = Tenant::where('slug', $slug)->first();

            if ($tenant) {
                return $tenant; // Успешно нашли тенанта по кастомному домену
            }
        }

        Log::info("step 4");

        // ---------------------------------------------------------
        // 2. СТАНДАРТНАЯ ЛОГИКА: Поддомены (fatoran.mypwa.ru)
        // ---------------------------------------------------------
        if (
            !filter_var($host, FILTER_VALIDATE_IP) &&
            !in_array($host, ['localhost', '127.0.0.1'])
        ) {
            $parts = explode('.', $host);
            Log::info("step 5".print_r($parts, true));
            // Если это поддомен (например, fatoran.mypwa.ru -> count > 2)
            if (count($parts) > 2) {
                $slug = $parts[0];
                Log::info("step 6".$slug);
                if ($tenant = Tenant::where('slug', $slug)->first()) {
                    return $tenant;
                }
            }
        }

        // ---------------------------------------------------------
        // 3. ФАЛЛБЭК: Первый сегмент URL (mypwa.ru/fatoran)
        // ---------------------------------------------------------
        $slug = $request->segment(1);

        if ($slug) {
            return Tenant::where('slug', $slug)->first();
        }

        return null;
    }
}
