<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use App\Models\Tenant\TenantUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantUserPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка пользователей тенанта
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tenant_users.view');
    }

    /**
     * Просмотр конкретного пользователя
     */
    public function view(User $user, TenantUser $tenantUser): bool
    {
        return $user->hasPermission('tenant_users.view');
    }

    /**
     * Обновление пользователя
     */
    public function update(User $user, TenantUser $tenantUser): bool
    {
        return $user->hasPermission('tenant_users.update');
    }

    /**
     * Удаление пользователя
     */
    public function delete(User $user, TenantUser $tenantUser): bool
    {
        return $user->hasPermission('tenant_users.delete');
    }

    /**
     * Блокировка/разблокировка пользователя
     */
    public function toggleBlock(User $user, TenantUser $tenantUser): bool
    {
        return $user->hasPermission('tenant_users.block');
    }

    /**
     * Выдача VIP статуса
     */
    public function grantVip(User $user, TenantUser $tenantUser): bool
    {
        return $user->hasPermission('tenant_users.grant_vip');
    }

    /**
     * Отзыв VIP статуса
     */
    public function revokeVip(User $user, TenantUser $tenantUser): bool
    {
        return $user->hasPermission('tenant_users.revoke_vip');
    }
}
