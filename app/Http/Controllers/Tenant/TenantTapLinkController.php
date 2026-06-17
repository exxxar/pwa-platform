<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

class TenantTapLinkController extends Controller
{
    /**
     * Получение данных для страницы Taplink
     *
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(string $slug)
    {
        // Ищем тенанта по слагу. Если не найден - 404
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        // Получаем активные ссылки через relation
        $links = $tenant->tapLinks()
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'title', 'url', 'icon', 'icon_bg']);

        // Формируем ответ.
        // Адаптируй поля (avatar, description) под реальную структуру твоей таблицы tenants
        return response()->json([
            'success' => true,
            'data' => [
                'tenant' => [
                    'name' => $tenant->name,
                    'description' => $tenant->description ?? $tenant->settings['description'] ?? 'Добро пожаловать!',
                    'avatar' => $tenant->avatar ?? $tenant->settings['avatar'] ?? null,
                    'theme_color' => $tenant->theme_color ?? '#ff8a00',
                ],
                'links' => $links,
            ]
        ]);
    }
}
