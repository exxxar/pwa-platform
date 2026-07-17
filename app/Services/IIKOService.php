<?php

namespace App\Services;

use App\Enums\IntegrationTypeEnum;
use App\Http\Resources\IntegrationResource;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Category;
use App\Models\Tenant\Integration;
use App\Models\Tenant\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IIKOService
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

    /**
     * @throws HttpException
     */
    public function getToken($apiLogin = null): mixed
    {

        $tenant = app('tenant');

        $iiko = $tenant->iiko()->first();

        if (is_null($iiko)) {
            $iiko = Integration::query()
                ->create([
                    'tenant_id' => $tenant->id,
                    'type'=>IntegrationTypeEnum::IIKO->value,
                    'credentials'=>json_encode([
                        'api_login' => $apiLogin,
                        'organization_id' => null,
                        'terminal_group_id' => null,
                    ])
                ]);
        }

        if (!is_null($iiko) && !is_null($apiLogin)) {
            $credentials = $iiko->credentials ?? [];
            $credentials["api_login"] = $apiLogin;
            $iiko->credentials = $credentials;
            $iiko->save();
        }

        if (is_null($iiko->credentials["api_login"] ?? null))
            throw new HttpException(400, "API логин не задан");

        $url = config('iiko.api_url');

        $result = Http::post("$url/1/access_token", [
            'apiLogin' => $iiko->credentials["api_login"],
        ]);

        if ($result->status() != 200)
            throw new HttpException($result->status(), $result->json("errorDescription"));

        return $result->json("token") ?? null;

    }

    /**
     * @throws HttpException
     */
    public function get(): IntegrationResource
    {
        $tenant = app('tenant');

        $iiko = $tenant->iiko()->first();

        if (is_null($iiko))
            $iiko = Integration::query()
                ->create([
                    'type'=>IntegrationTypeEnum::IIKO->value,
                    'tenant_id' => $tenant->id,
                    'credentials'=>json_encode([
                        'api_login' => null,
                        'organization_id' => null,
                        'terminal_group_id' => null,
                    ])
                ]);

        return new IntegrationResource($iiko);
    }

    /**
     * @throws HttpException
     */
    public function organizations($token): mixed
    {
        $url = config('iiko.api_url');

        $result = Http::asJson()
            ->withToken(trim($token))
            ->post("$url/1/organizations", [
                'organizationIds' => [],
                'returnAdditionalInfo' => true,
                'includeDisabled' => true,
                'returnExternalData' => ["string"],
            ]);


        return $result->json("organizations") ?? null;
    }

    /**
     * @throws HttpException
     */
    public function terminals($token, $organizationId): mixed
    {

        $url = config('iiko.api_url');

        $result = Http::asJson()
            ->withToken(trim($token))
            ->post("$url/1/terminal_groups", [
                'organizationIds' => [$organizationId],
                'includeDisabled' => true,
                'returnExternalData' => ["string"],
            ]);

        return $result->json("terminalGroups") ?? null;
    }

    /**
     * @throws HttpException
     */
    public function menus(): mixed
    {
        $url = config('iiko.api_url');

        $token = $this->getToken();

        $tenant = app('tenant');

        $iiko = $tenant->iiko()->first();

        $organizationId = $iiko->credentials["organization_id"] ?? null;

        if (is_null($organizationId))
            throw new HttpException(404, "Организация не найдена!");

        $result = Http::asJson()
            ->withToken(trim($token))
            ->post("$url/2/menu", [
                'organizationId' => $organizationId,
                'startRevision' => 0,

            ]);

        return $result->json("externalMenus") ?? null;
    }

    /**
     * @throws HttpException
     */
    public function products($menuId): mixed
    {


        $url = config('iiko.api_url');

        $token = $this->getToken();

        $tenant = app('tenant');

        $iiko = $tenant->iiko()->first();

        $organizationId = $iiko->credentials["organization_id"] ?? null;

        if (is_null($organizationId))
            throw new HttpException(404, "Организация не найдена!");

        $result = Http::asJson()
            ->withToken(trim($token))
            ->post("$url/2/menu/by_id", [
                'organizationIds' => [$organizationId],
                'externalMenuId' => $menuId,
            ]);


        return $result->json() ?? null;
    }

    /**
     * @throws HttpException
     * @throws ValidationException
     */
    public function store(array $data): IntegrationResource
    {

        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "api_login" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $iiko = Integration::query()
            ->where("type", IntegrationTypeEnum::IIKO->value)
            ->where("tenant_id", $tenant->id)
            ->first();

        $tmp = [
            'tenant_id' => $tenant->id,
            'type'=>IntegrationTypeEnum::IIKO->value,
            'credentials'=>json_encode([
                'api_login' => $data["api_login"] ?? null,
                'organization_id' => $data["organization_id"] ?? null,
                'terminal_group_id' => $data["terminal_group_id"] ?? null,
            ])
        ];

        if (is_null($iiko))
            $iiko = Integration::query()
                ->create($tmp);
        else
            $iiko->update($tmp);

        return new IntegrationResource($iiko);
    }

    /**
     * @throws HttpException
     * @throws ValidationException
     */
    public function storeProductsAndCategories(array $data): void
    {


        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "products" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $basket = Basket::query()
            ->where("tenant_id", $tenant->id)
            ->get();

        foreach($basket as $item)
            $item->delete();

        $products = Product::query()
            ->where("tenant_id", $tenant->id)
            ->get();

        foreach ($products as $product) {
            $product->in_stop_list = true;
            $product->deleted_at = Carbon::now();
            $product->save();
        }

        foreach ($data["products"] as $product) {
            $product = (object)$product;

            $tmpProduct = Product::query()
                ->where("tenant_id", $tenant->id)
                ->where("external_id", $product->id)
                ->first();

            $tmp = [
                'sku' => $product->sku ?? null,
                'external_source'=>IntegrationTypeEnum::IIKO->value,
                'external_id' => $product->id ?? null,
                'name' => $product->name ?? null,
                'description' => $product->description ?? null,
                'images' => [$product->image ?? null],
                'type' => 0,
                'old_price' => 0,
                'price' => $product->price ?? 0,
                'in_stop_list' => $product->in_stop ,
                'tenant_id' => $tenant->id,
                'deleted_at' => null
            ];

            if (is_null($tmpProduct))
                $tmpProduct = Product::query()
                    ->create($tmp);
            else
                $tmpProduct->update($tmp);

            $category = $product->category;

            $tmpProductCategory = Category::query()
                ->where("name", $category)
                ->where("tenant_id", $tenant->id)
                ->first();

            if (is_null($tmpProductCategory))
                $tmpProductCategory = Category::query()
                    ->create([
                        'name' => $category,
                        'tenant_id' => $tenant->id,
                    ]);

            $tmpProduct->categories()->attach([$tmpProductCategory->id]);
        }


    }

    /**
     * Создание заказа в системе iiko
     *
     * @param array $orderData Данные заказа
     * @throws HttpException
     * @throws ValidationException
     */
    public function createOrder(array $orderData): mixed
    {
        $tenant = app('tenant');

        $iiko = $tenant->iiko()->first();


        if (is_null($iiko)) {
            throw new HttpException(400, "Система не настроена!");
        }

        $credentials = $iiko->credentials ?? [];

        $organizationId = $credentials["organization_id"] ?? null;
        $terminalGroupId = $credentials["terminal_group_id"] ?? null;

        $token = $this->getToken(); // метод, который получает токен iiko

        $url = rtrim(config('iiko.api_url'), '/'); // например https://api-ru.iiko.services/api

        // Базовая структура заказа (см. документацию iiko)
        $order = [
            "organizationId" => $organizationId,
            "terminalGroupId" => $terminalGroupId,
            "order" => [
                "id" => \Illuminate\Support\Str::uuid()->toString(),
                "externalNumber" => $orderData['order_id'] ?? null,
                "phone" => $orderData['customer']['phone'] ?? null,
                "customer" => $orderData['customer'] ?? null,
                "guests" => [
                    "count" => $orderData['guests_count'] ?? 1,
                ],
                "items" => [],
                "payments" => $orderData['payments'] ?? [],
            ],
            "createOrderSettings" => [
                "servicePrint" => false,
                "transportToFrontTimeout" => 0,
                "checkStopList" => false
            ]
        ];

        $basket = $orderData["items"] ?? [];

        foreach ($basket as $item) {
            $item = (object)$item;
            $comment = $item->comment ?? null;
            $product = $item->product ?? null;
            $collection = $item->collection ?? null;

            if (!is_null($product)) {

                if (is_null($product->iiko_article ?? null))
                    continue;

                $price = ($product->price ?? 0) * $item->count;

                $order['order']['items'][] = [
                    "productId"    => $product->external_id ?? null,  // ID товара в iiko
                    "type"         => "Product",
                    "amount"       => $item->count,    // Количество
                    "price"        => $price,       // Цена за штуку
                    "comment"      => $comment,
                ];

            }

        }

        if (count($order['order']['items'])==0)
            throw new HttpException(404, "Нет заказов для передачи в iiko!");

        // Отправляем запрос в iiko
        $response = Http::withToken(trim($token))
            ->timeout(15)
            ->post("$url/1/order/create", $order);

        if ($response->failed()) {

            throw new HttpException(
                $response->status(),
                $response->json("errorDescription") ?? $response->body()
            );
        }


        return $response->json();
    }

}
