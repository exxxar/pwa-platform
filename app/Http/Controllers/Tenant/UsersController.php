<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantUser;
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

        $query = TenantUser::where('tenant_id', $tenant->id);

        // Поиск по имени или телефону
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Фильтры
        if ($request->boolean('need_admins')) {
            $query->where('is_admin', true);
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
            $query->whereNotNull('phone')
                ->where('phone', '!=', '');
        }

        if ($request->boolean('need_without_phone')) {
            $query->where(function ($q) {
                $q->whereNull('phone')
                    ->orWhere('phone', '');
            });
        }

        $users = $query->orderByDesc('created_at')
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

        // Здесь можно использовать PhpSpreadsheet или Laravel Excel
        // Для примера — CSV
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
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="users.csv"');
    }

    /**
     * 🆕 Скачивание истории начислений кэшбэка
     */
    public function cashbackHistory(Request $request)
    {
        $tenant = app('tenant');

        // Получаем историю из таблицы cashback_transactions или аналогичной
        $history = DB::table('cashback_transactions')
            ->where('tenant_id', $tenant->id)
            ->join('tenant_users', 'cashback_transactions.tenant_user_id', '=', 'tenant_users.id')
            ->select([
                'tenant_users.name',
                'tenant_users.phone',
                'cashback_transactions.amount',
                'cashback_transactions.type',
                'cashback_transactions.description',
                'cashback_transactions.created_at',
            ])
            ->orderByDesc('cashback_transactions.created_at')
            ->get();

        $csv = "Имя,Телефон,Сумма,Тип,Описание,Дата\n";

        foreach ($history as $item) {
            $csv .= sprintf(
                "%s,%s,%.2f,%s,%s,%s\n",
                $item->name ?? '',
                $item->phone ?? '',
                $item->amount,
                $item->type,
                $item->description ?? '',
                $item->created_at
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="cashback_history.csv"');
    }

    /**
     * 🆕 Получить данные пользователя для редактирования
     */
    public function edit(int $userId)
    {
        $tenant = app('tenant');

        $user = TenantUser::where('tenant_id', $tenant->id)
            ->with(['roles'])
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
}
