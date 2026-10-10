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

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    // 🎯 ПОЛНЫЙ И ПРАВИЛЬНЫЙ КОНСТРУКТОР (все свойства инициализируются здесь)
    public function __construct(
        public int $tenantId,
        public string $message,
        public ?string $chatId = null,
        public ?int $threadId = null,
        public ?string $token = null,
        public ?string $filePath = null
    ) {}

    public function handle()
    {
        // 📝 ЛОГИРОВАНИЕ ЗАПУСКА ЗАДАЧИ
        Log::info('[Queue Job Started] SendTelegramNotificationJob', [
            'tenant_id' => $this->tenantId,
            'message_length' => mb_strlen($this->message),
            'chat_id' => $this->chatId,
            'thread_id' => $this->threadId,
            'has_file' => !empty($this->filePath),
        ]);

        try {
            // 🚀 ИНИЦИАЛИЗАЦИЯ КОНТЕКСТА ТЕНАНТА
            $tenant = Tenant::find($this->tenantId);
            if (!$tenant) {
                Log::error("[Queue] Tenant #{$this->tenantId} not found for SendTelegramNotificationJob.");
                return;
            }

            app()->instance('tenant', $tenant);
            if (function_exists('tenancy')) {
                tenancy()->initialize($tenant);
            }

            MessageService::call()->sendMessage([
                'telegram_message' => $this->message,
                'file_path'        => $this->filePath,
                'telegram_chat_id' => $this->chatId,
                'telegram_thread_id'=> $this->threadId,
                'telegram_token'   => $this->token,
                'recipients'       => [
                    'telegram' => true,
                    'partners' => !is_null($this->threadId),
                ],
            ]);

            Log::info('[Queue Job Success] SendTelegramNotificationJob completed', ['tenant_id' => $this->tenantId]);

        } catch (\Throwable $e) {
            Log::error('[Queue Job Failed] SendTelegramNotificationJob: ' . $e->getMessage(), [
                'tenant_id' => $this->tenantId,
                'message_length' => mb_strlen($this->message),
                'trace' => $e->getTraceAsString()
            ]);
            $this->release($this->backoff);
        }
    }
}
