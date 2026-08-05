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
            if ($request->route()) {
                $request->route()->setParameter('tenant', $tenantName);
            }
            $tenant = Tenant::where('slug', $tenantName)->first();
        } else {
            $tenant = $this->resolveTenant($request);
        }

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        // === ИСПРАВЛЕНИЕ ЗДЕСЬ ===
        if ($request->route()) {
            $request->route()->setParameter('tenant', $tenant->slug);
        }
        // =========================

        // Сохраняем tenant в контейнер (для доступа через app('tenant'))
        app()->instance('tenant', $tenant);

        // Делаем доступным через request (самый надежный способ)
        $request->tenant = $tenant;

        return $next($request);
    }

    private function resolveTenant($request): ?Tenant
    {
        $host = $request->getHost();

        // ---------------------------------------------------------
        // 1. ПРОВЕРКА КАСТОМНЫХ ДОМЕНОВ ИЗ КОНФИГА
        // ---------------------------------------------------------
        $customDomains = config('tenant_domains.domain_mapping', []);

        if (array_key_exists($host, $customDomains)) {
            $slug = $customDomains[$host];
            $tenant = Tenant::where('slug', $slug)->first();

            if ($tenant) {
                return $tenant;
            }
        }

        // ---------------------------------------------------------
        // 2. СТАНДАРТНАЯ ЛОГИКА: Поддомены
        // ---------------------------------------------------------
        if (
            !filter_var($host, FILTER_VALIDATE_IP) &&
            !in_array($host, ['localhost', '127.0.0.1'])
        ) {
            $parts = explode('.', $host);

            if (count($parts) > 2) {
                $slug = $parts[0];

                if ($tenant = Tenant::where('slug', $slug)->first()) {
                    return $tenant;
                }
            }
        }

        // ---------------------------------------------------------
        // 3. ФАЛЛБЭК: Первый сегмент URL
        // ---------------------------------------------------------
        $slug = $request->segment(1);
        if ($slug) {
            return Tenant::where('slug', $slug)->first();
        }

        return null;
    }
}
