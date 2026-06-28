<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\UserStat;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackOrderStats
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!Auth::guard('tenant')->check()) {
            return $response;
        }

        $userId = Auth::guard('tenant')->id();

        // 🆕 incrementStat вместо increment
        UserStat::incrementStat($userId, 'orders_count');

        $orderSum = (int) ($request->input('summary_price') ?: 0);
        if ($orderSum > 0) {
            UserStat::incrementStat($userId, 'orders_sum', $orderSum);
        }

        return $response;
    }
}
