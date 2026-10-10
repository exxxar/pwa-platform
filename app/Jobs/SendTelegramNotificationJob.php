<?php

namespace App\Jobs;

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

    public string $message;
    public ?string $chatId;
    public ?int $threadId;
    public ?string $token;
    public ?string $filePath;

    // 🔄 Настройки повторных попыток при таймауте
    public int $tries = 3;       // Попробовать 3 раза
    public int $backoff = 10;    // Ждать 10 секунд между попытками

    public function __construct(
        string $message,
        ?string $chatId = null,
        ?int $threadId = null,
        ?string $token = null,
        ?string $filePath = null
    ) {
        $this->message = $message;
        $this->chatId = $chatId;
        $this->threadId = $threadId;
        $this->token = $token;
        $this->filePath = $filePath;
    }

    public function handle()
    {
        try {
            // Используем ваш существующий MessageService, но принудительно включаем только telegram/partners
            MessageService::call()->sendMessage([
                'telegram_message' => $this->message,
                'file_path'        => $this->filePath,
                'telegram_chat_id' => $this->chatId,
                'telegram_thread_id'=> $this->threadId,
                'telegram_token'   => $this->token,
                'recipients'       => [
                    'telegram' => true,
                    'partners' => !is_null($this->threadId), // Если есть thread_id, значит это партнер
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[Queue] Ошибка отправки Telegram из очереди: ' . $e->getMessage(), [
                'message_length' => mb_strlen($this->message),
                'trace' => $e->getTraceAsString()
            ]);

            // Если произошла ошибка (например, таймаут), Laravel автоматически
            // вернет задачу в очередь и повторит её через $backoff секунд.
            $this->release($this->backoff);
        }
    }
}
