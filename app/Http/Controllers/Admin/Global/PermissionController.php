<?php

namespace App\Http\Controllers\Admin\Global;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Global\StorePermissionRequest;
use App\Http\Requests\Admin\Global\UpdatePermissionRequest;
use App\Http\Resources\Admin\PermissionResource;
use App\Models\Admin\Permission;
use App\Services\Admin\Global\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Список разрешений
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Permission::class);

        $filters = $request->only(['search']);
        $permissions = $this->permissionService->getPermissions($filters);

        return PermissionResource::collection($permissions);
    }

    /**
     * Создание разрешения
     */
    public function store(StorePermissionRequest $request)
    {
        $this->authorize('create', Permission::class);

        $permission = $this->permissionService->createPermission($request->validated());

        return (new PermissionResource($permission))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Просмотр разрешения
     */
    public function show(Permission $permission)
    {
        $this->authorize('view', $permission);

        return new PermissionResource($permission);
    }

    /**
     * Обновление разрешения
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $this->authorize('update', $permission);

        $permission = $this->permissionService->updatePermission($permission, $request->validated());

        return new PermissionResource($permission);
    }

    /**
     * Удаление разрешения
     */
    public function destroy(Permission $permission)
    {
        $this->authorize('delete', $permission);

        $this->permissionService->deletePermission($permission);

        return response()->json([
            'success' => true,
            'message' => 'Разрешение успешно удалено',
        ]);
    }
}
