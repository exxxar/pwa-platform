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
     * 🆕 Получение полного списка продуктов и коллекций в корзине
     */
    public function productsInBasket(): array
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        if (!$tenantUser) {
            return [
                'items' => [],
                'items_count' => 0,
                'total_price' => 0,
            ];
        }

        // 1. Получаем все активные записи корзины с необходимыми связями
        // 1. Получаем все активные записи корзины с необходимыми связями
        $basketItems = Basket::query()
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $tenantUser->id)
            ->whereNull('ordered_at')
            ->whereNull('table_approved_at')
            ->with([
                // 🛡️ ИСПРАВЛЕНО: убрали weight_config, добавили config
                'product:id,name,price,old_price,images,is_weight_product,config,tenant_id',
                'collection:id,name,image,pricing_type,fixed_price',
            ])
            ->get();

        // 🆕 Оптимизация N+1: Собираем все ID товаров из всех коллекций заранее
        $allCollectionProductIds = [];
        foreach ($basketItems as $item) {
            // Безопасно получаем массив params (на случай если cast не сработал)
            $params = is_array($item->params) ? $item->params : (json_decode($item->params, true) ?? []);

            if ($item->collection_id && !empty($params['ids']) && is_array($params['ids'])) {
                // 🛡️ КРИТИЧЕСКИ ВАЖНО: Приводим все ID к строке для надежного сравнения
                $ids = array_map('strval', $params['ids']);
                $allCollectionProductIds = array_merge($allCollectionProductIds, $ids);
            }
        }
        $allCollectionProductIds = array_unique($allCollectionProductIds);

        // 🆕 Делаем ОДИН запрос к БД, чтобы получить цены всех нужных товаров
        $productsPriceMap = [];
        if (!empty($allCollectionProductIds)) {
            $products = Product::whereIn('id', $allCollectionProductIds)
                ->select('id', 'price')
                ->get();

            foreach ($products as $product) {
                // 🛡️ Ключ массива обязательно строка, чтобы совпадать с JSON декодированием
                $productsPriceMap[(string)$product->id] = (float)($product->price ?? 0);
            }


        }

        // 2. Форматируем данные для фронтенда
        $formattedItems = $basketItems->map(function ($item) use ($tenant, $productsPriceMap) {
            // Безопасно получаем params
            $params = is_array($item->params) ? $item->params : (json_decode($item->params, true) ?? []);
            $extraCharge = (float)($params['extra_charge'] ?? 0);

            $baseData = [
                'basket_id' => $item->id,
                'count' => $item->count,
                'comment' => $item->comment,
                'params' => $params,
                'tenant_partner_id' => $item->tenant_partner_id,
                'extra_charge' => $extraCharge,
            ];

            // Сценарий А: Это обычный товар
            if ($item->product_id && $item->product) {
                $product = $item->product;
                $price = (float)($product->price ?? 0);
                $finalPrice = $price + $extraCharge;

                return array_merge($baseData, [
                    'type' => 'product',
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->images[0] ?? null,
                    'price' => $price,
                    'final_price' => $finalPrice,
                    'total_price' => $finalPrice * $item->count,
                    'is_weight_product' => $product->is_weight_product ?? false,
                ]);
            }

            // Сценарий Б: Это коллекция (подборка)
            if ($item->collection_id && $item->collection) {
                $collection = $item->collection;
                $selectedProductIds = $params['ids'] ?? [];
                $collectionPrice = 0.0;

                // Если цена фиксированная, берем её
                if ($collection->pricing_type === 'fixed' && $collection->fixed_price) {
                    $collectionPrice = (float)$collection->fixed_price;
                }
                // Иначе считаем сумму выбранных товаров внутри коллекции
                elseif (is_array($selectedProductIds) && !empty($selectedProductIds)) {
                    foreach ($selectedProductIds as $pid) {
                        // 🛡️ Надежное извлечение цены по строковому ключу
                        $collectionPrice += $productsPriceMap[(string)$pid] ?? 0.0;
                    }
                }

                // Добавляем наценку партнера к цене коллекции, если она есть
                $finalCollectionPrice = $collectionPrice + $extraCharge;

                return array_merge($baseData, [
                    'type' => 'collection',
                    'collection_id' => $collection->id,
                    'name' => $collection->name,
                    'image' => $collection->image ?? null,
                    'selected_product_ids' => $selectedProductIds,
                    'price' => $collectionPrice,
                    'final_price' => $finalCollectionPrice,
                    'total_price' => $finalCollectionPrice * $item->count,
                ]);
            }

            // Если запись "битая" (нет ни товара, ни коллекции), возвращаем null
            return null;
        })->filter()->values(); // Убираем null и сбрасываем ключи массива

        // 3. Считаем итоги
        $itemsCount = $formattedItems->sum('count');
        $totalPrice = $formattedItems->sum('total_price');

        return [
            'items' => $formattedItems,
            'items_count' => $itemsCount,
            'total_price' => round($totalPrice, 2),
        ];
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
            ->with(['product:id,name,price,price,images'])
            ->get();

        return [
            'items_count' => $cartItems->sum('count'),
            'total_price' => $cartItems->sum(function ($item) {
                $price = $item->product->price ?? 0;
                return $price * $item->count;
            }),
            'items' => $cartItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'count' => $item->count,
                    'price' => $item->product->price ?? 0,
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
    public function checkout(array $data, mixed $uploadedImage = null): array
    {
        $this->tenant = app('tenant');
        $this->tenantUser = Auth::guard('tenant')->user();
        $this->data = $data;
        $this->uploadedImage = $uploadedImage;

        $this->storeClientInfoAsContact();

        return $this->foodShopCheckout();
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
            "partner_id" => "nullable|integer", // 🆕 Обязательно указываем партнера
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $partnerId = (int)$data["partner_id"];

        // Безопасное приведение к объекту
        $collectionData = is_array($data["product_collection"])
            ? (object)$data["product_collection"]
            : $data["product_collection"];

        $variantId = $collectionData->variant_id ?? null;
        $collectionId = $collectionData->collection_id ?? null;

        // 🆕 Добавили проверку partner_id для безопасности
        $collection = Collection::query()
            ->where("tenant_id", $partnerId ?? $tenant->id)
            ->where("id", $collectionId)
            ->first();


        if (is_null($collection)) {
            throw new HttpException(404, "Коллекция не найдена в системе!");
        }

        // 🆕 Добавили фильтрацию по partner_id, чтобы не задублировать запись другого ресторана
        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();

        $ids = collect($collectionData->selected_products ?? [])
            ->pluck("product_id")
            ->toArray();

        $tableWithClient = Table::query()
            ->where("tenant_id", $tenant->id)
            ->whereNull("closed_at")
            ->whereHas('clients', function ($query) use ($tenantUser) {
                $query->where('tenant_users.id', $tenantUser->id);
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

        if ($productsInBasket->isEmpty() || is_null($variantId)) {
            Basket::query()->create($basketData);

        } else {

            foreach ($productsInBasket as $pib) {
                $params = is_array($pib->params) ? (object)$pib->params : $pib->params;

                if (!is_null($variantId) && ($params->variant_id ?? null) == $variantId) {

                    $pib->count++;
                    $pib->save();
                    break;
                }
            }
        }

    }


    public function incrementCollection(array $data): void
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $variantId = $data["variant_id"] ?? null;
        $collectionId = $data["collection_id"] ?? null;

        // 🆕 Добавили проверку partner_id для безопасности
        $collection = Collection::query()
            ->where("id", $collectionId)
            ->first();


        if (is_null($collection)) {
            throw new HttpException(404, "Коллекция не найдена в системе!");
        }

        // 🆕 Добавили фильтрацию по partner_id, чтобы не задублировать запись другого ресторана
        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();


        foreach ($productsInBasket as $pib) {
            $params = is_array($pib->params) ? (object)$pib->params : $pib->params;

            if (!is_null($variantId) && ($params->variant_id ?? null) == $variantId) {

                $pib->count++;
                $pib->save();
                break;
            }
        }


    }


    public function removeCollectionFromBasket(array $data): void
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $variantId = $data["variant_id"] ?? null;
        $collectionId = $data["collection_id"] ?? null;

        // 🆕 Добавили проверку partner_id для безопасности
        $collection = Collection::query()
            ->where("id", $collectionId)
            ->first();

        if (is_null($collection)) {
            throw new HttpException(404, "Коллекция не найдена в системе!");
        }

        // 🆕 Добавили фильтрацию по partner_id, чтобы не задублировать запись другого ресторана
        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();


        foreach ($productsInBasket as $pib) {
            $params = is_array($pib->params) ? (object)$pib->params : $pib->params;

            if (!is_null($variantId) && ($params->variant_id ?? null) == $variantId) {
                    $pib->delete();
                break;
            }
        }


    }

    public function decrementCollection(array $data): void
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $variantId = $data["variant_id"] ?? null;
        $collectionId = $data["collection_id"] ?? null;

        // 🆕 Добавили проверку partner_id для безопасности
        $collection = Collection::query()
            ->where("id", $collectionId)
            ->first();


        if (is_null($collection)) {
            throw new HttpException(404, "Коллекция не найдена в системе!");
        }

        // 🆕 Добавили фильтрацию по partner_id, чтобы не задублировать запись другого ресторана
        $productsInBasket = Basket::query()
            ->where("collection_id", $collection->id)
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNull("table_approved_at")
            ->get();


        foreach ($productsInBasket as $pib) {
            $params = is_array($pib->params) ? (object)$pib->params : $pib->params;

            if (!is_null($variantId) && ($params->variant_id ?? null) == $variantId) {

                if ($pib->count > 1) {
                    $pib->count--;
                    $pib->save();
                }

                if ($pib->count == 1)
                    $pib->delete();
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

        $config = $tenant->settings ?? [];
        $hasPartners = $config["partners"]["is_active"] ?? false;


        $ids = $hasPartners
            ? [$tenant->id, ...$tenant->partners()->get()->pluck("tenant_partner_id")]
            : [$tenant->id];


        $productId = $data["product_id"] ?? null;
        $productCount = $data["count"] ?? 1;
        $tableId = $data["table_id"] ?? null;

        $product = Product::query()
            ->withTrashed()
            ->whereIn("tenant_id", $ids)
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


            $productInBasket->tenant_partner_id = $product->tenant_id == $tenant->id ? null : $product->tenant_id;
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
            $params = is_array($pib->params) ? (object)$pib->params : $pib->params;

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
            $config = (object)$config;
        }

        if (!is_object($config)) {
            $config = (object)[];
        }

        return (object)[
            'min' => max(1, (int)($config->min ?? 100)),
            'max' => max(0, (int)($config->max ?? 0)),
            'step' => max(1, (int)($config->step ?? 50)),
        ];
    }
}
