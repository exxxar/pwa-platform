<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantUserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_user_id',
        'tenant_id',
        'title',
        'address',
        'city',
        'country',
        'lat',
        'lng',
        'is_default',
        'meta',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'meta' => 'array',
        'lat' => 'float',
        'lng' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(TenantUser::class, 'tenant_user_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
