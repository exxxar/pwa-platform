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
            $token = $this->tenant->settings['crm']['token'] ?? null;

            if (empty($token) || !is_string($token)) {
                $this->crmEnabled = false;
                return;
            }

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
     *   'dialog_id' => int|null,       // ID диалога (если есть — пишем в него)
     *   'thread_id' => string|null,    // ID потока для CRM/уведомлений
     *   'title' => string|null,        // Заголовок для CRM
     *   'meta' => array,               // Метаданные
     *   'recipients' => [              // 🆕 Кому отправлять
     *       'client' => bool,          // Клиенту (в его диалог заказа)
     *       'partners' => bool,        // Партнёрам (в их треды)
     *       'crm' => bool,             // В CRM
     *   ],
     * ]
     */
    public function sendMessage(array $data): array
    {
        $result = [
            'client' => null,
            'partners' => null,
            'crm' => null,
        ];

        $recipients = $data['recipients'] ?? ['client' => true];

        // 1. Отправка КЛИЕНТУ (в диалог заказа)
        if (!empty($recipients['client']) && !empty($data['dialog_id'])) {
            try {
                $result['client'] = $this->sendToDialog($data);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки клиенту: ' . $e->getMessage());
            }
        }

        // 2. Отправка ПАРТНЁРАМ (в их треды уведомлений)
        if (!empty($recipients['partners']) && !empty($data['thread_id'])) {
            try {
                $result['partners'] = $this->sendToPartnerThread($data);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки партнёрам: ' . $e->getMessage());
            }
        }

        // 3. Отправка в CRM
        if (!empty($recipients['crm']) && $this->crmEnabled) {
            try {
                $result['crm'] = $this->sendToCrm($data);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки в CRM: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Отправка в конкретный диалог (клиенту)
     */
    protected function sendToDialog(array $data): array
    {
        $dialogId = $data['dialog_id'];
        $dialog = TenantDialog::find($dialogId);

        if (!$dialog) {
            throw new \Exception("Диалог #{$dialogId} не найден");
        }

        $isSystem = $data['meta']['is_system'] ?? false;

        $message = TenantMessage::create([
            'tenant_id' => $dialog->tenant_id,
            'dialog_id' => $dialog->id,
            'message' => $data['message'] ?? '',
            'meta' => array_merge(
                $data['meta'] ?? [],
                [
                    'is_system' => $isSystem,
                    'sender_type' => $isSystem ? 'system' : 'user',
                ]
            ),
            'is_read' => false,
        ]);

        $dialog->update(['last_message_at' => now()]);

        return [
            'dialog_id' => $dialog->id,
            'message_id' => $message->id,
        ];
    }

    /**
     * Отправка в тред партнёра (уведомление)
     */
    protected function sendToPartnerThread(array $data): array
    {
        $threadId = $data['thread_id'];

        // TODO: Реализовать отправку в Telegram-тред партнёра
        // Пока просто логируем
        Log::info('[MessageService] Уведомление партнёру', [
            'thread_id' => $threadId,
            'message_preview' => mb_substr($data['message'] ?? '', 0, 100),
        ]);

        return [
            'thread_id' => $threadId,
            'status' => 'sent',
        ];
    }

    /**
     * Отправка в CRM (Kanban)
     */
    protected function sendToCrm(array $data): array
    {
        if (!$this->crmEnabled || !$this->kanbanClient) {
            return ['status' => 'skipped', 'reason' => 'CRM not configured'];
        }

        try {
            // TODO: Реальная логика отправки в CRM
            Log::info('[MessageService] CRM: сообщение подготовлено', [
                'title' => $data['title'] ?? 'Без заголовка',
                'thread_id' => $data['thread_id'] ?? null,
                'message_preview' => mb_substr($data['message'] ?? '', 0, 100),
            ]);

            return [
                'status' => 'stub',
                'message' => 'CRM integration is not implemented yet',
            ];
        } catch (\Throwable $e) {
            Log::error('[MessageService] CRM ошибка: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Проверка, включена ли CRM
     */
    public function isCrmEnabled(): bool
    {
        return $this->crmEnabled;
    }
}
