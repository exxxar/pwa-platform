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

class ScratchCardController extends Controller
{
    /**
     * Стоимость одной игры (кэшбэк)
     */
    private const DEFAULT_SPIN_COST = 500;

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
        $settings = $tenant->settings['scratch_card'] ?? [];

        $prizes = $settings['prizes'] ?? $this->getDefaultPrizes();

        return response()->json([
            'success' => true,
            'scratch_card' => [
                'can_play' => $settings['can_play'] ?? true,
                'interval' => $settings['interval'] ?? 1,
                'attempts_per_period' => $settings['attempts_per_period'] ?? 1,
                'spin_cost' => (float) ($settings['spin_cost'] ?? self::DEFAULT_SPIN_COST),
                'reveal_threshold' => $settings['reveal_threshold'] ?? 55,
                'rules' => $settings['rules'] ?? '',
                'rarity_chances' => $settings['rarity_chances'] ?? [
                        'common' => 60,
                        'rare' => 25,
                        'epic' => 12,
                        'legendary' => 3,
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
        $settings = $tenant->settings['scratch_card'] ?? [];
        $intervalDays = $settings['interval'] ?? 1;
        $attemptsPerPeriod = $settings['attempts_per_period'] ?? 1;

        $meta = $user->meta ?? [];
        $attempts = $meta['scratch_card_attempts'] ?? [];
        $cutoffDate = Carbon::now()->subDays($intervalDays);

        $validAttempts = collect($attempts)->filter(function ($attempt) use ($cutoffDate) {
            return Carbon::parse($attempt['played_at'])->gte($cutoffDate);
        })->values();

        $attemptsUsed = $validAttempts->count();
        $attemptsLeft = max(0, $attemptsPerPeriod - $attemptsUsed);
        $lastPlayDate = $validAttempts->last()['played_at'] ?? null;
        $pendingAttempt = collect($validAttempts)->firstWhere('prize_confirmed', false);

        return response()->json([
            'success' => true,
            'attempts_left' => $attemptsLeft,
            'attempts_used' => $attemptsUsed,
            'attempts_per_period' => $attemptsPerPeriod,
            'balance' => (float) $user->cashback_balance,
            'last_play_date' => $lastPlayDate,
            'game_finished' => $attemptsLeft <= 0,
            'wins_history' => $meta['scratch_card_wins'] ?? [],
            'pending_game_token' => $pendingAttempt['token'] ?? null,
            'pending_prize' => $pendingAttempt ? $this->findPrizeById($pendingAttempt['prize_id'], $pendingAttempt) : null,

        ]);
    }

    /**
     * 🎴 Начало игры: списание ставки + определение приза
     *
     * Вызывается при первом касании canvas (чтобы не списывать зря)
     */
    public function startGame(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['scratch_card'] ?? [];
        $intervalDays = $settings['interval'] ?? 1;
        $attemptsPerPeriod = $settings['attempts_per_period'] ?? 1;
        $spinCost = (float) ($settings['spin_cost'] ?? self::DEFAULT_SPIN_COST);

        $meta = $user->meta ?? [];
        $attempts = $meta['scratch_card_attempts'] ?? [];
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
        $prize = $this->determinePrize(
            $settings['prizes'] ?? $this->getDefaultPrizes(),
            $settings['rarity_chances'] ?? []
        );

        // ==========================================
        // 4️⃣ АТОМАРНАЯ ТРАНЗАКЦИЯ
        // ==========================================
        $gameToken = null;

        try {
            DB::transaction(function () use ($user, &$attempts, &$meta, $spinCost, $prize, &$gameToken) {

                // 💸 Списываем ставку
                CashBackService::call()->removeCashBack(
                    $spinCost,
                    "🎴 Ставка в Скретч-карту",
                    $user
                );

                // 🔑 Генерируем токен для подтверждения получения приза
                $gameToken = bin2hex(random_bytes(16));

                // 📝 Записываем попытку
                $attempts[] = [
                    'played_at' => Carbon::now()->toIso8601String(),
                    'cost' => $spinCost,
                    'token' => $gameToken,
                    'prize_id' => $prize['id'],
                    'prize_value' => $prize['value'],
                    'prize_rarity' => $prize['rarity'],
                    'prize_type' => $prize['type'],
                    'prize_confirmed' => false, // пока не подтверждено
                ];

                if (count($attempts) > 100) {
                    $attempts = array_slice($attempts, -100);
                }

                $meta['scratch_card_attempts'] = $attempts;
                $user->meta = $meta;
                $user->save();
            });

        } catch (\Throwable $e) {
            Log::error('[ScratchCard] Ошибка при старте игры', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
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

        Log::info('[ScratchCard] Игра начата', [
            'user_id' => $user->id,
            'cost' => $spinCost,
            'prize_type' => $prize['type'],
            'prize_value' => $prize['value'],
            'token' => $gameToken,
        ]);

        return response()->json([
            'success' => true,
            'prize' => $prize,
            'token' => $gameToken,
            'cost' => $spinCost,
            'balance' => $newBalance,
            'attempts_left' => max(0, $attemptsPerPeriod - count($validAttempts) - 1),
        ]);
    }

    /**
     * ✅ Подтверждение получения приза (после стирания карты)
     *
     * Вызывается, когда пользователь стёр достаточно слоя
     */
    public function confirmPrize(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $token = $validated['token'];
        $meta = $user->meta ?? [];
        $attempts = $meta['scratch_card_attempts'] ?? [];

        // Ищем попытку по токену
        $attemptIndex = null;
        foreach ($attempts as $idx => $attempt) {
            if (($attempt['token'] ?? null) === $token) {
                $attemptIndex = $idx;
                break;
            }
        }

        if ($attemptIndex === null) {
            return response()->json([
                'success' => false,
                'message' => 'Игра не найдена или уже подтверждена',
            ], 404);
        }

        // Проверка: уже подтверждено?
        if (!empty($attempts[$attemptIndex]['prize_confirmed'])) {
            return response()->json([
                'success' => true,
                'message' => 'Приз уже был начислен',
                'already_confirmed' => true,
            ]);
        }

        // Получаем данные приза
        $prize = $this->findPrizeById(
            $attempts[$attemptIndex]['prize_id'],
            $attempts[$attemptIndex]
        );

        // ==========================================
        // НАЧИСЛЕНИЕ ПРИЗА
        // ==========================================
        try {
            DB::transaction(function () use ($user, &$attempts, &$meta, $attemptIndex, $prize) {

                // 💰 Начисляем приз в зависимости от типа
                if ($prize['type'] === 'bonus') {
                    CashBackService::call()->addCashBack(
                        $prize['value'],
                        "🏆 Выигрыш в Скретч-карту: {$prize['title']}",
                        $user
                    );
                }

                // Для других типов (product, discounts) — просто логируем
                // Их обработка требует интеграции с корзиной/заказами

                // 🏆 Сохраняем в историю выигрышей
                $wins = $meta['scratch_card_wins'] ?? [];
                array_unshift($wins, [
                    'prize_id' => $prize['id'],
                    'title' => $prize['title'],
                    'description' => $prize['description'] ?? '',
                    'value' => $prize['value'],
                    'type' => $prize['type'],
                    'rarity' => $prize['rarity'],
                    'icon' => $prize['icon'] ?? 'fa-solid fa-gift',
                    'cost' => $attempts[$attemptIndex]['cost'] ?? 0,
                    'net_profit' => ($prize['type'] === 'bonus' ? $prize['value'] : 0) - ($attempts[$attemptIndex]['cost'] ?? 0),
                    'won_at' => Carbon::now()->toIso8601String(),
                ]);

                if (count($wins) > 50) {
                    $wins = array_slice($wins, 0, 50);
                }
                $meta['scratch_card_wins'] = $wins;

                // 📝 Помечаем попытку как подтверждённую
                $attempts[$attemptIndex]['prize_confirmed'] = true;
                $attempts[$attemptIndex]['confirmed_at'] = Carbon::now()->toIso8601String();

                $meta['scratch_card_attempts'] = $attempts;
                $user->meta = $meta;
                $user->save();
            });

        } catch (\Throwable $e) {
            Log::error('[ScratchCard] Ошибка подтверждения приза', [
                'user_id' => $user->id,
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось начислить приз. Свяжитесь с поддержкой.',
            ], 500);
        }

        $user->refresh();
        $newBalance = (float) $user->cashback_balance;

        // 📢 Уведомления для редких и выше призов
        if (in_array($prize['rarity'], ['epic', 'legendary'])) {
            $this->notifyAboutWin($user, $prize);
        }

        return response()->json([
            'success' => true,
            'message' => 'Приз успешно начислен!',
            'prize' => $prize,
            'balance' => $newBalance,
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

        $defaultChances = [
            'common' => 60,
            'rare' => 25,
            'epic' => 12,
            'legendary' => 3,
        ];
        $chances = array_merge($defaultChances, $chances);

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
            'value' => (int) ($prize['value'] ?? 0),
            'rarity' => $prize['rarity'] ?? 'common',
            'productId' => $prize['productId'] ?? null,
            'productName' => $prize['productName'] ?? null,
            'isPercent' => $prize['isPercent'] ?? false,
            'minOrderAmount' => $prize['minOrderAmount'] ?? null,
        ];
    }

    /**
     * 🔍 Найти приз по ID
     */
    protected function findPrizeById($prizeId, array $attemptData): array
    {
        $tenant = app('tenant');
        $settings = $tenant->settings['scratch_card'] ?? [];
        $prizes = $settings['prizes'] ?? $this->getDefaultPrizes();

        foreach ($prizes as $prize) {
            if (($prize['id'] ?? null) == $prizeId) {
                return array_merge($prize, [
                    'id' => $prize['id'],
                    'type' => $prize['type'] ?? 'bonus',
                    'title' => $prize['title'] ?? 'Бонус',
                    'description' => $prize['description'] ?? '',
                    'icon' => $prize['icon'] ?? 'fa-solid fa-gift',
                    'value' => (int) ($prize['value'] ?? 0),
                    'rarity' => $prize['rarity'] ?? 'common',
                ]);
            }
        }

        // Fallback на данные из попытки
        return [
            'id' => $prizeId,
            'type' => $attemptData['prize_type'] ?? 'bonus',
            'title' => 'Бонус',
            'description' => '',
            'icon' => 'fa-solid fa-gift',
            'value' => (int) ($attemptData['prize_value'] ?? 0),
            'rarity' => $attemptData['prize_rarity'] ?? 'common',
        ];
    }

    /**
     * 🎁 Дефолтные призы
     */
    protected function getDefaultPrizes(): array
    {
        return [
            // Обычные
            ['id' => 1, 'type' => 'bonus', 'title' => '50 бонусов', 'description' => 'Небольшой бонус к вашему балансу', 'icon' => 'fa-solid fa-coins', 'value' => 50, 'rarity' => 'common'],
            ['id' => 2, 'type' => 'bonus', 'title' => '100 бонусов', 'description' => 'Приятное пополнение', 'icon' => 'fa-solid fa-coins', 'value' => 100, 'rarity' => 'common'],
            ['id' => 3, 'type' => 'bonus', 'title' => '150 бонусов', 'description' => 'Маленький бонус', 'icon' => 'fa-solid fa-coins', 'value' => 150, 'rarity' => 'common'],
            ['id' => 4, 'type' => 'delivery_discount', 'title' => 'Скидка 100₽ на доставку', 'description' => 'Скидка на следующую доставку', 'icon' => 'fa-solid fa-truck', 'value' => 100, 'isPercent' => false, 'rarity' => 'common'],
            ['id' => 5, 'type' => 'bonus', 'title' => '200 бонусов', 'description' => 'Хороший бонус', 'icon' => 'fa-solid fa-gem', 'value' => 200, 'rarity' => 'common'],

            // Редкие
            ['id' => 6, 'type' => 'bonus', 'title' => '350 бонусов', 'description' => 'Ценный приз', 'icon' => 'fa-solid fa-gem', 'value' => 350, 'rarity' => 'rare'],
            ['id' => 7, 'type' => 'product_discount', 'title' => 'Скидка 15% на пиццу', 'description' => 'Скидка на любую пиццу', 'icon' => 'fa-solid fa-percent', 'value' => 15, 'productId' => 101, 'productName' => 'пиццу', 'rarity' => 'rare'],
            ['id' => 8, 'type' => 'delivery_discount', 'title' => 'Бесплатная доставка', 'description' => 'Следующая доставка за наш счёт', 'icon' => 'fa-solid fa-truck-fast', 'value' => 300, 'isPercent' => false, 'rarity' => 'rare'],
            ['id' => 9, 'type' => 'bonus', 'title' => '450 бонусов', 'description' => 'Редкий бонус', 'icon' => 'fa-solid fa-gem', 'value' => 450, 'rarity' => 'rare'],

            // Эпические
            ['id' => 10, 'type' => 'bonus', 'title' => '1000 бонусов', 'description' => 'Отличный бонус!', 'icon' => 'fa-solid fa-gem', 'value' => 1000, 'rarity' => 'epic'],
            ['id' => 11, 'type' => 'product', 'title' => 'Пицца Маргарита', 'description' => 'Бесплатная пицца', 'icon' => 'fa-solid fa-pizza-slice', 'value' => 600, 'productId' => 102, 'productName' => 'Пицца Маргарита', 'rarity' => 'epic'],
            ['id' => 12, 'type' => 'order_discount', 'title' => 'Скидка 20% на заказ', 'description' => 'Скидка на заказ от 1500₽', 'icon' => 'fa-solid fa-receipt', 'value' => 20, 'minOrderAmount' => 1500, 'rarity' => 'epic'],
            ['id' => 13, 'type' => 'bonus', 'title' => '1500 бонусов', 'description' => 'Эпический бонус!', 'icon' => 'fa-solid fa-gem', 'value' => 1500, 'rarity' => 'epic'],

            // Легендарные
            ['id' => 14, 'type' => 'product', 'title' => 'Сет "Праздничный"', 'description' => 'Большой сет бесплатно', 'icon' => 'fa-solid fa-gift', 'value' => 2500, 'productId' => 201, 'productName' => 'Сет "Праздничный"', 'rarity' => 'legendary'],
            ['id' => 15, 'type' => 'order_discount', 'title' => 'Скидка 50% на заказ', 'description' => 'Огромная скидка на заказ от 3000₽', 'icon' => 'fa-solid fa-crown', 'value' => 50, 'minOrderAmount' => 3000, 'rarity' => 'legendary'],
            ['id' => 16, 'type' => 'bonus', 'title' => '5000 бонусов', 'description' => 'ЛЕГЕНДАРНЫЙ ДЖЕКПОТ!', 'icon' => 'fa-solid fa-trophy', 'value' => 5000, 'rarity' => 'legendary'],
        ];
    }

    // ==========================================
    // 📢 УВЕДОМЛЕНИЯ
    // ==========================================

    protected function notifyAboutWin($user, array $prize): void
    {
        try {
            $tenant = app('tenant');
            $phone = $user->phone ?? 'не указан';
            $userName = $user->name ?? 'Не указано';

            $userMessage = $this->buildUserWinMessage($prize);
            $adminMessage = $this->buildAdminWinMessage($user, $prize, $phone, $userName);

            // 📱 В чат пользователя
            $dialog = $this->getOrCreateScratchDialog($user);
            if ($dialog) {
                try {
                    MessageService::call()->sendMessage([
                        'message' => $userMessage,
                        'dialog_id' => $dialog->id,
                        'recipients' => ['client' => true],
                        'meta' => [
                            'is_system' => true,
                            'event_type' => 'scratch_card_win',
                            'prize_id' => $prize['id'],
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[ScratchCard] Ошибка отправки в чат', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 📣 В Telegram
            $notifyPartners = $tenant->settings['scratch_card']['notify_partners'] ?? true;
            if ($notifyPartners) {
                try {
                    MessageService::call()->sendMessage([
                        'message' => $adminMessage,
                        'title' => '🎴 Крупный выигрыш в Скретч-карте',
                        'recipients' => ['partners' => true, 'telegram' => true],
                        'meta' => [
                            'event_type' => 'scratch_card_win',
                            'customer_name' => $userName,
                            'customer_phone' => $phone,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[ScratchCard] Ошибка отправки в Telegram', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Throwable $e) {
            Log::error('[ScratchCard] Ошибка уведомлений', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getOrCreateScratchDialog($user): ?TenantDialog
    {
        $tenant = app('tenant');

        $dialog = TenantDialog::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->where('type', 'scratch_card')
            ->first();

        if ($dialog) return $dialog;

        try {
            return TenantDialog::create([
                'tenant_id' => $tenant->id,
                'tenant_user_id' => $user->id,
                'type' => 'scratch_card',
                'title' => '🎴 Скретч-карта',
                'is_closed' => false,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildUserWinMessage(array $prize): string
    {
        $typeEmoji = match ($prize['type']) {
            'bonus' => '💰',
            'product' => '🎁',
            'product_discount', 'order_discount', 'delivery_discount' => '🏷️',
            default => '🎉',
        };

        return <<<HTML
🎴 <b>Поздравляем с выигрышем!</b>

{$typeEmoji} Ваш приз: <b>{$prize['title']}</b>

<i>Спасибо за игру! Возвращайтесь за новыми победами.</i>
HTML;
    }

    protected function buildAdminWinMessage($user, array $prize, string $phone, string $userName): string
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
{$rarityEmoji} <b>Крупный выигрыш в Скретч-карте!</b>

👤 <b>Клиент:</b> {$userName}
📱 <b>Телефон:</b> {$phone}
🆔 <b>ID:</b> #{$user->id}

🎁 <b>Приз:</b> {$prize['title']} ({$prize['type']}, {$prize['rarity']})
💰 <b>Ценность:</b> {$prize['value']}
🕐 <b>Время:</b> {$time}

🏢 <b>Тенант:</b> {$tenant->name}

<a href="{$profileUrl}">👁 Открыть профиль</a>
HTML;
    }
}
