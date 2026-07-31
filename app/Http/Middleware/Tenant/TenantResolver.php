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
            Log::info("step 1: Tenant not found for host: " . $request->getHost());
            abort(404, 'Tenant not found');
        }

        // === ИСПРАВЛЕНИЕ ЗДЕСЬ ===
        if ($request->route()) {
            Log::info("step 7: Устанавливаем параметр роута как СТРОКУ (slug)");
            // Передаем именно строку, а не объект $tenant!
            // Это предотвратит поломку TenantUserResolver и контроллеров
            $request->route()->setParameter('tenant', $tenant->slug);
        }
        // =========================

        // Сохраняем tenant в контейнер (для доступа через app('tenant'))
        app()->instance('tenant', $tenant);

        Log::info("step 8: Tenant успешно установлен: " . $tenant->slug);

        // Делаем доступным через request (самый надежный способ)
        $request->tenant = $tenant;

        return $next($request);
    }

    private function resolveTenant($request): ?Tenant
    {
        $host = $request->getHost();
        Log::info("step 2: Проверка хоста: " . $host);

        // ---------------------------------------------------------
        // 1. ПРОВЕРКА КАСТОМНЫХ ДОМЕНОВ ИЗ КОНФИГА
        // ---------------------------------------------------------
        $customDomains = config('tenant_domains.domain_mapping', []);

        if (array_key_exists($host, $customDomains)) {
            $slug = $customDomains[$host];
            Log::info("step 3: Найден кастомный домен, slug: " . $slug);
            $tenant = Tenant::where('slug', $slug)->first();

            if ($tenant) {
                return $tenant;
            }
        }

        Log::info("step 4: Кастомный домен не найден, проверяем поддомены");

        // ---------------------------------------------------------
        // 2. СТАНДАРТНАЯ ЛОГИКА: Поддомены
        // ---------------------------------------------------------
        if (
            !filter_var($host, FILTER_VALIDATE_IP) &&
            !in_array($host, ['localhost', '127.0.0.1'])
        ) {
            $parts = explode('.', $host);
            Log::info("step 5: Parts: " . print_r($parts, true));

            if (count($parts) > 2) {
                $slug = $parts[0];
                Log::info("step 6: Проверка slug поддомена: " . $slug);
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
