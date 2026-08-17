<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'icon',
        'is_active',
        'order_position',
        'external_id'
    ];

    protected $casts = [
      "is_active"=>"boolean"
    ];

    protected $appends = ["title"];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }


    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_categories',  // pivot-таблица
            'category_id',         // FK в pivot к categories
            'product_id'           // FK в pivot к products
        );
    }


    public function getTitleAttribute()
    {
        return $this->attributes['name'] ?? null;
    }

    public static function getCategoriesWithProducts(int $tenantId, int $productsPerCategory = 4)
    {
        // 🎯 КЭШИРОВАНИЕ: кэшируем результат на 5 минут
        $cacheKey = "categories_with_products_{$tenantId}_{$productsPerCategory}";

        return cache()->remember($cacheKey, 300, function () use ($tenantId, $productsPerCategory) {
            // 1) Получаем категории с количеством товаров ОДНИМ запросом
            $categories = Category::query()
                ->select('categories.id', 'categories.name', 'categories.order_position', 'categories.is_active')
                ->selectRaw('COUNT(DISTINCT products.id) as products_count')
                ->leftJoin('product_categories as ppc', 'ppc.category_id', '=', 'categories.id')
                ->leftJoin('products', function ($join) use ($tenantId) {
                    $join->on('ppc.product_id', '=', 'products.id')
                        ->where('products.tenant_id', '=', $tenantId)
                        ->whereNull('products.deleted_at')
                        ->where('products.in_stop_list', '=', false);
                })
                ->where('categories.tenant_id', $tenantId)
                ->where('categories.is_active', true)
                ->groupBy('categories.id', 'categories.name', 'categories.order_position', 'categories.is_active')
                ->havingRaw('COUNT(DISTINCT products.id) > 0')
                ->orderBy('categories.order_position')
                ->get();

            if ($categories->isEmpty()) {
                return [];
            }

            $categoryIds = $categories->pluck('id')->toArray();

            // 2) Получаем товары для всех категорий ОДНИМ запросом
            $products = Product::query()
                ->select(
                    'products.id',
                    'products.name',
                    'products.price',
                    'products.images',
                    'products.is_composite',
                    'products.description',
                    'products.order_position',
                    'ppc.category_id'
                )
                ->join('product_categories as ppc', 'ppc.product_id', '=', 'products.id')
                ->whereIn('ppc.category_id', $categoryIds)
                ->where('products.tenant_id', $tenantId)
                ->whereNull('products.deleted_at')
                ->where('products.in_stop_list', false)
                ->orderBy('products.order_position')
                ->orderBy('products.id')
                ->get();

            // 3) Группируем товары по category_id
            $productsByCategory = $products->groupBy('category_id');

            // 4) Собираем результат
            $result = [];
            foreach ($categories as $category) {
                $categoryProducts = $productsByCategory->get($category->id, collect());

                $result[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'order_position' => $category->order_position,
                    'is_active' => $category->is_active,
                    'products' => $categoryProducts->take($productsPerCategory)->values()->toArray(),
                    'products_count' => $category->products_count,
                ];
            }

            return $result;
        });
    }

}
