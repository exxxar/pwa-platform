<?php

namespace App\Models\Tenant;


use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'tenant_user_id',
        'dialog_id',
        'delivery_service_info',
        'deliveryman_info',
        'product_details',
        'product_count',
        'summary_price',
        'delivery_price',
        'delivery_range',
        'deliveryman_latitude',
        'deliveryman_longitude',
        'delivery_note',
        'receiver_name',
        'receiver_phone',
        'location_id',
        'status',
        'order_type',
        'payed_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'dialog_id' => 'integer',
        'tenant_user_id' => 'integer',
        'product_details' => 'array',
        'product_count' => 'integer',
        'summary_price' => 'float',
        'delivery_price' => 'float',
        'delivery_range' => 'float',
        'deliveryman_latitude' => 'float',
        'deliveryman_longitude' => 'float',
        'location_id' => 'integer',
        'status' => 'integer',
        'order_type' => 'integer',
        'payed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::observe(OrderObserver::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class);
    }

    /**
     * Отзывы к заказу
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 🆕 Связь с диалогом, созданным для этого заказа
     */
    public function dialog(): BelongsTo
    {
        return $this->belongsTo(TenantDialog::class, 'dialog_id');
    }

    public function referralRewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class, 'order_id');
    }

}
