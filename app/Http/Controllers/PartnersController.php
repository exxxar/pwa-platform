<?php

namespace App\Http\Controllers;

use App\Facades\PartnerService;
use App\Models\Tenant\Category;
use App\Models\Tenant\Partner;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;

class PartnersController extends Controller
{
    public function index(Request $request): \App\Http\Resources\PartnerCollection
    {
        return PartnerService::call()
            ->list($request->all());
    }

    public function togglePartnersInFavorites(Request $request)
    {
        $request->validate([
            "id" => "required"
        ]);

        return response()
            ->json([
                "fav_partners" => PartnerService::call()
                    ->togglePartnerInFavorites($request->id)
            ]);
    }

    public function partnersCategories(Request $request): \Illuminate\Database\Eloquent\Collection|array
    {
        return PartnerService::call()
            ->listOfPartnersCategories();
    }

    public function updateSettings(Request $request)
    {

        return PartnerService::call()
            ->updateSettings($request->all());
    }

    public function updateActiveStatus(Request $request)
    {
        return PartnerService::call()
            ->updateActiveStatus($request->all());
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            "product_id" => "required",
            "partner_id" => "required",
            "status" => "required",
        ]);

        return PartnerService::call()
            ->changeStatus($request->all());
    }


    /**
     * @throws ValidationException
     */
    public function store(Request $request): \App\Http\Resources\PartnerResource
    {
        $request->validate([
            "telegram_domain" => "required",
        ]);

        return PartnerService::call()
            ->create($request->all());
    }

    /**
     * @throws ValidationException
     */
    public function update(Request $request): \App\Http\Resources\PartnerResource
    {
        $request->validate([
            'id' => "required",
            'tenant_partner_id' => "required",
            'title' => "",
            'description' => "",
            'image' => "",
            'is_active' => "",
            'extra_charge' => "",
            'config' => "",
            'legal_info' => "",
        ]);

        return PartnerService::call()
            ->update($request->all(), $request->hasFile("file") ? $request->file("file") : null);


    }

    /**
     * @throws ValidationException
     */
    public function updateSelf(Request $request)
    {
        $request->validate([
            'title' => "",
            'description' => "",
        ]);

        return PartnerService::call()
            ->updateSelf($request->all(), $request->hasFile("file") ? $request->file("file") : null);


    }

    public function destroy(Request $request, $id)
    {

        return PartnerService::call()
            ->destroy($id);

    }


    /**
     * 🆕 Получить товары партнёра по категориям
     */
    public function productsByCategory(Request $request)
    {
        $request->validate([
            'partner_id' => 'required|integer',
        ]);

        $partner = Partner::findOrFail($request->partner_id);
        $partnerTenantId = $partner->tenant_partner_id;

        if (!$partnerTenantId) {
            return response()->json(['data' => []]);
        }



        // ✅ ПРАВИЛЬНО: используем withCount с отношением products (belongsToMany)
        // Eloquent сам построит правильный SQL через pivot-таблицу product_categories
        $categories = Category::query()
        ->where('tenant_id', $partnerTenantId)
            ->where('is_active', true)
            ->withCount([
                'products as products_count' => function ($query) {
                    $query->where('products.is_active', true)
                        ->where('products.in_stop_list', false);
                }
            ])
            ->with([
                'products' => function ($query) {
                    $query->where('products.is_active', true)
                        ->where('products.in_stop_list', false)
                        ->orderBy('products.order_position')
                        ->limit(20);
                }
            ])
            ->orderBy('order_position')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->name,
                    'name' => $category->name,
                    'description' => $category->description,
                    'products_count' => $category->products_count,
                    'products' => $category->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'title' => $product->name,
                            'price' => $product->price,
                            'current_price' => $product->price,
                            'old_price' => $product->old_price,
                            'description' => $product->description,
                            'sku' => $product->sku,
                            'images' => $product->images,
                            'is_active' => $product->is_active,
                        ];
                    }),
                ];
            });

        return response()->json(['data' => $categories]);
    }

    /**
     * Дозагрузка товаров категории
     */
    public function productsByCategoryMore(Request $request)
    {
        $request->validate([
            'partner_id' => 'required|integer',
            'category_id' => 'required|integer',
            'offset' => 'required|integer|min:0',
        ]);

        $partner = Partner::findOrFail($request->partner_id);
        $partnerTenantId = $partner->tenant_partner_id;

        if (!$partnerTenantId) {
            return response()->json(['data' => []]);
        }

        // ✅ ПРАВИЛЬНО: используем whereHas с отношением categories (belongsToMany)
        $products = Product::where('tenant_id', $partnerTenantId)
            ->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            })
            ->where('is_active', true)
            ->whereNull('in_stop_list')
            ->orderBy('order_position')
            ->offset($request->offset)
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'title' => $product->name,
                    'price' => $product->price,
                    'current_price' => $product->price,
                    'old_price' => $product->old_price,
                    'description' => $product->description,
                    'sku' => $product->sku,
                    'images' => $product->images,
                    'is_active' => $product->is_active,
                ];
            });

        return response()->json(['data' => $products]);
    }

    /**
     * 🆕 Изменение статуса товара партнёра
     */
    public function changePartnerProductStatus(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'partner_id' => 'required|integer',
            'status' => 'required|integer|in:0,1',
        ]);

        $partner = Partner::findOrFail($request->partner_id);

        // Обновляем конфиг партнёра (список исключённых товаров)
        $config = $partner->config ?? [];
        $excludes = $config['excludes'] ?? [];

        if ($request->status === 1) {
            // Скрыть товар
            if (!in_array($request->product_id, $excludes)) {
                $excludes[] = $request->product_id;
            }
        } else {
            // Показать товар
            $excludes = array_values(array_filter($excludes, function ($id) use ($request) {
                return $id != $request->product_id;
            }));
        }

        $config['excludes'] = $excludes;
        $partner->config = $config;
        $partner->save();

        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }

    /**
     * 🆕 Статистика товаров всех партнёров
     * GET /bot-client/partners/products-stats
     */
    public function productsStats()
    {
        $tenant = app('tenant');

        // Получаем ID всех тенантов-партнёров
        $partnerTenantIds = Partner::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotNull('tenant_partner_id')
            ->pluck('tenant_partner_id')
            ->toArray();

        if (empty($partnerTenantIds)) {
            return response()->json([
                'data' => [
                    'total_products' => 0,
                    'total_products_sum' => 0,
                ]
            ]);
        }

        // Считаем количество активных товаров
        $totalProducts = Product::whereIn('tenant_id', $partnerTenantIds)
            ->where('is_active', true)
            ->count();

        // 🆕 Считаем количество активных категорий
        $totalCategories = Category::whereIn('tenant_id', $partnerTenantIds)
            ->where('is_active', true)
            ->count();

        // Считаем общую сумму (берём текущую цену)
        $totalSum = Product::whereIn('tenant_id', $partnerTenantIds)
            ->where('is_active', true)
            ->sum('price');

        return response()->json([
            'data' => [
                'total_products' => $totalProducts,
                'total_products_categories' => $totalCategories,
                'total_products_sum' => (float) $totalSum,
            ]
        ]);
    }
}
