<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Broadcast;
use App\Models\Tenant\BroadcastMedia;
use App\Models\Tenant\TenantUser;
use App\Services\Tenants\BroadcastService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function __construct(
        private BroadcastService $broadcastService
    ) {}

    /**
     * Список рассылок
     */
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $query = Broadcast::where('tenant_id', $tenant->id)
            ->with(['author', 'media'])
            ->withCount('recipients');

        // Фильтр по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Сортировка
        $query->orderByDesc('created_at');

        $broadcasts = $query->paginate(20);

        return response()->json(['data' => $broadcasts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'nullable|string|max:4000',
            'recipient_type' => 'required|in:all,active,vip,segment,custom',
            'recipient_filters' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',

            // 🆕 Упрощённая валидация кнопок
            'buttons' => 'nullable|string', // Будем декодировать JSON вручную
        ]);

        // 🆕 Декодируем buttons из JSON строки
        $buttons = [];
        if (!empty($validated['buttons'])) {
            $buttons = json_decode($validated['buttons'], true) ?? [];

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректный формат кнопок: ' . json_last_error_msg(),
                ], 422);
            }
        }

        try {
            $mediaFiles = [
                'image' => $request->file('images', []),
                'video' => $request->file('videos', []),
                'audio' => $request->file('audios', []),
            ];

            $broadcast = $this->broadcastService->create($validated, $mediaFiles);

            // 🆕 Создаём кнопки отдельно
            if (!empty($buttons)) {
                $this->createButtonsFromData($broadcast, $buttons);
            }

            return response()->json([
                'success' => true,
                'data' => $broadcast->load(['media', 'buttons']),
                'message' => 'Рассылка создана',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('[Broadcast] Ошибка создания: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания рассылки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🆕 Создание кнопок из данных
     */
    private function createButtonsFromData($broadcast, array $buttons): void
    {
        foreach ($buttons as $rowIndex => $row) {
            if (!is_array($row)) continue;

            foreach ($row as $position => $button) {
                if (!is_array($button) || empty($button['text'])) continue;

                \App\Models\Tenant\BroadcastButton::create([
                    'broadcast_id' => $broadcast->id,
                    'text' => $button['text'],
                    'url' => $button['url'] ?? null,
                    'callback_data' => $button['callback_data'] ?? null,
                    'type' => $button['type'] ?? 'callback',
                    'row_index' => $rowIndex,
                    'position' => $position,
                ]);
            }
        }
    }

    /**
     * Просмотр рассылки
     */
    public function show(Request $request, $tenantUuid,int $id)
    {

        $tenant = app('tenant');

        $broadcast = Broadcast::where('tenant_id', $tenant->id)
            ->with(['author', 'media', 'buttons', 'recipients.user'])
            ->findOrFail($id);

        return response()->json([
            'data' => $broadcast,
            'statistics' => $this->broadcastService->getStatistics($broadcast),
        ]);
    }

    /**
     * Обновление черновика
     */
    public function update(Request $request, int $id)
    {
        $tenant = app('tenant');

        $broadcast = Broadcast::where('tenant_id', $tenant->id)
            ->where('status', Broadcast::STATUS_DRAFT)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'message' => 'nullable|string|max:4000',
            'recipient_type' => 'sometimes|in:all,active,vip,segment,custom',
            'recipient_filters' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $broadcast->update($validated);

        return response()->json([
            'success' => true,
            'data' => $broadcast,
        ]);
    }

    /**
     * Отправка рассылки
     */
    public function send(int $id)
    {
        $tenant = app('tenant');

        $broadcast = Broadcast::where('tenant_id', $tenant->id)
            ->whereIn('status', [
                Broadcast::STATUS_DRAFT,
                Broadcast::STATUS_SCHEDULED,
            ])
            ->findOrFail($id);

        $success = $this->broadcastService->send($broadcast);

        return response()->json([
            'success' => $success,
            'message' => $success
                ? 'Рассылка отправлена'
                : 'Не удалось отправить рассылку',
        ]);
    }

    /**
     * Отмена рассылки
     */
    public function cancel(int $id)
    {
        $tenant = app('tenant');

        $broadcast = Broadcast::where('tenant_id', $tenant->id)->findOrFail($id);

        $success = $this->broadcastService->cancel($broadcast);

        return response()->json([
            'success' => $success,
            'message' => $success
                ? 'Рассылка отменена'
                : 'Нельзя отменить эту рассылку',
        ]);
    }

    /**
     * Удаление рассылки
     */
    public function destroy(int $id)
    {
        $tenant = app('tenant');

        $broadcast = Broadcast::where('tenant_id', $tenant->id)
            ->where('status', Broadcast::STATUS_DRAFT)
            ->findOrFail($id);

        // Удаляем медиафайлы
        foreach ($broadcast->media as $media) {
            $this->broadcastService->deleteMedia($media);
        }

        $broadcast->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Рассылка удалена',
        ]);
    }

    /**
     * Загрузка медиафайла
     */
    public function uploadMedia(Request $request, int $broadcastId)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB
            'type' => 'required|in:image,video,audio',
        ]);

        $tenant = app('tenant');

        $broadcast = Broadcast::where('tenant_id', $tenant->id)
            ->where('status', Broadcast::STATUS_DRAFT)
            ->findOrFail($broadcastId);

        $file = $request->file('file');
        $type = $request->type;

        $path = $file->store(
            "broadcasts/{$broadcast->id}/{$type}",
            'public'
        );

        $media = BroadcastMedia::create([
            'broadcast_id' => $broadcast->id,
            'type' => $type,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sort_order' => $broadcast->media()->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $media,
        ]);
    }

    /**
     * Удаление медиафайла
     */
    public function deleteMedia(int $mediaId)
    {
        $media = BroadcastMedia::findOrFail($mediaId);

        $this->broadcastService->deleteMedia($media);

        return response()->json([
            'success' => true,
            'message' => 'Файл удалён',
        ]);
    }

    /**
     * Дублирование рассылки
     */
    public function duplicate(int $id)
    {
        $tenant = app('tenant');

        $original = Broadcast::where('tenant_id', $tenant->id)
            ->with(['media', 'buttons'])
            ->findOrFail($id);

        $copy = $original->replicate();
        $copy->title = $original->title . ' (копия)';
        $copy->status = Broadcast::STATUS_DRAFT;
        $copy->sent_at = null;
        $copy->total_recipients = 0;
        $copy->sent_count = 0;
        $copy->delivered_count = 0;
        $copy->read_count = 0;
        $copy->failed_count = 0;
        $copy->save();

        // Копируем кнопки
        foreach ($original->buttons as $button) {
            $buttonCopy = $button->replicate();
            $buttonCopy->broadcast_id = $copy->id;
            $buttonCopy->save();
        }

        return response()->json([
            'success' => true,
            'data' => $copy,
            'message' => 'Рассылка дублирована',
        ]);
    }

    /**
     * 🆕 Получение списка пользователей для выбора в рассылках
     */
    public function getUsers(Request $request)
    {
        $tenant = app('tenant');

        $query = TenantUser::where('tenant_id', $tenant->id)
            ->select(['id', 'name', 'phone', 'email', 'avatar', 'is_active']);

        // Поиск
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Фильтр по активности
        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $users = $query->orderBy('name')
            ->limit(500) // Лимит для производительности
            ->get();

        return response()->json([
            'data' => $users,
            'total' => $users->count(),
        ]);
    }

    /**
     * 🆕 Получение счётчиков получателей
     */
    /**
     * 🆕 Получение счётчиков получателей
     */
    public function getRecipientsCount()
    {
        $tenant = app('tenant');

        $counts = [
            'all' => TenantUser::where('tenant_id', $tenant->id)->count(),

            'active' => TenantUser::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->count(),

            'vip' => TenantUser::where('tenant_id', $tenant->id)
                ->where('is_vip', true)
                ->count(),
        ];

        return response()->json([
            'data' => $counts,
        ]);
    }
}
