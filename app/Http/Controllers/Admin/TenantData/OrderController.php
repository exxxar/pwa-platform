<?php

namespace App\Http\Controllers\Admin\TenantData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantData\UpdateOrderStatusRequest;
use App\Http\Resources\Admin\OrderResource;
use App\Models\Tenant\Order;
use App\Services\Admin\TenantData\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Список заказов с фильтрацией и пагинацией
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->only([
            'tenant_id',
            'status',
            'tenant_user_id',
            'payed_from',
            'payed_to',
            'sort_by',
            'sort_dir',
        ]);
        $perPage = $request->input('per_page', 15);

        $orders = $this->orderService->getOrders($filters, $perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Просмотр заказа с детальной информацией
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order = $this->orderService->getOrderWithDetails($order);

        return new OrderResource($order);
    }

    /**
     * Обновление статуса заказа
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $this->authorize('updateStatus', $order);

        $status = $request->input('status');
        $order = $this->orderService->updateStatus($order, $status);

        return new OrderResource($order);
    }
}
