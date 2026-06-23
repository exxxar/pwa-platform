<?php

namespace App\Services;

use App\Http\Resources\BasketCollection;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Partner;
use App\Models\Tenant\Collection;
use App\Models\Tenant\Product;
use App\Models\Tenant\Table;
use App\Services\Helpers\BasketHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use Symfony\Component\HttpKernel\Exception\HttpException;


class BasketService
{

    use BasketHelper;

    protected array $data;
    protected $tenant;
    protected $tenantUser;
    protected $uploadedImage = null;

    private const PAYMENT_TYPES = ["Онлайн в приложении", "Картой в заведении", "Переводом", "Наличными", "СБП"];

    public static function call(): self
    {
        return app(self::class);
    }

    /**
     * Универсальный прокси
     */
    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }


    public function __construct()
    {
    }


    /**
     * @throws ValidationException
     */
    public function checkout(array $data, mixed $uploadedImage = null): ?object
    {

        $this->tenant = app('tenant');
        $this->tenantUser = Auth::guard('tenant')->user();

        $this->data = $data;
        $this->uploadedImage = $uploadedImage;

        $this->storeClientInfoAsContact();

        //$displayType = $this->data["display_type"] ?? 0;

        return $this->foodShopCheckout();

    }

    /**
     * @throws HttpException
     */
    public function productsInBasket($tableId = null): BasketCollection
    {


        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();


        $allProductsInBasket = is_null($tableId) ? Basket::query()
            ->with(['product' => fn($q) => $q->withTrashed()])
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("table_approved_at")
            ->whereNull("ordered_at")
            ->get() :
            Basket::query()
                ->with(['product' => fn($q) => $q->withTrashed()])
                ->where("tenant_user_id", $tenantUser->id)
                ->where("table_id", $tableId)
                ->where("tenant_id", $tenant->id)
                ->whereNull("table_approved_at")
                ->whereNull("ordered_at")
                ->get();

        foreach ($allProductsInBasket as $item) {
            if (!is_null($item->product->deleted_at)) {
                $item->delete();
            }
        }

        return new BasketCollection($allProductsInBasket);
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function addCollection(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "product_collection" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $Collection = (object)$data["product_collection"];

        $variantId = $data["variant_id"] ?? null;

        $CollectionId = $Collection->id ?? null;

        $collection = Collection::query()
            ->where("tenant_id", $tenant->id)
            ->where("id", $CollectionId)
            ->first();

        if (is_null($collection))
            throw new HttpException(404, "Коллекция не найдена в системе!");


        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();

        $ids = Collection::make($Collection->products)
            ->where("is_checked", true)
            ->pluck("id");

        $tableWithClient = Table::query()
            ->where("tenant_id", $tenant->id)
            ->whereNull("closed_at")
            ->whereHas('clients', function ($query) {
                $query->where('id', $tenantUser->id);
            })->first();

        $tmp = [
            'collection_id' => $collection->id,
            'count' => 1,
            'tenant_user_id' => $tenantUser->id,
            'tenant_id' => $tenant->id,
            'ordered_at' => null,
            'table_id' => $tableWithClient->id ?? null,
            'params' => (object)[
                "variant_id" => Str::uuid(),
                "ids" => $ids->toArray()
            ],
        ];

        if (count($productsInBasket) == 0) {
            Basket::query()->create($tmp);
        } else {

            $findVariant = false;
            foreach ($productsInBasket as $pib) {
                $params = (object)($pib->params ?? null);

                if (($params->variant_id ?? null) == $variantId && !is_null($variantId)) {
                    $findVariant = true;
                    $pib->count++;
                    $pib->save();
                }

            }

            if (!$findVariant)
                Basket::query()->create($tmp);


        }

    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function incrementCollection(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "collection_id" => "required",
            "variant_id" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $variantId = $data["variant_id"] ?? null;

        $CollectionId = $data["collection_id"] ?? null;

        $collection = Collection::query()
            ->where("tenant_id", $tenant->id)
            ->where("id", $CollectionId)
            ->first();

        if (is_null($collection))
            throw new HttpException(404, "Коллекция не найдена в системе!");


        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();


        if (count($productsInBasket) != 0) {
            foreach ($productsInBasket as $pib) {
                $params = (object)($pib->params ?? null);

                if (($params->variant_id ?? null) == $variantId) {
                    $pib->count++;
                    $pib->save();


                }
            }

        }
    }

    /**
     * @throws HttpException
     */
    public function decrement($itemId): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $productInBasket = Basket::query()
            ->with(['product' => fn($q) => $q->withTrashed()])
            ->where(function ($q) use ($itemId) {
                return $q->where("product_id", $itemId)
                    ->orWhere("collection_id", $itemId);
            })
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->first();

        if (!is_null($productInBasket->product->deleted_at ?? null)) {
            $productInBasket->delete();
            throw new HttpException(403, "Товар не найден!");
        }

        if (is_null($productInBasket))
            throw new HttpException(404, "Товар в корзине не найден!");

        $productCount = 1;

        $product = $productInBasket->product;

        if ($product->is_weight_product ?? false) {

            $weightConfig = (object)$product->weight_config ?? null;
            $min = $weightConfig->min ?? 0;
            $max = $weightConfig->max ?? 0;
            $step = $weightConfig->step ?? 0;

            $productCount = is_null($productInBasket) ? $min : $step;

            if (($productInBasket->count ?? 0) <= $min)
                $productCount = $min;

        }

        if ($productInBasket->count - $productCount > 0) {
            $productInBasket->count -= $productCount;
            $productInBasket->save();
        } else
            $productInBasket->delete();
    }

    /**
     * @throws HttpException
     */
    public function increment($itemId): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $productInBasket = Basket::query()
            ->with(['product' => fn($q) => $q->withTrashed()])
            ->where(function ($q) use ($itemId) {
                return $q->where("product_id", $itemId)
                    ->orWhere("collection_id", $itemId);
            })
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->first();

        if (is_null($productInBasket)) {
            throw new HttpException(404, "Товар в корзине не найден!");
        }

        if (!is_null($productInBasket->product->deleted_at ?? null)) {
            $productInBasket->delete();
            throw new HttpException(403, "Товар не найден!");
        }


        $productCount = 1;
        $product = $productInBasket->product;

        if ($product && $product->is_weight_product ?? false) {

            $weightConfig = is_array($product->weight_config)
                ? (object)$product->weight_config
                : json_decode($product->weight_config ?? '{}');

            $min = $weightConfig->min ?? 0;
            $max = $weightConfig->max ?? 0;
            $step = $weightConfig->step ?? 0;

            $productCount = $productInBasket->count == 0 ? $min : $step;

            if (($productInBasket->count ?? 0) >= $max && $max > 0)
                $productCount = 0;
        }

        $productInBasket->count += $productCount;
        $productInBasket->save();

    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function addProductComment(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "product_id" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $botIds = [$tenant->id, ...$tenant->partners()->get()->pluck("tenant_partner_id")];

        $productId = $data["product_id"] ?? null;

        $product = Product::query()
            ->whereIn("tenant_id", $botIds)
            ->where("id", $productId)
            ->first();

        if (is_null($product))
            throw new HttpException(404, "Продукт не найден в системе!");

        $productInBasket = Basket::query()
            ->where("product_id", $product->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->first();

        if (!is_null($productInBasket)) {
            $productInBasket->comment = $data["comment"] ?? null;
            $productInBasket->save();

        }


    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function addAndIncrementProduct(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "product_id" => "required",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $config = $tenant->config ?? [];
        $hasPartners = $config["partners"]["is_active"] ?? false;

        $botIds = $hasPartners
            ? [$tenant->id, ...$tenant->partners()->get()->pluck("tenant_partner_id")]
            : [$tenant->id];

        $productId = $data["product_id"] ?? null;
        $productCount = $data["count"] ?? 1;
        $tableId = null;

        $product = Product::query()
            ->withTrashed()
            ->whereIn("tenant_id", $botIds)
            ->where("id", $productId)
            ->first();

        if (is_null($product)) {
            throw new HttpException(404, "Продукт не найден в системе!");
        }

        if (!is_null($product->deleted_at)) {
            $product->delete();
            throw new HttpException(403, "Продукт недоступен!");
        }

        $productInBasket = Basket::query()
            ->where("product_id", $product->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->first();

        if (!is_null($tableId)) {
            $tableWithClient = Table::query()
                ->where("tenant_id", $tenant->id)
                ->where("id", $tableId)
                ->whereNull("closed_at")
                ->first();
        }

        $isWeightProduct = $product->is_weight_product ?? false;

        // ==========================================
        // ЛОГИКА ДЛЯ ВЕСОВЫХ ТОВАРОВ
        // ==========================================
        if ($isWeightProduct) {
            // Безопасно получаем конфигурацию веса
            $weightConfig = $product->weight_config ?? [];
            if (is_string($weightConfig)) {
                $weightConfig = json_decode($weightConfig, true) ?? [];
            }
            $weightConfig = (object) $weightConfig;

            // Значения по умолчанию (защита от некорректных настроек)
            $min = max(1, (int) ($weightConfig->min ?? 100));   // Минимум 1 грамм
            $max = max(0, (int) ($weightConfig->max ?? 0));     // 0 = без лимита
            $step = max(1, (int) ($weightConfig->step ?? 50));  // Минимум 1 грамм шаг

            // Определяем, сколько добавлять
            if (is_null($productInBasket)) {
                // Первое добавление — берём минимум
                $productCount = $min;
            } else {
                // Повторное добавление — прибавляем шаг
                $productCount = $step;

                // Проверяем, не превышен ли максимум
                if ($max > 0 && ($productInBasket->count + $step) > $max) {
                    // Добавляем только остаток до максимума
                    $productCount = max(0, $max - $productInBasket->count);

                    if ($productCount === 0) {
                        throw new HttpException(400, "Достигнут максимальный вес товара ({$max}г)");
                    }
                }
            }
        }

        // ==========================================
        // СОЗДАНИЕ ИЛИ ОБНОВЛЕНИЕ ЗАПИСИ В КОРЗИНЕ
        // ==========================================
        if (is_null($productInBasket)) {
            $extraCharge = 0;
            if ($product->tenant_id != $tenant->id) {
                $partner = Partner::query()
                    ->where("tenant_id", $tenant->id)
                    ->where("tenant_partner_id", $product->tenant_id)
                    ->first();

                $extraCharge = is_null($partner) ? 0 : ($partner->extra_charge ?? 0);
            }

            $productInBasket = Basket::query()->create([
                'product_id' => $product->id,
                'count' => $productCount,
                'tenant_user_id' => $tenantUser->id,
                'table_id' => $tableWithClient->id ?? null,
                'tenant_id' => $tenant->id,
                'tenant_partner_id' => $product->tenant_id == $tenant->id ? null : $product->tenant_id,
                'params' => [
                    "extra_charge" => $extraCharge,
                    // Сохраняем конфигурацию веса для фронта
                    "weight_config" => $isWeightProduct ? [
                        'min' => $min ?? 0,
                        'max' => $max ?? 0,
                        'step' => $step ?? 0,
                    ] : null,
                ],
                'ordered_at' => null,
                'table_approved_at' => null,
            ]);
        } else {
            if (!is_null($tableId)) {
                $productInBasket->table_id = $tableId;
            }
            $productInBasket->count += $productCount;
            $productInBasket->save();
        }
    }

    /**
     * @throws HttpException
     */
    public function clearBasket(): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        Basket::query()
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->delete();
    }

    /**
     * @throws HttpException
     */
    public function removeFromBasket($itemId): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $basket = Basket::query()
            ->where(function ($q) use ($itemId) {
                return $q->where("product_id", $itemId)
                    ->orWhere("collection_id", $itemId);
            })
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->first();

        if (is_null($basket))
            throw new HttpException(404, "Элемент не найден!");

        $basket->delete();
    }

    /**
     * @throws HttpException
     * @throws ValidationException
     */
    public function decrementAndRemoveCollection(array $data): void
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "collection_id" => "required",
            "variant_id" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $variantId = $data["variant_id"] ?? null;

        $CollectionId = $data["collection_id"] ?? null;

        $collection = Collection::query()
            ->where("tenant_id", $tenant->id)
            ->where("id", $CollectionId)
            ->first();

        if (is_null($collection))
            throw new HttpException(404, "Коллекция не найдена в системе!");

        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();

        if (count($productsInBasket) != 0) {
            foreach ($productsInBasket as $pib) {
                $params = (object)($pib->params ?? null);

                if (($params->variant_id ?? null) == $variantId) {
                    if ($pib->count - 1 > 0) {
                        $pib->count--;
                        $pib->save();
                    } else
                        $pib->delete();

                }
            }
        }
    }

    /**
     * @throws HttpException
     */
    public function decrementAndRemoveProduct($productId): void
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $productId = $data["product_id"] ?? $productId ?? null;


        $productInBasket = Basket::query()
            ->where("product_id", $productId)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->first();


        if (is_null($productInBasket)) {
            throw new HttpException(404, "Товар не найден в корзине!");
        }

        $product = $productInBasket->product;
        $isWeightProduct = $product->is_weight_product ?? false;

        if ($isWeightProduct) {
            // Получаем конфигурацию веса
            $weightConfig = $productInBasket->params['weight_config']
                ?? $product->weight_config
                ?? [];

            if (is_string($weightConfig)) {
                $weightConfig = json_decode($weightConfig, true) ?? [];
            }
            $weightConfig = (object) $weightConfig;

            $min = max(1, (int) ($weightConfig->min ?? 100));
            $step = max(1, (int) ($weightConfig->step ?? 50));

            // Для весовых товаров уменьшаем на шаг
            $newCount = $productInBasket->count - $step;

            // Если меньше минимума — удаляем из корзины
            if ($newCount < $min) {
                $productInBasket->delete();
                return;
            }

            $productInBasket->count = $newCount;
        } else {
            // Для обычных товаров
            $productInBasket->count -= 1;

            if ($productInBasket->count <= 0) {
                $productInBasket->delete();
                return;
            }
        }

        $productInBasket->save();
    }
}
