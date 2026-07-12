<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralReward extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'order_id',
        'from_referral_id',
        'level',
        'type',
        'amount',
        'percent',
        'description',
    ];

    protected $casts = [
        'level' => 'integer',
        'amount' => 'float',
        'percent' => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function fromReferral(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'from_referral_id');
    }
}
