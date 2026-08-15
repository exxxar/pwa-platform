<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CoffeeController extends Controller
{
    /**
     * Инициализация кофейной карты
     */
    public function init(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $meta = $user->meta ?? [];

        if (!isset($meta['coffee'])) {
            $meta['coffee'] = [
                'count' => 0,
                'last_marked_at' => null,
                'total_exchanged' => 0,
            ];
            $user->meta = $meta;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'coffee' => $meta['coffee'],
        ]);
    }

    /**
     * Получить прогресс пользователя
     */
    public function getProgress(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $meta = $user->meta ?? [];
        $coffee = $meta['coffee'] ?? ['count' => 0, 'last_marked_at' => null, 'total_exchanged' => 0];

        return response()->json([
            'success' => true,
            'coffee' => $coffee,
        ]);
    }

    /**
     * Отметить чашку (для админа)
     */
    public function mark(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'cup_index' => 'required|integer|min:0',
            'timestamp' => 'required|integer',
        ]);

        // Проверка времени (QR действителен 5 минут)
        $now = now()->timestamp;
        if (abs($now - $request->timestamp) > 300) {
            return response()->json(['error' => 'QR-код истёк'], 400);
        }

        $user = TenantUser::find($request->user_id);
        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        $meta = $user->meta ?? [];
        $coffee = $meta['coffee'] ?? ['count' => 0, 'last_marked_at' => null, 'total_exchanged' => 0];

        $maxCups = $user->tenant->settings['coffee']['max'] ?? 6;

        if ($coffee['count'] >= $maxCups) {
            return response()->json(['error' => 'Все чашки уже отмечены'], 400);
        }

        if ($request->cup_index !== $coffee['count']) {
            return response()->json(['error' => 'Неверный индекс чашки'], 400);
        }

        $coffee['count']++;
        $coffee['last_marked_at'] = now()->toIso8601String();

        $meta['coffee'] = $coffee;
        $user->meta = $meta;
        $user->save();

        Log::info('Coffee marked', [
            'user_id' => $user->id,
            'cup_index' => $request->cup_index,
            'new_count' => $coffee['count'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Чашка отмечена',
            'coffee' => $coffee,
        ]);
    }

    /**
     * Обменять на бесплатный кофе (для админа)
     */
    public function exchange(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'timestamp' => 'required|integer',
        ]);

        // Проверка времени (QR действителен 5 минут)
        $now = now()->timestamp;
        if (abs($now - $request->timestamp) > 300) {
            return response()->json(['error' => 'QR-код истёк'], 400);
        }

        $user = TenantUser::find($request->user_id);
        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        $meta = $user->meta ?? [];
        $coffee = $meta['coffee'] ?? ['count' => 0, 'last_marked_at' => null, 'total_exchanged' => 0];

        $maxCups = $user->tenant->settings['coffee']['max'] ?? 6;

        if ($coffee['count'] < $maxCups) {
            return response()->json(['error' => 'Недостаточно чашек для обмена'], 400);
        }

        // Сбрасываем счётчик и увеличиваем total_exchanged
        $coffee['count'] = 0;
        $coffee['total_exchanged'] = ($coffee['total_exchanged'] ?? 0) + 1;
        $coffee['last_marked_at'] = now()->toIso8601String();

        $meta['coffee'] = $coffee;
        $user->meta = $meta;
        $user->save();

        Log::info('Coffee exchanged', [
            'user_id' => $user->id,
            'total_exchanged' => $coffee['total_exchanged'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Бесплатный кофе выдан',
            'coffee' => $coffee,
        ]);
    }

    /**
     * Сбросить прогресс (для админа)
     */
    public function reset(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $user = TenantUser::find($request->user_id);
        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        $meta = $user->meta ?? [];
        $meta['coffee'] = [
            'count' => 0,
            'last_marked_at' => null,
            'total_exchanged' => $meta['coffee']['total_exchanged'] ?? 0,
        ];
        $user->meta = $meta;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Прогресс сброшен',
            'coffee' => $meta['coffee'],
        ]);
    }
}
