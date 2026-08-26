<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_user_id' => $this->tenant_user_id,
            'dialog_id' => $this->dialog_id,

            // Отправитель
            'sender_type' => $this->sender_type,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender_name,

            // Сообщение
            'message' => $this->message,
            'message_type' => $this->message_type,

            // Вложения
            'has_attachment' => $this->has_attachment,
            'attachment' => $this->attachment,
            'attachment_size_formatted' => $this->attachment_size_formatted,

            // Метаданные
            'meta' => $this->meta,

            // Статус прочтения
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->format('Y-m-d H:i:s'),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
