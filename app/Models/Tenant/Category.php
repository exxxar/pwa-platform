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

    public static  function getCategoriesWithProducts(int $tenantId)
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

        // 2) Товары (минимум полей)
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
            ->orderBy('products.id')
            ->get()
            ->groupBy('category_id');

        // 3) Собираем структуру
        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'name' => $category->name,
                'order_position' => $category->order_position,
                'is_active' => $category->is_active,
                'products' => ($products[$category->id] ?? collect())->values(),
            ];
        }

        return $result;
    }

}
