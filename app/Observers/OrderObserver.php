<?php

namespace App\Observers;

use App\Models\Tenant\Order;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage; // Убедитесь, что эта модель существует

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Если диалог уже привязан (например, создан вручную до этого), не делаем ничего
        if ($order->dialog_id) {
            return;
        }

        // 1. Создаем диалог
        $dialog = TenantDialog::create([
            'tenant_id' => $order->tenant_id,
            'tenant_user_id' => $order->tenant_user_id,
            'type' => 'order_support', // Тип диалога: поддержка по заказу
            'title' => 'Поддержка по заказу #' . $order->id,
            'is_closed' => false,
        ]);

        // 2. Привязываем ID диалога к заказу
        $order->dialog_id = $dialog->id;

        // 3. Сохраняем заказ ТИХО (без повторного вызова событий), чтобы избежать бесконечного цикла
        $order->saveQuietly();

        // 4. (Опционально, но рекомендуется) Создаем приветственное системное сообщение
        TenantMessage::create([
            'dialog_id' => $dialog->id,
            'tenant_id' => $order->tenant_id,
            'tenant_user_id' => null, // null означает, что сообщение от системы/бота
            'message' => 'Здравствуйте! Это чат поддержки по вашему заказу #' . $order->id . '. Если у вас есть вопросы, напишите нам здесь.',
            'is_read' => true, // Системные сообщения считаем "прочитанными", чтобы не накручивать счетчик непрочитанных
        ]);
    }

    /**
     * Handle the Order "updated" event.
     * (Например, автоматически закрывать диалог при отмене заказа)
     */
    public function updated(Order $order): void
    {
        // Если заказ отменен (статус 3, согласно вашему предыдущему коду), можно закрыть диалог
        if ($order->isDirty('status') && $order->status === 3) {
            if ($order->dialog) {
                $order->dialog->update(['is_closed' => true]);
            }
        }
    }
}
