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

    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    public function __construct()
    {
    }

    /**
     * 🆕 Краткая информация о корзине
     */
    public function getCartSummary(int $tenantId, ?int $userId): array
    {
        if (!$userId) {
            return [
                'items_count' => 0,
                'total_price' => 0,
                'items' => [],
            ];
        }

        $cartItems = Basket::where('tenant_id', $tenantId)
            ->where('tenant_user_id', $userId)
            ->whereNull('ordered_at')
            ->with(['product:id,name,price,current_price,images'])
            ->get();

        return [
            'items_count' => $cartItems->sum('count'),
            'total_price' => $cartItems->sum(function ($item) {
                $price = $item->product->current_price ?? $item->product->price ?? 0;
                return $price * $item->count;
            }),
            'items' => $cartItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'count' => $item->count,
                    'price' => $item->product->current_price ?? $item->product->price ?? 0,
                    'name' => $item->product->name,
                    'image' => $item->product->images[0] ?? null,
                ];
            }),
        ];
    }

    /**
     * Оформление заказа
     * @throws ValidationException
     */
    public function checkout(array $data, mixed $uploadedImage = null): ?string
    {
        $this->tenant = app('tenant');
        $this->tenantUser = Auth::guard('tenant')->user();
        $this->data = $data;
        $this->uploadedImage = $uploadedImage;

        $this->storeClientInfoAsContact();

        return $this->foodShopCheckout();
    }

    /**
     * Получение товаров в корзине
     * @throws HttpException
     */
    public function productsInBasket($tableId = null): BasketCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $query = Basket::query()
            ->with(['product' => fn($q) => $q->withTrashed()])
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("table_approved_at")
            ->whereNull("ordered_at");

        if (!is_null($tableId)) {
            $query->where("table_id", $tableId);
        }

        $allProductsInBasket = $query->get();

        // Удаляем товары, которые были удалены из каталога
        foreach ($allProductsInBasket as $item) {
            if (!is_null($item->product?->deleted_at)) {
                $item->delete();
            }
        }

        // Перезагружаем коллекцию после удаления
        $allProductsInBasket = $query->get();

        return new BasketCollection($allProductsInBasket);
    }

    /**
     * Добавление подборки (коллекции) в корзину
     * @throws ValidationException|HttpException
     */
    public function addCollection(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "product_collection" => "required",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Безопасное приведение к объекту (исправлен баг с массивами)
        $collectionData = is_array($data["product_collection"])
            ? (object) $data["product_collection"]
            : $data["product_collection"];

        $variantId = $data["variant_id"] ?? null;
        $collectionId = $collectionData->id ?? null;

        $collection = Collection::query()
            ->where("tenant_id", $tenant->id)
            ->where("id", $collectionId)
            ->first();

        if (is_null($collection)) {
            throw new HttpException(404, "Коллекция не найдена в системе!");
        }

        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();

        $ids = collect($collectionData->products ?? [])
            ->where("is_checked", true)
            ->pluck("id")
            ->toArray();

        $tableWithClient = Table::query()
            ->where("tenant_id", $tenant->id)
            ->whereNull("closed_at")
            ->whereHas('clients', function ($query) use ($tenantUser) {
                $query->where('tenant_users.id', $tenantUser->id);  // ← указали таблицу
            })->first();

        $basketData = [
            'collection_id' => $collection->id,
            'count' => 1,
            'tenant_user_id' => $tenantUser->id,
            'tenant_id' => $tenant->id,
            'ordered_at' => null,
            'table_id' => $tableWithClient?->id,
            'params' => [
                "variant_id" => Str::uuid()->toString(),
                "ids" => $ids,
            ],
        ];

        if ($productsInBasket->isEmpty()) {
            Basket::query()->create($basketData);
        } else {
            $findVariant = false;

            foreach ($productsInBasket as $pib) {
                $params = is_array($pib->params) ? (object) $pib->params : $pib->params;

                if (!is_null($variantId) && ($params->variant_id ?? null) == $variantId) {
                    $findVariant = true;
                    $pib->count++;
                    $pib->save();
                    break;
                }
            }

            if (!$findVariant) {
                Basket::query()->create($basketData);
            }
        }
    }

    /**
     * Увеличение количества подборки
     * @throws ValidationException|HttpException
     */
    public function incrementCollection(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "collection_id" => "required",
            "variant_id" => "required",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $variantId = $data["variant_id"];
        $collectionId = $data["collection_id"];

        $collection = Collection::query()
            ->where("tenant_id", $tenant->id)
            ->where("id", $collectionId)
            ->first();

        if (is_null($collection)) {
            throw new HttpException(404, "Коллекция не найдена в системе!");
        }

        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();

        foreach ($productsInBasket as $pib) {
            $params = is_array($pib->params) ? (object) $pib->params : $pib->params;

            if (($params->variant_id ?? null) == $variantId) {
                $pib->count++;
                $pib->save();
                break;
            }
        }
    }

    /**
     * Уменьшение количества товара (через /decrement/{id})
     * @throws HttpException
     */
    public function decrement($itemId): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        // ИСПРАВЛЕНИЕ: Сначала находим товар, ПОТОМ проверяем его свойства
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

        // ИСПРАВЛЕНИЕ: Сначала проверяем null, потом обращаемся к свойствам
        if (is_null($productInBasket)) {
            throw new HttpException(404, "Товар в корзине не найден!");
        }

        if (!is_null($productInBasket->product?->deleted_at)) {
            $productInBasket->delete();
            throw new HttpException(403, "Товар удалён из каталога!");
        }

        $product = $productInBasket->product;
        $productCount = 1;

        if ($product?->is_weight_product) {
            $weightConfig = $this->parseWeightConfig($product->weight_config);

            $min = $weightConfig->min;
            $step = $weightConfig->step;

            // Для весовых товаров уменьшаем на шаг
            $productCount = $step;

            // Если текущее количество <= min, то удаляем полностью
            if (($productInBasket->count ?? 0) <= $min) {
                $productInBasket->delete();
                return;
            }
        }

        if ($productInBasket->count - $productCount > 0) {
            $productInBasket->count -= $productCount;
            $productInBasket->save();
        } else {
            $productInBasket->delete();
        }
    }

    /**
     * Увеличение количества товара (через /increment/{id})
     * @throws HttpException
     */
    public function increment($itemId): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        // ИСПРАВЛЕНИЕ: Сначала находим товар, ПОТОМ проверяем его свойства
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

        // ИСПРАВЛЕНИЕ: Сначала проверяем null
        if (is_null($productInBasket)) {
            throw new HttpException(404, "Товар в корзине не найден!");
        }

        if (!is_null($productInBasket->product?->deleted_at)) {
            $productInBasket->delete();
            throw new HttpException(403, "Товар удалён из каталога!");
        }

        $product = $productInBasket->product;
        $productCount = 1;

        if ($product?->is_weight_product) {
            $weightConfig = $this->parseWeightConfig($product->weight_config);

            $min = $weightConfig->min;
            $max = $weightConfig->max;
            $step = $weightConfig->step;

            // Если количество 0 — ставим минимум, иначе прибавляем шаг
            $productCount = $productInBasket->count == 0 ? $min : $step;

            // Проверка максимума
            if ($max > 0 && ($productInBasket->count + $step) > $max) {
                throw new HttpException(400, "Достигнут максимальный вес товара ({$max}г)");
            }
        }

        $productInBasket->count += $productCount;
        $productInBasket->save();
    }

    /**
     * Добавление комментария к товару
     * @throws ValidationException|HttpException
     */
    public function addProductComment(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "product_id" => "required",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $productId = $data["product_id"] ?? null;

        $productInBasket = Basket::query()
            ->where("product_id", $productId)
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
     * Добавление и инкремент товара
     * @throws ValidationException|HttpException
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
        $tableId = $data["table_id"] ?? null;

        $product = Product::query()
            ->withTrashed()
            ->whereIn("tenant_id", $botIds)
            ->where("id", $productId)
            ->first();

        if (is_null($product)) {
            throw new HttpException(404, "Продукт не найден в системе!");
        }

        if (!is_null($product->deleted_at)) {
            throw new HttpException(403, "Продукт недоступен!");
        }

        $productInBasket = Basket::query()
            ->where("product_id", $product->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->first();

        // ИСПРАВЛЕНИЕ: Инициализируем tableWithClient заранее
        $tableWithClient = null;
        if (!is_null($tableId)) {
            $tableWithClient = Table::query()
                ->where("tenant_id", $tenant->id)
                ->where("id", $tableId)
                ->whereNull("closed_at")
                ->first();
        } else {
            // Ищем стол с клиентом, если tableId не указан
            $tableWithClient = Table::query()
                ->where("tenant_id", $tenant->id)
                ->whereNull("closed_at")
                ->whereHas('clients', function ($query) use ($tenantUser) {
                    $query->where('tenant_users.id', $tenantUser->id);  // ← указали таблицу
                })->first();
        }

        $isWeightProduct = $product->is_weight_product ?? false;
        $weightConfigData = null;

        // Логика для весовых товаров
        if ($isWeightProduct) {
            $weightConfig = $this->parseWeightConfig($product->weight_config);

            $min = $weightConfig->min;
            $max = $weightConfig->max;
            $step = $weightConfig->step;

            $weightConfigData = [
                'min' => $min,
                'max' => $max,
                'step' => $step,
            ];

            if (is_null($productInBasket)) {
                $productCount = $min;
            } else {
                $productCount = $step;

                if ($max > 0 && ($productInBasket->count + $step) > $max) {
                    $productCount = max(0, $max - $productInBasket->count);

                    if ($productCount === 0) {
                        throw new HttpException(400, "Достигнут максимальный вес товара ({$max}г)");
                    }
                }
            }
        }

        // Создание или обновление записи
        if (is_null($productInBasket)) {
            $extraCharge = 0;
            if ($product->tenant_id != $tenant->id) {
                $partner = Partner::query()
                    ->where("tenant_id", $tenant->id)
                    ->where("tenant_partner_id", $product->tenant_id)
                    ->first();

                $extraCharge = $partner?->extra_charge ?? 0;
            }

            $basketData = [
                'product_id' => $product->id,
                'count' => $productCount,
                'tenant_user_id' => $tenantUser->id,
                'table_id' => $tableWithClient?->id,
                'tenant_id' => $tenant->id,
                'tenant_partner_id' => $product->tenant_id == $tenant->id ? null : $product->tenant_id,
                'params' => array_filter([
                    "extra_charge" => $extraCharge,
                    "weight_config" => $weightConfigData,
                ]),
                'ordered_at' => null,
                'table_approved_at' => null,
            ];

            Basket::query()->create($basketData);
        } else {
            if (!is_null($tableWithClient)) {
                $productInBasket->table_id = $tableWithClient->id;
            }
            $productInBasket->count += $productCount;
            $productInBasket->save();
        }
    }

    /**
     * Очистка корзины
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
     * Удаление товара из корзины
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

        if (is_null($basket)) {
            throw new HttpException(404, "Элемент не найден!");
        }

        $basket->delete();
    }

    /**
     * Уменьшение/удаление подборки
     * @throws ValidationException|HttpException
     */
    public function decrementAndRemoveCollection(array $data): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "collection_id" => "required",
            "variant_id" => "required",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $variantId = $data["variant_id"];
        $collectionId = $data["collection_id"];

        $collection = Collection::query()
            ->where("tenant_id", $tenant->id)
            ->where("id", $collectionId)
            ->first();

        if (is_null($collection)) {
            throw new HttpException(404, "Коллекция не найдена в системе!");
        }

        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();

        foreach ($productsInBasket as $pib) {
            $params = is_array($pib->params) ? (object) $pib->params : $pib->params;

            if (($params->variant_id ?? null) == $variantId) {
                if ($pib->count - 1 > 0) {
                    $pib->count--;
                    $pib->save();
                } else {
                    $pib->delete();
                }
                break;
            }
        }
    }

    /**
     * Уменьшение/удаление товара
     * ИСПРАВЛЕНО: убрана несуществующая переменная $data
     * @throws HttpException
     */
    public function decrementAndRemoveProduct($productId): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        // ИСПРАВЛЕНИЕ: используем только $productId из параметра
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
        $isWeightProduct = $product?->is_weight_product ?? false;

        if ($isWeightProduct) {
            $weightConfig = $this->parseWeightConfig(
                $productInBasket->params['weight_config'] ?? $product->weight_config ?? []
            );

            $min = $weightConfig->min;
            $step = $weightConfig->step;

            $newCount = $productInBasket->count - $step;

            if ($newCount < $min) {
                $productInBasket->delete();
                return;
            }

            $productInBasket->count = $newCount;
        } else {
            $productInBasket->count -= 1;

            if ($productInBasket->count <= 0) {
                $productInBasket->delete();
                return;
            }
        }

        $productInBasket->save();
    }

    // ==========================================
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ==========================================

    /**
     * Безопасный парсинг конфигурации веса
     */
    protected function parseWeightConfig($config): object
    {
        if (is_string($config)) {
            $config = json_decode($config, true) ?? [];
        }

        if (is_array($config)) {
            $config = (object) $config;
        }

        if (!is_object($config)) {
            $config = (object) [];
        }

        return (object) [
            'min' => max(1, (int) ($config->min ?? 100)),
            'max' => max(0, (int) ($config->max ?? 0)),
            'step' => max(1, (int) ($config->step ?? 50)),
        ];
    }
}
