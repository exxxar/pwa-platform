<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Notifications\NewOrderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TenantAuthController extends Controller
{

    public function loginPage()
    {
        return Inertia::render('Tenant/Auth/Login', [
            'tenant' => tenant(),
            'user' => auth('tenant')->user(),
        ]);
    }

    public function registrationPage()
    {
        return Inertia::render('Tenant/Auth/Register', [
            'tenant' => tenant(),
            'user' => auth('tenant')->user(),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:tenant_users,phone',
            'password' => 'required|string|min:6',
        ]);

        $user = TenantUser::create([
            'tenant_id' => tenant()->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => bcrypt($data['password']),
        ]);

        Auth::guard('tenant')->login($user);

        return redirect('/');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('phone', 'password');

        if (Auth::guard('tenant')->attempt($credentials)) {
            return redirect()->intended('/'); // или куда нужно
        }

        return back()->withErrors(['phone' => 'Неверный телефон или пароль']);
    }

    public function logout()
    {
        Auth::guard('tenant')->logout();
        return response()->json(['success' => true]);
    }

    public function me()
    {
        $user = Auth::guard('tenant')->user();

        if ($user) {
            $user->load(['roles.permissions']);
        }

        return response()->json($user);
    }


    public function handlerShopLanding($tenant)
    {

        \Illuminate\Support\Facades\Session::put("tenant", $tenant ?? null);

        $tenantUser = Auth::guard('tenant')->user();

        $tenant = Tenant::where('slug', $tenant)->firstOrFail();
        Inertia::setRootView("shop-landing");
        return Inertia::render('ShopLanding', [
            'tenant' => $tenant,
            'tenant_user' => $tenantUser
        ]);

    }

    public function handlerAgent($tenant)
    {

        \Illuminate\Support\Facades\Session::put("tenant", $tenant ?? null);

        $tenantUser = Auth::guard('tenant')->user();

        $tenant = Tenant::where('slug', $tenant)->firstOrFail();
        Inertia::setRootView("mobile");
        return Inertia::render('AgentDashboard', [
            'tenant' => $tenant,
            'tenant_user' => $tenantUser
        ]);

    }

    public function handler($tenant)
    {


        \Illuminate\Support\Facades\Session::put("tenant", $tenant ?? null);

        $tenantUser = Auth::guard('tenant')->user();


        $tenant = Tenant::where('slug', $tenant)->firstOrFail();
        Inertia::setRootView("mobile");
        return Inertia::render('MobileMain', [
            'tenant' => $tenant,
            'tenant_user' => $tenantUser
        ]);

    }

    public function manifest($tenantSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        $tenantIconBase = "/storage/tenants/{$tenant->id}/icons";
        $tenantScreenshotBase = "/storage/tenants/{$tenant->id}/screenshots";

        $defaultIconBase = "/storage/defaults/icons";       // или просто "/icons"
        $defaultScreenshotBase = "/storage/defaults/screenshots"; // или "/screenshots"

        // 2. Хелпер-функция для проверки существования иконки
        $getIconUrl = function (string $filename) use ($tenant, $tenantIconBase, $defaultIconBase) {
            // Путь относительно storage/app/public для проверки (без /storage/ в начале)
            $tenantStoragePath = "tenants/{$tenant->id}/icons/{$filename}";

            if (Storage::disk('public')->exists($tenantStoragePath)) {
                return "{$tenantIconBase}/{$filename}";
            }

            // Если у тенанта нет файла, отдаем дефолтный
            return "{$defaultIconBase}/{$filename}";
        };

        // 3. Хелпер-функция для проверки существования скриншота
        $getScreenshotUrl = function (string $filename) use ($tenant, $tenantScreenshotBase, $defaultScreenshotBase) {
            $tenantStoragePath = "tenants/{$tenant->id}/screenshots/{$filename}";

            if (Storage::disk('public')->exists($tenantStoragePath)) {
                return "{$tenantScreenshotBase}/{$filename}";
            }

            return "{$defaultScreenshotBase}/{$filename}";
        };

        // 4. Формируем манифест, используя наши умные хелперы
        $manifest = [
            "id" => "/pwa/{$tenant->slug}/",
            "name" => $tenant->name,
            "short_name" => $tenant->short_name ?? $tenant->name,

            "start_url" => "/pwa/#/menu?source=pwa",
            "scope" => "/pwa/",

            "display" => "standalone",
            "display_override" => ["standalone", "minimal-ui"],
            "orientation" => "any",
            "theme_color" => $tenant->theme_color ?? '#ff8a00',
            "background_color" => $tenant->background_color ?? "#ffffff",
            "lang" => "ru",
            "description" => $tenant->description ?? "Официальное приложение {$tenant->name}",
            "categories" => ["shopping", "food", "business"],

            "icons" => [
                [
                    "src" => $getIconUrl('icon-192x192.png'),
                    "sizes" => "192x192",
                    "type" => "image/png"
                ],
                [
                    "src" => $getIconUrl('icon-512x512.png'),
                    "sizes" => "512x512",
                    "type" => "image/png"
                ],
                [
                    "src" => $getIconUrl('icon-192x192-maskable.png'),
                    "sizes" => "192x192",
                    "type" => "image/png",
                    "purpose" => "maskable"
                ],
                [
                    "src" => $getIconUrl('icon-512x512-maskable.png'),
                    "sizes" => "512x512",
                    "type" => "image/png",
                    "purpose" => "maskable"
                ]
            ],

            "screenshots" => [
                [
                    "src" => $getScreenshotUrl('shop-mobile.png'),
                    "sizes" => "375x667",
                    "type" => "image/png",
                    "form_factor" => "narrow"
                ]
            ],

            // Shortcuts с хэш-роутингом
            "shortcuts" => [
                [
                    "name" => "Меню",
                    "short_name" => "Меню",
                    "url" => "/pwa/#/menu",
                    "icons" => [
                        ["src" => $getIconUrl('shortcut-menu.png'), "sizes" => "192x192", "type" => "image/png"]
                    ]
                ],
                [
                    "name" => "Корзина",
                    "short_name" => "Корзина",
                    "url" => "/pwa/#/cart",
                    "icons" => [
                        ["src" => $getIconUrl('shortcut-cart.png'), "sizes" => "192x192", "type" => "image/png"]
                    ]
                ],
                [
                    "name" => "Кэшбэк",
                    "short_name" => "Кэшбэк",
                    "url" => "/pwa/#/cashback",
                    "icons" => [
                        ["src" => $getIconUrl('shortcut-cashback.png'), "sizes" => "192x192", "type" => "image/png"]
                    ]
                ],
                [
                    "name" => "Колесо",
                    "short_name" => "Колесо",
                    "url" => "/pwa/#/wheel-classic",
                    "icons" => [
                        ["src" => $getIconUrl('shortcut-wheel.png'), "sizes" => "192x192", "type" => "image/png"]
                    ]
                ]
            ],

            "launch_handler" => [
                "client_mode" => "focus-existing"
            ]
        ];

        // 5. Отдаем с правильными заголовками и кэшированием
        return response()->json($manifest)
            ->header('Cache-Control', 'public, max-age=3600') // Кэшируем на 1 час
            ->header('Content-Type', 'application/manifest+json');
    }
}
