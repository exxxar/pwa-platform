<?php

namespace App\Models\Agent;

use App\Models\Tenant\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentReferral extends Model
{
    protected $fillable = [
        'agent_id',
        'referral_code',
        'referred_tenant_user_id',
        'ip_address',
        'user_agent',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'converted',
        'converted_at',
        'bonus_amount',
    ];

    protected $casts = [
        'converted' => 'boolean',
        'converted_at' => 'datetime',
        'bonus_amount' => 'decimal:2',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'referred_tenant_user_id');
    }

    public function scopeConverted($query)
    {
        return $query->where('converted', true);
    }
}
