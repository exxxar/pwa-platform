<?php

namespace App\Http\Controllers\Tenant\Games;

use App\Facades\CashbackService;
use App\Facades\MessageService;
use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantDialog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CardGameController extends Controller
{
    /**
     * Стоимость одной игры (кэшбэк)
     */
    private const DEFAULT_SPIN_COST = 100;

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
        $settings = $tenant->settings['card_game'] ?? [];

        // Призы с дефолтным набором, если не настроено
        $prizes = $settings['prizes'] ?? $this->getDefaultPrizes();

        return response()->json([
            'card_game' => [
                'can_play' => $settings['can_play'] ?? true,
                'interval' => $settings['interval'] ?? 1,
                'attempts_per_period' => $settings['attempts_per_period'] ?? 1,
                'grid_columns' => $settings['grid_columns'] ?? 4,
                'grid_rows' => $settings['grid_rows'] ?? 3,
                'spin_cost' => (float) ($settings['spin_cost'] ?? self::DEFAULT_SPIN_COST),
                'rules' => $settings['rules'] ?? '',
                'win_message' => $settings['win_message'] ?? '',
                'rarity_chances' => $settings['rarity_chances'] ?? [
                        'common' => 70,
                        'rare' => 20,
                        'epic' => 8,
                        'legendary' => 2,
                    ],
                'prizes' => $prizes,
            ]
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
        $settings = $tenant->settings['card_game'] ?? [];
        $intervalDays = $settings['interval'] ?? 1;
        $attemptsPerPeriod = $settings['attempts_per_period'] ?? 1;

        $meta = $user->meta ?? [];
        $attempts = $meta['card_game_attempts'] ?? [];
        $cutoffDate = Carbon::now()->subDays($intervalDays);

        // Фильтруем только актуальные попытки
        $validAttempts = collect($attempts)->filter(function ($attempt) use ($cutoffDate) {
            return Carbon::parse($attempt['played_at'])->gte($cutoffDate);
        })->values();

        $attemptsUsed = $validAttempts->count();
        $attemptsLeft = max(0, $attemptsPerPeriod - $attemptsUsed);
        $lastPlayDate = $validAttempts->last()['played_at'] ?? null;

        return response()->json([
            'success' => true,
            'attempts_left' => $attemptsLeft,
            'attempts_used' => $attemptsUsed,
            'attempts_per_period' => $attemptsPerPeriod,
            'balance' => (float) $user->cashback_balance,
            'last_play_date' => $lastPlayDate,
            'game_finished' => $attemptsLeft <= 0,
            'wins_history' => $meta['card_game_wins'] ?? [],
        ]);
    }

    /**
     * 🎴 Основная логика игры: ставка + выбор приза
     */
    public function play(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['card_game'] ?? [];
        $intervalDays = $settings['interval'] ?? 1;
        $attemptsPerPeriod = $settings['attempts_per_period'] ?? 1;
        $spinCost = (float) ($settings['spin_cost'] ?? self::DEFAULT_SPIN_COST);

        $meta = $user->meta ?? [];
        $attempts = $meta['card_game_attempts'] ?? [];
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
        // 2️⃣ ПРОВЕРКА БАЛАНСА КЭШБЭКА
        // ==========================================
        $currentBalance = (float) $user->cashback_balance;

        if ($currentBalance < $spinCost) {
            Log::warning('[CardGame] Недостаточно кэшбэка', [
                'user_id' => $user->id,
                'balance' => $currentBalance,
                'required' => $spinCost,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Недостаточно кэшбэка. Нужно {$spinCost}₽, у вас " . number_format($currentBalance, 0, '.', '') . "₽",
                'balance' => $currentBalance,
                'required' => $spinCost,
                'shortage' => $spinCost - $currentBalance,
            ], 403);
        }

        // ==========================================
        // 3️⃣ ОПРЕДЕЛЕНИЕ ПРИЗА (до транзакции)
        // ==========================================
        $prize = $this->determinePrize($settings['prizes'] ?? $this->getDefaultPrizes(), $settings['rarity_chances'] ?? []);

        // ==========================================
        // 4️⃣ АТОМАРНАЯ ТРАНЗАКЦИЯ: ставка + приз + попытка
        // ==========================================
        try {
            DB::transaction(function () use ($user, &$attempts, &$meta, $spinCost, $prize) {

                // 💸 Списываем ставку
                CashBackService::call()->removeCashBack(
                    $spinCost,
                    "🎴 Ставка в Карточную игру",
                    $user
                );

                // 💰 Начисляем приз
                CashBackService::call()->addCashBack(
                    $prize['value'],
                    "🏆 Выигрыш в Карточную игру: {$prize['title']}",
                    $user
                );

                // 📝 Записываем попытку
                $attempts[] = [
                    'played_at' => Carbon::now()->toIso8601String(),
                    'cost' => $spinCost,
                    'prize_id' => $prize['id'],
                    'prize_value' => $prize['value'],
                    'prize_rarity' => $prize['rarity'],
                ];

                if (count($attempts) > 100) {
                    $attempts = array_slice($attempts, -100);
                }

                $meta['card_game_attempts'] = $attempts;

                // 🏆 Сохраняем историю выигрышей
                $wins = $meta['card_game_wins'] ?? [];
                array_unshift($wins, [
                    'prize_id' => $prize['id'],
                    'title' => $prize['title'],
                    'description' => $prize['description'] ?? '',
                    'value' => $prize['value'],
                    'rarity' => $prize['rarity'],
                    'icon' => $prize['icon'] ?? 'fa-solid fa-gift',
                    'cost' => $spinCost,
                    'net_profit' => $prize['value'] - $spinCost,
                    'won_at' => Carbon::now()->toIso8601String(),
                ]);

                if (count($wins) > 50) {
                    $wins = array_slice($wins, 0, 50);
                }
                $meta['card_game_wins'] = $wins;

                $user->meta = $meta;
                $user->save();
            });

        } catch (\Throwable $e) {
            Log::error('[CardGame] Ошибка при обработке игры', [
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

        Log::info('[CardGame] Успешная игра', [
            'user_id' => $user->id,
            'cost' => $spinCost,
            'prize_value' => $prize['value'],
            'net_profit' => $prize['value'] - $spinCost,
            'new_balance' => $newBalance,
        ]);

        // 📢 Уведомления (только для редких и выше призов)
        if (in_array($prize['rarity'], ['epic', 'legendary'])) {
            $this->notifyAboutWin($user, $prize, $spinCost);
        }

        return response()->json([
            'success' => true,
            'prize' => $prize,
            'cost' => $spinCost,
            'net_profit' => $prize['value'] - $spinCost,
            'balance' => $newBalance,
            'attempts_left' => max(0, $attemptsPerPeriod - count($validAttempts) - 1),
        ]);
    }

    /**
     * 🎲 Определение приза с учётом редкости
     */
    protected function determinePrize(array $prizes, array $chances): array
    {
        if (empty($prizes)) {
            $prizes = $this->getDefaultPrizes();
        }

        // По умолчанию
        $defaultChances = [
            'common' => 70,
            'rare' => 20,
            'epic' => 8,
            'legendary' => 2,
        ];
        $chances = array_merge($defaultChances, $chances);

        // Определяем целевую редкость
        $rand = mt_rand(1, 100);
        $cumulative = 0;
        $targetRarity = 'common';

        foreach (['common', 'rare', 'epic', 'legendary'] as $rarity) {
            $cumulative += ($chances[$rarity] ?? 0);
            if ($rand <= $cumulative) {
                $targetRarity = $rarity;
                break;
            }
        }

        // Фильтруем призы нужной редкости
        $pool = array_filter($prizes, fn($p) => ($p['rarity'] ?? 'common') === $targetRarity);

        // Если пул пуст — берём любой доступный
        if (empty($pool)) {
            $pool = $prizes;
        }

        // Выбираем случайный приз
        $pool = array_values($pool);
        $prize = $pool[array_rand($pool)];

        // Гарантируем обязательные поля
        return [
            'id' => $prize['id'] ?? uniqid(),
            'title' => $prize['title'] ?? 'Бонус',
            'description' => $prize['description'] ?? '',
            'icon' => $prize['icon'] ?? 'fa-solid fa-gift',
            'value' => (int) ($prize['value'] ?? 0),
            'rarity' => $prize['rarity'] ?? 'common',
        ];
    }

    /**
     * 🎁 Дефолтные призы (если не настроено)
     */
    protected function getDefaultPrizes(): array
    {
        return [
            ['id' => 1, 'title' => 'Монетка', 'description' => 'Небольшой бонус', 'icon' => 'fa-solid fa-coins', 'value' => 100, 'rarity' => 'common'],
            ['id' => 2, 'title' => 'Удача', 'description' => 'Немного везения', 'icon' => 'fa-solid fa-clover', 'value' => 200, 'rarity' => 'common'],
            ['id' => 3, 'title' => 'Кристалл', 'description' => 'Редкий кристалл', 'icon' => 'fa-solid fa-gem', 'value' => 500, 'rarity' => 'rare'],
            ['id' => 4, 'title' => 'Сундук', 'description' => 'Сокровища!', 'icon' => 'fa-solid fa-box-open', 'value' => 750, 'rarity' => 'rare'],
            ['id' => 5, 'title' => 'Корона', 'description' => 'Королевский бонус', 'icon' => 'fa-solid fa-crown', 'value' => 1500, 'rarity' => 'epic'],
            ['id' => 6, 'title' => 'Дракон', 'description' => 'Эпический приз', 'icon' => 'fa-solid fa-dragon', 'value' => 2000, 'rarity' => 'epic'],
            ['id' => 7, 'title' => 'Джекпот', 'description' => 'ЛЕГЕНДАРНЫЙ ПРИЗ!', 'icon' => 'fa-solid fa-trophy', 'value' => 5000, 'rarity' => 'legendary'],
            ['id' => 8, 'title' => 'Единорог', 'description' => 'Невероятная удача', 'icon' => 'fa-solid fa-hat-wizard', 'value' => 10000, 'rarity' => 'legendary'],
        ];
    }

    // ==========================================
    // 📢 УВЕДОМЛЕНИЯ
    // ==========================================

    protected function notifyAboutWin($user, array $prize, float $spinCost): void
    {
        try {
            $tenant = app('tenant');
            $phone = $user->phone ?? 'не указан';
            $userName = $user->name ?? 'Не указано';

            $userMessage = $this->buildUserWinMessage($prize, $spinCost);
            $adminMessage = $this->buildAdminWinMessage($user, $prize, $spinCost, $phone, $userName);

            // 📱 В чат пользователя
            $dialog = $this->getOrCreateCardGameDialog($user);
            if ($dialog) {
                try {
                    MessageService::call()->sendMessage([
                        'message' => $userMessage,
                        'dialog_id' => $dialog->id,
                        'recipients' => ['client' => true],
                        'meta' => [
                            'is_system' => true,
                            'event_type' => 'card_game_win',
                            'prize_id' => $prize['id'],
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[CardGame] Ошибка отправки в чат', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 📣 В Telegram
            $notifyPartners = $tenant->settings['card_game']['notify_partners'] ?? true;
            if ($notifyPartners) {
                try {
                    MessageService::call()->sendMessage([
                        'message' => $adminMessage,
                        'title' => '🎴 Крупный выигрыш в Карточной игре',
                        'recipients' => ['partners' => true, 'telegram' => true],
                        'meta' => [
                            'event_type' => 'card_game_win',
                            'customer_name' => $userName,
                            'customer_phone' => $phone,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[CardGame] Ошибка отправки в Telegram', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Throwable $e) {
            Log::error('[CardGame] Ошибка уведомлений', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getOrCreateCardGameDialog($user): ?TenantDialog
    {
        $tenant = app('tenant');

        $dialog = TenantDialog::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->where('type', 'card_game')
            ->first();

        if ($dialog) return $dialog;

        try {
            return TenantDialog::create([
                'tenant_id' => $tenant->id,
                'tenant_user_id' => $user->id,
                'type' => 'card_game',
                'title' => '🎴 Карточная игра',
                'is_closed' => false,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildUserWinMessage(array $prize, float $spinCost): string
    {
        $netProfit = $prize['value'] - $spinCost;
        $profitText = $netProfit >= 0 ? "+{$netProfit}" : (string) $netProfit;

        return <<<HTML
🎴 <b>Результат карточной игры</b>

🎁 Ваш приз: <b>{$prize['title']}</b>
💰 Начислено: <b>+{$prize['value']} бонусов</b>

💳 Ставка: {$spinCost}₽
📊 Итого: <b>{$profitText}</b>

<i>Спасибо за игру! Возвращайтесь за новыми победами.</i>
HTML;
    }

    protected function buildAdminWinMessage($user, array $prize, float $spinCost, string $phone, string $userName): string
    {
        $tenant = app('tenant');
        $baseUrl = request()->getSchemeAndHttpHost();
        $profileUrl = "{$baseUrl}/pwa#/admin/users/{$user->id}";

        $rarityEmoji = match ($prize['rarity']) {
            'epic' => '💜',
            'legendary' => '🏆',
            default => '🎴',
        };

        $time = $user->created_at?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i');
        return <<<HTML
{$rarityEmoji} <b>Крупный выигрыш в Карточной игре!</b>

👤 <b>Клиент:</b> {$userName}
📱 <b>Телефон:</b> {$phone}
🆔 <b>ID:</b> #{$user->id}

🎁 <b>Приз:</b> {$prize['title']} ({$prize['rarity']})
💰 <b>Начислено:</b> +{$prize['value']} бонусов
💳 <b>Ставка:</b> {$spinCost}₽
🕐 <b>Время:</b> {$time}

🏢 <b>Тенант:</b> {$tenant->name}

<a href="{$profileUrl}">👁 Открыть профиль</a>
HTML;
    }
}
