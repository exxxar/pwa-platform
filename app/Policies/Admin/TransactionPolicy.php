<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use App\Models\Tenant\Transaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка транзакций
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('transactions.view');
    }

    /**
     * Просмотр конкретной транзакции
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->hasPermission('transactions.view');
    }
}
