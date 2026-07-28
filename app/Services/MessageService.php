<?php

namespace App\Services;

use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use Exxxar\Kanban\Services\KanbanClient;
use Illuminate\Support\Facades\Log;

class MessageService
{
    protected $tenant;
    protected $tenantUser;
    protected ?KanbanClient $kanbanClient = null;
    protected bool $crmEnabled = false;

    public static function call(): self
    {
        return app(self::class);
    }

    public function __construct()
    {
        $this->tenant = app('tenant');
        $this->tenantUser = auth('tenant')->user();
        $this->initCrmClient();
    }

    protected function initCrmClient(): void
    {
        try {
            $kanbanConfig = $this->tenant->settings['kanban'] ?? [];
            $token = $kanbanConfig['token'] ?? null;

            if (empty($token) || !is_string($token)) {
                $this->crmEnabled = false;
                return;
            }

            // Инициализация фасадa Kanban
            \Exxxar\Kanban\Facades\Kanban::setBaseUrl($kanbanConfig['base_url'] ?? config('kanban.base_url'))
                ->setToken($token)
                ->setTimeout(30)
                ->setConnectTimeout(10)
                ->setRetryTimes(3)
                ->setRetrySleep(100)
                ->setLoggingEnabled(true);

            $this->kanbanClient = app(KanbanClient::class);
            $this->crmEnabled = true;
        } catch (\Throwable $e) {
            $this->crmEnabled = false;
            Log::warning('[MessageService] CRM не инициализирована: ' . $e->getMessage());
        }
    }

    /**
     * Универсальный метод отправки
     *
     * @param array $data [
     *   'message' => string,           // Текст сообщения
     *   'file_path' => string|null,    // Путь к файлу (например, PDF чек)
     *   'dialog_id' => int|null,       // ID диалога (для клиента)
     *   'thread_id' => string|null,    // ID потока (для партнеров)
     *   'title' => string|null,        // Заголовок (для CRM/партнеров)
     *   'meta' => array,               // Метаданные (включая kanban_payload, kanban_custom_data и т.д.)
     *   'recipients' => [              // Кому отправлять
     *       'client' => bool,
     *       'partners' => bool,
     *       'crm' => bool,
     *   ],
     * ]
     */
    public function sendMessage(array $data): array
    {
        $result = ['client' => null, 'partners' => null, 'crm' => null];
        $recipients = $data['recipients'] ?? ['client' => true];

        // 1. Отправка КЛИЕНТУ
        if (!empty($recipients['client']) && !empty($data['dialog_id'])) {
            try {
                $result['client'] = $this->sendToDialog($data);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки клиенту: ' . $e->getMessage());
            }
        }

        // 2. Отправка ПАРТНЁРАМ
        if (!empty($recipients['partners']) && !empty($data['thread_id'])) {
            try {
                $result['partners'] = $this->sendToPartnerThread($data);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки партнёрам: ' . $e->getMessage());
            }
        }

        // 3. Отправка в CRM (Kanban)
        if (!empty($recipients['crm']) && $this->crmEnabled) {
            try {
                $result['crm'] = $this->sendToCrm($data);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки в CRM: ' . $e->getMessage());
            }
        }

        return $result;
    }

    protected function sendToDialog(array $data): array
    {
        $dialog = TenantDialog::find($data['dialog_id']);
        if (!$dialog) throw new \Exception("Диалог #{$data['dialog_id']} не найден");

        $isSystem = $data['meta']['is_system'] ?? false;

        $message = TenantMessage::create([
            'tenant_id' => $dialog->tenant_id,
            'dialog_id' => $dialog->id,
            'message' => $data['message'] ?? '',
            'meta' => array_merge($data['meta'] ?? [], [
                'is_system' => $isSystem,
                'sender_type' => $isSystem ? 'system' : 'user',
            ]),
            'is_read' => false,
        ]);

        $dialog->update(['last_message_at' => now()]);

        // Если есть файл, прикрепляем его к сообщению (зависит от вашей реализации TenantMessage)
        if (!empty($data['file_path'])) {
            // $message->attachFile($data['file_path']);
        }

        return ['dialog_id' => $dialog->id, 'message_id' => $message->id];
    }

    protected function sendToPartnerThread(array $data): array
    {
        // 🚨 ЗАМЕНИТЕ ЭТОТ БЛОК на вызов вашего реального Telegram-сервиса
        // Пример: app(\App\Services\TelegramService::class)->sendToThread($data['thread_id'], $data['message']);

        Log::info('[MessageService] Уведомление партнёру (Telegram)', [
            'thread_id' => $data['thread_id'],
            'title' => $data['title'] ?? 'Новый заказ',
            'message_preview' => mb_substr($data['message'] ?? '', 0, 100),
        ]);

        return ['thread_id' => $data['thread_id'], 'status' => 'sent'];
    }

    protected function sendToCrm(array $data): array
    {
        if (!$this->crmEnabled) return ['status' => 'skipped', 'reason' => 'CRM not configured'];

        $kanbanConfig = $this->tenant->settings['kanban'] ?? [];
        $boardUuid = $data['meta']['kanban_board_uuid'] ?? $kanbanConfig['board_uuid'] ?? null;
        $thread = $data['meta']['kanban_thread'] ?? $kanbanConfig['order_thread'] ?? 0;

        if (!$boardUuid) return ['status' => 'skipped', 'reason' => 'No board UUID'];

        $phone = $data['meta']['customer_phone'] ?? null;
        $existingTaskId = $this->findKanbanClientByPhone($boardUuid, $phone);

        // Базовый билдер запроса
        $query = \Exxxar\Kanban\Facades\Kanban::query()
            ->message($data['message'])
            ->senderType('system')
            ->senderLabel('FoodShop Checkout')
            ->payload($data['meta']['kanban_payload'] ?? []);

        if (!empty($data['file_path'])) {
            $query->file($data['file_path']);
        }

        if ($existingTaskId) {
            // Обновляем кастомные данные существующего клиента
            \Exxxar\Kanban\Facades\Kanban::clients()->updateCustomData(
                $existingTaskId,
                $data['meta']['kanban_custom_data'] ?? []
            );

            $result = $query->task($existingTaskId)->send();
        } else {
            // Создаем нового клиента
            $result = \Exxxar\Kanban\Facades\Kanban::client()
                ->board($boardUuid)
                ->thread($thread)
                ->title($data['title'] ?? ($data['meta']['customer_name'] ?? 'Новый клиент'))
                ->priority('medium')
                ->label('order')
                ->label('foodshop')
                ->clientData(array_merge([
                    'company_name' => $data['meta']['customer_name'] ?? 'Нет имени',
                    'contact_person' => $data['meta']['customer_name'] ?? 'Нет имени',
                    'phone' => $phone,
                    'source' => 'FoodShop',
                    'cost' => $data['meta']['summary_price'] ?? 0,
                    'placement_type' => ($data['meta']['need_pickup'] ?? false) ? 'Самовывоз' : 'Доставка',
                    'address' => $data['meta']['delivery_note'] ?? '',
                    'custom_data' => $data['meta']['kanban_custom_data'] ?? [],
                ], $data['meta']['kanban_client_extra'] ?? []))
                ->message($data['message']) // Kanban позволяет добавить первое сообщение при создании
                ->senderType('system')
                ->senderLabel('FoodShop Checkout')
                ->payload($data['meta']['kanban_payload'] ?? [])
                ->send();
        }

        return [
            'status' => 'sent',
            'task_id' => $result['task_id'] ?? null,
            'message_id' => $result['message_id'] ?? null,
        ];
    }

    /**
     * Поиск клиента в Kanban по телефону
     */
    protected function findKanbanClientByPhone(string $boardUuid, ?string $phone): ?string
    {
        if (empty($phone)) return null;

        try {
            // Адаптируйте этот вызов под реальный API вашего Kanban-пакета для поиска по custom_data или phone
            $clients = \Exxxar\Kanban\Facades\Kanban::clients()
                ->board($boardUuid)
                ->search($phone); // Или другой метод поиска, предусмотренный пакетом

            // Если пакет возвращает коллекцию, ищем совпадение по телефону в clientData
            foreach ($clients as $client) {
                $clientPhone = $client['phone'] ?? ($client['custom_data']['phone'] ?? null);
                if ($clientPhone && str_contains($clientPhone, $phone)) {
                    return $client['id']; // Возвращаем task_id / client_id
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[MessageService] Ошибка поиска клиента в Kanban: ' . $e->getMessage());
        }

        return null;
    }

    public function isCrmEnabled(): bool
    {
        return $this->crmEnabled;
    }
}
