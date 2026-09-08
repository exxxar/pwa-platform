<?php

namespace App\Models\Agent;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentClient extends Model
{
    protected $fillable = [
        'agent_id',
        'tenant_user_id',
        'status',
        'notes',
    ];

    // === Отношения ===
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'client_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
