<?php

namespace App\Http\Controllers\Tenant\Games;

use App\Facades\CashbackService;
use App\Facades\MessageService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TreasureHuntController extends Controller
{
    /**
     * Стоимость игры (кэшбэк) — единая для всех уровней
     */
    private const GAME_COST = 100;

    /**
     * Стоимость бустеров (кэшбэк)
     */
    private const BOOSTER_COSTS = [
        'radar' => 30,
        'shield' => 50,
        'compass' => 20,
    ];

    /**
     * Конфигурация уровней
     */
    private const LEVELS = [
        1 => [
            'name' => 'Остров',
            'icon' => 'fa-solid fa-umbrella-beach',
            'size' => 4,
            'treasures' => 3,
            'traps' => 2,
            'hints' => 2,
            'desc' => 'Небольшой остров с сокровищами. Идеально для начинающих!',
            'rewards' => [
                ['emoji' => '💰', 'name' => 'Обычное', 'min' => 30, 'max' => 80, 'tier' => 'common', 'weight' => 70],
                ['emoji' => '💎', 'name' => 'Редкое', 'min' => 150, 'max' => 250, 'tier' => 'rare', 'weight' => 25],
                ['emoji' => '👑', 'name' => 'Легендарное', 'min' => 400, 'max' => 600, 'tier' => 'legendary', 'weight' => 5],
            ],
        ],
        2 => [
            'name' => 'Пещера',
            'icon' => 'fa-solid fa-mountain',
            'size' => 5,
            'treasures' => 5,
            'traps' => 4,
            'hints' => 3,
            'desc' => 'Тёмная пещера с большими сокровищами, но и больше ловушек!',
            'rewards' => [
                ['emoji' => '💰', 'name' => 'Обычное', 'min' => 50, 'max' => 150, 'tier' => 'common', 'weight' => 60],
                ['emoji' => '💎', 'name' => 'Редкое', 'min' => 250, 'max' => 450, 'tier' => 'rare', 'weight' => 30],
                ['emoji' => '👑', 'name' => 'Легендарное', 'min' => 700, 'max' => 1200, 'tier' => 'legendary', 'weight' => 10],
            ],
        ],
        3 => [
            'name' => 'Храм',
            'icon' => 'fa-solid fa-landmark',
            'size' => 6,
            'treasures' => 8,
            'traps' => 6,
            'hints' => 4,
            'desc' => 'Древний храм с несметными богатствами. Только для опытных!',
            'rewards' => [
                ['emoji' => '💰', 'name' => 'Обычное', 'min' => 100, 'max' => 300, 'tier' => 'common', 'weight' => 50],
                ['emoji' => '💎', 'name' => 'Редкое', 'min' => 400, 'max' => 800, 'tier' => 'rare', 'weight' => 35],
                ['emoji' => '👑', 'name' => 'Легендарное', 'min' => 1000, 'max' => 1500, 'tier' => 'legendary', 'weight' => 15],
            ],
        ],
    ];

    /**
     * 📋 Получение настроек и состояния
     */
    public function getState(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $meta = $user->meta ?? [];
        $game = $meta['treasure_hunt'] ?? [];

        // Проверяем, есть ли активная игра
        $activeGame = $game['active_game'] ?? null;
        $history = $game['history'] ?? [];
        $unlockedLevels = $game['unlocked_levels'] ?? [1];
        $totalTreasures = $game['total_treasures'] ?? 0;

        return response()->json([
            'success' => true,
            'balance' => (float) $user->cashback_balance,
            'game_cost' => self::GAME_COST,
            'booster_costs' => self::BOOSTER_COSTS,
            'levels' => $this->getLevelsPublicData($unlockedLevels),
            'total_treasures' => $totalTreasures,
            'history' => array_slice($history, 0, 30),
            'active_game' => $activeGame ? $this->sanitizeActiveGame($activeGame) : null,
            'unlocked_levels' => $unlockedLevels,
        ]);
    }

    /**
     * 🎮 Начать новую игру
     */
    public function startGame(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|integer|in:1,2,3',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $level = $validated['level'];
        $levelConfig = self::LEVELS[$level] ?? null;

        if (!$levelConfig) {
            return response()->json(['success' => false, 'message' => 'Уровень не найден'], 404);
        }

        $meta = $user->meta ?? [];
        $game = $meta['treasure_hunt'] ?? [];
        $unlockedLevels = $game['unlocked_levels'] ?? [1];

        // Проверка: уровень открыт?
        if (!in_array($level, $unlockedLevels)) {
            return response()->json([
                'success' => false,
                'message' => 'Этот уровень ещё не открыт',
            ], 403);
        }

        // Проверка: нет ли активной игры?
        if (!empty($game['active_game'])) {
            return response()->json([
                'success' => false,
                'message' => 'У вас уже есть активная игра. Завершите её или сдайтесь.',
            ], 403);
        }

        // Проверка баланса
        $currentBalance = (float) $user->cashback_balance;
        if ($currentBalance < self::GAME_COST) {
            return response()->json([
                'success' => false,
                'message' => "Недостаточно кэшбэка. Нужно " . self::GAME_COST . "₽, у вас " . number_format($currentBalance, 0, '.', '') . "₽",
                'balance' => $currentBalance,
                'required' => self::GAME_COST,
                'shortage' => self::GAME_COST - $currentBalance,
            ], 403);
        }

        // Генерация карты
        $map = $this->generateMap($levelConfig);
        $gameToken = (string) Str::uuid();

        try {
            DB::transaction(function () use ($user, &$meta, &$game, $level, $map, $gameToken) {
                // 💸 Списываем ставку
                CashBackService::call()->removeCashBack(
                    self::GAME_COST,
                    "🗺️ Охота за сокровищами ({$level} уровень)",
                    $user
                );

                // Сохраняем активную игру
                $game['active_game'] = [
                    'token' => $gameToken,
                    'level' => $level,
                    'map' => $map, // Полная карта с сокровищами (скрыта от клиента)
                    'revealed' => [], // Индексы открытых клеток
                    'found_this_round' => 0,
                    'earned_this_round' => 0,
                    'moves' => 0,
                    'shield_active' => false,
                    'started_at' => Carbon::now()->toIso8601String(),
                ];

                $meta['treasure_hunt'] = $game;
                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::error('[TreasureHunt] Ошибка старта игры', [
                'user_id' => $user->id,
                'level' => $level,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка. Средства не списаны.',
            ], 500);
        }

        $user->refresh();
        $newBalance = (float) $user->cashback_balance;

        Log::info('[TreasureHunt] Игра начата', [
            'user_id' => $user->id,
            'level' => $level,
            'token' => $gameToken,
        ]);

        return response()->json([
            'success' => true,
            'token' => $gameToken,
            'level' => $level,
            'level_config' => $this->getLevelPublicData($level, [1]),
            'balance' => $newBalance,
            'map_size' => $levelConfig['size'] * $levelConfig['size'],
            'total_treasures' => $levelConfig['treasures'],
        ]);
    }

    /**
     * 🎯 Открыть клетку
     */
    public function revealCell(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'cell_index' => 'required|integer|min:0',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $token = $validated['token'];
        $cellIndex = $validated['cell_index'];

        $meta = $user->meta ?? [];
        $game = $meta['treasure_hunt'] ?? [];
        $activeGame = $game['active_game'] ?? null;

        // Проверка токена
        if (!$activeGame || ($activeGame['token'] ?? null) !== $token) {
            return response()->json([
                'success' => false,
                'message' => 'Активная игра не найдена',
            ], 404);
        }

        // Проверка: игра уже окончена?
        if (!empty($activeGame['finished'])) {
            return response()->json([
                'success' => false,
                'message' => 'Игра уже завершена',
            ], 400);
        }

        $level = $activeGame['level'];
        $levelConfig = self::LEVELS[$level];
        $mapSize = $levelConfig['size'] * $levelConfig['size'];

        // Валидация индекса клетки
        if ($cellIndex < 0 || $cellIndex >= $mapSize) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный индекс клетки',
            ], 400);
        }

        // Проверка: клетка уже открыта?
        if (in_array($cellIndex, $activeGame['revealed'] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'Клетка уже открыта',
            ], 400);
        }

        $map = $activeGame['map'];
        $cell = $map[$cellIndex];

        // Обработка результата
        $result = [
            'cell_index' => $cellIndex,
            'type' => $cell['type'],
            'tier' => $cell['tier'] ?? null,
            'emoji' => $cell['emoji'] ?? null,
            'name' => $cell['name'] ?? null,
            'value' => $cell['value'] ?? 0,
            'shield_used' => false,
            'game_over' => false,
            'game_won' => false,
            'earned_this_round' => $activeGame['earned_this_round'],
            'found_this_round' => $activeGame['found_this_round'],
            'moves' => ($activeGame['moves'] ?? 0) + 1,
        ];

        try {
            DB::transaction(function () use ($user, &$meta, &$game, &$activeGame, $cellIndex, $cell, &$result) {

                // Добавляем клетку в открытые
                $activeGame['revealed'][] = $cellIndex;
                $activeGame['moves'] = ($activeGame['moves'] ?? 0) + 1;

                if ($cell['type'] === 'treasure') {
                    $activeGame['found_this_round'] = ($activeGame['found_this_round'] ?? 0) + 1;
                    $activeGame['earned_this_round'] = ($activeGame['earned_this_round'] ?? 0) + $cell['value'];

                    $result['earned_this_round'] = $activeGame['earned_this_round'];
                    $result['found_this_round'] = $activeGame['found_this_round'];

                    // Проверка полной победы
                    $levelConfig = self::LEVELS[$activeGame['level']];
                    if ($activeGame['found_this_round'] >= $levelConfig['treasures']) {
                        $activeGame['finished'] = true;
                        $activeGame['won'] = true;
                        $result['game_over'] = true;
                        $result['game_won'] = true;

                        // Начисляем выигрыш
                        CashBackService::call()->addCashBack(
                            $activeGame['earned_this_round'],
                            "🏆 Все сокровища найдены!",
                            $user
                        );

                        // Открываем следующий уровень
                        $unlockedLevels = $game['unlocked_levels'] ?? [1];
                        $nextLevel = $activeGame['level'] + 1;
                        if ($nextLevel <= 3 && !in_array($nextLevel, $unlockedLevels)) {
                            $unlockedLevels[] = $nextLevel;
                            $game['unlocked_levels'] = $unlockedLevels;
                            $result['level_unlocked'] = $nextLevel;
                        }

                        // Добавляем в историю
                        $this->addToHistory($game, $activeGame);
                    }

                } elseif ($cell['type'] === 'trap') {
                    if (!empty($activeGame['shield_active'])) {
                        // Щит сработал
                        $activeGame['shield_active'] = false;
                        $result['shield_used'] = true;
                        $cell['type'] = 'shield_blocked'; // Меняем тип для отображения
                        $result['type'] = 'shield_blocked';
                    } else {
                        // Проигрыш — забираем половину выигрыша
                        $activeGame['finished'] = true;
                        $activeGame['won'] = false;
                        $result['game_over'] = true;
                        $result['game_won'] = false;

                        $half = (int) floor(($activeGame['earned_this_round'] ?? 0) / 2);
                        $activeGame['earned_this_round'] = $half;
                        $result['earned_this_round'] = $half;

                        if ($half > 0) {
                            CashBackService::call()->addCashBack(
                                $half,
                                "💀 Половина сокровищ (ловушка)",
                                $user
                            );
                        }

                        $this->addToHistory($game, $activeGame);
                    }
                }
                // hint и empty не требуют действий

                // Сохраняем изменения
                $game['active_game'] = $activeGame;
                $meta['treasure_hunt'] = $game;
                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::error('[TreasureHunt] Ошибка открытия клетки', [
                'user_id' => $user->id,
                'cell_index' => $cellIndex,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке хода',
            ], 500);
        }

        $user->refresh();
        $result['balance'] = (float) $user->cashback_balance;

        // Уведомления для легендарных призов
        if ($result['type'] === 'treasure' && $result['tier'] === 'legendary') {
            $this->notifyAboutLegendary($user, $cell, $activeGame['level']);
        }

        return response()->json([
            'success' => true,
            'cell' => [
                'index' => $cellIndex,
                'type' => $result['type'],
                'tier' => $result['tier'],
                'emoji' => $result['emoji'],
                'name' => $result['name'],
                'value' => $result['value'],
            ],
            'shield_used' => $result['shield_used'],
            'game_over' => $result['game_over'],
            'game_won' => $result['game_won'],
            'earned_this_round' => $result['earned_this_round'],
            'found_this_round' => $result['found_this_round'],
            'moves' => $result['moves'],
            'level_unlocked' => $result['level_unlocked'] ?? null,
            'balance' => $result['balance'],
        ]);
    }

    /**
     * 🏳️ Сдаться и забрать выигрыш
     */
    public function giveUp(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $meta = $user->meta ?? [];
        $game = $meta['treasure_hunt'] ?? [];
        $activeGame = $game['active_game'] ?? null;

        if (!$activeGame || ($activeGame['token'] ?? null) !== $validated['token']) {
            return response()->json([
                'success' => false,
                'message' => 'Активная игра не найдена',
            ], 404);
        }

        if (!empty($activeGame['finished'])) {
            return response()->json([
                'success' => false,
                'message' => 'Игра уже завершена',
            ], 400);
        }

        $earned = $activeGame['earned_this_round'] ?? 0;
        $level = $activeGame['level'];

        try {
            DB::transaction(function () use ($user, &$meta, &$game, &$activeGame, $earned, $level) {
                // Начисляем заработанное
                if ($earned > 0) {
                    CashBackService::call()->addCashBack(
                        $earned,
                        "🏳️ Добровольное завершение охоты",
                        $user
                    );
                }

                // Помечаем как завершённую
                $activeGame['finished'] = true;
                $activeGame['won'] = false;
                $activeGame['gave_up'] = true;

                $this->addToHistory($game, $activeGame);

                // Очищаем активную игру
                $game['active_game'] = null;
                $meta['treasure_hunt'] = $game;
                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::error('[TreasureHunt] Ошибка при сдаче', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при завершении игры',
            ], 500);
        }

        $user->refresh();

        return response()->json([
            'success' => true,
            'earned' => $earned,
            'balance' => (float) $user->cashback_balance,
        ]);
    }

    /**
     * 🚀 Использовать бустер
     */
    public function useBooster(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'booster' => 'required|string|in:radar,shield,compass',
            'cell_index' => 'nullable|integer|min:0', // Для компаса
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $meta = $user->meta ?? [];
        $game = $meta['treasure_hunt'] ?? [];
        $activeGame = $game['active_game'] ?? null;

        if (!$activeGame || ($activeGame['token'] ?? null) !== $validated['token']) {
            return response()->json([
                'success' => false,
                'message' => 'Активная игра не найдена',
            ], 404);
        }

        if (!empty($activeGame['finished'])) {
            return response()->json([
                'success' => false,
                'message' => 'Игра уже завершена',
            ], 400);
        }

        $booster = $validated['booster'];
        $cost = self::BOOSTER_COSTS[$booster] ?? 0;

        // Проверка баланса
        $currentBalance = (float) $user->cashback_balance;
        if ($currentBalance < $cost) {
            return response()->json([
                'success' => false,
                'message' => "Недостаточно кэшбэка. Нужно {$cost}₽",
                'balance' => $currentBalance,
                'required' => $cost,
            ], 403);
        }

        $map = $activeGame['map'];
        $revealed = $activeGame['revealed'] ?? [];
        $levelConfig = self::LEVELS[$activeGame['level']];
        $size = $levelConfig['size'];

        $result = ['booster' => $booster];

        try {
            DB::transaction(function () use ($user, &$meta, &$game, &$activeGame, $booster, $cost, $map, $revealed, $size, &$result, $validated) {

                // 💸 Списываем стоимость бустера
                CashBackService::call()->removeCashBack(
                    $cost,
                    "🚀 Бустер: {$booster}",
                    $user
                );

                switch ($booster) {
                    case 'radar':
                        // Находим неоткрытое сокровище
                        $treasureCells = [];
                        foreach ($map as $i => $cell) {
                            if ($cell['type'] === 'treasure' && !in_array($i, $revealed)) {
                                $treasureCells[] = $i;
                            }
                        }

                        if (empty($treasureCells)) {
                            throw new \Exception('Сокровищ больше нет');
                        }

                        $targetIndex = $treasureCells[array_rand($treasureCells)];
                        $result['radar_target'] = $targetIndex;
                        break;

                    case 'shield':
                        $activeGame['shield_active'] = true;
                        $result['shield_active'] = true;
                        break;

                    case 'compass':
                        $cellIndex = $validated['cell_index'] ?? null;
                        if ($cellIndex === null) {
                            throw new \Exception('Не указан индекс клетки для компаса');
                        }

                        $row = intdiv($cellIndex, $size);
                        $col = $cellIndex % $size;

                        // Находим ближайшее неоткрытое сокровище
                        $minDist = PHP_INT_MAX;
                        foreach ($map as $i => $cell) {
                            if ($cell['type'] === 'treasure' && !in_array($i, $revealed)) {
                                $r = intdiv($i, $size);
                                $c = $i % $size;
                                $dist = abs($r - $row) + abs($c - $col);
                                if ($dist < $minDist) {
                                    $minDist = $dist;
                                }
                            }
                        }

                        if ($minDist === PHP_INT_MAX) {
                            throw new \Exception('Сокровищ больше нет');
                        }

                        $result['distance'] = $minDist;
                        break;
                }

                $game['active_game'] = $activeGame;
                $meta['treasure_hunt'] = $game;
                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::warning('[TreasureHunt] Ошибка бустера', [
                'user_id' => $user->id,
                'booster' => $booster,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Ошибка при использовании бустера',
            ], 400);
        }

        $user->refresh();
        $result['balance'] = (float) $user->cashback_balance;

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * 💡 Получить подсказку (бесплатно при открытии клетки типа hint)
     */
    public function getHint(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'cell_index' => 'required|integer|min:0',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $meta = $user->meta ?? [];
        $game = $meta['treasure_hunt'] ?? [];
        $activeGame = $game['active_game'] ?? null;

        if (!$activeGame || ($activeGame['token'] ?? null) !== $validated['token']) {
            return response()->json(['success' => false, 'message' => 'Игра не найдена'], 404);
        }

        $cellIndex = $validated['cell_index'];
        $map = $activeGame['map'];
        $revealed = $activeGame['revealed'] ?? [];
        $levelConfig = self::LEVELS[$activeGame['level']];
        $size = $levelConfig['size'];

        // Проверяем, что это действительно hint клетка
        if (!isset($map[$cellIndex]) || $map[$cellIndex]['type'] !== 'hint') {
            return response()->json(['success' => false, 'message' => 'Это не клетка подсказки'], 400);
        }

        $row = intdiv($cellIndex, $size);
        $col = $cellIndex % $size;

        // Находим ближайшее неоткрытое сокровище
        $nearest = null;
        $minDist = PHP_INT_MAX;
        foreach ($map as $i => $cell) {
            if ($cell['type'] === 'treasure' && !in_array($i, $revealed)) {
                $r = intdiv($i, $size);
                $c = $i % $size;
                $dist = abs($r - $row) + abs($c - $col);
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $nearest = ['r' => $r, 'c' => $c, 'dist' => $dist];
                }
            }
        }

        if (!$nearest) {
            return response()->json([
                'success' => true,
                'direction' => '',
                'distance' => 0,
                'message' => 'Сокровищ больше нет',
            ]);
        }

        $direction = '';
        if ($nearest['r'] < $row) $direction .= '↑';
        if ($nearest['r'] > $row) $direction .= '↓';
        if ($nearest['c'] < $col) $direction .= '←';
        if ($nearest['c'] > $col) $direction .= '→';

        return response()->json([
            'success' => true,
            'direction' => $direction,
            'distance' => $nearest['dist'],
        ]);
    }

    // ==========================================
    // 🛠️ ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ==========================================

    protected function generateMap(array $levelConfig): array
    {
        $size = $levelConfig['size'];
        $total = $size * $size;

        // Создаём пустую карту
        $map = array_fill(0, $total, [
            'type' => 'empty',
            'tier' => null,
            'emoji' => null,
            'name' => null,
            'value' => 0,
        ]);

        // Размещаем сокровища
        $this->placeRandom($map, 'treasure', $levelConfig['treasures'], $levelConfig['rewards']);
        // Ловушки
        $this->placeRandom($map, 'trap', $levelConfig['traps']);
        // Подсказки
        $this->placeRandom($map, 'hint', $levelConfig['hints']);

        return $map;
    }

    protected function placeRandom(array &$map, string $type, int $count, array $rewards = []): void
    {
        $indices = range(0, count($map) - 1);
        shuffle($indices);

        $placed = 0;
        foreach ($indices as $i) {
            if ($placed >= $count) break;
            if ($map[$i]['type'] === 'empty') {
                $map[$i]['type'] = $type;

                if ($type === 'treasure' && !empty($rewards)) {
                    $reward = $this->pickRandomReward($rewards);
                    $map[$i]['tier'] = $reward['tier'];
                    $map[$i]['emoji'] = $reward['emoji'];
                    $map[$i]['name'] = $reward['name'];
                    $map[$i]['value'] = mt_rand($reward['min'], $reward['max']);
                }

                $placed++;
            }
        }
    }

    protected function pickRandomReward(array $rewards): array
    {
        $totalWeight = array_sum(array_column($rewards, 'weight'));
        $random = mt_rand(1, $totalWeight);

        $cumulative = 0;
        foreach ($rewards as $r) {
            $cumulative += $r['weight'];
            if ($random <= $cumulative) {
                return $r;
            }
        }

        return $rewards[0];
    }

    protected function addToHistory(array &$game, array $activeGame): void
    {
        $history = $game['history'] ?? [];

        array_unshift($history, [
            'id' => uniqid('hunt_'),
            'date' => Carbon::now()->format('Y-m-d'),
            'level' => $activeGame['level'],
            'treasures' => $activeGame['found_this_round'] ?? 0,
            'moves' => $activeGame['moves'] ?? 0,
            'won' => $activeGame['won'] ?? false,
            'gave_up' => $activeGame['gave_up'] ?? false,
            'earned' => $activeGame['earned_this_round'] ?? 0,
            'cost' => self::GAME_COST,
        ]);

        if (count($history) > 50) {
            $history = array_slice($history, 0, 50);
        }

        $game['history'] = $history;
        $game['total_treasures'] = ($game['total_treasures'] ?? 0) + ($activeGame['found_this_round'] ?? 0);

        // Очищаем активную игру
        $game['active_game'] = null;
    }

    protected function sanitizeActiveGame(array $activeGame): array
    {
        // Возвращаем клиенту только безопасные данные (без карты!)
        $levelConfig = self::LEVELS[$activeGame['level']] ?? self::LEVELS[1];
        $totalCells = $levelConfig['size'] * $levelConfig['size'];

        // Создаём массив клеток без содержимого
        $cells = [];
        for ($i = 0; $i < $totalCells; $i++) {
            $revealed = in_array($i, $activeGame['revealed'] ?? []);
            $cellData = [
                'index' => $i,
                'revealed' => $revealed,
            ];

            if ($revealed && isset($activeGame['map'][$i])) {
                $mapCell = $activeGame['map'][$i];
                $cellData['type'] = $mapCell['type'];
                $cellData['tier'] = $mapCell['tier'] ?? null;
                $cellData['emoji'] = $mapCell['emoji'] ?? null;
                $cellData['name'] = $mapCell['name'] ?? null;
                $cellData['value'] = $mapCell['value'] ?? 0;
            }

            $cells[] = $cellData;
        }

        return [
            'token' => $activeGame['token'],
            'level' => $activeGame['level'],
            'level_config' => $this->getLevelPublicData($activeGame['level'], []),
            'cells' => $cells,
            'found_this_round' => $activeGame['found_this_round'] ?? 0,
            'earned_this_round' => $activeGame['earned_this_round'] ?? 0,
            'moves' => $activeGame['moves'] ?? 0,
            'shield_active' => $activeGame['shield_active'] ?? false,
            'finished' => $activeGame['finished'] ?? false,
            'won' => $activeGame['won'] ?? false,
            'total_treasures' => $levelConfig['treasures'],
        ];
    }

    protected function getLevelsPublicData(array $unlockedLevels): array
    {
        $result = [];
        foreach (self::LEVELS as $id => $config) {
            $result[] = $this->getLevelPublicData($id, $unlockedLevels);
        }
        return $result;
    }

    protected function getLevelPublicData(int $id, array $unlockedLevels): array
    {
        $config = self::LEVELS[$id];
        return [
            'id' => $id,
            'name' => $config['name'],
            'icon' => $config['icon'],
            'size' => $config['size'],
            'treasures' => $config['treasures'],
            'traps' => $config['traps'],
            'hints' => $config['hints'],
            'desc' => $config['desc'],
            'unlocked' => in_array($id, $unlockedLevels),
            'rewards' => array_map(fn($r) => [
                'emoji' => $r['emoji'],
                'name' => $r['name'],
                'min' => $r['min'],
                'max' => $r['max'],
                'tier' => $r['tier'],
            ], $config['rewards']),
        ];
    }

    protected function notifyAboutLegendary($user, array $cell, int $level): void
    {
        try {
            $tenant = app('tenant');
            $phone = $user->phone ?? 'не указан';
            $userName = $user->name ?? 'Не указано';
            $levelName = self::LEVELS[$level]['name'] ?? 'Неизвестно';

            $adminMessage = <<<HTML
👑 <b>ЛЕГЕНДАРНАЯ НАХОДКА!</b>

👤 <b>Клиент:</b> {$userName}
📱 <b>Телефон:</b> {$phone}
🗺️ <b>Уровень:</b> {$levelName}

{$cell['emoji']} <b>Сокровище:</b> {$cell['name']}
💰 <b>Ценность:</b> +{$cell['value']} бонусов

🏢 <b>Тенант:</b> {$tenant->name}
HTML;

            MessageService::call()->sendMessage([
                'message' => $adminMessage,
                'title' => '👑 Легендарная находка!',
                'recipients' => ['partners' => true, 'telegram' => true],
                'meta' => [
                    'event_type' => 'treasure_hunt_legendary',
                    'customer_name' => $userName,
                    'customer_phone' => $phone,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[TreasureHunt] Ошибка уведомления', ['error' => $e->getMessage()]);
        }
    }
}
