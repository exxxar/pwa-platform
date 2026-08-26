<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use App\Models\Tenant\UserReferral;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReferralPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка рефералов
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('referrals.view');
    }

    /**
     * Просмотр цепочки рефералов пользователя
     */
    public function showChain(User $user): bool
    {
        return $user->hasPermission('referrals.view');
    }

    /**
     * Просмотр статистики
     */
    public function stats(User $user): bool
    {
        return $user->hasPermission('referrals.view');
    }

    /**
     * Ручное изменение реферальной связи
     */
    public function manuallyAdjust(User $user, UserReferral $referral): bool
    {
        return $user->hasPermission('referrals.adjust');
    }
}
