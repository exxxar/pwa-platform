<?php

namespace App\Models\Tenant;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'table_id',
        'delivery_service_info',
        'deliveryman_info',
        'product_details',
        'product_count',
        'summary_price',
        'delivery_price',
        'delivery_range',
        'deliveryman_latitude',
        'deliveryman_longitude',
        "service_rating",
        "service_review",
        'delivery_note',
        'receiver_name',
        'receiver_phone',
        "location_id",
        'status',
        'order_type',
        'is_cashback_crediting',
        'payed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'bot_id' => 'integer',
        'table_id' => 'integer',
        'deliveryman_id' => 'integer',
        'status' => 'integer',
        'customer_id' => 'integer',
        'is_cashback_crediting' => 'boolean',
        'delivery_service_info' => 'array',
        'deliveryman_info' => 'array',
        'product_details' => 'array',
        'summary_price' => 'double',
        'delivery_price' => 'double',
        'delivery_range' => 'double',

        'deliveryman_latitude' => 'double',
        'deliveryman_longitude' => 'double',
        'payed_at' => 'timestamp',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class);
    }



    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

}
