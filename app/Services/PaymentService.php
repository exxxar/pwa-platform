<?php

namespace App\Services;

class PaymentService
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

    public function setBotBalance($amount)
    {
        if (is_null($this->bot) || is_null($this->botUser))
            throw new HttpException(400, "Критерии функции не выполнены!");

        $accountBalance = $this->botUser->manager->balance ?? 0;

        if ($accountBalance - $amount < 0)
            throw new HttpException(400, "Недостаточно средств на балансе");

        $this->botUser->manager->balance -= $amount;
        $this->botUser->save();
        $this->bot->balance += min($amount, $accountBalance);
        $this->bot->save();

        BotMethods::bot()
            ->whereBot($this->bot)
            ->sendMessage(
                $this->botUser->telegram_chat_id,
                "Баланс бота <b>" . $this->bot->bot_domain . "</b> успешно пополнен на <b>$amount руб</b>. И составляет теперь <b>" . $this->bot->balance . " руб.</b>");

    }


    public function sbpNotificationProductsPayment($data)
    {
        if (is_null($this->bot)) {
            throw new HttpException(404, "Бот не найден!");
        }

        $orderId = $data['OrderId'] ?? null;
        $amount = isset($data['Amount']) && is_numeric($data['Amount']) ? $data['Amount'] / 100 : 0;

        $callbackChannel = $this->bot->order_channel ?? $this->bot->main_channel ?? env("BASE_ADMIN_CHANNEL");
        $thread = $this->bot->topics["orders"] ?? null;

        $customerKey = $data['CustomerKey'] ?? null;
        $rebillId = $data['RebillId'] ?? null;


        $order = Order::query()
            ->where("id", $orderId)
            ->first();

        if (is_null($order)) {
            return "OK";
        }

        if (is_null($customerKey))
            $customerKey = $order->customer_id;

        if (!isset($data['Success']) || !isset($data['Status']) || $data['Status'] !== 'CONFIRMED') {

            Log::info("test payment status=>" . print_r($data, true));
            if (($data["Status"] ?? 'REFUNDED') == 'REFUNDED' || ($data["Status"] ?? 'REJECTED') == 'REJECTED') {

                $file = print_r($data, true);

                BotMethods::bot()
                    ->whereBot($this->bot)
                    ->sendMessage(
                        $callbackChannel,
                        "⛔Оплата СБП по заказу #$orderId в размере $amount руб. НЕ прошла!",
                        $thread
                    );
                sleep(1);

                BotMethods::bot()
                    ->whereBot($this->bot)
                    ->sendDocument(
                        $callbackChannel,
                        "Данные по запросу СБП #" . ($order->id ?? 'не указан'),
                        InputFile::createFromContents($file, "sbp-support-info.txt")
                    );

                sleep(1);
                if (!is_null($customerKey)) {
                    $botUser = BotUser::query()
                        ->where("id", $customerKey)
                        ->first();

                    if (!is_null($botUser))
                        BotMethods::bot()
                            ->whereBot($this->bot)
                            ->sendMessage(
                                $botUser->telegram_chat_id,
                                "⛔Оплата СБП по заказу #$orderId в размере $amount руб. НЕ прошла!"
                            );


                }

            }


            return "OK";
        }


        if (!$orderId || $amount <= 0) {
            return "OK";
        }

        $order->payed_at = Carbon::now();
        $order->status = OrderStatusEnum::Completed->value;
        $order->save();

        BotMethods::bot()
            ->whereBot($this->bot)
            ->sendMessage(
                $callbackChannel,
                "✅Оплата СБП по заказу #$order->id в размере $amount (в заказе $order->summary_price) руб. прошла успешно!",
                $thread
            );

        $file = print_r($data, true);

        BotMethods::bot()
            ->whereBot($this->bot)
            ->sendDocument(
                $callbackChannel,
                "Данные по запросу СБП #" . ($order->id ?? 'не указан'),
                InputFile::createFromContents($file, "sbp-support-info.txt")
            );

        if (!is_null($customerKey)) {
            $clientBotUser = BotUser::query()
                ->where("id", $customerKey)
                ->first();

            if (!is_null($clientBotUser)) {
                $config = $clientBotUser->config ?? [];
                $config["tinkoff_rebill_id"] = $rebillId;
                $clientBotUser->config = $config;
                $clientBotUser->save();

                BotMethods::bot()
                    ->whereBot($this->bot)
                    ->sendMessage(
                        $clientBotUser->telegram_chat_id,
                        "✅Ваша СБП-оплата в размере $amount руб. прошла успешно (заказ №$orderId)!\nЗаказ принят в работу!"
                    );
            }
        }

        return "OK";
    }

    /**
     * @throws ValidationException
     */
    public function sbpTablePayment(array $data, $table): string
    {
        if (is_null($this->bot) || is_null($this->botUser)) {
            throw new HttpException(404, "Бот не найден!");
        }

        $bot = $this->bot;
        $botUser = $this->botUser;


        $isSelf = ($this->data["is_self"] ?? "false") === "true";

        $client = isset($data["client"]) ? json_decode($data["client"]) : null;
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, "Ошибка в JSON клиента");
        }
        if (is_null($client)) {
            throw new HttpException(400, "Не указаны данные клиента");
        }

        $basketQuery = \App\Models\Basket::query()
            ->where("bot_id", $bot->id)
            ->where("table_id", $table->id)
            ->whereNull("ordered_at");

        if ($isSelf) {
            $basketQuery->where("bot_user_id", $botUser->id);
        }

        $basket = $basketQuery->get();

        $items = [];
        $tmpOrderProductInfo = [];
        $currency = "RUB";
        $summaryPrice = 0;
        $summaryCount = 0;
        $description = "";

        $config = $bot->config ?? null;
        if (is_null($config)) {
            throw new HttpException(400, "Система не настроена!");
        }

        $sbpItem = Collection::make($config)->where("key", "sbp")->first();
        $sbp = $sbpItem->value ?? null;

        if (!isset($sbp["tinkoff"])) {
            throw new HttpException(400, "Настройки Tinkoff не найдены!");
        }

        $terminalKey = $sbp["tinkoff"]["terminal_key"] ?? null;
        $terminalPassword = $sbp["tinkoff"]["terminal_password"] ?? null;
        $tax = $sbp["tinkoff"]["tax"] ?? "osn";
        $vat = $sbp["tinkoff"]["vat"] ?? "vat20";

        foreach ($basket as $basketItem) {
            $product = $basketItem->product ?? null;
            $count = $basketItem->count ?? 0;
            $price = 0;

            if ($product) {
                $price = $product->current_price ?? 0;
                $description .= "$product->title x$count = $price,\n";
                $tmpOrderProductInfo[] = (object)[
                    "title" => $product->title,
                    "count" => $count,
                    "price" => $price,
                    "frontpad_article" => $product->frontpad_article ?? null,
                    "iiko_article" => $product->iiko_article ?? null,
                ];
                $price *= $count;
            }

            $summaryCount += $count;
            $summaryPrice += $price;
        }

        $priceWithDiscount = max(0, $summaryPrice);
        $items[] = [
            'Name' => "Оплата столика",
            'Quantity' => 1,
            'Price' => $priceWithDiscount,
            'NDS' => $vat,
        ];

        $tinkoff = new TinkoffBankService(config('sbp.payments.tinkoff.url'), $terminalKey, $terminalPassword);

        $order = Order::query()->create([
            'bot_id' => $bot->id,
            'customer_id' => $botUser->id,
            'product_details' => [(object)[
                "data" => $data,
                "from" => $bot->title ?? $bot->bot_domain ?? $bot->id,
                "products" => $tmpOrderProductInfo,
            ]],
            'product_count' => $summaryCount,
            'summary_price' => $priceWithDiscount,
            'receiver_name' => $client->name ?? 'Нет имени',
            'receiver_phone' => $client->phone ?? 'Нет телефона',
            'table_id' => $table->id,
            'status' => OrderStatusEnum::NewOrder->value,
            'order_type' => OrderTypeEnum::InternalStore->value,
            'payed_at' => null,
        ]);

        $payment = [
            'OrderId' => $order->id,
            'Amount' => $priceWithDiscount,
            'Language' => 'ru',
            'Description' => "Оплата за обслуживание столика $table->number",
            'Email' => $botUser->email ?? '',
            'Phone' => $order->receiver_phone,
            'Name' => $order->receiver_name,
            'Taxation' => $tax,
        ];

        $paymentURL = $tinkoff->paymentURL($payment, $items);
        if (!$paymentURL) {
            throw new HttpException(400, "Ошибка формирования платежной ссылки!");
        }

        $payment_id = $tinkoff->payment_id ?? Str::uuid()->toString();

        Transaction::query()->create([
            'user_id' => $botUser->user_id,
            'bot_user_id' => $botUser->id,
            'bot_id' => $bot->id,
            'payload' => $payment_id,
            'currency' => $currency,
            'total_amount' => $summaryPrice,
            'status' => 0,
            'products_info' => (object)[
                "payment_id" => $payment_id,
                "payload" => $description,
                "prices" => $items,
            ],
        ]);

        return $paymentURL;
    }


    public function invoiceServiceLink(array $data): object
    {
        if (is_null($this->bot) || is_null($this->botUser))
            throw new HttpException(404, "Бот не найден!");

        $terminalKey = env("TINKOFF_TERMINAL_KEY");
        $terminalPassword = env("TINKOFF_TERMINAL_PASSWORD");
        $tax = env("TINKOFF_PAYMENT_TAX");
        $vat = env("TINKOFF_PAYMENT_VAT");

        $items[] = [
            'Name' => "Оплата услуг сервиса",
            'Quantity' => 1,
            'Price' => $data["amount"],    //цена товара в рублях
            'NDS' => $vat ?? 'vat20',  //НДС //tax
        ];

        $tinkoff = new TinkoffBankService(config('sbp.payments.tinkoff.url'), $terminalKey, $terminalPassword);

        $payment = [
            'OrderId' => $data["order_id"] ?? Str::uuid(),        //Ваш идентификатор платежа
            'Amount' => $data["amount"],           //сумма всего платежа в рублях
            'Language' => 'ru',            //язык - используется для локализации страницы оплаты
            'Description' => "Оплата услуг сервиса",   //описание платежа
            'Email' => $this->botUser->email ?? env("TINKOFF_INVOICE_ERROR_EMAIL") ?? '',//email покупателя
            'Phone' => $this->botUser->phone ?? env("TINKOFF_INVOICE_ERROR_PHONE") ?? '',   //телефон покупателя
            'Name' => $this->botUser->fio_from_telegram ?? $this->botUser->username ?? '', //Имя покупателя
            'Taxation' => $tax,     //Налогооблажение
            'CustomerKey' => $this->botUser->id
        ];


//Получение url для оплаты
        $paymentURL = $tinkoff->paymentURL($payment, $items);

        if (!$paymentURL)
            throw new HttpException(400, "Ошибка формирования платежной ссылки!");

        return (object)[
            "url" => $paymentURL
        ];

    }

    public function invoiceLink(array $data, $needKeyboard = false)
    {
        if (is_null($this->bot) || is_null($this->botUser) || is_null($this->slug))
            throw new HttpException(404, "Бот не найден!");

        $bot = $this->bot;
        $botUser = $this->botUser;
        $slug = $this->slug;
        $currency = "RUB";
        $isRecurrent = ($data["is_recurrent"] ?? false) == "true";


        $config = $slug->config ?? null;

        if (is_null($config))
            throw new HttpException(400, "Система не настроена!");

        $sbp = Collection::make($config)
            ->where("key", "sbp")
            ->first()["value"] ?? null;

        $terminalKey = $sbp["tinkoff"]["terminal_key"] ?? null;
        $terminalPassword = $sbp["tinkoff"]["terminal_password"] ?? null;
        $tax = $sbp["tinkoff"]["tax"] ?? "osn";
        $vat = $sbp["tinkoff"]["vat"] ?? "vat20";

        $items[] = [
            'Name' => $data["description"],
            'Quantity' => 1,
            'Price' => $data["amount"],    //цена товара в рублях
            'NDS' => $vat ?? 'vat20',  //НДС //tax
        ];

        $tinkoff = new TinkoffBankService(config('sbp.payments.tinkoff.url'), $terminalKey, $terminalPassword);

        $order = Order::query()
            ->create([
                'bot_id' => $this->bot->id,
                'customer_id' => $this->botUser->id,
                'product_count' => 1,
                'summary_price' => $data["amount"],
                'delivery_note' => $data["description"],
                'receiver_name' => $data["name"],
                'receiver_phone' => $data["phone"],
                'status' => OrderStatusEnum::NewOrder->value,
                'order_type' => OrderTypeEnum::InternalStore->value,
            ]);

        $payment = [
            'OrderId' => $order->id ?? Str::uuid(),        //Ваш идентификатор платежа
            'Amount' => $data["amount"],           //сумма всего платежа в рублях
            'Language' => 'ru',            //язык - используется для локализации страницы оплаты
            'Description' => $data["description"],   //описание платежа
            'Email' => $data["email"] ?? '',//email покупателя
            'Phone' => $data["phone"] ?? '',   //телефон покупателя
            'Name' => $data["name"] ?? '', //Имя покупателя
            'Taxation' => $tax,     //Налогооблажение
            'CustomerKey' => $botUser->id
        ];

        if ($isRecurrent)
            $payment["Recurrent"] = 'Y';


//Получение url для оплаты
        $paymentURL = $tinkoff->paymentURL($payment, $items);

        if (!$paymentURL)
            throw new HttpException(400, "Ошибка формирования платежной ссылки!");

        $payment_id = $tinkoff->payment_id ?? null;

        /*  $keyboard = [
              [
                  ["text" => "Проверить оплату СБП", "callback_data" => "/test_invoice_sbp_tinkoff_automatic $payment_id $slug->id"]
              ],
          ];*/

        BotMethods::bot()
            ->whereBot($this->bot)
            ->sendMessage(
                $botUser->telegram_chat_id,
                "<code>$paymentURL</code> - нажмите чтобы скопировать\n\nВам необходимо подтвердить факт платежа клиента <code>" . ($data["phone"] ?? '') . "</code>. Сумма платежа " . $data["amount"] . " руб. (Зака №$order->id)"
            //   $keyboard
            );
    }

    /**
     * @throws ValidationException
     */
    public function sbpForShop($order, $message = null): ?object
    {
        if (is_null($this->bot) || is_null($this->botUser))
            throw new HttpException(404, "Бот не найден!");

        $bot = $this->bot;
        $botUser = $this->botUser;

        $currency = "RUB";

        $config = $bot->config ?? null;

        if (is_null($config))
            throw new HttpException(400, "Система не настроена!");

        $sbp = $config["sbp"] ?? null;
        $selectedSBPBank = $sbp["selected_sbp_bank"] ?? "tinkoff";

        $terminalKey = $sbp[$selectedSBPBank]["terminal_key"] ?? null;
        $terminalPassword = $sbp[$selectedSBPBank]["terminal_password"] ?? null;
        $tax = $sbp[$selectedSBPBank]["tax"] ?? "osn";
        $vat = $sbp[$selectedSBPBank]["vat"] ?? "vat20";

        $items[] = [
            'Name' => "Товар магазина",
            'Quantity' => 1,
            'Price' => $order->summary_price,    //цена товара в рублях
            'NDS' => $vat ?? 'vat20',  //НДС //tax
        ];

        $deliveryPrice = $order->delivery_price ?? 0;

        if ($deliveryPrice > 0) {
            $items[] = [
                'Name' => "Доставка",
                'Quantity' => 1,
                'Price' => $order->delivery_price,    //цена товара в рублях
                'NDS' => $vat ?? 'vat20',  //НДС //tax
            ];

            $message .= "\nЦена доставки: <b>$deliveryPrice</b> руб.";
            $message .= "\nИтого с доставкой: <b>" . ($order->summary_price + $deliveryPrice) . "</b> руб.";

        }

        $tinkoff = new TinkoffBankService(config('sbp.payments.tinkoff.url'), $terminalKey, $terminalPassword);


        $payment = [
            'OrderId' => $order->id,        //Ваш идентификатор платежа
            'Amount' => $order->summary_price + $deliveryPrice,           //сумма всего платежа в рублях
            'Language' => 'ru',            //язык - используется для локализации страницы оплаты
            'Description' => "Оплата заказа " . ($deliveryPrice > 0 ? "и доставки" : ""),   //описание платежа
            'Email' => $this->botUser->email ?? '',//email покупателя
            'Phone' => $order->receiver_phone,   //телефон покупателя
            'Name' => $order->receiver_name, //Имя покупателя
            'Taxation' => $tax,    //Налогооблажение
            'CustomerKey' => $botUser->id,    //покупатель
        ];


//Получение url для оплаты
        $paymentURL = $tinkoff->paymentURL($payment, $items);

        if (!$paymentURL) {
            \App\Facades\BotMethods::bot()
                ->whereBot($this->bot)
                ->sendMessage(
                    $this->botUser->telegram_chat_id, "Ошибка формирования платежной ссылки!");


            return null;

        }

        $payment_id = $tinkoff->payment_id ?? Str::uuid()->toString();

        // $payload = Str::uuid()->toString();

        Transaction::query()->create([
            'user_id' => $botUser->user_id,
            'bot_user_id' => $botUser->id,
            'bot_id' => $bot->id,
            'payload' => $payment_id,
            'currency' => $currency,
            'total_amount' => $order->summary_price,
            'status' => 0,
            'products_info' => (object)[
                "order_id" => $order->id,
                "payment_id" => $payment_id,
                "payload" => $payment ?? null,
            ],
        ]);

        /*
                $keyboard = [
                    [
                        ["text" => "💳Перейти к оплате", "url" => "$paymentURL"],
                    ],

                ];

                \App\Facades\BotMethods::bot()
                    ->whereBot($this->bot)
                    ->sendInlineKeyboard(
                        $this->botUser->telegram_chat_id,
                        $message ?? "Оплатите заказ, для того чтоб мы приступили к его выполнению:)",
                        $keyboard
                    );*/

        $keyboard = [
            /*   [
                   ["text" => "Автоматическая проверка СБП", "callback_data" => "/test_foods_sbp_tinkoff_automatic $payment_id $order->id"]
               ],
               [
                   ["text" => "Клиент оплатил (прислали скриншот)", "callback_data" => "/test_foods_manual_payment $botUser->id $order->id"]
               ]*/
        ];


        BotMethods::bot()
            ->whereBot($this->bot)
            ->sendInlineKeyboard(
                $bot->order_channel,
                "<b>⚠Внимание заказ СБП! № заказа: $order->id\n</b>\nОжидаемая сумма платежа <b>" . ($order->summary_price + $deliveryPrice) . " руб. ($order->summary_price руб - цена заказа и $deliveryPrice руб - цена доставки)</b>. Клиент еще не оплатил.",
                $keyboard
            );

        return (object)[
            "url" => $paymentURL
        ];
    }

    /**
     * @throws ValidationException
     */
    public function checkout(): void
    {
        if (is_null($this->bot) || is_null($this->botUser))
            throw new HttpException(404, "Бот не найден!");

        $bot = $this->bot;
        $botUser = $this->botUser;

        $config = $this->getConfig();

        $taxSystemCode = $bot->company->vat_code ?? 1;


        $basket = \App\Models\Basket::query()
            ->where("bot_id", $this->bot->id)
            ->where("bot_user_id", $this->botUser->id)
            ->whereNull("ordered_at")
            ->get();
        $prices = [];

        $currency = "RUB";
        $providerData = (object)[
            "receipt" => []
        ];

        $summaryPrice = 0;
        $summaryCount = 0;
        $description = "";


        foreach ($basket as $basketItem) {


            $product = $basketItem->product ?? null;
            $collection = $basketItem->collection ?? null;
            $count = $basketItem->count ?? 0;
            $price = 0;

            if (!is_null($product)) {
                $price = ($product->current_price ?? 0) * $count;


                $prices[] = [
                    "label" => $product->title,
                    "amount" => $price * 100
                ];

                $description .= "$product->title x$count = $price\n";

                $providerData->receipt[] =
                    (object)[
                        "description" => "$product->title",
                        "quantity" => "$count.00",
                        "amount" => (object)[
                            "value" => $price * 100,
                            "currency" => $currency
                        ],
                        "vat_code" => $taxSystemCode
                    ];
            }

            if (!is_null($collection)) {
                $collectionTitles = "";


                $params = is_null($item->params ?? null) ? null : (object)$basketItem->params;


                foreach (($collection->products ?? []) as $basketProduct) {


                    if (!in_array($basketProduct->id, $params->ids ?? []))
                        continue;

                    $collectionTitles .= "-" . $basketProduct->title . "\n";
                    $price += $product->current_price ?? 0;
                }

                $price = $price * $basketItem->count;

                $prices[] = [
                    "label" => "Коллекция `" . ($collection->title) . "`: " . $collectionTitles,
                    "amount" => $price * 100
                ];

                $description .= "Коллекция $collection->title x$count = $price\n";

                $providerData->receipt[] =
                    (object)[
                        "description" => "Коллекция `" . ($collection->title) . "`: " . $collectionTitles,
                        "quantity" => "$count.00",
                        "amount" => (object)[
                            "value" => $price * 100,
                            "currency" => $currency
                        ],
                        "vat_code" => $taxSystemCode
                    ];


            }


            $summaryCount += $count;
            $summaryPrice += $price;
        }


        if ($summaryPrice < 100) {
            \App\Facades\BotMethods::bot()
                ->whereBot($this->bot)
                ->sendMessage(
                    $this->botUser->telegram_chat_id, "❗Сумма заказа должна быть больше чем <strong>100 руб 00 коп.</strong>❗");

            return;
        }

        $payload = Str::uuid()->toString();

        $providerToken = $bot->payment_provider_token;

        Transaction::query()->create([
            'user_id' => $botUser->user_id,
            'bot_user_id' => $botUser->id,
            'bot_id' => $bot->id,
            'payload' => $payload,
            'currency' => $currency,
            'total_amount' => $summaryPrice,
            'status' => 0,
            'products_info' => (object)[
                "payload" => $tmpDescription ?? null,
                "prices" => $prices,
            ],
        ]);

        $needs = $config["base_payment_service"]["needs"] ?? [
            "need_name" => true,
            "need_phone_number" => true,
            "need_email" => false,
            "need_shipping_address" => false,
            "send_phone_number_to_provider" => false,
            "send_email_to_provider" => false,
            "is_flexible" => false,
            "disable_notification" => false,
            "protect_content" => false,
        ];

        $btnPaymentText = "\xF0\x9F\x8E\xB2Оплатить";

        $keyboard = [
            [
                ["text" => $btnPaymentText, "pay" => true],
            ],

        ];

        $title = $config["base_payment_service"]["checkout_title"] ?? "Заказ товара";
        $description = $config["base_payment_service"]["checkout_description"] ?? "Ваш товар";

        \App\Facades\BotMethods::bot()
            ->whereBot($this->bot)
            ->sendInvoice(
                $this->botUser->telegram_chat_id,
                title: $title,
                description: $description,
                prices: $prices,
                payload: $payload,
                providerToken: $providerToken,
                currency: $currency,
                needs: $needs,
                keyboard: $keyboard,
                providerData: $providerData
            );
    }

    /**
     * @throws ValidationException
     */
    public function checkoutLink(array $data)
    {

        if (is_null($this->bot) || is_null($this->botUser) || is_null($this->slug))
            throw new HttpException(404, "Бот не найден!");

        $validator = Validator::make($data, [
            "products.*" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);


        $bot = $this->bot;
        $botUser = $this->botUser;
        $slug = $this->slug;

        //  Log::info("slug config".print_r($slug->config, true));

        $taxSystemCode = (Collection::make($slug->config)
            ->where("key", "tax_system_code")
            ->first())["value"] ?? $bot->company->vat_code ?? 1;

        $tmpProducts = $data["products"];
        $ids = Collection::make($tmpProducts)
            ->pluck("id")
            ->toArray();


        $products = Product::query()
            ->whereIn("id", is_array($ids) ? $ids : [$ids])
            ->get();


        $prices = [];
        $currency = "RUB";
        $providerData = (object)[
            "receipt" => []
        ];

        $summaryPrice = 0;
        $summaryCount = 0;
        $discount = $data["discount"] ?? 0;
        $tmpDescription = "";

        foreach ($products as $product) {

            $tmpCount = array_values(array_filter($tmpProducts, function ($item) use ($product) {
                return $item->id === $product->id;
            }))[0]->count ?? 0;

            $tmpPrice = ($product->current_price ?? 0) * $tmpCount;


            $prices[] = [
                "label" => $product->title,
                "amount" => $tmpPrice * 100
            ];

            $tmpDescription .= "$product->title x$tmpCount = $tmpPrice\n";

            $providerData->receipt[] =
                (object)[
                    "description" => "Заказ товара",
                    "quantity" => "$tmpCount.00",
                    "amount" => (object)[
                        "value" => $tmpPrice * 100,
                        "currency" => $currency
                    ],
                    "vat_code" => $taxSystemCode
                ];

            $summaryCount += $tmpCount;
            $summaryPrice += $tmpPrice;
        }


        $payload = Str::uuid()->toString();

        $providerToken = $bot->payment_provider_token;

        Transaction::query()->create([
            'user_id' => $botUser->user_id,
            'bot_user_id' => $botUser->id,
            'bot_id' => $bot->id,
            'payload' => $payload,
            'currency' => $currency,
            'total_amount' => max(1, $summaryPrice - $discount),
            'status' => 0,
            'products_info' => (object)[
                "payload" => $tmpDescription ?? null,
                "prices" => $prices,
            ],
        ]);

        $needs = [
            "need_name" => (Collection::make($slug->config)
                    ->where("key", "need_name")
                    ->first())["value"] ?? false,
            "need_phone_number" => (Collection::make($slug->config)
                    ->where("key", "need_phone_number")
                    ->first())["value"] ?? false,
            "need_email" => (Collection::make($slug->config)
                    ->where("key", "need_email")
                    ->first())["value"] ?? false,
            "need_shipping_address" => (Collection::make($slug->config)
                    ->where("key", "need_shipping_address")
                    ->first())["value"] ?? false,
            "send_phone_number_to_provider" => (Collection::make($slug->config)
                    ->where("key", "need_send_phone_number_to_provider")
                    ->first())["value"] ?? false,
            "send_email_to_provider" => (Collection::make($slug->config)
                    ->where("key", "need_send_email_to_provider")
                    ->first())["value"] ?? false,
            "is_flexible" => (Collection::make($slug->config)
                    ->where("key", "is_flexible")
                    ->first())["value"] ?? false,
            "disable_notification" => (Collection::make($slug->config)
                    ->where("key", "disable_notification")
                    ->first())["value"] ?? false,
            "protect_content" => (Collection::make($slug->config)
                    ->where("key", "protect_content")
                    ->first())["value"] ?? false,
        ];


        $title = (Collection::make($slug->config)
            ->where("key", "checkout_title")
            ->first())["value"] ?? "Заказ товара";

        $description = (Collection::make($slug->config)
            ->where("key", "checkout_description")
            ->first())["value"] ?? "Ваш товар";


        return \App\Facades\BotMethods::bot()
            ->whereBot($this->bot)
            ->createInvoiceLink(
                $this->botUser->telegram_chat_id,
                title: $title,
                description: $description,
                prices: $prices,
                payload: $payload,
                providerToken: $providerToken,
                currency: $currency,
                needs: $needs,
                providerData: $providerData
            );
    }
}
