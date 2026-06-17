<?php

namespace App\Models\Tenant;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'dialog_id',
        'message',
        'meta',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function dialog()
    {
        return $this->belongsTo(TenantDialog::class, 'dialog_id');
    }


}
