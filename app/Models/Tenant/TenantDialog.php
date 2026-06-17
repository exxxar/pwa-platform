<?php

namespace App\Models\Tenant;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantDialog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tenant_user_id',
        'external_task_id',
        'type',
        'title',
        'is_closed',
        'last_message_at',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(TenantUser::class, 'tenant_user_id');
    }

    public function messages()
    {
        return $this->hasMany(TenantMessage::class, 'dialog_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(TenantMessage::class, 'dialog_id')->latest();
    }
}
