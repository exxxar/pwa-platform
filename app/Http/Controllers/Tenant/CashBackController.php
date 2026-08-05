<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\CashBackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class CashBackController extends Controller
{
    /**
     * Получить баланс и историю
     */
    public function index(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $balance = CashBackService::call()->getBalance($user);
        $history = CashBackService::call()->getHistory($user, 50);

        // Специальные начисления (по категориям)
        $specialSubs = $history->where('level', '>', 1)
            ->groupBy('description')
            ->map(function ($items) {
                return [
                    'sub_title' => $items->first()->description,
                    'total' => $items->sum('amount'),
                ];
            })
            ->values();

        return response()->json([
            'balance' => $balance,
            'history' => $history,
            'special_subs' => $specialSubs,
        ]);
    }

    /**
     * Получить только историю (для пагинации)
     */
    public function history(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $history = CashBackService::call()->getHistory($user, $limit * $page);

        $paginatedHistory = $history->forPage($page, $limit)->values();

        return response()->json([
            'data' => $paginatedHistory,
            'current_page' => $page,
            'has_more' => $history->count() > ($page * $limit),
        ]);
    }

    /**
     * Скачать историю в CSV
     */
    public function downloadHistory(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $history = CashBackService::call()->getHistory($user, 1000);

        $csv = "Дата,Тип,Сумма,Описание\n";

        foreach ($history as $item) {
            $csv .= sprintf(
                "%s,%s,%.2f,%s\n",
                $item->created_at->format('d.m.Y H:i'),
                $item->type === 'credit' ? 'Начисление' : 'Списание',
                $item->amount,
                $item->description ?? ''
            );
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cashback_history.csv"',
        ]);
    }
}
