<?php

namespace App\Services;

use App\Models\Tenant\Order;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderDialogService
{
    /**
     * Создание или получение ЕДИНСТВЕННОГО диалога для заказа
     * Гарантирует, что на один заказ — один диалог
     */
    public function getOrCreateDialog(Order $order): TenantDialog
    {
        // Ищем по external_task_id = "order_{id}"
        return TenantDialog::firstOrCreate(
            [
                'tenant_id' => $order->tenant_id,
                'external_task_id' => "order_{$order->id}",
                'type' => 'order',
            ],
            [
                'tenant_user_id' => $order->tenant_user_id,
                'title' => "Заказ #{$order->id}",
                'is_closed' => false,
                'is_archived' => false,
                'last_message_at' => now(),
            ]
        );
    }

    /**
     * Добавление СИСТЕМНОГО сообщения в диалог заказа
     * Системные сообщения помечаются флагом is_system в meta
     */
    public function addSystemMessage(
        Order $order,
        string $message,
        string $type = 'system',
        array $extraMeta = []
    ): TenantMessage {
        $dialog = $this->getOrCreateDialog($order);

        $tenantMessage = TenantMessage::create([
            'tenant_id' => $order->tenant_id,
            'dialog_id' => $dialog->id,
            'message' => $message,
            'meta' => array_merge([
                'order_id' => $order->id,
                'type' => $type,
                'is_system' => true, // 🆕 Флаг системного сообщения
                'sender_type' => 'system', // 🆕 Отправитель — система
            ], $extraMeta),
            'is_read' => false,
        ]);

        $dialog->update(['last_message_at' => now()]);

        return $tenantMessage;
    }

    /**
     * Добавление сообщения о статусе заказа (СИСТЕМНОЕ)
     */
    public function addStatusMessage(Order $order, string $status, string $comment = null): TenantMessage
    {
        $statusLabels = [
            'new' => '🆕 Новый заказ создан',
            'processing' => '⚙️ Заказ в обработке',
            'ready' => '✅ Заказ готов',
            'delivery' => '🚚 Заказ передан в доставку',
            'delivered' => '🎉 Заказ доставлен',
            'cancelled' => '❌ Заказ отменён',
        ];

        $message = $statusLabels[$status] ?? "Статус: $status";
        if ($comment) {
            $message .= "\n💬 $comment";
        }

        return $this->addSystemMessage(
            $order,
            $message,
            'status_change',
            ['status' => $status]
        );
    }

    /**
     * Добавление сообщения о платеже (СИСТЕМНОЕ)
     */
    public function addPaymentMessage(Order $order, float $amount, $method): TenantMessage
    {
        $methodLabels = [
            0 => '💳 Онлайн',
            1 => '💳 Картой в заведении',
            2 => '🏦 Переводом',
            3 => '💵 Наличными',
            4 => '📱 СБП',
        ];

        $methodText = is_numeric($method) ? ($methodLabels[$method] ?? $method) : $method;

        return $this->addSystemMessage(
            $order,
            "💰 Оплата: " . number_format($amount, 0, '.', ' ') . " ₽ ({$methodText})",
            'payment',
            [
                'amount' => $amount,
                'method' => $method,
            ]
        );
    }

    /**
     * Добавление сообщения с деталями заказа (СИСТЕМНОЕ)
     * Это главное сообщение, которое видит клиент
     */
    public function addOrderDetailsMessage(
        Order $order,
        string $message,
        array $extraMeta = []
    ): TenantMessage {
        return $this->addSystemMessage(
            $order,
            $message,
            'order_details',
            $extraMeta
        );
    }

    /**
     * Отправка PDF-чека в диалог заказа (СИСТЕМНОЕ)
     */
    public function sendInvoiceToDialog(Order $order, string $pdfPath, array $extraMeta = []): ?TenantMessage
    {
        try {
            $dialog = $this->getOrCreateDialog($order);

            if (!Storage::disk('public')->exists($pdfPath)) {
                Log::warning("[OrderDialog] PDF не найден: {$pdfPath}");
                return null;
            }

            $fileSize = Storage::disk('public')->size($pdfPath);
            $fileName = basename($pdfPath);
            $fileUrl = Storage::disk('public')->url($pdfPath);

            $message = "🧾 <strong>Чек на оплату заказа #{$order->id}</strong>\n\n"
                . "💰 Сумма: <strong>" . number_format($order->summary_price ?? 0, 0, '.', ' ') . " ₽</strong>\n"
                . "📦 Позиций: <strong>" . ($order->product_count ?? 0) . "</strong>\n"
                . "📄 Файл: {$fileName}";

            $tenantMessage = TenantMessage::create([
                'tenant_id' => $order->tenant_id,
                'dialog_id' => $dialog->id,
                'message' => $message,
                'meta' => array_merge([
                    'order_id' => $order->id,
                    'type' => 'invoice',
                    'is_system' => true,
                    'sender_type' => 'system',
                    'attachment' => [
                        'type' => 'pdf',
                        'path' => $pdfPath,
                        'url' => $fileUrl,
                        'name' => $fileName,
                        'size' => $fileSize,
                        'mime' => 'application/pdf',
                    ],
                ], $extraMeta),
                'is_read' => false,
            ]);

            $dialog->update(['last_message_at' => now()]);

            return $tenantMessage;
        } catch (\Throwable $e) {
            Log::error('[OrderDialog] Ошибка отправки чека: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Добавление произвольного сообщения в диалог заказа
     */
    public function addOrderMessage(
        Order $order,
        string $message,
        array $extraMeta = []
    ): TenantMessage {
        $dialog = $this->getOrCreateDialog($order);

        $isSystem = $extraMeta['is_system'] ?? false;

        $tenantMessage = TenantMessage::create([
            'tenant_id' => $order->tenant_id,
            'dialog_id' => $dialog->id,
            'message' => $message,
            'meta' => array_merge([
                'order_id' => $order->id,
                'is_system' => $isSystem,
                'sender_type' => $isSystem ? 'system' : 'user',
            ], $extraMeta),
            'is_read' => false,
        ]);

        $dialog->update(['last_message_at' => now()]);

        return $tenantMessage;
    }

    /**
     * Закрытие диалога заказа
     */
    public function closeDialog(Order $order): void
    {
        $dialog = $this->getOrCreateDialog($order);
        $dialog->update(['is_closed' => true]);

        $this->addSystemMessage($order, '🔒 Диалог закрыт', 'dialog_closed');
    }

    /**
     * Получить вложения диалога
     */
    public function getDialogAttachments(int $dialogId): array
    {
        $messages = TenantMessage::where('dialog_id', $dialogId)
            ->whereNotNull('meta')
            ->get();

        $attachments = [];
        foreach ($messages as $message) {
            $meta = $message->meta;
            if (isset($meta['attachment'])) {
                $attachments[] = [
                    'id' => $message->id . '_att',
                    'type' => $meta['attachment']['type'] ?? 'file',
                    'name' => $meta['attachment']['name'] ?? 'file',
                    'path' => $meta['attachment']['path'] ?? null,
                    'url' => $meta['attachment']['url'] ?? null,
                    'size' => $meta['attachment']['size'] ?? 0,
                    'created_at' => $message->created_at,
                ];
            }
        }

        return $attachments;
    }
}
