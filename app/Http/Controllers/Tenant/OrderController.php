<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Models\Tenant\Order;
use App\Services\OrderService;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // ===== ORDERS =====

    public function getOrders(Request $request)
    {
        return OrderService::call()
            ->orderList(
                $request->all(),
                $request->get("size") ?? config('app.results_per_page')
            );
    }

    public function getRandomRecentOrders()
    {
        $tenant = app('tenant');

        // 1. Берем 10 последних заказов в активных статусах
        // ⚠️ ВАЖНО: Замените [1, 2, 3] на реальные значения ваших статусов из OrderStatusEnum
        // (например, [OrderStatusEnum::NewOrder->value, OrderStatusEnum::Completed->value])
        // Мы не показываем отмененные заказы (status = cancelled), чтобы не отпугивать клиентов.
        $recentOrders = Order::where('tenant_id', $tenant->id)
           // ->whereIn('status', [2])
            ->latest()
            ->limit(10)
            ->get()
            ->shuffle() // Перемешиваем эти 10 заказов в памяти (это мгновенно)
            ->take(5);  // Берем 5 случайных

        // 2. Форматируем данные для фронтенда, доставая их из JSON-поля product_details
        $formattedOrders = $recentOrders->map(function ($order) {
            $extractedItems = [];

            // Парсим массив product_details
            if (!empty($order->product_details) && is_array($order->product_details)) {
                foreach ($order->product_details as $detailGroup) {
                    if (isset($detailGroup['products']) && is_array($detailGroup['products'])) {
                        foreach ($detailGroup['products'] as $productData) {
                            $extractedItems[] = [
                                'product' => [
                                    'title' => $productData['name'] ?? 'Товар',
                                ],
                                'quantity' => $productData['count'] ?? 1,
                            ];
                        }
                    }
                }
            }

            return [
                'id' => $order->id,
                'created_at' => $order->created_at->toISOString(),
                'total' => (float) $order->summary_price,
                'items' => $extractedItems,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedOrders->values()->toArray(),
        ]);
    }

    public function getAllOrders(Request $request)
    {
        return OrderService::call()
            ->orderList(
                $request->all(),
                $request->get("size") ?? config('app.results_per_page'),
                true
            );
    }

    public function sendSBPInvoice(Request $request)
    {
        $result = OrderService::call()->sendSBPInvoice($request->all());
        return response()->json($result);
    }

    public function repeatOrder(Request $request)
    {
        return OrderService::call()->repeatOrder($request->all());
    }

    public function declineOrder(Request $request)
    {
        OrderService::call()->declineOrder($request->get("order_id"));
        return response()->noContent();
    }

    public function changeStatusOrder(Request $request)
    {
        OrderService::call()->changeStatusOrder(
            $request->get("order_id"),
            $request->get("status", 0)
        );
        return response()->noContent();
    }

    public function loadOrderById(Request $request)
    {
        return OrderService::call()->getOrder($request->get("order_id"));
    }

    public function addCashBackToOrder(Request $request)
    {
        OrderService::call()->addCashBackToOrder($request->all());
        return response()->noContent();
    }

    public function getDeliveryPrice(Request $request)
    {
        return response()->json(
            OrderService::call()->getDeliveryPrice($request->all())
        );
    }

    // ===== REVIEWS =====

    public function getReviews(Request $request)
    {
        return ReviewService::call()->getReviews($request->all());
    }

    public function getReviewsByProductId(Request $request)
    {
        return ReviewService::call()->getReviewsByProductId($request->all());
    }

    public function notifyUser(Request $request)
    {
        ReviewService::call()->notifyUser($request->all());
        return response()->noContent();
    }

    /**
     * @throws ValidationException
     */
    public function canReviewOrder(Request $request): \Illuminate\Http\JsonResponse
    {
        $result = ReviewService::call()->canReviewOrder($request->get('order_id'));
        return response()->json($result);
    }

    /**
     * @throws ValidationException
     */
    public function storeReview(Request $request): \App\Http\Resources\ReviewResource
    {
        return ReviewService::call()->storeReview($request->all());
    }

    /**
     * @throws ValidationException
     */
    public function updateReview(Request $request, $id): \App\Http\Resources\ReviewResource
    {
        return ReviewService::call()->updateReview($id, $request->all());
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function deleteReview($id): \Illuminate\Http\Response
    {
        ReviewService::call()->deleteReview($id);
        return response()->noContent();
    }
}
