<?php

namespace App\Jobs;

use App\Models\Tenant\Order;
use App\Models\Tenant\Tenant;
use App\Services\Tenants\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public int $orderId,
        public int $tenantId,
        public ?int $dialogId,
        public string $clientMessage,
        public string $crmMessage,
        public string $telegramMessage,
        public array $partnerMessages,
        public array $kanbanData
    ) {}

    public function handle()
    {
        Log::info('[Queue Job Started] ProcessOrderNotificationsJob', [
            'order_id' => $this->orderId,
            'tenant_id' => $this->tenantId,
            'dialog_id' => $this->dialogId,
            'has_client_message' => !empty($this->clientMessage),
            'has_crm_message' => !empty($this->crmMessage),
            'has_telegram_message' => !empty($this->telegramMessage),
            'partners_count' => count($this->partnerMessages),
        ]);

        try {
            $tenant = Tenant::find($this->tenantId);
            if (!$tenant) {
                Log::error("[Queue] Tenant #{$this->tenantId} not found for ProcessOrderNotificationsJob.");
                return;
            }

            app()->instance('tenant', $tenant);
            if (function_exists('tenancy')) {
                tenancy()->initialize($tenant);
            }

            // 1. ОТПРАВКА В CRM (Kanban) + Клиенту в БД
            $crmResult = MessageService::call()->sendMessage([
                'client_message' => $this->clientMessage,
                'crm_message'    => $this->crmMessage,
                'dialog_id'      => $this->dialogId,
                'title'          => "Заказ #{$this->orderId}",
                'meta'           => [
                    'order_id'           => $this->orderId,
                    'kanban_board_uuid'  => $this->kanbanData['board_uuid'],
                    'kanban_thread'      => $this->kanbanData['thread'],
                    'kanban_custom_data' => $this->kanbanData['custom_data'],
                    'kanban_payload'     => $this->kanbanData['payload'],
                    'customer_name'      => $this->kanbanData['customer_name'],
                    'customer_phone'     => $this->kanbanData['customer_phone'],
                    'summary_price'      => $this->kanbanData['summary_price'],
                    'need_pickup'        => $this->kanbanData['need_pickup'],
                    'delivery_note'      => $this->kanbanData['delivery_note'],
                ],
                'recipients' => ['client' => true, 'crm' => true],
            ]);

            if (isset($crmResult['client']['status']) && $crmResult['client']['status'] === 'failed') {
                throw new \Exception('Отправка сообщения клиенту вернула статус failed.');
            }
            if (isset($crmResult['crm']['status']) && $crmResult['crm']['status'] === 'failed') {
                throw new \Exception('Отправка сообщения в CRM вернула статус failed.');
            }

            // 2. ОБНОВЛЯЕМ ЗАКАЗ ПОЛУЧЕННЫМ task_id из CRM
            if (!empty($crmResult['crm']['task_id'])) {
                $order = Order::find($this->orderId);
                if ($order) {
                    $order->updateQuietly(['meta' => array_merge($order->meta ?? [], [
                        'kanban_task_id'    => $crmResult['crm']['task_id'],
                        'kanban_message_id' => $crmResult['crm']['message_id'] ?? null,
                        'kanban_board_uuid' => $this->kanbanData['board_uuid'],
                    ])]);
                }
            }

            // 3. ОТПРАВКА В TELEGRAM (Основной чат)
            if (!empty($this->telegramMessage)) {
                $tgResult = MessageService::call()->sendMessage([
                    'telegram_message' => $this->telegramMessage,
                    'recipients'       => ['telegram' => true],
                ]);

                if (isset($tgResult['telegram']['status']) && $tgResult['telegram']['status'] === 'failed') {
                    throw new \Exception('Отправка в Telegram вернула статус failed.');
                }
            }

            // 4. ОТПРАВКА ПАРТНЕРАМ В TELEGRAM
            foreach ($this->partnerMessages as $partnerData) {
                $partnerResult = MessageService::call()->sendMessage([
                    'message'    => $partnerData['message'],
                    'thread_id'  => $partnerData['thread'],
                    'title'      => "Заказ #{$this->orderId} — {$partnerData['name']}",
                    'meta'       => [
                        'order_id' => $this->orderId,
                        'partner_id' => $partnerData['id'] ?? null,
                        'type' => 'partner_order',
                        'is_system' => true
                    ],
                    'recipients' => ['partners' => true],
                ]);

                if (isset($partnerResult['partners']['status']) && $partnerResult['partners']['status'] === 'failed') {
                    throw new \Exception("Отправка партнеру {$partnerData['name']} вернула статус failed.");
                }
            }

            Log::info('[Queue Job Success] ProcessOrderNotificationsJob completed', ['order_id' => $this->orderId]);

        } catch (\Throwable $e) {
            Log::error('[Queue Job Failed] ProcessOrderNotificationsJob: ' . $e->getMessage(), [
                'tenant_id' => $this->tenantId,
                'order_id' => $this->orderId,
                'trace' => $e->getTraceAsString()
            ]);
            $this->release($this->backoff);
        }
    }
}
