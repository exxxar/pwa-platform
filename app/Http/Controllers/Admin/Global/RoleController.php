<?php

namespace App\Http\Controllers\Admin\Global;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Global\StoreRoleRequest;
use App\Http\Requests\Admin\Global\UpdateRoleRequest;
use App\Http\Resources\Admin\RoleResource;
use App\Models\Role;
use App\Services\Admin\Global\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Список ролей
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        $roles = $this->roleService->getRoles();

        return RoleResource::collection($roles);
    }

    /**
     * Создание роли
     */
    public function store(StoreRoleRequest $request)
    {
        $this->authorize('create', Role::class);

        $role = $this->roleService->createRole($request->validated());

        return (new RoleResource($role))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Просмотр роли
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        $role->load('permissions');

        return new RoleResource($role);
    }

    /**
     * Обновление роли
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->authorize('update', $role);

        $role = $this->roleService->updateRole($role, $request->validated());

        return new RoleResource($role);
    }

    /**
     * Удаление роли
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        $this->roleService->deleteRole($role);

        return response()->json([
            'success' => true,
            'message' => 'Роль успешно удалена',
        ]);
    }
}
