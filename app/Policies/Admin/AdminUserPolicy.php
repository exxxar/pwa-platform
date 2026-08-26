<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminUserPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка админов
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin_users.view');
    }

    /**
     * Просмотр конкретного админа
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasPermission('admin_users.view');
    }

    /**
     * Создание админа
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('admin_users.create');
    }

    /**
     * Обновление админа
     */
    public function update(User $user, User $model): bool
    {
        // Нельзя редактировать самого себя через этот метод
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('admin_users.update');
    }

    /**
     * Удаление админа
     */
    public function delete(User $user, User $model): bool
    {
        // Нельзя удалить самого себя
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('admin_users.delete');
    }
}
