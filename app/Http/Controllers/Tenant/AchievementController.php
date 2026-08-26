<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CashBackHistory;
use App\Models\Tenant\UserAchievement;
use App\Models\Tenant\UserStat;
use App\Services\Tenants\AchievementService;
use App\Services\Tenants\CashBackService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AchievementController extends Controller
{
    protected AchievementService $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    /**
     * Получить все достижения пользователя
     */
    public function index()
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $unlocked = $this->achievementService->getUserAchievements($user->id);
        $available = $this->achievementService->getAvailableAchievements($user->id);
        $progress = $this->achievementService->getUserProgress($user->id);

        return response()->json([
            'unlocked' => $unlocked,
            'available' => $available,
            'progress' => $progress,
            'stats' => $this->getUserStats($user->id),
            'cashback' => $this->getUserCashbackStats($user),
        ]);
    }

    /**
     * Получить статистику пользователя
     */
    public function stats()
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'stats' => $this->getUserStats($user->id),
            'cashback' => $this->getUserCashbackStats($user),
        ]);
    }

    /**
     * Забрать награду за достижение
     * 🆕 Поддерживает разные типы наград: cashback, points, discount и др.
     */
    public function claimReward(int $achievementId)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userAchievement = UserAchievement::where('tenant_user_id', $user->id)
            ->where('achievement_id', $achievementId)
            ->where('reward_claimed', 0)
            ->with('achievement')
            ->first();

        if (!$userAchievement) {
            return response()->json([
                'success' => false,
                'message' => 'Достижение не найдено или награда уже получена',
            ], 404);
        }

        $achievement = $userAchievement->achievement;

        if (!$achievement) {
            return response()->json([
                'success' => false,
                'message' => 'Достижение не найдено',
            ], 404);
        }

        // 🎯 Применяем награду в зависимости от типа
        try {
            $rewardResult = $this->applyReward($user, $achievement);

            // Отмечаем награду как полученную
            $userAchievement->claimReward();

            // Обновляем статистику пользователя
            $this->incrementUserStat($user->id, 'rewards_claimed', 1);
            $this->incrementUserStat(
                $user->id,
                'total_rewards_value',
                (float) $achievement->reward_value
            );

            Log::info('[Achievement] Reward claimed', [
                'user_id' => $user->id,
                'achievement_id' => $achievementId,
                'reward_type' => $achievement->reward_type,
                'reward_value' => $achievement->reward_value,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Награда получена!',
                'reward' => [
                    'type' => $achievement->reward_type,
                    'value' => $achievement->reward_value,
                    'applied' => $rewardResult,
                ],
                'cashback_balance' => CashBackService::call()->getBalance($user),
                'stats' => $this->getUserStats($user->id),
            ]);

        } catch (\Throwable $e) {
            Log::error('[Achievement] Failed to claim reward', [
                'user_id' => $user->id,
                'achievement_id' => $achievementId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить награду: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🆕 Применить награду в зависимости от типа
     *
     * @return array Результат применения награды
     */
    protected function applyReward($user, $achievement): array
    {
        $rewardType = $achievement->reward_type;
        $rewardValue = (float) $achievement->reward_value;
        $description = "Награда за достижение: {$achievement->title}";

        switch ($rewardType) {
            case 'cashback':
            case 'cash_back':
            case 'bonus':
                // 💰 Начисляем кэшбэк через сервис
                CashBackService::call()->addCashBack(
                    amount: $rewardValue,
                    description: $description,
                    user: $user,
                    orderId: null,
                    percent: null,
                    withLevels: false // 🎯 Не применяем реферальные уровни к наградам
                );

                return [
                    'type' => 'cashback',
                    'amount' => $rewardValue,
                    'new_balance' => CashBackService::call()->getBalance($user),
                ];

            case 'discount':
                // 🎁 Применяем скидку (сохраняем в мета пользователя)
                $this->applyDiscountReward($user, $rewardValue, $achievement);

                return [
                    'type' => 'discount',
                    'value' => $rewardValue,
                ];

            case 'points':
            case 'loyalty_points':
                // 🏆 Баллы лояльности (если есть отдельная система)
                $this->applyPointsReward($user, $rewardValue, $description);

                return [
                    'type' => 'points',
                    'amount' => $rewardValue,
                ];

            case 'free_delivery':
                // 🚚 Бесплатная доставка
                $this->applyFreeDeliveryReward($user, $achievement);

                return [
                    'type' => 'free_delivery',
                    'value' => $rewardValue,
                ];

            default:
                Log::warning('[Achievement] Unknown reward type', [
                    'type' => $rewardType,
                    'achievement_id' => $achievement->id,
                ]);

                return [
                    'type' => $rewardType,
                    'value' => $rewardValue,
                    'note' => 'Тип награды не поддерживается автоматически',
                ];
        }
    }

    /**
     * 🆕 Применить скидку как награду
     */
    protected function applyDiscountReward($user, float $discountPercent, $achievement): void
    {
        $currentDiscount = (float) ($user->permanent_personal_discount ?? 0);
        $newDiscount = max($currentDiscount, $discountPercent);

        $user->update([
            'permanent_personal_discount' => $newDiscount,
        ]);
    }

    /**
     * 🆕 Применить баллы лояльности (заглушка — расширьте под свою систему)
     */
    protected function applyPointsReward($user, float $points, string $description): void
    {
        // Если у вас есть отдельная система баллов — реализуйте здесь
        // Пока что начисляем как кэшбэк (можно разделить логику)
        CashBackService::call()->addCashBack(
            amount: $points,
            description: $description,
            user: $user,
            orderId: null,
            percent: null,
            withLevels: false
        );
    }

    /**
     * 🆕 Применить бесплатную доставку
     */
    protected function applyFreeDeliveryReward($user, $achievement): void
    {
        // Сохраняем в мета пользователя
        $meta = $user->meta ?? [];
        $meta['free_delivery_coupons'] = ($meta['free_delivery_coupons'] ?? 0) + 1;
        $meta['last_free_delivery_reward'] = now()->toISOString();

        $user->update(['meta' => $meta]);
    }

    /**
     * Получить статистику пользователя
     */
    protected function getUserStats(int $userId): array
    {
        $stats = UserStat::where('tenant_user_id', $userId)
            ->pluck('stat_value', 'stat_key')
            ->toArray();

        // 🆕 Добавляем стандартные значения по умолчанию
        return array_merge([
            'rewards_claimed' => 0,
            'total_rewards_value' => 0,
            'achievements_unlocked' => 0,
        ], $stats);
    }

    /**
     * 🆕 Получить статистику по кэшбэку для пользователя
     */
    protected function getUserCashbackStats($user): array
    {
        $tenant = app('tenant');

        // Текущий баланс
        $balance = CashBackService::call()->getBalance($user);

        // Общая сумма начислений
        $totalEarned = CashBackHistory::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->where('type', 'credit')
            ->sum('amount');

        // Общая сумма списаний
        $totalSpent = CashBackHistory::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->where('type', 'debit')
            ->sum('amount');

        // Количество операций
        $operationsCount = CashBackHistory::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->count();

        // Сумма наград за достижения
        $achievementRewardsTotal = CashBackHistory::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->where('type', 'credit')
            ->where('description', 'like', 'Награда за достижение%')
            ->sum('amount');

        // Количество наград за достижения
        $achievementRewardsCount = CashBackHistory::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->where('type', 'credit')
            ->where('description', 'like', 'Награда за достижение%')
            ->count();

        return [
            'balance' => (float) $balance,
            'total_earned' => (float) $totalEarned,
            'total_spent' => (float) $totalSpent,
            'operations_count' => (int) $operationsCount,
            'achievement_rewards_total' => (float) $achievementRewardsTotal,
            'achievement_rewards_count' => (int) $achievementRewardsCount,
        ];
    }

    /**
     * 🆕 Инкремент статистики пользователя
     */
    protected function incrementUserStat(int $userId, string $key, float $increment = 1): void
    {
        try {
            $tenant = app('tenant');

            $stat = UserStat::firstOrCreate(
                [
                    'tenant_user_id' => $userId,
                    'stat_key' => $key,
                ],
                [
                    'tenant_id' => $tenant->id,
                    'stat_value' => 0,
                ]
            );

            $stat->increment('stat_value', $increment);
        } catch (\Throwable $e) {
            Log::warning('[Achievement] Failed to increment stat', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
