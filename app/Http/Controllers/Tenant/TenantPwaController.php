<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $pwaData = $request->input('pwa', []);

        // Валидация основных полей
        $validator = validator($pwaData, [
            'name' => 'nullable|string|max:50',
            'short_name' => 'nullable|string|max:12',
            'description' => 'nullable|string|max:300',
            'theme_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'orientation' => 'nullable|string|in:portrait,landscape,any',
            'display' => 'nullable|string|in:standalone,fullscreen,minimal-ui,browser',
            'lang' => 'nullable|string|max:5',
            'categories' => 'nullable|array',
            'categories.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Обновляем meta
        $meta = $tenant->meta ?? [];

        // Сохраняем только разрешённые поля
        $allowedFields = [
            'name', 'short_name', 'description', 'theme_color',
            'background_color', 'orientation', 'display', 'lang',
            'categories', 'icons', 'screenshots', 'shortcuts'
        ];

        $pwaSettings = $meta['pwa'] ?? [];
        foreach ($allowedFields as $field) {
            if (isset($pwaData[$field])) {
                $pwaSettings[$field] = $pwaData[$field];
            }
        }

        $meta['pwa'] = $pwaSettings;
        $tenant->meta = $meta;

        // Также обновляем основные поля тенанта
        if (!empty($pwaData['theme_color'])) {
            $tenant->theme_color = $pwaData['theme_color'];
        }
        if (!empty($pwaData['background_color'])) {
            $tenant->background_color = $pwaData['background_color'];
        }

        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => 'PWA настройки сохранены',
            'settings' => $tenant->settings,
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
