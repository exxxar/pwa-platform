<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use App\Models\Admin\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка разрешений
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('permissions.view');
    }

    /**
     * Просмотр конкретного разрешения
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->hasPermission('permissions.view');
    }

    /**
     * Создание разрешения
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('permissions.create');
    }

    /**
     * Обновление разрешения
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->hasPermission('permissions.update');
    }

    /**
     * Удаление разрешения
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasPermission('permissions.delete');
    }
}
