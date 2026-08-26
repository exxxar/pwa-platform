<?php

namespace App\Services\Admin\Global;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\Order;
use App\Models\Tenant\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Получить общую статистику для дашборда
     */
    public function getDashboardStats(): array
    {
        return [
            'tenants' => [
                'total' => Tenant::count(),
                'active' => Tenant::where('is_active', true)->count(),
            ],
            'users' => [
                'total' => TenantUser::count(),
                'active' => TenantUser::where('is_active', true)->count(),
                'vip' => TenantUser::where('is_vip', true)->count(),
            ],
            'orders' => [
                'total' => Order::count(),
                'today' => Order::whereDate('created_at', today())->count(),
                'paid' => Order::whereNotNull('payed_at')->count(),
            ],
            'revenue' => [
                'total' => Transaction::successful()->sum('amount'),
                'today' => Transaction::successful()->whereDate('paid_at', today())->sum('amount'),
                'month' => Transaction::successful()->whereMonth('paid_at', now()->month)->sum('amount'),
            ],
        ];
    }

    /**
     * Получить статистику по тенанту
     */
    public function getTenantStats(Tenant $tenant): array
    {
        return [
            'users' => [
                'total' => $tenant->users()->count(),
                'active' => $tenant->users()->where('is_active', true)->count(),
                'vip' => $tenant->users()->where('is_vip', true)->count(),
                'new_today' => $tenant->users()->whereDate('created_at', today())->count(),
            ],
            'orders' => [
                'total' => Order::where('tenant_id', $tenant->id)->count(),
                'paid' => Order::where('tenant_id', $tenant->id)->whereNotNull('payed_at')->count(),
                'today' => Order::where('tenant_id', $tenant->id)->whereDate('created_at', today())->count(),
            ],
            'revenue' => [
                'total' => Transaction::where('tenant_id', $tenant->id)->successful()->sum('amount'),
                'month' => Transaction::where('tenant_id', $tenant->id)
                    ->successful()
                    ->whereMonth('paid_at', now()->month)
                    ->sum('amount'),
            ],
            'products' => [
                'total' => $tenant->products()->count(),
                'active' => $tenant->products()->where('is_active', true)->count(),
            ],
        ];
    }

    /**
     * Получить динамику регистраций пользователей по дням
     */
    public function getUserRegistrationsChart(?int $tenantId = null, int $days = 30): array
    {
        $query = TenantUser::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $startDate = now()->subDays($days);

        return $query->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Получить динамику заказов по дням
     */
    public function getOrdersChart(?int $tenantId = null, int $days = 30): array
    {
        $query = Order::query()->whereNotNull('payed_at');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $startDate = now()->subDays($days);

        return $query->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(summary_price) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'orders_count' => $item->orders_count,
                'revenue' => (float) $item->revenue,
            ])
            ->toArray();
    }

    /**
     * Получить топ тенантов по выручке
     */
    public function getTopTenantsByRevenue(int $limit = 10, ?string $period = 'month'): array
    {
        $query = Transaction::query()
            ->select('tenant_id', DB::raw('SUM(amount) as total_revenue'))
            ->successful()
            ->groupBy('tenant_id');

        if ($period === 'month') {
            $query->whereMonth('paid_at', now()->month);
        } elseif ($period === 'year') {
            $query->whereYear('paid_at', now()->year);
        }

        return $query->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $tenant = Tenant::find($item->tenant_id);
                return [
                    'tenant_id' => $item->tenant_id,
                    'tenant_name' => $tenant?->name ?? 'Неизвестный',
                    'total_revenue' => (float) $item->total_revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Получить топ пользователей по сумме заказов
     */
    public function getTopUsersByOrders(int $tenantId, int $limit = 10): array
    {
        return Order::query()
            ->select(
                'tenant_user_id',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(summary_price) as total_spent')
            )
            ->where('tenant_id', $tenantId)
            ->whereNotNull('payed_at')
            ->groupBy('tenant_user_id')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $user = TenantUser::find($item->tenant_user_id);
                return [
                    'user_id' => $item->tenant_user_id,
                    'user_name' => $user?->name ?? 'Неизвестный',
                    'user_phone' => $user?->phone,
                    'orders_count' => $item->orders_count,
                    'total_spent' => (float) $item->total_spent,
                ];
            })
            ->toArray();
    }
}
