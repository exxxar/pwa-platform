<?php

namespace App\Models\Tenant;


use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'image',
        'description',
        'is_active',
        'discount',
        'order_position',
        'config',

    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
        'config' => 'array',
    ];


    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }

    public function products() {
        return $this->belongsToMany(Product::class, 'collection_product')
            ->withPivot('sort_order');
    }
}
