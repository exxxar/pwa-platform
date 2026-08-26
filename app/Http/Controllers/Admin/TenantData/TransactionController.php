<?php

namespace App\Http\Controllers\Admin\TenantData;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TransactionResource;
use App\Models\Tenant\Transaction;
use App\Services\Admin\TenantData\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Список транзакций с фильтрацией и пагинацией
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Transaction::class);

        $filters = $request->only([
            'tenant_id',
            'status',
            'provider',
            'from',
            'to',
            'sort_by',
            'sort_dir',
        ]);
        $perPage = $request->input('per_page', 15);

        $transactions = $this->transactionService->getTransactions($filters, $perPage);

        return TransactionResource::collection($transactions);
    }

    /**
     * Просмотр транзакции с детальной информацией
     */
    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        $transaction = $this->transactionService->getTransactionWithDetails($transaction);

        return new TransactionResource($transaction);
    }
}
