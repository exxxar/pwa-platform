<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Broadcast extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'tenant_user_id',
        'title',
        'message',
        'status',
        'recipient_type',
        'recipient_filters',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'read_count',
        'failed_count',
    ];

    protected $casts = [
        'recipient_filters' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'read_count' => 'integer',
        'failed_count' => 'integer',
    ];

    // ==========================================
    // СТАТУСЫ
    // ==========================================

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Черновик',
        self::STATUS_SCHEDULED => 'Запланирована',
        self::STATUS_SENDING => 'Отправка',
        self::STATUS_SENT => 'Отправлена',
        self::STATUS_FAILED => 'Ошибка',
        self::STATUS_CANCELLED => 'Отменена',
    ];

    // ==========================================
    // СВЯЗИ
    // ==========================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'tenant_user_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(BroadcastMedia::class)->orderBy('sort_order');
    }

    public function buttons(): HasMany
    {
        return $this->hasMany(BroadcastButton::class)
            ->orderBy('row_index')
            ->orderBy('position');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastRecipient::class);
    }

    // ==========================================
    // SCOPE
    // ==========================================

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->total_recipients === 0) return 0;
        return round(($this->sent_count / $this->total_recipients) * 100, 1);
    }

    public function getMediaImagesAttribute()
    {
        return $this->media->where('type', 'image');
    }

    public function getMediaVideosAttribute()
    {
        return $this->media->where('type', 'video');
    }

    public function getMediaAudiosAttribute()
    {
        return $this->media->where('type', 'audio');
    }

    public function getKeyboardAttribute(): array
    {
        $keyboard = [];

        foreach ($this->buttons as $button) {
            $row = $button->row_index;
            if (!isset($keyboard[$row])) {
                $keyboard[$row] = [];
            }

            $keyboard[$row][] = [
                'text' => $button->text,
                $button->type === 'url' ? 'url' : 'callback_data' =>
                    $button->type === 'url' ? $button->url : $button->callback_data,
            ];
        }

        return array_values($keyboard);
    }
}
