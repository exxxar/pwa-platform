<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\UserStat;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackUserStats
{
    public function handle(Request $request, Closure $next, string $statKey)
    {
        $response = $next($request);

        if (!Auth::guard('tenant')->check()) {
            return $response;
        }

        if (is_null($request->tenant ?? null))
        {
            return $response;
        }

        $userId = Auth::guard('tenant')->id();

        // 🆕 incrementStat вместо increment
        UserStat::incrementStat($userId, $statKey);

        return $response;
    }
}
