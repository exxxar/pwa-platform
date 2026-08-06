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
use Illuminate\Support\Facades\Log;
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
        // 1. Валидация и получение данных
        $validated = $request->validate([
            "address" => "required|string",
            "lat" => "required|numeric",
            "lng" => "required|numeric",
        ]);

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();
        $config = $tenant->settings ?? [];

        if (empty($config)) {
            return response()->json([
                "message" => "Конфигурация доставки не найдена",
                "distance" => 0,
                "price" => 0,
                "address" => null,
                "config" => []
            ], 400);
        }

        // 🆕 Выносим price_per_km наверх — он участвует в ВСЕХ расчётах
        $pricePerKm = (float)($config["shop"]["price_per_km"]
            ?? $config["min_base_delivery_price"]
            ?? 80);

        $minBaseDeliveryPrice = (float)($config["shop"]["min_base_delivery_price"]
            ?? $config["min_base_delivery_price"] ?? 100);

        // 2. Оптимизированный запрос ID тенантов из корзины
        $basketTenantIds = Basket::query()
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->pluck('tenant_partner_id')
            ->unique()
            ->toArray();

        // 3. Получение партнеров (магазинов)
        $partners = [];
        if (!empty($basketTenantIds)) {
            $partners = Tenant::query()
                ->whereIn('id', $basketTenantIds)
                ->get()
                ->keyBy('id');
        }

        if (!isset($partners[$tenant->id])) {
            $partners[$tenant->id] = $tenant;
        }

        $clientLat = (float)$validated['lat'];
        $clientLng = (float)$validated['lng'];
        $address = $validated['address'];

        // 4. Зоны доставки
        $deliveryZones = $config['shop']['delivery_zones'] ?? $config['delivery_zones'] ?? [];

        usort($deliveryZones, function ($a, $b) {
            return ((float)($a['radius'] ?? 0)) <=> ((float)($b['radius'] ?? 0));
        });

        // Хелпер для парсинга цены зоны
        $parseZonePrice = function ($price) {
            if (is_numeric($price)) return (float)$price;

            $priceStr = mb_strtolower((string)$price);
            if (str_contains($priceStr, 'бесплатно') || str_contains($priceStr, 'free')) {
                return 0.0;
            }

            preg_match('/\d+([\.,]\d+)?/', $priceStr, $matches);
            return isset($matches[0]) ? (float)str_replace(',', '.', $matches[0]) : 0.0;
        };

        $sumDistance = 0.0;
        $sumPrice = 0.0;
        $partnerBoxConfig = [];

        // 5. Расчет для каждого партнера
        foreach ($partners as $partner) {
            $isPartnersActive = (bool)($config["partners"]["is_active"] ?? false);
            $isPartnersDisplaySelf = (bool)($config["partners"]["display_self"] ?? false);

            if ($isPartnersActive && !$isPartnersDisplaySelf && $partner->id === $tenant->id) {
                continue;
            }

            $shopCoords = $partner->settings["shop_coords"]
                ?? $partner->settings["shop"]["shop_coords"]
                ?? null;

            // Если координаты магазина не указаны — базовая цена без расстояния
            if (empty($shopCoords)) {
                $deliveryPrice = $minBaseDeliveryPrice;

                $partnerUuid = $partner->uuid ?? 'partner_' . $partner->id;
                $partnerBoxConfig[$partnerUuid] = [
                    "id" => $partner->id,
                    "price" => $deliveryPrice,
                    "title" => $partner->name ?? $partner->slug ?? 'Неизвестный магазин',
                    "distance" => 0,
                    "address" => $address,
                    "shop_coords" => null,
                    "client_coords" => $clientLat . ", " . $clientLng,
                    "is_outside_zones" => false,
                    "no_coords" => true,
                ];

                $sumPrice += $deliveryPrice;
                continue;
            }

            // Расчёт расстояния
            $distanceInMeters = GEOService::call()->getDistance($clientLat, $clientLng, $shopCoords);
            $distCoef = env("DISTANCE_COEF") ?? 1;
            $distanceInKm = round(($distanceInMeters / 1000) * $distCoef, 2);

            // 🆕 Базовая стоимость за километры (участвует ВСЕГДА)
            $distanceCost = round($distanceInKm * $pricePerKm, 2);

            $deliveryPrice = 0.0;
            $isOutsideZones = false;

            if (!empty($deliveryZones)) {
                // Ищем первую подходящую зону
                $zoneFound = false;
                foreach ($deliveryZones as $zone) {
                    $radius = (float)($zone['radius'] ?? 0);
                    if ($distanceInKm <= $radius) {
                        // 🎯 ФОРМУЛА: цена_зоны + (price_per_km × км)
                        $zoneBasePrice = $parseZonePrice($zone['price'] ?? 100);
                        $deliveryPrice = round($zoneBasePrice + $distanceCost, 2);
                        $zoneFound = true;
                        break;
                    }
                }

                // За пределами всех зон
                if (!$zoneFound) {
                    $isOutsideZones = true;

                    // Берём цену самой дальней зоны как базу
                    $lastZonePrice = $parseZonePrice(end($deliveryZones)['price'] ?? 100);

                    // 🎯 ФОРМУЛА: цена_последней_зоны + (price_per_km × км)
                    $outsidePrice = round($lastZonePrice + $distanceCost, 2);

                    // Fallback на случай если зоны есть, но цена последней = 0
                    $fallbackPrice = round($minBaseDeliveryPrice + $distanceCost, 2);

                    $deliveryPrice = max($outsidePrice, $fallbackPrice);
                }
            } else {
                // Зоны не настроены — чистая линейная формула
                $deliveryPrice = round($minBaseDeliveryPrice + $distanceCost, 2);
            }

            // Гарантируем минимальную цену
            if ($deliveryPrice < $minBaseDeliveryPrice && $distanceInKm > 0) {
                $deliveryPrice = $minBaseDeliveryPrice;
            }

            $partnerUuid = $partner->uuid ?? 'partner_' . $partner->id;

            $partnerBoxConfig[$partnerUuid] = [
                "id" => $partner->id,
                "price" => $deliveryPrice,
                "title" => $partner->name ?? $partner->slug ?? 'Неизвестный магазин',
                "distance" => $distanceInKm,
                "address" => $address,
                "shop_coords" => $shopCoords,
                "client_coords" => $clientLat . ", " . $clientLng,
                "is_outside_zones" => $isOutsideZones,
                // 🆕 Прозрачность расчёта для фронта
                "breakdown" => [
                    "base_zone_price" => $deliveryPrice - $distanceCost,
                    "distance_km" => $distanceInKm,
                    "price_per_km" => $pricePerKm,
                    "distance_cost" => $distanceCost,
                    "total" => $deliveryPrice,
                ],
            ];

            $sumDistance += $distanceInKm;
            $sumPrice += $deliveryPrice;
        }

        return response()->json([
            "distance" => round($sumDistance, 2),
            "price" => round($sumPrice, 2),
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

    public function getCategories(Request $request): CategoryCollection
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
