<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Exxxar\Kanban\Facades\Kanban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TenantPwaController extends Controller
{
    /**
     * 🆕 Получение текущих PWA настроек с URL файлов
     */
    public function getPwaSettings()
    {
        $tenant = app('tenant');
        $baseUrl = url('/');

        // Базовые настройки PWA из meta
        $pwa = $tenant->settings['pwa'] ?? [];

        // Хелпер для формирования URL иконки
        $getIconUrl = function ($filename) use ($tenant, $baseUrl) {
            if (!$filename) return null;

            $path = "tenants/{$tenant->id}/icons/{$filename}";
            if (Storage::disk('public')->exists($path)) {
                return "{$baseUrl}/storage/{$path}";
            }

            return null;
        };

        // Хелпер для формирования URL скриншота
        $getScreenshotUrl = function ($filename) use ($tenant, $baseUrl) {
            if (!$filename) return null;

            $path = "tenants/{$tenant->id}/screenshots/{$filename}";
            if (Storage::disk('public')->exists($path)) {
                return "{$baseUrl}/storage/{$path}";
            }

            return null;
        };

        // Формируем ответ с URL для превью
        $response = [
            'settings' => [
                'name' => $pwa['name'] ?? null,
                'short_name' => $pwa['short_name'] ?? null,
                'description' => $pwa['description'] ?? null,
                'theme_color' => $pwa['theme_color'] ?? $tenant->theme_color ?? '#ff8a00',
                'background_color' => $pwa['background_color'] ?? $tenant->background_color ?? '#ffffff',
                'orientation' => $pwa['orientation'] ?? 'portrait',
                'display' => $pwa['display'] ?? 'standalone',
                'lang' => $pwa['lang'] ?? 'ru',
                'categories' => $pwa['categories'] ?? ['shopping', 'food', 'business'],

                // Иконки с URL
                'icons' => [
                    'icon_192' => $pwa['icons']['icon_192'] ?? null,
                    'icon_512' => $pwa['icons']['icon_512'] ?? null,
                    'icon_192_maskable' => $pwa['icons']['icon_192_maskable'] ?? null,
                    'icon_512_maskable' => $pwa['icons']['icon_512_maskable'] ?? null,
                ],

                // Иконки с полными URL для превью
                'icons_urls' => [
                    'icon_192' => $getIconUrl($pwa['icons']['icon_192'] ?? null),
                    'icon_512' => $getIconUrl($pwa['icons']['icon_512'] ?? null),
                    'icon_192_maskable' => $getIconUrl($pwa['icons']['icon_192_maskable'] ?? null),
                    'icon_512_maskable' => $getIconUrl($pwa['icons']['icon_512_maskable'] ?? null),
                ],

                // Скриншоты
                'screenshots' => [
                    'mobile' => $pwa['screenshots']['mobile'] ?? null,
                    'desktop' => $pwa['screenshots']['desktop'] ?? null,
                ],

                // Скриншоты с полными URL
                'screenshots_urls' => [
                    'mobile' => $getScreenshotUrl($pwa['screenshots']['mobile'] ?? null),
                    'desktop' => $getScreenshotUrl($pwa['screenshots']['desktop'] ?? null),
                ],

                // Шорткаты
                'shortcuts' => $pwa['shortcuts'] ?? [
                        'menu' => ['enabled' => true, 'name' => 'Меню', 'short_name' => 'Меню', 'url' => '/pwa/#/menu', 'icon' => null],
                        'cart' => ['enabled' => true, 'name' => 'Корзина', 'short_name' => 'Корзина', 'url' => '/pwa/#/cart', 'icon' => null],
                        'cashback' => ['enabled' => true, 'name' => 'Кэшбэк', 'short_name' => 'Кэшбэк', 'url' => '/pwa/#/cashback', 'icon' => null],
                        'wheel' => ['enabled' => true, 'name' => 'Колесо', 'short_name' => 'Колесо', 'url' => '/pwa/#/wheel-classic', 'icon' => null],
                    ],

                // URL иконок шорткатов
                'shortcuts_icons_urls' => [],
            ],
        ];

        // Формируем URL для иконок шорткатов
        foreach ($response['settings']['shortcuts'] as $key => $shortcut) {
            if (!empty($shortcut['icon'])) {
                $response['settings']['shortcuts_icons_urls'][$key] = $getIconUrl($shortcut['icon']);
            }
        }

        return response()->json($response);
    }

    /**
     * Сохранение PWA настроек
     */
    public function savePwaSettings(Request $request)
    {
        $tenant = app('tenant');

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Тенант не найден',
            ], 404);
        }

        // 1. Получаем данные.
        // Поддерживаем оба варианта: если фронт шлет { pwa: {...} } или плоский объект {...}
        $pwaData = $request->input('pwa', $request->all());

        // 2. Валидация данных (защищает БД от поломки манифеста)
        // Мы явно разрешаем все поля, включая *_urls, так как они используются в manifest()
        $validated = $request->validate([
            'pwa.name' => 'nullable|string|max:255',
            'pwa.short_name' => 'nullable|string|max:255',
            'pwa.description' => 'nullable|string',
            'pwa.theme_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'pwa.background_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'pwa.display' => 'nullable|in:fullscreen,standalone,minimal-ui,browser',
            'pwa.orientation' => 'nullable|in:any,natural,landscape,portrait',
            'pwa.lang' => 'nullable|string|max:10',
            'pwa.categories' => 'nullable|array',

            // Разрешаем массивы настроек и их URL-представления
            'pwa.icons' => 'nullable|array',
            'pwa.screenshots' => 'nullable|array',
            'pwa.shortcuts' => 'nullable|array',
            'pwa.icons_urls' => 'nullable|array',
            'pwa.screenshots_urls' => 'nullable|array',
            'pwa.shortcuts_icons_urls' => 'nullable|array',
        ]);

        // Извлекаем проверенные данные (они будут в ключе 'pwa' из-за правил валидации)
        $cleanPwaData = $validated['pwa'] ?? $pwaData;

        // 3. Сохранение в meta (или settings, в зависимости от вашей модели)
        $meta = $tenant->meta ?? [];

        // Поскольку фронтенд присылает полное состояние формы PWA,
        // мы можем безопасно перезаписать секцию 'pwa' целиком.
        // Это гарантирует, что удаленные на фронте элементы (например, отключенный шорткат)
        // также удалятся из базы, а не будут висеть мертвым грузом.
        $meta['pwa'] = $cleanPwaData;

        $tenant->meta = $meta;
        $tenant->save();

        // 4. Формируем ответ
        return response()->json([
            'success' => true,
            'message' => 'Настройки PWA успешно сохранены',
            'pwa' => $tenant->meta['pwa'],
        ]);
    }

    /**
     * Сохранение PWA настроек
     */
    private function savePwaSection($tenant, array $pwaData): array
    {
        $validator = Validator::make($pwaData, [
            'name' => 'nullable|string|max:50',
            'short_name' => 'nullable|string|max:12',
            'description' => 'nullable|string|max:300',
            'theme_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'orientation' => 'nullable|string|in:portrait,landscape,any',
            'display' => 'nullable|string|in:standalone,fullscreen,minimal-ui,browser',
            'lang' => 'nullable|string|max:5',
            'categories' => 'nullable|array',
            'categories.*' => 'string|max:50',
            'icons' => 'nullable|array',
            'screenshots' => 'nullable|array',
            'shortcuts' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        // Обновляем meta
        $meta = $tenant->meta ?? [];
        $pwaSettings = $meta['pwa'] ?? [];

        $allowedFields = [
            'name', 'short_name', 'description', 'theme_color',
            'background_color', 'orientation', 'display', 'lang',
            'categories', 'icons', 'screenshots', 'shortcuts'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $pwaData)) {
                $pwaSettings[$field] = $pwaData[$field];
            }
        }

        $meta['pwa'] = $pwaSettings;
        $tenant->meta = $meta;

        // Обновляем основные поля тенанта
        if (!empty($pwaData['theme_color'])) {
            $tenant->theme_color = $pwaData['theme_color'];
        }
        if (!empty($pwaData['background_color'])) {
            $tenant->background_color = $pwaData['background_color'];
        }

        return ['success' => true];
    }

    /**
     * Сохранение Kanban CRM настроек
     *
     * ⚠️ ВАЖНО: settings — это computed attribute на основе meta,
     * поэтому сохраняем в meta['kanban'], а не в settings
     */
    private function saveKanbanSection($tenant, array $kanbanData): array
    {
        $validator = Validator::make($kanbanData, [
            'enabled' => 'required|boolean',
            'is_active' => 'nullable|boolean',
            'base_url' => 'nullable|url|max:255',
            'token' => 'nullable|string|max:255',
            'board_uuid' => 'nullable|string|regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            'order_thread' => 'nullable|integer|min:0|max:100',
            'auto_create_client' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        // === СОХРАНЯЕМ В meta, а НЕ в settings ===
        $meta = $tenant->meta ?? [];
        $kanbanSettings = $meta['kanban'] ?? [];

        // Разрешённые поля
        $allowedFields = [
            'enabled', 'is_active', 'base_url', 'token',
            'board_uuid', 'order_thread', 'auto_create_client',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $kanbanData)) {
                $kanbanSettings[$field] = $kanbanData[$field];
            }
        }

        // Синхронизация is_active и enabled
        if (isset($kanbanData['enabled'])) {
            $kanbanSettings['is_active'] = $kanbanData['enabled'];
        }
        if (isset($kanbanData['is_active'])) {
            $kanbanSettings['enabled'] = $kanbanData['is_active'];
        }

        // Дефолтные значения
        $kanbanSettings['enabled'] = $kanbanSettings['enabled'] ?? false;
        $kanbanSettings['is_active'] = $kanbanSettings['is_active'] ?? false;
        $kanbanSettings['base_url'] = $kanbanSettings['base_url'] ?? 'https://crm.mypwa.ru/api/v1';
        $kanbanSettings['order_thread'] = $kanbanSettings['order_thread'] ?? 0;
        $kanbanSettings['auto_create_client'] = $kanbanSettings['auto_create_client'] ?? true;

        // === КЛЮЧЕВОЕ ИСПРАВЛЕНИЕ ===
        $meta['kanban'] = $kanbanSettings;
        $tenant->meta = $meta; // ← сохраняем в meta, а не в settings

        // Логируем изменение
        Log::info('[TenantPwa] Kanban settings updated', [
            'tenant_id' => $tenant->id,
            'enabled' => $kanbanSettings['enabled'],
            'board_uuid' => $kanbanSettings['board_uuid'] ?? null,
        ]);

        return ['success' => true];
    }

    /**
     * Тестовый метод для проверки подключения к Kanban
     */
    public function testKanbanConnection(Request $request)
    {
        // === ВАЛИДАЦИЯ ===
        $validated = $request->validate([
            'settings.base_url' => 'required|url|max:255',
            'settings.token' => 'required|string|max:255',
            'settings.timeout' => 'nullable|integer|min:1|max:120',
            'settings.connect_timeout' => 'nullable|integer|min:1|max:60',
        ]);

        $settings = $validated['settings'];

        try {
            // === НАСТРОЙКА SDK ЧЕРЕЗ FLUENT INTERFACE ===
            Kanban::setBaseUrl($settings['base_url'])
                ->setToken($settings['token'])
                ->setTimeout($settings['timeout'] ?? 30)
                ->setConnectTimeout($settings['connect_timeout'] ?? 10)
                ->setRetryTimes(3)
                ->setRetrySleep(100)
                ->setLoggingEnabled(false); // При тесте логи не нужны

            // === ПРОВЕРКА ПОДКЛЮЧЕНИЯ ===
            $boards = Kanban::boards()->list();

            // === СБОР ДОПОЛНИТЕЛЬНОЙ ИНФОРМАЦИИ ===
            $boardsData = array_map(fn($board) => [
                'uuid' => $board->uuid,
                'title' => $board->title,
                'columns_count' => count($board->columns ?? []),
                'tags_count' => count($board->tags ?? []),
            ], $boards);

            // === ПОЛУЧЕНИЕ СПИСКА ШАБЛОНОВ ===
            $templates = [];
            try {
                $templates = Kanban::boards()->templates();
            } catch (\Throwable $e) {
                // Шаблоны — опциональны
            }

            return response()->json([
                'success' => true,
                'message' => '✅ Подключение к KanbanCRM установлено',
                'connection' => [
                    'base_url' => $settings['base_url'],
                    'token_preview' => substr($settings['token'], 0, 8) . '...',
                    'timeout' => $settings['timeout'] ?? 30,
                    'connect_timeout' => $settings['connect_timeout'] ?? 10,
                ],
                'stats' => [
                    'boards_count' => count($boards),
                    'templates_count' => count($templates),
                ],
                'boards' => $boardsData,
                'templates' => array_map(fn($t) => [
                    'id' => $t['id'] ?? null,
                    'title' => $t['title'] ?? null,
                ], $templates),
            ]);

        } catch (\Exxxar\Kanban\Exceptions\KanbanException $e) {
            Log::warning('[TenantPwa] Kanban API error: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'base_url' => $settings['base_url'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Ошибка API KanbanCRM: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'hint' => $this->getErrorHint($e->getCode()),
            ], 400);

        } catch (\Exxxar\Kanban\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка валидации на стороне KanbanCRM',
                'validation_errors' => $e->errors(),
            ], 422);

        } catch (\Exxxar\Kanban\Exceptions\NotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ресурс не найден в KanbanCRM',
                'hint' => 'Проверьте правильность base_url и токена',
            ], 404);

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error('[TenantPwa] Kanban connection failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Не удалось подключиться к серверу KanbanCRM',
                'hint' => 'Проверьте URL и доступность сервера',
                'details' => $e->getMessage(),
            ], 503);

        } catch (\Throwable $e) {
            Log::error('[TenantPwa] Kanban test failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Непредвиденная ошибка при подключении',
                'hint' => 'Обратитесь в поддержку',
            ], 500);
        }
    }

    /**
     * Подсказка по коду ошибки
     */
    private function getErrorHint(int $code): string
    {
        return match ($code) {
            401, 403 => 'Неверный токен или недостаточно прав',
            404 => 'API endpoint не найден. Проверьте base_url',
            422 => 'Ошибка валидации данных',
            429 => 'Слишком много запросов. Попробуйте позже',
            500, 502, 503, 504 => 'Сервер KanbanCRM временно недоступен',
            default => 'Проверьте настройки подключения',
        };
    }

    /**
     * Тест конкретной доски по UUID
     */
    public function testKanbanBoard(Request $request, string $boardUuid)
    {
        $validated = $request->validate([
            'base_url' => 'required|url',
            'token' => 'required|string',
        ]);

        try {
            Kanban::setBaseUrl($validated['base_url'])
                ->setToken($validated['token'])
                ->setTimeout(15)
                ->setConnectTimeout(5)
                ->setLoggingEnabled(false);

            $board = Kanban::boards()->get($boardUuid);

            return response()->json([
                'success' => true,
                'message' => '✅ Доска найдена и доступна',
                'board' => [
                    'id' => $board->id,
                    'uuid' => $board->uuid,
                    'title' => $board->title,
                    'description' => $board->description,
                    'columns_count' => count($board->columns),
                    'columns' => array_map(fn($col) => [
                        'id' => $col->id,
                        'thread' => $col->thread,
                        'title' => $col->title,
                        'position' => $col->position,
                    ], $board->columns),
                    'tags_count' => count($board->tags),
                    'tags' => array_map(fn($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'color' => $tag->color,
                    ], $board->tags),
                ],
            ]);

        } catch (\Exxxar\Kanban\Exceptions\NotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Доска не найдена',
                'hint' => 'Проверьте правильность UUID доски',
            ], 404);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при проверке доски: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить текущие настройки PWA и Kanban
     */
    public function getSettings()
    {
        $tenant = app('tenant');

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Тенант не найден',
            ], 404);
        }

        // Берём из meta, а не из settings
        $meta = $tenant->meta ?? [];

        return response()->json([
            'success' => true,
            'pwa' => $meta['pwa'] ?? [
                    'name' => null,
                    'short_name' => null,
                    'description' => null,
                    'theme_color' => '#667eea',
                    'background_color' => '#ffffff',
                    'orientation' => 'any',
                    'display' => 'standalone',
                    'lang' => 'ru',
                    'categories' => [],
                ],
            'kanban' => $meta['kanban'] ?? [
                    'enabled' => false,
                    'is_active' => false,
                    'base_url' => 'https://crm.mypwa.ru/api/v1',
                    'token' => '',
                    'board_uuid' => '',
                    'order_thread' => 0,
                    'auto_create_client' => true,
                ],
        ]);
    }

    /**
     * Загрузка иконки
     */
    public function uploadIcon(Request $request)
    {
        $request->validate([
            'icon' => 'required|image|mimes:png|max:2048',
            'type' => 'required|string|in:icon_192,icon_512,icon_192_maskable,icon_512_maskable,shortcut_menu,shortcut_cart,shortcut_cashback,shortcut_wheel',
        ]);

        $tenant = app('tenant');
        $file = $request->file('icon');
        $type = $request->input('type');

        // Удаляем старую иконку этого типа, если есть
        $this->deleteOldIcon($tenant->id, $type);

        // Генерируем имя файла
        $filename = $type . '_' . time() . '.png';
        $path = "tenants/{$tenant->id}/icons";

        // Сохраняем
        $file->storeAs($path, $filename, 'public');

        $fullPath = "{$path}/{$filename}";
        $url = url("/storage/{$fullPath}");

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'path' => $fullPath,
            'url' => $url,
        ]);
    }

    /**
     * Загрузка скриншота
     */
    public function uploadScreenshot(Request $request)
    {
        $request->validate([
            'screenshot' => 'required|image|mimes:png,jpg,jpeg|max:5120',
            'type' => 'required|string|in:mobile,desktop',
        ]);

        $tenant = app('tenant');
        $file = $request->file('screenshot');
        $type = $request->input('type');

        // Удаляем старый скриншот этого типа
        $this->deleteOldScreenshot($tenant->id, $type);

        $filename = "screenshot_{$type}_" . time() . '.' . $file->getClientOriginalExtension();
        $path = "tenants/{$tenant->id}/screenshots";

        $file->storeAs($path, $filename, 'public');

        $fullPath = "{$path}/{$filename}";
        $url = url("/storage/{$fullPath}");

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'path' => $fullPath,
            'url' => $url,
        ]);
    }

    /**
     * Удаление иконки
     */
    public function deleteIcon(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
        ]);

        $tenant = app('tenant');
        $type = $request->input('type');

        $this->deleteOldIcon($tenant->id, $type);

        // Удаляем из настроек
        $meta = $tenant->meta ?? [];
        if (isset($meta['pwa']['icons'][$type])) {
            unset($meta['pwa']['icons'][$type]);
            $tenant->meta = $meta;
            $tenant->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Иконка удалена',
        ]);
    }

    /**
     * Удаление скриншота
     */
    public function deleteScreenshot(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:mobile,desktop',
        ]);

        $tenant = app('tenant');
        $type = $request->input('type');

        $this->deleteOldScreenshot($tenant->id, $type);

        // Удаляем из настроек
        $meta = $tenant->meta ?? [];
        if (isset($meta['pwa']['screenshots'][$type])) {
            unset($meta['pwa']['screenshots'][$type]);
            $tenant->meta = $meta;
            $tenant->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Скриншот удалён',
        ]);
    }

    // ==========================================
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ==========================================

    /**
     * Удаление старой иконки по типу
     */
    private function deleteOldIcon($tenantId, $type)
    {
        $tenant = \App\Models\Tenant\Tenant::find($tenantId);
        if (!$tenant) return;

        $meta = $tenant->meta ?? [];
        $pwa = $meta['pwa'] ?? [];

        // Проверяем иконки
        if (isset($pwa['icons'][$type])) {
            $oldFilename = $pwa['icons'][$type];
            $oldPath = "tenants/{$tenantId}/icons/{$oldFilename}";
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Проверяем иконки шорткатов
        if (str_starts_with($type, 'shortcut_')) {
            $shortcutKey = str_replace('shortcut_', '', $type);
            if (isset($pwa['shortcuts'][$shortcutKey]['icon'])) {
                $oldFilename = $pwa['shortcuts'][$shortcutKey]['icon'];
                $oldPath = "tenants/{$tenantId}/icons/{$oldFilename}";
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }
    }

    /**
     * Удаление старого скриншота по типу
     */
    private function deleteOldScreenshot($tenantId, $type)
    {
        $tenant = \App\Models\Tenant\Tenant::find($tenantId);
        if (!$tenant) return;

        $meta = $tenant->meta ?? [];
        $pwa = $meta['pwa'] ?? [];

        if (isset($pwa['screenshots'][$type])) {
            $oldFilename = $pwa['screenshots'][$type];
            $oldPath = "tenants/{$tenantId}/screenshots/{$oldFilename}";
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
    }
}
