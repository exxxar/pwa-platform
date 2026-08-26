<?php

namespace App\Services\Admin\Global;

use App\Models\Role;
use App\Models\Admin\Permission;

class RoleService
{
    /**
     * Получить список ролей
     */
    public function getRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::with('permissions')->get();
    }

    /**
     * Создать роль
     */
    public function createRole(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'label' => $data['label'] ?? $data['name'],
        ]);

        if (!empty($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return $role->load('permissions');
    }

    /**
     * Обновить роль
     */
    public function updateRole(Role $role, array $data): Role
    {
        $role->update([
            'name' => $data['name'] ?? $role->name,
            'label' => $data['label'] ?? $role->label,
        ]);

        if (isset($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return $role->fresh()->load('permissions');
    }

    /**
     * Удалить роль
     */
    public function deleteRole(Role $role): bool
    {
        // Защита от удаления системных ролей
        if (in_array($role->name, ['super_admin'])) {
            throw new \Exception('Нельзя удалить системную роль');
        }

        return $role->delete();
    }
}
