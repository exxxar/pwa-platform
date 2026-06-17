<?php

namespace App\Services;

use App\Enums\IntegrationTypeEnum;
use App\Http\Resources\IntegrationResource;
use App\Models\Tenant\Integration;
use App\Models\Tenant\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FrontPadService
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
    public function getProducts()
    {
        $tenant = app('tenant');

        $frontPad = Integration::query()
            ->where("type", IntegrationTypeEnum::FRONTPAD->value)
            ->where("tenant_id", $tenant->id)
            ->first();

        $token = $frontPad->credentials["token"] ?? null;

        if (is_null($frontPad) || is_null($token))
            throw new HttpException(404, "FrontPad не подключен!");

        $result = Http::asForm()->post(config("frontpad.api_url") . "?get_products", [
            'secret' => trim($token)
        ]);

        $status = $result->json("result") ?? "error";

        if ($status == "error")
            throw new HttpException(403, "Ошибка получения списка товаров!");


        return $result->json();
    }

    public function getClient($clientPhone)
    {
        $tenant = app('tenant');

        $frontPad = Integration::query()
            ->where("type", IntegrationTypeEnum::FRONTPAD->value)
            ->where("tenant_id", $tenant->id)
            ->first();

        $token = $frontPad->credentials["token"] ?? null;

        if (is_null($frontPad) || is_null($frontPad->token ?? null))
            throw new HttpException(404, "FrontPad не подключен!");

        $result = Http::asForm()->post(config("frontpad.api_url") . "?get_client", [
            'secret' => $token,
            'client_phone' => $clientPhone,
        ]);

        $status = $result->json("result") ?? "error";

        if ($status == "error")
            throw new HttpException(403, "Ошибка получения информации о клиенте!");

        return $result->json();
    }

    public function getCertificate($certificate)
    {
        $tenant = app('tenant');

        $frontPad = Integration::query()
            ->where("type", IntegrationTypeEnum::FRONTPAD->value)
            ->where("tenant_id", $tenant->id)
            ->first();

        $token = $frontPad->credentials["token"] ?? null;

        if (is_null($frontPad) || is_null($token?? null))
            throw new HttpException(404, "FrontPad не подключен!");

        $result = Http::asForm()->post(config("frontpad.api_url") . "?get_certificate", [
            'secret' => $token,
            'certificate' => $certificate,
        ]);

        $status = $result->json("result") ?? "error";

        if ($status == "error")
            throw new HttpException(403, "Ошибка получения информации о сертификате!");

        return $result->json();
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function newOrder(array $data)
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            "products" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);


        $products = array_values(Collection::make($data["products"])
            ->where("external_source", IntegrationTypeEnum::FRONTPAD->value)
            ->whereNotNull("external_id")
            ->pluck("external_id")->toArray());

        $productsKol = array_values(Collection::make($data["products"])
            ->where("external_source", IntegrationTypeEnum::FRONTPAD->value)
            ->whereNotNull("external_id")
            ->pluck("count")->toArray());

        $frontPad = Integration::query()
            ->where("type", IntegrationTypeEnum::FRONTPAD->value)
            ->where("tenant_id", $tenant->id)
            ->first();

        $token = $frontPad->credentials["token"] ?? null;

        if (is_null($frontPad) || is_null($token))
            throw new HttpException(404, "FrontPad не подключен!");

        $hookUrl = env("app_url") . "/front-pad/callback/" . $tenant->domain; //$data["hook_url"] ?? $frontPad->hook_url ?? null;//
        $channel = $data["channel"] ?? $frontPad->credentials["channel"] ?? null;//
        $affiliate = $data["affiliate"] ??$frontPad->credentials["affiliate"] ?? null;//
        $point = $data["point"] ?? $frontPad->credentials["point"] ?? null;//

        $cash = $data["cash"] ?? false;

        if (!is_null($frontPad->credentials["pays"] ?? null))
            $payId = Collection::make($frontPad->credentials["pays"])
                ->where("key", $cash ? "cash" : "card")
                ->first()->value ?? 1;

        if (!is_null($frontPad->credentials["statueses"] ?? null))
            $newOrder = Collection::make($frontPad->credentials["statueses"])
                ->where("key", "new")
                ->first()->value ?? 1;


        $result = Http::asForm()->post(config("frontpad.api_url") . "?new_order", [
            'secret' => $frontPad->token,
            'product' => $products,//массив артикулов товаров [ОБЯЗАТЕЛЬНЫЙ ПАРАМЕТР];
            'product_kol' => $productsKol,//массив количества товаров [ОБЯЗАТЕЛЬНЫЙ ПАРАМЕТР];
            'product_mod' => $data["product_mod"] ?? null,//массив модификаторов товаров, где значение элемента массива является ключом родителя
            'product_price' => $data["product_price"] ?? null,
            'score' => $data["score"] ?? null, //баллы для оплаты заказа
            'sale' => $data["sale"] ?? null, //скидка, положительное, целое число от 1 до 100;
            'card' => $data["card"] ?? null, //карта клиента, положительное, целое число до 16 знаков;
            'street' => $data["street"] ?? null, //улица, длина до 50 знаков;
            'home' => $data["home"] ?? null, // дом, длина до 50 знаков;
            'pod' => $data["pod"] ?? null, //  подъезд, длина до 2 знаков;
            'et' => $data["et"] ?? null, //этаж, длина до 2 знаков;
            'apart' => $data["apart"] ?? null, //квартира, длина до 50 знаков;
            'phone' => $data["phone"] ?? null, //телефон, длина до 50 знаков;
            'mail' => $data["mail"] ?? null, //адрес электронной почты, длина до 50 знаков, доступно только с активной опцией автоматического сохранения клиентов;
            'descr' => $data["descr"] ?? null, //примечание, длина до 100 знаков;
            'name' => $data["name"] ?? null, //имя клиента, длина до 50 знаков;
            'pay' => $payId ?? 1, //отметка оплаты заказа, значение можно посмотреть в справочнике “Варианты оплаты”;
            'certificate' => $data["certificate"] ?? null, //номер сертификата;
            'person' => $data["person"] ?? 1, //оличество персон, длина 2 знака. Обратите внимание, привязка "автосписания" к количеству персон, переданному через api, не осуществляется;
            'tags' => $data["tags"] ?? null, //массив отметок заказов, значение кодов API можно посмотреть в справочнике программы.
            'hook_status' => [$newOrder ?? 1], //массив статусов заказов, значение кодов API можно посмотреть в справочнике программы.Передается в формате аналогичном массиву товаров (не более 5), см. пример;
            'hook_url' => $hookUrl,// url для отправки вебхука по текущему заказу (если параметр не передан, вебхук будетотправлен по url из настроек API;
            'channel' => $channel,// канал продаж, значение можно посмотреть в справочнике программы;
            'datetime' => $data["datetime"] ?? null,//время “предзаказа”, указывается в формате ГГГГ-ММ-ДД ЧЧ:ММ:СС,
            //например 2016-08-15 15:30:00. Максимальный период предзаказа - 30 дней от текущей даты;
            'affiliate' => $affiliate, //филиал, значение можно посмотреть в справочнике программы;
            'point' => $point,//точка продаж, значение можно посмотреть в справочнике программы.


        ]);

        if ($result->json("result") == 'error') {
            if ($result->json("error") == "cash_close") {
                MessageService::call()
                    ->sendMessage([
                        "message"=>"Пользователь пытался сделать заказ когда система FrontPad была закрыта."
                    ]);
            }
        }

        return $result->json();
    }


    /**
     * @throws ValidationException
     */
    public function store(array $data): IntegrationResource
    {
        $tenant = app('tenant');

        $pays = isset($data["pays"]) ? json_decode($data["pays"]) : null;
        $statuses = isset($data["statuses"]) ? json_decode($data["statuses"]) : null;

        $frontPad = Integration::query()->updateOrCreate(
            [
                'type'=>IntegrationTypeEnum::FRONTPAD->value,
                'tenant_id' => $tenant->id,
            ],
            [
                'credentials'=>json_encode([
                    'hook_url' => $data["hook_url"] ?? null,
                    'channel' => $data["channel"] ?? null,
                    'affiliate' => $data["affiliate"] ?? null,
                    'point' => $data["point"] ?? null,
                    'token' => $data["token"] ?? null,
                    'statuses' => $statuses,
                    'pays' => $pays,
                ])
            ]);

        return new IntegrationResource($frontPad);
    }


    public function import($file)
    {
        $tenant = app('tenant');

        $products = Product::query()
            ->where("tenant_id", $tenant->id)
            ->get();

        foreach ($products as $product) {
            $product->in_stop_list = true;
            $product->deleted_at = Carbon::now();
            $product->save();
        }

        $path = $file->store('imports'); // 2. Полный путь к файлу

        $fullPath = storage_path('app/' . $path);

        Excel::import(new ProductFrontPadImport($tenant->id), $fullPath, null, \Maatwebsite\Excel\Excel::HTML);

        Storage::delete($path);
    }

    /**
     * @throws ValidationException
     */
    public function loadProducts()
    {
        $tenant = app('tenant');

        $frProducts = $this->getProducts();

        $products = Product::query()
            ->where("tenant_id", $tenant->id)
            ->get();

        foreach ($products as $product) {
            $product->in_stop_list = true;
            $product->deleted_at = Carbon::now();
            $product->save();
        }

        foreach ($frProducts->product_id as $key => $value) {
            $product = Product::query()
                ->withTrashed()
                ->where("external_source", IntegrationTypeEnum::FRONTPAD->value)
                ->where("external_id", $value)
                ->where("tenant_id", $tenant->id)
                ->first();

            $tmpProduct = [
                'sku' => null,
                'vk_product_id' => null,
                'external_source' => IntegrationTypeEnum::FRONTPAD->value,
                'external_id' => $value,
                'name' => $frProducts->name[$key],
                'description' => $frProducts->name[$key],
                'images' => [],
                'type' => 0,
                'old_price' => 0,
                'price' => $frProducts->price[$key],
                'in_stop_list' => false,
                'tenant_id' => $tenant->id,
                'deleted_at' => null
            ];

            if (is_null($product))
                Product::query()->create($tmpProduct);
            else
                $product->update($tmpProduct);
        }
    }
}
