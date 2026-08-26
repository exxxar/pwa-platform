<?php

namespace App\Http\Controllers\Admin\Global;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Global\StoreAdminUserRequest;
use App\Http\Requests\Admin\Global\UpdateAdminUserRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\Admin\User;
use App\Services\Admin\Global\AdminUserService;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    protected AdminUserService $adminUserService;

    public function __construct(AdminUserService $adminUserService)
    {
        $this->adminUserService = $adminUserService;
    }

    /**
     * Список глобальных админов
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search']);
        $perPage = $request->input('per_page', 15);

        $admins = $this->adminUserService->getAdmins($filters, $perPage);

        return AdminUserResource::collection($admins);
    }

    /**
     * Создание нового админа
     */
    public function store(StoreAdminUserRequest $request)
    {
        $this->authorize('create', User::class);

        $admin = $this->adminUserService->createAdmin($request->validated());

        return (new AdminUserResource($admin))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Просмотр конкретного админа
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load('roles.permissions');

        return new AdminUserResource($user);
    }

    /**
     * Обновление админа
     */
    public function update(UpdateAdminUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $admin = $this->adminUserService->updateAdmin($user, $request->validated());

        return new AdminUserResource($admin);
    }

    /**
     * Удаление админа
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $this->adminUserService->deleteAdmin($user);

        return response()->json([
            'success' => true,
            'message' => 'Админ успешно удален',
        ]);
    }
}
