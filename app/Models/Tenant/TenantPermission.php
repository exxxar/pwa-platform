<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TenantPermission extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'label',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles()
    {
        return $this->belongsToMany(
            TenantRole::class,
            'tenant_permission_role',
            'tenant_permission_id',
            'tenant_role_id'
        )->withPivot('tenant_id');
    }
}
