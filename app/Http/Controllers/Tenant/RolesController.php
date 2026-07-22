<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantPermission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolesController extends Controller
{

    public function getAvailablePermissions()
    {
        return response()->json([
            'permissions' => config('permissions.map')
        ]);
    }

    /**
     * Получить список ролей с подсчетом пользователей и прав
     */
    public function index()
    {
        $tenant = app('tenant');

        $roles = TenantRole::where('tenant_id', $tenant->id)
            ->withCount(['permissions', 'users'])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json(['data' => $roles]);
    }

    /**
     * Получить все доступные разрешения (permissions) для текущего тенанта
     */
    public function permissions()
    {
        $tenant = app('tenant');

        $permissions = TenantPermission::where('tenant_id', $tenant->id)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'label']);

        return response()->json(['data' => $permissions]);
    }

    /**
     * Создать новую роль
     */
    public function store(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/', // Только латиница, цифры и подчеркивание
                Rule::unique('tenant_roles', 'name')->where('tenant_id', $tenant->id)
            ],
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:tenant_permissions,id',
        ]);

        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'label' => $validated['label'],
            'name' => $validated['name'],
        ]);

        // Синхронизируем разрешения
        if (!empty($validated['permission_ids'])) {
            // Важно: передаем tenant_id в pivot, как мы обсуждали ранее
            $syncData = [];
            foreach ($validated['permission_ids'] as $permId) {
                $syncData[$permId] = ['tenant_id' => $tenant->id];
            }
            $role->permissions()->sync($syncData);
        }

        $role->loadCount(['permissions', 'users']);

        return response()->json(['data' => $role], 201);
    }

    /**
     * Обновить существующую роль
     */
    public function update(Request $request, int $roleId)
    {
        $tenant = app('tenant');

        $role = TenantRole::where('tenant_id', $tenant->id)->findOrFail($roleId);

        // Запрещаем редактировать системные роли через этот метод (или ограничиваем поля)
        if (in_array($role->name, ['super_admin', 'user'])) {
            return response()->json(['message' => 'Системные роли нельзя изменять'], 403);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('tenant_roles', 'name')->where('tenant_id', $tenant->id)->ignore($role->id)
            ],
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:tenant_permissions,id',
        ]);

        $role->update([
            'label' => $validated['label'],
            'name' => $validated['name'],
        ]);

        if (isset($validated['permission_ids'])) {
            $syncData = [];
            foreach ($validated['permission_ids'] as $permId) {
                $syncData[$permId] = ['tenant_id' => $tenant->id];
            }
            $role->permissions()->sync($syncData);
        }

        $role->loadCount(['permissions', 'users']);

        return response()->json(['data' => $role]);
    }

    /**
     * Удалить роль
     */
    public function destroy(int $roleId)
    {
        $tenant = app('tenant');

        $role = TenantRole::where('tenant_id', $tenant->id)->findOrFail($roleId);

        // Защита от удаления системных ролей
        if (in_array($role->name, ['super_admin', 'user'])) {
            return response()->json(['message' => 'Системные роли нельзя удалять'], 403);
        }

        // Проверка: есть ли пользователи с этой ролью
        if ($role->users()->count() > 0) {
            return response()->json([
                'message' => 'Невозможно удалить роль, так как она назначена пользователям.'
            ], 422);
        }

        $role->delete();

        return response()->json(['success' => true]);
    }
}
