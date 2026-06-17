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
}
