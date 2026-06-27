<?php

namespace App\Models\Tenant;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TenantMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tenant_user_id',
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

    protected $appends = ["has_attachment","attachment","message_type","attachment_size_formatted"];

    public function dialog()
    {
        return $this->belongsTo(TenantDialog::class, 'dialog_id');
    }

    public function user()
    {
        return $this->belongsTo(TenantUser::class, 'tenant_user_id');
    }


    // ==========================================
    // 🆕 АКСЕССОРЫ ДЛЯ ВЛОЖЕНИЙ
    // ==========================================

    /**
     * Есть ли вложение у сообщения
     */
    public function getHasAttachmentAttribute(): bool
    {
        return isset($this->meta['attachment']);
    }

    /**
     * Получить данные вложения
     */
    public function getAttachmentAttribute(): ?array
    {
        $attachment = $this->meta['attachment'] ?? null;

        if (!$attachment) {
            return null;
        }

        // Обновляем URL (на случай, если домен изменился)
        if (isset($attachment['path'])) {
            try {
                $attachment['url'] = Storage::disk('public')->url($attachment['path']);
                $attachment['exists'] = Storage::disk('public')->exists($attachment['path']);
            } catch (\Throwable $e) {
                $attachment['exists'] = false;
            }
        }

        return $attachment;
    }

    /**
     * Тип сообщения (для удобства на фронте)
     */
    public function getMessageTypeAttribute(): string
    {
        return $this->meta['type'] ?? 'text';
    }

    /**
     * Размер файла в читаемом виде
     */
    public function getAttachmentSizeFormattedAttribute(): ?string
    {
        $size = $this->meta['attachment']['size'] ?? null;

        if (!$size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

}
