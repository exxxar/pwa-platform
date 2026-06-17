<?php

namespace App\Http\Middleware\Tenant;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckTenantRole
{
    public function handle($request, Closure $next, string $role)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        if (!$user->hasRole($role)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
