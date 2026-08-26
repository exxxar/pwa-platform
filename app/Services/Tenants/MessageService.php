<?php

namespace App\Services\Tenants;

use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use Exxxar\Kanban\Services\KanbanClient;
use Illuminate\Support\Facades\Log;
use function App\Services\str_contains;

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
     * 🎯 ЕДИНАЯ ТОЧКА ОТПРАВКИ СООБЩЕНИЙ
     *
     * @param array $data
     *   'message'          => string        Текст (HTML)
     *   'file_path'        => string|null   Путь к файлу (PDF чек и т.д.)
     *   'dialog_id'        => int|null      Для записи в чат с клиентом
     *   'thread_id'        => string|null   Telegram thread партнёра
     *   'title'            => string|null   Заголовок (CRM / Telegram)
     *   'meta'             => array         kanban_payload, kanban_custom_data и т.д.
     *   'recipients'       => [
     *       'client'    => bool,   // → запись в TenantMessage
     *       'partners'  => bool,   // → Telegram thread
     *       'crm'       => bool,   // → Kanban
     *       'telegram'  => bool,   // → Telegram channel/support_chat
     *   ],
     *   // Опциональные overrides для Telegram:
     *   'telegram_chat_id'  => string|null,
     *   'telegram_thread_id'=> int|null,
     *   'telegram_token'    => string|null,
     *   'parse_mode'        => string,      // HTML|Markdown (по умолчанию HTML)
     */
    public function sendMessage(array $data): array
    {
        $result = [
            'client'   => null,
            'partners' => null,
            'crm'      => null,
            'telegram' => null,
        ];

        $recipients = $data['recipients'] ?? ['client' => true];

        // 1. КЛИЕНТУ (запись в БД)
        if (!empty($recipients['client']) && !empty($data['dialog_id'])) {
            try {
                $clientData = $data;
                // 🎯 Приоритет отдаем client_message, если он есть
                $clientData['message'] = $data['client_message'] ?? $data['message'] ?? '';
                $result['client'] = $this->sendToDialog($clientData);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки клиенту: ' . $e->getMessage());
            }
        }

        // 2. ПАРТНЁРАМ (Telegram thread)
        if (!empty($recipients['partners'])) {
            try {
                $partnerData = $data;
                $partnerData['message'] = $data['partner_message'] ?? $data['message'] ?? '';
                $result['partners'] = $this->sendToPartnerThread($partnerData);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки партнёрам: ' . $e->getMessage());
            }
        }

        // 3. CRM (Kanban)
        if (!empty($recipients['crm']) && $this->crmEnabled) {
            try {
                $crmData = $data;
                $crmData['message'] = $data['crm_message'] ?? $data['message'] ?? '';
                $result['crm'] = $this->sendToCrm($crmData);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки в CRM: ' . $e->getMessage());
            }
        }

        // 4. ПРЯМАЯ ОТПРАВКА В TELEGRAM (канал/группа уведомлений)
        if (!empty($recipients['telegram'])) {
            try {
                $tgData = $data;
                // 🎯 Приоритет отдаем telegram_message
                $tgData['message'] = $data['telegram_message'] ?? $data['message'] ?? '';

                // 🎯 Автоматическое добавление ссылок (опционально, если нужно добавить их к любому сообщению)
                if (!empty($data['meta']['append_telegram_links'])) {
                    $tgData['message'] = $this->appendTelegramLinks($tgData['message'], $tgData);
                }

                $result['telegram'] = $this->sendToTelegram($tgData);
            } catch (\Throwable $e) {
                Log::error('[MessageService] Ошибка отправки в Telegram: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * 🎯 Автоматически добавляет ссылки на диалог и профиль клиента к сообщению
     * (Используйте этот метод, если захотите генерировать ссылки внутри самого MessageService)
     */
    protected function appendTelegramLinks(string $message, array $data): string
    {
        $dialogId = $data['dialog_id'] ?? $data['meta']['dialog_id'] ?? null;
        $userId   = $data['meta']['tenant_user_id'] ?? null;
        $baseUrl  = request()->getSchemeAndHttpHost();

        if ($baseUrl && $dialogId) {
            $chatUrl = "{$baseUrl}/pwa#/chat/{$dialogId}";
            $message .= "\n🔗 <a href=\"{$chatUrl}\">Открыть чат</a>";
        }

        if ($userId) {
            $client = \App\Models\Tenant\TenantUser::query()->find($userId);
            if ($client && method_exists($client, 'getTelegramInfo')) {
                $clientInfo = $client->getTelegramInfo();
                if (!empty($clientInfo['profile_url'])) {
                    $message .= "\n👤 <a href=\"{$clientInfo['profile_url']}\">Профиль клиента</a>";
                }
            }
        }

        return $message . "\n";
    }

    // ==========================================
    // 📱 ОТПРАВКА КЛИЕНТУ (в диалог)
    // ==========================================

    protected function sendToDialog(array $data): array
    {
        $dialog = TenantDialog::find($data['dialog_id']);
        if (!$dialog) {
            throw new \Exception("Диалог #{$data['dialog_id']} не найден");
        }

        $isSystem = $data['meta']['is_system'] ?? false;

        $message = TenantMessage::create([
            'tenant_id'      => $dialog->tenant_id,
            'tenant_user_id' => $dialog->tenant_user_id,
            'dialog_id'      => $dialog->id,
            'message'        => $data['message'] ?? '',
            'meta'           => array_merge($data['meta'] ?? [], [
                'is_system'   => $isSystem,
                'sender_type' => $isSystem ? 'system' : 'user',
            ]),
            'is_read'        => false,
        ]);

        $dialog->update(['last_message_at' => now()]);

        return ['dialog_id' => $dialog->id, 'message_id' => $message->id];
    }

    // ==========================================
    // 📣 ОТПРАВКА ПАРТНЁРАМ (Telegram thread)
    // ==========================================

    protected function sendToPartnerThread(array $data): array
    {
        $threadId = $data['thread_id'] ?? null;

        if (!$threadId) {
            return ['status' => 'skipped', 'reason' => 'no thread_id'];
        }

        return $this->sendToTelegram(array_merge($data, [
            'telegram_thread_id' => $threadId,
        ]));
    }

    // ==========================================
    // 📢 ОТПРАВКА В TELEGRAM (универсальный метод)
    // ==========================================

    protected function sendToTelegram(array $data): array
    {
        $tgSettings = $this->tenant->settings['telegram'] ?? [];

        $token    = $data['telegram_token']     ?? $tgSettings['token']          ?? null;
        $chatId   = $data['telegram_chat_id']   ?? $tgSettings['channel_id']     ?? $tgSettings['support_chat_id'] ?? null;
        $threadId = $data['telegram_thread_id'] ?? $tgSettings['thread_id']      ?? null;

        if (!$token || !$chatId) {
            Log::debug('[MessageService] Telegram не настроен для tenant #' . $this->tenant->id);
            return ['status' => 'skipped', 'reason' => 'no telegram config'];
        }

        $payload = [
            'chat_id'                  => $chatId,
            'text'                     => $data['message'] ?? '',
            'parse_mode'               => $data['parse_mode'] ?? 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($threadId) {
            $payload['message_thread_id'] = (int) $threadId;
        }

        // 📎 Если есть файл — отправляем как документ
        if (!empty($data['file_path'])) {
            return $this->sendTelegramFile($token, $payload, $data['file_path']);
        }

        $success = $this->executeTelegramRequest($token, 'sendMessage', $payload);

        return [
            'status'    => $success ? 'sent' : 'failed',
            'chat_id'   => $chatId,
            'thread_id' => $threadId,
        ];
    }

    protected function sendTelegramFile(string $token, array $basePayload, string $filePath): array
    {
        $fullPath = storage_path('app/public/' . $filePath);
        if (!file_exists($fullPath)) {
            $fullPath = $filePath; // может быть уже абсолютный
        }

        if (!file_exists($fullPath)) {
            Log::warning('[MessageService] Файл не найден: ' . $filePath);
            return ['status' => 'failed', 'reason' => 'file not found'];
        }

        $cFile   = new \CURLFile($fullPath);
        $payload = [
            'chat_id'    => $basePayload['chat_id'],
            'document'   => $cFile,
            'caption'    => $basePayload['text'] ?? '',
            'parse_mode' => $basePayload['parse_mode'] ?? 'HTML',
        ];

        if (!empty($basePayload['message_thread_id'])) {
            $payload['message_thread_id'] = $basePayload['message_thread_id'];
        }

        $success = $this->executeTelegramRequest($token, 'sendDocument', $payload, true);

        return ['status' => $success ? 'sent' : 'failed'];
    }

    /**
     * Низкоуровневый cURL запрос к Telegram Bot API
     */
    protected function executeTelegramRequest(
        string $token,
        string $method,
        array  $payload,
        bool   $multipart = false
    ): bool {
        $ch  = curl_init();
        $url = "https://api.telegram.org/bot{$token}/{$method}";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($multipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            Log::warning("[MessageService] Telegram {$method} error. HTTP: {$httpCode} | Error: {$curlError} | Response: {$response}");
            return false;
        }

        return true;
    }

    // ==========================================
    // 📊 ОТПРАВКА В CRM (Kanban)
    // ==========================================

    protected function sendToCrm(array $data): array
    {
        if (!$this->crmEnabled) {
            return ['status' => 'skipped', 'reason' => 'CRM not configured'];
        }

        $kanbanConfig = $this->tenant->settings['kanban'] ?? [];
        $boardUuid    = $data['meta']['kanban_board_uuid'] ?? $kanbanConfig['board_uuid'] ?? null;
        $thread       = $data['meta']['kanban_thread']     ?? $kanbanConfig['order_thread'] ?? 0;

        if (!$boardUuid) {
            return ['status' => 'skipped', 'reason' => 'No board UUID'];
        }

        $phone          = $data['meta']['customer_phone'] ?? null;
        $existingTaskId = $this->findKanbanClientByPhone($boardUuid, $phone);

        $query = \Exxxar\Kanban\Facades\Kanban::query()
            ->message($data['message'])
            ->senderType('system')
            ->senderLabel('FoodShop Checkout')
            ->payload($data['meta']['kanban_payload'] ?? []);

        if (!empty($data['file_path'])) {
            $query->file($data['file_path']);
        }

        if ($existingTaskId) {
            \Exxxar\Kanban\Facades\Kanban::clients()->updateCustomData(
                $existingTaskId,
                $data['meta']['kanban_custom_data'] ?? []
            );

            $result = $query->task($existingTaskId)->send();
        } else {
            $result = \Exxxar\Kanban\Facades\Kanban::client()
                ->board($boardUuid)
                ->thread($thread)
                ->title($data['title'] ?? ($data['meta']['customer_name'] ?? 'Новый клиент'))
                ->priority('medium')
                ->label('order')
                ->label('foodshop')
                ->clientData(array_merge([
                    'company_name'   => $data['meta']['customer_name'] ?? 'Нет имени',
                    'contact_person' => $data['meta']['customer_name'] ?? 'Нет имени',
                    'phone'          => $phone,
                    'source'         => 'FoodShop',
                    'cost'           => $data['meta']['summary_price'] ?? 0,
                    'placement_type' => ($data['meta']['need_pickup'] ?? false) ? 'Самовывоз' : 'Доставка',
                    'address'        => $data['meta']['delivery_note'] ?? '',
                    'custom_data'    => $data['meta']['kanban_custom_data'] ?? [],
                ], $data['meta']['kanban_client_extra'] ?? []))
                ->message($data['message'])
                ->senderType('system')
                ->senderLabel('FoodShop Checkout')
                ->payload($data['meta']['kanban_payload'] ?? [])
                ->send();
        }

        return [
            'status'     => 'sent',
            'task_id'    => $result['task_id']    ?? null,
            'message_id' => $result['message_id'] ?? null,
        ];
    }

    protected function findKanbanClientByPhone(string $boardUuid, ?string $phone): ?string
    {
        if (empty($phone)) return null;

        try {
            $clients = \Exxxar\Kanban\Facades\Kanban::clients()
                ->board($boardUuid)
                ->search($phone);

            foreach ($clients as $client) {
                $clientPhone = $client['phone'] ?? ($client['custom_data']['phone'] ?? null);
                if ($clientPhone && str_contains($clientPhone, $phone)) {
                    return $client['id'];
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
