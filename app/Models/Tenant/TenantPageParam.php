<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TenantPageParam extends Model
{
    protected $fillable = [
        'tenant_page_id',
        'key',
        'type',
        'value',
    ];

    public function page()
    {
        return $this->belongsTo(TenantPage::class, 'tenant_page_id');
    }
}
