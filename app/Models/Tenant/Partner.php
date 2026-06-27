<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tenant_partner_id',
        'title',
        'description',
        'order_position',
        'image',
        'is_active',
        'extra_charge',
        'config',
        'legal_info',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'legal_info' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function partner()
    {
        return $this->belongsTo(Tenant::class, 'tenant_partner_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (удобно)
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_position');
    }

    /**
     * 🆕 Отношение к товарам партнёра через tenant_partner_id
     * HasMany: Partner -> Tenant (по tenant_partner_id) -> Products
     */
    public function partnerProducts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            Product::class,      // целевая модель
            'tenant_id',         // FK в products
            'tenant_partner_id'  // FK в partners
        );
    }

    /**
     * 🆕 Отношение к активным категориям партнёра
     */
    public function partnerCategories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            Category::class,
            'tenant_id',
            'tenant_partner_id'
        );
    }
}
