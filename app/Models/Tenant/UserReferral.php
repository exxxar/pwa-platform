<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReferral extends Model
{
    protected $fillable = [
        'tenant_id',
        'referrer_id',
        'referred_id',
        'level',
        'is_active',
        'registered_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
        'registered_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'referred_id');
    }

    // ==========================================
    // SCOPE
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopeForReferrer($query, int $referrerId)
    {
        return $query->where('referrer_id', $referrerId);
    }
}
