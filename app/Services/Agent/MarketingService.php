<?php

namespace App\Services\Agent;

use App\Models\MarketingCategory;
use App\Models\MarketingMaterial;

class MarketingService
{
    /**
     * Получить все активные категории с материалами
     */
    public function getCategoriesWithMaterials(): \Illuminate\Database\Eloquent\Collection
    {
        return MarketingCategory::with(['materials' => fn ($q) => $q->where('is_active', true)])
            ->active()
            ->get();
    }

    /**
     * Увеличить счётчик скачиваний
     */
    public function trackDownload(MarketingMaterial $material): void
    {
        $material->incrementDownloadCount();
    }
}
