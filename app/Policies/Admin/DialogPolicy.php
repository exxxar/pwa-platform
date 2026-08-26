<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use App\Models\Tenant\TenantDialog;
use Illuminate\Auth\Access\HandlesAuthorization;

class DialogPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка диалогов
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('dialogs.view');
    }

    /**
     * Просмотр конкретного диалога
     */
    public function view(User $user, TenantDialog $dialog): bool
    {
        return $user->hasPermission('dialogs.view');
    }

    /**
     * Ответ в диалог
     */
    public function reply(User $user, TenantDialog $dialog): bool
    {
        return $user->hasPermission('dialogs.reply');
    }

    /**
     * Закрытие диалога
     */
    public function close(User $user, TenantDialog $dialog): bool
    {
        return $user->hasPermission('dialogs.close');
    }
}
