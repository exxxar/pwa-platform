<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Models\Tenant\TenantDialog;
use App\Services\MessageService;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * Получить список всех заказов (с пагинацией, поиском и фильтрами)
     */
    public function index(Request $request)
    {
        $query = Order::query()->with(['tenantUser', 'dialog']);

        // 1. Поиск по ID, имени или телефону
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('tenantUser', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Фильтр по статусу (🔥 ИСПРАВЛЕНИЕ ЗДЕСЬ)
        if ($request->filled('status')) {
            // Карта соответствия строковых значений из Vue и integer из OrderStatusEnum
            $statusMap = [
                'new'        => 0, // OrderStatusEnum::NewOrder
                'processing' => 1, // OrderStatusEnum::InDelivery (или 5, зависит от вашей логики)
                'completed'  => 2, // OrderStatusEnum::Completed
                'cancelled'  => 3, // OrderStatusEnum::Decline
            ];

            // Если пришло строковое значение, заменяем его на integer. Если уже integer - оставляем.
            $statusValue = $statusMap[$request->status] ?? $request->status;

            $query->where('status', (int) $statusValue);
        }

        // 3. Сортировка (🔥 ДОБАВЛЕНА ЗАЩИТА ОТ SQL INJECTION)
        $sortParam = $request->get('order_by', 'id');
        $direction = $request->get('direction', 'desc');

        // Разрешаем сортировку только по безопасным колонкам
        $allowedSortColumns = ['id', 'summary_price', 'created_at', 'updated_at'];
        if (!in_array($sortParam, $allowedSortColumns)) {
            $sortParam = 'id';
        }
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortParam, $direction);

        $orders = $query->paginate($request->get('size', 20));

        return response()->json([
            'data' => $orders->items(),
            'paginate' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Получить детализацию одного заказа
     */
    public function show($id)
    {
        $order = Order::with(['tenantUser', 'dialog', 'dialog.messages' => function ($q) {
            $q->latest()->limit(20); // Последние 20 сообщений для контекста
        }])->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Быстрая смена статуса заказа
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string']);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();

        // Опционально: отправить уведомление в чат о смене статуса
        if ($order->dialog_id) {
            MessageService::call()->sendMessage([
                'dialog_id' => $order->dialog_id,
                'message' => "ℹ️ Статус вашего заказа #{$order->id} изменен на: " . $this->getStatusText($request->status),
                'meta' => ['is_system' => true, 'type' => 'status_change'],
                'recipients' => ['client' => true]
            ]);
        }

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Быстрая отправка сообщения в чат, привязанный к заказу
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);

        $order = Order::with('dialog')->findOrFail($id);

        if (!$order->dialog_id) {
            return response()->json(['error' => 'У заказа нет привязанного диалога'], 400);
        }

        MessageService::call()->sendMessage([
            'dialog_id' => $order->dialog_id,
            'message' => $request->message,
            'meta' => ['is_system' => false, 'sender_type' => 'admin'],
            'recipients' => ['client' => true]
        ]);

        return response()->json(['success' => true]);
    }

    private function getStatusText($status)
    {
        $map = [
            'new' => 'Новый',
            'processing' => 'В обработке',
            'completed' => 'Выполнен',
            'cancelled' => 'Отменен',
        ];
        return $map[$status] ?? $status;
    }
}
