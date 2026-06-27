<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchievementController extends Controller
{
    protected $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    /**
     * Получить все достижения пользователя
     */
    public function index()
    {
        $userId = Auth::guard('tenant')->id();

        $unlocked = $this->achievementService->getUserAchievements($userId);
        $available = $this->achievementService->getAvailableAchievements($userId);
        $progress = $this->achievementService->getUserProgress($userId);

        return response()->json([
            'unlocked' => $unlocked,
            'available' => $available,
            'progress' => $progress,
            'stats' => $this->getUserStats($userId),
        ]);
    }

    /**
     * Получить статистику пользователя
     */
    public function stats()
    {
        $userId = Auth::guard('tenant')->id();

        return response()->json([
            'stats' => $this->getUserStats($userId),
        ]);
    }

    /**
     * Забрать награду за достижение
     */
    public function claimReward(int $achievementId)
    {
        $userId = Auth::guard('tenant')->id();

        $userAchievement = \App\Models\Tenant\UserAchievement::where('tenant_user_id', $userId)
            ->where('achievement_id', $achievementId)
            ->where('reward_claimed', 0)
            ->first();

        if (!$userAchievement) {
            return response()->json([
                'success' => false,
                'message' => 'Достижение не найдено или награда уже получена',
            ], 404);
        }

        $userAchievement->claimReward();

        return response()->json([
            'success' => true,
            'message' => 'Награда получена!',
            'reward' => [
                'type' => $userAchievement->achievement->reward_type,
                'value' => $userAchievement->achievement->reward_value,
            ],
        ]);
    }

    /**
     * Получить статистику пользователя
     */
    protected function getUserStats(int $userId): array
    {
        $stats = \App\Models\Tenant\UserStat::where('tenant_user_id', $userId)
            ->pluck('stat_value', 'stat_key')
            ->toArray();

        return $stats;
    }
}
