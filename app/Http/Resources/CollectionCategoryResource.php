<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CollectionCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'collection_id' => $this->collection_id,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            'selection_rule' => $this->selection_rule ?? 'one',
            'sort_order' => (int)($this->sort_order ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'category' => new CategoryResource($this->whenLoaded('category')),

            'products' => ProductResource::collection(
                $this->whenLoaded('products')
            ),

            'products_count' => $this->whenLoaded('products', fn () => $this->products->count()),
        ];
    }
}
