<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle($request, Closure $next, string $permission)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        if (!$user->hasPermission($permission)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
