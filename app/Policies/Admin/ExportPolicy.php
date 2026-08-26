<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExportPolicy
{
    use HandlesAuthorization;

    /**
     * Экспорт пользователей
     */
    public function exportUsers(User $user): bool
    {
        return $user->hasPermission('exports.users');
    }

    /**
     * Экспорт заказов
     */
    public function exportOrders(User $user): bool
    {
        return $user->hasPermission('exports.orders');
    }

    /**
     * Экспорт транзакций
     */
    public function exportTransactions(User $user): bool
    {
        return $user->hasPermission('exports.transactions');
    }

    /**
     * Экспорт тенантов
     */
    public function exportTenants(User $user): bool
    {
        return $user->hasPermission('exports.tenants');
    }
}
