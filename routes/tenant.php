<?php

use App\Http\Controllers\Admin\AdminOrderController;

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
use App\Http\Controllers\Tenant\AchievementAdminController;
use App\Http\Controllers\Tenant\AchievementController;
use App\Http\Controllers\Tenant\CashBackController;
use App\Http\Controllers\Tenant\ClientsController;
use App\Http\Controllers\Tenant\CoffeeController;
use App\Http\Controllers\Tenant\CollectionController;
use App\Http\Controllers\Tenant\FavoriteController;
use App\Http\Controllers\Tenant\FeedbackController;
use App\Http\Controllers\Tenant\FriendsController;
use App\Http\Controllers\Tenant\PromoCodeController;
use App\Http\Controllers\Tenant\RolesController;
use App\Http\Controllers\Tenant\StatisticController;
use App\Http\Controllers\Tenant\TransactionAdminController;
use App\Http\Controllers\Tenant\UsersController;
use App\Http\Controllers\Tenant\BroadcastController;
use App\Http\Controllers\Tenant\OrderController;
use App\Http\Controllers\Tenant\ReferralController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\TenantPwaController;
use App\Http\Controllers\Tenant\TenantSocialAuthController;
use App\Http\Controllers\Tenant\TenantTapLinkController;
use App\Http\Controllers\Tenant\WebhookReceiverController;
use App\Http\Controllers\TenantDialogController;
use App\Http\Controllers\TenantSettingsController;
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

Route::domain('mypwa.ru')->group(function(){
    Route::view("/", "landing-2");
});

function routes() {

    Route::get('/app-version', function () {
        $version = config('app.version', '1.0.0');
        return response()->json([
            'version' => $version,
            'force_update' => false,
        ]);
    })->middleware('throttle:60,1');

    Route::get('/', function (Request $request) {
        $agent = new Agent();
        if ($agent->isMobile()) {
            return redirect('/pwa', 301);
        }
        $tenant = $request->tenant;
        \Illuminate\Support\Facades\Session::put("tenant", $tenant->name);
        $tenantUser = Auth::guard('tenant')->user();
        Inertia::setRootView("shop-landing");
        return Inertia::render('ShopLanding', [
            'tenant' => $tenant,
            'tenant_user' => $tenantUser
        ]);
    })->name("home");

    Route::get('/taplink', [TenantTapLinkController::class, 'index']);
    Route::get('/auth/login', [TenantAuthController::class, 'loginPage']);
    Route::get('/auth/register', [TenantAuthController::class, 'registrationPage']);

    Route::any('/webhook', [WebhookReceiverController::class, 'handle'])
        ->name('webhook.workspace');

    Route::get('/l-shop/{any?}', [TenantAuthController::class, 'handlerShopLanding'])
        ->where('any', '.*')
        ->name("shop.landing");

    Route::get('/pwa/{any?}', [TenantAuthController::class, 'handler'])
        ->where('any', '.*');

    Route::get('/manifest.json', [TenantAuthController::class, 'manifest']);
    Route::get('/sw.js', [TenantAuthController::class, 'serviceWorker']);

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

    Route::get("/test-sub", function () {
        $tenantUser = Auth::guard('tenant')->user();
        $tenantUserTMP = TenantUser::find($tenantUser->id);
        $tenantUserTMP->notify(new NewOrderNotification('12345'));
        return "ok";
    });

    Route::get("/tables-qr", [ProductController::class, "generateTablesQR"]);

    Route::prefix('profile')->middleware(['auth:tenant'])->group(function () {
        Route::get('/', [ProfileController::class, 'index']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
        Route::post('/avatar', [ProfileController::class, 'updateAvatar']);
    });

    Route::prefix('favorites')->middleware(['auth:tenant'])->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::get('/fav-partners', [FavoriteController::class, 'favPartners']);
        Route::get('/products', [FavoriteController::class, 'products']);
        Route::post('/', [FavoriteController::class, 'store']);
        Route::delete('/{productId}', [FavoriteController::class, 'destroy']);
        Route::delete('/clear', [FavoriteController::class, 'clear']);
    });

    // ==========================================
    // 🎮 ИГРЫ ПОДКЛЮЧАЮТСЯ ИЗ ОТДЕЛЬНОГО ФАЙЛА
    // ==========================================
    require base_path('routes/games.php');

    Route::prefix('admin')->group(function () {
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index']);
            Route::get("/export", [AdminOrderController::class, "export"]);
            Route::get('/{id}', [AdminOrderController::class, 'show']);
            Route::get('/{id}/export', [AdminOrderController::class, 'exportSingle']);
            Route::post('/{id}/status', [AdminOrderController::class, 'updateStatus']);
            Route::post('/{id}/message', [AdminOrderController::class, 'sendMessage']);
        });

        Route::get('/transactions', [TransactionAdminController::class, 'index'])
            ->name('admin.transactions.index');

        Route::prefix('achievements')->group(function () {
            Route::get('/data', [AchievementAdminController::class, 'index']);
            Route::post('/', [AchievementAdminController::class, 'store']);
            Route::post('/claim/{achievementId}', [AchievementController::class, 'claim']);
            Route::post('/claim-all', [AchievementController::class, 'claimAll']);
            Route::put('/{achievement}', [AchievementAdminController::class, 'update']);
            Route::post('/{achievement}/toggle', [AchievementAdminController::class, 'toggle']);
            Route::delete('/{achievement}', [AchievementAdminController::class, 'destroy']);
        });

        Route::prefix('clients')->middleware(['auth:tenant'])->group(function () {
            Route::get('/{userId}', [ClientsController::class, 'show']);
            Route::get('/{userId}/messages', [ClientsController::class, 'messages']);
            Route::post('/{userId}/send', [ClientsController::class, 'sendMessage']);
            Route::post('/{userId}/send-file', [ClientsController::class, 'sendFile']);
            Route::post('/{userId}/read', [ClientsController::class, 'markAsRead']);
            Route::delete('/messages/{messageId}', [ClientsController::class, 'deleteMessage']);
        });

        Route::prefix('roles')->group(function () {
            Route::get('/', [RolesController::class, 'index']);
            Route::post('/', [RolesController::class, 'store']);
            Route::put('/{roleId}', [RolesController::class, 'update']);
            Route::delete('/{roleId}', [RolesController::class, 'destroy']);
        });

        Route::get('/permissions', [RolesController::class, 'permissions']);

        Route::prefix('statistic')->middleware(['auth:tenant'])->group(function () {
            Route::get('/main', [StatisticController::class, 'main']);
            Route::get('/traffic', [StatisticController::class, 'traffic']);
            Route::get('/export', [StatisticController::class, 'export']);
        });

        Route::prefix('promocodes')->middleware(['auth:tenant'])->group(function () {
            Route::get('/', [PromoCodeController::class, 'index']);
            Route::post('/', [PromoCodeController::class, 'store']);
            Route::get('/{id}', [PromoCodeController::class, 'show']);
            Route::put('/{id}', [PromoCodeController::class, 'update']);
            Route::delete('/{id}', [PromoCodeController::class, 'destroy']);
            Route::post('/{id}/toggle-active', [PromoCodeController::class, 'toggleActive']);
        });

        Route::prefix("tap-links")->group(function () {
            Route::get('/', [TenantTapLinkController::class, 'adminIndex']);
            Route::post('/', [TenantTapLinkController::class, 'store']);
            Route::put('/{taplink}', [TenantTapLinkController::class, 'update']);
            Route::delete('/{taplink}', [TenantTapLinkController::class, 'destroy']);
            Route::post('/{taplink}/move', [TenantTapLinkController::class, 'move']);
        });

        Route::prefix("tenant-settings")->group(function () {
            Route::put('/basic', [TenantSettingsController::class, 'updateBasic']);
            Route::put('/shop', [TenantSettingsController::class, 'updateShop']);
            Route::put('/faq', [TenantSettingsController::class, 'updateFaq']);
            Route::put('/landing', [TenantSettingsController::class, 'updateLanding']);
            Route::put('/telegram', [TenantSettingsController::class, 'updateTelegram']);
            Route::put('/wheel', [TenantSettingsController::class, 'updateWheel']);
            Route::put('/guests', [TenantSettingsController::class, 'updateGuests']);
            Route::put('/main-menu', [TenantSettingsController::class, 'updateMainMenu']);
            Route::put('/cashback', [TenantSettingsController::class, 'updateCashback']);
            Route::put('/interactive', [TenantSettingsController::class, 'updateInteractive']);
            Route::put('/tables', [TenantSettingsController::class, 'updateTables']);
            Route::put('/menu', [TenantSettingsController::class, 'updateMenu']);
            Route::put('/calculators', [TenantSettingsController::class, 'updateCalculators']);
            Route::put('/games', [TenantSettingsController::class, 'updateGames']);
            Route::put('/crm', [TenantSettingsController::class, 'updateCrm']);
            Route::post('/main-menu/upload-icon', [TenantSettingsController::class, 'uploadMainMenuIcon']);
            Route::post('/main-menu/reset-icon', [TenantSettingsController::class, 'resetMainMenuIcon']);
            Route::get('/pwa', [TenantPwaController::class, 'getPwaSettings']);
            Route::post('/pwa', [TenantPwaController::class, 'savePwaSettings']);
            Route::put('/pwa', [TenantPwaController::class, 'savePwaSettings']);
            Route::post('/pwa/upload-icon', [TenantPwaController::class, 'uploadIcon']);
            Route::post('/pwa/upload-screenshot', [TenantPwaController::class, 'uploadScreenshot']);
            Route::delete('/pwa/icon', [TenantPwaController::class, 'deleteIcon']);
            Route::delete('/pwa/screenshot', [TenantPwaController::class, 'deleteScreenshot']);
            Route::post('/shop/test-sbp-payment', [TenantSettingsController::class, 'testSbpPayment']);
        });

        Route::prefix('users')->middleware(['auth:tenant'])->group(function () {
            Route::get('/search', [UsersController::class, 'search']);
            Route::get('/download', [UsersController::class, 'download']);
            Route::get('/cashback-history', [UsersController::class, 'cashbackHistory']);

            Route::post('/find-by-referral', [UsersController::class, 'findByReferralCode']);
            Route::post('/manage-by-referral', [UsersController::class, 'manageCashbackByReferralCode']);

            Route::post('/{userId}/cashback', [UsersController::class, 'addCashback']);
            Route::post('/{userId}/add-cashback', [UsersController::class, 'manageCashback']);
            Route::post('/{userId}/start-chat', [UsersController::class, 'startChat']);
            Route::get('/{userId}/edit', [UsersController::class, 'edit']);
            Route::put('/{userId}', [UsersController::class, 'update']);
            Route::post('/{userId}/toggle-block', [UsersController::class, 'toggleBlock']);
            Route::post('/{userId}/toggle-vip', [UsersController::class, 'toggleVip']);
        });

        Route::prefix('broadcasts')->middleware(['auth:tenant'])->group(function () {
            Route::get('/', [BroadcastController::class, 'index']);
            Route::post('/', [BroadcastController::class, 'store']);
            Route::post('/{id}/send', [BroadcastController::class, 'send']);
            Route::post('/{id}/cancel', [BroadcastController::class, 'cancel']);
            Route::post('/{id}/duplicate', [BroadcastController::class, 'duplicate']);
            Route::post('/{id}/media', [BroadcastController::class, 'uploadMedia']);
            Route::delete('/media/{mediaId}', [BroadcastController::class, 'deleteMedia']);
            Route::get('/users', [BroadcastController::class, 'getUsers']);
            Route::get('/recipients-count', [BroadcastController::class, 'getRecipientsCount']);
            Route::get('/{id}', [BroadcastController::class, 'show']);
            Route::put('/{id}', [BroadcastController::class, 'update']);
            Route::delete('/{id}', [BroadcastController::class, 'destroy']);
        });
    });

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

    Route::prefix('friends')->group(function () {
        Route::post('/request', [FriendsController::class, 'sendRequest']);
        Route::post('/{id}/accept', [FriendsController::class, 'acceptRequest']);
        Route::post('/{id}/reject', [FriendsController::class, 'rejectRequest']);
        Route::delete('/{friendId}', [FriendsController::class, 'removeFriend']);
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
        ->group(function () {
            Route::get('/unread-count', [TenantDialogController::class, 'unreadCount']);
            Route::get('/{dialogId}/unread-count', [TenantDialogController::class, 'dialogUnreadCount']);
            Route::get('/{dialogId}/read-statuses', [TenantDialogController::class, 'getReadStatuses']);
            Route::get('/', [TenantDialogController::class, 'index']);
            Route::get('/{dialogId}', [TenantDialogController::class, 'show']);
            Route::get('/{dialogId}/messages', [TenantDialogController::class, 'messages']);
            Route::post('/{dialogId}/messages', [TenantDialogController::class, 'sendMessage']);
            Route::post('/{dialogId}/read', [TenantDialogController::class, 'markAsRead']);
            Route::post('/{dialogId}/close', [TenantDialogController::class, 'close']);
            Route::post('/{dialogId}/archive', [TenantDialogController::class, 'archive']);
            Route::post('/{dialogId}/restore', [TenantDialogController::class, 'restore']);
            Route::delete('/{dialogId}', [TenantDialogController::class, 'destroy']);
            Route::delete('/archive', [TenantDialogController::class, 'emptyArchive']);
            Route::post('/archive-multiple', [TenantDialogController::class, 'archiveMultiple']);
            Route::get('/{dialogId}/attachments', [TenantDialogController::class, 'attachments']);
        });

    // Coffee Card API
    Route::prefix('coffee')->group(function () {
        // Клиентские маршруты
        Route::post('/init', [CoffeeController::class, 'init']);
        Route::get('/progress', [CoffeeController::class, 'getProgress']);

        // Админские маршруты
        Route::middleware('admin')->group(function () {
            Route::post('/mark', [CoffeeController::class, 'mark']);
            Route::post('/exchange', [CoffeeController::class, 'exchange']);
            Route::post('/reset', [CoffeeController::class, 'reset']);
        });


    });

    Route::prefix('feedback')->group(function () {
        Route::post('/', [FeedbackController::class, 'submit']);
    });

    Route::prefix("basket")
        ->controller(BasketController::class)
        ->group(function () {
            Route::post('/', "loadProductsInBasket");
            Route::post('/checkout', "checkout")->middleware(['track.order']);
            Route::post("/get-delivery-price-new", [ProductController::class, "getDeliveryPriceNew"]);
            Route::post('/checkout-link', "checkoutLink");
            Route::post("/use-wheel-of-fortune-prize", "useWheelOfFortunePrize");
            Route::post('/increment/{id}', "incrementItem")->middleware(['track.stats:products_in_cart']);
            Route::post('/decrement/{id}', "decrementItem");

            Route::post('/add-product', [BasketController::class, 'addProduct']);
            Route::post('/inc-product', "incProductInBasket")->middleware(['track.stats:products_in_cart']);
            Route::post('/comment-product', "commentProductInBasket");
            Route::post('/dec-product', "decProductInBasket");
            Route::post('/inc-collection', "incCollectionInBasket")->middleware(['track.stats:collections_in_cart']);
            Route::post('/dec-collection', "decCollectionInBasket");
            Route::post('/remove-collection', "removeCollection");
            Route::delete('/clear', "clearBasket");
            Route::delete('/remove/{id}', "removeBasketItem");
        });

    Route::prefix("achievements")
        ->group(function () {
            Route::get('/', [AchievementController::class, 'index']);
            Route::get('//stats', [AchievementController::class, 'stats']);
            Route::post('//{id}/claim', [AchievementController::class, 'claimReward']);
        });

    Route::prefix("stories")
        ->group(function () {
            Route::get("/", [StoryController::class, "index"]);
            Route::get("/{id}", [StoryController::class, "show"]);
            Route::post("/", [StoryController::class, "store"]);
            Route::delete("/{id}", [StoryController::class, "destroy"]);
        });

    Route::prefix('referrals')
        ->group(function () {
            Route::get('/tree', [ReferralController::class, 'tree']);
            Route::get('/rewards', [ReferralController::class, 'rewards']);
            Route::get('/link', [ReferralController::class, 'link']);
            Route::get('/friends', [ReferralController::class, 'friends']);
            Route::get('/friends/requests', [ReferralController::class, 'friendRequests']);
            Route::post('/friends/request', [ReferralController::class, 'sendFriendRequest']);
            Route::post('/friends/request/{requestId}/accept', [ReferralController::class, 'acceptFriendRequest']);
            Route::post('/friends/request/{requestId}/reject', [ReferralController::class, 'rejectFriendRequest']);
            Route::delete('/friends/{friendId}', [ReferralController::class, 'removeFriend']);
        });

    Route::prefix('cashback')->group(function () {
        Route::get('/', [CashBackController::class, 'index']);
        Route::get('/history', [CashBackController::class, 'history']);
        Route::get('/download', [CashBackController::class, 'downloadHistory']);
    });

    Route::prefix("shop")
        ->group(function () {
            Route::prefix("orders")
                ->group(function () {
                    Route::post("/", [OrderController::class, "getOrders"]);
                    Route::post('/rr', [OrderController::class, 'getRandomRecentOrders']);
                    Route::post("/send-sbp-invoice", [OrderController::class, "sendSBPInvoice"]);
                    Route::post("/all", [OrderController::class, "getAllOrders"]);
                    Route::post("/repeat-order", [OrderController::class, "repeatOrder"]);
                    Route::post("/decline-order", [OrderController::class, "declineOrder"]);
                    Route::post("/change-order-status", [OrderController::class, "changeStatusOrder"]);
                    Route::post("/get-order-by-id", [OrderController::class, "loadOrderById"]);
                    Route::post("/add-cashback-to-order", [OrderController::class, "addCashBackToOrder"]);
                    Route::post("/get-delivery-price", [OrderController::class, "getDeliveryPrice"]);
                });

            Route::prefix("reviews")
                ->group(function () {
                    Route::post("/", [OrderController::class, "getReviews"]);
                    Route::post("/by-product-id", [OrderController::class, "getReviewsByProductId"]);
                    Route::post("/can-review-order", [OrderController::class, "canReviewOrder"]);
                    Route::post("/store-review", [OrderController::class, "storeReview"]);
                    Route::put("/update-review/{id}", [OrderController::class, "updateReview"]);
                    Route::delete("/delete-review/{id}", [OrderController::class, "deleteReview"]);
                    Route::post("/notify-user", [OrderController::class, "notifyUser"]);
                });

            Route::prefix('collections')->group(function () {
                Route::get('/', [CollectionController::class, 'active']);
                Route::get('/{id}', [CollectionController::class, 'show']);
                Route::post('/list', [CollectionController::class, 'index']);
                Route::post('/', [CollectionController::class, 'store']);
                Route::delete('/{id}', [CollectionController::class, 'destroy']);
                Route::post('/{id}/toggle-active', [CollectionController::class, 'toggleActive']);
                Route::post('/{id}/toggle-stop-list', [CollectionController::class, 'toggleStopList']);
                Route::post('/{id}/duplicate', [CollectionController::class, 'duplicate']);
                Route::post('/remove-all', [CollectionController::class, 'removeAll']);
                Route::post('/{collectionId}/categories', [CollectionController::class, 'addCategory']);
                Route::post('/categories/{categoryId}', [CollectionController::class, 'updateCategory']);
                Route::delete('/categories/{categoryId}', [CollectionController::class, 'removeCategory']);
                Route::post('/categories/{categoryId}/products', [CollectionController::class, 'addProducts']);
                Route::delete('/categories/{categoryId}/products/{productId}', [CollectionController::class, 'removeProduct']);
                Route::post('/categories/{categoryId}/products/reorder', [CollectionController::class, 'reorderProducts']);
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
        });

    Route::prefix("partners")
        ->controller(PartnersController::class)
        ->group(function () {
            Route::post("/", "index");
            Route::post("/full-partners", "fullIndex");
            Route::post("/store", "store");
            Route::post("/toggle-favorite", "togglePartnersInFavorites");
            Route::post("/update-settings", "updateSettings");
            Route::post("/update-active-status", "updateActiveStatus");
            Route::post("/change-status", "changeStatus");
            Route::post("/partners-categories", "partnersCategories");
            Route::post("/update", "update");
            Route::post("/update-self", "updateSelf");
            Route::post("/remove/{partnerId}", "destroy");
            Route::get('/products-stats', [PartnersController::class, 'productsStats']);
            Route::get('/products-by-category', [PartnersController::class, 'productsByCategory']);
            Route::get('/products-by-category/more', [PartnersController::class, 'productsByCategoryMore']);
            Route::post('/change-product-status', [PartnersController::class, 'changePartnerProductStatus']);
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
}

Route::middleware(['tenant'])->group(function () {
    routes();
});

Route::get("/m", function (){
    return redirect('/pwa', 301);
});

Route::get('/auth/vk/redirect', [TenantSocialAuthController::class, 'redirect']);
Route::get('/auth/vk/callback', [TenantSocialAuthController::class, 'callback']);

Route::post('/auth/login', [TenantAuthController::class, 'login']);
Route::post('/auth/logout', [TenantAuthController::class, 'logout']);

Route::get('/me', [TenantAuthController::class, 'me']);

Route::middleware(['tenant.access'])->group(function () {});

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
