<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WheelController extends Controller
{

    public function getData(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['wheel'] ?? [];

        $intervalDays = $settings['interval'] ?? 1;


        $maxAttempts = 1;

        $meta = $user->meta ?? [];
        $attempts = $meta['wheel_attempts'] ?? [];


        $cutoffDate = Carbon::now()->subDays($intervalDays);

        Log::info('[Wheel getData] Проверка попыток', [
            'user_id' => $user->id,
            'interval_days' => $intervalDays,
            'cutoff' => $cutoffDate,
            'total_attempts_in_meta' => count($attempts)
        ]);

        $validAttempts = collect($attempts)->filter(function ($attempt) use ($cutoffDate) {
            return Carbon::parse($attempt['played_at'])->gte($cutoffDate);
        })->values()->toArray();

        Log::info('[Wheel getData] Результат', [
            'valid_attempts' => count($validAttempts),
            'max_attempts' => $maxAttempts
        ]);

        return response()->json([
            'rules' => $settings['rules'] ?? null,
            'before_script' => $settings['before_script'] ?? null,
            'after_script' => $settings['after_script'] ?? null,
            'items' => $settings['items'] ?? [],
            'action' => [
                'current_attempts' => count($validAttempts),
                'max_attempts' => $maxAttempts,
                'data' => $meta['wheel_wins'] ?? []
            ]
        ]);
    }

    public function recordAttempt(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            Log::warning('[Wheel] Пользователь не авторизован');
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['wheel'] ?? [];
        $intervalDays = $settings['interval'] ?? 1;

        $meta = $user->meta ?? [];
        $attempts = $meta['wheel_attempts'] ?? [];
        $cutoffDate = Carbon::now()->subDays($intervalDays);

        Log::info('[Wheel] Попыток в meta:', ['total' => count($attempts), 'cutoff' => $cutoffDate]);

        // Фильтруем актуальные попытки
        $validAttempts = collect($attempts)->filter(function ($attempt) use ($cutoffDate) {
            return Carbon::parse($attempt['played_at'])->gte($cutoffDate);
        })->values()->toArray();

        Log::info('[Wheel] Актуальных попыток:', ['count' => count($validAttempts)]);

        if (count($validAttempts) >= 1) {
            Log::info('[Wheel] Отказано: лимит исчерпан');
            return response()->json([
                'success' => false,
                'message' => 'Попытки на этот период закончились. Попробуйте позже!'
            ], 403);
        }

        // Записываем новую попытку
        $attempts[] = [
            'played_at' => Carbon::now()->toIso8601String(),
        ];

        if (count($attempts) > 100) {
            $attempts = array_slice($attempts, -100);
        }

        $meta['wheel_attempts'] = $attempts;
        $user->meta = $meta;
        $user->save();

        Log::info('[Wheel] Попытка успешно записана', ['total_attempts' => count($attempts)]);

        return response()->json([
            'success' => true,
            'message' => 'Попытка зафиксирована',
            'current_attempts' => count($validAttempts) + 1
        ]);
    }

    /**
     * Получение истории выигрышей пользователя
     */
    public function getHistory(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Пользователь не авторизован'], 401);
        }

        // Берем историю из meta, если её нет — возвращаем пустой массив
        $wins = $user->meta['wheel_wins'] ?? [];

        return response()->json([
            'success' => true,
            'data' => $wins
        ]);
    }
    /**
     * Сохранение выигрыша в профиль пользователя
     */
    public function saveWin(Request $request)
    {
        // Получаем текущего авторизованного пользователя тенанта
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Пользователь не авторизован'], 401);
        }

        $validated = $request->validate([
            'prize_id' => 'required|integer',
            'description' => 'required|string|max:255',
            'mark' => 'nullable|string|max:255',
            'form_data' => 'nullable|array', // Дополнительные данные из форм (имя, телефон и т.д.)
        ]);

        // Получаем текущие настройки пользователя
        $meta = $user->meta ?? [];
        $wheelWins = $meta['wheel_wins'] ?? [];


        // Формируем запись о новом выигрыше
        $newWin = [
            'prize_id' => $validated['prize_id'],
            'description' => $validated['description'],
            'mark' => $validated['mark'] ?? 'в заведении',
            'won_at' => now()->toIso8601String(),
            'form_data' => $validated['form_data'] ?? [],
        ];

        // Добавляем новый выигрыш в начало массива (последние выигрыши сверху)
        array_unshift($wheelWins, $newWin);

        // 🛡️ Ограничиваем историю последними 50 выигрышами, чтобы поле meta не раздувалось
        if (count($wheelWins) > 50) {
            $wheelWins = array_slice($wheelWins, 0, 50);
        }

        $meta['wheel_wins'] = $wheelWins;

        // Сохраняем обновленные данные
        $user->meta = $meta;
        $user->save();


        return response()->json([
            'success' => true,
            'message' => 'Выигрыш успешно сохранен в профиль!',
            'wins' => $wheelWins
        ]);
    }
}
