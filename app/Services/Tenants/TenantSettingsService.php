<?php

namespace App\Services\Tenants;

class TenantSettingsService
{
    /**
     * Получить все настройки по умолчанию
     */
    public static function getDefaultSettings(): array
    {
        return [
            // Основные
            ...config('tenant.general', []),
            ...config('tenant.telegram', []),

            // Доставка
            ...config('tenant.delivery', []),

            // Платежи
            ...config('tenant.payment', []),

            // Реферальная программа
            'referral' => config('tenant.referral', []),

            // PWA
            'pwa' => config('tenant.pwa', []),
            'landing' => config('tenant.landing', []),

            // Пункты меню
            'menu_items' => config('tenant.menu', []),
            'main_menu_items' => config('tenant.main_menu', []),
            // Калькуляторы
            'food_calculators' => config('tenant.calculators', []),

            // Бонус-игры
            'bonus_games' => config('tenant.games', []),

            // Фичи
            ...config('tenant.features', []),

            // Kanban
            'kanban' => config('tenant.kanban', []),

            // Кофе
            'coffee' => config('tenant.coffee', []),
            'seo' => config('tenant.seo', []),

            // Кэшбэк
            'max_cashback_use_percent' => config('tenant.cashback.max_percent', 15),
            'level_1' => config('tenant.cashback.level_1', 0),
            'level_2' => config('tenant.cashback.level_2', 0),
            'level_3' => config('tenant.cashback.level_3', 0),
            'cashback_config' => config('tenant.cashback.config', []),
            'warnings' => config('tenant.cashback.warnings', []),

            // Разное
            ...config('tenant.misc', []),
        ];
    }



    /**
     * Получить конкретную секцию настроек
     */
    public static function getSection(string $section): array
    {
        return match ($section) {
            'general' => config('tenant.general', []),
            'delivery' => config('tenant.delivery', []),
            'payment' => config('tenant.payment', []),
            'referral' => config('tenant.referral', []),
            'pwa' => config('tenant.pwa', []),
            'menu' => config('tenant.menu', []),
            'calculators' => config('tenant.calculators', []),
            'games' => config('tenant.games', []),
            'features' => config('tenant.features', []),
            'kanban' => config('tenant.kanban', []),
            'coffee' => config('tenant.coffee', []),
            'cashback' => config('tenant.cashback', []),
            'misc' => config('tenant.misc', []),
            'landing' => config('tenant.landing', []),
            'telegram' => config('tenant.telegram', []),
            default => [],
        };
    }
}
