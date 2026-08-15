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

class GuessNumberController extends Controller
{
    /**
     * Единая стоимость игры (кэшбэк)
     */
    private const GAME_COST = 100;

    /**
     * Конфигурация режимов
     */
    private const MODES = [
        'classic' => [
            'name' => 'Классика',
            'icon' => 'fa-solid fa-dice',
            'min' => 1,
            'max' => 100,
            'max_attempts' => 10,
            'desc' => 'Угадай число от 1 до 100. Чем меньше попыток — тем больше приз!',
            'rewards' => [
                ['attempts' => '1 попытка', 'max_attempts' => 1, 'value' => 500],
                ['attempts' => '2-3 попытки', 'max_attempts' => 3, 'value' => 200],
                ['attempts' => '4-5 попыток', 'max_attempts' => 5, 'value' => 100],
                ['attempts' => '6-7 попыток', 'max_attempts' => 7, 'value' => 50],
                ['attempts' => '8+ попыток', 'max_attempts' => PHP_INT_MAX, 'value' => 20],
            ],
        ],
        'jackpot' => [
            'name' => 'Джекпот',
            'icon' => 'fa-solid fa-crown',
            'min' => 1,
            'max' => 1000,
            'max_attempts' => 15,
            'desc' => 'Число от 1 до 1000. Высокие ставки — высокие выигрыши!',
            'rewards' => [
                ['attempts' => '1-3 попытки', 'max_attempts' => 3, 'value' => 5000],
                ['attempts' => '4-7 попыток', 'max_attempts' => 7, 'value' => 2000],
                ['attempts' => '8-12 попыток', 'max_attempts' => 12, 'value' => 500],
                ['attempts' => '13+ попыток', 'max_attempts' => PHP_INT_MAX, 'value' => 100],
            ],
        ],
        'challenge' => [
            'name' => 'Вызов',
            'icon' => 'fa-solid fa-fire',
            'min' => 1,
            'max' => 100,
            'max_attempts' => 3,
            'desc' => 'Только 3 попытки, чтобы угадать число. Награда за мастерство!',
            'rewards' => [
                ['attempts' => '1 попытка', 'max_attempts' => 1, 'value' => 1000],
                ['attempts' => '2 попытки', 'max_attempts' => 2, 'value' => 500],
                ['attempts' => '3 попытки', 'max_attempts' => 3, 'value' => 300],
            ],
        ],
    ];

    /**
     * 📋 Получение состояния игры
     */
    public function getState(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $meta = $user->meta ?? [];
        $game = $meta['guess_number'] ?? [];

        $stats = $game['stats'] ?? [
            'wins' => 0,
            'current_streak' => 0,
            'best_streak' => 0,
        ];

        $history = $game['history'] ?? [];
        $activeGame = $game['active_game'] ?? null;

        return response()->json([
            'success' => true,
            'balance' => (float) $user->cashback_balance,
            'game_cost' => self::GAME_COST,
            'modes' => $this->getModesPublicData(),
            'stats' => $stats,
            'history' => array_slice($history, 0, 30),
            'active_game' => $activeGame ? $this->sanitizeActiveGame($activeGame) : null,
        ]);
    }

    /**
     * 🎮 Начать новую игру
     */
    public function startGame(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|string|in:classic,jackpot,challenge',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $mode = $validated['mode'];
        $modeConfig = self::MODES[$mode];

        $meta = $user->meta ?? [];
        $game = $meta['guess_number'] ?? [];

        // Проверка: есть ли активная игра?
        if (!empty($game['active_game']) && empty($game['active_game']['finished'])) {
            return response()->json([
                'success' => false,
                'message' => 'У вас уже есть активная игра. Завершите её или сдадитесь.',
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

        // Генерация секретного числа (ТОЛЬКО НА СЕРВЕРЕ!)
        $secretNumber = mt_rand($modeConfig['min'], $modeConfig['max']);
        $gameToken = (string) Str::uuid();

        try {
            DB::transaction(function () use ($user, &$meta, &$game, $mode, $secretNumber, $gameToken) {
                // 💸 Списываем ставку
                CashBackService::call()->removeCashBack(
                    self::GAME_COST,
                    "🔢 Угадай число ({$mode})",
                    $user
                );

                // 🔒 Сохраняем активную игру (секретное число только на сервере!)
                $game['active_game'] = [
                    'token' => $gameToken,
                    'mode' => $mode,
                    'secret_number' => $secretNumber, // Скрыто от клиента!
                    'min' => self::MODES[$mode]['min'],
                    'max' => self::MODES[$mode]['max'],
                    'max_attempts' => self::MODES[$mode]['max_attempts'],
                    'attempts' => 0,
                    'guesses' => [], // История попыток [{value, hint, distance}]
                    'finished' => false,
                    'won' => false,
                    'started_at' => Carbon::now()->toIso8601String(),
                ];

                $meta['guess_number'] = $game;
                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::error('[GuessNumber] Ошибка старта игры', [
                'user_id' => $user->id,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка. Средства не списаны.',
            ], 500);
        }

        $user->refresh();

        Log::info('[GuessNumber] Игра начата', [
            'user_id' => $user->id,
            'mode' => $mode,
            'token' => $gameToken,
        ]);

        return response()->json([
            'success' => true,
            'token' => $gameToken,
            'mode' => $mode,
            'mode_config' => $this->getModePublicData($mode),
            'balance' => (float) $user->cashback_balance,
        ]);
    }

    /**
     * 🎯 Сделать попытку угадать число
     */
    public function guess(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'number' => 'required|integer',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $token = $validated['token'];
        $guessValue = (int) $validated['number'];

        $meta = $user->meta ?? [];
        $game = $meta['guess_number'] ?? [];
        $activeGame = $game['active_game'] ?? null;

        // Проверка токена
        if (!$activeGame || ($activeGame['token'] ?? null) !== $token) {
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

        $mode = $activeGame['mode'];
        $modeConfig = self::MODES[$mode];
        $min = $activeGame['min'];
        $max = $activeGame['max'];
        $maxAttempts = $activeGame['max_attempts'];

        // Валидация числа
        if ($guessValue < $min || $guessValue > $max) {
            return response()->json([
                'success' => false,
                'message' => "Число должно быть от {$min} до {$max}",
            ], 400);
        }

        // Проверка: не исчерпаны ли попытки
        if (($activeGame['attempts'] ?? 0) >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Попытки закончились',
            ], 400);
        }

        $secretNumber = $activeGame['secret_number'];
        $distance = abs($guessValue - $secretNumber);

        // Определяем подсказку
        if ($guessValue === $secretNumber) {
            $hint = 'correct';
            $icon = 'fa-solid fa-bullseye';
            $text = 'В яблочко!';
            $distanceText = null;
        } elseif ($guessValue < $secretNumber) {
            $hint = 'higher';
            $icon = 'fa-solid fa-arrow-up';
            $text = 'Загаданное число больше';
            $distanceText = $this->getDistanceText($distance, $max - $min);
        } else {
            $hint = 'lower';
            $icon = 'fa-solid fa-arrow-down';
            $text = 'Загаданное число меньше';
            $distanceText = $this->getDistanceText($distance, $max - $min);
        }

        $newAttempts = ($activeGame['attempts'] ?? 0) + 1;
        $isWin = ($guessValue === $secretNumber);
        $isLost = (!$isWin && $newAttempts >= $maxAttempts);

        $reward = 0;
        $stats = $game['stats'] ?? ['wins' => 0, 'current_streak' => 0, 'best_streak' => 0];

        try {
            DB::transaction(function () use (
                $user, &$meta, &$game, &$activeGame,
                $guessValue, $hint, $icon, $text, $distanceText, $distance,
                $newAttempts, $isWin, $isLost, &$reward, &$stats, $mode
            ) {
                // Добавляем попытку в историю
                $activeGame['guesses'][] = [
                    'value' => $guessValue,
                    'hint' => $hint,
                    'icon' => $icon,
                    'text' => $text,
                    'distance' => $distanceText,
                    'distance_num' => $distance,
                ];
                $activeGame['attempts'] = $newAttempts;

                if ($isWin) {
                    // 🏆 ПОБЕДА!
                    $reward = $this->calculateReward($mode, $newAttempts, $stats['current_streak'] ?? 0);

                    $activeGame['finished'] = true;
                    $activeGame['won'] = true;
                    $activeGame['reward'] = $reward;

                    // Начисляем выигрыш
                    CashBackService::call()->addCashBack(
                        $reward,
                        "🏆 Победа в Угадай число ({$mode}, {$newAttempts} попыток)",
                        $user
                    );

                    // Обновляем статистику
                    $stats['wins'] = ($stats['wins'] ?? 0) + 1;
                    $stats['current_streak'] = ($stats['current_streak'] ?? 0) + 1;
                    if (($stats['current_streak'] ?? 0) > ($stats['best_streak'] ?? 0)) {
                        $stats['best_streak'] = $stats['current_streak'];
                    }

                    // Добавляем в историю
                    $this->addToHistory($game, $activeGame, $reward);

                } elseif ($isLost) {
                    // 💀 ПРОИГРЫШ
                    $activeGame['finished'] = true;
                    $activeGame['won'] = false;

                    $stats['current_streak'] = 0;

                    $this->addToHistory($game, $activeGame, 0);
                }

                $game['stats'] = $stats;
                $game['active_game'] = $activeGame;
                $meta['guess_number'] = $game;
                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::error('[GuessNumber] Ошибка попытки', [
                'user_id' => $user->id,
                'guess' => $guessValue,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке попытки',
            ], 500);
        }

        $user->refresh();

        // Уведомления для крупных выигрышей
        if ($isWin && $reward >= 1000) {
            $this->notifyAboutBigWin($user, $mode, $newAttempts, $reward);
        }

        return response()->json([
            'success' => true,
            'guess' => [
                'value' => $guessValue,
                'hint' => $hint,
                'icon' => $icon,
                'text' => $text,
                'distance' => $distanceText,
                'distance_num' => $distance,
            ],
            'attempts' => $newAttempts,
            'max_attempts' => $maxAttempts,
            'game_over' => $isWin || $isLost,
            'game_won' => $isWin,
            'reward' => $reward,
            'secret_number' => ($isWin || $isLost) ? $secretNumber : null, // Раскрываем только в конце
            'stats' => $stats,
            'balance' => (float) $user->cashback_balance,
        ]);
    }

    /**
     * 🏳️ Сдаться
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
        $game = $meta['guess_number'] ?? [];
        $activeGame = $game['active_game'] ?? null;

        if (!$activeGame || ($activeGame['token'] ?? null) !== $validated['token']) {
            return response()->json([
                'success' => false,
                'message' => 'Игра не найдена',
            ], 404);
        }

        if (!empty($activeGame['finished'])) {
            return response()->json([
                'success' => false,
                'message' => 'Игра уже завершена',
            ], 400);
        }

        try {
            DB::transaction(function () use (&$game, &$activeGame) {
                $activeGame['finished'] = true;
                $activeGame['won'] = false;
                $activeGame['gave_up'] = true;

                // Сбрасываем серию побед
                $stats = $game['stats'] ?? ['wins' => 0, 'current_streak' => 0, 'best_streak' => 0];
                $stats['current_streak'] = 0;
                $game['stats'] = $stats;

                $this->addToHistory($game, $activeGame, 0);
                $game['active_game'] = null;

                $user = Auth::guard('tenant')->user();
                $meta = $user->meta ?? [];
                $meta['guess_number'] = $game;
                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::error('[GuessNumber] Ошибка сдачи', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сдаче',
            ], 500);
        }

        $user->refresh();

        return response()->json([
            'success' => true,
            'secret_number' => $activeGame['secret_number'],
            'balance' => (float) $user->cashback_balance,
            'stats' => $game['stats'],
        ]);
    }

    // ==========================================
    // 🛠️ ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ==========================================

    protected function calculateReward(string $mode, int $attempts, int $currentStreak): int
    {
        $modeConfig = self::MODES[$mode] ?? self::MODES['classic'];
        $rewards = $modeConfig['rewards'];

        $base = 0;
        foreach ($rewards as $reward) {
            if ($attempts <= $reward['max_attempts']) {
                $base = $reward['value'];
                break;
            }
        }

        // Бонус за серию побед (3+) = x1.5
        if ($currentStreak >= 3) {
            $base = (int) floor($base * 1.5);
        }

        return $base;
    }

    protected function getDistanceText(int $distance, int $maxDist): string
    {
        $percent = ($distance / $maxDist) * 100;

        if ($percent <= 10) return '🔥 Очень горячо!';
        if ($percent <= 25) return '🔥 Горячо';
        if ($percent <= 50) return '😐 Тепло';
        return '❄️ Холодно';
    }

    protected function addToHistory(array &$game, array $activeGame, int $reward): void
    {
        $history = $game['history'] ?? [];

        array_unshift($history, [
            'id' => uniqid('gn_'),
            'date' => Carbon::now()->format('Y-m-d'),
            'mode' => $activeGame['mode'],
            'attempts' => $activeGame['attempts'] ?? 0,
            'secret' => $activeGame['secret_number'],
            'won' => $activeGame['won'] ?? false,
            'gave_up' => $activeGame['gave_up'] ?? false,
            'reward' => $reward,
            'cost' => self::GAME_COST,
        ]);

        if (count($history) > 50) {
            $history = array_slice($history, 0, 50);
        }

        $game['history'] = $history;
        $game['active_game'] = null; // Очищаем активную игру
    }

    protected function sanitizeActiveGame(array $activeGame): array
    {
        // ВАЖНО: НЕ отдаём secret_number клиенту!
        return [
            'token' => $activeGame['token'],
            'mode' => $activeGame['mode'],
            'mode_config' => $this->getModePublicData($activeGame['mode']),
            'min' => $activeGame['min'],
            'max' => $activeGame['max'],
            'max_attempts' => $activeGame['max_attempts'],
            'attempts' => $activeGame['attempts'],
            'guesses' => $activeGame['guesses'] ?? [],
            'finished' => $activeGame['finished'] ?? false,
            'won' => $activeGame['won'] ?? false,
            'reward' => $activeGame['reward'] ?? 0,
            // secret_number намеренно НЕ включаем!
        ];
    }

    protected function getModesPublicData(): array
    {
        $result = [];
        foreach (self::MODES as $key => $config) {
            $result[$key] = $this->getModePublicData($key);
        }
        return $result;
    }

    protected function getModePublicData(string $mode): array
    {
        $config = self::MODES[$mode];
        return [
            'name' => $config['name'],
            'icon' => $config['icon'],
            'min' => $config['min'],
            'max' => $config['max'],
            'max_attempts' => $config['max_attempts'],
            'desc' => $config['desc'],
            'rewards' => array_map(fn($r) => [
                'attempts' => $r['attempts'],
                'value' => $r['value'],
            ], $config['rewards']),
        ];
    }

    protected function notifyAboutBigWin($user, string $mode, int $attempts, int $reward): void
    {
        try {
            $tenant = app('tenant');
            $phone = $user->phone ?? 'не указан';
            $userName = $user->name ?? 'Не указано';
            $modeName = self::MODES[$mode]['name'] ?? $mode;

            $adminMessage = <<<HTML
🎯 <b>Крупная победа в "Угадай число"!</b>

👤 <b>Клиент:</b> {$userName}
📱 <b>Телефон:</b> {$phone}
🎮 <b>Режим:</b> {$modeName}

🔢 <b>Угадал за:</b> {$attempts} попыток
💰 <b>Выигрыш:</b> +{$reward} бонусов

🏢 <b>Тенант:</b> {$tenant->name}
HTML;

            MessageService::call()->sendMessage([
                'message' => $adminMessage,
                'title' => '🎯 Угадай число: крупная победа',
                'recipients' => ['partners' => true, 'telegram' => true],
                'meta' => [
                    'event_type' => 'guess_number_big_win',
                    'customer_name' => $userName,
                    'customer_phone' => $phone,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[GuessNumber] Ошибка уведомления', ['error' => $e->getMessage()]);
        }
    }
}
