<?php

namespace App\Services\Admin\Global;

use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    protected string $cacheKey = 'system_settings';

    /**
     * Получить все настройки
     */
    public function getAllSettings(): array
    {
        return Cache::remember($this->cacheKey, 3600, function () {
            // Здесь можно загружать из базы данных или config файлов
            return [
                'billing' => [
                    'default_plan' => 'basic',
                    'trial_days' => 14,
                ],
                'notifications' => [
                    'email_enabled' => true,
                    'telegram_enabled' => true,
                ],
                'limits' => [
                    'max_users_per_tenant' => 1000,
                    'max_products_per_tenant' => 5000,
                ],
            ];
        });
    }

    /**
     * Получить конкретную секцию настроек
     */
    public function getSection(string $section): array
    {
        $settings = $this->getAllSettings();
        return $settings[$section] ?? [];
    }

    /**
     * Обновить настройки
     */
    public function updateSettings(array $data): void
    {
        // Здесь можно сохранять в базу данных
        // Например: SystemSetting::updateOrCreate(...)

        // Очищаем кэш
        $this->clearCache();
    }

    /**
     * Очистить кэш настроек
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}
