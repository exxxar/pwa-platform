<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Notifications\NewOrderNotification;
use App\Services\BasketService;
use App\Services\ChatService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class TenantAuthController extends Controller
{

    public function serviceWorker()
    {

        $path = public_path('modified_sw.js');

        if (!file_exists($path)) {
            abort(404);
        }

        $content = file_get_contents($path);

        $currentVersion = config('app.version', '1.0.0');

        // Заменяем метку на реальную версию из .env
        $content = str_replace('___MY_PROJECT_VERSION___', $currentVersion, $content);

        return response($content, 200, [
            'Content-Type' => 'application/javascript',
            'Service-Worker-Allowed' => '/pwa/',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);

    }

    public function loginPage(Request $request)
    {

        $tenantModel = $request->tenant;

        if (is_null($tenantModel)) {
            return redirect()->route('public.landing')->with('requested_slug', $tenantModel->name);
        }

        Session::put("tenant", $tenantModel);

        $tenantUser = Auth::guard('tenant')->user();

        Inertia::setRootView("mobile");


        return Inertia::render('LoginPage', [
            'tenant' => $tenantModel,
            'tenant_user' => $tenantUser,
            'initial_data' => null,
        ]);

    }

    public function registrationPage()
    {
        return Inertia::render('Tenant/Auth/Register', [
            'tenant' => tenant(),
            'user' => auth('tenant')->user(),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        // Определяем, что ввел пользователь: email или телефон
        $field = filter_var($request->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $credentials = [
            $field => $request->identifier,
            'password' => $request->password,
        ];


        // Пытаемся авторизовать через guard 'tenant'
        if (Auth::guard('tenant')->attempt($credentials, true)) {
            // Защита от фиксации сессии
            $request->session()->regenerate();

            $user = Auth::guard('tenant')->user();

            // 🚀 ВАЖНО: Загружаем роли и права, чтобы фронтенд их сразу получил
            $user->load('roles.permissions');

            return response()->json([
                'message' => 'Успешный вход',
                'user' => $user,
                // 'token' => $user->createToken('auth_token')->plainTextToken, // Раскомментируйте, если используете Laravel Sanctum с токенами
            ]);
        }

        return response()->json([
            'message' => 'Неверный телефон/email или пароль'
        ], 401);
    }

    /**
     * Регистрация нового пользователя
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:tenant_users,phone',
            'password' => 'required|string|min:6',
        ]);

        $user = TenantUser::create([
            'tenant_id' => app('tenant')->id, // или tenant()->id
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'is_active' => true, // Гарантируем, что пользователь активен
            // 'uuid' => (string) \Illuminate\Support\Str::uuid(), // Если модель не генерирует это сама
        ]);

        // Автоматический вход после регистрации
        Auth::guard('tenant')->login($user);
        $request->session()->regenerate();

        $user->load('roles.permissions');

        return response()->json([
            'message' => 'Регистрация успешна',
            'user' => $user,
        ], 201);
    }

    /**
     * Выход из системы
     */
    public function logout(Request $request)
    {
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Вы успешно вышли']);
    }

    public function me()
    {
        $user = Auth::guard('tenant')->user();

        if ($user) {
            $user->load(['roles.permissions']);
        }

        return response()->json($user);
    }


    public function handlerShopLanding(Request $request)
    {

        $tenant = $request->tanant;
        \Illuminate\Support\Facades\Session::put("tenant", $tenant->name ?? null);

        $tenantUser = Auth::guard('tenant')->user();

        Inertia::setRootView("shop-landing");
        return Inertia::render('ShopLanding', [
            'tenant' => $tenant,
            'tenant_user' => $tenantUser
        ]);

    }

    public function handlerAgent(Request $request)
    {

        $tenant = $request->tenant;
        \Illuminate\Support\Facades\Session::put("tenant", $tenant->name ?? null);

        $tenantUser = Auth::guard('tenant')->user();

        Inertia::setRootView("mobile");
        return Inertia::render('AgentDashboard', [
            'tenant' => $tenant,
            'tenant_user' => $tenantUser
        ]);

    }


    public function handler(
        Request $request, // 🆕 Добавляем Request
                $tenant,
        ChatService $chatService,
        BasketService $basketService,
        ProductService $productService
    ) {
        if (empty($tenant) || !preg_match('/^[a-zA-Z0-9_-]+$/', $tenant)) {
            return redirect()->route('public.landing');
        }

        $tenantModel = Tenant::query()->where('slug', $tenant)->first();

        if (is_null($tenantModel)) {
            return redirect()->route('public.landing')->with('requested_slug', $tenant);
        }

        Session::put("tenant", $tenantModel);

        // 🆕 1. ПЕРЕХВАТ РЕФЕРАЛЬНОГО КОДА
        $refCode = $request->query('ref');
        $hasPendingReferral = false;

        if ($refCode) {
            // Сохраняем в сессию, чтобы использовать при реальной авторизации/создании
            Session::put('pending_referral_code', $refCode);
            $hasPendingReferral = true;

            // Опционально: очищаем URL от ?ref=..., чтобы ссылка выглядела аккуратно при шеринге
            // Но для Inertia лучше просто оставить как есть, фронтенд сам разберется.
        }

        $rawDisabled = $tenantModel->settings["is_disabled"] ?? false;

        $isDisabled = match (true) {
            is_bool($rawDisabled) => $rawDisabled,
            is_string($rawDisabled) => in_array(strtolower($rawDisabled), ['true', '1', 'yes', 'on']),
            is_numeric($rawDisabled) => (int)$rawDisabled === 1,
            default => false,
        };

        $tenantUser = Auth::guard('tenant')->user();

        $isAdmin = $tenantUser && (
                in_array('super_admin', $tenantUser->role_names ?? []) ||
                in_array('admin', $tenantUser->role_names ?? [])
            );

        $initialData = $this->loadInitialData(
            $tenantModel,
            $tenantUser,
            $chatService,
            $basketService,
            $productService
        );

        Inertia::setRootView("mobile");

        if ($isDisabled && !$isAdmin) {
            return Inertia::render('Public/MaintenancePage', [
                'tenant' => $tenantModel,
                'tenant_user' => $tenantUser,
                'initial_data' => $initialData,
                'has_pending_referral' => $hasPendingReferral, // 🆕 Передаем во фронтенд
                'referral_code' => $refCode, // 🆕 Передаем сам код
            ]);
        }

        return Inertia::render('MobileMain', [
            'tenant' => $tenantModel,
            'tenant_user' => $tenantUser,
            'initial_data' => $initialData,
            'has_pending_referral' => $hasPendingReferral, // 🆕 Передаем во фронтенд
            'referral_code' => $refCode, // 🆕 Передаем сам код
        ]);
    }

    /**
     * 🆕 Загрузка начальных данных для приложения
     */
    private function loadInitialData(
        Tenant         $tenant,
                       $tenantUser,
        ChatService    $chatService,
        BasketService  $basketService,
        ProductService $productService
    ): array
    {
        $data = [
            'chats' => null,
            'cart' => null,
            'products_count' => 0,
            'recommendations' => null,
        ];

        try {
            // 1. Чаты (только метаданные)
            if ($tenantUser) {
                $data['chats'] = $chatService->getDialogsSummary($tenantUser->id);
            }

            // 2. Корзина
            $data['cart'] = $basketService->getCartSummary($tenant->id, $tenantUser?->id);

            // 3. Количество товаров в каталоге
            $data['products_count'] = $productService->getActiveProductsCount($tenant->id);

            // 4. Рекомендации (первые 5-10 товаров)
            $data['recommendations'] = $productService->getRecommendedProducts(
                $tenant->id,
                $tenantUser?->id,
                8
            );

        } catch (\Throwable $e) {
            // Логируем ошибку, но не прерываем загрузку
            \Log::warning('[InitialData] Ошибка загрузки: ' . $e->getMessage());
        }

        return $data;
    }

    public function manifest($tenantSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $baseUrl = request()->getSchemeAndHttpHost();

        $tenantIconBase = "{$baseUrl}/storage/tenants/{$tenant->id}/icons";
        $tenantScreenshotBase = "{$baseUrl}/storage/tenants/{$tenant->id}/screenshots";
        $defaultIconBase = "{$baseUrl}/storage/defaults/icons";
        $defaultScreenshotBase = "{$baseUrl}/storage/defaults/screenshots";

        // 🆕 PWA настройки из settings
        $pwa = $tenant->settings['pwa'] ?? [];
        $icons = $pwa['icons'] ?? [];
        $screenshots = $pwa['screenshots'] ?? [];
        $shortcuts = $pwa['shortcuts'] ?? [];

        // Хелпер для иконок
        $getIconUrl = function (string $filename, $customPath = null) use ($tenant, $tenantIconBase, $defaultIconBase) {
            // Если задан кастомный путь из настроек
            if ($customPath) {
                $tenantStoragePath = "tenants/{$tenant->id}/icons/{$customPath}";
                if (Storage::disk('public')->exists($tenantStoragePath)) {
                    return "{$tenantIconBase}/{$customPath}";
                }
            }

            // Иначе ищем по имени файла
            $tenantStoragePath = "tenants/{$tenant->id}/icons/{$filename}";
            if (Storage::disk('public')->exists($tenantStoragePath)) {
                return "{$tenantIconBase}/{$filename}";
            }

            return "{$defaultIconBase}/{$filename}";
        };

        // Хелпер для скриншотов
        $getScreenshotUrl = function (string $filename, $customPath = null) use ($tenant, $tenantScreenshotBase, $defaultScreenshotBase) {
            if ($customPath) {
                $tenantStoragePath = "tenants/{$tenant->id}/screenshots/{$customPath}";
                if (Storage::disk('public')->exists($tenantStoragePath)) {
                    return "{$tenantScreenshotBase}/{$customPath}";
                }
            }

            $tenantStoragePath = "tenants/{$tenant->id}/screenshots/{$filename}";
            if (Storage::disk('public')->exists($tenantStoragePath)) {
                return "{$tenantScreenshotBase}/{$filename}";
            }

            return "{$defaultScreenshotBase}/{$filename}";
        };

        // 🆕 Формируем список иконок
        $manifestIcons = [
            [
                "src" => $getIconUrl('icon-192x192.png', $icons['icon_192'] ?? null),
                "sizes" => "192x192",
                "type" => "image/png"
            ],
            [
                "src" => $getIconUrl('icon-512x512.png', $icons['icon_512'] ?? null),
                "sizes" => "512x512",
                "type" => "image/png"
            ],
            [
                "src" => $getIconUrl('icon-192x192-maskable.png', $icons['icon_192_maskable'] ?? null),
                "sizes" => "192x192",
                "type" => "image/png",
                "purpose" => "maskable"
            ],
            [
                "src" => $getIconUrl('icon-512x512-maskable.png', $icons['icon_512_maskable'] ?? null),
                "sizes" => "512x512",
                "type" => "image/png",
                "purpose" => "maskable"
            ]
        ];

        // 🆕 Формируем скриншоты
        $manifestScreenshots = [
            [
                "src" => $getScreenshotUrl('shop-mobile.png', $screenshots['mobile'] ?? null),
                "sizes" => "375x667",
                "type" => "image/png",
                "form_factor" => "narrow"
            ],
            [
                "src" => $getScreenshotUrl('shop-desktop.png', $screenshots['desktop'] ?? null),
                "sizes" => "1920x1080",
                "type" => "image/png",
                "form_factor" => "wide"
            ]
        ];

        // 🆕 Формируем шорткаты (только включённые)
        $manifestShortcuts = [];
        foreach ($shortcuts as $key => $shortcut) {
            if (!($shortcut['enabled'] ?? false)) continue;

            $manifestShortcuts[] = [
                "name" => $shortcut['name'],
                "short_name" => $shortcut['short_name'] ?? $shortcut['name'],
                "url" => $shortcut['url'],
                "icons" => [
                    [
                        "src" => $getIconUrl("shortcut-{$key}.png", $shortcut['icon'] ?? null),
                        "sizes" => "192x192",
                        "type" => "image/png"
                    ]
                ]
            ];
        }

        $manifest = [
            "id" => "/pwa/{$tenant->slug}/",
            "name" => $pwa['name'] ?? $tenant->name,
            "short_name" => $pwa['short_name'] ?? $tenant->short_name ?? $tenant->name,
            "start_url" => "/pwa/#/catalog?source=pwa",
            "scope" => "/pwa/",
            "display" => $pwa['display'] ?? 'standalone',
            "display_override" => ["standalone", "minimal-ui"],
            "orientation" => $pwa['orientation'] ?? 'portrait',
            "theme_color" => $pwa['theme_color'] ?? $tenant->theme_color ?? '#ff8a00',
            "background_color" => $pwa['background_color'] ?? $tenant->background_color ?? "#ffffff",
            "lang" => $pwa['lang'] ?? 'ru',
            "description" => $pwa['description'] ?? $tenant->description ?? "Официальное приложение {$tenant->name}",
            "categories" => $pwa['categories'] ?? ["shopping", "food", "business"],
            "protocol_handlers" => [
                ["protocol" => "mailto", "url" => "/pwa/compose?to=%s"],
                ["protocol" => "web+tel", "url" => "/pwa/call?number=%s"]
            ],
            "icons" => $manifestIcons,
            "screenshots" => $manifestScreenshots,
            "shortcuts" => $manifestShortcuts,
            "launch_handler" => ["client_mode" => "focus-existing"]
        ];

        return response()->json($manifest)
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Content-Type', 'application/manifest+json');
    }
}
