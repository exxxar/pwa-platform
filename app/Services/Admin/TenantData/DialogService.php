<?php

namespace App\Services\Admin\TenantData;

use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;

class DialogService
{
    /**
     * Получить список диалогов
     */
    public function getDialogs(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = TenantDialog::query()->with(['user', 'lastMessage']);

        // Фильтр по tenant_id
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Фильтр по закрытости
        if (isset($filters['is_closed'])) {
            $query->where('is_closed', $filters['is_closed']);
        }

        // Фильтр по наличию непрочитанных
        if (isset($filters['has_unread']) && $filters['has_unread']) {
            $query->whereHas('unreadMessages');
        }

        // Сортировка по последнему сообщению
        $query->orderBy('last_message_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Получить диалог с сообщениями
     */
    public function getDialogWithMessages(TenantDialog $dialog, int $perPage = 50): array
    {
        $messages = $dialog->messages()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'dialog' => $dialog->load('user'),
            'messages' => $messages,
        ];
    }

    /**
     * Ответить в диалог
     */
    public function reply(TenantDialog $dialog, array $data): TenantMessage
    {
        $message = TenantMessage::create([
            'tenant_id' => $dialog->tenant_id,
            'tenant_user_id' => $dialog->tenant_user_id,
            'dialog_id' => $dialog->id,
            'sender_type' => 'admin',
            'sender_id' => auth()->id(),
            'message' => $data['message'],
            'meta' => $data['meta'] ?? [],
            'is_read' => false,
        ]);

        // Обновляем last_message_at в диалоге
        $dialog->update(['last_message_at' => now()]);

        return $message;
    }

    /**
     * Закрыть диалог
     */
    public function closeDialog(TenantDialog $dialog): TenantDialog
    {
        $dialog->update(['is_closed' => true]);
        return $dialog;
    }

    /**
     * Отметить все сообщения как прочитанные
     */
    public function markAsRead(TenantDialog $dialog): int
    {
        return $dialog->markAllAsRead();
    }
}
