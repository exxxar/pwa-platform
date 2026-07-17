<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\Tenant;
use Closure;


class TenantResolver
{
    public function handle($request, Closure $next)
    {


        if (env("APP_DEBUG") ?? false) {
            $tenantName = env("TEST_PWA_NAME") ?? 'test';
            $request->route()->setParameter('tenant', $tenantName);
            $tenant = Tenant::where('slug', $tenantName)->first();

        } else
        {

            $tenant = $this->resolveTenant($request);
        }


        if (!$tenant) {

            abort(404, 'Tenant not found');
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

        // localhost и IP не считаем поддоменными доменами
        if (
            !filter_var($host, FILTER_VALIDATE_IP) &&
            !in_array($host, ['localhost'])
        ) {
            $parts = explode('.', $host);

            // test.pwa-platform.test
            if (count($parts) > 2) {
                $slug = $parts[0];

                if ($tenant = Tenant::where('slug', $slug)->first()) {
                    return $tenant;
                }
            }
        }

        // pwa-platform.test/test/pwa
        // 127.0.0.1:8000/test/pwa
        $slug = $request->segment(1);


        if ($slug) {
            return Tenant::where('slug', $slug)->first();
        }

        return null;
    }
}
