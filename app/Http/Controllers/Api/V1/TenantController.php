<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantPermission;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantUser;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use function str_contains;
use function str_starts_with;

class TenantController extends Controller
{


    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'url_or_slug'       => ['required', 'string', 'max:255'],
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'address'           => ['nullable', 'string'],
            'phone'             => ['nullable', 'string'],
            'site'              => ['nullable', 'url'],
            'lat'               => ['nullable', 'numeric'],
            'lng'               => ['nullable', 'numeric'],
            'working_hours'     => ['nullable', 'array'],
        ]);

        $input = trim($request->input('url_or_slug'));
        $logs  = [];

        $slug = $this->extractSlug($input, $logs);
        if (!$slug) {
            return response()->json(['success' => false, 'message' => 'Неверный slug или URL', 'logs' => $logs], 422);
        }

        $logs[] = "🔍 Извлеченный slug: {$slug}";
        $name = $request->input('name') ?: Str::headline($slug);

        // Собираем данные места для миграции
        $placeData = array_filter([
            'description'       => $request->input('description'),
            'short_description' => $request->input('short_description'),
            'address'           => $request->input('address'),
            'phone'             => $request->input('phone'),
            'site'              => $request->input('site'),
            'lat'               => $request->input('lat'),
            'lng'               => $request->input('lng'),
            'working_hours'     => $request->input('working_hours'),
        ], fn($v) => $v !== null);

        $existingTenant = Tenant::where('slug', $slug)->first();
        $alreadyExists  = (bool) $existingTenant;
        if ($alreadyExists) {
            $logs[] = "⚠️ Тенант уже существует. Обновляем данные.";
        }

        $tenantData = $this->buildTenantData($slug, $name, $placeData, $logs);

        $logs[] = "💾 Сохранение данных тенанта...";
        $tenant = Tenant::withoutEvents(function () use ($slug, $tenantData) {
            return Tenant::updateOrCreate(['slug' => $slug], $tenantData);
        });

        $logs[] = "⚙️ Настройка прав, ролей и администратора...";
        $provision = $this->provisionTenant($tenant, $logs);
        $logs[] = "✅ Тенант полностью готов к работе!";

        return response()->json([
            'success'        => true,
            'already_exists' => $alreadyExists,
            'tenant'         => [
                'id'   => $tenant->id,
                'uuid' => $tenant->uuid,
                'slug' => $tenant->slug,
                'name' => $tenant->name,
            ],
            'admin' => $provision,
            'logs'  => $logs,
        ]);
    }

    private function buildTenantData(string $slug, string $name, array $placeData, array &$logs): array
    {
        // 1. Базовая конфигурация (как в оригинале)
        $baseMeta = [
            'sbp' => [
                'sber' => [],
                'tinkoff' => [
                    'tax' => 'osn',
                    'vat' => 'none',
                    'terminal_key' => '1739195568603',
                    'terminal_password' => '_tou#gb2VC6so^Vt',
                ],
                'selected_sbp_bank' => 'tinkoff',
            ],
            'icons' => [
                ['slug' => 'profile', 'title' => 'Профиль', 'has_icon' => true, 'image_url' => 'profile.png', 'is_visible' => true],
                ['slug' => 'shop', 'title' => 'Заказать Доставку', 'has_icon' => true, 'image_url' => 'shop.png', 'is_visible' => true],
                ['slug' => 'basket', 'title' => 'Корзина', 'has_icon' => true, 'image_url' => 'basket.png', 'is_visible' => true],
                ['slug' => 'history', 'title' => 'История заказов', 'has_icon' => true, 'image_url' => 'history.png', 'is_visible' => true],
                ['slug' => 'events', 'title' => 'Розыгрыши', 'has_icon' => true, 'image_url' => 'events.png', 'is_visible' => true],
                ['slug' => 'about', 'title' => 'О Нас & Контакты', 'has_icon' => true, 'image_url' => 'contacts.png', 'is_visible' => true],
                ['slug' => 'wheel_of_fortune_btn', 'title' => 'Колесо фортуны', 'has_icon' => true, 'image_url' => 'profile.png', 'is_visible' => true],
                ['slug' => 'friends_btn', 'title' => 'Друзья', 'has_icon' => true, 'image_url' => 'profile.png', 'is_visible' => true],
                ['slug' => 'main_menu_btn', 'title' => 'Главное меню', 'is_visible' => true, 'has_icon' => false, 'image_url' => 'booking.png'],
                ['slug' => 'booking', 'title' => 'Бронирование столика', 'image_url' => 'booking.png', 'is_visible' => true, 'has_icon' => true],
            ],
            'coffee' => [],
            'kanban' => ['is_active' => false, 'board_uuid' => null, 'token' => null],
            'manager' => ['link' => 'https://t.me/EgorShipilov', 'title' => 'Написать'],
            'interval' => 1,
            'map_tiler' => 'l7t0HU7CqsgOKgS9rtvU',
            'min_price' => 800,
            'max_tables' => 10,
            'can_use_sbp' => true,
            'is_disabled' => false,
            'can_use_card' => false,
            'can_use_cash' => false,
            'shop_coords' => '45.070734, 39.037108', // Дефолт, будет перезаписан, если есть lat/lng
            'price_per_km' => 70,
            'min_base_delivery_price' => 100,
            'free_shipping_starts_from' => 0,
            'subscriptions' => [
                'text' => 'Подпишись на каналы ниже и получи доступ к проекту',
                'channels' => [['id' => -1001947900076, 'link' => '@gastro_pub_yoj', 'title' => 'Канал Ежа']],
                'is_active' => true,
            ],
            'tables_variants' => [
                ['id' => 11, 'edit' => true, 'image' => '11.png', 'seats' => 4, 'number' => 1, 'description' => 'Прямоугольный стол с диваном на 4 мест'],
                ['id' => 11, 'edit' => true, 'image' => '11.png', 'seats' => 4, 'number' => 2, 'description' => 'Прямоугольный стол с диваном на 4 мест'],
            ],
            'init_certificate' => ['type' => 'cashback', 'title' => 'Подарочный сертификат', 'amount' => 500, 'is_active' => true, 'description' => '500 рублей на CashBack'],
            'need_promo_code' => true,
            'need_table_list' => true,
            'need_person_counter' => true,
            'need_category_by_page' => true,
            'need_health_restrictions' => true,
            'need_prizes_from_wheel_of_fortune' => true,
            'need_hide_delivery_period' => false,
            'need_hide_disabled_products' => true,
            'need_automatic_delivery_request' => true,
            'need_pay_after_call' => false,
            'can_buy_after_closing' => false,
            'can_use_booking' => false,
            'can_work_in_marketplace' => null,
            'min_price_for_cashback' => 2000,
        ];

        // 2. 🚀 МИГРАЦИЯ ДАННЫХ: Перезаписываем дефолты данными из Place
        $migratedFields = [];

        if (!empty($placeData['lat']) && !empty($placeData['lng'])) {
            $baseMeta['shop_coords'] = "{$placeData['lat']}, {$placeData['lng']}";
            $migratedFields[] = 'Координаты';
        }
        if (!empty($placeData['address'])) {
            $baseMeta['address'] = $placeData['address'];
            $migratedFields[] = 'Адрес';
        }
        if (!empty($placeData['phone'])) {
            $baseMeta['phone'] = $placeData['phone'];
            $migratedFields[] = 'Телефон';
        }
        if (!empty($placeData['site'])) {
            $baseMeta['website'] = $placeData['site'];
            $migratedFields[] = 'Сайт';
        }
        if (!empty($placeData['working_hours'])) {
            $baseMeta['working_hours'] = $placeData['working_hours'];
            $migratedFields[] = 'Часы работы';
        }

        if (!empty($migratedFields)) {
            $logs[] = "📦 Перенесены данные места: " . implode(', ', $migratedFields);
        }

        // 3. Формируем итоговый массив тенанта
        return [
            'uuid' => (string) Str::uuid(),
            'slug' => $slug,
            'name' => $name,
            'description' => $placeData['description'] ?? 'Тенант ' . $name,
            'long_description' => $placeData['description'] ?? 'Описание тенанта ' . $name,
            'short_description' => $placeData['short_description'] ?? 'Короткое описание',
            'image' => null,
            'icon' => null,
            'theme_color' => '#3490dc',
            'app_type' => 'partner',
            'order_channel' => 'web',
            'balance' => 1000,
            'tax_per_day' => 5,
            'meta' => $baseMeta,
            'is_active' => true,
            'welcome_message' => 'Добро пожаловать!',
            'maintenance_message' => 'Ведутся технические работы',
            'blocked_message' => 'Аккаунт заблокирован',
            'cashback_fire_percent' => 10,
            'cashback_fire_period' => 7,
            'vk_shop_link' => 'https://vk.com/test_shop',
            'level_1' => 1.5,
            'level_2' => 3.0,
            'level_3' => 5.0,
        ];
    }
    /**
     * Умное извлечение slug.
     * Если пришло что-то похожее на URL (есть http/ или есть ".") — парсим host.
     * Иначе — считаем, что пришёл просто slug.
     */
    private function extractSlug(string $input, array &$logs): ?string
    {
        // Проверяем: это URL или просто slug?
        $looksLikeUrl = str_starts_with($input, 'http://')
            || str_starts_with($input, 'https://')
            || str_contains($input, '.');

        if ($looksLikeUrl) {
            $url = $input;
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $host = parse_url($url, PHP_URL_HOST);
            if (!$host) {
                return null;
            }

            $parts = explode('.', $host);
            $slug  = $parts[0];

            if (empty($slug) || $slug === 'www') {
                // Для API нет интерактива — возвращаем null, клиент должен прислать корректные данные
                $logs[] = "Не удалось автоматически определить slug из URL '{$input}'. Используйте поле name или передайте slug напрямую.";
                return null;
            }

            return $slug;
        }

        // Пришёл просто slug — используем как есть
        return $input;
    }


    /**
     * "Обустраивает" тенант: права → роль → админ.
     * Возвращает массив с данными админа.
     */
    private function provisionTenant(Tenant $tenant, array &$logs): array
    {
        // 1. Создаём/находим все права для этого тенанта из конфига
        $permissionsMap = config('permissions.map', []);
        $permissionIds  = [];

        foreach ($permissionsMap as $name => $label) {
            $perm = TenantPermission::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                ['label' => $label]
            );
            $permissionIds[] = $perm->id;
        }

        // 2. Создаём/находим роль super_admin
        $role = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'super_admin'],
            ['label' => 'Суперадмин']
        );

        // 3. Синхронизируем права с передачей tenant_id в pivot
        $permissionsSyncData = [];
        foreach ($permissionIds as $permId) {
            $permissionsSyncData[$permId] = ['tenant_id' => $tenant->id];
        }
        $role->permissions()->sync($permissionsSyncData);

        // 4. Формируем email админа
        $safeSlug   = Str::slug($tenant->slug ?: $tenant->name, '_');
        $adminEmail = "admin_{$safeSlug}@mypwa.ru";
        $password   = 'admin123';

        // 5. Проверяем, существует ли уже админ
        $existingAdmin = TenantUser::where('tenant_id', $tenant->id)
            ->where('email', $adminEmail)
            ->first();

        if ($existingAdmin) {
            $hasSuperAdminRole = $existingAdmin->roles()
                ->where('tenant_roles.id', $role->id)
                ->wherePivot('tenant_id', $tenant->id)
                ->exists();

            if (!$hasSuperAdminRole) {
                $existingAdmin->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
                $logs[] = "🔗 Роль super_admin добавлена существующему администратору.";
            }

            $logs[] = "👤 Администратор уже существует: {$adminEmail}";
            $logs[] = "🔑 Пароль: {$password}";

            return [
                'email'    => $adminEmail,
                'password' => $password,
                'created'  => false,
            ];
        }

        // 6. Создаём нового админа
        $adminUser = TenantUser::create([
            'tenant_id' => $tenant->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Администратор',
            'email' => $adminEmail,
            'phone' => '+79494320661',
            'password' => bcrypt($password),
            'is_active' => true,
            'is_vip' => true,
            'referral_code' => method_exists(TenantUser::class, 'generateReferralCode')
                ? TenantUser::generateReferralCode()
                : Str::random(8),
        ]);

        $adminUser->roles()->attach($role->id, ['tenant_id' => $tenant->id]);

        $logs[] = "👤 Создан новый администратор: {$adminEmail}";
        $logs[] = "🔑 Пароль по умолчанию: {$password}";

        return [
            'email'    => $adminEmail,
            'password' => $password,
            'created'  => true,
        ];
    }
}
