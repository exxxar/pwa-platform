<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'type' => $this->type ?? 'manual',
            'pricing_type' => $this->pricing_type ?? 'sum',
            'fixed_price' => $this->fixed_price ? (float)$this->fixed_price : null,
            'discount' => (int)($this->discount ?? 0),
            'image' => $this->image,
            'is_active' => (bool)($this->is_active ?? true),
            'in_stop_list' => (bool)($this->in_stop_list ?? false),
            'order_position' => (int)($this->order_position ?? 0),
            'config' => $this->config,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'collection_categories' => CollectionCategoryResource::collection(
                $this->whenLoaded('collectionCategories')
            ),

            // Для совместимости со старым кодом фронта
            'products_count' => $this->whenLoaded('collectionCategories', function () {
                return $this->collectionCategories->sum(
                    fn ($cat) => $cat->products->count()
                );
            }),
        ];
    }
}
