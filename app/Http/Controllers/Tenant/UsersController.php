<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\CashBackHistory;
use App\Services\CashBackService;
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

        // 1. Находим пользователя и проверяем принадлежность к тенанту (безопасность)
        $user = TenantUser::where('tenant_id', $tenant->id)
            ->where('id', $userId)
            ->firstOrFail();

        // 2. Валидация с явным типом операции
        $validated = $request->validate([
            'amount'      => 'required|numeric|min:1',          // Всегда положительное
            'type'        => 'required|in:credit,debit',        // Явный тип операции
            'description' => 'required|string|max:255',
        ]);

        $amount      = (float) $validated['amount'];
        $type        = $validated['type'];
        $description = $validated['description'];

        try {
            DB::beginTransaction();

            if ($type === 'credit') {
                // Начисление кэшбэка
                // withLevels: false — ручное начисление админом не должно триггерить реферальные уровни
                CashBackService::call()->addCashBack(
                    amount: $amount,
                    description: $description,
                    user: $user,
                    withLevels: false
                );
                $message = 'Кэшбэк успешно начислен';
            } else {
                // Списание кэшбэка
                // Дополнительная проверка, что у пользователя хватает баллов
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

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'user'              => $user->fresh(),
                    'cashback_balance'  => $user->fresh()->cashback_balance,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Ловим конкретную ошибку от сервиса (например, "Недостаточно средств")
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Ошибка операции с кэшбэком'
            ], 422);
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

        try {
            // 🆕 Делегируем логику сервису. Он сам обновит баланс, создаст историю и начислит реферальные бонусы.
            CashBackService::call()->addCashBack(
                amount: (float) $validated['amount'],
                description: $validated['description'] ?? 'Ручное начисление администратором',
                user: $user,
                orderId: null,
                percent: null,
                withLevels: true // Оставляем true, чтобы сработала реферальная система, если она настроена
            );

            return response()->json([
                'success' => true,
                'data' => $user->fresh()->load('cashbacks'),
                'message' => "Успешно начислено {$validated['amount']} баллов",
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
