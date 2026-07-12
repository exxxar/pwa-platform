<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFriend extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'friend_id',
        'status',
        'initiator_id',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'user_id');
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'friend_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'initiator_id');
    }

    // ==========================================
    // SCOPE
    // ==========================================

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('friend_id', $userId);
        });
    }
}
