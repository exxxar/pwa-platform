<?php

namespace App\Http\Controllers\Admin\TenantData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantData\UpdateTenantUserRequest;
use App\Http\Requests\Admin\TenantData\ToggleBlockUserRequest;
use App\Http\Requests\Admin\TenantData\GrantVipRequest;
use App\Http\Resources\Admin\TenantUserResource;
use App\Models\Tenant\TenantUser;
use App\Services\Admin\TenantData\TenantUserService;
use Illuminate\Http\Request;

class TenantUserController extends Controller
{
    protected TenantUserService $userService;

    public function __construct(TenantUserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Список пользователей тенанта
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', TenantUser::class);

        $filters = $request->only(['tenant_id', 'search', 'is_active', 'is_vip', 'sort_by', 'sort_dir']);
        $perPage = $request->input('per_page', 15);

        $users = $this->userService->getUsers($filters, $perPage);

        return TenantUserResource::collection($users);
    }

    /**
     * Просмотр пользователя с детальной информацией
     */
    public function show(TenantUser $user)
    {
        $this->authorize('view', $user);

        $user = $this->userService->getUserWithDetails($user);

        return new TenantUserResource($user);
    }

    /**
     * Обновление пользователя
     */
    public function update(UpdateTenantUserRequest $request, TenantUser $user)
    {
        $this->authorize('update', $user);

        $user = $this->userService->updateUser($user, $request->validated());

        return new TenantUserResource($user);
    }

    /**
     * Удаление пользователя
     */
    public function destroy(TenantUser $user)
    {
        $this->authorize('delete', $user);

        $this->userService->deleteUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно удален',
        ]);
    }

    /**
     * Блокировка/разблокировка пользователя
     */
    public function toggleBlock(ToggleBlockUserRequest $request, TenantUser $user)
    {
        $this->authorize('toggleBlock', $user);

        $message = $request->input('message');
        $user = $this->userService->toggleBlock($user, $message);

        return new TenantUserResource($user);
    }

    /**
     * Выдача VIP статуса
     */
    public function grantVip(GrantVipRequest $request, TenantUser $user)
    {
        $this->authorize('grantVip', $user);

        $days = $request->input('days');
        $user = $this->userService->grantVip($user, $days);

        return new TenantUserResource($user);
    }

    /**
     * Отзыв VIP статуса
     */
    public function revokeVip(TenantUser $user)
    {
        $this->authorize('revokeVip', $user);

        $user = $this->userService->revokeVip($user);

        return new TenantUserResource($user);
    }
}
