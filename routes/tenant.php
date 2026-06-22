<?php

use App\Http\Controllers\BasketController;
use App\Http\Controllers\BitrixController;
use App\Http\Controllers\CdekController;
use App\Http\Controllers\IikoController;
use App\Http\Controllers\PartnersController;
use App\Http\Controllers\ProductCollectionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\TenantSocialAuthController;
use App\Http\Controllers\Tenant\TenantTapLinkController;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Notifications\NewOrderNotification;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Tenant\TenantPasswordController;
use App\Http\Controllers\Tenant\TenantEmailVerificationController;
use Inertia\Inertia;
use Jenssegers\Agent\Agent;

$routes = function () {

    Route::get('/', function (Request $request) {
        $agent = new Agent();

        if ($agent->isMobile()) {
            return redirect()->to('/pwa', 301, [], false);
        }

        return view("landing");
    });

    Route::get('/job', function (Request $request) {

        $agent = new Agent();

        if ($agent->isMobile()) {
            return redirect()->to('/agent', 301, [], false);
        }

        return view("job-landing");
    });

    Route::get('/shop/{any?}', [TenantAuthController::class, 'handlerShopLanding'])
        ->where('any', '.*');

    Route::get('/agent/{any?}', [TenantAuthController::class, 'handlerAgent'])
        ->where('any', '.*');

    Route::get('/pwa/{any?}', [TenantAuthController::class, 'handler'])
        ->where('any', '.*');

    Route::get('/manifest.json', [TenantAuthController::class, 'manifest']);

    Route::get('/sw.js', function () {
        $path = public_path('sw.js');

        if (!file_exists($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/javascript',
            'Service-Worker-Allowed' => '/pwa/',
            'Cache-Control' => 'no-cache, no-store, must-revalidate', // Важно! Чтобы обновления подхватывались
        ]);
    });


    Route::post('/push/subscribe', function (Request $request) {
        $request->validate(['endpoint' => 'required', 'keys' => 'required']);

        $tenantUser = TenantUser::find(Auth::guard('tenant')->user()->id);
        $tenantUser->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth')
        );

        return response()->json(['success' => true]);
    });

    Route::post('/push/unsubscribe', function (Request $request) {

        $tenantUser = TenantUser::find(Auth::guard('tenant')->user()->id);
        $tenantUser->deletePushSubscription($request->input('endpoint'));
        return response()->json(['success' => true]);
    });

    Route::get("/test-sub", function (){
        $tenantUser = Auth::guard('tenant')->user();

        $tenantUserTMP = TenantUser::find($tenantUser->id);
        $tenantUserTMP->notify(new NewOrderNotification('12345'));

        return "ok";
    });

    Route::get("/tables-qr", [ProductController::class, "generateTablesQR"]);


    Route::get('/taplink', [TenantTapLinkController::class, 'index']);

    Route::prefix("tables")
        ->controller(TableController::class)
        ->group(function () {
            Route::post('/current', "currentTable");
            Route::post('/table-data', "loadTableData");
            Route::post('/waiter-tables', "waiterTableList");
            Route::post('/close-table', "closeTable");
            Route::post('/table-pay', "tablePay");
            Route::post('/send-order-to-my-chat', "sendOrderToMyChat");
            Route::post('/change-table-waiter', "changeTableWaiter");
            Route::post('/accept-table-order', "changeBasketStatus");
            Route::post('/request-approve-table', "requestApproveTable");
            Route::post('/store-additional-service', "storeAdditionalService");
            Route::post('/self-checkout', "selfCheckout");
            Route::post('/approved-self-basket', "approvedSelfBasket");
            Route::post('/call-waiter', "callWaiter");
            Route::post('/all-orders', "getAllTableOrders");
            Route::post('/nearest-booking-list', "nearestBookingList");
            Route::post('/my-upcoming-bookings', "myUpcomingBookings");
            Route::post('/booking-list', "bookingList");
            Route::post('/book-table', "bookATable");
            Route::post('/export-nearest-bookings', "exportNearestBookings");
            Route::delete('/cancel-booking/{bookingId}', "cancelBooking");
        });


    Route::prefix("addresses")
        ->controller(\App\Http\Controllers\LocationController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::delete('/{id}', 'destroy');
            Route::post('/{id}/default', 'setDefault');
        });


    Route::prefix("dialogs")
        ->controller(\App\Http\Controllers\TenantDialogController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}/messages', 'messages');
            Route::post('/{id}/messages', 'send');
        });
    Route::prefix("basket")
        ->controller(BasketController::class)
        ->group(function () {
            Route::post('/', "loadProductsInBasket");
            Route::post('/checkout', "checkout");
            Route::post("/get-delivery-price-new", [ProductController::class, "getDeliveryPriceNew"]);
            Route::post('/checkout-link', "checkoutLink");
            Route::post("/use-wheel-of-fortune-prize", "useWheelOfFortunePrize");
            Route::post('/increment/{id}', "incrementItem");
            Route::post('/decrement/{id}', "decrementItem");
            Route::post('/inc-product', "incProductInBasket");
            Route::post('/comment-product', "commentProductInBasket");
            Route::post('/dec-product', "decProductInBasket");
            Route::post('/inc-collection', "incCollectionInBasket");
            Route::post('/dec-collection', "decCollectionInBasket");
            Route::delete('/clear', "clearBasket");
            Route::delete('/remove/{id}', "removeBasketItem");

        });


    Route::prefix("stories")
        ->group(function () {
            Route::get("/", [StoryController::class, "index"]); // Получить список историй
            Route::get("/{id}", [StoryController::class, "show"]); // Получить историю по ID
            Route::post("/", [StoryController::class, "store"]); // Создать или обновить историю
            Route::delete("/{id}", [StoryController::class, "destroy"]); // Удалить историю
        });

    Route::prefix("shop")
        ->group(function () {
            Route::prefix("orders")
                ->group(function () {
                    Route::post("/", [ProductController::class, "getOrders"]);
                    Route::post("/send-sbp-invoice", [ProductController::class, "sendSBPInvoice"]);
                    Route::post("/all", [ProductController::class, "getAllOrders"]);
                    Route::post("/repeat-order", [ProductController::class, "repeatOrder"]);
                    Route::post("/decline-order", [ProductController::class, "declineOrder"]);
                    Route::post("/change-order-status", [ProductController::class, "changeStatusOrder"]);
                    Route::post("/get-order-by-id", [ProductController::class, "loadOrderById"]);
                    Route::post("/add-cashback-to-order", [ProductController::class, "addCashBackToOrder"]);

                    Route::post("/get-delivery-price", [ProductController::class, "getDeliveryPrice"]);
                });

            Route::prefix("reviews")
                ->group(function () {
                    Route::post("/", [ProductController::class, "getReviews"]);
                    Route::post("/by-product-id", [ProductController::class, "getReviewsByProductId"]);
                    Route::post("/store-review", [ProductController::class, "storeReview"]);
                    Route::post("/notify-user", [ProductController::class, "notifyUser"]);
                });

            Route::prefix("products")
                ->group(function () {

                    Route::post("/by-category", [ProductController::class, "listByCategories"]);
                    Route::post("/more-by-category", [ProductController::class, "loadMoreProductsByCategories"]);
                    Route::post("/store-category", [ProductController::class, "storeCategory"]);
                    Route::post("/fav-list", [ProductController::class, "getFavList"]);
                    Route::post("/toggle-favorite", [ProductController::class, "toggleProductInFavorites"]);
                    Route::post("/export-all-products", [ProductController::class, "exportAllProducts"]);
                    Route::post("/load-recommended-products", [ProductController::class, "loadRecommendedProducts"]);
                    Route::post("/by-ids", [ProductController::class, "getProductsByIds"]);
                    Route::post("/random", [ProductController::class, "randomProducts"]);
                    Route::post("/categories", [ProductController::class, "getCategories"]);
                    Route::post("/add-product", [ProductController::class, "saveProduct"]);
                    Route::post("/change-recommendation-status", [ProductController::class, "changeRecommendationStatus"]);
                    Route::post("/categories/recommendation-status", [ProductController::class, "changeCategoryRecommendationStatus"]);
                    Route::post("/remove-all-products", [ProductController::class, "removeAllProducts"]);
                    Route::delete("/remove-category/{categoryId}", [ProductController::class, "removeCategoryId"]);
                    Route::post("/categories/status/{id}", [ProductController::class, "changeCategoryStatus"]);
                    Route::post("/add-category", [ProductController::class, "storeCategory"]);
                    Route::post("/in-category", [ProductController::class, "getProductsInCategory"]);
                    Route::post("/category/{productId}", [ProductController::class, "getCategory"]);
                    Route::post("/{productId}", [ProductController::class, "getProduct"]);
                    Route::post("/restore-product/{productId}", [ProductController::class, "restore"]);
                    Route::post("/stop-list-product/{productId}", [ProductController::class, "stopList"]);
                    Route::post("/", [ProductController::class, "index"]);
                    Route::delete("/{productId}", [ProductController::class, "destroy"]);
                });
        });

    Route::prefix("iiko")
        ->controller(IikoController::class)
        ->group(function () {
            Route::post('/', "index");
            Route::post('/token', "getToken");
            Route::post('/organizations', "getOrganizations");
            Route::post('/terminals', "getTerminals");
            Route::post('/menu', "getMenu");
            Route::post('/products', "getProducts");
            Route::post('/store-products', "storeProducts");
            Route::post('/store', "store");
        });

    Route::prefix("bitrix")
        ->controller(BitrixController::class)
        ->group(function () {
            Route::post('/load-connections', "index");
            Route::post('/store', "store");
            Route::post('/check', "check");
            Route::delete('/remove/{id}', "remove");
        });

    Route::prefix("cdek")
        ->controller(CdekController::class)
        ->group(function () {
            Route::post('/store', "store");
            Route::post('/calc-basket-tariff', "calcBasketTariff");
            Route::post('/make-order', "makeOrder");
            Route::post('/get-cities', "getCities");
            Route::post('/get-regions', "getRegions");
            Route::post('/get-offices', "getOffices");
            Route::post('/calc-tariff', "calcTariff");
            Route::post('/calc-tariff-by-code/{code}', "calcTariffByCode");
        });;


    Route::prefix("partners")
        ->controller(PartnersController::class)
        ->group(function () {
            Route::post("/", "index");
            Route::post("/store", "store");
            Route::post("/toggle-favorite", "togglePartnersInFavorites");
            Route::post("/update-settings", "updateSettings");
            Route::post("/update-active-status", "updateActiveStatus");
            Route::post("/change-status", "changeStatus");
            Route::post("/partners-categories", "partnersCategories");
            Route::post("/update", "update");
            Route::post("/update-self", "updateSelf");
            Route::post("/remove/{partnerId}", "destroy");
        });


    Route::prefix("product-collections")
        ->controller(ProductCollectionController::class)
        ->group(function () {
            Route::post("/", "index");
            Route::post("/global", "globalList");
            Route::post("/store", "store");
            Route::post("/remove/{collectionId}", "destroy");
            Route::post("/duplicate/{collectionId}", "duplicate");
        });
};

if (env("APP_DEBUG") ?? false) {
    Route::domain('localhost')->group($routes);
    Route::domain('127.0.0.1')->group($routes);
}


Route::domain('{tenant}.mypwa.ru')->group($routes);
Route::domain('{tenant}.pwa-platform.test')->group($routes);




Route::get('/auth/vk/redirect', [TenantSocialAuthController::class, 'redirect']);
Route::get('/auth/vk/callback', [TenantSocialAuthController::class, 'callback']);

Route::post('/login', [TenantAuthController::class, 'login']);
Route::post('/logout', [TenantAuthController::class, 'logout']);
Route::get('/login', [TenantAuthController::class, 'loginPage']);
Route::get('/register', [TenantAuthController::class, 'registrationPage']);

Route::get('/me', [TenantAuthController::class, 'me']);


Route::middleware(['tenant.access'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::get('/orders', [OrderController::class, 'index']);
});


// Страницы
Route::get('/confirm-password', [TenantPasswordController::class, 'confirmPasswordPage'])
    ->middleware('tenant.access')
    ->name('tenant.password.confirm');

Route::get('/forgot-password', [TenantPasswordController::class, 'forgotPasswordPage'])
    ->name('tenant.password.request');

Route::get('/reset-password/{token}', [TenantPasswordController::class, 'resetPasswordPage'])
    ->name('tenant.password.reset');

Route::get('/verify-email', [TenantEmailVerificationController::class, 'verifyEmailPage'])
    ->middleware('tenant.access')
    ->name('tenant.verification.notice');

// Действия
Route::post('/confirm-password', [TenantPasswordController::class, 'confirmPassword'])
    ->middleware('tenant.access');

Route::post('/forgot-password', [TenantPasswordController::class, 'sendResetLink']);

Route::post('/reset-password', [TenantPasswordController::class, 'resetPassword']);

Route::post('/email/resend', [TenantEmailVerificationController::class, 'resend'])
    ->middleware('tenant.access')
    ->name('tenant.verification.resend');

Route::get('/email/verify/{id}/{hash}', [TenantEmailVerificationController::class, 'verify'])
    ->middleware('tenant.access')
    ->name('tenant.verification.verify');
