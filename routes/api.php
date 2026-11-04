<?php

use App\Http\Controllers\Api\DeliveryCalculationController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantExportController;
use App\Http\Controllers\Api\V1\TenantExportProductsController;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DesktopDeliverymanController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('desktop')->group(function () {
    Route::post('/auth/login', [DesktopDeliverymanController::class, 'login'])->middleware('throttle:8,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [DesktopDeliverymanController::class, 'me']);
        Route::post('/auth/logout', [DesktopDeliverymanController::class, 'logout']);
        Route::get('/dashboard', [DesktopDeliverymanController::class, 'dashboard']);
        Route::patch('/status', [DesktopDeliverymanController::class, 'toggleStatus']);
        Route::get('/orders/available', [DesktopDeliverymanController::class, 'availableOrders']);
        Route::get('/orders/active', [DesktopDeliverymanController::class, 'activeOrders']);
        Route::get('/orders/completed', [DesktopDeliverymanController::class, 'completedOrders']);
        Route::post('/orders/{id}/accept', [DesktopDeliverymanController::class, 'acceptOrder']);
        Route::patch('/orders/{id}/status', [DesktopDeliverymanController::class, 'changeStatus']);
        Route::post('/orders/{id}/confirm-delivery', [DesktopDeliverymanController::class, 'confirmDelivery']);
        Route::post('/orders/{id}/payment-link', [DesktopDeliverymanController::class, 'requestDeliveryPayment']);
        Route::post('/delivery-calculator/shops/{shopId}/calculate', [DeliveryCalculationController::class, 'calculate']);
        Route::post('/shops/{id}/payment-link', [DesktopDeliverymanController::class, 'generateShopPaymentLink']);
        Route::put('/orders/{id}/details', [DesktopDeliverymanController::class, 'updateOrderDetails']);
        Route::post('/location', [DesktopDeliverymanController::class, 'updateLocation']);
        Route::get('/settings', [DesktopDeliverymanController::class, 'getSettings']);
        Route::put('/settings', [DesktopDeliverymanController::class, 'saveSettings']);
        Route::get('/shops', [DesktopDeliverymanController::class, 'getShops']);
        Route::put('/shops', [DesktopDeliverymanController::class, 'saveShops']);
        Route::get('/dialogs/{dialogId}/messages', [DesktopDeliverymanController::class, 'getDialogMessages']);
        Route::post('/dialogs/{dialogId}/messages', [DesktopDeliverymanController::class, 'sendDialogMessage']);
    });
});

Route::get('/widget/products', function (Request $request) {
    return response()->json([
        [
            'id' => 1,
            'name' => 'Тестовый товар',
            'price' => '1 500 ₽',
            'image' => 'https://via.placeholder.com/300x200',
            'link' => 'http://localhost:8000/product/1'
        ]
    ]);
});

Route::post('/v1/tenants', [TenantController::class, 'create']);
Route::get('/v1/tenants/export', TenantExportController::class);
Route::get('/v1/tenants/{id}/products/export', TenantExportProductsController::class);
