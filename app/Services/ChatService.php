<?php

namespace App\Services;

use App\Models\Tenant\TenantDialog;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * 🆕 Краткая информация о чатах (для начальной загрузки)
     */
    public function getDialogsSummary(int $userId): array
    {
        $dialogs = TenantDialog::where('tenant_user_id', $userId)
            ->where('is_archived', false)
            ->select([
                'id',
                'title',
                'type',
                'unread_count',
                'last_message_at',
            ])
            ->withCount(['messages as messages_count'])
            ->orderByDesc('last_message_at')
            ->limit(10) // Только первые 10 для скорости
            ->get();

        return [
            'items' => $dialogs,
            'total_unread' => $dialogs->sum('unread_count'),
            'total_count' => $dialogs->count(),
        ];
    }
}
