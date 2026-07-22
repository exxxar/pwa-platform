<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionAdminController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = app('tenant')->id;

        // Базовый запрос с eager loading для оптимизации (защита от N+1)
        $query = Transaction::query()
            ->where('tenant_id', $tenantId)
            ->with(['user:id,name,phone', 'order:id,receiver_name']);

        // 🆕 1. Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 🆕 2. Фильтр по провайдеру (банку)
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        // 🆕 3. Глобальный поиск (по ID транзакции, внешнему ID, ID заказа, имени или телефону пользователя)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('external_payment_id', 'like', "%{$search}%")
                    ->orWhere('order_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // 🆕 4. Фильтр по диапазону дат
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 🆕 5. Сортировка (по умолчанию: новые сверху)
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Пагинация
        $perPage = $request->get('per_page', 20);
        $transactions = $query->paginate($perPage);

        // 🆕 6. Статистика за текущий период (или общую, если фильтры не заданы)
        $statsQuery = Transaction::where('tenant_id', $tenantId);

        // Применяем те же фильтры к статистике, кроме пагинации
        if ($request->filled('status')) $statsQuery->where('status', $request->status);
        if ($request->filled('provider')) $statsQuery->where('provider', $request->provider);
        if ($request->filled('date_from')) $statsQuery->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $statsQuery->whereDate('created_at', '<=', $request->date_to);

        $stats = [
            'total_count' => (clone $statsQuery)->count(),
            'success_count' => (clone $statsQuery)->where('status', 'success')->count(),
            'pending_count' => (clone $statsQuery)->where('status', 'pending')->count(),
            'total_amount' => (clone $statsQuery)->where('status', 'success')->sum('amount'),
        ];

        return response()->json([
            'transactions' => $transactions,
            'stats' => $stats,
            'providers' => ['tinkoff', 'sber', 'psb', 'vtb', 'yandex'],
        ]);
    }
}
