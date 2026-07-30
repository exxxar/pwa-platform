<?php

namespace App\Http\Controllers;

use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Models\Tenant\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantDialogController extends Controller
{
    /**
     * Список диалогов
     */
    public function index()
    {

        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialogs = TenantDialog::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->with(['lastMessage', 'user'])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return response()->json($dialogs);
    }


    public function show($dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        // Ищем диалог с проверкой принадлежности
        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->with(['user'])
            ->first();

        if (!$dialog) {
            return response()->json([
                'message' => 'Диалог не найден',
            ], 404);
        }

        return response()->json($dialog);
    }

    /**
     * Сообщения диалога
     */
    public function messages(Request $request, $dialogId, $tenant)
    {

        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::query()
            ->where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();


        Log::info(print_r([
            $dialogId,
                $user->toArray() ?? 'нет пользователя',
                $tenant->toArray() ?? 'нет тенанта'
        ], true));

        if (!$dialog) {
            return response()->json([
                'message' => 'Диалог не найден',
            ], 404);
        }

        $page = (int)$request->get('page', 1);
        $perPage = (int)$request->get('size', 20);

        $messages = TenantMessage::where('dialog_id', $dialog->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        // Отмечаем сообщения как прочитанные
        TenantMessage::where('dialog_id', $dialog->id)
            ->where('is_read', false)
            ->where('tenant_user_id', '!=', $user->id) // не свои сообщения
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $tenant, $dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();

        if (!$dialog) {
            return response()->json(['message' => 'Диалог не найден'], 404);
        }

        // 🛠️ ИСПРАВЛЕНИЕ 1: Убрали dialog_id из валидации (он уже есть в URL),
        // или изменили exists на tenant_dialogs, если оставляете.
        $validated = $request->validate([
            'text' => 'nullable|string|max:2000',
            'message' => 'nullable|string|max:2000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,webm,mp3,mp4,pdf,doc,docx|max:10240',
        ]);

        // 🛠️ ИСПРАВЛЕНИЕ 2: Берем текст из любого поля (text или message)
        $textContent = $request->input('text') ?: $request->input('message', '');
        $attachmentUrls = [];

        // 🛠️ ИСПРАВЛЕНИЕ 3: Реальное сохранение файлов
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                // Генерируем уникальное имя файла
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Сохраняем в public disk (storage/app/public/messages/attachments)
                $path = $file->storeAs('public/messages/attachments', $filename);

                // Генерируем публичную ссылку для фронтенда
                $attachmentUrls[] = asset('storage/messages/attachments/' . $filename);
            }
        }

        // 🛠️ ИСПРАВЛЕНИЕ 4: Сохраняем ссылки в meta (или в отдельную колонку attachments, если она есть)
        $message = TenantMessage::create([
            'tenant_id' => $tenant->id,
            'dialog_id' => $dialog->id,
            'sender_id' => $user->id, // Рекомендуется добавить, если есть такое поле
            'message' => $textContent,
            'meta' => [
                'attachments' => $attachmentUrls,
                'has_voice' => !empty($attachmentUrls) && str_contains($attachmentUrls[0], 'webm'), // Флаг для фронтенда
            ],
            'is_read' => false,
        ]);

        // Обновляем время последнего сообщения
        $dialog->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => $message
        ], 201);
    }

    /**
     * Закрытие диалога
     */
    public function close($dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();

        if (!$dialog) {
            return response()->json(['message' => 'Диалог не найден'], 404);
        }

        $dialog->update(['is_closed' => true]);

        return response()->json(['success' => true]);
    }


    public function markAsRead($dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();

        if (!$dialog) {
            return response()->json(['message' => 'Диалог не найден'], 404);
        }

        // Считаем сколько было непрочитанных
        $unreadCount = TenantMessage::where('dialog_id', $dialog->id)
            ->where('is_read', false)
            ->where('tenant_user_id', '!=', $user->id)
            ->count();

        // Отмечаем как прочитанные
        TenantMessage::where('dialog_id', $dialog->id)
            ->where('is_read', false)
            ->where('tenant_user_id', '!=', $user->id)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'dialog_id' => $dialog->id,
            'marked_count' => $unreadCount,
        ]);
    }

    public function archive($dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();

        if (!$dialog) {
            return response()->json(['message' => 'Диалог не найден'], 404);
        }

        $dialog->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Восстановить диалог
     */
    public function restore($dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();

        if (!$dialog) {
            return response()->json(['message' => 'Диалог не найден'], 404);
        }

        $dialog->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Окончательно удалить диалог
     */
    public function destroy($dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();

        if (!$dialog) {
            return response()->json(['message' => 'Диалог не найден'], 404);
        }

        // Удаляем все сообщения
        TenantMessage::where('dialog_id', $dialog->id)->delete();

        // Удаляем сам диалог
        $dialog->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Очистить весь архив
     */
    public function emptyArchive()
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $archivedDialogs = TenantDialog::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->where('is_archived', true)
            ->get();

        $count = $archivedDialogs->count();

        foreach ($archivedDialogs as $dialog) {
            TenantMessage::where('dialog_id', $dialog->id)->delete();
            $dialog->delete();
        }

        return response()->json([
            'success' => true,
            'deleted_count' => $count,
        ]);
    }

    /**
     * Массовое архивирование
     */
    public function archiveMultiple(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        TenantDialog::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->whereIn('id', $request->ids)
            ->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Получить вложения диалога
     */
    public function attachments($dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();

        if (!$dialog) {
            return response()->json(['message' => 'Диалог не найден'], 404);
        }

        $messages = TenantMessage::where('dialog_id', $dialog->id)
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

        return response()->json($attachments);
    }

    /**
     * 🆕 Получение общего количества непрочитанных сообщений
     * GET /dialogs/unread-count
     *
     * Возвращает:
     * - total: общее число непрочитанных
     * - by_dialog: количество по каждому диалогу
     */
    public function unreadCount()
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        // Общее количество непрочитанных (исключая свои сообщения)
        $totalUnread = TenantMessage::query()
            ->join('tenant_dialogs', 'tenant_messages.dialog_id', '=', 'tenant_dialogs.id')
            ->where('tenant_dialogs.tenant_id', $tenant->id)
            ->where('tenant_dialogs.tenant_user_id', $user->id)
            ->where('tenant_dialogs.is_archived', false)
            ->where('tenant_messages.is_read', false)
            ->where('tenant_messages.tenant_user_id', '!=', $user->id) // не свои
            ->count();

        // Количество непрочитанных по каждому диалогу
        $byDialog = TenantMessage::query()
            ->select('tenant_messages.dialog_id', DB::raw('COUNT(*) as unread_count'))
            ->join('tenant_dialogs', 'tenant_messages.dialog_id', '=', 'tenant_dialogs.id')
            ->where('tenant_dialogs.tenant_id', $tenant->id)
            ->where('tenant_dialogs.tenant_user_id', $user->id)
            ->where('tenant_dialogs.is_archived', false)
            ->where('tenant_messages.is_read', false)
            ->where('tenant_messages.tenant_user_id', '!=', $user->id)
            ->groupBy('tenant_messages.dialog_id')
            ->pluck('unread_count', 'dialog_id')
            ->map(fn($count) => (int)$count);

        return response()->json([
            'total' => $totalUnread,
            'by_dialog' => $byDialog,
        ]);
    }

    /**
     * 🆕 Получение непрочитанных сообщений конкретного диалога
     * GET /dialogs/{dialogId}/unread-count
     */
    public function dialogUnreadCount($dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();

        if (!$dialog) {
            return response()->json(['message' => 'Диалог не найден'], 404);
        }

        $unreadCount = TenantMessage::where('dialog_id', $dialog->id)
            ->where('is_read', false)
            ->where('tenant_user_id', '!=', $user->id)
            ->count();

        return response()->json([
            'dialog_id' => $dialog->id,
            'unread_count' => $unreadCount,
        ]);
    }
}
