<?php

use Illuminate\Http\Request;
use Exxxar\Kanban\Facades\Kanban;

// Вспомогательная функция для инициализации Kanban из переданных настроек
$initKanban = function (Request $request) {
    $settings = $request->input('settings', []);
    $baseUrl = $settings['base_url'] ?? null;
    $token = $settings['token'] ?? null;
    $boardUuid = $settings['board_uuid'] ?? null;

    if (!$baseUrl || !$token) {
        throw new \Exception('Отсутствуют обязательные параметры: base_url или token в settings');
    }

    return [
        'kanban' => Kanban::setBaseUrl($baseUrl)
            ->setToken($token)
            ->setTimeout(30)
            ->setConnectTimeout(10)
            ->setRetryTimes(3)
            ->setRetrySleep(100)
            ->setLoggingEnabled(true),
        'board_uuid' => $boardUuid,
    ];
};

// === ТЕСТ 1: Проверка подключения (список досок) ===
Route::post('/test-kanban', function (Request $request) use ($initKanban) {
    try {
        $init = $initKanban($request);
        $boards = \Exxxar\Kanban\Facades\Kanban::boards()->list();

        return response()->json([
            'success' => true,
            'boards_count' => count($boards),
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// === ТЕСТ 2: Создание задачи + первое сообщение ===
Route::post('/test-kanban/create-task', function (Request $request) use ($initKanban) {
    try {
        $init = $initKanban($request);

        $result = \Exxxar\Kanban\Facades\Kanban::tasks()->sendMessageOrCreate([
            'board_uuid' => $init['board_uuid'],
            'thread' => $request->input('settings.order_thread', 0),
            'type' => 1,
            'title' => 'Тестовая задача от SDK #' . rand(1000, 9999),
            'description' => 'Создана автоматически через KanbanSDK',
            'priority' => 'medium',
            'labels' => ['development', 'test'],
            'message' => 'Привет! Это тестовое сообщение из SDK.',
            'sender_type' => 'system',
            'sender_label' => 'KanbanSDK Test',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Задача создана и сообщение отправлено!',
            'data' => ['task_id' => $result['task_id'], 'task_title' => $result['task']->title],
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// === ТЕСТ 3: Создание клиента + первое сообщение ===
Route::post('/test-kanban/create-client', function (Request $request) use ($initKanban) {
    try {
        $init = $initKanban($request);

        $result = \Exxxar\Kanban\Facades\Kanban::tasks()->sendMessageOrCreate([
            'board_uuid' => $init['board_uuid'],
            'thread' => $request->input('settings.order_thread', 0),
            'type' => 2,
            'title' => 'ООО Тестовая Компания',
            'description' => 'Новый клиент из SDK',
            'priority' => 'high',
            'labels' => ['client', 'vip'],
            'client_data' => [
                'company_name' => 'ООО Тестовая Компания',
                'contact_person' => 'Иванов Иван',
                'phone' => '+7900' . rand(1000000, 9999999),
                'source' => 'SDK Test',
                'cost' => rand(50, 500) * 1000,
            ],
            'message' => 'Здравствуйте! Мы заинтересованы в сотрудничестве.',
            'sender_type' => 'external',
            'sender_label' => 'Клиент с сайта',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Клиент создан и сообщение отправлено!',
            'data' => [
                'task_id' => $result['task_id'],
                'client_name' => $result['task']->client?->company_name,
                'client_phone' => $result['task']->client?->phone,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// === ТЕСТ 4: Отправка сообщения существующей задаче ===
Route::post('/test-kanban/send-message', function (Request $request) use ($initKanban) {
    try {
        $init = $initKanban($request);
        $taskId = $request->input('task_id');

        if (!$taskId) {
            return response()->json(['success' => false, 'error' => 'Не указан task_id'], 400);
        }

        $result = \Exxxar\Kanban\Facades\Kanban::tasks()->continueDialog(
            taskId: (int) $taskId,
            message: 'Тестовое сообщение из SDK для задачи #' . $taskId,
            senderType: 'manager',
            senderLabel: 'Менеджер SDK'
        );

        return response()->json([
            'success' => true,
            'message' => 'Сообщение отправлено!',
            'data' => ['task_id' => $result['task_id'], 'message_id' => $result['message_id']],
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// === ТЕСТ 5: Универсальный метод (создать или отправить) ===
Route::post('/test-kanban/smart-send', function (Request $request) use ($initKanban) {
    try {
        $init = $initKanban($request);
        $taskId = $request->input('task_id');

        $params = [
            'message' => 'Тестовое сообщение Smart Send #' . rand(1000, 9999),
            'sender_type' => 'external',
            'sender_label' => 'SDK Smart Send',
        ];

        if ($taskId) {
            $params['task_id'] = (int) $taskId;
        } else {
            $params['board_uuid'] = $init['board_uuid'];
            $params['thread'] = $request->input('settings.order_thread', 0);
            $params['type'] = 1;
            $params['title'] = 'Smart Send Test #' . rand(1000, 9999);
            $params['priority'] = 'low';
        }

        $result = \Exxxar\Kanban\Facades\Kanban::tasks()->sendMessageOrCreate($params);

        return response()->json([
            'success' => true,
            'message' => $result['created'] ? 'Создана новая задача и отправлено сообщение' : 'Отправлено сообщение существующей задаче',
            'data' => ['task_id' => $result['task_id'], 'created' => $result['created']],
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// === ТЕСТ 6: Получение задач доски ===
Route::post('/test-kanban/tasks', function (Request $request) use ($initKanban) {
    try {
        $init = $initKanban($request);
        $tasks = \Exxxar\Kanban\Facades\Kanban::tasks()->getAll($init['board_uuid']);

        return response()->json([
            'success' => true,
            'tasks_count' => count($tasks),
            'tasks' => collect($tasks)->map(fn($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'type' => $task->type->label(),
                'created_at' => $task->created_at?->toDateTimeString(),
            ])->take(10), // Ограничим вывод 10 последними задачами для чистоты ответа
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// === ТЕСТ 7: Получение сообщений конкретной задачи ===
Route::post('/test-kanban/messages', function (Request $request) use ($initKanban) {
    try {
        $init = $initKanban($request);
        $taskId = $request->input('task_id');

        if (!$taskId) {
            return response()->json(['success' => false, 'error' => 'Не указан task_id'], 400);
        }

        // 🔄 Используем новый метод getByTask вместо list
        $messages = \Exxxar\Kanban\Facades\Kanban::messages()->getByTask((int) $taskId);

        return response()->json([
            'success' => true,
            'task_id' => $taskId,
            'messages_count' => count($messages),
            'messages' => collect($messages)->map(fn($msg) => [
                'id' => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_label' => $msg->sender_label,
                'message' => $msg->message,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at?->toDateTimeString(),
            ])->take(10),
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// === ТЕСТ 8: 🆕 Получение сообщений по всей доске (глобальная лента) ===
Route::post('/test-kanban/board-messages', function (Request $request) use ($initKanban) {
    try {
        $init = $initKanban($request);
        $limit = $request->input('limit', 20);

        // 🆕 Используем новый метод getByBoard
        $messages = \Exxxar\Kanban\Facades\Kanban::messages()->getByBoard($init['board_uuid'], [
            'limit' => $limit
        ]);

        return response()->json([
            'success' => true,
            'board_uuid' => $init['board_uuid'],
            'messages_count' => count($messages),
            'messages' => collect($messages)->map(fn($msg) => [
                'id' => $msg->id,
                'task_id' => $msg->task_id,
                'sender_type' => $msg->sender_type,
                'sender_label' => $msg->sender_label,
                'message' => $msg->message,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at?->toDateTimeString(),
            ])->toArray(),
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// === ТЕСТ 9: 🆕 Создание полноценного тестового заказа (имитация foodShopCheckout) ===
Route::post('/test-kanban/create-order', function (Request $request) use ($initKanban) {
    try {
        $init = $initKanban($request);
        $settings = $request->input('settings', []);

        // Генерируем случайные тестовые данные
        $mockOrderId = rand(10000, 99999);
        $mockPhone = '+7900' . rand(1000000, 9999999);
        $mockPrice = rand(1500, 15000);
        $mockDeliveryPrice = 300;

        // Используем тот же метод, что и в реальном checkout
        $result = \Exxxar\Kanban\Facades\Kanban::tasks()->sendMessageOrCreate([
            'board_uuid' => $init['board_uuid'],
            'thread' => $settings['order_thread'] ?? 0,
            'type' => 2, // Тип: Клиент (с данными заказа)
            'title' => "Тестовый заказ #{$mockOrderId}",
            'priority' => 'high',
            'labels' => ['order', 'foodshop', 'test'],

            // Данные клиента, как в foodShopCheckout
            'client_data' => [
                'company_name' => 'ООО "Тестовая Доставка"',
                'contact_person' => 'Иванов Иван Иванович',
                'phone' => $mockPhone,
                'source' => 'FoodShop Test',
                'cost' => $mockPrice,
                'placement_type' => 'Доставка',
                'custom_data' => [
                    'tenant_id' => 1,
                    'tenant_name' => 'Тестовый Ресторан',
                    'tenant_user_id' => 42,
                    'last_order_id' => $mockOrderId,
                    'last_order_date' => now()->toIso8601String(),
                    'total_orders' => 1,
                    // 🎯 Ключевое для CardOrder.vue:
                    'product_details' => [
                        [
                            'from' => 'Тестовый Ресторан',
                            'products' => [
                                ['name' => 'Пицца Пепперони', 'count' => 2, 'price' => 1200],
                                ['name' => 'Кола 0.5л', 'count' => 2, 'price' => 200],
                            ]
                        ]
                    ],
                    'delivery_price' => $mockDeliveryPrice,
                    'delivery_note' => 'ул. Тестовая, д. 1, кв. 42, домофон 123',
                    'payment_type' => 4, // СБП
                ]
            ],

            // Форматированное сообщение, как buildCrmMessage
            'message' => "🛒 **Новый заказ #{$mockOrderId}**\n\n" .
                "👤 Клиент: Иванов Иван Иванович\n" .
                "📞 Телефон: {$mockPhone}\n" .
                "💰 Сумма: {$mockPrice} ₽\n" .
                "🚚 Доставка: {$mockDeliveryPrice} ₽\n\n" .
                "📦 **Состав:**\n" .
                "• Пицца Пепперони x2 = 1200 ₽\n" .
                "• Кола 0.5л x2 = 200 ₽\n\n" .
                "💳 Оплата: СБП\n" .
                "📍 Адрес: ул. Тестовая, д. 1, кв. 42",

            'sender_type' => 'system',
            'sender_label' => 'FoodShop Checkout (Test)',

            // Метаданные для payload
            'payload' => [
                'source' => 'foodshop_test',
                'order_id' => $mockOrderId,
                'tenant_id' => 1,
                'tenant_user_id' => 42,
                'customer_name' => 'Иванов Иван Иванович',
                'customer_phone' => $mockPhone,
                'summary_price' => $mockPrice,
                'summary_count' => 4,
                'payment_type' => 4,
                'type' => 'new_client_and_order',
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => '🎉 Тестовый заказ успешно создан и отправлен в CRM!',
            'data' => [
                'task_id' => $result['task_id'],
                'message_id' => $result['message_id'],
                'order_id' => $mockOrderId,
                'client_name' => $result['task']->client?->company_name,
                'total_price' => $result['task']->client?->cost,
                'created' => $result['created'],
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});
