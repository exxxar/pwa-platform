<?php

namespace App\Observers;

use App\Models\Tenant\TenantUser;
use App\Services\Tenants\AchievementService;

class TenantUserObserver
{
    public function created(TenantUser $user)
    {
        app(AchievementService::class)->initializeUserStats($user->id);
    }
}
