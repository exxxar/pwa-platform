<?php

namespace App\Services;

use App\Models\Tenant\Broadcast;
use App\Models\Tenant\BroadcastButton;
use App\Models\Tenant\BroadcastMedia;
use App\Models\Tenant\BroadcastRecipient;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BroadcastService
{

    /**
     * 🆕 Создание рассылки
     */
    public function create(array $data, array $mediaFiles = []): Broadcast
    {
        return DB::transaction(function () use ($data, $mediaFiles) {
            // 🆕 Безопасное получение значений с дефолтами
            $scheduledAt = $data['scheduled_at'] ?? null;

            $broadcast = Broadcast::create([
                'tenant_id' => app('tenant')->id,
                'tenant_user_id' => auth('tenant')->id(),
                'title' => $data['title'] ?? '',
                'message' => $data['message'] ?? null,
                'status' => $scheduledAt
                    ? Broadcast::STATUS_SCHEDULED
                    : Broadcast::STATUS_DRAFT,
                'recipient_type' => $data['recipient_type'] ?? 'all',
                'recipient_filters' => $data['recipient_filters'] ?? null,
                'scheduled_at' => $scheduledAt,
            ]);

            // Загрузка медиа
            $this->uploadMedia($broadcast, $mediaFiles);

            // Создание кнопок (если переданы как массив)
            if (!empty($data['buttons'])) {
                $buttons = $data['buttons'];

                // Если это JSON строка — декодируем
                if (is_string($buttons)) {
                    $buttons = json_decode($buttons, true) ?? [];
                }

                if (is_array($buttons)) {
                    $this->createButtons($broadcast, $buttons);
                }
            }

            return $broadcast;
        });
    }

    /**
     * 🆕 Загрузка медиафайлов
     */
    private function uploadMedia(Broadcast $broadcast, array $files): void
    {
        $order = 0;

        foreach (['image', 'video', 'audio'] as $type) {
            $fileList = $files[$type] ?? [];

            if (!is_array($fileList)) {
                $fileList = [$fileList];
            }

            foreach ($fileList as $file) {
                if (!$file || !($file instanceof \Illuminate\Http\UploadedFile)) {
                    continue;
                }

                $path = $file->store(
                    "broadcasts/{$broadcast->id}/{$type}",
                    'public'
                );

                BroadcastMedia::create([
                    'broadcast_id' => $broadcast->id,
                    'type' => $type,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'sort_order' => $order++,
                ]);
            }
        }
    }

    /**
     * 🆕 Создание кнопок
     */
    private function createButtons(Broadcast $broadcast, array $buttons): void
    {
        foreach ($buttons as $rowIndex => $row) {
            if (!is_array($row)) continue;

            foreach ($row as $position => $button) {
                if (!is_array($button)) continue;

                // 🆕 Пропускаем кнопки без текста
                if (empty($button['text'])) continue;

                BroadcastButton::create([
                    'broadcast_id' => $broadcast->id,
                    'text' => $button['text'],
                    'url' => $button['url'] ?? null,
                    'callback_data' => $button['callback_data'] ?? null,
                    'type' => $button['type'] ?? 'callback',
                    'row_index' => (int) $rowIndex,
                    'position' => (int) $position,
                ]);
            }
        }
    }

    /**
     * 🆕 Получение получателей по фильтру
     */
    public function getRecipients(Broadcast $broadcast): \Illuminate\Support\Collection
    {
        $query = TenantUser::where('tenant_id', $broadcast->tenant_id);

        switch ($broadcast->recipient_type) {
            case 'active':
                $query->where('is_active', true);
                break;

            case 'vip':
                $query->where('is_vip', true);
                break;

            case 'segment':
                $filters = $broadcast->recipient_filters ?? [];
                if (!empty($filters['min_orders'])) {
                    $query->whereHas('orders', function ($q) use ($filters) {
                        $q->havingRaw('COUNT(*) >= ?', [$filters['min_orders']]);
                    });
                }
                break;

            case 'custom':
                $ids = $broadcast->recipient_filters['user_ids'] ?? [];
                $query->whereIn('id', $ids);
                break;

            case 'all':
            default:
                // Все пользователи
                break;
        }

        return $query->get();
    }

    /**
     * 🆕 Отправка рассылки
     */
    public function send(Broadcast $broadcast): bool
    {
        if (!in_array($broadcast->status, [
            Broadcast::STATUS_DRAFT,
            Broadcast::STATUS_SCHEDULED,
        ])) {
            return false;
        }

        $broadcast->update(['status' => Broadcast::STATUS_SENDING]);

        try {
            $recipients = $this->getRecipients($broadcast);

            $broadcast->update(['total_recipients' => $recipients->count()]);

            foreach ($recipients as $user) {
                $this->sendToUser($broadcast, $user);
            }

            $broadcast->update([
                'status' => Broadcast::STATUS_SENT,
                'sent_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('[Broadcast] Ошибка отправки: ' . $e->getMessage());

            $broadcast->update([
                'status' => Broadcast::STATUS_FAILED,
            ]);

            return false;
        }
    }

    /**
     * 🆕 Отправка конкретному пользователю
     */
    private function sendToUser(Broadcast $broadcast, TenantUser $user): void
    {
        try {
            DB::transaction(function () use ($broadcast, $user) {
                // Находим или создаём диалог с пользователем
                $dialog = TenantDialog::firstOrCreate(
                    [
                        'tenant_id' => $broadcast->tenant_id,
                        'tenant_user_id' => $user->id,
                        'type' => 'broadcast',
                    ],
                    [
                        'title' => 'Рассылка',
                        'is_active' => true,
                    ]
                );

                // Создаём сообщение в диалоге
                $message = TenantMessage::create([
                    'tenant_id' => $broadcast->tenant_id,
                    'dialog_id' => $dialog->id,
                    'tenant_user_id' => null, // от системы
                    'type' => 'broadcast',
                    'content' => $broadcast->message,
                    'metadata' => [
                        'broadcast_id' => $broadcast->id,
                        'media' => $broadcast->media->pluck('path')->toArray(),
                        'keyboard' => $broadcast->keyboard,
                    ],
                    'is_read' => false,
                ]);

                // Создаём запись о получателе
                BroadcastRecipient::create([
                    'broadcast_id' => $broadcast->id,
                    'tenant_user_id' => $user->id,
                    'status' => BroadcastRecipient::STATUS_SENT,
                    'dialog_message_id' => $message->id,
                    'sent_at' => now(),
                ]);

                // Обновляем счётчики
                $broadcast->increment('sent_count');
            });

        } catch (\Exception $e) {
            Log::error("[Broadcast] Ошибка отправки пользователю #{$user->id}: " . $e->getMessage());

            BroadcastRecipient::create([
                'broadcast_id' => $broadcast->id,
                'tenant_user_id' => $user->id,
                'status' => BroadcastRecipient::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            $broadcast->increment('failed_count');
        }
    }

    /**
     * 🆕 Отмена рассылки
     */
    public function cancel(Broadcast $broadcast): bool
    {
        if (!in_array($broadcast->status, [
            Broadcast::STATUS_DRAFT,
            Broadcast::STATUS_SCHEDULED,
        ])) {
            return false;
        }

        return $broadcast->update(['status' => Broadcast::STATUS_CANCELLED]);
    }

    /**
     * 🆕 Удаление медиафайла
     */
    public function deleteMedia(BroadcastMedia $media): bool
    {
        Storage::disk('public')->delete($media->path);
        return $media->delete();
    }

    /**
     * 🆕 Получить статистику рассылки
     */
    public function getStatistics(Broadcast $broadcast): array
    {
        return [
            'total' => $broadcast->total_recipients,
            'sent' => $broadcast->sent_count,
            'delivered' => $broadcast->delivered_count,
            'read' => $broadcast->read_count,
            'failed' => $broadcast->failed_count,
            'progress' => $broadcast->progress_percent,
            'delivery_rate' => $broadcast->total_recipients > 0
                ? round(($broadcast->delivered_count / $broadcast->total_recipients) * 100, 1)
                : 0,
            'read_rate' => $broadcast->sent_count > 0
                ? round(($broadcast->read_count / $broadcast->sent_count) * 100, 1)
                : 0,
        ];
    }
}
