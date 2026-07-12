<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\Order;
use App\Models\Tenant\CashbackTransaction;
use App\Models\Tenant\TrafficSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    /**
     * 🆕 Основная статистика
     */
    public function main(Request $request)
    {
        $tenant = app('tenant');

        $statistic = [
            'users_in_bd' => TenantUser::where('tenant_id', $tenant->id)->count(),
            'vip_in_bd' => TenantUser::where('tenant_id', $tenant->id)->where('is_vip', true)->count(),
            'admin_in_bd' => TenantUser::where('tenant_id', $tenant->id)->where('is_admin', true)->count(),
            'work_admin_in_bd' => TenantUser::where('tenant_id', $tenant->id)
                ->where('is_admin', true)
                ->where('last_activity_at', '>=', now()->subMinutes(15))
                ->count(),

            'summary_cashback' => TenantUser::where('tenant_id', $tenant->id)->sum('cashback'),
            'summary_cashback_people_count' => TenantUser::where('tenant_id', $tenant->id)
                ->where('cashback', '>', 0)
                ->count(),

            'cashback_summary_up' => CashbackTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'up')
                ->sum('amount'),
            'cashback_summary_up_people_count' => CashbackTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'up')
                ->distinct('tenant_user_id')
                ->count('tenant_user_id'),

            'cashback_summary_down' => CashbackTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'down')
                ->sum('amount'),
            'cashback_summary_down_people_count' => CashbackTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'down')
                ->distinct('tenant_user_id')
                ->count('tenant_user_id'),

            'cashback_up_level_1' => CashbackTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'up')
                ->where('level', 1)
                ->sum('amount'),
            'cashback_up_level_2' => CashbackTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'up')
                ->where('level', 2)
                ->sum('amount'),
            'cashback_up_level_3' => CashbackTransaction::where('tenant_id', $tenant->id)
                ->where('type', 'up')
                ->where('level', 3)
                ->sum('amount'),

            'orders' => [
                'sum' => $this->getOrdersByMonth($tenant->id, $request),
                'products' => $this->getTopProducts($tenant->id, $request),
            ],
            'users' => [
                'sum' => $this->getUsersByMonth($tenant->id, $request),
            ],
            'cashback_up' => [
                'sum' => $this->getCashbackByMonth($tenant->id, 'up', $request),
            ],
            'cashback_down' => [
                'sum' => $this->getCashbackByMonth($tenant->id, 'down', $request),
            ],
        ];

        return response()->json(['statistic' => $statistic]);
    }

    /**
     * 🆕 Статистика трафика
     */
    public function traffic(Request $request)
    {
        $tenant = app('tenant');

        $query = TrafficSource::where('tenant_id', $tenant->id)
            ->select('id', 'source', DB::raw('COUNT(*) as count'))
            ->groupBy('id', 'source');

        // Фильтр по дате
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->date_from,
                $request->date_to,
            ]);
        }

        // Индивидуальные переходы
        if ($request->boolean('is_individual')) {
            $query->where('is_individual', true);
        }

        $traffics = $query->orderByDesc('count')->get();

        return response()->json(['traffics' => $traffics]);
    }

    /**
     * 🆕 Экспорт статистики
     */
    public function export(Request $request)
    {
        // Здесь можно использовать Laravel Excel
        return response()->download(storage_path('app/statistic.xlsx'));
    }

    // Вспомогательные методы
    private function getOrdersByMonth($tenantId, $request) { /* ... */ }
    private function getTopProducts($tenantId, $request) { /* ... */ }
    private function getUsersByMonth($tenantId, $request) { /* ... */ }
    private function getCashbackByMonth($tenantId, $type, $request) { /* ... */ }
}
