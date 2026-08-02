<?php

namespace App\Http\Controllers\Tenant;

use App\Exports\AdminOrdersExport;
use App\Http\Controllers\Controller;

use App\Models\Tenant\Order;
use App\Services\OrderService;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;


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
        // ⚠️ ВАЖНО: Раскомментируйте и настройте фильтр статусов!
        // В вашем dd у всех заказов "status" => 0. Если 0 - это "отменен" или "черновик",
        // вы показываете клиентам некорректные данные.
        $recentOrders = Order::where('tenant_id', $tenant->id)
            // ->whereIn('status', [OrderStatusEnum::NewOrder->value, OrderStatusEnum::Completed->value])
            ->latest()
            ->limit(10)
            ->get()
            ->shuffle() // Для 10 записей shuffle в памяти действительно быстр и безопасен
            ->take(5);

        // 2. Форматируем данные для фронтенда
        $formattedOrders = $recentOrders->map(function ($order) {
            $extractedItems = [];
            $productDetails = $order->product_details;

            if (!empty($productDetails)) {
                // ✅ ИСПРАВЛЕНИЕ: Нормализация структуры JSON
                // Если ключ 'products' находится на верхнем уровне, оборачиваем весь массив в еще один массив,
                // чтобы привести его к единому формату "массив групп".
                if (isset($productDetails['products']) && is_array($productDetails['products'])) {
                    $productDetails = [$productDetails];
                }

                // Теперь мы гарантированно работаем со списком групп
                if (is_array($productDetails)) {
                    foreach ($productDetails as $detailGroup) {
                        if (is_array($detailGroup) && isset($detailGroup['products']) && is_array($detailGroup['products'])) {
                            foreach ($detailGroup['products'] as $productData) {
                                $extractedItems[] = [
                                    'product' => [
                                        'title' => $productData['name'] ?? 'Товар',
                                    ],
                                    // ✅ Приводим к int, чтобы фронтенд всегда получал число, а не строку
                                    'quantity' => (int) ($productData['count'] ?? 1),
                                ];
                            }
                        }
                    }
                }
            }

            return [
                'id' => $order->id,
                // ✅ Добавлена проверка на случай, если created_at по какой-то причине null
                'created_at' => $order->created_at ? $order->created_at->toISOString() : null,
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
