<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStat extends Model
{
    protected $fillable = [
        'tenant_user_id',
        'stat_key',
        'stat_value',
    ];

    protected $casts = [
        'stat_value' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'tenant_user_id');
    }

    // ==========================================
    // 🆕 СТАТИЧЕСКИЕ МЕТОДЫ С ДРУГИМИ ИМЕНАМИ
    // ==========================================

    /**
     * Увеличить значение статистики
     * (переименовано из increment, чтобы не конфликтовать с Eloquent)
     */
    public static function incrementStat(int $userId, string $key, int $amount = 1): void
    {
        $stat = self::firstOrCreate(
            ['tenant_user_id' => $userId, 'stat_key' => $key],
            ['stat_value' => 0]
        );

        $stat->increment('stat_value', $amount);

        // Проверяем достижения
        app(\App\Services\Tenants\AchievementService::class)->checkAchievements($userId, $key);
    }

    /**
     * Уменьшить значение статистики
     */
    public static function decrementStat(int $userId, string $key, int $amount = 1): void
    {
        $stat = self::firstOrCreate(
            ['tenant_user_id' => $userId, 'stat_key' => $key],
            ['stat_value' => 0]
        );

        $stat->decrement('stat_value', $amount);

        // Проверяем достижения
        app(\App\Services\Tenants\AchievementService::class)->checkAchievements($userId, $key);
    }

    /**
     * Установить значение статистики
     */
    public static function setStat(int $userId, string $key, int $value): void
    {
        $stat = self::firstOrCreate(
            ['tenant_user_id' => $userId, 'stat_key' => $key],
            ['stat_value' => 0]
        );

        $stat->update(['stat_value' => $value]);

        // Проверяем достижения
        app(\App\Services\Tenants\AchievementService::class)->checkAchievements($userId, $key);
    }

    /**
     * Получить значение статистики
     */
    public static function getStat(int $userId, string $key): int
    {
        return self::where('tenant_user_id', $userId)
            ->where('stat_key', $key)
            ->value('stat_value') ?? 0;
    }

    /**
     * Получить все статистики пользователя
     */
    public static function getAllStats(int $userId): array
    {
        return self::where('tenant_user_id', $userId)
            ->pluck('stat_value', 'stat_key')
            ->toArray();
    }
}
