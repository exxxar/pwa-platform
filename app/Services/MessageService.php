<?php

namespace App\Services;

use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use Exxxar\Kanban\Enums\KanbanTaskTypeEnum;
use Exxxar\Kanban\Facades\Kanban;
use Illuminate\Support\Facades\Auth;

class MessageService
{
    public static function call(): self
    {
        return app(self::class);
    }

    /**
     * Универсальный прокси
     */
    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    public function sendMessage($params = null): static
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $message = $params["message"] ?? '-';

        $dialogId = $params["dialog_id"] ?? null;

        $dialogTitle = $params["title"] ?? 'Информация от администратора';
        $dialogType = $params["type"] ?? 'system';
        $attachments = $params["attachments"] ?? [];
        $keyboard = $params["keyboard"] ?? [];
        // $needSendToUser = $params["send_to_user"] ?? true;
        $taskType = $params["task_type"] ?? 3;


        $payload = [
            'from' => [
                'id' => $tenantUser->id ?? '-',
                'name' => $tenantUser->name ?? '-',
                'email' => $tenantUser->email ?? '-',
                'phone' => $tenantUser->phone ?? '-',
                'sex' => $tenantUser->sex ?? 'male',
            ],
            'thread_id' => $params["thread_id"] ?? 0,
            'attachments' => json_encode($attachments),
            'keyboard' => json_encode($keyboard)
        ];

        if (is_null($dialogId)) {
            $dialog = TenantDialog::query()->create([
                'tenant_id' => $tenant->id,
                'tenant_user_id' => $tenantUser->id,
                'title' => $dialogTitle,
                'type' => $dialogType,
            ]);

            $task = Kanban::tasks()->create([
                'thread' => $params["thread_id"],
                'type' => KanbanTaskTypeEnum::from($taskType)->value,
                'payload' => $payload
            ]);

            $dialog->external_task_id = $task->id;
            $dialog->save();
        } else
            $dialog = TenantDialog::query()
                ->findOrFail($dialogId);

        TenantMessage::create([
            'tenant_id' => $tenant->id,
            'dialog_id' => $dialog->id,
            'meta' => json_encode($payload),
            'message' => $message,
        ]);

        Kanban::tasks()->sendMessage($dialog->external_task_id, $payload);

        return $this;
    }

}
