<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'tenant_user_id',
        'product_id',
        'order_id',
        'rating',
        'text',
        'title',
        'status',
        'images',
        'likes_count',
        'dislikes_count',
        'admin_response',
        'responded_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'status' => 'integer',
        'images' => 'array',
        'responded_at' => 'datetime',
    ];

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    // ==========================================
    // СВЯЗИ
    // ==========================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'tenant_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ==========================================
    // СКОУПЫ
    // ==========================================

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    // ==========================================
    // МЕТОДЫ
    // ==========================================

    public function approve(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    public function reject(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    public function respond(string $response): void
    {
        $this->update([
            'admin_response' => $response,
            'responded_at' => now(),
        ]);
    }
}
