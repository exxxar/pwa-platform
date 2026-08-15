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

class PrizeCardController extends Controller
{
    /**
     * Стоимость одной игры (кэшбэк)
     */
    private const GAME_COST = 500;

    /**
     * Таблица призов с типами и редкостью
     */
    private const PRIZES = [
        // Обычные (70%)
        ['id' => 1, 'type' => 'bonus', 'title' => '50 бонусов', 'description' => 'Небольшой бонус к вашему балансу', 'icon' => 'fa-solid fa-coins', 'value' => 50, 'rarity' => 'common'],
        ['id' => 2, 'type' => 'bonus', 'title' => '100 бонусов', 'description' => 'Приятное пополнение баланса', 'icon' => 'fa-solid fa-coins', 'value' => 100, 'rarity' => 'common'],
        ['id' => 3, 'type' => 'bonus', 'title' => '150 бонусов', 'description' => 'Хороший бонус', 'icon' => 'fa-solid fa-coins', 'value' => 150, 'rarity' => 'common'],
        ['id' => 4, 'type' => 'delivery_discount', 'title' => 'Скидка 100₽ на доставку', 'description' => 'Скидка на следующую доставку', 'icon' => 'fa-solid fa-truck', 'value' => 100, 'isPercent' => false, 'rarity' => 'common'],

        // Редкие (20%)
        ['id' => 5, 'type' => 'bonus', 'title' => '350 бонусов', 'description' => 'Ценный бонус', 'icon' => 'fa-solid fa-gem', 'value' => 350, 'rarity' => 'rare'],
        ['id' => 6, 'type' => 'bonus', 'title' => '450 бонусов', 'description' => 'Редкий бонус', 'icon' => 'fa-solid fa-gem', 'value' => 450, 'rarity' => 'rare'],
        ['id' => 7, 'type' => 'product_discount', 'title' => 'Скидка 15% на пиццу', 'description' => 'Скидка на любую пиццу', 'icon' => 'fa-solid fa-percent', 'value' => 15, 'productId' => 101, 'productName' => 'пиццу', 'rarity' => 'rare'],
        ['id' => 8, 'type' => 'delivery_discount', 'title' => 'Бесплатная доставка', 'description' => 'Следующая доставка за наш счёт', 'icon' => 'fa-solid fa-truck-fast', 'value' => 300, 'isPercent' => false, 'rarity' => 'rare'],

        // Эпические (8%)
        ['id' => 9, 'type' => 'bonus', 'title' => '1000 бонусов', 'description' => 'Отличный бонус!', 'icon' => 'fa-solid fa-gem', 'value' => 1000, 'rarity' => 'epic'],
        ['id' => 10, 'type' => 'bonus', 'title' => '1500 бонусов', 'description' => 'Эпический бонус!', 'icon' => 'fa-solid fa-gem', 'value' => 1500, 'rarity' => 'epic'],
        ['id' => 11, 'type' => 'product', 'title' => 'Пицца Маргарита', 'description' => 'Бесплатная пицца Маргарита', 'icon' => 'fa-solid fa-pizza-slice', 'value' => 600, 'productId' => 102, 'productName' => 'Пицца Маргарита', 'rarity' => 'epic'],
        ['id' => 12, 'type' => 'order_discount', 'title' => 'Скидка 20% на заказ', 'description' => 'Скидка на заказ от 1500₽', 'icon' => 'fa-solid fa-receipt', 'value' => 20, 'minOrderAmount' => 1500, 'rarity' => 'epic'],

        // Легендарные (2%)
        ['id' => 13, 'type' => 'product', 'title' => 'Сет "Праздничный"', 'description' => 'Большой праздничный сет бесплатно', 'icon' => 'fa-solid fa-gift', 'value' => 2500, 'productId' => 201, 'productName' => 'Сет "Праздничный"', 'rarity' => 'legendary'],
        ['id' => 14, 'type' => 'order_discount', 'title' => 'Скидка 50% на заказ', 'description' => 'Огромная скидка на заказ от 3000₽', 'icon' => 'fa-solid fa-crown', 'value' => 50, 'minOrderAmount' => 3000, 'rarity' => 'legendary'],
        ['id' => 15, 'type' => 'bonus', 'title' => '5000 бонусов', 'description' => 'ЛЕГЕНДАРНЫЙ ДЖЕКПОТ!', 'icon' => 'fa-solid fa-trophy', 'value' => 5000, 'rarity' => 'legendary'],
    ];

    /**
     * Шансы выпадения редкости
     */
    private const RARITY_CHANCES = [
        'common' => 70,
        'rare' => 20,
        'epic' => 8,
        'legendary' => 2,
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
        $settings = $tenant->settings['prize_card'] ?? [];

        return response()->json([
            'success' => true,
            'prize_card' => [
                'can_play' => $settings['can_play'] ?? true,
                'interval' => (int)($settings['interval'] ?? 1),
                'attempts_per_period' => (int)($settings['attempts_per_period'] ?? 1),
                'spin_cost' => (float)($settings['spin_cost'] ?? self::GAME_COST),
                'grid_columns' => (int)($settings['grid_columns'] ?? 4),
                'grid_rows' => (int)($settings['grid_rows'] ?? 3),
                'rules' => $settings['rules'] ?? '',
                'prizes' => self::PRIZES,
                'rarity_chances' => $settings['rarity_chances'] ?? self::RARITY_CHANCES,
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
        $settings = $tenant->settings['prize_card'] ?? [];
        $intervalDays = (int)($settings['interval'] ?? 1);
        $attemptsPerPeriod = (int)($settings['attempts_per_period'] ?? 1);

        $meta = $user->meta ?? [];
        $attempts = $meta['prize_card_attempts'] ?? [];
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
            'balance' => (float)$user->cashback_balance,
            'game_finished' => $attemptsLeft <= 0,
            'wins_history' => array_slice($meta['prize_card_wins'] ?? [], 0, 50),
        ]);
    }

    /**
     * 🎴 Сыграть: ставка + определение приза + начисление
     */
    public function play(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['prize_card'] ?? [];
        $intervalDays = (int)($settings['interval'] ?? 1);
        $attemptsPerPeriod = (int)($settings['attempts_per_period'] ?? 1);
        $spinCost = (float)($settings['spin_cost'] ?? self::GAME_COST);

        $meta = $user->meta ?? [];
        $attempts = $meta['prize_card_attempts'] ?? [];
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
        $currentBalance = (float)$user->cashback_balance;

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
        // 3️⃣ 🎲 ОПРЕДЕЛЕНИЕ ПРИЗА НА СЕРВЕРЕ
        // ==========================================
        $prize = $this->determinePrize(
            $settings['prizes'] ?? self::PRIZES,
            $settings['rarity_chances'] ?? self::RARITY_CHANCES
        );

        // ==========================================
        // 4️⃣ АТОМАРНАЯ ТРАНЗАКЦИЯ
        // ==========================================
        try {
            DB::transaction(function () use ($user, &$meta, &$attempts, $spinCost, $prize) {

                // 💸 Списываем ставку
                CashBackService::call()->removeCashBack(
                    $spinCost,
                    "🎴 Ставка в Карточную игру",
                    $user
                );

                // 💰 Если приз — бонусы, начисляем сразу
                if ($prize['type'] === 'bonus') {
                    CashBackService::call()->addCashBack(
                        $prize['value'],
                        "🏆 Выигрыш в Карточную игру: {$prize['title']}",
                        $user
                    );
                } else {
                    // Для других типов призов — сохраняем как "неактивированный"
                    // Пользователь сможет активировать позже
                    $unclaimedPrizes = $meta['unclaimed_prizes'] ?? [];
                    $unclaimedPrizes[] = [
                        'id' => uniqid('prize_'),
                        'source' => 'prize_card',
                        'prize' => $prize,
                        'won_at' => Carbon::now()->toIso8601String(),
                        'claimed' => false,
                    ];

                    // Ограничиваем до 20 неактивированных
                    if (count($unclaimedPrizes) > 20) {
                        $unclaimedPrizes = array_slice($unclaimedPrizes, -20);
                    }

                    $meta['unclaimed_prizes'] = $unclaimedPrizes;
                }

                // 📝 Записываем попытку
                $attempts[] = [
                    'played_at' => Carbon::now()->toIso8601String(),
                    'cost' => $spinCost,
                    'prize_id' => $prize['id'],
                    'prize_type' => $prize['type'],
                    'prize_value' => $prize['value'],
                    'prize_rarity' => $prize['rarity'],
                ];

                if (count($attempts) > 100) {
                    $attempts = array_slice($attempts, -100);
                }

                $meta['prize_card_attempts'] = $attempts;

                // 🏆 Сохраняем в историю выигрышей
                $wins = $meta['prize_card_wins'] ?? [];
                array_unshift($wins, [
                    'id' => uniqid('win_'),
                    'prize_id' => $prize['id'],
                    'title' => $prize['title'],
                    'description' => $prize['description'] ?? '',
                    'type' => $prize['type'],
                    'value' => $prize['value'],
                    'rarity' => $prize['rarity'],
                    'icon' => $prize['icon'] ?? 'fa-solid fa-gift',
                    'cost' => $spinCost,
                    'net_profit' => ($prize['type'] === 'bonus' ? $prize['value'] : 0) - $spinCost,
                    'won_at' => Carbon::now()->toIso8601String(),
                ]);

                if (count($wins) > 50) {
                    $wins = array_slice($wins, 0, 50);
                }
                $meta['prize_card_wins'] = $wins;

                $user->meta = $meta;
                $user->save();
            });

        } catch (\Throwable $e) {
            Log::error('[PrizeCard] Ошибка игры', [
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
        $newBalance = (float)$user->cashback_balance;

        Log::info('[PrizeCard] Игра завершена', [
            'user_id' => $user->id,
            'cost' => $spinCost,
            'prize_type' => $prize['type'],
            'prize_value' => $prize['value'],
            'prize_rarity' => $prize['rarity'],
            'net_profit' => ($prize['type'] === 'bonus' ? $prize['value'] : 0) - $spinCost,
        ]);

        // 📢 Уведомления для редких и выше призов
        if (in_array($prize['rarity'], ['epic', 'legendary'])) {
            $this->notifyAboutWin($user, $prize, $spinCost);
        }

        return response()->json([
            'success' => true,
            'prize' => $prize,
            'cost' => $spinCost,
            'net_profit' => ($prize['type'] === 'bonus' ? $prize['value'] : 0) - $spinCost,
            'balance' => $newBalance,
            'attempts_left' => max(0, $attemptsPerPeriod - count($validAttempts) - 1),
        ]);
    }

    // ==========================================
    // 🎲 ГЕНЕРАЦИЯ ПРИЗА
    // ==========================================

    protected function determinePrize(array $prizes, array $chances): array
    {
        if (empty($prizes)) {
            $prizes = self::PRIZES;
        }

        $defaultChances = self::RARITY_CHANCES;
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

        if (empty($pool)) {
            $pool = $prizes;
        }

        $pool = array_values($pool);
        $prize = $pool[array_rand($pool)];

        return [
            'id' => $prize['id'] ?? uniqid(),
            'type' => $prize['type'] ?? 'bonus',
            'title' => $prize['title'] ?? 'Бонус',
            'description' => $prize['description'] ?? '',
            'icon' => $prize['icon'] ?? 'fa-solid fa-gift',
            'value' => (int)($prize['value'] ?? 0),
            'rarity' => $prize['rarity'] ?? 'common',
            'productId' => $prize['productId'] ?? null,
            'productName' => $prize['productName'] ?? null,
            'isPercent' => $prize['isPercent'] ?? false,
            'minOrderAmount' => $prize['minOrderAmount'] ?? null,
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
            $dialog = $this->getOrCreatePrizeCardDialog($user);
            if ($dialog) {
                try {
                    MessageService::call()->sendMessage([
                        'message' => $userMessage,
                        'dialog_id' => $dialog->id,
                        'recipients' => ['client' => true],
                        'meta' => [
                            'is_system' => true,
                            'event_type' => 'prize_card_win',
                            'prize_id' => $prize['id'],
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[PrizeCard] Ошибка отправки в чат', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 📣 В Telegram
            $notifyPartners = $tenant->settings['prize_card']['notify_partners'] ?? true;
            if ($notifyPartners) {
                try {
                    MessageService::call()->sendMessage([
                        'message' => $adminMessage,
                        'title' => '🎴 Крупный выигрыш в Карточной игре',
                        'recipients' => ['partners' => true, 'telegram' => true],
                        'meta' => [
                            'event_type' => 'prize_card_big_win',
                            'customer_name' => $userName,
                            'customer_phone' => $phone,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[PrizeCard] Ошибка отправки в Telegram', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Throwable $e) {
            Log::error('[PrizeCard] Ошибка уведомлений', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getOrCreatePrizeCardDialog($user): ?TenantDialog
    {
        $tenant = app('tenant');

        $dialog = TenantDialog::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->where('type', 'prize_card')
            ->first();

        if ($dialog) return $dialog;

        try {
            return TenantDialog::create([
                'tenant_id' => $tenant->id,
                'tenant_user_id' => $user->id,
                'type' => 'prize_card',
                'title' => '🎴 Карточная игра',
                'is_closed' => false,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildUserWinMessage(array $prize, float $spinCost): string
    {
        $typeEmoji = match ($prize['type']) {
            'bonus' => '💰',
            'product' => '🎁',
            'product_discount', 'order_discount', 'delivery_discount' => '🏷️',
            default => '🎉',
        };

        $netProfit = ($prize['type'] === 'bonus' ? $prize['value'] : 0) - $spinCost;
        $profitText = $netProfit >= 0 ? "+{$netProfit}" : (string)$netProfit;

        $res = $prize['type'] !== 'bonus' ? '<i>Приз сохранён в вашем профиле. Свяжитесь с нами для активации.</i>' : '';
        return <<<HTML
        🎴 <b>Результат карточной игры</b>

        {$typeEmoji} Ваш приз: <b>{$prize['title']}</b>

        💳 Ставка: {$spinCost}₽
        📊 Чистый результат: <b>{$profitText}</b>

        {$res}

        <i>Спасибо за игру!</i>
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
            'rare' => '💎',
            default => '🎴',
        };

        $time = $user->created_at?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i');

        return <<<HTML
        {$rarityEmoji} <b>Крупный выигрыш в Карточной игре!</b>

        👤 <b>Клиент:</b> {$userName}
        📱 <b>Телефон:</b> {$phone}
        🆔 <b>ID:</b> #{$user->id}

        🎁 <b>Приз:</b> {$prize['title']}
        📦 <b>Тип:</b> {$prize['type']} ({$prize['rarity']})
        💰 <b>Ценность:</b> {$prize['value']}
        💳 <b>Ставка:</b> {$spinCost}₽
        🕐 <b>Время:</b> {$time}

        🏢 <b>Тенант:</b> {$tenant->name}

        <a href="{$profileUrl}">👁 Открыть профиль</a>
        HTML;
    }
}
