<?php

namespace App\Services\Tenants;

use App\Enums\OrderStatusEnum;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Tenant\Order;
use App\Models\Tenant\TenantUser;
use App\Services\Documents;
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
     * Начислить кэшбэк за заказ
     *
     * @throws ValidationException
     * @throws HttpException
     */
    public function addCashBackToOrder(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "order_id" => "required|exists:orders,id",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $orderId = $data["order_id"];

        $order = Order::query()
            ->where("id", $orderId)
            ->where("tenant_id", $tenant->id)
            ->first();

        if (!$order) {
            throw new HttpException(404, "Заказ не найден");
        }

        // Проверка на наличие поля is_cashback_crediting (если его нет в БД, эту проверку можно убрать)
        if (!empty($order->is_cashback_crediting)) {
            throw new HttpException(400, "По данному заказу уже был начислен CashBack");
        }

        // 🛠 ИСПРАВЛЕНО: используем tenant_user_id вместо несуществующего customer_id
        $client = TenantUser::query()->where("id", $order->tenant_user_id)->first();

        if (!$client) {
            throw new HttpException(404, "Клиент не найден");
        }

        $cashbackPercent = $tenant->cashback_percent ?? 5;
        $cashbackAmount = $order->summary_price * ($cashbackPercent / 100);

        if ($cashbackAmount > 0) {
            CashBackService::call()->addCashBack(
                amount: $cashbackAmount,
                description: "Кэшбэк за заказ #{$order->id}",
                user: $client,
                orderId: $order->id,
                withLevels: true
            );
        }

        // Если поле существует в БД, обновляем его
        if (in_array('is_cashback_crediting', $order->getFillable())) {
            $order->is_cashback_crediting = true;
            $order->save();
        }
    }

    /**
     * Регистрация данных доставщика
     *
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

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $birthday = Carbon::parse($data["birthday"] ?? $tenantUser->birthday ?? Carbon::now())->format("Y-m-d");

        $tenantUser->name = $data["name"] ?? $tenantUser->name;
        $tenantUser->phone = $data["phone"] ?? $tenantUser->phone;
        $tenantUser->email = $data["email"] ?? $tenantUser->email;
        $tenantUser->birthday = $birthday;
        $tenantUser->city = $data["city"] ?? $tenantUser->city;
        $tenantUser->country = $data["country"] ?? $tenantUser->country;
        $tenantUser->address = $data["address"] ?? $tenantUser->address;
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
                    'params' => json_decode($document->params ?? '[]', true),
                    'verified_at' => null,
                ]
            );
        }
    }

    /**
     * Сохранение координат доставщика в активные заказы
     *
     * @throws HttpException
     */
    public function storeCoordsToOrder(float $lat = 0, float $lon = 0): bool
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $orders = Order::query()
            ->where('tenant_id', $tenant->id)
            ->where('deliveryman_id', $tenantUser->id)
            ->whereNotIn('status', [OrderStatusEnum::Completed->value, OrderStatusEnum::Decline->value])
            ->get();

        if ($orders->isEmpty()) {
            return false;
        }

        foreach ($orders as $order) {
            $order->deliveryman_latitude = $lat;
            $order->deliveryman_longitude = $lon;
            $order->save();
        }

        return true;
    }

    /**
     * Принятие заказа доставщиком
     *
     * @throws HttpException
     */
    public function acceptOrder($orderId): bool
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $order = Order::query()
            ->where("id", $orderId)
           // ->where("tenant_id", $tenant->id)
            ->first();


        if (is_null($order)) {
            return false;
        }

        $deliverymanInfo = [
            "name" => $tenantUser->name ?? "Курьер #{$tenantUser->id}",
            "phone" => $tenantUser->phone ?? '-',
            "documents" => [],
        ];

        $order->update([
            'deliveryman_id' => $tenantUser->id,
            // 🆕 Передаем массив, который Laravel благодаря касту сам превратит в валидный JSON
            'delivery_service_info' => [
                'name' => $tenant->name ?? $tenant->title ?? 'Служба доставки'
            ],
            'deliveryman_info' => $deliverymanInfo,
            'status' => OrderStatusEnum::InDelivery->value,
        ]);

        return true;
    }

    /**
     * Подтверждение доставки
     * (Основная логика гео-проверки теперь в DeliverymanController,
     * здесь оставлен базовый метод для совместимости, если он вызывается из других мест)
     */
    public function confirmDelivery($orderId): void
    {
        $order = Order::find($orderId);
        if ($order) {
            $order->status = OrderStatusEnum::Completed->value;
            $order->delivered_at = now();
            $order->save();
        }
    }

    /**
     * Отмена заказа
     *
     * @throws HttpException
     */
    public function declineOrder($orderId): void
    {
        $tenant = app('tenant');

        $order = Order::query()
            ->where("id", $orderId)
            ->where("tenant_id", $tenant->id)
            ->first();

        if (is_null($order)) {
            throw new HttpException(404, "Заказ не найден!");
        }

        $order->status = OrderStatusEnum::Decline->value;
        $order->save();
    }

    /**
     * Получение заказа
     *
     * @throws HttpException
     */
    public function getOrder($orderId): OrderResource
    {
        $tenant = app('tenant');

        $order = Order::query()
            ->where("tenant_id", $tenant->id)
            ->where("id", $orderId)
            ->first();

        if (is_null($order)) {
            throw new HttpException(404, "Заказ не найден!");
        }

        return new OrderResource($order);
    }

    /**
     * Повтор заказа
     *
     * @throws HttpException
     * @throws ValidationException
     */
    public function repeatOrder(array $data): array
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "id" => "required",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $order = Order::query()
            ->where("id", $data["id"])
            ->where("tenant_id", $tenant->id)
            ->firstOrFail();

        $details = $order->product_details["products"] ?? [];

        if (empty($details)) {
            throw new HttpException(404, "Продукты не найдены");
        }

        $ids = array_values(\Illuminate\Support\Collection::make($details)->pluck("id")->toArray());

        BasketService::call()->clearBasket();

        foreach ($ids as $id) {
            BasketService::call()->addAndIncrementProduct([
                "product_id" => $id
            ]);
        }

        return BasketService::call()->productsInBasket();
    }

    /**
     * Смена статуса заказа
     *
     * @throws HttpException
     */
    public function changeStatusOrder($orderId, $status = 0): void
    {
        $tenant = app('tenant');

        $order = Order::query()
            ->where("id", $orderId)
            ->where("tenant_id", $tenant->id)
            ->first();

        if (is_null($order)) {
            throw new HttpException(404, "Заказ не найден!");
        }

        $order->status = $status ?? 0;
        $order->save();

        // Уведомление клиента о смене статуса автоматически сработает через OrderObserver
    }

    /**
     * Список заказов
     *
     * @throws HttpException
     * @throws ValidationException
     */
    public function orderList(array $data, $size = 30, $needAll = false): OrderCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "search" => "nullable|string",
            "order_by" => "nullable|string",
            "direction" => "nullable|in:asc,desc",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $search = $data["search"] ?? null;
        $orderBy = $data["order_by"] ?? "id";
        $direction = $data["direction"] ?? "desc";
        $tenantUserId = $data["tenant_user_id"] ?? null;

        $orders = Order::query()->where("tenant_id", $tenant->id);

        if (!is_null($search)) {
            $orders = $orders->where(function($q) use ($search) {
                $q->where("id", "like", "%$search%")
                    ->orWhere("receiver_name", "like", "%$search%")
                    ->orWhere("receiver_phone", "like", "%$search%");
            });
        }

        if (!$needAll || !is_null($tenantUserId)) {
            $orders = $orders->where("tenant_user_id", $tenantUserId ?? $tenantUser->id);
        }

        $orders = $orders->orderBy($orderBy, $direction)->paginate($size);

        return new OrderCollection($orders);
    }

    /**
     * Отправка СБП инвойса
     *
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

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $order = Order::query()
            ->where("id", $data["order_id"])
            ->where("tenant_id", $tenant->id)
            ->first();

        if (is_null($order)) {
            throw new HttpException(404, "Заказ не найден");
        }

        // Интеграция с СБП — здесь должен быть реальный вызов платежного шлюза
        return [
            "status" => "pending",
            "order_id" => $order->id,
            "amount" => $data["amount"],
        ];
    }

    /**
     * Расчет стоимости доставки
     *
     * @throws ValidationException
     */
    public function getDeliveryPrice(array $data): array
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

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
