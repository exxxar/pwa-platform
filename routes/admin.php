<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ==========================================
// 🌐 AUTH
// ==========================================

Route::prefix('admin-panel')->group(function () {

    Route::get('/login', function () {
        Inertia::setRootView("app");
        return Inertia::render('Auth/Login');
    })->name('admin-panel.login');

    // Публичные маршруты (без аутентификации)
    Route::post('/login', [\App\Http\Controllers\Admin\Auth\LoginController::class, '__invoke'])
        ->name('admin-panel.login');

    // Защищенные маршруты (требуют аутентификации)
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::get('/', function () {

            $user = \Illuminate\Support\Facades\Auth::user();

            Inertia::setRootView("app");
            return Inertia::render('Dashboard/Index', [

            ]);
        })->name('admin-panel.dashboard');

        Route::get('/user', function (Request $request) {
            $user = $request->user();
            $user->load('roles.permissions');
            return response()->json($user);
        });

        // Logout
        Route::post('/logout', [\App\Http\Controllers\Admin\Auth\LogoutController::class, '__invoke'])
            ->name('admin-panel.logout');

        // ==========================================
        // 🌐 GLOBAL - Управление платформой
        // ==========================================

        // --- Тенанты ---
        Route::prefix('tenants')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Global\TenantController::class, 'index'])
                ->name('admin-panel.tenants.index');

            Route::post('/', [\App\Http\Controllers\Admin\Global\TenantController::class, 'store'])
                ->name('admin-panel.tenants.store');

            Route::get('/{tenant}', [\App\Http\Controllers\Admin\Global\TenantController::class, 'show'])
                ->name('admin-panel.tenants.show');

            Route::put('/{tenant}', [\App\Http\Controllers\Admin\Global\TenantController::class, 'update'])
                ->name('admin-panel.tenants.update');

            Route::delete('/{tenant}', [\App\Http\Controllers\Admin\Global\TenantController::class, 'destroy'])
                ->name('admin-panel.tenants.destroy');

            // Кастомные действия
            Route::patch('/{tenant}/toggle-status', [\App\Http\Controllers\Admin\Global\TenantController::class, 'toggleStatus'])
                ->name('admin-panel.tenants.toggle-status');

            Route::patch('/{tenant}/update-balance', [\App\Http\Controllers\Admin\Global\TenantController::class, 'updateBalance'])
                ->name('admin-panel.tenants.update-balance');
        });

        // --- Глобальные админы ---
        Route::prefix('admin-users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Global\AdminUserController::class, 'index'])
                ->name('admin-panel.admin-users.index');

            Route::post('/', [\App\Http\Controllers\Admin\Global\AdminUserController::class, 'store'])
                ->name('admin-panel.admin-users.store');

            Route::get('/{user}', [\App\Http\Controllers\Admin\Global\AdminUserController::class, 'show'])
                ->name('admin-panel.admin-users.show');

            Route::put('/{user}', [\App\Http\Controllers\Admin\Global\AdminUserController::class, 'update'])
                ->name('admin-panel.admin-users.update');

            Route::delete('/{user}', [\App\Http\Controllers\Admin\Global\AdminUserController::class, 'destroy'])
                ->name('admin-panel.admin-users.destroy');
        });

        // --- Глобальные роли ---
        Route::prefix('roles')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Global\RoleController::class, 'index'])
                ->name('admin-panel.roles.index');

            Route::post('/', [\App\Http\Controllers\Admin\Global\RoleController::class, 'store'])
                ->name('admin-panel.roles.store');

            Route::get('/{role}', [\App\Http\Controllers\Admin\Global\RoleController::class, 'show'])
                ->name('admin-panel.roles.show');

            Route::put('/{role}', [\App\Http\Controllers\Admin\Global\RoleController::class, 'update'])
                ->name('admin-panel.roles.update');

            Route::delete('/{role}', [\App\Http\Controllers\Admin\Global\RoleController::class, 'destroy'])
                ->name('admin-panel.roles.destroy');
        });

        // --- Глобальные разрешения ---
        Route::prefix('permissions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Global\PermissionController::class, 'index'])
                ->name('admin-panel.permissions.index');

            Route::post('/', [\App\Http\Controllers\Admin\Global\PermissionController::class, 'store'])
                ->name('admin-panel.permissions.store');

            Route::get('/{permission}', [\App\Http\Controllers\Admin\Global\PermissionController::class, 'show'])
                ->name('admin-panel.permissions.show');

            Route::put('/{permission}', [\App\Http\Controllers\Admin\Global\PermissionController::class, 'update'])
                ->name('admin-panel.permissions.update');

            Route::delete('/{permission}', [\App\Http\Controllers\Admin\Global\PermissionController::class, 'destroy'])
                ->name('admin-panel.permissions.destroy');
        });

        // --- Системные настройки ---
        Route::prefix('settings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Global\SystemSettingController::class, 'index'])
                ->name('admin-panel.settings.index');

            Route::put('/', [\App\Http\Controllers\Admin\Global\SystemSettingController::class, 'update'])
                ->name('admin-panel.settings.update');

            Route::post('/clear-cache', [\App\Http\Controllers\Admin\Global\SystemSettingController::class, 'clearCache'])
                ->name('admin-panel.settings.clear-cache');
        });

        // ==========================================
        // 🏢 TENANT DATA - Управление данными тенантов
        // ==========================================

        // --- Пользователи тенантов ---
        Route::prefix('tenant-users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TenantData\TenantUserController::class, 'index'])
                ->name('admin-panel.tenant-users.index');

            Route::get('/{user}', [\App\Http\Controllers\Admin\TenantData\TenantUserController::class, 'show'])
                ->name('admin-panel.tenant-users.show');

            Route::put('/{user}', [\App\Http\Controllers\Admin\TenantData\TenantUserController::class, 'update'])
                ->name('admin-panel.tenant-users.update');

            Route::delete('/{user}', [\App\Http\Controllers\Admin\TenantData\TenantUserController::class, 'destroy'])
                ->name('admin-panel.tenant-users.destroy');

            // Кастомные действия
            Route::patch('/{user}/toggle-block', [\App\Http\Controllers\Admin\TenantData\TenantUserController::class, 'toggleBlock'])
                ->name('admin-panel.tenant-users.toggle-block');

            Route::post('/{user}/grant-vip', [\App\Http\Controllers\Admin\TenantData\TenantUserController::class, 'grantVip'])
                ->name('admin-panel.tenant-users.grant-vip');

            Route::post('/{user}/revoke-vip', [\App\Http\Controllers\Admin\TenantData\TenantUserController::class, 'revokeVip'])
                ->name('admin-panel.tenant-users.revoke-vip');
        });

        // --- Заказы ---
        Route::prefix('orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TenantData\OrderController::class, 'index'])
                ->name('admin-panel.orders.index');

            Route::get('/{order}', [\App\Http\Controllers\Admin\TenantData\OrderController::class, 'show'])
                ->name('admin-panel.orders.show');

            Route::patch('/{order}/update-status', [\App\Http\Controllers\Admin\TenantData\OrderController::class, 'updateStatus'])
                ->name('admin-panel.orders.update-status');
        });

        // --- Товары ---
        Route::prefix('products')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TenantData\ProductController::class, 'index'])
                ->name('admin-panel.products.index');

            Route::post('/', [\App\Http\Controllers\Admin\TenantData\ProductController::class, 'store'])
                ->name('admin-panel.products.store');

            Route::get('/{product}', [\App\Http\Controllers\Admin\TenantData\ProductController::class, 'show'])
                ->name('admin-panel.products.show');

            Route::put('/{product}', [\App\Http\Controllers\Admin\TenantData\ProductController::class, 'update'])
                ->name('admin-panel.products.update');

            Route::delete('/{product}', [\App\Http\Controllers\Admin\TenantData\ProductController::class, 'destroy'])
                ->name('admin-panel.products.destroy');

            // Кастомные действия
            Route::patch('/{product}/toggle-stop-list', [\App\Http\Controllers\Admin\TenantData\ProductController::class, 'toggleStopList'])
                ->name('admin-panel.products.toggle-stop-list');

            Route::patch('/{product}/toggle-active', [\App\Http\Controllers\Admin\TenantData\ProductController::class, 'toggleActive'])
                ->name('admin-panel.products.toggle-active');
        });

        // --- Транзакции ---
        Route::prefix('transactions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TenantData\TransactionController::class, 'index'])
                ->name('admin-panel.transactions.index');

            Route::get('/{transaction}', [\App\Http\Controllers\Admin\TenantData\TransactionController::class, 'show'])
                ->name('admin-panel.transactions.show');
        });

        // --- Диалоги ---
        Route::prefix('dialogs')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TenantData\DialogController::class, 'index'])
                ->name('admin-panel.dialogs.index');

            Route::get('/{dialog}', [\App\Http\Controllers\Admin\TenantData\DialogController::class, 'show'])
                ->name('admin-panel.dialogs.show');

            Route::post('/{dialog}/reply', [\App\Http\Controllers\Admin\TenantData\DialogController::class, 'reply'])
                ->name('admin-panel.dialogs.reply');

            Route::patch('/{dialog}/close', [\App\Http\Controllers\Admin\TenantData\DialogController::class, 'close'])
                ->name('admin-panel.dialogs.close');

            Route::patch('/{dialog}/mark-as-read', [\App\Http\Controllers\Admin\TenantData\DialogController::class, 'markAsRead'])
                ->name('admin-panel.dialogs.mark-as-read');
        });

        // --- Кэшбэк ---
        Route::prefix('cashback')->group(function () {
            Route::get('/history', [\App\Http\Controllers\Admin\TenantData\CashbackController::class, 'history'])
                ->name('admin-panel.cashback.history');

            Route::post('/manually-adjust', [\App\Http\Controllers\Admin\TenantData\CashbackController::class, 'manuallyAdjust'])
                ->name('admin-panel.cashback.manually-adjust');
        });

        // --- Рефералы ---
        Route::prefix('referrals')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TenantData\ReferralController::class, 'index'])
                ->name('admin-panel.referrals.index');

            Route::get('/user/{user}/chain', [\App\Http\Controllers\Admin\TenantData\ReferralController::class, 'showChain'])
                ->name('admin-panel.referrals.show-chain');

            Route::get('/stats', [\App\Http\Controllers\Admin\TenantData\ReferralController::class, 'stats'])
                ->name('admin-panel.referrals.stats');

            Route::patch('/{referral}/manually-adjust', [\App\Http\Controllers\Admin\TenantData\ReferralController::class, 'manuallyAdjust'])
                ->name('admin-panel.referrals.manually-adjust');
        });

        // ==========================================
        // 📊 REPORTS & EXPORTS
        // ==========================================

        // --- Отчеты ---
        Route::prefix('reports')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Admin\Global\ReportController::class, 'dashboard'])
                ->name('admin-panel.reports.dashboard');

            Route::get('/tenant/{tenant}', [\App\Http\Controllers\Admin\Global\ReportController::class, 'tenantStats'])
                ->name('admin-panel.reports.tenant-stats');

            Route::get('/user-registrations', [\App\Http\Controllers\Admin\Global\ReportController::class, 'userRegistrationsChart'])
                ->name('admin-panel.reports.user-registrations');

            Route::get('/orders-chart', [\App\Http\Controllers\Admin\Global\ReportController::class, 'ordersChart'])
                ->name('admin-panel.reports.orders-chart');

            Route::get('/top-tenants', [\App\Http\Controllers\Admin\Global\ReportController::class, 'topTenantsByRevenue'])
                ->name('admin-panel.reports.top-tenants');

            Route::get('/top-users', [\App\Http\Controllers\Admin\Global\ReportController::class, 'topUsersByOrders'])
                ->name('admin-panel.reports.top-users');
        });

        // --- Экспорт ---
        Route::prefix('exports')->group(function () {
            Route::post('/users', [\App\Http\Controllers\Admin\Global\ExportController::class, 'exportUsers'])
                ->name('admin-panel.exports.users');

            Route::post('/orders', [\App\Http\Controllers\Admin\Global\ExportController::class, 'exportOrders'])
                ->name('admin-panel.exports.orders');

            Route::post('/transactions', [\App\Http\Controllers\Admin\Global\ExportController::class, 'exportTransactions'])
                ->name('admin-panel.exports.transactions');

            Route::post('/tenants', [\App\Http\Controllers\Admin\Global\ExportController::class, 'exportTenants'])
                ->name('admin-panel.exports.tenants');
        });
    });
});
