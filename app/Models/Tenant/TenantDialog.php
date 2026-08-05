<?php

namespace App\Models\Tenant;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    protected $appends = ["unread_count","has_unread"];

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

    /**
     * Непрочитанные сообщения диалога
     */
    /**
     * Непрочитанные сообщения диалога
     * 🆕 Фильтруем по реальному отправителю, а не по tenant_user_id
     */
    public function unreadMessages()
    {
        return $this->messages()
            ->where('is_read', false)
            ->where(function ($q) {
                // Сообщения от админа/системы
                $q->whereIn('sender_type', ['admin', 'system'])
                    // ИЛИ сообщения от другого пользователя (не владельца диалога)
                    ->orWhere(function ($sub) {
                        $sub->where('sender_type', 'user')
                            ->whereColumn('sender_id', '!=', 'tenant_user_id');
                    });
            });
    }

    /**
     * Количество непрочитанных сообщений
     */
    public function getUnreadCountAttribute(): int
    {
        return $this->unreadMessages()->count();
    }

    /**
     * Отметить все сообщения диалога как прочитанные
     */
    public function markAllAsRead(): int
    {
        return $this->unreadMessages()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'dialog_id');
    }

    /**
     * Есть ли непрочитанные сообщения
     */
    public function getHasUnreadAttribute(): bool
    {
        return $this->unreadMessages()->exists();
    }
}
