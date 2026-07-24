<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PartnerCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($partner) {
                return [
                    'id' => $partner->id,
                    'tenant_id' => $partner->tenant_id,
                    'tenant_partner_id' => $partner->tenant_partner_id,
                    'title' => $partner->title,
                    'name' => $partner->title, // для совместимости
                    'description' => $partner->description,
                    'tags' => $partner->tags ?? [],
                    'image' => $partner->image,
                    'is_active' => $partner->is_active,
                    'extra_charge' => $partner->extra_charge,
                    'order_position' => $partner->order_position,
                    'config' => $partner->config,
                    'legal_info' => $partner->legal_info,

                    // 🆕 Новые поля статистики
                    'products_count' => $partner->products_count ?? 0,
                    'products_sum' => round($partner->products_sum ?? 0, 2),

                    // Форматированные значения для фронта
                    'products_count_formatted' => number_format($partner->products_count ?? 0, 0, '.', ' '),
                    'products_sum_formatted' => number_format($partner->products_sum ?? 0, 0, '.', ' ') . ' ₽',

                    'created_at' => $partner->created_at,
                    'updated_at' => $partner->updated_at,
                ];
            })->toArray(),
        ];
    }
}
