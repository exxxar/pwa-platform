<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantUser;
use App\Services\Tenants\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    /**
     * Максимальное количество фото
     */
    private const MAX_PHOTOS = 3;

    /**
     * Максимальный размер одного файла (10 МБ)
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Максимальная длина сообщения
     */
    private const MAX_MESSAGE_LENGTH = 1000;

    /**
     * 📬 Отправка обратной связи
     */
    public function submit(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Необходима авторизация',
            ], 401);
        }

        // ==========================================
        // ВАЛИДАЦИЯ
        // ==========================================
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'message' => 'required|string|max:' . self::MAX_MESSAGE_LENGTH,
            'photos' => 'nullable|array|max:' . self::MAX_PHOTOS,
            'photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:' . (self::MAX_FILE_SIZE / 1024),
        ], [
            'name.required' => 'Укажите ваше имя',
            'phone.required' => 'Укажите номер телефона',
            'message.required' => 'Введите текст сообщения',
            'message.max' => 'Сообщение слишком длинное (макс. ' . self::MAX_MESSAGE_LENGTH . ' символов)',
            'photos.max' => 'Максимум ' . self::MAX_PHOTOS . ' фотографии',
            'photos.*.image' => 'Файл должен быть изображением',
            'photos.*.max' => 'Размер файла не должен превышать 10 МБ',
        ]);

        $tenant = app('tenant');

        try {
            // ==========================================
            // ОЧИСТКА ТЕЛЕФОНА
            // ==========================================
            $cleanPhone = preg_replace('/[^\d+]/', '', $validated['phone']);

            // ==========================================
            // СОХРАНЕНИЕ ФОТОГРАФИЙ
            // ==========================================
            $savedPhotos = [];
            $photoUrls = [];

            if ($request->hasFile('photos')) {
                $photos = $request->file('photos');

                foreach ($photos as $index => $photo) {
                    $extension = $photo->getClientOriginalExtension();
                    $fileName = sprintf(
                        'feedback_%s_%d.%s',
                        Str::uuid()->toString(),
                        $index + 1,
                        $extension
                    );

                    $path = "feedback/{$tenant->id}/{$user->id}/{$fileName}";

                    Storage::disk('public')->putFileAs(
                        dirname($path),
                        $photo,
                        basename($path)
                    );

                    $savedPhotos[] = $path;
                    $photoUrls[] = Storage::disk('public')->url($path);
                }
            }

            // ==========================================
            // СОЗДАНИЕ / ПОИСК ДИАЛОГА
            // ==========================================
            $dialog = TenantDialog::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'tenant_user_id' => $user->id,
                    'type' => 'support',
                ],
                [
                    'title' => '💬 Обратная связь',
                    'is_closed' => false,
                ]
            );

            // ==========================================
            // ФОРМИРОВАНИЕ ТЕКСТА СООБЩЕНИЯ
            // ==========================================
            $messageText = $this->buildFeedbackMessage($validated, $cleanPhone, $user, $photoUrls);

            // ==========================================
            // ОТПРАВКА ЧЕРЕЗ MessageService
            // ==========================================
            $kanbanConfig = $tenant->settings['kanban'] ?? [];
            $kanbanEnabled = !empty($kanbanConfig['enabled'])
                && !empty($kanbanConfig['board_uuid'])
                && !empty($kanbanConfig['token']);

            $metaData = [
                'is_system' => false,
                'type' => 'feedback',
                'feedback_name' => $validated['name'],
                'feedback_phone' => $cleanPhone,
                'feedback_message' => $validated['message'],
                'photos_count' => count($savedPhotos),
                'photos' => $savedPhotos,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_phone' => $user->phone,

                // Для CRM
                'kanban_board_uuid' => $kanbanConfig['board_uuid'] ?? null,
                'kanban_thread' => $kanbanConfig['feedback_thread'] ?? $kanbanConfig['order_thread'] ?? 0,
                'customer_name' => $validated['name'],
                'customer_phone' => $cleanPhone,
                'kanban_custom_data' => [
                    'feedback_message' => $validated['message'],
                    'photos_count' => count($savedPhotos),
                    'source' => 'feedback',
                    'user_id' => $user->id,
                ],
                'kanban_payload' => [
                    'source' => 'feedback',
                    'type' => 'new_feedback',
                    'user_id' => $user->id,
                ],
                'kanban_client_extra' => [
                    'feedback_message' => $validated['message'],
                    'feedback_source' => 'Обратная связь',
                ],
            ];

            // Отправка клиенту (в диалог)
            MessageService::call()->sendMessage([
                'message' => $messageText,
                'dialog_id' => $dialog->id,
                'title' => "💬 Обратная связь от {$validated['name']}",
                'meta' => $metaData,
                'recipients' => [
                    'client' => true,
                    'telegram' => true, // В канал поддержки
                    'crm' => $kanbanEnabled,
                ],
            ]);

            // ==========================================
            // ОТПРАВКА ФОТО ОТДЕЛЬНО (если есть)
            // ==========================================
            foreach ($savedPhotos as $photoPath) {
                MessageService::call()->sendMessage([
                    'message' => "📎 Фото к обратной связи #{$user->id}",
                    'file_path' => $photoPath,
                    'dialog_id' => $dialog->id,
                    'meta' => array_merge($metaData, ['is_system' => true]),
                    'recipients' => [
                        'client' => true,
                        'telegram' => true,
                    ],
                ]);
            }

            // ==========================================
            // АВТООТВЕТ КЛИЕНТУ
            // ==========================================
            $autoReply = $this->buildAutoReply($validated['name']);

            MessageService::call()->sendMessage([
                'message' => $autoReply,
                'dialog_id' => $dialog->id,
                'meta' => [
                    'is_system' => true,
                    'type' => 'feedback_auto_reply',
                ],
                'recipients' => ['client' => true],
            ]);

            // ==========================================
            // ЛОГИРОВАНИЕ
            // ==========================================
            Log::info('[Feedback] Сообщение отправлено', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'phone' => $cleanPhone,
                'photos_count' => count($savedPhotos),
                'dialog_id' => $dialog->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Спасибо! Ваше сообщение отправлено. Мы ответим в ближайшее время.',
                'dialog_id' => $dialog->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('[Feedback] Ошибка отправки', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отправке. Попробуйте позже.',
            ], 500);
        }
    }

    /**
     * 🎨 Формирование красивого сообщения для Telegram / CRM
     */
    private function buildFeedbackMessage(array $data, string $phone, TenantUser $user, array $photoUrls): string
    {
        $tenant = app('tenant');
        $baseUrl = request()->getSchemeAndHttpHost();
        $profileUrl = $baseUrl ? "{$baseUrl}/pwa#/admin/users/{$user->id}" : null;

        $message = "💬 <b>ОБРАТНАЯ СВЯЗЬ</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "👤 <b>Имя:</b> {$data['name']}\n";
        $message .= "📞 <b>Телефон:</b> <code>{$phone}</code>\n";

        if ($user->id) {
            $message .= "🆔 <b>ID клиента:</b> #{$user->id}\n";
        }

        $message .= "📅 <b>Время:</b> " . now()->format('d.m.Y H:i') . "\n";

        if ($profileUrl) {
            $message .= "🔗 <a href=\"{$profileUrl}\">Профиль клиента</a>\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📝 <b>Сообщение:</b>\n";
        $message .= "<i>" . e($data['message']) . "</i>\n";

        if (!empty($photoUrls)) {
            $message .= "\n📎 <b>Прикреплено фото:</b> " . count($photoUrls) . " шт.\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🏢 <b>Источник:</b> " . e($tenant->name ?? $tenant->slug) . "\n";

        return $message;
    }

    /**
     * 🤖 Автоответ клиенту
     */
    private function buildAutoReply(string $name): string
    {
        $firstName = explode(' ', $name)[0];

        return <<<HTML
        👋 <b>{$firstName}, спасибо за обращение!</b>

        Ваше сообщение получено и передано в службу поддержки.

        ⏰ <b>Обычное время ответа:</b> в течение 24 часов
        📱 <b>Срочные вопросы:</b> напишите нам в чат

        Мы обязательно вам ответим! 💙
        HTML;
    }
}
