<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TenantPage extends Model
{
    protected $fillable = [
        'tenant_id',
        'slug',
        'title',
        'is_system',
        'structure',
        'settings',
    ];

    protected $casts = [
        'structure' => 'array',
        'settings' => 'array',
        'is_system' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function params()
    {
        return $this->hasMany(TenantPageParam::class);
    }
}
