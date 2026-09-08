<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngredientGroup extends Model
{
    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'selection_rule',   // ✅ НОВОЕ: single, multiple, all, optional
        'min_select',       // ✅ НОВОЕ
        'max_select',       // ✅ НОВОЕ
        'is_required',      // ✅ НОВОЕ
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'min_select' => 'integer',
        'max_select' => 'integer',
        'is_required' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'group_id')->orderBy('sort_order');
    }
}
