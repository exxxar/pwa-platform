<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_user_id',
        'status',
        'verification_status',
        'balance',
        'pending_balance',
        'total_earned',
        'legal_type',
        'inn',
        'ogrn',
        'bank_account',
        'bik',
        'bank_name',
        'referral_code',
        'referrals_count',
        'clients_count',
        'tenant_count',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'is_required' => 'boolean',
        'referrals_count' => 'integer',
        'clients_count' => 'integer',
        'tenant_count' => 'integer',
    ];

    // === Хуки ===
    protected static function booted(): void
    {
        static::creating(function (Agent $agent) {
            if (empty($agent->referral_code)) {
                $agent->referral_code = 'agent_' . Str::upper(Str::random(8));
            }
        });
    }

    // === Отношения ===
    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(AgentClient::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AgentDocument::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AgentReferral::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // === Скоупы ===
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // === Аксессоры ===
    public function getIsVerifiedAttribute(): bool
    {
        return $this->status === 'verified';
    }

    public function getReferralUrlAttribute(): string
    {
        return config('app.url') . '/?ref=' . $this->referral_code;
    }
}
