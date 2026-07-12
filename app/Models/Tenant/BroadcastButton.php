<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastButton extends Model
{
    protected $fillable = [
        'broadcast_id',
        'text',
        'url',
        'callback_data',
        'type',
        'row_index',
        'position',
    ];

    protected $casts = [
        'row_index' => 'integer',
        'position' => 'integer',
    ];

    public const TYPE_CALLBACK = 'callback';
    public const TYPE_URL = 'url';

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }
}
