<?php

namespace App\Services\Admin\Global;

use App\Models\Admin\Permission;

class PermissionService
{
    /**
     * Получить список разрешений
     */
    public function getPermissions(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Permission::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Создать разрешение
     */
    public function createPermission(array $data): Permission
    {
        return Permission::create([
            'name' => $data['name'],
            'label' => $data['label'] ?? $data['name'],
        ]);
    }

    /**
     * Обновить разрешение
     */
    public function updatePermission(Permission $permission, array $data): Permission
    {
        $permission->update([
            'label' => $data['label'] ?? $permission->label,
            // name лучше не менять, так как он используется в коде
        ]);

        return $permission;
    }

    /**
     * Удалить разрешение
     */
    public function deletePermission(Permission $permission): bool
    {
        // Проверяем, не используется ли разрешение в ролях
        if ($permission->roles()->exists()) {
            throw new \Exception('Нельзя удалить разрешение, которое используется в ролях');
        }

        return $permission->delete();
    }
}
