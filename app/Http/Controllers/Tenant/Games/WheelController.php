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

        // 🎰 Стоимость вращения (можно вынести в настройки тенанта)
        $spinCost = (float)($settings['spin_cost'] ?? 1000);

        $meta = $user->meta ?? [];
        $attempts = $meta['wheel_attempts'] ?? [];
        $cutoffDate = Carbon::now()->subDays($intervalDays);

        Log::info('[Wheel] Попыток в meta:', ['total' => count($attempts), 'cutoff' => $cutoffDate]);

        // ==========================================
        // 1️⃣ ПРОВЕРКА ЛИМИТА ПОПЫТОК
        // ==========================================
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

        // ==========================================
        // 2️⃣ 🎰 ПРОВЕРКА БАЛАНСА КЭШБЭКА
        // ==========================================
        $currentBalance = (float)$user->cashback_balance;

        if ($currentBalance < $spinCost) {
            Log::warning('[Wheel] Недостаточно кэшбэка', [
                'user_id' => $user->id,
                'balance' => $currentBalance,
                'required' => $spinCost,
                'shortage' => $spinCost - $currentBalance,
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
        // 3️⃣ 💰 СПИСАНИЕ + ЗАПИСЬ ПОПЫТКИ (АТОМАРНО)
        // ==========================================
        try {
            DB::transaction(function () use ($user, &$attempts, &$meta, $spinCost) {

                // 💸 Списываем кэшбэк (отрицательная сумма)
                CashBackService::call()->removeCashBack(
                    $spinCost,
                    "🎰 Ставка в Колесо Фортуны",
                    $user
                );

                // 📝 Записываем попытку в meta
                $attempts[] = [
                    'played_at' => Carbon::now()->toIso8601String(),
                    'cost' => $spinCost, // 🆕 Сохраняем стоимость для истории
                ];

                // Чистим старые записи, если их слишком много
                if (count($attempts) > 100) {
                    $attempts = array_slice($attempts, -100);
                }

                $meta['wheel_attempts'] = $attempts;
                $user->meta = $meta;
                $user->save();
            });

        } catch (\Throwable $e) {
            Log::error('[Wheel] Ошибка при списании кэшбэка или записи попытки', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обработке вращения. Средства не списаны.',
            ], 500);
        }

        // ==========================================
        // 4️⃣ 🔄 ОБНОВЛЯЕМ ПОЛЬЗОВАТЕЛЯ ДЛЯ АКТУАЛЬНОГО БАЛАНСА
        // ==========================================
        $user->refresh();
        $newBalance = (float)$user->cashback_balance;

        Log::info('[Wheel] Успешное вращение', [
            'user_id' => $user->id,
            'cost' => $spinCost,
            'old_balance' => $currentBalance,
            'new_balance' => $newBalance,
            'total_attempts' => count($attempts),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Попытка зафиксирована, кэшбэк списан',
            'current_attempts' => count($validAttempts) + 1,
            'balance' => $newBalance,           // 🆕 Новый баланс
            'cost' => $spinCost,                // 🆕 Сколько списали
            'previous_balance' => $currentBalance, // 🆕 Для отладки
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


    public function saveWin(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Пользователь не авторизован'], 401);
        }

        $validated = $request->validate([
            'prize_id' => 'required|integer',
            'description' => 'required|string|max:255',
            'mark' => 'nullable|string|max:255',
            'form_data' => 'nullable|array',
        ]);

        // ==========================================
        // 1️⃣ СОХРАНЕНИЕ ВЫИГРЫША В META
        // ==========================================
        $meta = $user->meta ?? [];
        $wheelWins = $meta['wheel_wins'] ?? [];

        $newWin = [
            'prize_id' => $validated['prize_id'],
            'description' => $validated['description'],
            'mark' => $validated['mark'] ?? 'в заведении',
            'won_at' => now()->toIso8601String(),
            'form_data' => $validated['form_data'] ?? [],
        ];

        array_unshift($wheelWins, $newWin);

        if (count($wheelWins) > 50) {
            $wheelWins = array_slice($wheelWins, 0, 50);
        }

        $meta['wheel_wins'] = $wheelWins;
        $user->meta = $meta;
        $user->save();

        // ==========================================
        // 2️⃣ 📢 ОТПРАВКА УВЕДОМЛЕНИЙ
        // ==========================================
        $this->notifyAboutWin($user, $newWin);

        return response()->json([
            'success' => true,
            'message' => 'Выигрыш успешно сохранен в профиль!',
            'wins' => $wheelWins
        ]);
    }

    /**
     * 📢 Отправка уведомлений о выигрыше в колесе
     *
     * - Сообщение пользователю в его чат поддержки
     * - Уведомление админам/партнёрам в Telegram
     * - (Опционально) Создание задачи в CRM
     */
    protected function notifyAboutWin($user, array $win): void
    {
        try {
            $tenant = app('tenant');
            $prizeName = $win['description'] ?? 'Приз';
            $mark = $win['mark'] ?? 'в заведении';
            $formData = $win['form_data'] ?? [];

            $phone = $formData['phone'] ?? $user->phone ?? 'не указан';
            $userName = $formData['name'] ?? $user->name ?? 'Не указано';

            // ==========================================
            // 🎨 ФОРМИРОВАНИЕ СООБЩЕНИЯ
            // ==========================================
            $userMessage = $this->buildUserWinMessage($prizeName, $mark);
            $adminMessage = $this->buildAdminWinMessage($user, $prizeName, $mark, $phone, $userName);

            // ==========================================
            // 📱 1. СООБЩЕНИЕ ПОЛЬЗОВАТЕЛЮ В ЕГО ЧАТ
            // ==========================================
            $dialog = $this->getOrCreateWheelDialog($user);

            if ($dialog) {
                try {
                    MessageService::call()->sendMessage([
                        'message' => $userMessage,
                        'dialog_id' => $dialog->id,
                        'recipients' => [
                            'client' => true, // Запись в TenantMessage
                        ],
                        'meta' => [
                            'is_system' => true,
                            'event_type' => 'wheel_win',
                            'prize_id' => $win['prize_id'],
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[Wheel] Не удалось отправить сообщение в чат пользователя', [
                        'user_id' => $user->id,
                        'dialog_id' => $dialog->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ==========================================
            // 📣 2. УВЕДОМЛЕНИЕ АДМИНАМ / ПАРТНЁРАМ В TELEGRAM
            // ==========================================
            $tgSettings = $tenant->settings['telegram'] ?? [];
            $wheelNotifications = $tenant->settings['wheel']['notify_partners'] ?? true;

            if ($wheelNotifications) {
                try {
                    MessageService::call()->sendMessage([
                        'message' => $adminMessage,
                        'title' => '🎰 Выигрыш в Колесе Фортуны',
                        'recipients' => [
                            'partners' => true,    // В thread партнёров
                            'telegram' => true,    // В канал уведомлений
                        ],
                        'meta' => [
                            'event_type' => 'wheel_win',
                            'customer_name' => $userName,
                            'customer_phone' => $phone,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[Wheel] Не удалось отправить уведомление в Telegram', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ==========================================
            // 📊 3. (ОПЦИОНАЛЬНО) СОЗДАНИЕ ЗАДАЧИ В CRM
            // ==========================================
            $createCrmTask = $tenant->settings['wheel']['create_crm_task'] ?? false;

            if ($createCrmTask) {
                try {
                    MessageService::call()->sendMessage([
                        'message' => $adminMessage,
                        'title' => "🎰 Выигрыш: {$userName}",
                        'recipients' => [
                            'crm' => true,
                        ],
                        'meta' => [
                            'customer_name' => $userName,
                            'customer_phone' => $phone,
                            'kanban_payload' => [
                                'event' => 'wheel_win',
                                'prize' => $prizeName,
                                'mark' => $mark,
                            ],
                            'kanban_custom_data' => [
                                'wheel_prize' => $prizeName,
                                'wheel_mark' => $mark,
                                'wheel_won_at' => $win['won_at'],
                            ],
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[Wheel] Не удалось создать задачу в CRM', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Throwable $e) {
            // Уведомления не должны ломать основной функционал
            Log::error('[Wheel] Критическая ошибка при отправке уведомлений о выигрыше', [
                'user_id' => $user->id,
                'win' => $win,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 🏗️ Получить или создать диалог для уведомлений о колесе
     */
    protected function getOrCreateWheelDialog($user): ?TenantDialog
    {
        $tenant = app('tenant');

        // Ищем существующий диалог типа 'wheel_support'
        $dialog = TenantDialog::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->where('type', 'wheel_support')
            ->first();

        if ($dialog) {
            return $dialog;
        }

        // Создаём новый диалог
        try {
            return TenantDialog::create([
                'tenant_id' => $tenant->id,
                'tenant_user_id' => $user->id,
                'type' => 'wheel_support',
                'title' => '🎰 Колесо Фортуны',
                'is_closed' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Wheel] Не удалось создать диалог', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 📱 Сообщение пользователю в чат
     */
    protected function buildUserWinMessage(string $prizeName, string $mark): string
    {
        $markEmoji = match (true) {
            str_contains(mb_strtolower($mark), 'заведен') => '🏪',
            str_contains(mb_strtolower($mark), 'доставк') => '🚚',
            str_contains(mb_strtolower($mark), 'самовывоз') => '📦',
            default => '🎁',
        };

        return <<<HTML
🎉 <b>Поздравляем с выигрышем!</b>

Вы выиграли: <b>{$prizeName}</b>

{$markEmoji} <b>Где забрать:</b> {$mark}

💡 Чтобы получить приз:
• Сохраните это сообщение
• Покажите его сотруднику при получении
• Или дождитесь связи с менеджером в этом чате

<i>Приз действителен в течение 30 дней с момента выигрыша.</i>
HTML;
    }

    /**
     * 📣 Сообщение админам / партнёрам
     */
    protected function buildAdminWinMessage($user, string $prizeName, string $mark, string $phone, string $userName): string
    {
        $tenant = app('tenant');
        $baseUrl = request()->getSchemeAndHttpHost();
        $profileUrl = "{$baseUrl}/pwa#/admin/users/{$user->id}";

        $time = $user->created_at?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i');
        return <<<HTML
🎰 <b>Новый выигрыш в Колесе Фортуны!</b>

👤 <b>Клиент:</b> {$userName}
📱 <b>Телефон:</b> {$phone}
🆔 <b>ID:</b> #{$user->id}

🎁 <b>Приз:</b> {$prizeName}
📍 <b>Получение:</b> {$mark}
🕐 <b>Время:</b> {$time}

🏢 <b>Тенант:</b> {$tenant->name}

<a href="{$profileUrl}">👁 Открыть профиль клиента</a>

<i>Свяжитесь с клиентом для уточнения деталей получения приза.</i>
HTML;
    }
}
