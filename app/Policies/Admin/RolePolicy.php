<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use App\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка ролей
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roles.view');
    }

    /**
     * Просмотр конкретной роли
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.view');
    }

    /**
     * Создание роли
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('roles.create');
    }

    /**
     * Обновление роли
     */
    public function update(User $user, Role $role): bool
    {
        // Нельзя изменять системную роль super_admin
        if ($role->name === 'super_admin') {
            return false;
        }

        return $user->hasPermission('roles.update');
    }

    /**
     * Удаление роли
     */
    public function delete(User $user, Role $role): bool
    {
        // Нельзя удалить системную роль super_admin
        if ($role->name === 'super_admin') {
            return false;
        }

        return $user->hasPermission('roles.delete');
    }
}
