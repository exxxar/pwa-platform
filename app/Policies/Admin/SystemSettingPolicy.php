<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemSettingPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр системных настроек
     */
    public function view(User $user): bool
    {
        return $user->hasPermission('settings.view');
    }

    /**
     * Обновление системных настроек
     */
    public function update(User $user): bool
    {
        return $user->hasPermission('settings.update');
    }

    /**
     * Очистка кэша настроек
     */
    public function clearCache(User $user): bool
    {
        return $user->hasPermission('settings.update');
    }
}
