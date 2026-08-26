<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use App\Models\Tenant\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка заказов
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view');
    }

    /**
     * Просмотр конкретного заказа
     */
    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.view');
    }

    /**
     * Обновление статуса заказа
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.update_status');
    }
}
