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
use Illuminate\Support\Str;

class DailyBonusController extends Controller
{
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
        $settings = $tenant->settings['daily_bonus'] ?? [];

        return response()->json([
            'success' => true,
            'daily_bonus' => [
                'can_play' => $settings['can_play'] ?? true,
                'streak_days' => (int) ($settings['streak_days'] ?? 7),
                'streak_reset_days' => (int) ($settings['streak_reset_days'] ?? 1),
                'title' => $settings['title'] ?? 'Ежедневный бонус',
                'subtitle' => $settings['subtitle'] ?? 'Заходи каждый день и открывай сундучки!',
                'rules' => $settings['rules'] ?? '',
                'type_colors' => $settings['type_colors'] ?? [
                        'bonus' => '#ffd700',
                        'discount' => '#ff6b6b',
                        'product' => '#4facfe',
                        'jackpot' => '#ffd700',
                    ],
                'rewards' => $settings['rewards'] ?? $this->getDefaultRewards(),
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

        $meta = $user->meta ?? [];
        $bonus = $meta['daily_bonus'] ?? [];

        $currentStreak = (int) ($bonus['current_streak'] ?? 0);
        $bestStreak = (int) ($bonus['best_streak'] ?? 0);
        $lastOpenDate = $bonus['last_open_date'] ?? null;
        $lastOpenTimezone = $bonus['last_open_timezone'] ?? 'UTC';

        // 🕐 Проверяем, не пропустил ли пользователь дни (с учётом часового пояса!)
        $userTimezone = $request->input('timezone', 'UTC');
        $today = Carbon::now($userTimezone)->format('Y-m-d');

        if ($lastOpenDate) {
            $lastDate = Carbon::parse($lastOpenDate, $lastOpenTimezone);
            $todayDate = Carbon::parse($today, $userTimezone);
            $diffDays = $todayDate->diffInDays($lastDate, false);

            // Получаем настройки сброса серии
            $tenant = app('tenant');
            $resetDays = (int) (($tenant->settings['daily_bonus']['streak_reset_days'] ?? 1));

            if ($diffDays > $resetDays) {
                // Пропустил больше дней — сбрасываем серию
                $currentStreak = 0;
                $bonus['current_streak'] = 0;
                $user->meta = $meta;
                $user->save();

                Log::info('[DailyBonus] Серия сброшена', [
                    'user_id' => $user->id,
                    'diff_days' => $diffDays,
                    'reset_days' => $resetDays,
                ]);
            }
        }

        // 📜 Берём историю и помечаем просроченные призы
        $history = $bonus['history'] ?? [];
        $pendingPrize = $bonus['pending_prize'] ?? null;

        // Помечаем просроченные призы
        foreach ($history as &$item) {
            if ($item['type'] !== 'bonus' && empty($item['claimed']) && empty($item['expired'])) {
                $prizeDate = Carbon::parse($item['date'])->format('Y-m-d');
                if ($prizeDate < $today) {
                    $item['expired'] = true;
                }
            }
        }

        // Сохраняем изменения в истории (expired)
        $bonus['history'] = $history;
        $user->meta = $meta;
        $user->save();

        return response()->json([
            'success' => true,
            'current_streak' => $currentStreak,
            'best_streak' => $bestStreak,
            'last_open_date' => $lastOpenDate,
            'today_opened' => $lastOpenDate === $today,
            'pending_prize' => $pendingPrize,
            'prize_history' => array_slice($history, 0, 30), // Ограничиваем до 30 последних
            'server_date' => $today,
        ]);
    }

    /**
     * 🎁 Открытие сундучка
     *
     * ⚠️ КРИТИЧЕСКИ ВАЖНО: Приз генерируется ТОЛЬКО на сервере!
     */
    public function open(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        // 🕐 Часовой пояс от клиента
        $userTimezone = $request->input('timezone', 'UTC');
        if (!in_array($userTimezone, timezone_identifiers_list())) {
            $userTimezone = 'UTC';
        }

        $today = Carbon::now($userTimezone)->format('Y-m-d');

        $tenant = app('tenant');
        $settings = $tenant->settings['daily_bonus'] ?? [];
        $streakDays = (int) ($settings['streak_days'] ?? 7);
        $resetDays = (int) ($settings['streak_reset_days'] ?? 1);

        $meta = $user->meta ?? [];
        $bonus = $meta['daily_bonus'] ?? [];

        $lastOpenDate = $bonus['last_open_date'] ?? null;
        $lastOpenTimezone = $bonus['last_open_timezone'] ?? 'UTC';

        // ==========================================
        // 1️⃣ ПРОВЕРКА: УЖЕ ОТКРЫТО СЕГОДНЯ?
        // ==========================================
        if ($lastOpenDate) {
            $lastDate = Carbon::parse($lastOpenDate, $lastOpenTimezone)->format('Y-m-d');
            if ($lastDate === $today) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы уже открыли сундучок сегодня. Возвращайтесь завтра!',
                ], 403);
            }
        }

        // ==========================================
        // 2️⃣ РАСЧЁТ ТЕКУЩЕЙ СЕРИИ
        // ==========================================
        $currentStreak = (int) ($bonus['current_streak'] ?? 0);
        $bestStreak = (int) ($bonus['best_streak'] ?? 0);

        if ($lastOpenDate) {
            $lastDate = Carbon::parse($lastOpenDate, $lastOpenTimezone);
            $todayDate = Carbon::parse($today, $userTimezone);
            $diffDays = $todayDate->diffInDays($lastDate, false);

            if ($diffDays > $resetDays) {
                $currentStreak = 0; // Сброс серии
            }
        }

        // Новый день серии
        $newStreak = $currentStreak + 1;

        // Не даём выйти за пределы streak_days
        if ($newStreak > $streakDays) {
            $newStreak = $streakDays;
        }

        // ==========================================
        // 3️⃣ 🎲 ГЕНЕРАЦИЯ ПРИЗА НА СЕРВЕРЕ
        // ==========================================
        $rewards = $settings['rewards'] ?? $this->getDefaultRewards();
        $rewardIndex = min($newStreak - 1, count($rewards) - 1);
        $rewardConfig = $rewards[$rewardIndex] ?? $rewards[0];

        $prize = $this->generatePrizeFromConfig($rewardConfig, $newStreak, $streakDays);

        // Генерируем уникальный ID приза
        $prizeId = (string) Str::uuid();

        // ==========================================
        // 4️⃣ АТОМАРНОЕ СОХРАНЕНИЕ
        // ==========================================
        try {
            DB::transaction(function () use ($user, &$meta, &$bonus, $newStreak, &$bestStreak, $prize, $prizeId, $today, $userTimezone) {

                // 💰 Если приз — бонусы, начисляем сразу
                if ($prize['type'] === 'bonus') {
                    CashBackService::call()->addCashBack(
                        $prize['value'],
                        "🎁 Ежедневный бонус (день {$newStreak})",
                        $user
                    );
                    $prize['claimed'] = true;
                    $prize['claimed_at'] = Carbon::now()->toIso8601String();
                } else {
                    $prize['claimed'] = false;
                }

                // Добавляем ID и дату
                $prize['id'] = $prizeId;
                $prize['date'] = $today;
                $prize['opened_at'] = Carbon::now()->toIso8601String();
                $prize['expired'] = false;

                // Обновляем pending_prize (если не бонус — нужно обналичить)
                if ($prize['type'] !== 'bonus') {
                    $bonus['pending_prize'] = $prize;
                } else {
                    $bonus['pending_prize'] = null;
                }

                // 📜 История
                $history = $bonus['history'] ?? [];
                array_unshift($history, $prize);
                if (count($history) > 50) {
                    $history = array_slice($history, 0, 50);
                }
                $bonus['history'] = $history;

                // 📊 Серия
                $bonus['current_streak'] = $newStreak;
                if ($newStreak > $bestStreak) {
                    $bestStreak = $newStreak;
                }
                $bonus['best_streak'] = $bestStreak;
                $bonus['last_open_date'] = $today;
                $bonus['last_open_timezone'] = $userTimezone;

                $meta['daily_bonus'] = $bonus;
                $user->meta = $meta;
                $user->save();
            });

        } catch (\Throwable $e) {
            Log::error('[DailyBonus] Ошибка открытия сундучка', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка. Попробуйте позже.',
            ], 500);
        }

        // Обновляем пользователя для нового баланса
        $user->refresh();
        $newBalance = (float) $user->cashback_balance;

        Log::info('[DailyBonus] Сундучок открыт', [
            'user_id' => $user->id,
            'streak' => $newStreak,
            'prize_type' => $prize['type'],
            'prize_value' => $prize['value'] ?? null,
            'prize_id' => $prizeId,
        ]);

        // 📢 Уведомления для крупных призов
        if (in_array($prize['type'], ['jackpot', 'product'])) {
            $this->notifyAboutPrize($user, $prize, $newStreak);
        }

        return response()->json([
            'success' => true,
            'prize' => $prize,
            'current_streak' => $newStreak,
            'best_streak' => $bestStreak,
            'balance' => $newBalance,
            'server_date' => $today,
        ]);
    }

    /**
     * ✅ Обналичивание приза (для не-бонусных призов)
     */
    public function claim(Request $request)
    {
        $validated = $request->validate([
            'prize_id' => 'required|string',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $prizeId = $validated['prize_id'];
        $meta = $user->meta ?? [];
        $bonus = $meta['daily_bonus'] ?? [];
        $history = $bonus['history'] ?? [];

        // 🔍 Ищем приз по ID
        $prizeIndex = null;
        foreach ($history as $idx => $item) {
            if (($item['id'] ?? null) === $prizeId) {
                $prizeIndex = $idx;
                break;
            }
        }

        if ($prizeIndex === null) {
            return response()->json([
                'success' => false,
                'message' => 'Приз не найден',
            ], 404);
        }

        $prize = $history[$prizeIndex];

        // ⚠️ Проверки
        if (!empty($prize['claimed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Приз уже был обналичен',
            ], 400);
        }

        if (!empty($prize['expired'])) {
            return response()->json([
                'success' => false,
                'message' => 'Срок действия приза истёк',
            ], 400);
        }

        if ($prize['type'] === 'bonus') {
            return response()->json([
                'success' => false,
                'message' => 'Бонусы уже начислены автоматически',
            ], 400);
        }

        // ==========================================
        // 🎁 ОБРАБОТКА ПРИЗА ПО ТИПУ
        // ==========================================
        try {
            DB::transaction(function () use ($user, &$history, $prizeIndex, $prize, &$bonus, &$meta) {

                switch ($prize['type']) {
                    case 'discount':
                        // TODO: Сохранить купон/промокод в профиле пользователя
                        // Пока просто логируем
                        Log::info('[DailyBonus] Скидка обналичена', [
                            'user_id' => $user->id,
                            'discount' => $prize['value'] . '%',
                        ]);
                        break;

                    case 'product':
                        // TODO: Добавить товар в корзину или создать купон
                        Log::info('[DailyBonus] Товар обналичен', [
                            'user_id' => $user->id,
                            'product' => $prize['productName'] ?? $prize['title'],
                        ]);
                        break;

                    case 'jackpot':
                        // Джекпот — обычно это бонусы, но может быть что угодно
                        if (($prize['subtype'] ?? null) === 'bonus' && isset($prize['value'])) {
                            CashBackService::call()->addCashBack(
                                $prize['value'],
                                "🏆 ДЖЕКПОТ из Ежедневного бонуса",
                                $user
                            );
                        }
                        break;
                }

                // 📝 Помечаем приз как обналиченный
                $history[$prizeIndex]['claimed'] = true;
                $history[$prizeIndex]['claimed_at'] = Carbon::now()->toIso8601String();

                $bonus['history'] = $history;
                $bonus['pending_prize'] = null; // Очищаем pending

                $meta['daily_bonus'] = $bonus;
                $user->meta = $meta;
                $user->save();
            });

        } catch (\Throwable $e) {
            Log::error('[DailyBonus] Ошибка обналичивания', [
                'user_id' => $user->id,
                'prize_id' => $prizeId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось обналичить приз. Свяжитесь с поддержкой.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Приз успешно обналичен!',
            'prize' => $history[$prizeIndex],
        ]);
    }

    // ==========================================
    // 🎲 ГЕНЕРАЦИЯ ПРИЗА
    // ==========================================

    protected function generatePrizeFromConfig(array $config, int $streakDay, int $maxStreak): array
    {
        $type = $config['type'] ?? 'bonus';

        // Джекпот на последнем дне
        if ($streakDay === $maxStreak && $type === 'jackpot') {
            $options = $config['options'] ?? [];
            if (!empty($options)) {
                $option = $options[array_rand($options)];
                return [
                    'type' => 'jackpot',
                    'subtype' => $option['type'] ?? 'bonus',
                    'value' => (int) ($option['value'] ?? 500),
                    'icon' => $option['icon'] ?? 'fa-solid fa-crown',
                    'title' => $option['title'] ?? 'ДЖЕКПОТ!',
                    'description' => "Приз за {$maxStreak} дней подряд!",
                ];
            }
        }

        switch ($type) {
            case 'bonus':
                $min = (int) ($config['min'] ?? 5);
                $max = (int) ($config['max'] ?? 50);
                $value = mt_rand($min, $max);
                return [
                    'type' => 'bonus',
                    'value' => $value,
                    'icon' => $config['icon'] ?? 'fa-solid fa-coins',
                    'title' => $config['title'] ?? "{$value} бонусов",
                    'description' => 'Бонусы на ваш счёт',
                ];

            case 'discount':
                $min = (int) ($config['min'] ?? 5);
                $max = (int) ($config['max'] ?? 20);
                $value = mt_rand($min, $max);
                return [
                    'type' => 'discount',
                    'value' => $value,
                    'icon' => $config['icon'] ?? 'fa-solid fa-percent',
                    'title' => $config['title'] ?? "Скидка {$value}%",
                    'description' => 'На следующий заказ',
                ];

            case 'product':
                $products = $config['products'] ?? ['Подарок'];
                $product = $products[array_rand($products)];
                return [
                    'type' => 'product',
                    'productName' => $product,
                    'icon' => $config['icon'] ?? 'fa-solid fa-gift',
                    'title' => $config['title'] ?? $product,
                    'description' => 'Бесплатный товар',
                    'value' => 0,
                ];

            default:
                return [
                    'type' => 'bonus',
                    'value' => 10,
                    'icon' => 'fa-solid fa-coins',
                    'title' => '10 бонусов',
                    'description' => 'Утешительный приз',
                ];
        }
    }

    protected function getDefaultRewards(): array
    {
        return [
            ['type' => 'bonus', 'min' => 5, 'max' => 15, 'icon' => 'fa-solid fa-coins', 'title' => 'Бонусы'],
            ['type' => 'bonus', 'min' => 15, 'max' => 30, 'icon' => 'fa-solid fa-coins', 'title' => 'Бонусы'],
            ['type' => 'discount', 'min' => 5, 'max' => 15, 'icon' => 'fa-solid fa-percent', 'title' => 'Скидка на заказ'],
            ['type' => 'bonus', 'min' => 30, 'max' => 70, 'icon' => 'fa-solid fa-gem', 'title' => 'Бонусы'],
            ['type' => 'bonus', 'min' => 70, 'max' => 150, 'icon' => 'fa-solid fa-gem', 'title' => 'Бонусы'],
            ['type' => 'product', 'products' => ['Пицца Маргарита'], 'icon' => 'fa-solid fa-gift', 'title' => 'Ценный приз'],
            [
                'type' => 'jackpot',
                'options' => [
                    ['type' => 'bonus', 'value' => 500, 'icon' => 'fa-solid fa-crown', 'title' => 'ДЖЕКПОТ! 500 бонусов'],
                ],
                'icon' => 'fa-solid fa-crown',
                'title' => 'ДЖЕКПОТ',
            ],
        ];
    }

    // ==========================================
    // 📢 УВЕДОМЛЕНИЯ
    // ==========================================

    protected function notifyAboutPrize($user, array $prize, int $streak): void
    {
        try {
            $tenant = app('tenant');
            $phone = $user->phone ?? 'не указан';
            $userName = $user->name ?? 'Не указано';

            $adminMessage = <<<HTML
🎁 <b>Крупный приз в Ежедневном бонусе!</b>

👤 <b>Клиент:</b> {$userName}
📱 <b>Телефон:</b> {$phone}
🔥 <b>Серия:</b> {$streak} дней

🎁 <b>Приз:</b> {$prize['title']}
📦 <b>Тип:</b> {$prize['type']}

🏢 <b>Тенант:</b> {$tenant->name}
HTML;

            MessageService::call()->sendMessage([
                'message' => $adminMessage,
                'title' => '🎁 Ежедневный бонус',
                'recipients' => ['partners' => true, 'telegram' => true],
                'meta' => [
                    'event_type' => 'daily_bonus_big_prize',
                    'customer_name' => $userName,
                    'customer_phone' => $phone,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[DailyBonus] Ошибка уведомления', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
