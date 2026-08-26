<?php

namespace App\Http\Controllers\Admin\Global;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use App\Services\Admin\Global\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Общая статистика для дашборда
     */
    public function dashboard()
    {
        $stats = $this->reportService->getDashboardStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Статистика по конкретному тенанту
     */
    public function tenantStats(Tenant $tenant)
    {
        $stats = $this->reportService->getTenantStats($tenant);

        return response()->json([
            'success' => true,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'data' => $stats,
        ]);
    }

    /**
     * График регистраций пользователей
     */
    public function userRegistrationsChart(Request $request)
    {
        $tenantId = $request->input('tenant_id');
        $days = $request->input('days', 30);

        $chart = $this->reportService->getUserRegistrationsChart($tenantId, $days);

        return response()->json([
            'success' => true,
            'tenant_id' => $tenantId,
            'days' => $days,
            'data' => $chart,
        ]);
    }

    /**
     * График заказов
     */
    public function ordersChart(Request $request)
    {
        $tenantId = $request->input('tenant_id');
        $days = $request->input('days', 30);

        $chart = $this->reportService->getOrdersChart($tenantId, $days);

        return response()->json([
            'success' => true,
            'tenant_id' => $tenantId,
            'days' => $days,
            'data' => $chart,
        ]);
    }

    /**
     * Топ тенантов по выручке
     */
    public function topTenantsByRevenue(Request $request)
    {
        $limit = $request->input('limit', 10);
        $period = $request->input('period', 'month');

        // Валидация периода
        if (!in_array($period, ['month', 'year', 'all'])) {
            return response()->json([
                'success' => false,
                'message' => 'Недопустимый период. Используйте: month, year или all',
            ], 400);
        }

        $topTenants = $this->reportService->getTopTenantsByRevenue($limit, $period);

        return response()->json([
            'success' => true,
            'period' => $period,
            'limit' => $limit,
            'data' => $topTenants,
        ]);
    }

    /**
     * Топ пользователей по сумме заказов
     */
    public function topUsersByOrders(Request $request)
    {
        $tenantId = $request->input('tenant_id');
        $limit = $request->input('limit', 10);

        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Параметр tenant_id обязателен',
            ], 400);
        }

        $topUsers = $this->reportService->getTopUsersByOrders($tenantId, $limit);

        return response()->json([
            'success' => true,
            'tenant_id' => $tenantId,
            'limit' => $limit,
            'data' => $topUsers,
        ]);
    }
}
