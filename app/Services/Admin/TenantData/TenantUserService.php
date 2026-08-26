<?php

namespace App\Services\Admin\TenantData;

use App\Models\Tenant\TenantUser;
use App\Models\Tenant\TenantRole;

class TenantUserService
{
    /**
     * Получить список пользователей тенанта
     */
    public function getUsers(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = TenantUser::query()->with(['roles', 'addresses']);

        // Фильтр по tenant_id
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Поиск
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Фильтр по активности
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Фильтр по VIP
        if (isset($filters['is_vip'])) {
            $query->where('is_vip', $filters['is_vip']);
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Получить пользователя с детальной информацией
     */
    public function getUserWithDetails(TenantUser $user): TenantUser
    {
        return $user->load([
            'roles',
            'addresses',
            'cashbacks',
            'orders',
            'referrer',
            'directReferrals',
        ]);
    }

    /**
     * Обновить пользователя
     */
    public function updateUser(TenantUser $user, array $data): TenantUser
    {
        $user->update($data);

        // Синхронизация ролей внутри тенанта
        if (isset($data['role_ids'])) {
            $user->roles()->sync($data['role_ids']);
        }

        return $user->fresh();
    }

    /**
     * Удалить пользователя
     */
    public function deleteUser(TenantUser $user): bool
    {
        return $user->delete();
    }

    /**
     * Заблокировать/разблокировать пользователя
     */
    public function toggleBlock(TenantUser $user, ?string $message = null): TenantUser
    {
        if ($user->isBlocked()) {
            $user->activate();
            $user->update(['blocked_at' => null, 'blocked_message' => null]);
        } else {
            $user->deactivate();
            $user->update([
                'blocked_at' => now(),
                'blocked_message' => $message,
            ]);
        }

        return $user;
    }

    /**
     * Выдать VIP статус
     */
    public function grantVip(TenantUser $user, ?int $days = null): TenantUser
    {
        $user->grantVip($days);
        return $user;
    }

    /**
     * Отозвать VIP статус
     */
    public function revokeVip(TenantUser $user): TenantUser
    {
        $user->revokeVip();
        return $user;
    }
}
