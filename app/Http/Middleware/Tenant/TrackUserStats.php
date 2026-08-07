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

        $host = $request->getHost();
        $systemDomains = config('tenant_domains.system_domains', []);

        if (in_array($host, $systemDomains) && !(env("APP_DEBUG") ?? false)) {
            return $next($request);
        }

        if (!Auth::guard('tenant')->check()) {
            return $response;
        }


        $userId = Auth::guard('tenant')->id();

        // 🆕 incrementStat вместо increment
        UserStat::incrementStat($userId, $statKey);

        return $response;
    }
}
