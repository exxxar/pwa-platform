<?php

namespace App\Services\Admin\TenantData;

use App\Models\Tenant\Transaction;

class TransactionService
{
    /**
     * Получить список транзакций
     */
    public function getTransactions(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Transaction::query()->with(['user', 'order', 'tenant']);

        // Фильтр по tenant_id
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Фильтр по статусу
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Фильтр по провайдеру
        if (!empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        // Фильтр по дате
        if (!empty($filters['from'])) {
            $query->where('paid_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('paid_at', '<=', $filters['to']);
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Получить транзакцию с деталями
     */
    public function getTransactionWithDetails(Transaction $transaction): Transaction
    {
        return $transaction->load(['user', 'order', 'tenant']);
    }
}
