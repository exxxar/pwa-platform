<?php

namespace App\Services\Tenants;

use Illuminate\Support\Collection;

class PricingService
{
    /**
     * Получить все тарифы
     */
    public static function getPlans(): Collection
    {
        $plans = config('pricing.plans', []);

        return collect($plans)->map(function ($plan) {
            return self::formatPlan($plan);
        });
    }

    /**
     * Получить активные тарифы (отсортированные)
     */
    public static function getActivePlans(): Collection
    {
        return self::getPlans()
            ->filter(fn($plan) => $plan['is_active'] ?? true)
            ->sortBy('sort_order');
    }

    /**
     * Получить тариф по slug
     */
    public static function getPlanBySlug(string $slug): ?array
    {
        return self::getPlans()
            ->firstWhere('slug', $slug);
    }

    /**
     * Получить выделенный тариф
     */
    public static function getFeaturedPlan(): ?array
    {
        return self::getPlans()
            ->firstWhere('is_featured', true);
    }

    /**
     * Форматирование тарифа
     */
    private static function formatPlan(array $plan): array
    {
        $plan['is_active'] = $plan['is_active'] ?? true;
        $plan['sort_order'] = $plan['sort_order'] ?? 0;

        // Форматированная цена
        $plan['formatted_price'] = number_format($plan['price'] ?? 0, 0, '.', ' ');

        // Форматированная старая цена
        $plan['formatted_old_price'] = $plan['old_price']
            ? number_format($plan['old_price'], 0, '.', ' ')
            : null;

        // Период прописью
        $plan['period_label'] = match($plan['period'] ?? 'month') {
            'month' => '₽/мес',
            'year' => '₽/год',
            'once' => '₽',
            default => '₽',
        };

        // Скидка в процентах
        if ($plan['old_price'] && $plan['old_price'] > $plan['price']) {
            $plan['discount_percent'] = (int) round(
                (($plan['old_price'] - $plan['price']) / $plan['old_price']) * 100
            );
        } else {
            $plan['discount_percent'] = null;
        }

        return $plan;
    }

    /**
     * Получить настройки отображения
     */
    public static function getDisplaySettings(): array
    {
        return config('pricing.display', []);
    }
}
