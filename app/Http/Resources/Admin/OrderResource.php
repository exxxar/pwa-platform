<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_user_id' => $this->tenant_user_id,
            'dialog_id' => $this->dialog_id,

            // Пользователь
            'user' => $this->when($this->resource->relationLoaded('tenantUser') && $this->tenantUser, function () {
                return [
                    'id' => $this->tenantUser->id,
                    'name' => $this->tenantUser->name,
                    'phone' => $this->tenantUser->phone,
                    'avatar' => $this->tenantUser->avatar ? asset('storage/' . $this->tenantUser->avatar) : null,
                ];
            }),

            // Тенант
            'tenant' => $this->when($this->resource->relationLoaded('tenant') && $this->tenant, function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'slug' => $this->tenant->slug,
                ];
            }),

            // Товары
            'product_details' => $this->product_details,
            'product_count' => (int) $this->product_count,

            // Цены
            'summary_price' => (float) $this->summary_price,
            'delivery_price' => (float) $this->delivery_price,
            'total_price' => (float) ($this->summary_price + $this->delivery_price),

            // Доставка
            'delivery_range' => (float) $this->delivery_range,
            'delivery_service_info' => $this->delivery_service_info,
            'deliveryman_info' => $this->deliveryman_info,
            'deliveryman_latitude' => $this->deliveryman_latitude ? (float) $this->deliveryman_latitude : null,
            'deliveryman_longitude' => $this->deliveryman_longitude ? (float) $this->deliveryman_longitude : null,
            'delivery_note' => $this->delivery_note,

            // Получатель
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'location_id' => $this->location_id,

            // Статус
            'status' => (int) $this->status,
            'order_type' => (int) $this->order_type,

            // Оплата
            'payed_at' => $this->payed_at?->format('Y-m-d H:i:s'),
            'is_paid' => !is_null($this->payed_at),

            // Диалог
            'dialog' => $this->when($this->resource->relationLoaded('dialog') && $this->dialog, function () {
                return [
                    'id' => $this->dialog->id,
                    'title' => $this->dialog->title,
                    'is_closed' => $this->dialog->is_closed,
                ];
            }),

            // Отзыв
            'review' => $this->when($this->resource->relationLoaded('review') && $this->review, function () {
                return [
                    'id' => $this->review->id,
                    'rating' => $this->review->rating,
                    'comment' => $this->review->comment,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
