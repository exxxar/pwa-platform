<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductCollection;
use App\Models\Tenant\Collection;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\TenantUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderService
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
     * @throws ValidationException
     * @throws HttpException
     */
    public function addCashBackToOrder(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "order_id" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $orderId = $data["order_id"] ?? null;

        $order = Order::query()
            ->where("id", $orderId)
            ->where("tenant_id", $tenant->id)
            ->first();

        if (is_null($order))
            throw new HttpException(404, "Заказ не найден");

        if (($order->is_cashback_crediting ?? false) === true)
            throw new HttpException(400, "По данному заказу уже был начислен автоматический CashBack");

        $order->is_cashback_crediting = true;
        $order->save();

        $client = TenantUser::query()->where("id", $order->customer_id)->first();

        if (is_null($client))
            throw new HttpException(404, "Клиент не найден");

        // Начисление кэшбэка — вынесите в отдельный CashBackService при необходимости
        // CashBackService::call()->add([
        //     "user_id"    => $client->id,
        //     "amount"     => $order->summary_price,
        //     "need_review"=> true,
        //     "info"       => "Автоматическое начисление CashBack после заказа",
        // ]);
    }

    /**
     * @throws ValidationException
     */
    public function registerDeliveryman(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "name" => "required",
            "phone" => "required",
            "documents.*.title" => "required",
            "documents.*.description" => "required",
            "documents.*.type" => "required",
            "documents.*.file_id" => "required",
            "documents.*.params" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $birthday = Carbon::parse($data["birthday"] ?? $tenantUser->birthday ?? Carbon::now())->format("Y-m-d");

        $tenantUser->name = $data["name"] ?? $tenantUser->name ?? null;
        $tenantUser->phone = $data["phone"] ?? $tenantUser->phone ?? null;
        $tenantUser->email = $data["email"] ?? $tenantUser->email ?? null;
        $tenantUser->birthday = $birthday;
        $tenantUser->city = $data["city"] ?? $tenantUser->city ?? null;
        $tenantUser->country = $data["country"] ?? $tenantUser->country ?? null;
        $tenantUser->address = $data["address"] ?? $tenantUser->address ?? null;
        $tenantUser->sex = (bool)($data["sex"] ?? false);
        $tenantUser->age = Carbon::now()->year - Carbon::parse($birthday)->year;
        $tenantUser->save();

        $documents = $data["documents"] ?? [];

        foreach ($documents as $document) {
            $document = (object)$document;

            Documents::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'tenant_user_id' => $tenantUser->id,
                    'file_id' => $document->file_id,
                ],
                [
                    'title' => $document->title ?? null,
                    'description' => $document->description ?? null,
                    'type' => $document->type ?? 0,
                    'params' => json_decode($document->params ?? '[]'),
                    'verified_at' => null,
                ]
            );
        }
    }

    /**
     * @throws HttpException
     */
    public function storeCoordsToOrder(float $lat = 0, float $lon = 0): bool
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $orders = Order::query()
            ->where('tenant_id', $tenant->id)
            ->where('deliveryman_id', $tenantUser->id)
            ->whereNot('status', OrderStatusEnum::Completed->value)
            ->get();

        if ($orders->isEmpty())
            return false;

        foreach ($orders as $order) {
            $order->deliveryman_latitude = $lat;
            $order->deliveryman_longitude = $lon;
            $order->save();
        }

        return true;
    }

    /**
     * @throws HttpException
     */
    public function acceptOrder($orderId): bool
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $order = Order::query()
            ->where("id", $orderId)
            ->where("tenant_id", $tenant->id)
            ->first();

        if (is_null($order))
            return false;

        $documents = Documents::query()
            ->where("tenant_id", $tenant->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->whereNotNull("verified_at")
            ->get();

        $deliverymanInfo = (object)[
            "name" => $tenantUser->name ?? $tenantUser->id,
            "phone" => $tenantUser->phone ?? '-',
            "documents" => $documents->pluck('params')->toArray(),
        ];

        $order->update([
            'deliveryman_id' => $tenantUser->id,
            'delivery_service_info' => $tenant->title ?? $tenant->id,
            'deliveryman_info' => $deliverymanInfo,
            'delivery_price' => 0,
            'delivery_range' => 0,
            'status' => OrderStatusEnum::InDelivery->value,
        ]);

        return true;
    }

    public function confirmDelivery($orderId): void
    {
        // Подтвердить можно в случае если координаты доставщика и заказчика близко друг к другу
        // Выставить рейтинг
    }

    /**
     * @throws HttpException
     */
    public function declineOrder($orderId): void
    {
        $tenant = app('tenant');

        $order = Order::query()
            ->where("id", $orderId)
            ->where("tenant_id", $tenant->id)
            ->first();

        if (is_null($order))
            throw new HttpException(404, "Заказ не найден!");

        $order->status = OrderStatusEnum::Decline->value;
        $order->save();
    }

    /**
     * @throws HttpException
     */
    public function getOrder($orderId): OrderResource
    {
        $tenant = app('tenant');

        $order = Order::query()
            ->where("tenant_id", $tenant->id)
            ->where("id", $orderId)
            ->orderBy("created_at", "DESC")
            ->first();

        if (is_null($order))
            throw new HttpException(404, "Заказ не найден!");

        return new OrderResource($order);
    }

    /**
     * @throws HttpException
     * @throws ValidationException
     */
    public function repeatOrder(array $data): \App\Http\Resources\BasketCollection
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "id" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $order = Order::query()
            ->where("id", $data["id"])
            ->firstOrFail();

        $details = $order->product_details["products"] ?? [];

        if (empty($details))
            throw new HttpException(404, "Продукты не найдены");

        $ids = array_values(\Illuminate\Support\Collection::make($details)
            ->pluck("id")->toArray());

        BasketService::call()
            ->clearBasket();

        foreach ($ids as $id)
            BasketService::call()
                ->addAndIncrementProduct([
                    "product_id" => $id
                ]);

      /*  $products = Product::query()
            // ->where("tenant_id", $tenant->id)
            ->where("in_stop_list", false)
            ->whereIn("id", $ids)
            ->get();*/

        return BasketService::call()
            ->productsInBasket();
    }

    /**
     * @throws HttpException
     */
    public function changeStatusOrder($orderId, $status = 0): void
    {
        $tenant = app('tenant');

        $order = Order::query()
            ->where("id", $orderId)
            ->where("tenant_id", $tenant->id)
            ->first();

        if (is_null($order))
            throw new HttpException(404, "Заказ не найден!");

        $order->status = $status ?? 0;
        $order->save();

        // Уведомление клиента о смене статуса можно реализовать через NotificationService / Mail
    }

    /**
     * @throws HttpException
     * @throws ValidationException
     */
    public function orderList(array $data, $size = 30, $needAll = false): OrderCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "search" => "",
            "order_by" => "",
            "direction" => "",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $search = $data["search"] ?? null;
        $orderBy = $data["order_by"] ?? "id";
        $direction = $data["direction"] ?? "desc";
        $tenantUserId = $data["tenant_user_id"] ?? null;

        $orders = Order::query()
            ->where("tenant_id", $tenant->id);

        if (!is_null($search))
            $orders = $orders->where("id", "like", "%$search%");

        if (!$needAll || !is_null($tenantUserId))
            $orders = $orders->where("tenant_user_id", $tenantUserId ?? $tenantUser->id);

        $orders = $orders
            ->orderBy($orderBy, $direction)
            ->paginate($size);

        return new OrderCollection($orders);
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function sendSBPInvoice(array $data): array
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "order_id" => "required",
            "amount" => "required|numeric",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $order = Order::query()
            ->where("id", $data["order_id"])
            ->where("tenant_id", $tenant->id)
            ->first();

        if (is_null($order))
            throw new HttpException(404, "Заказ не найден");

        // Интеграция с СБП — замените на реальный вызов платёжного шлюза
        return [
            "status" => "pending",
            "order_id" => $order->id,
            "amount" => $data["amount"],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function getDeliveryPrice(array $data): array
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $basePrice = $tenant->settings["delivery_base_price"] ?? 200;
        $pricePerKm = $tenant->settings["delivery_price_per_km"] ?? 50;

        return [
            "price" => $basePrice,
            "per_km" => $pricePerKm,
            "currency" => "RUB",
        ];
    }

    public function printOrderToPdf()
    {
        // Генерация PDF документа с заказом
    }

    public function printStatisticToExcel()
    {
        // Генерация xls-документа со статистикой по доставщику
    }

    public function globalStatistic()
    {
        // Глобальная статистика доставки
    }

    public function personalStatistic()
    {
        // Персональная статистика доставщика
    }

    public function removeOrder()
    {
        // Удаление заказа владельцем заказа
    }
}
