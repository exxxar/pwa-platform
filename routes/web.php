<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicLandingController;
use App\Http\Controllers\Tenant\PaymentCallbackController;
use App\Services\Tenants\PricingService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::view('/maintenance', 'pages.maintenance')
    ->name('maintenance');

Route::get('/landing', [PublicLandingController::class, 'index'])
    ->name('public.landing');

Route::prefix('payment-products-notify')->group(function () {
    // Универсальный маршрут для всех банков
    // Примеры: /payment-products-notify/tinkoff/my-shop, /payment-products-notify/sber/123
    Route::any('/{bank}/{tenant}', [PaymentCallbackController::class, 'handleProductsCallback'])
        ->name('payment.products.callback');
});


// ==========================================
// МАРШРУТЫ ДЛЯ ОПЛАТЫ УСЛУГ СЕРВИСА
// ==========================================
Route::prefix('payment-service-notify')->group(function () {
    Route::any('/tinkoff', [PaymentCallbackController::class, 'tinkoffServiceCallback'])
        ->name('payment.tinkoff.service.callback');

    // Можно добавить другие банки для услуг в будущем:
    // Route::any('/sber', [PaymentCallbackController::class, 'sberServiceCallback'])
    //     ->name('payment.sber.service.callback');
});


Route::get('/pricing', function () {
    return response()->json([
        'data' => PricingService::getActivePlans(),
        'display' => PricingService::getDisplaySettings(),
    ])->header('Cache-Control', 'public, max-age=300');
});

/*Route::get('/', function () {
    Inertia::setRootView("app");

    return Inertia::render('Welcome', [
        'canLogin' => true,//Route::has('login'),
        'canRegister' => true, //Route::has('register'),
        'laravelVersion' => '1',//Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});*/

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

require __DIR__. "/test.php";

