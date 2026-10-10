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

    public int $tries = 3;       // Максимум 3 попытки
    public int $backoff = 10;    // Ждать 10 секунд между попытками

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
        Log::info('[Queue Job Started] SendTelegramNotificationJob', [
            'tenant_id' => $this->tenantId,
            'message_length' => mb_strlen($this->message),
            'chat_id' => $this->chatId,
            'thread_id' => $this->threadId,
            'has_file' => !empty($this->filePath),
        ]);

        try {
            $tenant = Tenant::find($this->tenantId);
            if (!$tenant) {
                Log::error("[Queue] Tenant #{$this->tenantId} not found for SendTelegramNotificationJob.");
                return; // Прерываем без ретрая, если тенанта нет в БД
            }

            app()->instance('tenant', $tenant);
            if (function_exists('tenancy')) {
                tenancy()->initialize($tenant);
            }

            // 1. Получаем результат отправки из MessageService
            $result = MessageService::call()->sendMessage([
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

            // 🚀 2. ПРОВЕРКА СТАТУСА: Если API вернул 'failed', выбрасываем исключение
            if (isset($result['telegram']['status']) && $result['telegram']['status'] === 'failed') {
                throw new \Exception('Telegram API вернул статус failed для основного канала.');
            }

            if (isset($result['partners']['status']) && $result['partners']['status'] === 'failed') {
                throw new \Exception('Telegram API вернул статус failed для канала партнеров.');
            }

            Log::info('[Queue Job Success] SendTelegramNotificationJob completed', ['tenant_id' => $this->tenantId]);

        } catch (\Throwable $e) {
            Log::error('[Queue Job Failed] SendTelegramNotificationJob: ' . $e->getMessage(), [
                'tenant_id' => $this->tenantId,
                'message_length' => mb_strlen($this->message),
                'trace' => $e->getTraceAsString()
            ]);

            // 🔄 3. ВОЗВРАЩАЕМ ЗАДАЧУ В ОЧЕРЕДЬ для повторной попытки
            $this->release($this->backoff);
        }
    }
}
