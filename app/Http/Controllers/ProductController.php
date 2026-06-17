<?php

namespace App\Http\Controllers;

use App\Facades\GEOService;
use App\Facades\PaymentService;
use App\Facades\ProductService;
use App\Facades\TenantUserService;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductController extends Controller
{
    public function sendSBPInvoice(Request $request)
    {
        $request->validate([
            "amount" => "required",
            "description" => "required"
        ]);

        PaymentService::call()
            ->invoiceLink($request->all());
    }

    public function generateTablesQR(Request $request)
    {

        $tenant = app('tenant');

        $countTables = $request->get("count") ?? 30;
        $scriptId = $request->get("script-id") ?? null;
        $test = $request->get("test") ?? null;

        if (is_null($scriptId))
            throw new HttpException(404, "Скрипт магазина не найдена!");

        $mpdf = new Mpdf();

        $number = Str::uuid();

        $tables = [];
        $row = [];
        for ($i = 0; $i < $countTables; $i++) {

            $qrLink = "https://t.me/$botDomain?start=" .
                base64_encode("777slug" . $scriptId . "table" . $i);

            $qr = (object)[
                "id" => $i + 1,
                "qr" => "https://api.qrserver.com/v1/create-qr-code/?size=450x450&qzone=2&data=$qrLink"
            ];

            if ($i % 2 != 0)
                $row[] = $qr;
            else {
                $tables[] = $row;
                $row = [
                    $qr
                ];
            }
        }

        $tables[] = $row;

        if (!is_null($test))
            return response()
                ->json($tables);

        ini_set('max_execution_time', 30000);
        $mpdf->WriteHTML(view("pdf.tables-qr", [
            "tables" => $tables
        ]));

        return $mpdf->Output("tables-$number.pdf", \Mpdf\Output\Destination::DOWNLOAD);
    }


    public function changeStatusOrder(Request $request): \Illuminate\Http\Response
    {
        $request->validate([
            "order_id" => "required",
            "status" => "required"
        ]);

        BusinessLogic::delivery()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->changeStatusOrder(
                $request->order_id ?? null,
                $request->status ?? 0,
                $request->user_telegram_chat_id ?? null
            );

        return response()->noContent();
    }


    /**
     * @throws ValidationException
     */
    public function getDeliveryPriceNew(Request $request): \Illuminate\Http\JsonResponse
    {

        $request->validate([
            "address" => "required",
            "lat" => "required",
            "lng" => "required",
        ]);


        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $config = $tenant->settings ?? [];

        $basketIds = Basket::query()
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->get()
            ->pluck("tenant_partner_id");

        $partners = Tenant::query()
            ->whereIn('id', $basketIds)
            ->distinct('id')
            ->get();

        $partners = [...$partners, $tenant];

        if (empty($config))
            return response()->json([
                "distance" => 0,
                "price" => 0,
                "address" => null,
                "config" => []
            ], 404);


        $sumDistance = 0;
        $sumPrice = 0;

        $partnerBoxConfig = [];

        $address = $request->address;

        $lat = $request->lat ?? 0;
        $lng = $request->lng ?? 0;

        $price_per_km = $config["delivery"]["price_per_km"] ?? 100;
        $min_base_delivery_price = $config["delivery"]["min_base_delivery_price"] ?? 100;

        $isPartnersActive = $config["partners"]["is_active"] ?? false;

        $isPartnersDisplaySelf = $config["partners"]["display_self"] ?? false;


        foreach ($partners as $partner) {
            if ($isPartnersActive && !$isPartnersDisplaySelf
                && $partner->id == $tenant->id
            )
                continue;


            $partnerBoxConfig[$partner->uuid] = (object)[
                "id" => $partner->id,
                "price" => 0,
                "title" => $partner->name ?? $partner->slug ?? '-',
                "distance" => 0,
                "address" => $address,
                "shop_coords" => $partner->settings["shop_coords"] ?? null,
                "client_coords" => $lat . ", " . $lng,
            ];

            $tmpDistance = GEOService::call()
                ->getDistance($lat, $lng);

            $distance = floatval(round($tmpDistance / 1000 ?? 0, 2));

            $partnerBoxConfig[$partner->uuid]->distance = $distance;
            $partnerBoxConfig[$partner->uuid]->price = round($min_base_delivery_price + $distance * $price_per_km, 2);

            $sumDistance += $partnerBoxConfig[$partner->uuid]->distance;
            $sumPrice += $partnerBoxConfig[$partner->uuid]->price;

        }

        return response()->json([
            "distance" => $sumDistance,
            "price" => $sumPrice,
            "address" => $address,
            "config" => $partnerBoxConfig,
        ]);
    }


    /**
     * @throws ValidationException
     */
    public function loadRecommendedProducts(Request $request): ProductCollection
    {
        return ProductService::call()
            ->loadRecommendedProducts();
    }

    public function exportAllProducts(Request $request): \Illuminate\Http\Response
    {
        ProductService::call()
            ->exportAllProducts();

        return response()->noContent();
    }

    /**
     * @throws ValidationException
     */
    public function getAllOrders(Request $request): \App\Http\Resources\OrderCollection
    {
        return BusinessLogic::delivery()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->orderList($request->all(), $request->get("size") ?? config('app.results_per_page'), true);

    }

    /**
     * @throws ValidationException
     */
    public function getOrders(Request $request): \App\Http\Resources\OrderCollection
    {
        return BusinessLogic::delivery()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->orderList($request->all(), $request->get("size") ?? config('app.results_per_page'));

    }

    public function getReviews(Request $request): \App\Http\Resources\ReviewCollection
    {


        return BusinessLogic::review()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->reviews($request->all(), $request->get("size") ?? config('app.results_per_page'));
    }

    public function getReviewsByProductId(Request $request): \App\Http\Resources\ReviewCollection
    {

        $request->validate([
            "product_id" => "required"
        ]);

        return BusinessLogic::review()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->reviewsByProductId($request->product_id, $request->get("size") ?? config('app.results_per_page'));
    }

    public function notifyUser(Request $request)
    {
        BusinessLogic::review()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->notifyUserForReview($request->all());
    }

    /**
     * @throws ValidationException
     */
    public function storeReview(Request $request): \App\Http\Resources\ReviewResource
    {
        $request->validate([
            'id' => "required",

        ]);

        return BusinessLogic::review()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->store($request->all(),
                $request->hasFile('photo') ?
                    $request->file('photo') : null);
    }

    /**
     * @throws ValidationException
     */
    public function addCashBackToOrder(Request $request)
    {
        $request->validate([
            "order_id" => "required"
        ]);

        BusinessLogic::delivery()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->addCashBackToOrder($request->all());
    }

    public function loadOrderById(Request $request): \App\Http\Resources\OrderResource
    {
        $request->validate([
            "order_id" => "required"
        ]);

        return BusinessLogic::delivery()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->getOrder($request->order_id ?? null);
    }

    public function declineOrder(Request $request): \Illuminate\Http\Response
    {
        $request->validate([
            "order_id" => "required"
        ]);

        BusinessLogic::delivery()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->declineOrder($request->order_id ?? null);

        return response()->noContent();
    }

    /**
     * @throws ValidationException
     */
    public function repeatOrder(Request $request): ProductCollection
    {
        $request->validate([
            "products" => "required"
        ]);

        return BusinessLogic::delivery()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->repeatOrder($request->all());
    }

    public function getProductsByIds(Request $request): ProductCollection
    {
        $request->validate([
            "ids" => "required|array"
        ]);

        return ProductService::call()
            ->byIds($request->ids ?? []);
    }


    public function changeCategoryStatus(Request $request, $categoryId): CategoryResource
    {
        return ProductService::call()
            ->changeCategoryStatus($categoryId);
    }

    public function removeCategoryId(Request $request, $categoryId): CategoryResource
    {
        return ProductService::call()
            ->destroyCategory($categoryId);
    }

    public function loadMoreProductsByCategories(Request $request)
    {
        $request->validate([
            "category_id" => "required",
            "offset" => "required"
        ]);

        return ProductService::call()
            ->loadMoreProductsByCategories(
                $request->category_id,
                $request->offset,
                $request->partner_id ?? null);
    }

    public function listByCategories(Request $request)
    {
        return ProductService::call()
            ->listByCategories($request->all());
    }

    public function index(Request $request): ProductCollection
    {

        return ProductService::call()
            ->list(
                search: $request->search ?? null,
                filters: [
                    "categories" => $request->categories ?? null,
                    "min_price" => $request->min_price ?? null,
                    "max_price" => $request->max_price ?? null
                ],
                size: $request->get("size") ?? config('app.results_per_page'),
                needAll: $request->get("need_all") ?? false,
                needRemoved: $request->get("need_removed") ?? false
            );
    }

    public function getFavList(Request $request): ProductCollection
    {
        return ProductService::call()
            ->favList();
    }

    public function toggleProductInFavorites(Request $request)
    {
        $request->validate([
            "id" => "required"
        ]);

        return response()
            ->json([
                "favorites" => TenantUserService::call()
                    ->toggleProductInFavorites($request->id)
            ]);
    }

    /**
     * @throws ValidationException
     */
    public function storeCategory(Request $request): CategoryResource
    {
        $request->validate([
            "category" => "required"
        ]);

        return ProductService::call()
            ->createOrUpdateCategory($request->all());
    }

    public function getCategories(Request $request): \App\Services\CategoryCollection
    {
        return ProductService::call()
            ->categories(
                true,
                $request->all(),
                $request->get("size") ?? config('app.results_per_page'));
    }

    public function getProduct(Request $request, $productId): ProductResource
    {
        return ProductService::call()
            ->product($productId);
    }

    public function randomProducts(Request $request): ProductCollection
    {

        return ProductService::call()
            ->randomList();
    }


    /**
     * @throws ValidationException
     */
    public function changeCategoryRecommendationStatus(Request $request): array
    {
        $request->validate([
            "category_id" => "required",
            "status" => "required",
        ]);

        return ProductService::call()
            ->changeCategoryRecommendationStatus($request->all());


    }

    /**
     * @throws ValidationException
     */
    public function changeRecommendationStatus(Request $request): array
    {
        $request->validate([
            "product_id" => "required",
            "status" => "required",
        ]);

        return ProductService::call()
            ->changeRecommendationStatus($request->all());


    }

    /**
     * @throws ValidationException
     */
    public function saveProduct(Request $request): ProductResource
    {
        $request->validate([
            "article" => "",
            "title" => "required",
            "type" => "required",
            "price" => "required",
        ]);

        return ProductService::call()
            ->createOrUpdate($request->all(),
                $request->hasFile('photos') ?
                    $request->file('photos') : null);

    }

    public function removeAllProducts(Request $request)
    {
        ProductService::call()
            ->removeAllProducts();

        return response()->noContent();
    }

    public function stopList(Request $request, $productId): ProductResource
    {
        return ProductService::call()
            ->stopList($productId);
    }

    public function restore(Request $request, $productId): ProductResource
    {
        return ProductService::call()
            ->restore($productId);
    }


    public function destroy(Request $request, $productId)
    {
        return ProductService::call()
            ->destroy($productId);
    }

    public function duplicate(Request $request, $productId): ProductResource
    {
        return ProductService::call()
            ->duplicate($productId);
    }


    public function getProductsInCategory(Request $request): ProductCollection
    {

        return ProductService::call()
            ->productsInCategory(
                $request->category_id ?? null,
                $request->search ?? null
            );
    }

    public function getCategory(Request $request, $categoryId): CategoryResource
    {
        return ProductService::call()
            ->category($categoryId);
    }

}
