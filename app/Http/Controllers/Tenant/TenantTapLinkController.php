<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantTapLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TenantTapLinkController extends Controller
{
    /**
     * Помощник для получения текущего тенанта
     */
    private function getCurrentTenant()
    {
        return Auth::guard('tenant')->user()->tenant;
    }

    /**
     * 🌐 ФРОНТЕНД: Получение данных для публичной страницы Taplink
     */
    public function index($slug)
    {
        $user = Auth::guard('tenant')->user();

        $tenant = Tenant::where('slug', $slug)
            ->with(['tapLinks' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order', 'asc');
            }])
            ->firstOrFail();

        Inertia::setRootView("mobile");

        return Inertia::render('TapLink', [
            'tenant' => $tenant,
            'tenant_user' => $user
        ]);
    }

    /**
     * ⚙️ АДМИНКА: Получение списка ссылок для Vue-компонента
     */
    public function adminIndex()
    {
        $tenant = $this->getCurrentTenant();

        $links = TenantTapLink::where('tenant_id', $tenant->id)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json($links);
    }

    /**
     * ⚙️ АДМИНКА: Создание новой ссылки
     */
    public function store(Request $request)
    {
        $tenant = $this->getCurrentTenant();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'icon' => 'nullable|string|max:100',
            'icon_bg' => 'nullable|string|max:20',
        ]);

        // Определяем максимальный sort_order, чтобы новая ссылка добавлялась в конец
        $maxSort = TenantTapLink::where('tenant_id', $tenant->id)->max('sort_order') ?? 0;

        $link = TenantTapLink::create([
            'tenant_id' => $tenant->id,
            'title' => $validated['title'],
            'url' => $validated['url'],
            'icon' => $validated['icon'] ?? 'fa-solid fa-link',
            'icon_bg' => $validated['icon_bg'] ?? '#6366f1',
            'sort_order' => $maxSort + 1,
            'is_active' => true,
        ]);

        return response()->json($link, 201);
    }

    /**
     * ⚙️ АДМИНКА: Обновление ссылки
     */
    public function update(Request $request, TenantTapLink $taplink)
    {
        $tenant = $this->getCurrentTenant();

        // 🔒 БЕЗОПАСНОСТЬ: Проверяем, что ссылка принадлежит текущему тенанту
        if ($taplink->tenant_id !== $tenant->id) {
            abort(403, 'Доступ запрещен');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'icon' => 'nullable|string|max:100',
            'icon_bg' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $taplink->update($validated);

        return response()->json($taplink);
    }

    /**
     * ⚙️ АДМИНКА: Удаление ссылки
     */
    public function destroy(TenantTapLink $taplink)
    {
        $tenant = $this->getCurrentTenant();

        // 🔒 БЕЗОПАСНОСТЬ: Проверяем, что ссылка принадлежит текущему тенанту
        if ($taplink->tenant_id !== $tenant->id) {
            abort(403, 'Доступ запрещен');
        }

        $taplink->delete();

        return response()->json(['message' => 'Ссылка успешно удалена']);
    }

    /**
     * ⚙️ АДМИНКА: Перемещение ссылки вверх или вниз (для кнопок в Vue)
     */
    public function move(Request $request, TenantTapLink $taplink)
    {
        $tenant = $this->getCurrentTenant();

        if ($taplink->tenant_id !== $tenant->id) {
            abort(403, 'Доступ запрещен');
        }

        $validated = $request->validate([
            'direction' => 'required|in:up,down'
        ]);

        $query = TenantTapLink::where('tenant_id', $tenant->id);

        // Находим соседнюю ссылку в зависимости от направления
        if ($validated['direction'] === 'up') {
            $targetLink = $query->where('sort_order', '<', $taplink->sort_order)
                ->orderBy('sort_order', 'desc')
                ->first();
        } else {
            $targetLink = $query->where('sort_order', '>', $taplink->sort_order)
                ->orderBy('sort_order', 'asc')
                ->first();
        }

        // Если соседняя ссылка найдена, меняем их sort_order местами
        if ($targetLink) {
            $currentSort = $taplink->sort_order;
            $targetSort = $targetLink->sort_order;

            $taplink->sort_order = $targetSort;
            $targetLink->sort_order = $currentSort;

            $taplink->save();
            $targetLink->save();
        }

        // Возвращаем обновленный отсортированный список для мгновенного обновления во Vue
        $updatedLinks = TenantTapLink::where('tenant_id', $tenant->id)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json($updatedLinks);
    }
}
