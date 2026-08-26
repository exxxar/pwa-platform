<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $fillable = [
        'tenant_user_id',
        'achievement_id',
        'unlocked_at',
        'reward_claimed',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'reward_claimed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'tenant_user_id');
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    /**
     * Отметить награду как полученную
     */
    public function claimReward(): void
    {
        $this->update(['reward_claimed' => 1]);

        // Начисляем награду
        if ($this->achievement->reward_type && $this->achievement->reward_value > 0) {
            app(\App\Services\Tenants\AchievementService::class)->giveReward($this);
        }
    }
}
