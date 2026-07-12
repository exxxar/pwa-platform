<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_user_id' => $this->tenant_user_id,
            'product_id' => $this->product_id,
            'order_id' => $this->order_id,

            // Данные отзыва
            'rating' => $this->rating,
            'text' => $this->text,
            'title' => $this->title,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'images' => $this->images,

            // Статистика
            'likes_count' => $this->likes_count,
            'dislikes_count' => $this->dislikes_count,

            // Ответ администратора
            'admin_response' => $this->admin_response,
            'responded_at' => $this->responded_at?->format('Y-m-d H:i:s'),

            // Связи
            'tenant_user' => $this->whenLoaded('tenantUser', function () {
                return [
                    'id' => $this->tenantUser->id,
                    'name' => $this->tenantUser->name,
                    'avatar' => $this->tenantUser->avatar,
                ];
            }),

            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'images' => $this->product->images,
                ];
            }),

            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'summary_price' => $this->order->summary_price,
                ];
            }),

            // Даты
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
