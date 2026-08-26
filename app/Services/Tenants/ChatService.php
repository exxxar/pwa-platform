<?php

namespace App\Services\Tenants;

use App\Models\Tenant\TenantDialog;

class ChatService
{
    /**
     * 🆕 Краткая информация о чатах (для начальной загрузки)
     */
    public function getDialogsSummary(int $userId): array
    {
        $dialogs = TenantDialog::where('tenant_user_id', $userId)
            ->where('is_closed', false)
            ->select([
                'id',
                'tenant_user_id', // Важно оставить для корректной работы связей
                'title',
                'type',
                'last_message_at',
            ])
            ->withCount([
                'messages as messages_count',
                'unreadMessages as unread_count'
            ])
            ->orderByDesc('last_message_at')
            ->limit(10)
            ->get();

        return [
            'items' => $dialogs,
            'total_unread' => $dialogs->sum('unread_count'),
            'total_count' => $dialogs->count(),
        ];
    }
}
