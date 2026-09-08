<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingMaterial extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'download_count',
        'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketingCategory::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }
}
