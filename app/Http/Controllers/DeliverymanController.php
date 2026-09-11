<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Models\Tenant\Order;
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

class DeliverymanController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $activeOrdersCount = Order::where('deliveryman_id', $user->id)
            ->whereIn('status', [
                OrderStatusEnum::InDelivery->value,
                OrderStatusEnum::ReadyForDelivery->value,
                OrderStatusEnum::StartsCooking->value,
            ])
            ->count();

        $completedOrdersCount = Order::where('deliveryman_id', $user->id)
            ->where('status', OrderStatusEnum::Completed->value)
            ->count();

        $today = Carbon::today('Europe/Moscow')->format('Y-m-d');
        $availableOrdersCount = Order::whereNull('deliveryman_id')
            ->whereIn('status', [
                OrderStatusEnum::NewOrder->value,
                OrderStatusEnum::StartsCooking->value,
                OrderStatusEnum::ReadyForDelivery->value
            ])
            ->whereDate('created_at', '>=', $today)
            ->count();

        // 🆕 РАСЧЕТ ЗАРАБОТКА: Сумма delivery_price завершенных заказов
        // Сейчас настроено на ТЕКУЩИЙ МЕСЯЦ.
        $earned = Order::where('deliveryman_id', $user->id)
            ->where('status', OrderStatusEnum::Completed->value)
            ->whereMonth('delivered_at', Carbon::now()->month)
            ->whereYear('delivered_at', Carbon::now()->year)
            ->sum('delivery_price') ?? 0.0;

        /*
         * 💡 КАК ИЗМЕНИТЬ ПЕРИОД:
         *
         * 1. За ВСЁ время (убрать whereMonth и whereYear):
         *    $earned = Order::where('deliveryman_id', $user->id)
         *        ->where('status', OrderStatusEnum::Completed->value)
         *        ->sum('delivery_price') ?? 0.0;
         *
         * 2. За ПОСЛЕДНИЕ 7 дней:
         *    $earned = Order::where('deliveryman_id', $user->id)
         *        ->where('status', OrderStatusEnum::Completed->value)
         *        ->where('delivered_at', '>=', Carbon::now()->subDays(7))
         *        ->sum('delivery_price') ?? 0.0;
         */

        return response()->json([
            'success' => true,
            'data' => [
                'deliveryman' => [
                    'id' => $user->id,
                    'name' => $user->name ?? 'Курьер',
                    'phone' => $user->phone,
                    'status' => $user->is_online ? 'online' : 'offline',
                    'earned' => (float) $earned, // 🆕 Теперь здесь реальная сумма доставок
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
        $today = Carbon::today('Europe/Moscow')
            ->format('Y-m-d');

        $orders = Order::query()
            ->whereNull('deliveryman_id')
            ->whereIn('status', [
                OrderStatusEnum::NewOrder->value,
                OrderStatusEnum::StartsCooking->value,
                OrderStatusEnum::ReadyForDelivery->value
            ])
            ->whereDate('created_at', '>=', $today)
            ->with(['tenant', 'location']) // 🆕 Подгружаем location
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $formatted = $orders->map(function ($order) {
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
                'info' => $order->delivery_note, // 🆕 HTML-заметка как 'info'
            ];
        });

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function activeOrders(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $orders = Order::query()
            ->where('deliveryman_id', $user->id)
            ->whereIn('status', [
                OrderStatusEnum::InDelivery->value,
                OrderStatusEnum::ReadyForDelivery->value,
                OrderStatusEnum::StartsCooking->value,
            ])
            ->with(['tenant', 'dialog', 'location']) // 🆕 Подгружаем location
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $orders->map(function ($order) {
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
                'info' => $order->delivery_note, // 🆕 HTML-заметка как 'info'
            ];
        });

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function completedOrders(Request $request): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $orders = Order::query()
            ->where('deliveryman_id', $user->id)
            ->where('status', OrderStatusEnum::Completed->value)
            ->with(['tenant', 'location']) // 🆕 Подгружаем location
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $formatted = $orders->map(function ($order) {
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
                'delivered_at' => $order->delivered_at ?? $order->updated_at,
                'info' => $order->delivery_note, // 🆕 HTML-заметка как 'info'
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

    /**
     * POST /api/deliveryman/orders/{id}/message
     * Отправка сообщения клиенту от имени курьера
     */
    public function sendMessage(Request $request, int $orderId): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $order = Order::where('id', $orderId)
            ->where('deliveryman_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Заказ не найден'], 404);
        }

        if (!$order->dialog_id) {
            return response()->json(['error' => 'У этого заказа нет привязанного чата'], 400);
        }

        // Создаем сообщение от курьера
        $message = TenantMessage::create([
            'tenant_id' => $order->tenant_id,
            'dialog_id' => $order->dialog_id,
            'sender_type' => 'deliveryman', // 🆕 Новый тип отправителя
            'sender_id' => $user->id,
            'message' => $request->message,
            'meta' => [
                'order_id' => $order->id,
                'sender_name' => $user->name ?? 'Курьер',
                'type' => 'deliveryman_message'
            ],
            'is_read' => false,
        ]);

        // Обновляем время последнего сообщения в диалоге
        TenantDialog::where('id', $order->dialog_id)
            ->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Сообщение отправлено клиенту',
            'data' => $message
        ]);
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
                    $order,
                    "🚚 Курьер {$user->name} принял ваш заказ и готовится к выезду!",
                    'status_change',
                    ['deliveryman_name' => $user->name]
                );
            }

            return response()->json(['success' => true, 'message' => 'Заказ успешно принят', 'order_id' => $id]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    // 🆕 НОВЫЙ МЕТОД: Смена статуса курьером
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|integer|in:0,1,2,3,4,5']);

        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $order = Order::where('id', $id)->where('deliveryman_id', $user->id)->first();
        if (!$order) {
            return response()->json(['error' => 'Заказ не найден или не принадлежит вам'], 404);
        }

        $order->status = $request->status;
        $order->save(); // OrderObserver автоматически отправит сообщение в чат

        $statusLabels = [
            0 => 'Новый', 1 => 'В доставке', 2 => 'Доставлен',
            3 => 'Отменен', 4 => 'Готов к доставке', 5 => 'Готовится'
        ];

        return response()->json([
            'success' => true,
            'message' => "Статус изменен на: {$statusLabels[$request->status]}"
        ]);
    }

    public function updateLocation(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $result = OrderService::call()->storeCoordsToOrder($request->latitude, $request->longitude);
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Координаты обновлены' : 'Нет активных заказов'
        ]);
    }

    public function confirmDelivery(Request $request, int $id): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $order = Order::where('id', $id)->where('deliveryman_id', $user->id)->first();
        if (!$order) return response()->json(['error' => 'Заказ не найден'], 404);
        if ($order->status == OrderStatusEnum::Completed->value) return response()->json(['error' => 'Заказ уже завершен'], 400);

        // Гео-проверка
        $targetLat = $order->target_latitude ?? null;
        $targetLon = $order->target_longitude ?? null;
        $currentLat = $order->deliveryman_latitude;
        $currentLon = $order->deliveryman_longitude;

        if ($targetLat && $targetLon && $currentLat && $currentLon) {
            $distance = $this->calculateDistance($currentLat, $currentLon, $targetLat, $targetLon);
            if ($distance > 0.5) {
                return response()->json([
                    'error' => "Вы находитесь слишком далеко от точки доставки ({$distance} км). Подъедите ближе.",
                    'distance_km' => round($distance, 2)
                ], 403);
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
     * GET /api/deliveryman/dialogs/{dialogId}/messages
     * Получение истории сообщений диалога
     */
    public function getDialogMessages(Request $request, int $dialogId): JsonResponse
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        // Проверяем, что этот диалог принадлежит заказу этого курьера
        $order = Order::where('dialog_id', $dialogId)
            ->where('deliveryman_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Доступ к этому чату запрещен'], 403);
        }

        $messages = TenantMessage::where('dialog_id', $dialogId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_type' => $msg->sender_type,
                    'sender_id' => $msg->sender_id,
                    'message' => $msg->message,
                    'meta' => $msg->meta,
                    'created_at' => $msg->created_at->toISOString(),
                ];
            });

        return response()->json(['success' => true, 'data' => $messages]);
    }

    /**
     * POST /api/deliveryman/dialogs/{dialogId}/messages
     * Отправка сообщения в диалог
     */
    public function sendDialogMessage(Request $request, int $dialogId): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:1000']);

        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        $order = Order::where('dialog_id', $dialogId)
            ->where('deliveryman_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Доступ к этому чату запрещен'], 403);
        }

        $message = TenantMessage::create([
            'tenant_id' => $order->tenant_id,
            'dialog_id' => $dialogId,
            'sender_type' => 'deliveryman',
            'sender_id' => $user->id,
            'message' => $request->message,
            'meta' => [
                'order_id' => $order->id,
                'sender_name' => $user->name ?? 'Курьер',
                'type' => 'deliveryman_message'
            ],
            'is_read' => false,
        ]);

        TenantDialog::where('id', $dialogId)->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'message' => $message->message,
                'meta' => $message->meta,
                'created_at' => $message->created_at->toISOString(),
            ]
        ]);
    }
}
