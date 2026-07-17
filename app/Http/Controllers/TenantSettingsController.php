<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TenantSettingsController extends Controller
{

    private const ALLOWED_THEMES = [
        'default', 'ocean', 'forest', 'sunset', 'royal', 'mono',
        'coffee', 'midnight', 'cyber', 'gold'
    ];

    protected function getTenant(): Tenant
    {
        return app('tenant');
    }

    /**
     * Безопасное обновление части meta-данных
     */
    protected function mergeIntoMeta(Tenant $tenant, array $newData): void
    {
        $meta = $tenant->meta ?? [];
        // array_replace_recursive идеально подходит для глубокого обновления JSON-структур
        $meta = array_replace_recursive($meta, $newData);
        $tenant->update(['meta' => $meta]);
    }

    /**
     * 1. Обновление основной информации (бывшая Company)
     */
    public function updateBasic(Request $request)
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:512',
            'meta.company' => 'nullable|array',
        ]);

        // Обновляем прямые поля модели
        if (isset($validated['name'])) $tenant->name = $validated['name'];
        if (isset($validated['description'])) $tenant->description = $validated['description'];
        $tenant->save();

        // Обновляем вложенные данные в meta
        if (isset($validated['meta']['company'])) {
            $this->mergeIntoMeta($tenant, ['company' => $validated['meta']['company']]);
        }

        return response()->json(['success' => true, 'message' => 'Основная информация обновлена']);
    }




    /**
     * Загрузка иконки для пункта главного меню
     */
    public function uploadMainMenuIcon(Request $request)
    {
        $request->validate([
            'icon' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
            'menu_key' => 'required|string',
        ]);

        $file = $request->file('icon');
        $menuKey = $request->input('menu_key');

        // Генерируем уникальное имя файла, сохраняя оригинальное расширение
        $extension = $file->getClientOriginalExtension();
        $filename = 'menu_' . $menuKey . '_' . time() . '.' . $extension;

        // Сохраняем в public/storage/main-menu-icons (или ваша папка)
        $path = $file->storeAs('main-menu-icons', $filename, 'public');

        return response()->json([
            'success' => true,
            'filename' => Storage::url($path), // Вернет полный URL, например /storage/main-menu-icons/menu_shop_123.png
        ]);
    }

    public function resetMainMenuIcon(Request $request)
    {
        $validated = $request->validate([
            'menu_key' => 'required|string|in:shop,basket,profile,booking,history,chat,events,about,referral',
        ]);

        $tenant = $this->getTenant();
        $settings = $tenant->meta ?? [];

        // Поддерживаем оба ключа на случай миграции данных
        $mainMenu = $settings['main_menu'] ?? $settings['main_menu_items'] ?? [];
        $key = $validated['menu_key'];

        if (!isset($mainMenu[$key])) {
            return response()->json(['success' => false, 'message' => 'Пункт меню не найден'], 404);
        }

        $currentImg = $mainMenu[$key]['img'] ?? '';

        // 🆕 Дефолтные значения (должны совпадать с реальными файлами в public/images/menu/)
        $defaults = [
            'shop' => 'shop.png',
            'basket' => 'basket.png',
            'profile' => 'profile.png',
            'booking' => 'tables.png',
            'history' => 'history.png',
            'chat' => 'chat.png',
            'events' => 'events.png',
            'about' => 'contacts.png',
            'referral' => 'referral.png',
        ];

        $defaultImg = $defaults[$key] ?? 'default.png';

        // 🗑️ Если текущая картинка - это загруженный файл (лежит в storage), удаляем его
        if (str_starts_with($currentImg, '/storage/main-menu-icons/')) {
            // Преобразуем URL в путь для Storage disk 'public'
            $filePath = str_replace('/storage/', 'public/', $currentImg);

            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
        }

        // 🔄 Сбрасываем на дефолтное имя файла
        $mainMenu[$key]['img'] = "/images/shop/".$defaultImg;

        // Сохраняем под тем ключом, который используется в системе
        $settings['main_menu_items'] = $mainMenu;
        // Если у вас в БД используется ключ 'main_menu_items', раскомментируйте строку ниже и закомментируйте верхнюю:
        // $settings['main_menu_items'] = $mainMenu;

        $tenant->update(['meta' => $settings]);

        // Возвращаем правильный путь для фронтенда
        $responsePath = '/images/shop/' . $defaultImg;

        return response()->json([
            'success' => true,
            'message' => 'Иконка сброшена до стандартной',
            'img' => $responsePath,
            'default_name' => $defaultImg
        ]);
    }

    public function updateMainMenu(Request $request)
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'main_menu_items' => 'required|array', // Требуем массив
            'main_menu_items.*.is_visible' => 'boolean',
            'main_menu_items.*.title' => 'required|string|max:50', // Название обязательно
            'main_menu_items.*.img' => 'nullable|string|max:500', // 🆕 Увеличили с 100 до 255
            'main_menu_items.*.order' => 'nullable|integer',
        ]);

       // dd($validated['main_menu_items']);

        $this->mergeIntoMeta($tenant, ['main_menu_items' => $validated['main_menu_items']]);

        return response()->json(['success' => true, 'message' => 'Главное меню обновлено']);
    }

    /**
     * 2. Обновление настроек магазина
     */
    public function updateShop(Request $request)
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([

            'default_theme_scheme' => 'nullable|string|in:' . implode(',', self::ALLOWED_THEMES),
        ]);


        $this->mergeIntoMeta($tenant,  [...$request->all()]);


        return response()->json(['success' => true, 'message' => 'Настройки магазина обновлены']);
    }

    /**
     * 3. Обновление кэшбэка и сертификатов
     */
    public function updateCashback(Request $request)
    {
        $tenant = $this->getTenant();
        $this->mergeIntoMeta($tenant, $request->all());
        return response()->json(['success' => true, 'message' => 'Настройки баллов обновлены']);
    }

    /**
     * 4. Обновление интерактива (Кофе)
     */
    public function updateInteractive(Request $request)
    {
        $tenant = $this->getTenant();
        $this->mergeIntoMeta($tenant, ['coffee' => $request->input('coffee', [])]);
        return response()->json(['success' => true, 'message' => 'Настройки интерактива обновлены']);
    }

    /**
     * 5. Обновление столиков
     */
    public function updateTables(Request $request)
    {
        $tenant = $this->getTenant();
        $this->mergeIntoMeta($tenant, ['tables' => $request->input('tables', [])]);
        return response()->json(['success' => true, 'message' => 'Настройки столиков обновлены']);
    }

    /**
     * 6. Обновление пунктов меню
     */
    public function updateMenu(Request $request)
    {
        $tenant = $this->getTenant();
        $this->mergeIntoMeta($tenant, ['menu_items' => $request->input('menu_items', [])]);
        return response()->json(['success' => true, 'message' => 'Пункты меню обновлены']);
    }

    /**
     * 7. Обновление калькуляторов
     */
    public function updateCalculators(Request $request)
    {
        $tenant = $this->getTenant();
        $this->mergeIntoMeta($tenant, ['food_calculators' => $request->input('food_calculators', [])]);
        return response()->json(['success' => true, 'message' => 'Калькуляторы обновлены']);
    }

    /**
     * 8. Обновление бонусных игр
     */
    public function updateGames(Request $request)
    {
        $tenant = $this->getTenant();
        $this->mergeIntoMeta($tenant, ['bonus_games' => $request->input('bonus_games', [])]);
        return response()->json(['success' => true, 'message' => 'Бонусные игры обновлены']);
    }

    /**
     * 9. Обновление CRM
     */
    public function updateCrm(Request $request)
    {
        $tenant = $this->getTenant();
        $this->mergeIntoMeta($tenant, ['crm' => $request->input('crm', [])]);
        return response()->json(['success' => true, 'message' => 'Настройки CRM обновлены']);
    }
}
