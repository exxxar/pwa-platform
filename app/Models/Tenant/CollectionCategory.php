<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionCategory extends Model
{
    public const RULE_ONE = 'one';
    public const RULE_ALL = 'all';

    protected $table = 'collection_categories';

    protected $fillable = [
        'collection_id',
        'category_id',
        'external_id',
        'category_name',
        'selection_rule',
        'sort_order',
    ];

    protected $casts = [
        'id' => 'integer',
        'external_id' => 'integer',
        'collection_id' => 'integer',
        'category_id' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'selection_rule' => self::RULE_ONE,
        'sort_order' => 0,
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function collectionCategoryProducts(): HasMany
    {
        return $this->hasMany(CollectionCategoryProduct::class, 'collection_category_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'collection_category_product',
            'collection_category_id',
            'product_id'
        )
            ->using(CollectionCategoryProduct::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('collection_category_product.sort_order');
    }
}
