<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnerCollection extends ResourceCollection
{
    protected function publicStorageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        return Storage::disk('public')->url($path);
    }

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
                    'image' => $this->publicStorageUrl($partner->image), // ✅ Изменено
                    'is_active' => $partner->is_active,
                    'extra_charge' => $partner->extra_charge,
                    'order_position' => $partner->order_position,
                    'config' => $partner->config,
                    'legal_info' => $partner->legal_info,
                    'address' => $partner->address,
                    'shop_coords' => $partner->shop_coords,

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
