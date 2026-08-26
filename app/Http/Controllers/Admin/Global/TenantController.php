<?php

namespace App\Http\Controllers\Admin\Global;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Global\StoreTenantRequest;
use App\Http\Requests\Admin\Global\UpdateTenantRequest;
use App\Http\Requests\Admin\Global\UpdateTenantBalanceRequest;
use App\Http\Resources\Admin\TenantResource;
use App\Models\Tenant\Tenant;
use App\Services\Admin\Global\TenantManagementService;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    protected TenantManagementService $tenantService;

    public function __construct(TenantManagementService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Список тенантов с фильтрацией и пагинацией
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Tenant::class);

        $filters = $request->only(['search', 'is_active', 'plan_slug', 'sort_by', 'sort_dir']);
        $perPage = $request->input('per_page', 15);

        $tenants = $this->tenantService->getTenants($filters, $perPage);

        return TenantResource::collection($tenants);
    }

    /**
     * Создание нового тенанта
     */
    public function store(StoreTenantRequest $request)
    {
        $this->authorize('create', Tenant::class);

        $tenant = $this->tenantService->createTenant($request->validated());

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Просмотр конкретного тенанта
     */
    public function show(Request $request, Tenant $tenant)
    {
        $this->authorize('view', $tenant);

        // Загружаем дополнительную статистику, если запрошено
        if ($request->boolean('with_stats')) {
            $stats = $this->tenantService->getTenantStats($tenant);
            $tenant->users_count = $stats['users_count'];
            $tenant->active_users_count = $stats['active_users_count'];
            $tenant->orders_count = $stats['orders_count'];
        }

        return new TenantResource($tenant);
    }

    /**
     * Обновление тенанта
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        $tenant = $this->tenantService->updateTenant($tenant, $request->validated());

        return new TenantResource($tenant);
    }

    /**
     * Удаление тенанта
     */
    public function destroy(Tenant $tenant)
    {
        $this->authorize('delete', $tenant);

        $this->tenantService->deleteTenant($tenant);

        return response()->json([
            'success' => true,
            'message' => 'Тенант успешно удален',
        ]);
    }

    /**
     * Переключение статуса активности тенанта
     */
    public function toggleStatus(Tenant $tenant)
    {
        $this->authorize('toggleStatus', $tenant);

        $tenant = $this->tenantService->toggleStatus($tenant);

        return new TenantResource($tenant);
    }

    /**
     * Изменение баланса тенанта
     */
    public function updateBalance(UpdateTenantBalanceRequest $request, Tenant $tenant)
    {
        $this->authorize('updateBalance', $tenant);

        $amount = $request->input('amount');
        $reason = $request->input('reason', '');

        $tenant = $this->tenantService->updateBalance($tenant, $amount, $reason);

        return new TenantResource($tenant);
    }
}
