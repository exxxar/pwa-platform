<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use App\Models\Tenant\Tenant;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка тенантов
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tenants.view');
    }

    /**
     * Просмотр конкретного тенанта
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.view');
    }

    /**
     * Создание тенанта
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('tenants.create');
    }

    /**
     * Обновление тенанта
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.update');
    }

    /**
     * Удаление тенанта
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.delete');
    }

    /**
     * Переключение статуса активности
     */
    public function toggleStatus(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.update');
    }

    /**
     * Изменение баланса тенанта
     */
    public function updateBalance(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.update_balance');
    }
}
