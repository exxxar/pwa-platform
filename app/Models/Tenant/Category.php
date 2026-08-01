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

    public static function getCategoriesWithProducts(int $tenantId)
    {
        // 1) Категории
        $categories = Category::query()
            ->select('id', 'name', 'order_position', 'is_active')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('order_position')
            ->get();

        if ($categories->isEmpty()) {
            return [];
        }

        $categoryIds = $categories->pluck('id')->toArray();

        // 2) 🆕 Получаем ОБЩЕЕ количество товаров по каждой категории (для кнопки "Загрузить ещё")
        $productsCount = Product::query()
            ->selectRaw('ppc.category_id, COUNT(products.id) as count')
            ->join('product_categories as ppc', 'ppc.product_id', '=', 'products.id')
            ->whereIn('ppc.category_id', $categoryIds)
            ->where('products.tenant_id', $tenantId)
            ->whereNull('products.deleted_at')
            ->where('products.in_stop_list', false)
            ->groupBy('ppc.category_id')
            ->pluck('count', 'category_id') // Вернет массив вида [1 => 10, 2 => 5]
            ->toArray();

        // 3) Получаем все товары (чтобы сгруппировать их)
        $products = Product::query()
            ->select(
                'products.*',
                'ppc.category_id'
            )
            ->join('product_categories as ppc', 'ppc.product_id', '=', 'products.id')
            ->whereIn('ppc.category_id', $categoryIds)
            ->where('products.tenant_id', $tenantId)
            ->whereNull('products.deleted_at')
            ->where('products.in_stop_list', false)
            ->orderBy('products.order_position') // Лучше сортировать по порядку, а не по ID
            ->get()
            ->groupBy('category_id');

        // 4) 🆕 Собираем структуру с лимитом в 4 товара и общим количеством
        $result = [];

        foreach ($categories as $category) {
            $categoryProducts = ($products[$category->id] ?? collect())->values();

            $result[] = [
                'id' => $category->id,
                'name' => $category->name,
                'order_position' => $category->order_position,
                'is_active' => $category->is_active,
                'products' => $categoryProducts->take(4)->values()->toArray(), // 🎯 БЕРЕМ ТОЛЬКО 4 ПЕРВЫХ ТОВАРА
                'products_count' => $productsCount[$category->id] ?? 0,         // 🎯 ДОБАВЛЯЕМ ОБЩЕЕ КОЛИЧЕСТВО
            ];
        }

        return $result;
    }

}
