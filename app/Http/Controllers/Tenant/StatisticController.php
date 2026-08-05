<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CashBack;
use App\Models\Tenant\CashBackHistory;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderProduct;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\TrafficSource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatisticController extends Controller
{
    /**
     * 🆕 Основная статистика
     */
    public function main(Request $request)
    {
        $tenant = app('tenant');
        $tenantId = $tenant->id;

        // Определяем период
        [$dateFrom, $dateTo, $needAll] = $this->resolveDateRange($request);

        // ==========================================
        // БАЗОВЫЕ МЕТРИКИ (по пользователям — без фильтра по дате)
        // ==========================================
        $usersQuery = TenantUser::where('tenant_id', $tenantId);

        $usersInBd = (clone $usersQuery)->count();
        $vipInBd = (clone $usersQuery)->where('is_vip', true)->count();

        // Админы — через роли
        $adminInBd = (clone $usersQuery)
            ->whereHas('roles', fn($q) => $q->where('name', 'like', '%admin%'))
            ->count();

        $workAdminInBd = (clone $usersQuery)
            ->whereHas('roles', fn($q) => $q->where('name', 'like', '%admin%'))
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->count();

        // ==========================================
        // КЭШБЭК БАЛАНСЫ (из таблицы cash_backs)
        // ==========================================
        $cashBackQuery = CashBack::where('tenant_id', $tenantId);

        $summaryCashback = (float) (clone $cashBackQuery)->sum('amount');
        $summaryCashbackPeopleCount = (clone $cashBackQuery)
            ->where('amount', '>', 0)
            ->count();

        // ==========================================
        // ИСТОРИЯ КЭШБЭКА (с фильтром по датам)
        // ==========================================
        $cbHistoryQuery = CashBackHistory::where('tenant_id', $tenantId);

        if (!$needAll && $dateFrom && $dateTo) {
            $cbHistoryQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $cashbackSummaryUp = (float) (clone $cbHistoryQuery)->where('type', 'credit')->sum('amount');
        $cashbackSummaryUpPeopleCount = (clone $cbHistoryQuery)
            ->where('type', 'credit')
            ->distinct('tenant_user_id')
            ->count('tenant_user_id');

        $cashbackSummaryDown = (float) (clone $cbHistoryQuery)->where('type', 'debit')->sum('amount');
        $cashbackSummaryDownPeopleCount = (clone $cbHistoryQuery)
            ->where('type', 'debit')
            ->distinct('tenant_user_id')
            ->count('tenant_user_id');

        // По уровням
        $cashbackUpLevel1 = (float) (clone $cbHistoryQuery)->where('type', 'credit')->where('level', 1)->sum('amount');
        $cashbackUpLevel2 = (float) (clone $cbHistoryQuery)->where('type', 'credit')->where('level', 2)->sum('amount');
        $cashbackUpLevel3 = (float) (clone $cbHistoryQuery)->where('type', 'credit')->where('level', 3)->sum('amount');

        $cashbackUpLevel1People = (clone $cbHistoryQuery)
            ->where('type', 'credit')->where('level', 1)
            ->distinct('tenant_user_id')->count('tenant_user_id');
        $cashbackUpLevel2People = (clone $cbHistoryQuery)
            ->where('type', 'credit')->where('level', 2)
            ->distinct('tenant_user_id')->count('tenant_user_id');
        $cashbackUpLevel3People = (clone $cbHistoryQuery)
            ->where('type', 'credit')->where('level', 3)
            ->distinct('tenant_user_id')->count('tenant_user_id');

        // ==========================================
        // ГРАФИКИ
        // ==========================================
        $statistic = [
            'users_in_bd' => $usersInBd,
            'vip_in_bd' => $vipInBd,
            'admin_in_bd' => $adminInBd,
            'work_admin_in_bd' => $workAdminInBd,

            'summary_cashback' => $summaryCashback,
            'summary_cashback_people_count' => $summaryCashbackPeopleCount,

            'cashback_summary_up' => $cashbackSummaryUp,
            'cashback_summary_up_people_count' => $cashbackSummaryUpPeopleCount,
            'cashback_summary_down' => $cashbackSummaryDown,
            'cashback_summary_down_people_count' => $cashbackSummaryDownPeopleCount,

            'cashback_up_level_1' => $cashbackUpLevel1,
            'cashback_up_level_1_people_count' => $cashbackUpLevel1People,
            'cashback_up_level_2' => $cashbackUpLevel2,
            'cashback_up_level_2_people_count' => $cashbackUpLevel2People,
            'cashback_up_level_3' => $cashbackUpLevel3,
            'cashback_up_level_3_people_count' => $cashbackUpLevel3People,

            'orders' => [
                'sum' => $this->getOrdersByMonth($tenantId, $dateFrom, $dateTo, $needAll),
                'products' => $this->getTopProducts($tenantId, $dateFrom, $dateTo, $needAll),
            ],
            'users' => [
                'sum' => $this->getUsersByMonth($tenantId, $dateFrom, $dateTo, $needAll),
            ],
            'cashback_up' => [
                'sum' => $this->getCashbackByMonth($tenantId, 'credit', $dateFrom, $dateTo, $needAll),
            ],
            'cashback_down' => [
                'sum' => $this->getCashbackByMonth($tenantId, 'debit', $dateFrom, $dateTo, $needAll),
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
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source');

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay(),
            ]);
        }

        if ($request->boolean('is_individual')) {
            $query->where('is_individual', true);
        }

        $traffics = $query->orderByDesc('count')->limit(50)->get();

        return response()->json(['traffics' => $traffics]);
    }

    /**
     * 🆕 Экспорт статистики в CSV
     */
    public function export(Request $request)
    {
        $tenant = app('tenant');
        $tenantId = $tenant->id;

        [$dateFrom, $dateTo, $needAll] = $this->resolveDateRange($request);

        // Формируем CSV
        $csv = "Статистика по тенанту: {$tenant->name}\n";
        $csv .= "Период: " . ($needAll ? 'всё время' : "{$dateFrom} — {$dateTo}") . "\n\n";

        // Общие метрики
        $csv .= "=== ОБЩИЕ МЕТРИКИ ===\n";
        $csv .= "Всего пользователей," . TenantUser::where('tenant_id', $tenantId)->count() . "\n";
        $csv .= "VIP клиентов," . TenantUser::where('tenant_id', $tenantId)->where('is_vip', true)->count() . "\n";
        $csv .= "Баланс кэшбэка," . CashBack::where('tenant_id', $tenantId)->sum('amount') . "\n\n";

        // Продажи по месяцам
        $csv .= "=== ПРОДАЖИ ПО МЕСЯЦАМ ===\n";
        $csv .= "Год,Месяц,Сумма\n";
        foreach ($this->getOrdersByMonth($tenantId, $dateFrom, $dateTo, $needAll) as $row) {
            $csv .= "{$row['y']},{$row['m']},{$row['sump']}\n";
        }
        $csv .= "\n";

        // Топ товаров
        $csv .= "=== ТОП ТОВАРОВ ===\n";
        $csv .= "Название,Продано,Выручка\n";
        foreach ($this->getTopProducts($tenantId, $dateFrom, $dateTo, $needAll) as $row) {
            $title = str_replace('"', '""', $row['title']);
            $csv .= "\"{$title}\",{$row['count']},{$row['price']}\n";
        }

        $filename = 'statistic_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ==========================================
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ==========================================

    /**
     * Разрешение диапазона дат из запроса
     */
    private function resolveDateRange(Request $request): array
    {
        $needAll = $request->boolean('need_all', true);

        if ($needAll) {
            return [null, null, true];
        }

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->subYear()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        return [$dateFrom, $dateTo, false];
    }

    /**
     * 🆕 Заказы по месяцам (сумма)
     */
    private function getOrdersByMonth($tenantId, $dateFrom, $dateTo, bool $needAll): array
    {
        $query = Order::where('tenant_id', $tenantId)
            ->whereNotNull('payed_at')
            ->select(
                DB::raw('YEAR(payed_at) as y'),
                DB::raw('MONTH(payed_at) as m'),
                DB::raw('SUM(summary_price + delivery_price) as sump'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m');

        if (!$needAll && $dateFrom && $dateTo) {
            $query->whereBetween('payed_at', [$dateFrom, $dateTo]);
        }

        return $query->get()->map(fn($r) => [
            'y' => (int) $r->y,
            'm' => (int) $r->m,
            'sump' => (float) $r->sump,
            'count' => (int) $r->count,
        ])->toArray();
    }

    /**
     * 🆕 Топ товаров за период
     */
    /**
     * 🆕 Топ товаров за период (агрегация из JSON product_details)
     *
     * Товары хранятся в orders.product_details как JSON-массив:
     * [
     *   {"id": 1, "name": "Пицца", "price": 500, "count": 2},
     *   {"id": 2, "name": "Кола", "price": 100, "count": 1}
     * ]
     */
    private function getTopProducts($tenantId, $dateFrom, $dateTo, bool $needAll): array
    {
        // 1. Загружаем оплаченные заказы за период (только нужные поля для производительности)
        $query = Order::where('tenant_id', $tenantId)
            ->whereNotNull('payed_at')
            ->select('id', 'product_details');

        if (!$needAll && $dateFrom && $dateTo) {
            $query->whereBetween('payed_at', [$dateFrom, $dateTo]);
        }

        $orders = $query->get();

        // 2. Агрегируем товары в PHP
        $productsMap = []; // [productId => ['title' => ..., 'count' => ..., 'price' => ...]]

        foreach ($orders as $order) {
            $details = $order->product_details;

            if (!is_array($details)) {
                continue;
            }

            foreach ($details as $item) {
                // Поддержка разных форматов хранения
                $productId = $item['id'] ?? $item['product_id'] ?? null;
                $title = $item['name'] ?? $item['title'] ?? 'Без названия';
                $price = (float) ($item['price'] ?? 0);
                $count = (int) ($item['count'] ?? 1);

                if (!$productId) {
                    continue;
                }

                $key = (string) $productId;

                if (!isset($productsMap[$key])) {
                    $productsMap[$key] = [
                        'id' => $productId,
                        'title' => $title,
                        'count' => 0,
                        'price' => 0,
                    ];
                }

                $productsMap[$key]['count'] += $count;
                $productsMap[$key]['price'] += $price * $count;
            }
        }

        // 3. Сортируем по выручке (desc) и берём топ-100
        $products = array_values($productsMap);
        usort($products, fn($a, $b) => $b['price'] <=> $a['price']);
        $products = array_slice($products, 0, 100);

        // 4. Считаем общие суммы для процентов
        $totalCount = array_sum(array_column($products, 'count'));
        $totalPrice = array_sum(array_column($products, 'price'));

        // 5. Добавляем проценты
        return array_map(fn($p) => [
            'id' => $p['id'],
            'title' => $p['title'],
            'count' => $p['count'],
            'price' => round($p['price'], 2),
            'volume_count_ratio' => $totalCount > 0
                ? round(($p['count'] / $totalCount) * 100, 2)
                : 0,
            'volume_price_ratio' => $totalPrice > 0
                ? round(($p['price'] / $totalPrice) * 100, 2)
                : 0,
        ], $products);
    }

    /**
     * 🆕 Пользователи по месяцам регистрации
     */
    private function getUsersByMonth($tenantId, $dateFrom, $dateTo, bool $needAll): array
    {
        $query = TenantUser::where('tenant_id', $tenantId)
            ->select(
                DB::raw('YEAR(created_at) as y'),
                DB::raw('MONTH(created_at) as m'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m');

        if (!$needAll && $dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        return $query->get()->map(fn($r) => [
            'y' => (int) $r->y,
            'm' => (int) $r->m,
            'count' => (int) $r->count,
        ])->toArray();
    }

    /**
     * 🆕 Кэшбэк по месяцам (начисления или списания)
     */
    private function getCashbackByMonth($tenantId, string $type, $dateFrom, $dateTo, bool $needAll): array
    {
        $query = CashBackHistory::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->select(
                DB::raw('YEAR(created_at) as y'),
                DB::raw('MONTH(created_at) as m'),
                DB::raw('SUM(amount) as sum'),
                DB::raw('COUNT(DISTINCT tenant_user_id) as people_count')
            )
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m');

        if (!$needAll && $dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        return $query->get()->map(fn($r) => [
            'y' => (int) $r->y,
            'm' => (int) $r->m,
            'sum' => (float) $r->sum,
            'people_count' => (int) $r->people_count,
        ])->toArray();
    }
}
