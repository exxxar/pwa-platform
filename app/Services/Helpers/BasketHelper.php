<?php

namespace App\Services\Helpers;

use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use App\Facades\IIKOService;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Order;
use App\Models\Tenant\Partner;
use App\Models\Tenant\Tenant;
use App\Services\MessageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait BasketHelper
{


    private function fsPrepareDisabilities(): string
    {
        $hasDisability = ($this->data["has_disability"] ?? "false") == "true";

        $disabilities = json_decode($this->data["disabilities"] ?? '[]');
        $allergy = $this->data["allergy"] ?? 'не указана';

        $tmpMessage = "";
        if ($hasDisability) {

            $disabilitiesText = "<b>Внимание!</b> у клиента присутствуют ограничения по здоровью!\n";

            foreach ($disabilities as $disability)
                $disabilitiesText .= $disability == "пищевая аллергия" ? "-<em>$disability на: $allergy</em>\n" : "-<em>$disability</em>\n";

            $tmpMessage .= $disabilitiesText . "\n";


        }

        return $tmpMessage;
    }

    private function fsPrepareUserInfo($order, $cashback = 0)
    {

        $time = $this->data["time"] ?? null;
        $persons = $this->data["persons"] ?? 1;

        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0];
        $whenReady = ($this->data["when_ready"] ?? "false") == "true";
        $needPickup = ($this->data["need_pickup"] ?? "false") == "true";
        $useCashback = ($this->data["use_cashback"] ?? "false") == "true";
        $address = ($this->data["address"] ?? "");
        $lat = $this->data["lat"] ?? 0;
        $lng = $this->data["lng"] ?? 0;

        return !$needPickup ?
            sprintf("\n" . ($whenReady ? "🟢" : "🟡") . " Заказ №: <b>%s</b>\nИдентификатор клиента: <b>%s</b>\n\n<b>Данные для доставки:</b>\nФ.И.О.: <b>%s</b>\nНомер телефона: <b>%s</b>\nАдрес: %s\nЦена доставки: %s руб.\nДистанция: %s км\nНомер подъезда: %s\nНомер этажа: %s\nТип оплаты: <b>%s</b>\nСдача с: %s руб.\nДоп.инфо: %s\nИспользован кэшбэк: %s\nДоставить ко времени:%s\nЧисло персон: <b>%s</b> чел.\n",
                $order->id ?? '-',
                $this->tenantUser->id ?? '-',
                $this->data["name"] ?? 'Не указано',
                $this->data["phone"] ?? 'Не указано',
                "<code>" . $address . "," . ($this->data["flat_number"] ?? "") . "</code><code>($lat, $lng)</code>",
                $order->delivery_price ?? 0,
                $order->delivery_range ?? 0,
                $this->data["entrance_number"] ?? 'Не указано',
                $this->data["floor_number"] ?? 'Не указано',
                $cash,
                $this->data["money"] ?? 'Не указано',
                $this->data["info"] ?? 'Не указано',
                $useCashback ? $cashback : "нет",
                ($whenReady ? "По готовности" : Carbon::parse($time)->format('Y-m-d H:i')),
                $persons
            ) :
            sprintf("\n" . ($whenReady ? "🟢" : "🟡") . "Заказ №: <b>%s</b>\nИдентификатор: <b>%s</b>\n\n<b>Данные для самовывоза:</b>\nФ.И.О.: <b>%s</b>\nНомер телефона: <b>%s</b>\nТип оплаты: <b>%s</b>\nСдача с: %s руб.\nДоп.инфо: %s\nИспользован кэшбэк: %s\nЗаберу в:%s\nЧисло персон: <b>%s</b> чел.\n",
                $order->id ?? '-',
                $this->tenantUser->id,
                $this->data["name"] ?? 'Не указано',
                $this->data["phone"] ?? 'Не указано',
                $cash,
                $this->data["money"] ?? 'Не указано',
                $this->data["info"] ?? 'Не указано',
                $useCashback ? $cashback : "нет",
                ($whenReady ? "По готовности" : Carbon::parse($time)->format('Y-m-d H:i')),
                $persons
            );
    }

    private function fsSendResult($message)
    {
        MessageService::call()
            ->sendMessage([
                "message" => ("Спасибо, ваш заказ появился в нашей системе:\n\n<em>$message</em>" ?? "Данные не найдены") .
                    "\nВы можете оставить отзыв с фото и получить от нас дополнительный КэшБэк!",
                "keyboard" => [
                    [
                        ["text" => "📢Написать отзыв с фото"],
                    ],
                ]
            ]);

    }

    private function ensureCityPrefix(string $address): string
    {
        // Список признаков города / населённого пункта
        $patterns = [
            '/\bг\.\b/ui',        // г.
            '/\bгород\b/ui',      // город
            '/\bс\.\b/ui',        // с.
            '/\bсело\b/ui',       // село
            '/\bпос\.\b/ui',      // пос.
            '/\bпос[её]лок\b/ui', // поселок / посёлок
            '/\bпгт\b/ui',        // пгт
        ];

        // Проверка наличия признака
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $address)) {
                return trim($address);
            }
        }

        // Если признака нет — добавляем "г."
        return 'г. ' . trim($address);
    }

    private function ensureStreetPrefix(string $street): string
    {
        // Список признаков улицы
        $patterns = [
            '/\bул\.\b/ui',          // ул.
            '/\bулица\b/ui',         // улица
            '/\bпр-т\b/ui',          // пр-т
            '/\bпросп\.\b/ui',       // просп.
            '/\bпроспект\b/ui',      // проспект
            '/\bпер\.\b/ui',         // пер.
            '/\bпереулок\b/ui',      // переулок
            '/\bбул\.\b/ui',         // бул.
            '/\bбульвар\b/ui',       // бульвар
            '/\bпроезд\b/ui',        // проезд
            '/\bш\.\b/ui',           // ш.
            '/\bшоссе\b/ui',         // шоссе
            '/\bнаб\.\b/ui',         // наб.
            '/\bнабережная\b/ui',    // набережная
            '/\bпл\.\b/ui',          // пл.
            '/\bплощадь\b/ui',       // площадь
            '/\bтракт\b/ui',         // тракт
            '/\bтуп\.\b/ui',         // туп.
            '/\bтупик\b/ui',         // тупик
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $street)) {
                return trim($street);
            }
        }

        return 'ул. ' . trim($street);
    }


    private function fsPrepareAddress(): string
    {

        $city = $this->ensureCityPrefix($this->data["city"] ?? "");
        $street = $this->ensureStreetPrefix($this->data["street"] ?? "");

        return "$city, $street, " . ($this->data["building"] ?? "");
    }

    private function fsPrintPDFInfo($order, $summaryPrice, $summaryCount, $tmpOrderProductInfo, $cashback = 0)
    {


        $useCashback = ($this->data["use_cashback"] ?? "false") == "true";
        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0];

        $address = $this->fsPrepareAddress();
        $userId = $this->te->telegram_chat_id ?? 'Не указан';

        $paymentInfo = sprintf($this->tenant->settings["payment_info"] ??
            "Оплатите заказ по реквизитам:\nСбер XXXX-XXXX-XXXX-XXXX Иванов И.И. или переводом по номеру +7(000)000-00-00 - указав номер %s\nИ отправьте нам скриншот оплаты со словом <strong>оплата</strong>");

        $mpdf = new Mpdf();
        $current_date = Carbon::now("+3:00")->format("Y-m-d H:i:s");

        $number = Str::uuid();

        $mpdf->WriteHTML(view("pdf.order", [
            "title" => $tenant->title ?? $tenant->bot_domain ?? 'CashMan',
            "uniqNumber" => $number,
            "orderId" => $order->id,
            "name" => $order->receiver_name,
            "phone" => $order->receiver_phone,
            "address" => $address . "," . ($this->data["flat_number"] ?? ""),
            "message" => ($this->data["info"] ?? 'Не указано'),
            "entranceNumber" => ($this->data["entrance_number"] ?? 'Не указано'),
            "floorNumber" => ($this->data["floor_number"] ?? 'Не указано'),
            "cashType" => $cash,
            "money" => ($this->data["money"] ?? 'Не указано'),
            "disabilitiesText" => ($disabilitiesText ?? 'не указаны'),
            "totalPrice" => $summaryPrice,
            "discount" => $useCashback ? $cashback : 0,
            "totalCount" => $summaryCount,
            "distance" => $distance ?? 0, //$distance
            "deliveryPrice" => $deliveryPrice ?? 0, //цена доставки
            "currentDate" => $current_date,
            "code" => "Без промокода",
            "promoCount" => "0",
            "paymentInfo" => $paymentInfo,
            "products" => $tmpOrderProductInfo,
            "info" => $this->data["info"] ?? 'Не указано',
        ]));

        $file = $mpdf->Output("order-$number.pdf", \Mpdf\Output\Destination::STRING_RETURN);

        /*   sleep(1);
           BotMethods::bot()
               ->whereBot($tenant)
               ->sendDocument(
                   $tenantUser->telegram_chat_id,
                   "Информация о заказе #" . ($order->id ?? 'не указан'),
                   InputFile::createFromContents($file, "invoice.pdf")
               );*/
    }

    private function fsPrepareFrontPad($order, $tmpOrderProductInfo, $partnerId = null)
    {

        $bot = is_null($partnerId) ? $tenant : Bot::query()->find($partnerId);
        $frontPad = $bot->frontPad ?? null;

        if (is_null($frontPad))
            return;

        $persons = $this->data["persons"] ?? 1;
        $whenReady = ($this->data["when_ready"] ?? "false") == "true";
        $time = $this->data["time"] ?? null;
        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0];

        Log::info("tmpOrderProductInfo=>" . print_r($tmpOrderProductInfo, true));
        BusinessLogic::frontPad()
            ->setBot($bot)
            ->newOrder([
                "products" => $tmpOrderProductInfo,
                "phone" => $order->receiver_phone,
                "descr" => $this->data["info"] ?? 'Не указано',
                "name" => $order->receiver_name,
                "home" => ($this->data["building"] ?? ""),
                "street" => ($this->data["street"] ?? ""),
                'pod' => ($this->data["entrance_number"] ?? 'Не указано'),
                'et' => ($this->data["floor_number"] ?? 'Не указано'),
                'apart' => ($this->data["flat_number"] ?? ""),
                'person' => $persons,
                'datetime' => ($whenReady ? null
                    : Carbon::parse($time)->format('Y-m-d H:i:s')),
                'cash' => $cash
            ]);
    }


    private function fsPrepareDeliveryNote(): string
    {


        $disabilitiesText = $this->fsPrepareDisabilities();

        $persons = $this->data["persons"] ?? 1;
        $whenReady = ($this->data["when_ready"] ?? "false") == "true";
        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0];

        $time = $this->data["time"] ?? null;

        /*  $tenantUser->city = $this->data["city"] ?? $tenantUser->city ?? null;
          $tenantUser->address = ($this->data["street"] ?? "") . "," . ($this->data["building"] ?? "");
          $tenantUser->save();*/

        return ($this->data["info"] ?? 'Не указано') . "\n"
            . (is_null($this->data["entrance_number"] ?? null) ? "Номер подъезда: " . $this->data["entrance_number"] . "\n" : "")
            . (is_null($this->data["floor_number"] ?? null) ? "Номер этажа: " . $this->data["floor_number"] . "\n" : "")
            . "Тип оплаты: " . $cash . "\n"
            . (is_null($this->data["money"] ?? null) ? "Сдача с: " . $this->data["money"] . "\n" : "")
            . "Время доставки:" . ($whenReady ? "По готовности" : Carbon::parse($time)->format('Y-m-d H:i')) . "\n"
            . "Число персон:" . $persons . "\n"
            . "Ограничения:\n" . ($disabilitiesText ?? 'не указаны');
    }

    /**
     * @return void
     */
    protected function storeClientInfoAsContact(): void
    {

        $vowels = ["(", ")", "-"];
        $filteredPhone = !is_null($this->data["phone"] ?? $this->tenantUser->phone ?? null) ?
            str_replace($vowels, "", $this->data["phone"] ?? $this->tenantUser->phone) : null;

        $this->tenantUser->name = $this->data["name"] ?? $this->tenantUser->name ?? null;
        $this->tenantUser->phone = $filteredPhone;
        $this->tenantUser->save();
    }

    private function useCashBackForPayment($discount): void
    {
        $useCashback = ($this->data["use_cashback"] ?? "false") == "true";

        if (!$useCashback)
            return;

        /*   CashBackService::call()
               ->removeCashBack(
                   $discount ?? 0,
                   "Автоматическое списание скидки на покупку товара"
               );*/

    }

    private function prepareCashbackDiscount($summaryPrice)
    {
        $useCashback = ($this->data["use_cashback"] ?? "false") == "true";
        $maxCashbackUsePercent = $this->tenant->settings["max_cashback_use_percent"] ?? 0;
        $maxUserCashback = $this->tenantUser->cashback->amount ?? 0;
        $cashBackAmount = ($summaryPrice * ($maxCashbackUsePercent / 100));
        return ($useCashback ? min($cashBackAmount, $maxUserCashback) : 0);

    }


    private function sendPaidReceiptToCRM($order, $message)
    {
        $uploadedPhoto = $this->uploadedImage;

        $hasPhoto = !is_null($uploadedPhoto);

        $whenReady = ($this->data["when_ready"] ?? "false") == "true";

        if ($hasPhoto) {
            $ext = $uploadedPhoto->getClientOriginalExtension();
            $imageName = Str::uuid() . "." . $ext;
            $uploadedPhoto->storeAs("$imageName");

            $photoPath = storage_path() . "/app/$imageName";

            $tmpMessage =
                '<p><strong>#оплатачеком</strong></p>' .
                '<p>' . ($whenReady ? '🟢' : '🟡') .
                ' <strong>Заказ №:</strong> ' . ($order->id ?? '-') . '</p>' .
                '<p><strong>Идентификатор клиента:</strong> ' . ($this->tenantUser->id ?? '-') . '</p>' .
                '<p><strong>Пользователь:</strong> ' . ($order->receiver_name ?? '-') . '</p>' .
                '<p><strong>Телефон:</strong> ' . ($order->receiver_phone ?? '-') . '</p>' .
                '<br>' .
                '<p><strong>Пояснение к оплате:</strong> ' . ($this->data["image_info"] ?? 'не указано') . '</p>';
            '<p><strong>Ссылка на фото:</strong> ' . ($photoPath) . '</p>';
        }


        $thread = $this->tenant->topics["orders"] ?? null;


        //todo: доделать

    }

    /**
     * @throws ValidationException
     */
    private function foodShopCheckout(): ?object
    {
        $needPickup = ($this->data["need_pickup"] ?? "false") == "true";
        $deliveryPrice = $this->data["delivery_price"] ?? 0;
        $distance = $this->data["distance"] ?? 0;
        $lat = $this->data["lat"] ?? 0;
        $lng = $this->data["lng"] ?? 0;
        $address = $this->data["address"] ?? '';
        $locationId = $this->data["location_id"] ?? '';

        $paymentType = $this->data["payment_type"] ?? 4;
        $deliveryDetails = json_decode($this->data["delivery_details"] ?? '[]');
        $useCashback = ($this->data["use_cashback"] ?? "false") == "true";

        $basket = Basket::query()
            ->where("tenant_id", $this->tenant->id)
            ->where("tenant_user_id", $this->tenantUser->id)
            ->whereNull("ordered_at")
            ->get();


        $isPartnersActive = $this->tenant->settings["partners"]["is_active"] ?? false;

        $isPartnersDisplaySelf = $this->tenant->settings["partners"]["display_self"] ?? false;


        $summaryPrice = 0;
        $summaryCount = 0;
        $summaryDiscount = 0;

        $tmpOrderProductInfo = [];

        $partnerProductBox = [];
        $ids = [];

        foreach ($basket as $item) {
            $comment = $item->comment ?? null;
            $product = $item->product ?? null;
            $collection = $item->collection ?? null;

            $partner = $item->partner ?? $this->tenant;

            if ($isPartnersActive && !$isPartnersDisplaySelf
                && $partner->id == $this->tenant->id
            )
                continue;

            $deliveryDetails = (array)$deliveryDetails;

            if (empty($partnerProductBox[$partner->uuid])) {
                $partnerProductBox[$partner->uuid]["crm"] = $partner->setting["crm"] ?? [];
                $partnerProductBox[$partner->uuid]["id"] = $partner->id;
                $partnerProductBox[$partner->uuid]["name"] = $partner->name ?? $partner->slug ?? 'Без названия';
                $partnerProductBox[$partner->uuid]["message"] = "";
                $partnerProductBox[$partner->uuid]["extra_charge"] = (Partner::query()
                    ->where("tenant_id", $item->tenant_id)
                    ->where("tenant_partner_id", $item->tenant_partner_id)
                    ->first())->extra_charge ?? 0;
                $partnerProductBox[$partner->uuid]["summary_price"] = 0;
                $partnerProductBox[$partner->uuid]["summary_count"] = 0;
                $partnerProductBox[$partner->uuid]["summary_discount"] = 0;
                $partnerProductBox[$partner->uuid]["delivery_price"] = $deliveryDetails[$partner->uuid]->price ?? 0;
                $partnerProductBox[$partner->uuid]["distance"] = $deliveryDetails[$partner->uuid]->distance ?? 0;
                $partnerProductBox[$partner->uuid]["address"] = $deliveryDetails[$partner->uuid]->address ?? '-';
                $partnerProductBox[$partner->uuid]["thread"] = $partner->topics["delivery"] ??
                    $this->tenant->topics["delivery"] ?? null;

            }

            $price = 0;

            $extraCharge = $partnerProductBox[$partner->uuid]["extra_charge"];
            $isWeightProduct = false;
            if (!is_null($product)) {
                $isWeightProduct = $product->is_weight_product ?? false;

                $count = $item->count;

                $currentPrice = $item->params["discount_price"] ?? $product->current_price;

                $price = (($currentPrice ?? 0) * (1 + $extraCharge / 100)) * $count;

                $unitOfMeasure = "ед.";

                if ($isWeightProduct) {
                    $weightConfig = (object)$product->weight_config ?? null;
                    $step = $weightConfig->step ?? 100;

                    $price = ((($currentPrice ?? 0) * (1 + $extraCharge / 100)) * $count) / $step;

                    $unitOfMeasure = "гр.";
                }

                $tmpMessage = is_null($comment) ?
                    sprintf("💎%s x%s $unitOfMeasure=%s руб.\n",
                        $product->name,
                        $item->count,
                        $price
                    ) :
                    sprintf("💎%s x%s $unitOfMeasure=%s руб.\n<em>(%s)</em>\n",
                        $product->name,
                        $item->count,
                        $price,
                        $comment
                    );

                $partnerProductBox[$partner->uuid]["message"] .= $tmpMessage;
                // $productMessage .= $tmpMessage;

                $tmpOrderProductInfo[] = (object)[
                    "name" => $product->name,
                    "count" => $item->count,
                    "price" => $price,
                    'external_source' => $product->external_source ?? null,
                    'external_id' => $product->external_id ?? null,
                ];


                if (!in_array($product->id, $ids)) {
                    $ids[] = $product->id;

                    $partnerProductBox[$partner->uuid]["products"][] = $tmpOrderProductInfo;
                }


            }

            if (!is_null($collection)) {
                $collectionTitles = "";


                $params = is_null($item->params ?? null) ? null : (object)$item->params;

                foreach (($collection->products ?? []) as $product) {

                    if (!in_array($product->id, $params->ids ?? []))
                        continue;

                    $collectionTitles .= "-" . $product->name . "\n";

                    $tmpOrderProductInfo[] = (object)[
                        "name" => "Коллекция `" . ($collection->name) . "`: " . $product->name,
                        "count" => 1,
                        "price" => $product->current_price ?? 0,
                        'external_source' => $product->external_source ?? null,
                        'external_id' => $product->external_id ?? null,
                    ];

                    $price += ($product->current_price ?? 0) * (1 + $extraCharge / 100);


                    if (!in_array($product->id, $ids)) {
                        $ids[] = $product->id;

                        $partnerProductBox[$partner->uuid]["products"][] = $tmpOrderProductInfo;
                    }

                }

                $price = $price * $item->count;


                $tmpMessage = sprintf("💎Коллекция `%s` x%s=%s руб.:\n%s\n",
                    ($collection->name),
                    $item->count,
                    $price,
                    $collectionTitles,
                );

                //$productMessage .= $tmpMessage;
                $partnerProductBox[$partner->uuid]["message"] .= $tmpMessage;

            }

            $partnerProductBox[$partner->uuid]["summary_count"] += $isWeightProduct ? 1 : $item->count;
            $partnerProductBox[$partner->uuid]["summary_price"] += $price;
            $partnerProductBox[$partner->uuid]["summary_discount"] += $item->params["discount_amount"] ?? 0;

            $summaryDiscount += $item->params["discount_amount"] ?? 0;
            $summaryCount += $isWeightProduct ? 1 : $item->count;
            $summaryPrice += $price;

            $item->ordered_at = env("APP_DEBUG") ? null : Carbon::now();
            $item->save();

        }

        $deliveryNote = $this->fsPrepareDeliveryNote();
        $cashback = $this->prepareCashbackDiscount($summaryPrice);
        $this->useCashBackForPayment($cashback ?? 0);

        //todo: $deliveryPrice для всех partnerBox и суммарная

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'deliveryman_id' => null,
            'customer_id' => $this->tenantUser->id,
            'delivery_service_info' => null,//информация о сервисе доставки
            'deliveryman_info' => null,//информация о доставщике
            'product_details' => [
                (object)[
                    "from" => $tenant->title ?? $tenant->bot_domain ?? $tenant->id,
                    "products" => $tmpOrderProductInfo
                ]
            ],//информация о продуктах и заведении, из которого сделан заказ
            'product_count' => $summaryCount,
            'summary_price' => $summaryPrice - $cashback,
            'delivery_price' => $deliveryPrice,
            'delivery_range' => $distance ?? 0,
            'deliveryman_latitude' => 0,
            'deliveryman_longitude' => 0,
            'delivery_note' => $deliveryNote,
            'receiver_name' => $this->data["name"] ?? 'Нет имени',
            'receiver_phone' => $this->data["phone"] ?? 'Нет телефона',
            'location_id' => $locationId,
            'status' => OrderStatusEnum::NewOrder->value,//новый заказ, взят доставщиком, доставлен, не доставлен, отменен
            'order_type' => OrderTypeEnum::InternalStore->value,//тип заказа: на продукт из магазина, на продукт конструктора
            'payed_at' => Carbon::now(),
        ]);

        /* BusinessLogic::review()
             ->settenantUser($this->tenantUser)
             ->setBot($tenant)
             ->prepareReviews($order->id, $ids);*/


        foreach ($partnerProductBox as $key => $box) {
            $box = (object)$partnerProductBox[$key];

            $tenantInBox = Tenant::query()->find($box->id);

            $this->fsPrepareFrontPad($order, $tmpOrderProductInfo, $tenantInBox->id);

            $iiko = $tenantInBox->iiko ?? null;

            if ($iiko && !is_null($iiko->api_login ?? null)) {
                IIKOService::call()
                    ->createOrder([
                        "guests_count" => $this->data["persons"] ?? 1,
                        "order_id" => $order->id,
                        "customer" => [
                            "name" => $this->data["name"],
                            "surname" => $this->tenantUser->fio_from_telegram ?? $this->tenantUser->telegram_chat_id ?? "",
                            "comment" => $deliveryNote,
                            "gender" => $this->tenantUser->sex ? "Male" : "Female",
                            "type" => "regular",
                            "phone" => $this->data["phone"],
                        ],
                        "items" => $basket,
                    ]);

            }


        }

        $needBill = false;

        //todo: сделать ссылку в модели бота


        ini_set('max_execution_time', 300);
        $summaryProductMessage = "<b>⚠️⚠️⚠️Сводный заказ⚠️⚠️⚠️</b>\n"
            . (!$needPickup ? "#заказдоставка\n" : "#заказсамовывоз\n");

        $recountDeliveryPrice = $deliveryPrice == 0;

        foreach ($partnerProductBox as $key => $box) {
            $box = (object)$partnerProductBox[$key];

            $resultMessage = "Заказ из <b>$box->name</b>\n";
            $resultMessage .= (!$needPickup ? "#заказдоставка\n" : "#заказсамовывоз\n");
            //  $resultMessage .= $this->checkWheelOfFortuneAction();
            $resultMessage .= $this->fsPrepareDisabilities();

            $resultMessage .= $box->message ?? 'Неуказанный продукт (ошибка)';

            $localSummaryCount = $partnerProductBox[$key]["summary_count"] ?? 0;
            $localSummaryPrice = $partnerProductBox[$key]["summary_price"] ?? 0;
            $localSummaryDiscount = $partnerProductBox[$key]["summary_discount"] ?? 0;


            $resultMessage .= $this->fsPrepareUserInfo($order, $cashback);

            if ($localSummaryDiscount > 0)
                $resultMessage .= "\nСкидка по товарам: <b>-$localSummaryDiscount руб.</b>";
            $resultMessage .= "\nИтого: <b>" . $localSummaryPrice . " руб.</b> за <b>$localSummaryCount ед.</b>\n";

            $summaryProductMessage .= "\n<b>﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌</b>\n" .
                "Заказ из <b>$box->title</b>\n"
                . ($partnerProductBox[$key]["message"] ?? '')
                . "\nСкидка по товарам: <b>-$localSummaryDiscount руб.</b>"
                . "\nИтого: <b>" . $localSummaryPrice . " руб.</b> за <b>$localSummaryCount ед.</b>";


            if ($box->delivery_price > 0) {
                $localDeliveryPrice = $box->delivery_price;
                $localDistance = $box->distance;
                $resultMessage .= "\nДоставка: <b>" . $localDeliveryPrice . " руб.</b> за $localDistance км";
                $resultMessage .= "\nИтого c доставкой: <b>" . ($localSummaryPrice + $localDeliveryPrice) . " руб.</b>";

                $summaryProductMessage .= "\nДоставка: <b>" . $localDeliveryPrice . " руб.</b> за $localDistance км";
                $summaryProductMessage .= "\nИтого c доставкой: <b>" . ($localSummaryPrice + $localDeliveryPrice) . " руб.</b>";

                if ($recountDeliveryPrice)
                    $deliveryPrice += $localDeliveryPrice;
            }

            MessageService::call()
                ->sendMessage([
                    "message" => $resultMessage,
                    "thread_id" => $box->thread,
                ]);

        }

        $summaryProductMessage .= "\n<b>﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌</b>\n";


        if ($useCashback)
            $summaryProductMessage .= "Использованы баллы: <b>-$cashback</b> руб.\n";
        $summaryProductMessage .= "Итоговая скидка: <b>-$summaryDiscount</b> руб.\n";
        $summaryProductMessage .= "Итого по всем: <b>" . ($summaryPrice - $cashback) . " руб.</b> за <b>$summaryCount ед.</b>\n";


        if (count($deliveryDetails) > 0) {

            if ($deliveryPrice > 0) {
                $summaryProductMessage .= "Доставка: <b>" . $deliveryPrice . " руб.</b> за $distance км\n";
                $summaryProductMessage .= "Итого c доставкой: <b>" . (($summaryPrice - $cashback) + $deliveryPrice) . " руб.</b>\n";
            } else
                $summaryProductMessage .= "Доставка: <b>рассчитывается курьером</b>\n";

        }


        $summaryProductMessage .= $this->fsPrepareUserInfo($order, $cashback);
        // $summaryProductMessage .= $this->checkWheelOfFortuneAction();
        $summaryProductMessage .= $this->fsPrepareDisabilities();


        if ($this->tenant->settings["partners"]["is_active"] ?? false) {
            MessageService::call()
                ->sendMessage([
                    "message" => $summaryProductMessage,
                    "thread_id" => $this->tenant->topics["delivery"] ?? null,
                ]);
        }

        $order->delivery_price = $deliveryPrice;
        $order->save();

        switch ($paymentType) {
            case 0:
                //todo: доделать
                PaymentService::call()
                    ->checkout();
                //ссылка
                break;
            case 1:
                //картой в заведении
            case 2:
                //переводом
            case 3:
                //наличными

                $needBill = true;
                break;
            case 4:
                return
                    PaymentService::call()
                        ->sbpForShop($order, $summaryProductMessage);


        }

        if ($needBill)
            $this->fsPrintPDFInfo(
                order: $order,
                summaryPrice: $summaryPrice,
                summaryCount: $summaryCount,
                tmpOrderProductInfo: $tmpOrderProductInfo,
                cashback: $cashback
            );


        $this->sendPaidReceiptToChannel($order, $summaryProductMessage);

        $paymentInfo = sprintf($this->tenant->settings["payment_info"] ?? "Оплатите заказ по реквизитам:\nСбер XXXX-XXXX-XXXX-XXXX Иванов И.И. или переводом по номеру +7(000)000-00-00 - указав номер %s\nИ отправьте нам скриншот оплаты со словом <strong>оплата</strong>",
        );

        $summaryProductMessage .= "\n\n$paymentInfo";

        $this->fsSendResult($summaryProductMessage);

        $config = $this->tenantUser->meta ?? [];
        $config["current_promocodes"] = [];
        $this->tenantUser->meta = $config;
        $this->tenantUser->save();

        return null;

    }
}
