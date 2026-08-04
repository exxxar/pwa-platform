<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CollectionCategoryProduct extends Pivot
{
    protected $table = 'collection_category_product';

    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'collection_category_id',
        'product_id',
        'sort_order',
    ];

    protected $casts = [
        'id' => 'integer',
        'collection_category_id' => 'integer',
        'product_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function collectionCategory(): BelongsTo
    {
        return $this->belongsTo(CollectionCategory::class, 'collection_category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
