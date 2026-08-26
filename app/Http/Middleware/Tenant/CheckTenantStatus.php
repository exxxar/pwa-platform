<?php

namespace App\Http\Middleware\Tenant;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        // Получаем текущего тенанта (адаптируйте под вашу систему мультиарендности)
        $tenant = app('tenant');

        if ($tenant && !$tenant->is_active) {
            // Если тенант отключен (например, из-за нехватки баланса), редиректим на maintenance
            return redirect()->route('maintenance');
        }

        return $next($request);
    }
}
