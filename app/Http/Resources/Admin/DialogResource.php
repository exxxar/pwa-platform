<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DialogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_user_id' => $this->tenant_user_id,
            'external_task_id' => $this->external_task_id,
            'type' => $this->type,
            'title' => $this->title,
            'is_closed' => $this->is_closed,

            // Пользователь
            'user' => $this->when($this->resource->relationLoaded('user') && $this->user, function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'phone' => $this->user->phone,
                    'avatar' => $this->user->avatar ? asset('storage/' . $this->user->avatar) : null,
                ];
            }),

            // Последнее сообщение
            'last_message' => $this->when($this->resource->relationLoaded('lastMessage') && $this->lastMessage, function () {
                return [
                    'id' => $this->lastMessage->id,
                    'message' => $this->lastMessage->message,
                    'sender_type' => $this->lastMessage->sender_type,
                    'sender_name' => $this->lastMessage->sender_name,
                    'created_at' => $this->lastMessage->created_at?->format('Y-m-d H:i:s'),
                ];
            }),

            // Непрочитанные
            'unread_count' => (int) $this->unread_count,
            'has_unread' => $this->has_unread,

            'last_message_at' => $this->last_message_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
