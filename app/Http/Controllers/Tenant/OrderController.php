<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Services\OrderService;
use App\Services\ReviewService;
use Illuminate\Http\Request;

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
