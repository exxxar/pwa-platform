<?php

namespace App\Services;

use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;

use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Tenant\ProductAttribute;
use App\Models\Tenant\TenantUser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductService
{
    public static function call(): self
    {
        return app(self::class);
    }

    /**
     * Универсальный прокси (если вдруг хочешь динамику)
     */
    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    public function byIds(array $ids = []): ProductCollection
    {
        $products = Product::query()
            ->whereIn("id", $ids)
            ->get();

        return new ProductCollection($products);
    }

    // В ProductService или контроллере
    public function loadMoreProductsByCategories(int $categoryId, int $offset, ?int $partnerId = null)
    {
        $tenant = app('tenant');
        $tenantId = $partnerId ?? $tenant->id;

        // Загружаем следующие 4 товара (или сколько вам нужно, например, take(4))
        $products = Product::query()
            ->select('products.*', 'ppc.category_id')
            ->join('product_categories as ppc', 'ppc.product_id', '=', 'products.id')
            ->where('ppc.category_id', $categoryId)
            ->where('products.tenant_id', $tenantId)
            ->whereNull('products.deleted_at')
            ->where('products.in_stop_list', false)
            ->orderBy('products.order_position', 'asc')
            ->skip($offset)
            ->take(12) // 🎯 Грузим порциями по 4 штуки
            ->get();

        return (object)[
            "data" => $products->toArray()
        ];
    }

    /**
     * 🆕 Количество активных товаров
     */
    public function getActiveProductsCount(int $tenantId): int
    {
        return Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
    }

    /**
     * 🆕 Рекомендованные товары
     */
    public function getRecommendedProducts(
        int $tenantId,
        ?int $userId,
        int $limit = 8
    ): array {
        // Базовая логика рекомендаций
        // Можно улучшить на основе истории покупок пользователя

        $query = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['categories']);

        // Если есть пользователь — учитываем его историю
        if ($userId) {
            $purchasedProductIds = \App\Models\Tenant\OrderProduct::whereHas('order', function ($q) use ($userId) {
                $q->where('tenant_user_id', $userId);
            })->pluck('product_id')->toArray();

            if (!empty($purchasedProductIds)) {
                $query->whereNotIn('id', $purchasedProductIds);
            }
        }

        return $query->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'current_price' => $product->price,
                    'old_price' => $product->old_price,
                    'images' => $product->images,
                    'rating' => $product->rating,
                    'categories' => $product->categories->pluck('id'),
                ];
            })
            ->toArray();
    }

    public function listByCategories(array $data = null)
    {

        $tenant = app('tenant');


        $tenantId = $data["partner_id"] ?? $tenant->id;

        $categories = Category::getCategoriesWithProducts($tenantId);


        // Товары без категории
        $withoutCategory = Product::query()
            ->where("tenant_id", $tenantId)
            ->has("categories", "=", 0)
            ->where("in_stop_list", false)
            ->whereNull("deleted_at")
            ->take(8)
            ->offset(0)
            ->get();

        $tmpCategory = [
            "id" => -1,
            "is_active" => true,
            "order_position" => 0,
            "name" => "Без категории",
            "tenant_id" => $tenantId,
            "products" => $withoutCategory->toArray(),
            "products_count" => $withoutCategory->count()
        ];

        return (object)[
            "data" => [$tmpCategory, ...$categories]
        ];


    }

    public function favList(): ProductCollection
    {


        $tenantUser = Auth::guard('tenant')->user();

        $tenantUser = TenantUser::query()->findOrFail($tenantUser->id);

        $favIds =$tenantUser->meta["favorites"] ?? [];


        $products = Product::query()
            ->withTrashed()
            ->whereIn("id", $favIds)
            ->get();

        return new ProductCollection($products);

    }


    public function list($search = null, array $filters = null, $size = null, $needAll = false, $needRemoved = false): ProductCollection
    {

        $tenant = app('tenant');

        $size = $size ?? config('app.results_per_page');

        //need_hide_disabled_products
        $products = Product::query();

        if ($needRemoved)
            $products = $products->withTrashed();

        $products = $products->with(["categories",'attributes'])
            ->where("tenant_id", $tenant->id);


        $products = $needAll ? $products->where("in_stop_list", false) :
            $products->where("in_stop_list", true);


        if (!is_null($search))
            $products = $products
                ->where(function ($q) use ($search) {
                    $q->where("title", "like", "%$search%");
                    // ->orWhere("description", "like", "%$search%");
                });

        if (!empty($filters["categories"])) {
            $products = $products
                ->whereRelation('categories', function ($q) use ($filters) {
                    $q->whereIn('id', $filters["categories"]);
                });
        }

        if (($filters["min_price"] ?? 0) > 0 && ($filters["max_price"] ?? 0) > 0) {
            $products = $products->where(function ($q) use ($filters) {
                $q->where("price", ">=", $filters["min_price"])
                    ->where("price", "<=", $filters["max_price"]);
            });
        }

        if (($filters["min_price"] ?? 0) > 0 && ($filters["max_price"] ?? 0) == 0) {
            $products = $products->where("price", ">=", $filters["min_price"]);
        }

        if (($filters["min_price"] ?? 0) == 0 && ($filters["max_price"] ?? 0) > 0) {
            $products = $products->where("price", "<=", $filters["max_price"]);
        }

        $products = $products
            ->orderBy("created_at", "DESC")
            ->paginate($size);

        return new ProductCollection($products);
    }


    public function categories($isFull = false, array $data = [], $size = null): CategoryCollection
    {

        $tenant = app('tenant');

        $size = $size ?? config('app.results_per_page');

        $order = $data["order_by"] ?? "updated_at";
        $direction = $data["direction"] ?? "desc";


        $categories = Category::query()
            ->where("tenant_id", $tenant->id);

        if (!$isFull)
            $categories = $categories
                ->where("is_active", true);

        $categories = $categories
            ->orderBy($order, $direction)
            ->paginate($size);

        return new CategoryCollection($categories);
    }

    /**
     * @throws HttpException
     */
    public function product($productId): ProductResource
    {

        $product = Product::query()
            ->where("id", $productId)
            ->first();

        return is_null($product) ?
            throw new HttpException(404, "Продукт не найден!") :
            new ProductResource($product);

    }

    /**
     * @throws HttpException
     */
    public function randomList($take = 10): ProductCollection
    {

        $tenant = app('tenant');

        $products = Product::query()
            ->where("tenant_id", $tenant->id)
            ->get();

        return new ProductCollection($products->count() > 10 ? $products->random($take) : $products);

    }

    /**
     * @throws HttpException
     */
    public function loadRecommendedProducts(): ProductCollection
    {

        $tenant = app('tenant');


        $recommendation = $tenant->settings["recommendation"] ?? [
            "categories" => [],
            "products" => [],
            "excludes" => []
        ];

        $categoryIds = $recommendation["categories"] ?? [];

        $categories =Category::query()
            ->with(["products"])
            ->whereIn("id", $categoryIds)
            ->get();

        $tmpProducts = [];

        foreach ($categories as $category)
            foreach ($category->products as $product)
                $tmpProducts[] = $product->id;

        $productIds = $recommendation["products"] ?? [];

        $excludeIds = $recommendation["excludes"] ?? [];

        $products = Product::query()
            ->where("tenant_id", $tenant->id)
            ->whereIn("id", [...$productIds, ...$tmpProducts])
            ->whereNotIn("id", $excludeIds)
            ->get();

        return new ProductCollection($products);

    }


    /**
     * @throws HttpException
     * @throws ValidationException
     */
    public function changeCategoryRecommendationStatus(array $data): array
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "category_id" => "required",
            "status" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $categoryId = $data["category_id"];
        $status = $data["status"] ?? 0;

        $recommendation = $tenant->settings["recommendation"] ?? [
            "categories" => [],
            "products" => [],
            "excludes" => []
        ];

        switch ($status) {
            default:
            case 0:
                $recommendation["categories"] = array_filter($recommendation["categories"] ?? [], fn($v) => $v !== $categoryId);
                break;
            case 1:
                $recommendation["categories"][] = $categoryId;
                break;

        }


        $config = $tenant->settings;
        $config["recommendation"] = $recommendation;
        $tenant->config = $config;
        $tenant->save();

        return $config["recommendation"];

    }

    /**
     * @throws HttpException
     * @throws ValidationException
     */
    public function changeRecommendationStatus(array $data): array
    {

        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "product_id" => "required",
            "status" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $productId = $data["product_id"];
        $status = $data["status"] ?? 0;

        $recommendation = $tenant->settings["recommendation"] ?? [
            "categories" => [],
            "products" => [],
            "excludes" => []
        ];
        switch ($status) {
            default:
            case 0:
                $recommendation["products"] = array_filter($recommendation["products"] ?? [], fn($v) => $v !== $productId);
                $recommendation["excludes"] = array_filter($recommendation["excludes"] ?? [], fn($v) => $v !== $productId);

                break;
            case 1:
                $recommendation["products"][] = $productId;
                $recommendation["excludes"] = array_filter($recommendation["excludes"] ?? [], fn($v) => $v !== $productId);
                break;
            case 2:
                $recommendation["excludes"][] = $productId;
                $recommendation["products"] = array_filter($recommendation["products"] ?? [], fn($v) => $v !== $productId);
                break;
        }

        $config = $tenant->settings;
        $config["recommendation"] = $recommendation;
        $tenant->config = $config;
        $tenant->save();

        return $config["recommendation"];
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function createOrUpdate(array $data, array $uploadedPhotos = null): ProductResource
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "sku" => "",
            "title" => "required",
            "type" => "required",
            "price" => "required",
            "in_stop_list" => "",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $slug = $tenant->slug;


        $photos = !is_null($uploadedPhotos) ?
            $this->uploadPhotos("/public/companies/$slug", $uploadedPhotos) : [];

        if (count($photos) > 0)
            for ($i = 0; $i < count($photos); $i++) {
                $photos[$i] = "/images-by-bot-id/" . $tenant->id . "/" . $photos[$i];
            }


        $images = $data["images"] ?? null;

        if (!is_null($images))
            $images = json_decode($images);

        $images = count($photos) == 0 ? (is_array($images) ? $images : null) : [...$photos, ...$images];

        $variants = $data["variants"] ?? null;

        if (!is_null($variants))
            $variants = json_decode($variants);

        $removedOptions = $data["removed_options"] ?? null;

        if (!is_null($removedOptions)) {
            $removedOptions = json_decode($removedOptions);
            foreach ($removedOptions as $key => $id) {
                $tmpOption = ProductAttribute::query()
                    ->where("id", $id)
                    ->first();

                if (!is_null($tmpOption)) {
                    $tmpOption->delete();
                }

            }
        }

        $productId = $data["id"] ?? null;

        $tmp = [
            'sku' => $data["article"] ?? null,
            'external_source' => $data["vk_product_id"] ?? null,
            'name' => $data["name"] ?? null,
            'description' => $data["description"] ?? null,
            'delivery_terms' => $data["delivery_terms"] ?? null,
            'images' => $images,
            'type' => $data["type"] ?? 0,
            'old_price' => $data["old_price"] ?? 0,
            'price' => $data["price"] ?? 0,

            'not_for_delivery' => ($data["not_for_delivery"] ?? false) == "true",//? Carbon::now() : false,
            'is_weight_product' => ($data["is_weight_product"] ?? false) == "true",
            'tenant_id' => $data["tenant_id"] ?? $tenant->id,
            'weight_config' => is_null($data["weight_config"] ?? null) ?
                null : json_decode($data["weight_config"] ?? '[]'),
            'dimension' => is_null($data["dimension"] ?? null) ?
                null : json_decode($data["dimension"] ?? '[]'),
        ];


        if (!is_null($productId)) {
            $product = Product::query()
                ->with(["categories", "attributes"])
                ->where("id", $productId)
                ->first();

            $product->update($tmp);
        } else
            $product = Product::query()
                ->with(["categories", "attributes"])
                ->create($tmp);


        if (!is_null($data["in_stop_list"] ?? null)) {
            $product->in_stop_list = $data["in_stop_list"] == "true" ;
            $product->save();
        }


        $options = $data["attributes"] ?? null;

        if (!is_null($options)) {
            $options = json_decode($options);
            foreach ($options as $option) {
                $option = (object)$option;

                if (is_null($option->id))
                    ProductAttribute::query()
                        ->create([
                            'name' => $option->title,
                            'value' => $option->value,
                            'section' => $option->section,
                            'product_id' => $product->id
                        ]);
                else
                    ProductAttribute::query()
                        ->find($option->id)
                        ->update([
                            'title' => $option->title,
                            'value' => $option->value,
                            'section' => $option->section,
                        ]);
            }

        }

        $categories = $data["categories"] ?? null;

        if (!is_null($categories)) {

            $tmp = [];
            $categories = json_decode($categories);


            foreach ($categories as $category) {
                $tmpCategory = Category::query()
                    ->where("id", $category)
                    ->first();

                if (is_null($tmpCategory))
                    $tmpCategory = Category::query()
                        ->create([
                            'title' => $category,
                            'tenant_id' => $tenant->id
                        ]);


                $tmp[] = $tmpCategory->id;
            }
            $product->categories()->sync($tmp);


        }

        return new ProductResource($product);

    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function createOrUpdateCategory(array $data): CategoryResource
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "category" => "required",
        ]);


        if ($validator->fails())
            throw new ValidationException($validator);

        $id = $data["category"]["id"] ?? null;
        $tmp = [
            'name' => $data["category"]["name"] ?? '-',
            'order_position' => $data["category"]["order_position"] ?? 0,
            'tenant_id' => $tenant->id,
        ];

        if (is_null($id))
            $category = Category::query()
                ->create($tmp);
        else {
            $category = Category::query()->find($id);

            $category->update($tmp);
        }

        return new CategoryResource($category);
    }

    /**
     * @throws HttpException
     */
    public function stopList($productId): ProductResource
    {
        $product = Product::query()
            ->withTrashed()
            ->find($productId);

        if (is_null($product))
            throw new HttpException(404, "Продукт не найден");


        if (is_null($product->in_stop_list))
            $product->in_stop_list = true;
        else
            $product->in_stop_list = false;

        $product->save();

        return new ProductResource($product);
    }

    /**
     * @throws HttpException
     */
    public function restore($productId): ProductResource
    {
        $product = Product::query()
            ->withTrashed()
            ->find($productId);

        if (is_null($product))
            throw new HttpException(404, "Продукт не найден");


        $product->deleted_at = null;
        $product->save();

        return new ProductResource($product);
    }

    /**
     * @throws HttpException
     */
    public function destroy($productId): ProductResource
    {
        $product = Product::query()
            ->with(["categories", "attributes"])
            ->find($productId);

        if (is_null($product))
            throw new HttpException(404, "Продукт не найден");

        $options = $product->attributes;

        if (!empty($options))
            foreach ($options as $option)
                $option->delete();

        $categories = $product->categories;

        $tmp = [];
        if (!empty($categories))
            foreach ($categories as $category)
                $tmp[] = $category->id;

        $tmpProduct = $product;
        $product->categories()->detach($tmp);

        $product->delete();

        return new ProductResource($tmpProduct);
    }

    /**
     * @throws HttpException
     */
    public function removeCategory($categoryId): CategoryResource
    {
        $category = Category::query()
            ->with(["products"])
            ->where("id", $categoryId)
            ->first();

        if (is_null($category))
            throw new HttpException(404, "Категория не найдена");


        $ids = $category->products
            ->get()
            ->pluck("id");

        $category->products->detach(array_values($ids));

        $tmpCategory = $category;
        $category->delete();

        return new CategoryResource($tmpCategory);
    }

    /**
     * @throws HttpException
     */
    public function changeCategoryStatus($categoryId): CategoryResource
    {
        $category = Category::query()
            ->with(["products"])
            ->find($categoryId);

        if (is_null($category))
            throw new HttpException(404, "Категория не найдена");

        $category->is_active = !$category->is_active ?? false;
        $category->save();

        return new CategoryResource($category);
    }


    public function exportAllProducts($data = null)
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();
/*
        $name = Str::uuid();

        $date = Carbon::now()->format("Y-m-d H-i-s");

     //   Excel::store(new ProductExport($tenant->id), "$name.xls", "public", \Maatwebsite\Excel\Excel::XLS);

        BotMethods::bot()
            ->whereBot($tenant)
            ->sendDocument($tenantUser->telegram_chat_id,
                "Экспорт товаров",
                InputFile::create(
                    storage_path("app/public") . "/$name.xls",
                    "products-export-$date.xls"
                )
            );

        unlink(storage_path("app/public") . "/$name.xls");*/

    }

    /**
     * @throws HttpException
     */
    public function removeAllProducts(): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();


        $products = Product::query()
            ->with(["categories", "attributes"])
            ->where("tenant_id", $tenant->id)
            ->get();

        if (empty($products))
            throw new HttpException(404, "Продукты не найден");

        $baskets = Basket::query()
            ->where("tenant_id", $tenant->id)
            ->get();

        if (!empty($baskets))
            foreach ($baskets as $basket)
                $basket->delete();

        foreach ($products as $product) {
            $options = $product->attributes;

            if (!empty($options))
                foreach ($options as $option)
                    $option->delete();

            $categories = $product->categories;

            $tmp = [];
            if (!empty($categories))
                foreach ($categories as $category)
                    $tmp[] = $category->id;

            $product->categories()->detach($tmp);

            $product->delete();
        }


    }

    /**
     * @throws HttpException
     */
    public function destroyCategory($categoryId): CategoryResource
    {
        $category = Category::query()
            ->find($categoryId);

        if (is_null($category))
            throw new HttpException(404, "Категория не найден");

        $tmpCategory = $category;
        $category->delete();

        return new CategoryResource($tmpCategory);
    }

    /**
     * @throws HttpException
     */
    public function duplicate($productId): ProductResource
    {
        $product = Product::query()
            ->with(["categories", "attributes"])
            ->find($productId);

        if (is_null($product))
            throw new HttpException(404, "Продукт не найден");

        $newProduct = $product->replicate();
        $newProduct->save();

        if (!empty($product->categories)) {
            $tmp = [];
            foreach ($product->categories as $category)
                $tmp[] = $category->id;

            $newProduct->categories()->sync($tmp);
        }

        if (!empty($product->attributes))
            foreach ($product->attributes as $option)
                ProductAttribute::query()
                    ->create([
                        'name' => $option->name,
                        'value' => $option->value,
                        'section' => $option->section,
                        'product_id' => $newProduct->id
                    ]);


        return new ProductResource($newProduct);
    }



    /**
     * @throws HttpException
     */
    public function productsInCategory($categoryId, $search = null, $size = null): ProductCollection
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $size = $size ?? config('app.results_per_page');

        $products = Product::query()
            ->with(["categories", "attributes"])
            ->where("tenant_id", $tenant->id);

        if (!is_null($search))
            $products = $products
                ->where(function ($q) use ($search) {
                    $q->where("title", "like", "%$search%")
                        ->orWhere("description", "like", "%$search%");
                });

        $products = $products
            ->whereRelation('categories', 'id', $categoryId)
            ->orderBy("created_at", "DESC")
            ->paginate($size);

        return new ProductCollection($products);
    }

    /**
     * @throws HttpException
     */
    public function category($categoryId): CategoryResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $category = Category::query()
            ->where("tenant_id", $tenant->id)
            ->where("id", $categoryId)
            ->first();

        return new CategoryResource($category);
    }

    /**
     * @param array $data
     * @return void
     */
    private function contactsPrepare(array $data): void
    {
        $vowels = ["(", ")", "-"];
        $filteredPhone = !is_null($data["phone"] ?? $tenantUser->phone ?? null) ?
            str_replace($vowels, "", $data["phone"] ?? $tenantUser->phone) : null;

        $tenantUser->name = $data["name"] ?? $tenantUser->name ?? null;
        $tenantUser->phone = $filteredPhone;
        $tenantUser->save();
    }
}
