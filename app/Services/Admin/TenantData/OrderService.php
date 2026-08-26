<?php

namespace App\Services\Admin\TenantData;

use App\Models\Tenant\Order;

class OrderService
{
    /**
     * Получить список заказов
     */
    public function getOrders(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Order::query()->with(['tenantUser', 'tenant']);

        // Фильтр по tenant_id
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Фильтр по статусу
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Фильтр по пользователю
        if (!empty($filters['tenant_user_id'])) {
            $query->where('tenant_user_id', $filters['tenant_user_id']);
        }

        // Фильтр по дате оплаты
        if (!empty($filters['payed_from'])) {
            $query->where('payed_at', '>=', $filters['payed_from']);
        }
        if (!empty($filters['payed_to'])) {
            $query->where('payed_at', '<=', $filters['payed_to']);
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Получить заказ с детальной информацией
     */
    public function getOrderWithDetails(Order $order): Order
    {
        return $order->load([
            'tenantUser',
            'tenant',
            'dialog',
            'review',
        ]);
    }

    /**
     * Обновить статус заказа
     */
    public function updateStatus(Order $order, int $status): Order
    {
        $order->update(['status' => $status]);

        // Опционально: логирование смены статуса, уведомления

        return $order;
    }
}
