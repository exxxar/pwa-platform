<?php

// routes/web.php

// === ТЕСТ 1: Проверка подключения ===
use Exxxar\Kanban\Facades\Kanban;
use Illuminate\Support\Facades\Route;

Route::get('/test-kanban', function () {
    try {

        Kanban::setBaseUrl('https://crm.mypwa.ru/api/v1')
            ->setToken('kb_SyXvkcnhRu7hD0nZAOwga6blD1TFSUEyXNdW9UyQ')
            ->setTimeout(30)
            ->setConnectTimeout(10)
            ->setRetryTimes(3)
            ->setRetrySleep(100)
            ->setLoggingEnabled(true);

        $boards = \Exxxar\Kanban\Facades\Kanban::boards()
            ->list();



        return response()->json([
            'success' => true,
            'config' => [
                'base_url' => config('kanban.base_url'),
                'token_set' => !empty(config('kanban.token')),
                'timeout' => config('kanban.timeout'),
            ],
            'boards_count' => count($boards),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'config' => [
                'base_url' => config('kanban.base_url'),
                'token_set' => !empty(config('kanban.token')),
            ],
        ], 500);
    }
});

// === ТЕСТ 2: Создание задачи + первое сообщение ===
Route::get('/test-kanban/create-task', function (\Illuminate\Http\Request $request) {
    try {
        $boardUuid = "928e6e06-b9b0-4cca-a45c-0926ba7539f6";//$request->get('board', config('kanban.default_board_uuid'));

        Kanban::setBaseUrl('https://crm.mypwa.ru/api/v1')
            ->setToken('kb_SyXvkcnhRu7hD0nZAOwga6blD1TFSUEyXNdW9UyQ')
            ->setTimeout(30)
            ->setConnectTimeout(10)
            ->setRetryTimes(3)
            ->setRetrySleep(100)
            ->setLoggingEnabled(true);

        $result = \Exxxar\Kanban\Facades\Kanban::tasks()->sendMessageOrCreate([
            'board_uuid' => $boardUuid,
            'thread' => 0, // Первая колонка
            'type' => 1, // Обычная задача
            'title' => 'Тестовая задача от SDK #' . rand(1000, 9999),
            'description' => 'Создана автоматически через KanbanSDK',
            'priority' => 'medium',
            'labels' => ['development', 'test'],
            'message' => 'Привет! Это тестовое сообщение из SDK. Задача создана автоматически.',
            'sender_type' => 'system',
            'sender_label' => 'KanbanSDK Test',
            'payload' => [
                'source' => 'sdk-test',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Задача создана и сообщение отправлено!',
            'data' => [
                'task_id' => $result['task_id'],
                'message_id' => $result['message_id'],
                'created' => $result['created'],
                'task_title' => $result['task']->title,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// === ТЕСТ 3: Создание клиента + первое сообщение ===
Route::get('/test-kanban/create-client', function (\Illuminate\Http\Request $request) {
    try {
        $boardUuid = "928e6e06-b9b0-4cca-a45c-0926ba7539f6";//$request->get('board', config('kanban.default_board_uuid'));

        Kanban::setBaseUrl('https://crm.mypwa.ru/api/v1')
            ->setToken('kb_SyXvkcnhRu7hD0nZAOwga6blD1TFSUEyXNdW9UyQ')
            ->setTimeout(30)
            ->setConnectTimeout(10)
            ->setRetryTimes(3)
            ->setRetrySleep(100)
            ->setLoggingEnabled(true);

        $result = \Exxxar\Kanban\Facades\Kanban::tasks()->sendMessageOrCreate([
            'board_uuid' => $boardUuid,
            'thread' => 0,
            'type' => 2, // Клиент
            'title' => 'ООО Тестовая Компания',
            'description' => 'Новый клиент из SDK',
            'priority' => 'high',
            'labels' => ['client', 'vip'],
            'client_data' => [
                'company_name' => 'ООО Тестовая Компания',
                'contact_person' => 'Иванов Иван Иванович',
                'phone' => '+7' . rand(900, 999) . rand(100, 999) . rand(10, 99) . rand(10, 99),
                'source' => 'SDK Test',
                'cost' => rand(50, 500) * 1000,
                'placement_type' => 'Премиум',
                'custom_data' => [
                    'industry' => 'IT',
                    'employees' => rand(10, 500),
                ],
            ],
            'message' => 'Здравствуйте! Мы заинтересованы в сотрудничестве. Можете связаться с нами?',
            'sender_type' => 'external',
            'sender_label' => 'Клиент с сайта',
            'payload' => [
                'source' => 'sdk-test',
                'form_type' => 'contact',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Клиент создан и сообщение отправлено!',
            'data' => [
                'task_id' => $result['task_id'],
                'message_id' => $result['message_id'],
                'created' => $result['created'],
                'client_name' => $result['task']->client?->company_name,
                'client_phone' => $result['task']->client?->phone,
                'client_cost' => $result['task']->client?->cost,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// === ТЕСТ 4: Отправка сообщения существующей задаче ===
Route::get('/test-kanban/send-message', function (\Illuminate\Http\Request $request) {
    try {

        $boardUuid = "928e6e06-b9b0-4cca-a45c-0926ba7539f6";//$request->get('board', config('kanban.default_board_uuid'));

        Kanban::setBaseUrl('https://crm.mypwa.ru/api/v1')
            ->setToken('kb_SyXvkcnhRu7hD0nZAOwga6blD1TFSUEyXNdW9UyQ')
            ->setTimeout(30)
            ->setConnectTimeout(10)
            ->setRetryTimes(3)
            ->setRetrySleep(100)
            ->setLoggingEnabled(true);

        $taskId = $request->get('task_id');

        if (!$taskId) {
            return response()->json([
                'success' => false,
                'error' => 'Укажите task_id в параметрах: ?task_id=123',
            ], 400);
        }

        $result = \Exxxar\Kanban\Facades\Kanban::tasks()->continueDialog(
            taskId: (int) $taskId,
            message: 'Это тестовое сообщение из SDK. Задача #' . $taskId,
            senderType: 'manager',
            senderLabel: 'Менеджер SDK',
            payload: [
                'source' => 'sdk-test',
                'timestamp' => now()->toIso8601String(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Сообщение отправлено!',
            'data' => [
                'task_id' => $result['task_id'],
                'message_id' => $result['message_id'],
                'created' => $result['created'],
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// === ТЕСТ 5: Универсальный метод (создать или отправить) ===
Route::get('/test-kanban/smart-send', function (\Illuminate\Http\Request $request) {
    try {
        $boardUuid = "928e6e06-b9b0-4cca-a45c-0926ba7539f6";//$request->get('board', config('kanban.default_board_uuid'));

        Kanban::setBaseUrl('https://crm.mypwa.ru/api/v1')
            ->setToken('kb_SyXvkcnhRu7hD0nZAOwga6blD1TFSUEyXNdW9UyQ')
            ->setTimeout(30)
            ->setConnectTimeout(10)
            ->setRetryTimes(3)
            ->setRetrySleep(100)
            ->setLoggingEnabled(true);

        $taskId = $request->get('task_id'); // Если есть — отправим существующей

        $params = [
            'message' => 'Тестовое сообщение #' . rand(1000, 9999),
            'sender_type' => 'external',
            'sender_label' => 'SDK Smart Send',
            'payload' => [
                'source' => 'sdk-smart-test',
                'timestamp' => now()->toIso8601String(),
            ],
        ];

        if ($taskId) {
            // Отправка существующей задаче
            $params['task_id'] = (int) $taskId;
        } else {
            // Создание новой задачи
            $params['board_uuid'] = $boardUuid;
            $params['thread'] = 0;
            $params['type'] = 1;
            $params['title'] = 'Smart Send Test #' . rand(1000, 9999);
            $params['priority'] = 'low';
        }

        $result = \Exxxar\Kanban\Facades\Kanban::tasks()->sendMessageOrCreate($params);

        return response()->json([
            'success' => true,
            'message' => $result['created']
                ? 'Создана новая задача и отправлено сообщение'
                : 'Отправлено сообщение существующей задаче',
            'data' => [
                'task_id' => $result['task_id'],
                'message_id' => $result['message_id'],
                'created' => $result['created'],
                'task_title' => $result['task']->title,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// === ТЕСТ 6: Получение задач доски ===
Route::get('/test-kanban/tasks', function (\Illuminate\Http\Request $request) {
    try {
        $boardUuid = "928e6e06-b9b0-4cca-a45c-0926ba7539f6";//$request->get('board', config('kanban.default_board_uuid'));

        Kanban::setBaseUrl('https://crm.mypwa.ru/api/v1')
            ->setToken('kb_SyXvkcnhRu7hD0nZAOwga6blD1TFSUEyXNdW9UyQ')
            ->setTimeout(30)
            ->setConnectTimeout(10)
            ->setRetryTimes(3)
            ->setRetrySleep(100)
            ->setLoggingEnabled(true);

        $tasks = \Exxxar\Kanban\Facades\Kanban::tasks()->getAll($boardUuid);

        return response()->json([
            'success' => true,
            'tasks_count' => count($tasks),
            'tasks' => array_map(fn($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'type' => $task->type->label(),
                'priority' => $task->priority?->label(),
                'created_at' => $task->created_at?->toDateTimeString(),
                'has_client' => $task->client !== null,
            ], $tasks),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// === ТЕСТ 7: Получение сообщений задачи ===
Route::get('/test-kanban/messages', function (\Illuminate\Http\Request $request) {
    try {

        $boardUuid = "928e6e06-b9b0-4cca-a45c-0926ba7539f6";//$request->get('board', config('kanban.default_board_uuid'));

        Kanban::setBaseUrl('https://crm.mypwa.ru/api/v1')
            ->setToken('kb_SyXvkcnhRu7hD0nZAOwga6blD1TFSUEyXNdW9UyQ')
            ->setTimeout(30)
            ->setConnectTimeout(10)
            ->setRetryTimes(3)
            ->setRetrySleep(100)
            ->setLoggingEnabled(true);

        $taskId = $request->get('task_id');

        if (!$taskId) {
            return response()->json([
                'success' => false,
                'error' => 'Укажите task_id: ?task_id=123',
            ], 400);
        }

        $messages = \Exxxar\Kanban\Facades\Kanban::messages()->list((int) $taskId);

        return response()->json([
            'success' => true,
            'task_id' => $taskId,
            'messages_count' => count($messages),
            'messages' => array_map(fn($msg) => [
                'id' => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_label' => $msg->sender_label,
                'message' => $msg->message,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at?->toDateTimeString(),
            ], $messages),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});
