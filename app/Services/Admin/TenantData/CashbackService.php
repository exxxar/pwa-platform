<?php

namespace App\Services\Admin\TenantData;

use App\Models\Tenant\CashBack;
use App\Models\Tenant\CashBackHistory;

class CashbackService
{
    /**
     * Получить историю кэшбэка
     */
    public function getHistory(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = CashBackHistory::query()->with(['user', 'order']);

        // Фильтр по tenant_id
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Фильтр по пользователю
        if (!empty($filters['tenant_user_id'])) {
            $query->where('tenant_user_id', $filters['tenant_user_id']);
        }

        // Фильтр по типу (credit/debit)
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Сортировка
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Ручное начисление/списание кэшбэка
     */
    public function manuallyAdjust(int $userId, float $amount, string $type, string $description = ''): CashBackHistory
    {
        $history = CashBackHistory::create([
            'tenant_id' => auth()->user()->tenant_id ?? null, // Или получить из пользователя
            'tenant_user_id' => $userId,
            'amount' => abs($amount),
            'type' => $type, // 'credit' или 'debit'
            'description' => $description,
            'level' => 0,
        ]);

        // Обновляем баланс кэшбэка пользователя
        $cashback = CashBack::firstOrCreate(
            ['tenant_user_id' => $userId],
            ['amount' => 0]
        );

        if ($type === 'credit') {
            $cashback->increment('amount', $amount);
        } else {
            $cashback->decrement('amount', $amount);
        }

        return $history;
    }
}
