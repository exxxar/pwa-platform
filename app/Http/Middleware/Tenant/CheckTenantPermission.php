<?php

namespace App\Http\Middleware\Tenant;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckTenantPermission
{
    public function handle($request, Closure $next, string $permission)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        if (!$user->hasPermission($permission)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
