<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDocument extends Model
{
    protected $fillable = [
        'agent_id',
        'type',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'is_required',
        'is_uploaded',
        'uploaded_at',
        'verification_status',
        'rejection_reason',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_uploaded' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeUploaded($query)
    {
        return $query->where('is_uploaded', true);
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }
}
