<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр дашборда
     */
    public function viewDashboard(User $user): bool
    {
        return $user->hasPermission('reports.view');
    }

    /**
     * Просмотр статистики тенанта
     */
    public function viewTenantStats(User $user): bool
    {
        return $user->hasPermission('reports.view');
    }
}
