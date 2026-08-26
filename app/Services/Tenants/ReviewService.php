<?php

namespace App\Services\Tenants;

use App\Http\Resources\ReviewCollection;
use App\Http\Resources\ReviewResource;
use App\Models\Tenant\Order;
use App\Models\Tenant\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReviewService
{
    public static function call(): self
    {
        return app(self::class);
    }

    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    /**
     * Проверить, может ли пользователь оставить отзыв к заказу
     */
    public function canReviewOrder($orderId): array
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $order = Order::query()
            ->where('id', $orderId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $tenantUser->id)
            ->first();

        if (!$order) {
            return [
                'can_review' => false,
                'reason' => 'Заказ не найден',
            ];
        }

        // Проверяем статус заказа (только выполненные)
        if (!in_array($order->status, [2, 'completed', 'delivered'])) {
            return [
                'can_review' => false,
                'reason' => 'Отзыв можно оставить только после выполнения заказа',
            ];
        }

        // Проверяем, есть ли уже отзыв
        $existingReview = Review::query()
            ->where('order_id', $orderId)
            ->where('tenant_user_id', $tenantUser->id)
            ->first();

        if ($existingReview) {
            return [
                'can_review' => false,
                'has_review' => true,
                'review' => new ReviewResource($existingReview),
                'reason' => 'Отзыв уже оставлен',
            ];
        }

        return [
            'can_review' => true,
            'has_review' => false,
        ];
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function storeReview(array $data)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            'product_id' => 'nullable|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'text' => 'required|string|max:1000',
            'title' => 'nullable|string|max:255',
            'images' => 'nullable|array|max:5',
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        // Проверка: хотя бы одна связь должна быть
        if (empty($data['product_id']) && empty($data['order_id'])) {
            throw new HttpException(400, 'Необходимо указать product_id или order_id');
        }

        // Проверка уникальности отзыва к заказу
        if (!empty($data['order_id'])) {
            $existingReview = Review::query()
                ->where('order_id', $data['order_id'])
                ->where('tenant_user_id', $tenantUser->id)
                ->first();

            if ($existingReview) {
                throw new HttpException(400, 'Вы уже оставили отзыв к этому заказу');
            }

            // Проверка владельца заказа
            $order = Order::query()
                ->where('id', $data['order_id'])
                ->where('tenant_id', $tenant->id)
                ->where('tenant_user_id', $tenantUser->id)
                ->first();

            if (!$order) {
                throw new HttpException(403, 'Вы не можете оставить отзыв к этому заказу');
            }
        }

        $review = Review::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $tenantUser->id,
            'product_id' => $data['product_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'rating' => $data['rating'],
            'text' => $data['text'],
            'title' => $data['title'] ?? null,
            'images' => $data['images'] ?? [],
            'status' => Review::STATUS_PENDING,
        ]);

        return new ReviewResource($review->load(['tenantUser', 'product', 'order']));
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function updateReview($reviewId, array $data)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $review = Review::query()
            ->where('id', $reviewId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $tenantUser->id)
            ->first();

        if (!$review) {
            throw new HttpException(404, 'Отзыв не найден');
        }

        $validator = Validator::make($data, [
            'rating' => 'sometimes|integer|min:1|max:5',
            'text' => 'sometimes|string|max:1000',
            'title' => 'nullable|string|max:255',
            'images' => 'nullable|array|max:5',
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $review->update([
            'rating' => $data['rating'] ?? $review->rating,
            'text' => $data['text'] ?? $review->text,
            'title' => $data['title'] ?? $review->title,
            'images' => $data['images'] ?? $review->images,
        ]);

        return new ReviewResource($review->load(['tenantUser', 'product', 'order']));
    }

    /**
     * @throws HttpException
     */
    public function deleteReview($reviewId): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $review = Review::query()
            ->where('id', $reviewId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $tenantUser->id)
            ->first();

        if (!$review) {
            throw new HttpException(404, 'Отзыв не найден');
        }

        $review->delete();
    }

    /**
     * @throws ValidationException
     */
    public function getReviews(array $data = [], $size = 30)
    {
        $tenant = app('tenant');

        $search = $data['search'] ?? null;
        $orderBy = $data['order_by'] ?? 'created_at';
        $dir = $data['direction'] ?? 'desc';
        $status = $data['status'] ?? null;

        $reviews = Review::query()
            ->where('tenant_id', $tenant->id)
            ->with(['tenantUser', 'product', 'order']);

        if (!is_null($search))
            $reviews = $reviews->where('text', 'like', "%$search%");

        if (!is_null($status))
            $reviews = $reviews->where('status', $status);

        return new ReviewCollection(
            $reviews->orderBy($orderBy, $dir)->paginate($size)
        );
    }

    /**
     * @throws ValidationException
     */
    public function getReviewsByProductId(array $data, $size = 30)
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $reviews = Review::query()
            ->where('tenant_id', $tenant->id)
            ->where('product_id', $data['product_id'])
            ->approved()
            ->with(['tenantUser'])
            ->orderBy('created_at', 'desc')
            ->paginate($size);

        return new ReviewCollection($reviews);
    }
}
