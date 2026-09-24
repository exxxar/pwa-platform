<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    protected $fillable = [
        'tenant_id', 'title', 'short_text', 'full_text', 'image',
        'badge', 'button_text', 'action_type', 'action_value',
        'position', 'is_active', 'starts_at', 'ends_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'position'   => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope: только активные и подходящие по дате
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }
}
