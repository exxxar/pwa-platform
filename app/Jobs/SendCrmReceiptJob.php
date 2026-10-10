<?php

namespace App\Jobs;

use App\Models\Tenant\Tenant;
use App\Services\Tenants\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCrmReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public int $orderId,
        public int $tenantId,
        public ?int $dialogId,
        public string $invoicePath,
        public string $paymentStatusText,
        public bool $kanbanEnabled,
        public ?string $kanbanBoardUuid
    ) {}

    public function handle()
    {
        try {
            // 🚀 ИНИЦИАЛИЗАЦИЯ КОНТЕКСТА ТЕНАНТА
            $tenant = Tenant::find($this->tenantId);
            if (!$tenant) {
                Log::error("[Queue] Tenant #{$this->tenantId} not found for SendCrmReceiptJob.");
                return;
            }

            app()->instance('tenant', $tenant);
            if (function_exists('tenancy')) {
                tenancy()->initialize($tenant);
            }

            // 1. Отправка чека клиенту в чат (запись в БД)
            if ($this->dialogId) {
                MessageService::call()->sendMessage([
                    'message' => "📄 Чек по заказу #{$this->orderId} (Статус: {$this->paymentStatusText})",
                    'file_path' => $this->invoicePath,
                    'dialog_id' => $this->dialogId,
                    'meta' => [
                        'order_id' => $this->orderId,
                        'payment_status_text' => $this->paymentStatusText,
                        'is_system' => true,
                    ],
                    'recipients' => ['client' => true],
                ]);
            }

            // 2. Отправка чека в CRM (Kanban) как вложение к задаче
            if ($this->kanbanEnabled) {
                MessageService::call()->sendMessage([
                    'message' => "📄 Чек по заказу #{$this->orderId} прикреплён",
                    'file_path' => $this->invoicePath,
                    'meta' => [
                        'order_id' => $this->orderId,
                        'kanban_board_uuid' => $this->kanbanBoardUuid,
                        'kanban_payload' => ['type' => 'invoice_attached', 'order_id' => $this->orderId],
                        'is_system' => true,
                    ],
                    'recipients' => ['crm' => true],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[Queue] Ошибка отправки чека (SendCrmReceiptJob): ' . $e->getMessage(), [
                'tenant_id' => $this->tenantId,
                'order_id' => $this->orderId,
                'file_path' => $this->invoicePath,
                'trace' => $e->getTraceAsString()
            ]);
            $this->release($this->backoff);
        }
    }
}
