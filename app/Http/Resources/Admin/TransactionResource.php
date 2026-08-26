<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_user_id' => $this->tenant_user_id,
            'order_id' => $this->order_id,

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
                    'status' => $this->order->status,
                ];
            }),

            // Тенант
            'tenant' => $this->when($this->resource->relationLoaded('tenant') && $this->tenant, function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                ];
            }),

            // Платежные данные
            'provider' => $this->provider,
            'external_payment_id' => $this->external_payment_id,

            // Сумма
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'formatted_amount' => $this->formatted_amount,

            // Статус
            'status' => $this->status,

            // Метаданные
            'meta' => $this->meta,

            // Дата оплаты
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
