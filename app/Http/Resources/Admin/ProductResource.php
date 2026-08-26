<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'old_price' => $this->old_price ? (float) $this->old_price : null,
            'sku' => $this->sku,
            'description' => $this->description,

            // Изображения
            'images' => $this->images ? collect($this->images)->map(fn($img) => asset('storage/' . $img))->toArray() : [],

            // Габариты
            'dimensions' => $this->dimensions,

            // Условия доставки
            'delivery_terms' => $this->delivery_terms,

            // Внешний источник
            'external_source' => $this->external_source,
            'external_id' => $this->external_id,

            // Конфигурация
            'config' => $this->config,

            // Статусы
            'is_active' => $this->is_active,
            'not_for_delivery' => $this->not_for_delivery,
            'in_stop_list' => $this->in_stop_list,
            'is_composite' => $this->is_composite,
            'is_weight_product' => $this->is_weight_product,

            // Сортировка
            'order_position' => (int) $this->order_position,

            // Категории
            'categories' => $this->when($this->resource->relationLoaded('categories'), function () {
                return $this->categories->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'icon' => $cat->icon,
                ]);
            }),

            // Рейтинг
            'rating' => (float) $this->rating,
            'average_rating' => $this->getAverageRatingAttribute(),
            'reviews_count' => $this->getReviewsCountAttribute(),

            // Тенант
            'tenant' => $this->when($this->resource->relationLoaded('tenant') && $this->tenant, function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                ];
            }),

            'tenant_name' => $this->tenant_name,

            // Ингредиенты
            'ingredient_groups' => $this->when($this->resource->relationLoaded('ingredientGroups'), function () {
                return $this->ingredientGroups->map(fn($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'sort_order' => $group->sort_order,
                    'ingredients' => $group->ingredients->map(fn($ing) => [
                        'id' => $ing->id,
                        'name' => $ing->name,
                        'price' => (float) $ing->price,
                    ]),
                ]);
            }),

            // Компоненты (для составных товаров)
            'components' => $this->when($this->resource->relationLoaded('components'), function () {
                return $this->components->map(fn($comp) => [
                    'id' => $comp->id,
                    'name' => $comp->name,
                    'quantity' => $comp->pivot->quantity,
                    'is_default' => $comp->pivot->is_default,
                ]);
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
