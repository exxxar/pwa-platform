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

class SlotMachineController extends Controller
{
    /**
     * Стоимость одного спина (кэшбэк)
     */
    private const DEFAULT_SPIN_COST = 1000;

    /**
     * Таблица символов с весами и выплатами
     */
    private const SYMBOLS = [
        ['icon' => '🍒', 'weight' => 40, 'prize_3x' => 1500, 'prize_2x' => 50,  'name' => 'Вишня'],
        ['icon' => '🍋', 'weight' => 25, 'prize_3x' => 2000, 'prize_2x' => 100, 'name' => 'Лимон'],
        ['icon' => '🍊', 'weight' => 15, 'prize_3x' => 3000, 'prize_2x' => 150, 'name' => 'Апельсин'],
        ['icon' => '🔔', 'weight' => 10, 'prize_3x' => 5000, 'prize_2x' => 250, 'name' => 'Колокол'],
        ['icon' => '💎', 'weight' => 7,  'prize_3x' => 10000, 'prize_2x' => 500, 'name' => 'Алмаз'],
        ['icon' => '7️⃣', 'weight' => 3,  'prize_3x' => 50000, 'prize_2x' => 1000, 'name' => 'Семёрка'],
    ];

    /**
     * 📋 Получение настроек игры
     */
    public function getSettings(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['slot_machine'] ?? [];

        return response()->json([
            'success' => true,
            'slot_machine' => [
                'can_play' => $settings['can_play'] ?? true,
                'interval' => (int) ($settings['interval'] ?? 1),
                'attempts_per_period' => (int) ($settings['attempts_per_period'] ?? 1),
                'spin_cost' => (float) ($settings['spin_cost'] ?? self::DEFAULT_SPIN_COST),
                'symbols' => array_map(fn($s) => [
                    'icon' => $s['icon'],
                    'name' => $s['name'],
                    'prize_3x' => $s['prize_3x'],
                    'prize_2x' => $s['prize_2x'],
                ], self::SYMBOLS),
            ],
        ]);
    }

    /**
     * 📊 Получение состояния пользователя
     */
    public function getState(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['slot_machine'] ?? [];
        $intervalDays = (int) ($settings['interval'] ?? 1);
        $attemptsPerPeriod = (int) ($settings['attempts_per_period'] ?? 1);

        $meta = $user->meta ?? [];
        $attempts = $meta['slot_machine_attempts'] ?? [];
        $cutoffDate = Carbon::now()->subDays($intervalDays);

        $validAttempts = collect($attempts)->filter(function ($attempt) use ($cutoffDate) {
            return Carbon::parse($attempt['played_at'])->gte($cutoffDate);
        })->values();

        $attemptsUsed = $validAttempts->count();
        $attemptsLeft = max(0, $attemptsPerPeriod - $attemptsUsed);

        return response()->json([
            'success' => true,
            'attempts_left' => $attemptsLeft,
            'attempts_used' => $attemptsUsed,
            'attempts_per_period' => $attemptsPerPeriod,
            'balance' => (float) $user->cashback_balance,
            'game_finished' => $attemptsLeft <= 0,
            'history' => array_slice($meta['slot_machine_history'] ?? [], 0, 50),
            'stats' => [
                'total_spins' => count($meta['slot_machine_history'] ?? []),
                'total_won' => array_sum(array_column($meta['slot_machine_history'] ?? [], 'win_amount')),
                'total_spent' => array_sum(array_column($meta['slot_machine_history'] ?? [], 'cost')),
                'biggest_win' => !empty($meta['slot_machine_history'])
                    ? max(array_column($meta['slot_machine_history'], 'win_amount'))
                    : 0,
            ],
        ]);
    }

    /**
     * 🎰 Выполнение спина
     */
    public function spin(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['slot_machine'] ?? [];
        $intervalDays = (int) ($settings['interval'] ?? 1);
        $attemptsPerPeriod = (int) ($settings['attempts_per_period'] ?? 1);
        $spinCost = (float) ($settings['spin_cost'] ?? self::DEFAULT_SPIN_COST);

        $meta = $user->meta ?? [];
        $attempts = $meta['slot_machine_attempts'] ?? [];
        $cutoffDate = Carbon::now()->subDays($intervalDays);

        // ==========================================
        // 1️⃣ ПРОВЕРКА ЛИМИТА ПОПЫТОК
        // ==========================================
        $validAttempts = collect($attempts)->filter(function ($attempt) use ($cutoffDate) {
            return Carbon::parse($attempt['played_at'])->gte($cutoffDate);
        })->values()->toArray();

        if (count($validAttempts) >= $attemptsPerPeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Попытки на этот период закончились. Попробуйте позже!',
            ], 403);
        }

        // ==========================================
        // 2️⃣ ПРОВЕРКА БАЛАНСА
        // ==========================================
        $currentBalance = (float) $user->cashback_balance;

        if ($currentBalance < $spinCost) {
            return response()->json([
                'success' => false,
                'message' => "Недостаточно кэшбэка. Нужно {$spinCost}₽, у вас " . number_format($currentBalance, 0, '.', '') . "₽",
                'balance' => $currentBalance,
                'required' => $spinCost,
                'shortage' => $spinCost - $currentBalance,
            ], 403);
        }

        // ==========================================
        // 3️⃣ 🎲 ГЕНЕРАЦИЯ РЕЗУЛЬТАТА НА СЕРВЕРЕ
        // ==========================================
        $reels = [
            $this->getRandomSymbol(),
            $this->getRandomSymbol(),
            $this->getRandomSymbol(),
        ];

        $winResult = $this->evaluateWin($reels, $spinCost);

        // ==========================================
        // 4️⃣ АТОМАРНАЯ ТРАНЗАКЦИЯ
        // ==========================================
        try {
            DB::transaction(function () use ($user, &$meta, &$attempts, $spinCost, $reels, $winResult) {

                // 💸 Списываем ставку
                CashBackService::call()->removeCashBack(
                    $spinCost,
                    "🎰 Спин в Слот-машине",
                    $user
                );

                // 💰 Начисляем выигрыш (если есть)
                if ($winResult['win_amount'] > 0) {
                    CashBackService::call()->addCashBack(
                        $winResult['win_amount'],
                        "🏆 Выигрыш в Слот-машине: {$winResult['combination']}",
                        $user
                    );
                }

                // 📝 Записываем попытку
                $attempts[] = [
                    'played_at' => Carbon::now()->toIso8601String(),
                    'cost' => $spinCost,
                    'reels' => $reels,
                    'win_amount' => $winResult['win_amount'],
                    'win_tier' => $winResult['win_tier'],
                ];

                if (count($attempts) > 100) {
                    $attempts = array_slice($attempts, -100);
                }

                $meta['slot_machine_attempts'] = $attempts;

                // 📜 История
                $history = $meta['slot_machine_history'] ?? [];
                array_unshift($history, [
                    'id' => uniqid('slot_'),
                    'reels' => $reels,
                    'win_amount' => $winResult['win_amount'],
                    'win_tier' => $winResult['win_tier'],
                    'combination' => $winResult['combination'],
                    'cost' => $spinCost,
                    'net_profit' => $winResult['win_amount'] - $spinCost,
                    'played_at' => Carbon::now()->toIso8601String(),
                ]);

                if (count($history) > 100) {
                    $history = array_slice($history, 0, 100);
                }
                $meta['slot_machine_history'] = $history;

                $user->meta = $meta;
                $user->save();
            });

        } catch (\Throwable $e) {
            Log::error('[SlotMachine] Ошибка спина', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка. Средства не списаны.',
            ], 500);
        }

        // ==========================================
        // 5️⃣ ОБНОВЛЯЕМ ПОЛЬЗОВАТЕЛЯ
        // ==========================================
        $user->refresh();
        $newBalance = (float) $user->cashback_balance;

        Log::info('[SlotMachine] Спин завершён', [
            'user_id' => $user->id,
            'reels' => $reels,
            'win_tier' => $winResult['win_tier'],
            'win_amount' => $winResult['win_amount'],
            'net_profit' => $winResult['win_amount'] - $spinCost,
        ]);

        // 📢 Уведомления для крупных выигрышей
        if (in_array($winResult['win_tier'], ['legendary', 'epic'])) {
            $this->notifyAboutWin($user, $reels, $winResult, $spinCost);
        }

        return response()->json([
            'success' => true,
            'reels' => $reels,
            'win_tier' => $winResult['win_tier'],
            'win_amount' => $winResult['win_amount'],
            'combination' => $winResult['combination'],
            'net_profit' => $winResult['win_amount'] - $spinCost,
            'cost' => $spinCost,
            'balance' => $newBalance,
            'attempts_left' => max(0, $attemptsPerPeriod - count($validAttempts) - 1),
        ]);
    }

    // ==========================================
    // 🎲 ЛОГИКА ИГРЫ
    // ==========================================

    /**
     * Получить случайный символ с учётом весов
     */
    protected function getRandomSymbol(): string
    {
        $totalWeight = array_sum(array_column(self::SYMBOLS, 'weight'));
        $random = mt_rand(1, $totalWeight);

        $cumulative = 0;
        foreach (self::SYMBOLS as $symbol) {
            $cumulative += $symbol['weight'];
            if ($random <= $cumulative) {
                return $symbol['icon'];
            }
        }

        return self::SYMBOLS[0]['icon'];
    }

    /**
     * Оценить выигрыш по комбинации
     */
    protected function evaluateWin(array $reels, float $spinCost): array
    {
        [$s1, $s2, $s3] = $reels;

        $sym1 = $this->findSymbol($s1);
        $sym2 = $this->findSymbol($s2);
        $sym3 = $this->findSymbol($s3);

        // 3 одинаковых
        if ($s1 === $s2 && $s2 === $s3) {
            $winAmount = $sym1['prize_3x'];

            // Определяем тир
            if ($s1 === '7️⃣') {
                $tier = 'legendary';
                $combination = 'ДЖЕКПОТ: Три семёрки!';
            } elseif ($s1 === '💎') {
                $tier = 'epic';
                $combination = 'Три алмаза!';
            } elseif ($s1 === '🔔') {
                $tier = 'rare';
                $combination = 'Три колокола!';
            } else {
                $tier = 'common';
                $combination = "Три {$sym1['name']}";
            }

            return [
                'win_tier' => $tier,
                'win_amount' => $winAmount,
                'combination' => $combination,
            ];
        }

        // 2 одинаковых
        if ($s1 === $s2 || $s2 === $s3 || $s1 === $s3) {
            $matchSymbol = $s1 === $s2 ? $s1 : ($s2 === $s3 ? $s2 : $s1);
            $matchData = $this->findSymbol($matchSymbol);

            return [
                'win_tier' => 'consolation',
                'win_amount' => $matchData['prize_2x'],
                'combination' => "Два {$matchData['name']}",
            ];
        }

        // Проигрыш
        return [
            'win_tier' => 'loss',
            'win_amount' => 0,
            'combination' => 'Нет комбинации',
        ];
    }

    /**
     * Найти данные символа по иконке
     */
    protected function findSymbol(string $icon): array
    {
        foreach (self::SYMBOLS as $symbol) {
            if ($symbol['icon'] === $icon) {
                return $symbol;
            }
        }
        return self::SYMBOLS[0];
    }

    // ==========================================
    // 📢 УВЕДОМЛЕНИЯ
    // ==========================================

    protected function notifyAboutWin($user, array $reels, array $winResult, float $spinCost): void
    {
        try {
            $tenant = app('tenant');
            $phone = $user->phone ?? 'не указан';
            $userName = $user->name ?? 'Не указано';
            $reelsStr = implode(' ', $reels);

            $adminMessage = <<<HTML
🎰 <b>Крупный выигрыш в Слот-машине!</b>

👤 <b>Клиент:</b> {$userName}
📱 <b>Телефон:</b> {$phone}
🆔 <b>ID:</b> #{$user->id}

🎰 <b>Комбинация:</b> {$reelsStr}
🏆 <b>Выигрыш:</b> +{$winResult['win_amount']} бонусов
💳 <b>Ставка:</b> {$spinCost}₽
📊 <b>Результат:</b> {$winResult['combination']}

🏢 <b>Тенант:</b> {$tenant->name}
HTML;

            MessageService::call()->sendMessage([
                'message' => $adminMessage,
                'title' => '🎰 Слот-машина: ' . ucfirst($winResult['win_tier']),
                'recipients' => ['partners' => true, 'telegram' => true],
                'meta' => [
                    'event_type' => 'slot_machine_big_win',
                    'customer_name' => $userName,
                    'customer_phone' => $phone,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[SlotMachine] Ошибка уведомления', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
