<?php

namespace App\Services\Admin\Global;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantPermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TenantManagementService
{
    /**
     * Получить список тенантов с фильтрацией и пагинацией
     */
    public function getTenants(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Tenant::query();

        // Поиск по названию или slug
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        // Фильтр по активности
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Фильтр по плану
        if (!empty($filters['plan_slug'])) {
            $query->where('plan_slug', $filters['plan_slug']);
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Создать нового тенанта
     */
    public function createTenant(array $data): Tenant
    {
        // Генерируем slug, если не указан
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Создаем тенант (внутри сработает booted() и создаст роли/права/админов)
        $tenant = Tenant::create($data);

        return $tenant;
    }

    /**
     * Обновить тенанта
     */
    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);
        return $tenant->fresh();
    }

    /**
     * Удалить тенанта
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        // Опционально: мягкое удаление или полная очистка связанных данных
        return $tenant->delete();
    }

    /**
     * Переключить статус активности тенанта
     */
    public function toggleStatus(Tenant $tenant): Tenant
    {
        $tenant->update(['is_active' => !$tenant->is_active]);
        return $tenant;
    }

    /**
     * Обновить баланс тенанта
     */
    public function updateBalance(Tenant $tenant, float $amount, string $reason = ''): Tenant
    {
        $tenant->increment('balance', $amount);

        // Опционально: логирование изменения баланса в отдельную таблицу

        return $tenant->fresh();
    }

    /**
     * Получить статистику по тенанту
     */
    public function getTenantStats(Tenant $tenant): array
    {
        return [
            'users_count' => $tenant->users()->count(),
            'active_users_count' => $tenant->users()->where('is_active', true)->count(),
            'orders_count' => $tenant->products()->count(), // Замените на orders() когда добавите связь
            'revenue' => 0, // Рассчитать из транзакций
        ];
    }
}
