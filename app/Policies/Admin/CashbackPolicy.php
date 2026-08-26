<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashbackPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр истории кэшбэка
     */
    public function viewHistory(User $user): bool
    {
        return $user->hasPermission('cashback.view');
    }

    /**
     * Ручное начисление/списание кэшбэка
     */
    public function manuallyAdjust(User $user): bool
    {
        return $user->hasPermission('cashback.adjust');
    }
}
