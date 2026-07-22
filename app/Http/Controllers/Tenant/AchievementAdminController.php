<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Achievement;
use App\Models\Tenant\UserAchievement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AchievementAdminController extends Controller
{
    /**
     * Получение данных для админ-панели достижений
     */
    public function index()
    {
        $tenantId = app('tenant')->id;

        // 1. Статистика
        $total = Achievement::where('tenant_id', $tenantId)->count();
        $active = Achievement::where('tenant_id', $tenantId)->where('is_active', true)->count();

        $unlockedCount = UserAchievement::whereHas('achievement', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->count();

        $rewardsGiven = UserAchievement::whereHas('achievement', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->where('reward_claimed', 1)->count();

        // 2. Список достижений
        $achievements = Achievement::where('tenant_id', $tenantId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'stats' => [
                'total' => $total,
                'active' => $active,
                'total_unlocked' => $unlockedCount,
                'total_rewards_given' => $rewardsGiven,
            ],
            'achievements' => $achievements,
            'condition_types' => Achievement::CONDITION_TYPES,
            'reward_types' => Achievement::REWARD_TYPES,
        ]);
    }

    /**
     * Создание нового достижения
     */
    public function store(Request $request)
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'condition_type' => 'required|string|in:' . implode(',', array_keys(Achievement::CONDITION_TYPES)),
            'condition_value' => 'required|integer|min:1',
            'reward_type' => 'required|string|in:' . implode(',', array_keys(Achievement::REWARD_TYPES)),
            'reward_value' => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $achievement = Achievement::create(array_merge($validated, [
            'tenant_id' => $tenantId,
        ]));

        return response()->json(['success' => true, 'data' => $achievement]);
    }

    /**
     * Обновление достижения
     */
    public function update(Request $request, Achievement $achievement)
    {
        $this->authorizeTenant($achievement);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'condition_type' => 'required|string|in:' . implode(',', array_keys(Achievement::CONDITION_TYPES)),
            'condition_value' => 'required|integer|min:1',
            'reward_type' => 'required|string|in:' . implode(',', array_keys(Achievement::REWARD_TYPES)),
            'reward_value' => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $achievement->update($validated);

        return response()->json(['success' => true, 'data' => $achievement]);
    }

    /**
     * Быстрое переключение статуса активности
     */
    public function toggle(Request $request, Achievement $achievement)
    {
        $this->authorizeTenant($achievement);

        $achievement->update(['is_active' => $request->boolean('is_active')]);

        return response()->json(['success' => true]);
    }

    /**
     * Удаление достижения
     */
    public function destroy(Achievement $achievement)
    {
        $this->authorizeTenant($achievement);

        // Опционально: можно добавить каскадное удаление или архивацию
        $achievement->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Проверка принадлежности достижения текущему тенанту
     */
    private function authorizeTenant(Achievement $achievement)
    {
        if ($achievement->tenant_id !== app('tenant')->id) {
            abort(403, 'Доступ запрещен');
        }
    }
}
