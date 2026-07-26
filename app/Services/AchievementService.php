<?php

namespace App\Services;

use App\Models\Tenant\Achievement;
use App\Models\Tenant\UserAchievement;
use App\Models\Tenant\UserStat;
use Illuminate\Support\Facades\Log;

class AchievementService
{
    /**
     * Проверка достижений для пользователя
     */
    public function checkAchievements(int $userId, string $statKey): array
    {
        $tenant = app('tenant');
        $currentValue = UserStat::getStat($userId, $statKey); // 🆕 getStat вместо get

        // Находим все активные достижения для этой статистики
        $achievements = Achievement::where('tenant_id', $tenant->id)
            ->where('condition_type', $statKey)
            ->where('is_active', true)
            ->where('condition_value', '<=', $currentValue)
            ->get();

        $unlocked = [];

        foreach ($achievements as $achievement) {
            // Проверяем, не получено ли уже
            $exists = UserAchievement::where('tenant_user_id', $userId)
                ->where('achievement_id', $achievement->id)
                ->exists();

            if (!$exists) {
                // Разблокируем достижение
                $userAchievement = UserAchievement::create([
                    'tenant_user_id' => $userId,
                    'achievement_id' => $achievement->id,
                    'unlocked_at' => now(),
                    'reward_claimed' => 0,
                ]);

                $unlocked[] = $userAchievement;

                Log::info("🏆 Достижение разблокировано!", [
                    'user_id' => $userId,
                    'achievement' => $achievement->title,
                    'reward' => $achievement->reward_type . ': ' . $achievement->reward_value,
                ]);
            }
        }

        return $unlocked;
    }

    /**
     * Выдача награды за достижение
     */
    public function giveReward(UserAchievement $userAchievement): array
    {
        // Защита от повторного получения
        if ($userAchievement->reward_claimed) {
            return [
                'success' => false,
                'message' => 'Награда уже получена',
            ];
        }

        $achievement = $userAchievement->achievement;
        $user = $userAchievement->user;

        switch ($achievement->reward_type) {
            case 'cashback':
                $user->increment('cashback', $achievement->reward_value);
                Log::info("💰 Начислен кэшбэк за достижение", [
                    'user_id' => $user->id,
                    'amount' => $achievement->reward_value,
                ]);
                break;

            case 'points':
                // $user->increment('points', $achievement->reward_value);
                break;

            case 'discount':
                // PromoCode::create([...]);
                break;
        }

        // Помечаем, что награда забрана
        $userAchievement->update([
            'reward_claimed' => 1,
            // 'reward_claimed_at' => now(), // Раскомментируйте, если добавите это поле в БД
        ]);

        return [
            'success' => true,
            'reward' => [
                'type' => $achievement->reward_type,
                'value' => $achievement->reward_value,
            ],
        ];
    }

    /**
     * Получить все достижения пользователя
     */
    public function getUserAchievements(int $userId): array
    {
        return UserAchievement::where('tenant_user_id', $userId)
            ->with('achievement')
            ->orderBy('unlocked_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Получить доступные достижения
     */
    public function getAvailableAchievements(int $userId): array
    {
        $tenant = app('tenant');
        $unlockedIds = UserAchievement::where('tenant_user_id', $userId)
            ->pluck('achievement_id')
            ->toArray();

        return Achievement::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotIn('id', $unlockedIds)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Получить прогресс пользователя
     */
    public function getUserProgress(int $userId): array
    {
        $tenant = app('tenant');
        $allAchievements = Achievement::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        $progress = [];

        foreach ($allAchievements as $achievement) {
            $currentValue = UserStat::getStat($userId, $achievement->condition_type); // 🆕 getStat
            $isUnlocked = UserAchievement::where('tenant_user_id', $userId)
                ->where('achievement_id', $achievement->id)
                ->exists();

            $progress[] = [
                'achievement' => $achievement,
                'current_value' => $currentValue,
                'target_value' => $achievement->condition_value,
                'progress_percent' => min(100, ($currentValue / max(1, $achievement->condition_value)) * 100),
                'is_unlocked' => $isUnlocked,
            ];
        }

        return $progress;
    }

    /**
     * Инициализация статистики пользователя
     */
    public function initializeUserStats(int $userId): void
    {
        $defaultStats = [
            'orders_count' => 0,
            'orders_sum' => 0,
            'reviews_count' => 0,
            'cashback_earned' => 0,
            'cashback_spent' => 0,
            'friends_invited' => 0,
            'games_played' => 0,
            'products_viewed' => 0,
            'products_in_cart' => 0,
        ];

        foreach ($defaultStats as $key => $value) {
            UserStat::firstOrCreate(
                ['tenant_user_id' => $userId, 'stat_key' => $key],
                ['stat_value' => $value]
            );
        }
    }
}
