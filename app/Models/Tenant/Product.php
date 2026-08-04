<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'price',
        'old_price',
        'sku',
        'description',
        'images',
        'dimensions',
        'delivery_terms',
        'external_source',
        'config',
        'external_id',
        'is_active',
        'not_for_delivery',
        'in_stop_list',
        'is_weight_product',
        'order_position',
    ];

    protected $casts = [
        'images' => 'array',
        'config' => 'array',
        'dimensions' => 'array',
        'is_active' => 'boolean',
        'in_stop_list' => 'boolean',
        'not_for_delivery' => 'boolean',
        'is_weight_product' => 'boolean',
    ];

    protected $with = ["categories", "tenant", "reviews"];

    protected $appends = ["rating","tenant_name" ];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'product_categories',
            'product_id',
            'category_id'
        );
    }

    public function collectionCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            CollectionCategory::class,
            'collection_category_product',
            'product_id',
            'collection_category_id'
        )
            ->using(CollectionCategoryProduct::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    protected function tenantName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tenant?->name ?? 'Неизвестное заведение'
        );
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    protected function rating(): Attribute
    {
        return Attribute::make(
            get: fn() => 0
        );
    }

    /**
     * Отзывы о товаре
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Только одобренные отзывы
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->approved();
    }

    /**
     * Средний рейтинг товара
     */
    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->approved()->avg('rating') ?? 0, 1);
    }

    /**
     * Количество отзывов
     */
    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->approved()->count();
    }

}
