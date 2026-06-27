<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Notifications\NewOrderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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
        $baseUrl = url('/');

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
                return "{$tenantScreenshotBase}/{$filename}";        }

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
            "start_url" => "/pwa/#/menu?source=pwa",
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
