<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashbackHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_user_id' => $this->tenant_user_id,

            // Пользователь
            'user' => $this->when($this->resource->relationLoaded('user') && $this->user, function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'phone' => $this->user->phone,
                ];
            }),

            // Заказ
            'order' => $this->when($this->resource->relationLoaded('order') && $this->order, function () {
                return [
                    'id' => $this->order->id,
                    'summary_price' => (float) $this->order->summary_price,
                ];
            }),

            // Операция
            'amount' => (float) $this->amount,
            'type' => $this->type, // 'credit' или 'debit'
            'description' => $this->description,
            'level' => (int) $this->level,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
