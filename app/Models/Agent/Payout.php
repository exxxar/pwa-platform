<?php

namespace App\Models\Agent;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $fillable = [
        'agent_id',
        'number',
        'amount',
        'status',
        'bank_details',
        'comment',
        'rejection_reason',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bank_details' => 'array',
        'processed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payout $payout) {
            if (empty($payout->number)) {
                $payout->number = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
            // Сохраняем снимок реквизитов агента на момент заявки
            if (empty($payout->bank_details) && $payout->agent) {
                $payout->bank_details = [
                    'bank_account' => $payout->agent->bank_account,
                    'bik' => $payout->agent->bik,
                    'bank_name' => $payout->agent->bank_name,
                    'inn' => $payout->agent->inn,
                ];
            }
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
