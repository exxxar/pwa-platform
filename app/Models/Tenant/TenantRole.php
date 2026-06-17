<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TenantRole extends Model
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

    public function users()
    {
        return $this->belongsToMany(
            TenantUser::class,
            'tenant_role_user',
            'tenant_role_id',
            'tenant_user_id'
        )->withPivot('tenant_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(
            TenantPermission::class,
            'tenant_permission_role',
            'tenant_role_id',
            'tenant_permission_id'
        )->withPivot('tenant_id');
    }
}
