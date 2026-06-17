<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PromoCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'description',
        'slot_amount',
        'cashback_amount',
        'max_activation_count',
        'is_active',
        'activate_price',
        'available_to',
        'config',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'slot_amount' => 'integer',
        'config' => 'array',
        'cashback_amount' => 'double',
        'activate_price' => 'double',
        'is_active' => 'boolean',
        'available_to' => 'datetime:Y-m-d H:i:s',
    ];

    protected $with = ["users"];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }


    public function users(): BelongsToMany
    {
        return $this->belongsToMany(TenantUser::class);
    }



}
