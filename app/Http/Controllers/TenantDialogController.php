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
    public function messages(Request $request, $dialogId )
    {

        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        $dialog = TenantDialog::query()
            ->where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
          //  ->where('tenant_user_id', $user->id)
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

    public function sendMessage(Request $request, $dialogId)
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        // 1. Находим диалог (с подгрузкой клиента)
        $dialog = TenantDialog::where('id', $dialogId)
            ->where('tenant_id', $tenant->id)
            ->with('user')
            ->first();

        if (!$dialog) {
            return response()->json(['message' => 'Диалог не найден'], 404);
        }

        $validated = $request->validate([
            'text' => 'nullable|string|max:2000',
            'message' => 'nullable|string|max:2000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,webm,mp3,mp4,pdf,doc,docx,xls,xlsx,zip,rar|max:10240',
        ]);

        $textContent = $request->input('text') ?: $request->input('message', '');

        // 2. Определяем отправителя
        $isClientSender = $user->id === $dialog->tenant_user_id;
        $senderType = $isClientSender ? 'user' : 'admin';
        $senderId = $user->id;
        $senderName = $user->name ?? ($isClientSender ? 'Клиент' : 'Администратор');

        // 3. Подготовка мета-данных
        $metaData = [
            'sender_name' => $senderName,
            'type' => 'text',
        ];

        // 4. Обработка вложений
        $attachmentInfo = null;
        if ($request->hasFile('attachments')) {
            $file = $request->file('attachments')[0];
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('messages/attachments', $filename, 'public');

            // 🆕 Формируем ПУБЛИЧНУЮ ссылку на файл в storage
            $publicUrl = \Illuminate\Support\Facades\Storage::url($path);
            $absoluteUrl = asset('storage/' . str_replace('messages/attachments/', '', $path));

            // Нормализуем URL: Storage::url() уже добавляет /storage/
            // Если в .env APP_URL правильный, используем его
            $finalUrl = url(str_replace('public/', '', \Illuminate\Support\Facades\Storage::url($path)));

            $fileType = $this->getFileType($file->getClientOriginalExtension());

            $metaData['attachment'] = [
                'path' => $path,                         // Относительный путь в storage
                'url' => $finalUrl,                      // 🆕 Полная публичная ссылка
                'name' => $file->getClientOriginalName(),
                'original_name' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];

            $metaData['type'] = $fileType;

            $attachmentInfo = [
                'name' => $file->getClientOriginalName(),
                'url' => $finalUrl,                      // 🆕 Для Telegram
                'size' => $file->getSize(),
                'type' => $fileType,
                'extension' => $file->getClientOriginalExtension(),
            ];
        }

        // 5. Создание сообщения
        $message = TenantMessage::create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $dialog->tenant_user_id,
            'dialog_id' => $dialog->id,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'message' => $textContent,
            'meta' => $metaData,
            'is_read' => false,
        ]);

        // 6. Обновляем диалог
        $dialog->update([
            'last_message_at' => now(),
        ]);

        // 7. Отправляем уведомление в Telegram
        try {
            $this->sendChatMessageToTelegram(
                $tenant,
                $dialog,
                $message,
                $senderName,
                $textContent,
                $attachmentInfo,
                $isClientSender
            );
        } catch (\Throwable $e) {
            Log::warning('[Telegram Chat Notification] Ошибка отправки: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data' => $message
        ], 201);
    }


    private function sendChatMessageToTelegram(
        $tenant,
        $dialog,
        $message,
        $senderName,
        $textContent,
        $attachmentInfo,
        $isClientSender
    ): void {
        // 1. Настройки Telegram
        $tgSettings = $tenant->settings['telegram'] ?? [];
        $token = $tgSettings['token'] ?? null;
        $chatId = $tgSettings['support_chat_id'] ?? $tgSettings['channel_id'] ?? null;

        if (!$token || !$chatId) {
            return;
        }

        // 2. 🆕 Получаем информацию о клиенте через метод модели
        $client = $dialog->user;
        $clientInfo = $client ? $client->getTelegramInfo() : [
            'name' => 'Неизвестный клиент',
            'phone' => 'Не указан',
            'id' => $dialog->tenant_user_id,
        ];

        $clientName = e($clientInfo['name']);
        $clientPhone = e($clientInfo['phone']);
        $isVip = !empty($clientInfo['is_vip']);
        $isBlocked = !empty($clientInfo['is_blocked']);

        // 3. Формируем сообщение
        $icon = $isClientSender ? '👤' : '🛡️';
        $role = $isClientSender ? 'Клиент' : 'Администратор';

        $msg = "{$icon} <b>Новое сообщение в чате</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "📅 " . now()->format('d.m.Y H:i') . "\n\n";

        $msg .= "👤 <b>Отправитель:</b> " . e($senderName) . "\n";
        $msg .= "🎭 <b>Роль:</b> {$role}\n";
        $msg .= "💬 <b>Диалог #:</b> {$dialog->id}\n\n";

        // 4. 🆕 Блок с информацией о клиенте (из модели)
        $msg .= "📱 <b>Клиент:</b> {$clientName}";
        if ($isVip) $msg .= " ⭐️ <b>VIP</b>";
        if ($isBlocked) $msg .= " 🚫 <b>Заблокирован</b>";
        $msg .= "\n";
        $msg .= "📞 <b>Телефон:</b> <code>{$clientPhone}</code>\n";

        if (!empty($clientInfo['city'])) {
            $msg .= "🏙 <b>Город:</b> " . e($clientInfo['city']) . "\n";
        }

        if (!empty($clientInfo['cashback_balance']) && $clientInfo['cashback_balance'] > 0) {
            $msg .= "💰 <b>Кэшбек:</b> " . number_format($clientInfo['cashback_balance'], 0, '.', ' ') . " ₽\n";
        }

        $msg .= "\n";

        // 5. Текст сообщения
        if (!empty($textContent)) {
            $msg .= "💬 <b>Сообщение:</b>\n";
            $msg .= "<i>" . e($textContent) . "</i>\n\n";
        }

        // 6. 🆕 Вложение — с кликабельной ссылкой на файл
        if ($attachmentInfo) {
            $typeEmoji = $this->getAttachmentEmoji($attachmentInfo['type']);
            $sizeKb = round($attachmentInfo['size'] / 1024, 1);

            $msg .= "📎 <b>Вложение:</b>\n";

            // Если есть URL — делаем кликабельную ссылку
            if (!empty($attachmentInfo['url'])) {
                $msg .= "  {$typeEmoji} <a href=\"{$attachmentInfo['url']}\">" . e($attachmentInfo['name']) . "</a>\n";
            } else {
                $msg .= "  {$typeEmoji} " . e($attachmentInfo['name']) . "\n";
            }

            $msg .= "  • Тип: {$attachmentInfo['type']}\n";
            $msg .= "  • Размер: {$sizeKb} KB\n\n";
        }

        // 7. 🆕 Ссылки: на диалог и профиль клиента
        $baseUrl = request()->getSchemeAndHttpHost();
        if ($baseUrl) {
            // Новый формат ссылки на чат: /pwa#/chat/:dialogId
            $chatUrl = "{$baseUrl}/pwa#/chat/{$dialog->id}";
            $msg .= "🔗 <a href=\"{$chatUrl}\">Открыть чат</a>\n";

            if (!empty($clientInfo['profile_url'])) {
                $msg .= "👤 <a href=\"{$clientInfo['profile_url']}\">Профиль клиента</a>\n";
            }
        }

        // 8. Payload
        $payload = [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if (!empty($tgSettings['thread_id'])) {
            $payload['message_thread_id'] = (int) $tgSettings['thread_id'];
        }

        // 9. Отправка
        $this->sendTelegramCurlRequest($token, $payload);
    }

    /**
     * Эмодзи для типа вложения
     */
    private function getAttachmentEmoji(string $type): string
    {
        return match ($type) {
            'image' => '🖼',
            'video' => '🎬',
            'audio' => '🎵',
            'pdf' => '📄',
            'doc' => '📝',
            'xls' => '📊',
            'zip' => '🗜',
            default => '📎',
        };
    }

    /**
     * Вспомогательный метод для отправки запроса в Telegram API через cURL
     */
    private function sendTelegramCurlRequest(string $token, array $payload): bool
    {
        $ch = curl_init();
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Уменьшаем таймаут для чатов

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            Log::warning('[Telegram Notification] Ошибка отправки. HTTP: ' . $httpCode . ' | Error: ' . $curlError . ' | Response: ' . $response);
            return false;
        }

        return true;
    }


    /**
     * 🆕 Вспомогательный метод для определения типа файла (соответствует иконкам на фронте)
     */
    private function getFileType(string $extension): string
    {
        $map = [
            'jpeg' => 'image', 'jpg' => 'image', 'png' => 'image', 'gif' => 'image', 'webp' => 'image',
            'pdf' => 'pdf',
            'doc' => 'doc', 'docx' => 'doc',
            'xls' => 'xls', 'xlsx' => 'xls',
            'zip' => 'zip', 'rar' => 'zip',
            'mp4' => 'video', 'webm' => 'video',
            'mp3' => 'audio',
        ];
        return $map[strtolower($extension)] ?? 'file';
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

        // 🎯 Правильное условие: непрочитанные, где отправитель — НЕ текущий пользователь
        $baseQuery = TenantMessage::query()
            ->join('tenant_dialogs', 'tenant_messages.dialog_id', '=', 'tenant_dialogs.id')
            ->where('tenant_dialogs.tenant_id', $tenant->id)
            ->where('tenant_dialogs.tenant_user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('tenant_dialogs.is_archived')
                    ->orWhere('tenant_dialogs.is_archived', false);
            })
            ->where('tenant_messages.is_read', false)
            // 🆕 Фильтруем по реальному отправителю, а не по tenant_user_id
            ->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    // Либо отправитель — админ/система
                    $sub->whereIn('tenant_messages.sender_type', ['admin', 'system']);
                })
                    ->orWhere(function ($sub) use ($user) {
                        // Либо отправитель — другой пользователь (для групповых чатов)
                        $sub->where('tenant_messages.sender_type', 'user')
                            ->where('tenant_messages.sender_id', '!=', $user->id);
                    });
            });

        $totalUnread = (clone $baseQuery)->count();

        $byDialog = (clone $baseQuery)
            ->select('tenant_messages.dialog_id', DB::raw('COUNT(*) as unread_count'))
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
