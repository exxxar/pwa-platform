<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Facades\PaymentService;
use App\Models\Tenant\Order;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Models\Tenant\TenantUser;
use App\Services\Tenants\OrderDialogService;
use App\Services\Tenants\OrderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliverymanController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        // 🆕 Применяем фильтр магазинов
        $activeQuery = Order::where('deliveryman_id', $user->id)
            ->whereIn('status', [
                OrderStatusEnum::InDelivery->value,
                OrderStatusEnum::ReadyForDelivery->value,
                OrderStatusEnum::StartsCooking->value,
            ]);
        $this->applyShopFilter($activeQuery, $user);
        $activeOrdersCount = $activeQuery->count();

        $completedQuery = Order::where('deliveryman_id', $user->id)
            ->where('status', OrderStatusEnum::Completed->value);
        $this->applyShopFilter($completedQuery, $user);
        $completedOrdersCount = $completedQuery->count();

        $today = Carbon::today('Europe/Moscow')->format('Y-m-d');
        $availableQuery = Order::whereNull('deliveryman_id')
            ->whereIn('status', [
                OrderStatusEnum::NewOrder->value,
                OrderStatusEnum::StartsCooking->value,
                OrderStatusEnum::ReadyForDelivery->value
            ])
            ->whereDate('created_at', '>=', $today);
        $this->applyShopFilter($availableQuery, $user);
        $availableOrdersCount = $availableQuery->count();

        $earned = Order::where('deliveryman_id', $user->id)
            ->where('status', OrderStatusEnum::Completed->value)
            ->whereMonth('delivered_at', Carbon::now()->month)
            ->whereYear('delivered_at', Carbon::now()->year)
            ->sum('delivery_price') ?? 0.0;

        return response()->json([
            'success' => true,
            'data' => [
                'deliveryman' => [
                    'id' => $user->id,
                    'name' => $user->name ?? 'Курьер',
                    'phone' => $user->phone,
                    'status' => $user->is_online ? 'online' : 'offline',
                    'earned' => (float) $earned,
                    'active_orders_count' => $activeOrdersCount,
                    'available_orders_count' => $availableOrdersCount,
                    'completed_orders_count' => $completedOrdersCount,
                    'rating' => 4.9,
                ]
            ]
        ]);
    }

    public function availableOrders(Request $request): JsonResponse
    {
        // 🆕 Если даты не переданы, по умолчанию берем сегодняшний день
        $dateFrom = $request->input('date_from', Carbon::today('Europe/Moscow')->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::today('Europe/Moscow')->format('Y-m-d'));

        $orders = Order::query()
            ->whereNull('deliveryman_id')
            ->whereIn('status', [
                OrderStatusEnum::NewOrder->value,
                OrderStatusEnum::StartsCooking->value,
                OrderStatusEnum::ReadyForDelivery->value
            ])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->with(['tenant', 'location']);

        $this->applyShopFilter($orders, Auth::guard('tenant')->user());

        $orders = $orders->orderBy('created_at', 'desc')->limit(50)->get();

        $formatted = $orders->map(function ($order) {
            // ... (ваш существующий код маппинга без изменений)
            $loc = $order->location;
            $fullAddress = $loc ? trim(($loc->city ? $loc->city . ', ' : '') . $loc->address) : 'Адрес не указан';
            return [
                'id' => $order->id,
                'tenant_name' => $order->tenant?->name ?? 'Заведение',
                'address' => $fullAddress,
                'lat' => $loc?->lat,
                'lng' => $loc?->lng,
                'distance_km' => $order->delivery_range ?? 0,
                'order_price' => $order->summary_price,
                'delivery_price' => $order->delivery_price ?? 0,
                'created_at' => $order->created_at,
                'product_details' => $order->product_details,
                'receiver_name' => $order->receiver_name,
                'receiver_phone' => $order->receiver_phone,
                'dialog_id' => $order->dialog_id,
                'info' => $order->delivery_note,
            ];
        });

        return response()->json(['success' => true, 'data' => $formatted]);
    }


    public function activeOrders(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', Carbon::today('Europe/Moscow')->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::today('Europe/Moscow')->format('Y-m-d'));
        $user = Auth::guard('tenant')->user();

        $orders = Order::query()
            ->where('deliveryman_id', $user->id)
            ->whereIn('status', [
                OrderStatusEnum::InDelivery->value,
                OrderStatusEnum::ReadyForDelivery->value,
                OrderStatusEnum::StartsCooking->value,
            ])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->with(['tenant', 'dialog', 'location']);

        $this->applyShopFilter($orders, $user);
        $orders = $orders->orderBy('created_at', 'desc')->get();

        $formatted = $orders->map(function ($order) {
            // ... (ваш существующий код маппинга без изменений)
            $loc = $order->location;
            $fullAddress = $loc ? trim(($loc->city ? $loc->city . ', ' : '') . $loc->address) : 'Адрес не указан';
            return [
                'id' => $order->id,
                'status' => $order->status,
                'tenant' => ['name' => $order->tenant?->name ?? 'Заведение'],
                'address' => $fullAddress,
                'lat' => $loc?->lat,
                'lng' => $loc?->lng,
                'receiver_name' => $order->receiver_name,
                'receiver_phone' => $order->receiver_phone,
                'product_details' => $order->product_details,
                'summary_price' => $order->summary_price,
                'delivery_price' => $order->delivery_price ?? 0,
                'dialog_id' => $order->dialog_id,
                'created_at' => $order->created_at,
                'info' => $order->delivery_note,
            ];
        });

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function completedOrders(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', Carbon::today('Europe/Moscow')->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::today('Europe/Moscow')->format('Y-m-d'));
        $user = Auth::guard('tenant')->user();

        $orders = Order::query()
            ->where('deliveryman_id', $user->id)
            ->where('status', OrderStatusEnum::Completed->value)
            // Для завершенных логичнее фильтровать по дате доставки, но created_at тоже подойдет
            ->whereDate('delivered_at', '>=', $dateFrom)
            ->whereDate('delivered_at', '<=', $dateTo)
            ->with(['tenant', 'location']);

        $this->applyShopFilter($orders, $user);
        $orders = $orders->orderBy('delivered_at', 'desc')->limit(50)->get();

        $formatted = $orders->map(function ($order) {
            // ... (ваш существующий код маппинга без изменений)
            $loc = $order->location;
            $fullAddress = $loc ? trim(($loc->city ? $loc->city . ', ' : '') . $loc->address) : 'Адрес не указан';
            return [
                'id' => $order->id,
                'status' => $order->status,
                'tenant' => ['name' => $order->tenant?->name ?? 'Заведение'],
                'address' => $fullAddress,
                'lat' => $loc?->lat,
                'lng' => $loc?->lng,
                'receiver_name' => $order->receiver_name,
                'receiver_phone' => $order->receiver_phone,
                'product_details' => $order->product_details,
                'summary_price' => $order->summary_price,
                'delivery_price' => $order->delivery_price ?? 0,
                'created_at' => $order->created_at,
                'dialog_id' => $order->dialog_id,
                'delivered_at' => $order->delivered_at ?? $order->updated_at,
                'info' => $order->delivery_note,
            ];
        });

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function toggleStatus(Request $request): JsonResponse
    {
        $request->validate(['is_online' => 'required|boolean']);
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();
        $user->update(['is_online' => $request->boolean('is_online')]);

        return response()->json([
            'success' => true,
            'message' => $request->boolean('is_online') ? 'Вы вышли на линию' : 'Вы офлайн',
            'is_online' => $user->is_online
        ]);
    }

    public function sendMessage(Request $request, int $orderId): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:1000']);
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $order = Order::where('id', $orderId)->where('deliveryman_id', $user->id)->first();
        if (!$order) return response()->json(['error' => 'Заказ не найден'], 404);
        if (!$order->dialog_id) return response()->json(['error' => 'У этого заказа нет привязанного чата'], 400);

        $message = TenantMessage::create([
            'tenant_id' => $order->tenant_id,
            'dialog_id' => $order->dialog_id,
            'sender_type' => 'deliveryman',
            'sender_id' => $user->id,
            'message' => $request->message,
            'meta' => ['order_id' => $order->id, 'sender_name' => $user->name ?? 'Курьер', 'type' => 'deliveryman_message'],
            'is_read' => false,
        ]);

        TenantDialog::where('id', $order->dialog_id)->update(['last_message_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Сообщение отправлено клиенту', 'data' => $message]);
    }



    /**
     * POST /api/deliveryman/orders/{id}/request-payment
     * Курьер запрашивает ссылку на оплату доставки (без отправки в чат)
     */
    public function requestDeliveryPayment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        // Строгая проверка: заказ должен принадлежать этому курьеру
        $order = Order::where('id', $id)
            ->where('deliveryman_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Заказ не найден или не принадлежит вам'], 404);
        }

        try {
            $paymentData = [
                'order_id' => $order->id,
                'amount' => $request->amount,
                'description' => $request->description,
                'name' => $order->receiver_name,
                'phone' => $order->receiver_phone,
                'customer_key' => (string) $order->tenant_user_id,
            ];

            // 🆕 Используем новый метод, который ТОЛЬКО генерирует ссылку
            $paymentUrl = \App\Services\Tenants\PaymentService::call()->generateSimplePaymentLink($paymentData);

            return response()->json([
                'success' => true,
                'message' => 'Ссылка на оплату успешно сформирована',
                'data' => [
                    'url' => $paymentUrl,
                    'amount' => $request->amount,
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка генерации ссылки курьером: ' . $e->getMessage());
            return response()->json([
                'error' => 'Не удалось сформировать ссылку. Проверьте настройки эквайринга.'
            ], 500);
        }
    }

    public function acceptOrder(Request $request, int $id): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        try {
            $result = OrderService::call()->acceptOrder($id);

            if (!$result) {
                return response()->json(['error' => 'Не удалось принять заказ. Возможно, его уже забрали.'], 400);
            }

            $order = Order::find($id);
            if ($order && $order->dialog_id) {
                app(OrderDialogService::class)->addSystemMessage(
                    $order, "🚚 Курьер {$user->name} принял ваш заказ и готовится к выезду!", 'status_change', ['deliveryman_name' => $user->name]
                );
            }

            return response()->json(['success' => true, 'message' => 'Заказ успешно принят', 'order_id' => $id]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|integer|in:0,1,2,3,4,5']);
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $order = Order::where('id', $id)->where('deliveryman_id', $user->id)->first();
        if (!$order) return response()->json(['error' => 'Заказ не найден или не принадлежит вам'], 404);

        $order->status = $request->status;
        $order->save();

        $statusLabels = [0 => 'Новый', 1 => 'В доставке', 2 => 'Доставлен', 3 => 'Отменен', 4 => 'Готов к доставке', 5 => 'Готовится'];
        return response()->json(['success' => true, 'message' => "Статус изменен на: {$statusLabels[$request->status]}"]);
    }

    public function updateLocation(Request $request, int $id): JsonResponse
    {
        $request->validate(['latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180']);
        $result = OrderService::call()->storeCoordsToOrder($request->latitude, $request->longitude);
        return response()->json(['success' => $result, 'message' => $result ? 'Координаты обновлены' : 'Нет активных заказов']);
    }

    public function confirmDelivery(Request $request, int $id): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();
        $order = Order::where('id', $id)->where('deliveryman_id', $user->id)->first();

        if (!$order) return response()->json(['error' => 'Заказ не найден'], 404);
        if ($order->status == OrderStatusEnum::Completed->value) return response()->json(['error' => 'Заказ уже завершен'], 400);

        $targetLat = $order->target_latitude ?? null;
        $targetLon = $order->target_longitude ?? null;
        $currentLat = $order->deliveryman_latitude;
        $currentLon = $order->deliveryman_longitude;

        if ($targetLat && $targetLon && $currentLat && $currentLon) {
            $distance = $this->calculateDistance($currentLat, $currentLon, $targetLat, $targetLon);
            if ($distance > 0.5) {
                return response()->json(['error' => "Вы находитесь слишком далеко от точки доставки ({$distance} км). Подъедите ближе.", 'distance_km' => round($distance, 2)], 403);
            }
        }

        $order->status = OrderStatusEnum::Completed->value;
        $order->delivered_at = now();
        $order->save();

        if ($order->dialog_id) {
            app(OrderDialogService::class)->addSystemMessage($order, "🎉 Ваш заказ успешно доставлен! Приятного аппетита.", 'status_change');
        }

        return response()->json(['success' => true, 'message' => 'Доставка подтверждена', 'earned' => $order->delivery_price ?? 0]);
    }

    public function requestPayout(Request $request): JsonResponse
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();
        $amount = (float) $request->amount;

        if (($user->balance ?? 0) < $amount) {
            return response()->json(['error' => 'Недостаточно средств на балансе'], 400);
        }

        DB::transaction(function () use ($user, $amount) {
            $user->decrement('balance', $amount);
        });

        return response()->json(['success' => true, 'message' => "Заявка на выплату {$amount} ₽ создана"]);
    }

    public function getDialogMessages(Request $request, int $dialogId): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();
        $order = Order::where('dialog_id', $dialogId)->where('deliveryman_id', $user->id)->first();

        if (!$order) return response()->json(['error' => 'Доступ к этому чату запрещен'], 403);

        $messages = TenantMessage::where('dialog_id', $dialogId)->orderBy('created_at', 'asc')->get()->map(function ($msg) {
            return ['id' => $msg->id, 'sender_type' => $msg->sender_type, 'sender_id' => $msg->sender_id, 'message' => $msg->message, 'meta' => $msg->meta, 'created_at' => $msg->created_at->toISOString()];
        });

        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function sendDialogMessage(Request $request, int $dialogId): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:1000']);
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();
        $order = Order::where('dialog_id', $dialogId)->where('deliveryman_id', $user->id)->first();

        if (!$order) return response()->json(['error' => 'Доступ к этому чату запрещен'], 403);

        $message = TenantMessage::create([
            'tenant_id' => $order->tenant_id, 'dialog_id' => $dialogId, 'sender_type' => 'deliveryman', 'sender_id' => $user->id,
            'message' => $request->message, 'meta' => ['order_id' => $order->id, 'sender_name' => $user->name ?? 'Курьер', 'type' => 'deliveryman_message'], 'is_read' => false,
        ]);

        TenantDialog::where('id', $dialogId)->update(['last_message_at' => now()]);

        return response()->json(['success' => true, 'data' => ['id' => $message->id, 'sender_type' => $message->sender_type, 'message' => $message->message, 'meta' => $message->meta, 'created_at' => $message->created_at->toISOString()]]);
    }

    public function getAvailableShops(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $shops = Tenant::where('is_active', true)
            ->select('id', 'name', 'slug', 'description') // 🆕 Добавили 'address'
            ->orderBy('name')
            ->get()
            ->map(function ($shop) {
                $settings = $shop->settings ?? [];
                // 🆕 Собираем полный адрес из доступных полей
                $city = $shop->city ?? ($settings['city'] ?? ($settings['shop']['city'] ?? ''));
                $address = $shop->full_address ;


                $fullAddress = '';
                if ($city) $fullAddress .= $city . ', ';
                $fullAddress .= $address;
                $fullAddress = trim($fullAddress, ', ') ?: 'Адрес не указан';

                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'slug' => $shop->slug,
                    'description' => $shop->description ?? 'Доставка заказов',
                    'image' => $shop->image,
                    'address' => $fullAddress, // 🆕 Явно отдаем собранный адрес
                    'settings' => [
                        'shop_coords' => $settings['shop_coords'] ?? ($settings['shop']['shop_coords'] ?? null)
                    ]
                ];
            });

        $meta = $user->meta ?? [];
        $userSettings = $meta['settings'] ?? [];
        $selectedShopIds = $userSettings['delivery_shops'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'shops' => $shops,
                'selected_ids' => $selectedShopIds,
            ]
        ]);
    }

    /**
     * POST /api/deliveryman/shops
     * Сохранение выбранных магазинов для доставки
     */
    public function updateDeliveryShops(Request $request): JsonResponse
    {
        // 🆕 ИСПРАВЛЕНО: 'present' вместо 'required' разрешает пустой массив []
        $request->validate([
            'shop_ids' => 'present|array',
            'shop_ids.*' => 'integer|exists:tenants,id',
        ]);

        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        // Если массив пуст, array_unique вернет [], что нам и нужно
        $shopIds = array_unique($request->shop_ids ?? []);

        $meta = $user->meta ?? [];
        $settings = $meta['settings'] ?? [];

        // 🆕 Сохраняем массив ID (даже если он пустой, это очистит настройки)
        $settings['delivery_shops'] = $shopIds;
        $meta['settings'] = $settings;

        $user->update(['meta' => $meta]);

        return response()->json([
            'success' => true,
            'message' => $shopIds ? 'Настройки доставки успешно сохранены' : 'Выбор магазинов очищен',
            'data' => [
                'selected_ids' => $shopIds,
                'count' => count($shopIds)
            ]
        ]);
    }

    // 🆕 УНИВЕРСАЛЬНЫЙ МЕТОД ФИЛЬТРАЦИИ ПО МАГАЗИНАМ
    private function applyShopFilter($query, TenantUser $user): void
    {
        $meta = $user->meta ?? [];
        $settings = $meta['settings'] ?? [];
        $selectedShopIds = $settings['delivery_shops'] ?? [];

        if (empty($selectedShopIds)) {
            // Если курьер ничего не выбрал, принудительно возвращаем 0 результатов
            $query->whereRaw('1 = 0');
        } else {
            // Иначе фильтруем только по выбранным tenant_id
            $query->whereIn('tenant_id', $selectedShopIds);
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * GET /api/deliveryman/settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();
        $meta = $user->meta ?? [];

        $settings = $meta['delivery_settings'] ?? [
            'auto_refresh' => false,
            'refresh_interval' => 30,
            'sound_enabled' => true,
        ];

        return response()->json(['success' => true, 'data' => $settings]);
    }

    /**
     * POST /api/deliveryman/settings
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $request->validate([
            'auto_refresh' => 'boolean',
            'refresh_interval' => 'integer|min:30', // Минимум 30 секунд
            'sound_enabled' => 'boolean',
        ]);

        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();
        $meta = $user->meta ?? [];

        $meta['delivery_settings'] = $request->only(['auto_refresh', 'refresh_interval', 'sound_enabled']);
        $user->update(['meta' => $meta]);

        return response()->json([
            'success' => true,
            'message' => 'Настройки успешно сохранены',
            'data' => $meta['delivery_settings']
        ]);
    }

    /**
     * PUT /api/deliveryman/orders/{id}/details
     * Обновление расстояния и стоимости доставки для заказа
     */
    public function updateOrderDetails(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'distance_km' => 'nullable|numeric|min:0',
            'delivery_price' => 'nullable|numeric|min:0',
        ]);

        $order = Order::findOrFail($id);

        // Опционально: можно добавить проверку, что заказ еще доступен (deliveryman_id == null),
        // но если это делает диспетчер/админ, то разрешаем редактировать в любом случае.

        $order->delivery_range = $request->distance_km; // или distance_km, в зависимости от названия колонки в БД
        $order->delivery_price = $request->delivery_price;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Данные заказа успешно обновлены',
            'data' => [
                'distance_km' => $order->delivery_range,
                'delivery_price' => $order->delivery_price
            ]
        ]);
    }

    /**
     * POST /api/deliveryman/shops/{id}/calculate-delivery
     * Расчет расстояния и стоимости доставки от конкретного заведения
     */
    public function calculateShopDelivery(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $shop = Tenant::findOrFail($id);
        $config = $shop->settings ?? [];

        $shopCoords = $config["shop_coords"] ?? ($config["shop"]["shop_coords"] ?? null);

        if (empty($shopCoords)) {
            return response()->json(['error' => 'Координаты заведения не указаны в настройках'], 400);
        }

        // 1. Считаем расстояние через ваш GEOService
        $distanceInMeters = \App\Services\Tenants\GEOService::call()->getDistance(
            (float)$request->lat,
            (float)$request->lng,
            $shopCoords
        );

        $distCoef = env("DISTANCE_COEF") ?? 1;
        $distanceInKm = round(($distanceInMeters / 1000) * $distCoef, 2);

        // 2. Применяем логику зон (как в getDeliveryPriceNew)
        $pricePerKm = (float)($config["shop"]["price_per_km"] ?? $config["price_per_km"] ?? 80);
        $deliveryZones = $config['shop']['delivery_zones'] ?? $config['delivery_zones'] ?? [];

        usort($deliveryZones, function ($a, $b) {
            return ((float)($a['radius'] ?? 0)) <=> ((float)($b['radius'] ?? 0));
        });

        $parseZonePrice = function ($price) {
            if (is_numeric($price)) return (float)$price;
            $priceStr = mb_strtolower((string)$price);
            if (str_contains($priceStr, 'бесплатно') || str_contains($priceStr, 'free')) return 0.0;
            preg_match('/\d+([\.,]\d+)?/', $priceStr, $matches);
            return isset($matches[0]) ? (float)str_replace(',', '.', $matches[0]) : 0.0;
        };

        $deliveryPrice = 0.0;
        $appliedZoneName = 'Базовый тариф';
        $isOutsideZones = false;

        if (!empty($deliveryZones)) {
            $zoneFound = false;
            foreach ($deliveryZones as $zone) {
                $radius = (float)($zone['radius'] ?? 0);
                if ($distanceInKm <= $radius) {
                    $zoneBasePrice = $parseZonePrice($zone['price'] ?? 0);
                    $deliveryPrice = round($zoneBasePrice + ($distanceInKm * $pricePerKm), 2);
                    $appliedZoneName = $zone['name'] ?? 'Зона';
                    $zoneFound = true;
                    break;
                }
            }

            if (!$zoneFound) {
                $isOutsideZones = true;
                $lastZone = end($deliveryZones);
                $lastZoneBasePrice = $parseZonePrice($lastZone['price'] ?? 0);
                $appliedZoneName = ($lastZone['name'] ?? 'Дальняя зона') . ' (сверх лимита)';
                $deliveryPrice = round($lastZoneBasePrice + ($distanceInKm * $pricePerKm), 2);
            }
        } else {
            $minBaseDeliveryPrice = (float)($config["shop"]["min_base_delivery_price"] ?? $config["min_base_delivery_price"] ?? 100);
            $deliveryPrice = round($minBaseDeliveryPrice + ($distanceInKm * $pricePerKm), 2);
            $appliedZoneName = 'Без зон (линейный расчет)';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'shop_name' => $shop->name ?? $shop->title,
                'distance_km' => $distanceInKm,
                'delivery_price' => $deliveryPrice,
                'is_outside_zones' => $isOutsideZones,
                'breakdown' => [
                    'zone_name' => $appliedZoneName,
                    'distance_km' => $distanceInKm,
                    'price_per_km' => $pricePerKm,
                    'distance_cost' => round($distanceInKm * $pricePerKm, 2),
                    'total' => $deliveryPrice,
                ]
            ]
        ]);
    }

    /**
     * POST /api/deliveryman/shops/{id}/payment-link
     * Генерация платежной ссылки на рассчитанную сумму доставки
     */
    public function generateShopPaymentLink(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        $shop = Tenant::findOrFail($id);

        try {
            $paymentData = [
                'order_id' => 'DELIVERY_' . $shop->id . '_' . time(),
                'amount' => $request->amount,
                'description' => $request->description,
                'name' => 'Клиент доставки',
                'phone' => '',
                'customer_key' => 'deliveryman_calc',
            ];

            $paymentUrl = PaymentService::call()->generateSimplePaymentLink($paymentData);

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $paymentUrl,
                    'amount' => $request->amount,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка генерации ссылки доставки: ' . $e->getMessage());
            return response()->json(['error' => 'Не удалось сформировать ссылку на оплату'], 500);
        }
    }
}
