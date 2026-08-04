<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class Collection extends Model
{
    public const TYPE_MANUAL = 'manual';

    public const PRICING_TYPE_SUM = 'sum';
    public const PRICING_TYPE_FIXED = 'fixed';

    protected $table = 'collections';

    protected $fillable = [
        'tenant_id',
        'external_id',
        'name',
        'description',
        'short_description',
        'type',
        'pricing_type',
        'fixed_price',
        'discount',
        'image',
        'is_active',
        'in_stop_list',
        'order_position',
        'config',
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
        'in_stop_list' => 'boolean',
        'fixed_price' => 'decimal:2',
        'discount' => 'decimal:2',       // ← добавлено
        'order_position' => 'integer',   // ← добавлено
        'config' => 'array',
    ];

    protected $attributes = [
        'type' => 'manual',
        'pricing_type' => 'sum',
        'in_stop_list' => false,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function collectionCategories(): HasMany
    {
        return $this->hasMany(CollectionCategory::class, 'collection_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'collection_categories',
            'collection_id',
            'category_id'
        )
            ->withPivot([
                'category_name',
                'selection_rule',
                'sort_order',
            ])
            ->withTimestamps()
            ->orderBy('collection_categories.sort_order')
            ->orderBy('collection_categories.id');
    }

    public function products(): Builder
    {
        return $this->productsQuery();
    }

    public function productsQuery(): Builder
    {
        if (! $this->exists) {
            return Product::query()->whereRaw('1 = 0');
        }

        $productTable = (new Product())->getTable();

        return Product::query()
            ->select($productTable . '.*')
            ->join(
                'collection_category_product',
                $productTable . '.id',
                '=',
                'collection_category_product.product_id'
            )
            ->join(
                'collection_categories',
                'collection_categories.id',
                '=',
                'collection_category_product.collection_category_id'
            )
            ->where('collection_categories.collection_id', $this->getKey())
            ->groupBy($productTable . '.id')
            ->orderByRaw('MIN(collection_category_product.sort_order) ASC')
            ->orderBy($productTable . '.id');
    }

    public function getProductsAttribute(): SupportCollection
    {
        return $this->productsQuery()->get();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('in_stop_list', false);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('order_position') // ← исправлено
            ->orderBy('id');
    }
}
