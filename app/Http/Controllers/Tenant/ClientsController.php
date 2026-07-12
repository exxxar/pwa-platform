<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\DialogMessage;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    /**
     * 🆕 Получить данные пользователя
     */
    public function show(int $userId)
    {
        $tenant = app('tenant');

        $user = TenantUser::where('tenant_id', $tenant->id)
            ->findOrFail($userId);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'is_admin' => $user->is_admin ?? false,
                'is_vip' => $user->is_vip ?? false,
                'is_deliveryman' => $user->is_deliveryman ?? false,
                'cashback' => $user->cashback ?? 0,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * 🆕 Получить историю сообщений
     */
    public function messages(int $userId, Request $request)
    {
        $tenant = app('tenant');

        $messages = DialogMessage::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json($messages);
    }

    /**
     * 🆕 Отправить текстовое сообщение
     */
    public function sendMessage(int $userId, Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:4000',
        ]);

        $tenant = app('tenant');
        $admin = auth('tenant')->user();

        $message = DialogMessage::create([
            'tenant_id' => $tenant->id,
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'receiver_id' => $userId,
            'content' => $request->message,
            'is_read' => false,
        ]);

        return response()->json([
            'data' => $message,
        ], 201);
    }

    /**
     * 🆕 Отправить файл
     */
    public function sendFile(int $userId, Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
            'type' => 'nullable|in:image,video,audio,file',
        ]);

        $tenant = app('tenant');
        $admin = auth('tenant')->user();
        $file = $request->file('file');

        $path = $file->store("dialogs/{$userId}", 'public');

        $message = DialogMessage::create([
            'tenant_id' => $tenant->id,
            'sender_id' => $admin->id,
            'sender_type' => 'admin',
            'receiver_id' => $userId,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $request->type ?? 'file',
            'file_size' => $file->getSize(),
            'is_read' => false,
        ]);

        $message->file_url = \Storage::disk('public')->url($path);

        return response()->json([
            'data' => $message,
        ], 201);
    }

    /**
     * 🆕 Пометить сообщения как прочитанные
     */
    public function markAsRead(int $userId)
    {
        $tenant = app('tenant');

        DialogMessage::where('tenant_id', $tenant->id)
            ->where('receiver_id', auth('tenant')->id())
            ->where('sender_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * 🆕 Удалить сообщение
     */
    public function deleteMessage(int $messageId)
    {
        $tenant = app('tenant');

        $message = DialogMessage::where('tenant_id', $tenant->id)
            ->where('sender_type', 'admin')
            ->findOrFail($messageId);

        $message->delete();

        return response()->json(['success' => true]);
    }
}
