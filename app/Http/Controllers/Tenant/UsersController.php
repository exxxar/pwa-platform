<?php

namespace App\Http\Controllers\Tenant;


use App\Http\Controllers\Controller;
use App\Models\Tenant\CashBackHistory;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantUser;
use App\Services\Tenants\CashBackService;
use App\Services\Tenants\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    /**
     * 🆕 Поиск пользователей с фильтрами
     */
    public function search(Request $request)
    {
        $tenant = app('tenant');

        // 🆕 Добавляем eager loading баланса, чтобы избежать N+1 запросов на фронтенде
        $query = TenantUser::where('tenant_id', $tenant->id)->with('cashbacks');

        // Поиск по имени или телефону
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Фильтры
        if ($request->boolean('need_admins')) {
            if ($request->boolean('is_admin')) {
                $query->admins($tenant->id);
            } else {
                $query->regularUsers($tenant->id);
            }
        }

        if ($request->boolean('need_vip')) {
            $query->where('is_vip', true);
        }

        if ($request->boolean('need_not_vip')) {
            $query->where('is_vip', false);
        }

        if ($request->boolean('need_deliveryman')) {
            $query->where('is_deliveryman', true);
        }

        if ($request->boolean('need_with_phone')) {
            $query->whereNotNull('phone')->where('phone', '!=', '');
        }

        if ($request->boolean('need_without_phone')) {
            $query->where(function ($q) {
                $q->whereNull('phone')->orWhere('phone', '');
            });
        }

        // ==========================================
        // 🆕 ДИНАМИЧЕСКАЯ СОРТИРОВКА
        // ==========================================

        // 1. Получаем параметры сортировки (с безопасными значениями по умолчанию)
        $sortBy = $request->input('order_by', 'id');
        $sortDirection = $request->input('direction', 'desc');

        // 2. Белый список разрешенных колонок (ЗАЩИТА ОТ SQL-ИНЪЕКЦИЙ)
        // Добавьте сюда 'cashback_balance', если хотите разрешить сортировку по балансу
        $allowedColumns = ['id', 'name', 'created_at'];

        if (!in_array($sortBy, $allowedColumns)) {
            $sortBy = 'id'; // Сброс на безопасное значение по умолчанию
        }

        // 3. Разрешаем только 'asc' или 'desc'
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        // 4. Применяем сортировку и пагинацию
        $users = $query->orderBy($sortBy, $sortDirection)
            ->paginate($request->input('size', 20));

        return response()->json($users);
    }
    /**
     * 🆕 Скачивание списка пользователей
     */
    public function download(Request $request)
    {
        $tenant = app('tenant');

        $users = TenantUser::where('tenant_id', $tenant->id)
            ->select(['id', 'name', 'phone', 'email', 'is_admin', 'is_vip', 'is_deliveryman', 'created_at'])
            ->get();

        $csv = "ID,Имя,Телефон,Email,Админ,VIP,Курьер,Дата регистрации\n";

        foreach ($users as $user) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s\n",
                $user->id,
                $user->name ?? '',
                $user->phone ?? '',
                $user->email ?? '',
                $user->is_admin ? 'Да' : 'Нет',
                $user->is_vip ? 'Да' : 'Нет',
                $user->is_deliveryman ? 'Да' : 'Нет',
                $user->created_at?->format('Y-m-d H:i:s') ?? ''
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="users.csv"');
    }

    /**
     * 🆕 Скачивание истории начислений кэшбэка (Админский экспорт)
     */
    public function cashbackHistory(Request $request)
    {
        $tenant = app('tenant');

        // 🆕 Используем модель вместо DB::table для согласованности с CashBackService
        $history = CashBackHistory::where('tenant_id', $tenant->id)
            ->with(['user' => function ($query) {
                $query->select('id', 'name', 'phone');
            }])
            ->orderByDesc('created_at')
            ->get();

        $csv = "ID Пользователя,Имя,Телефон,Сумма,Тип,Описание,Дата\n";

        foreach ($history as $item) {
            $userName = $item->user ? ($item->user->name ?? 'Неизвестно') : 'Неизвестно';
            $userPhone = $item->user ? ($item->user->phone ?? '-') : '-';

            // 🆕 Очищаем описание от переносов строк и запятых, чтобы не сломать CSV
            $cleanDescription = str_replace(["\n", "\r", ","], " ", $item->description ?? '');
            $typeLabel = $item->type === 'credit' ? 'Начисление' : 'Списание';

            $csv .= sprintf(
                "%d,%s,%s,%.2f,%s,%s,%s\n",
                $item->tenant_user_id,
                $userName,
                $userPhone,
                $item->amount,
                $typeLabel,
                $cleanDescription,
                $item->created_at->format('Y-m-d H:i:s')
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="cashback_history.csv"');
    }

    /**
     * 🆕 Получить данные пользователя для редактирования
     */
    public function edit(int $userId)
    {
        $tenant = app('tenant');

        $user = TenantUser::where('tenant_id', $tenant->id)
            ->with(['roles', 'cashbacks'])
            ->findOrFail($userId);

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * 🆕 Обновление пользователя
     */
    public function update(Request $request, int $userId)
    {
        $tenant = app('tenant');

        $user = TenantUser::where('tenant_id', $tenant->id)
            ->findOrFail($userId);

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'birthday' => 'nullable|date',
            'sex' => 'nullable|in:male,female,other',
            'is_active' => 'boolean',
            'is_vip' => 'boolean',
            'vip_expires_at' => 'nullable|date|after:today',
        ]);

        // Обработка VIP даты
        if (isset($validated['vip_expires_at'])) {
            $validated['vip_expires_at'] = $validated['vip_expires_at']
                ? $validated['vip_expires_at'] . ' 23:59:59'
                : null;

            if ($validated['is_vip'] && !$user->vip_activated_at) {
                $validated['vip_activated_at'] = now();
            }
        }

        // Если VIP выключен — сбрасываем даты
        if (isset($validated['is_vip']) && !$validated['is_vip']) {
            $validated['vip_activated_at'] = null;
            $validated['vip_expires_at'] = null;
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data' => $user->fresh(),
            'message' => 'Профиль обновлён',
        ]);
    }

    /**
     * 🆕 Блокировка/разблокировка
     */
    public function toggleBlock(Request $request, int $userId)
    {
        $tenant = app('tenant');

        $user = TenantUser::where('tenant_id', $tenant->id)
            ->findOrFail($userId);

        $validated = $request->validate([
            'block' => 'required|boolean',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validated['block']) {
            $user->update([
                'blocked_at' => now(),
                'blocked_message' => $validated['message'] ?? null,
            ]);
        } else {
            $user->update([
                'blocked_at' => null,
                'blocked_message' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $user->fresh(),
            'message' => $validated['block'] ? 'Пользователь заблокирован' : 'Пользователь разблокирован',
        ]);
    }

    /**
     * 🆕 Выдача/отзыв VIP
     */
    public function toggleVip(Request $request, int $userId)
    {
        $tenant = app('tenant');

        $user = TenantUser::where('tenant_id', $tenant->id)
            ->findOrFail($userId);

        $validated = $request->validate([
            'vip' => 'required|boolean',
            'expires_at' => 'nullable|date|after:today',
        ]);

        if ($validated['vip']) {
            $user->update([
                'is_vip' => true,
                'vip_activated_at' => now(),
                'vip_expires_at' => $validated['expires_at'] ?? null,
            ]);
        } else {
            $user->update([
                'is_vip' => false,
                'vip_activated_at' => null,
                'vip_expires_at' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $user->fresh(),
            'message' => $validated['vip'] ? 'VIP статус выдан' : 'VIP статус отозван',
        ]);
    }



    /**
     * 🆕 Управление кэшбэком пользователя (начисление или списание)
     */
    public function manageCashback(Request $request, int $userId)
    {
        $tenant = app('tenant');

        $user = TenantUser::where('tenant_id', $tenant->id)
            ->where('id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'amount'      => 'required|numeric|min:1',
            'type'        => 'required|in:credit,debit',
            'description' => 'required|string|max:255',
        ]);

        $amount      = (float) $validated['amount'];
        $type        = $validated['type'];
        $description = $validated['description'];
        $balanceBefore = (float) $user->cashback_balance;

        try {
            DB::beginTransaction();

            if ($type === 'credit') {
                CashBackService::call()->addCashBack(
                    amount: $amount,
                    description: $description,
                    user: $user,
                    withLevels: false
                );
                $message = 'Кэшбэк успешно начислен';
            } else {
                if ($user->cashback_balance < $amount) {
                    throw new \Exception("Недостаточно средств. Доступно: {$user->cashback_balance}");
                }

                CashBackService::call()->removeCashBack(
                    amount: $amount,
                    description: $description,
                    user: $user
                );
                $message = 'Кэшбэк успешно списан';
            }

            DB::commit();

            $user->refresh();
            $balanceAfter = (float) $user->cashback_balance;

            // 🆕 Оповещаем пользователя
            $this->notifyUserAboutCashback($user, $type, $amount, $description, $balanceBefore, $balanceAfter);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'user'              => $user,
                    'cashback_balance'  => $balanceAfter,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Ошибка операции с кэшбэком'
            ], 422);
        }
    }


    /**
     * 🔍 Поиск пользователя по referral_code (для QR-сканера)
     */
    public function findByReferralCode(Request $request)
    {
        $validated = $request->validate([
            'referral_code' => 'required|string|max:20',
        ]);

        $tenant = app('tenant');
        $user = TenantUser::where('tenant_id', $tenant->id)
            ->where('referral_code', $validated['referral_code'])
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'cashback_balance' => (float) $user->cashback_balance,
                'referral_code' => $user->referral_code,
                'is_vip' => (bool) $user->is_vip,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            ],
        ]);
    }

    /**
     * 💰 Управление кэшбэком через referral_code (для QR-сканера)
     */
    public function manageCashbackByReferralCode(Request $request)
    {
        $validated = $request->validate([
            'referral_code' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:credit,debit',
            'description' => 'nullable|string|max:255',
        ]);

        $tenant = app('tenant');
        $user = TenantUser::where('tenant_id', $tenant->id)
            ->where('referral_code', $validated['referral_code'])
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден',
            ], 404);
        }

        $amount = (float) $validated['amount'];
        $type = $validated['type'];
        $description = $validated['description'] ?? ($type === 'credit' ? 'Начисление через QR' : 'Списание через QR');
        $balanceBefore = (float) $user->cashback_balance;

        try {
            DB::beginTransaction();

            if ($type === 'credit') {
                CashBackService::call()->addCashBack(
                    amount: $amount,
                    description: $description,
                    user: $user,
                    withLevels: false
                );
                $message = "Начислено {$amount} баллов";
            } else {
                if ($user->cashback_balance < $amount) {
                    throw new \Exception("Недостаточно средств. Доступно: {$user->cashback_balance}");
                }

                CashBackService::call()->removeCashBack(
                    amount: $amount,
                    description: $description,
                    user: $user
                );
                $message = "Списано {$amount} баллов";
            }

            DB::commit();

            $user->refresh();
            $balanceAfter = (float) $user->cashback_balance;

            // 🆕 Оповещаем пользователя
            $this->notifyUserAboutCashback($user, $type, $amount, $description, $balanceBefore, $balanceAfter);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'cashback_balance' => $balanceAfter,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Ошибка операции',
            ], 422);
        }
    }


    // ==========================================
    // 🆕 ПРИВАТНЫЙ МЕТОД ОПОВЕЩЕНИЯ
    // ==========================================

    /**
     * 📬 Оповещение пользователя об операции с кэшбэком
     *
     * Ищет или создаёт системный диалог и отправляет красивое сообщение
     */
    private function notifyUserAboutCashback(
        TenantUser $user,
        string $type,
        float $amount,
        string $description,
        float $balanceBefore,
        float $balanceAfter
    ): void {
        try {
            $tenant = app('tenant');

            // 1. Ищем или создаём системный диалог с пользователем
            $dialog = TenantDialog::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'tenant_user_id' => $user->id,
                    'type' => 'system',
                ],
                [
                    'title' => '💰 Уведомления о бонусах',
                ]
            );

            // 2. Формируем красивое сообщение
            $isCredit = $type === 'credit';
            $emoji = $isCredit ? '🎉' : '💸';
            $action = $isCredit ? 'НАЧИСЛЕНО' : 'СПИСАНО';
            $sign = $isCredit ? '+' : '-';
            $color = $isCredit ? '#10b981' : '#ef4444';

            $message = "{$emoji} <b>{$action} {$amount} БАЛЛОВ</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "📝 <b>Причина:</b> {$description}\n\n";
            $message .= "📊 <b>Баланс:</b>\n";
            $message .= "   Было: <b>{$balanceBefore}</b> баллов\n";
            $message .= "   Операция: <b>{$sign}{$amount}</b>\n";
            $message .= "   Стало: <b>{$balanceAfter}</b> баллов\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "💳 <b>Текущий баланс:</b> <b>{$balanceAfter} баллов</b>";

            if ($isCredit) {
                $message .= "\n\n🛍️ Баллы можно потратить в нашем магазине!";
            } else {
                $message .= "\n\nЕсли у вас есть вопросы — напишите нам в чат.";
            }

            // 3. Отправляем через MessageService
            MessageService::call()->sendMessage([
                'message' => $message,
                'dialog_id' => $dialog->id,
                'meta' => [
                    'is_system' => true,
                    'type' => 'cashback_' . $type,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => $description,
                ],
                'recipients' => [
                    'client' => true,  // → запись в TenantMessage
                    'telegram' => false, // Не спамим в общий канал
                ],
            ]);

        } catch (\Throwable $e) {
            // Не прерываем основную операцию, если оповещение упало
            \Illuminate\Support\Facades\Log::warning('[UsersController] Не удалось оповестить пользователя о кэшбэке', [
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🆕 Начисление бонусных баллов пользователю (Через CashBackService)
     */
    public function addCashback(Request $request, int $userId)
    {
        $tenant = app('tenant');
        $user = TenantUser::where('tenant_id', $tenant->id)->findOrFail($userId);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:100000',
            'description' => 'nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];
        $description = $validated['description'] ?? 'Ручное начисление администратором';
        $balanceBefore = (float) $user->cashback_balance;

        try {
            // 🆕 Делегируем логику сервису. Он сам обновит баланс, создаст историю и начислит реферальные бонусы.
            CashBackService::call()->addCashBack(
                amount: $amount,
                description: $description,
                user: $user,
                orderId: null,
                percent: null,
                withLevels: true // Оставляем true, чтобы сработала реферальная система, если она настроена
            );

            $user->refresh();
            $balanceAfter = (float) $user->cashback_balance;

            // 🆕 Оповещаем пользователя о начислении
            $this->notifyUserAboutCashback($user, 'credit', $amount, $description, $balanceBefore, $balanceAfter);

            return response()->json([
                'success' => true,
                'data' => $user->load('cashbacks'),
                'message' => "Успешно начислено {$amount} баллов",
            ]);

        } catch (\Exception $e) {
            // 🆕 Перехватываем исключения из сервиса (например, "Amount must be greater than 0")
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 🆕 Создание/получение диалога с пользователем (чтобы написать ему)
     */
    public function startChat(Request $request, int $userId)
    {
        $tenant = app('tenant');
        $admin = auth()->user() ?? $request->user();

        $user = TenantUser::where('tenant_id', $tenant->id)->findOrFail($userId);

        // ⚠️ ВАЖНО: Замените 'tenant_user_id' на реальное имя колонки из вашей миграции,
        // если оно отличается (например, 'creator_id' или 'user_id', если вы её добавите)
        $ownerColumn = 'tenant_user_id';
       // $interlocutorColumn = 'interlocutor_id';

        // Ищем существующий диалог между админом и пользователем (в любом порядке)
        $dialog = \App\Models\Tenant\TenantDialog::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($user, $admin, $ownerColumn) {
                // Вариант 1: Админ создал, пользователь - собеседник
                $q->where(function ($q2) use ($user, $admin, $ownerColumn) {
                    $q2->where($ownerColumn, $admin->id);
                    // Вариант 2: Пользователь создал, админ - собеседник
                })->orWhere(function ($q2) use ($user, $admin, $ownerColumn) {
                    $q2->where($ownerColumn, $user->id);
                });
            })
            ->first();

        // Если диалога нет — создаём новый
        if (!$dialog) {
            $dialog = \App\Models\Tenant\TenantDialog::create([
                'tenant_id' => $tenant->id,
                $ownerColumn => $admin->id,          // Используем динамическое имя колонки
                'type' => 'direct',
            ]);
        }

        return response()->json([
            'success' => true,
            'dialog_id' => $dialog->id,
        ]);
    }
}
