<?php

namespace App\Services\Helpers;

use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Order;
use App\Models\Tenant\Partner;
use App\Models\Tenant\Tenant;
use App\Services\MessageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait BasketHelper
{

    protected function safeInt($value): ?int
    {
        if (is_null($value) || $value === '' || $value === false) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Безопасное приведение к float или null
     */
    protected function safeFloat($value): ?float
    {
        if (is_null($value) || $value === '' || $value === false) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * Подготовка информации об ограничениях по здоровью
     */
    private function fsPrepareDisabilities(): string
    {
        $hasDisability = ($this->data["has_disability"] ?? "false") === "true";

        if (!$hasDisability) {
            return '';
        }

        $disabilities = json_decode($this->data["disabilities"] ?? '[]', true) ?: [];
        $allergy = $this->data["allergy"] ?? 'не указана';

        $disabilitiesText = "<b>Внимание!</b> у клиента присутствуют ограничения по здоровью!\n";

        foreach ($disabilities as $disability) {
            if ($disability === "пищевая аллергия") {
                $disabilitiesText .= "-<em>$disability на: $allergy</em>\n";
            } else {
                $disabilitiesText .= "-<em>$disability</em>\n";
            }
        }

        return $disabilitiesText . "\n";
    }

    /**
     * Подготовка информации о пользователе для сообщения
     */
    private function fsPrepareUserInfo($order, $cashback = 0): string
    {
        $time = $this->data["time"] ?? null;
        $persons = $this->data["persons"] ?? 1;
        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0] ?? 'Не указан';
        $whenReady = ($this->data["when_ready"] ?? "false") === "true";
        $needPickup = ($this->data["need_pickup"] ?? "false") === "true";
        $useCashback = ($this->data["use_cashback"] ?? "false") === "true";
        $address = $this->data["address"] ?? "";
        $lat = $this->data["lat"] ?? 0;
        $lng = $this->data["lng"] ?? 0;

        $orderId = $order->id ?? '-';
        $userId = $this->tenantUser->id ?? '-';
        $name = $this->data["name"] ?? 'Не указано';
        $phone = $this->data["phone"] ?? 'Не указано';
        $money = $this->data["money"] ?? 'Не указано';
        $info = $this->data["info"] ?? 'Не указано';
        $cashbackText = $useCashback ? $cashback : "нет";
        $timeText = $whenReady ? "По готовности" : Carbon::parse($time)->format('Y-m-d H:i');
        $statusIcon = $whenReady ? "🟢" : "🟡";

        if (!$needPickup) {
            return sprintf(
                "\n%s Заказ №: <b>%s</b>\nИдентификатор клиента: <b>%s</b>\n\n" .
                "<b>Данные для доставки:</b>\n" .
                "Ф.И.О.: <b>%s</b>\n" .
                "Номер телефона: <b>%s</b>\n" .
                "Адрес: <code>%s,%s</code><code>(%s, %s)</code>\n" .
                "Цена доставки: %s руб.\n" .
                "Дистанция: %s км\n" .
                "Номер подъезда: %s\n" .
                "Номер этажа: %s\n" .
                "Тип оплаты: <b>%s</b>\n" .
                "Сдача с: %s руб.\n" .
                "Доп.инфо: %s\n" .
                "Использован кэшбэк: %s\n" .
                "Доставить ко времени: %s\n" .
                "Число персон: <b>%s</b> чел.\n",
                $statusIcon,
                $orderId,
                $userId,
                $name,
                $phone,
                $address,
                $this->data["flat_number"] ?? "",
                $lat,
                $lng,
                $order->delivery_price ?? 0,
                $order->delivery_range ?? 0,
                $this->data["entrance_number"] ?? 'Не указано',
                $this->data["floor_number"] ?? 'Не указано',
                $cash,
                $money,
                $info,
                $cashbackText,
                $timeText,
                $persons
            );
        }

        return sprintf(
            "\n%s Заказ №: <b>%s</b>\nИдентификатор: <b>%s</b>\n\n" .
            "<b>Данные для самовывоза:</b>\n" .
            "Ф.И.О.: <b>%s</b>\n" .
            "Номер телефона: <b>%s</b>\n" .
            "Тип оплаты: <b>%s</b>\n" .
            "Сдача с: %s руб.\n" .
            "Доп.инфо: %s\n" .
            "Использован кэшбэк: %s\n" .
            "Заберу в: %s\n" .
            "Число персон: <b>%s</b> чел.\n",
            $statusIcon,
            $orderId,
            $userId,
            $name,
            $phone,
            $cash,
            $money,
            $info,
            $cashbackText,
            $timeText,
            $persons
        );
    }

    /**
     * Отправка результата клиенту
     */
    private function fsSendResult($message): void
    {
        MessageService::call()
            ->sendMessage([
                "message" => ("Спасибо, ваш заказ появился в нашей системе:\n\n<em>$message</em>") .
                    "\nВы можете оставить отзыв с фото и получить от нас дополнительный КэшБэк!",
                "keyboard" => [
                    [
                        ["text" => "📢Написать отзыв с фото"],
                    ],
                ],
            ]);
    }

    /**
     * Добавление префикса города к адресу
     */
    private function ensureCityPrefix(string $address): string
    {
        $patterns = [
            '/\bг\.\b/ui',
            '/\bгород\b/ui',
            '/\bс\.\b/ui',
            '/\bсело\b/ui',
            '/\bпос\.\b/ui',
            '/\bпос[её]лок\b/ui',
            '/\bпгт\b/ui',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $address)) {
                return trim($address);
            }
        }

        return 'г. ' . trim($address);
    }

    /**
     * Добавление префикса улицы
     */
    private function ensureStreetPrefix(string $street): string
    {
        $patterns = [
            '/\bул\.\b/ui',
            '/\bулица\b/ui',
            '/\bпр-т\b/ui',
            '/\bпросп\.\b/ui',
            '/\bпроспект\b/ui',
            '/\bпер\.\b/ui',
            '/\bпереулок\b/ui',
            '/\bбул\.\b/ui',
            '/\bбульвар\b/ui',
            '/\bпроезд\b/ui',
            '/\bш\.\b/ui',
            '/\bшоссе\b/ui',
            '/\bнаб\.\b/ui',
            '/\bнабережная\b/ui',
            '/\bпл\.\b/ui',
            '/\bплощадь\b/ui',
            '/\bтракт\b/ui',
            '/\bтуп\.\b/ui',
            '/\bтупик\b/ui',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $street)) {
                return trim($street);
            }
        }

        return 'ул. ' . trim($street);
    }

    /**
     * Формирование полного адреса
     */
    private function fsPrepareAddress(): string
    {
        $city = $this->ensureCityPrefix($this->data["city"] ?? "");
        $street = $this->ensureStreetPrefix($this->data["street"] ?? "");

        return "$city, $street, " . ($this->data["building"] ?? "");
    }

    /**
     * Генерация и сохранение PDF-чека
     * Возвращает путь к сохранённому файлу или null
     */
    private function fsPrintPDFInfo($order, $summaryPrice, $summaryCount, $tmpOrderProductInfo, $cashback = 0): ?string
    {
        $useCashback = ($this->data["use_cashback"] ?? "false") === "true";
        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0] ?? 'Не указан';

        $address = $this->fsPrepareAddress();
        $userId = $this->tenantUser->telegram_chat_id ?? 'Не указан';

        $paymentInfo = sprintf(
            $this->tenant->settings["payment_info"] ??
            "Оплатите заказ по реквизитам:\nСбер XXXX-XXXX-XXXX-XXXX Иванов И.И. или переводом по номеру +7(000)000-00-00 - указав номер %s\nИ отправьте нам скриншот оплаты со словом <strong>оплата</strong>",
            $order->id ?? '-'
        );

        $disabilitiesText = $this->fsPrepareDisabilities() ?: 'не указаны';
        $distance = $this->data["distance"] ?? 0;
        $deliveryPrice = $this->data["delivery_price"] ?? 0;
        $currentDate = Carbon::now("+3:00")->format("Y-m-d H:i:s");
        $number = Str::uuid()->toString();
        $tenantTitle = $this->tenant->title ?? $this->tenant->bot_domain ?? 'CashMan';

        // Проверяем наличие Mpdf
        if (!class_exists('\Mpdf\Mpdf')) {
            Log::warning('[PDF] Mpdf не установлен, чек не сгенерирован');
            return null;
        }

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => 'dejavusans',
            ]);

            $html = view("pdf.order", [
                "title" => $tenantTitle,
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
                "disabilitiesText" => $disabilitiesText,
                "totalPrice" => $summaryPrice,
                "discount" => $useCashback ? $cashback : 0,
                "totalCount" => $summaryCount,
                "distance" => $distance,
                "deliveryPrice" => $deliveryPrice,
                "currentDate" => $currentDate,
                "code" => "Без промокода",
                "promoCount" => "0",
                "paymentInfo" => $paymentInfo,
                "products" => $tmpOrderProductInfo,
                "info" => $this->data["info"] ?? 'Не указано',
            ])->render();

            $mpdf->WriteHTML($html);

            // ==========================================
            // 🆕 СОХРАНЕНИЕ ФАЙЛА В STORAGE
            // ==========================================
            $fileName = "order-{$order->id}-{$number}.pdf";
            $directory = "orders/{$order->tenant_id}/{$order->id}";
            $fullPath = "{$directory}/{$fileName}";

            // Получаем содержимое PDF как строку
            $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

            // Сохраняем в storage
            \Illuminate\Support\Facades\Storage::disk('public')->put($fullPath, $pdfContent);

            // Сохраняем путь к файлу в заказе (если есть поле)
            if (Schema::hasColumn('orders', 'invoice_path')) {
                $order->invoice_path = $fullPath;
                $order->save();
            }

            Log::info("[PDF] Чек сохранён: {$fullPath}");

            return $fullPath;
        } catch (\Throwable $e) {
            Log::error('[PDF] Ошибка генерации чека: ' . $e->getMessage());
            return null;
        }
    }
    /**
     * Подготовка заказа для FrontPad
     * ИСПРАВЛЕНО: неопределённые переменные $tenant и $bot
     */
    private function fsPrepareFrontPad($order, $tmpOrderProductInfo, $partnerId = null): void
    {
        // ИСПРАВЛЕНИЕ: используем $this->tenant
        $tenant = $this->tenant;
        $bot = is_null($partnerId) ? $tenant : Tenant::query()->find($partnerId);

        if (is_null($bot)) {
            return;
        }

        $frontPad = $bot->frontPad ?? null;

        if (is_null($frontPad)) {
            return;
        }

        $persons = $this->data["persons"] ?? 1;
        $whenReady = ($this->data["when_ready"] ?? "false") === "true";
        $time = $this->data["time"] ?? null;
        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0] ?? 'Не указан';

        Log::info("tmpOrderProductInfo=>" . print_r($tmpOrderProductInfo, true));

        // Примечание: BusinessLogic должен быть доступен через сервис-контейнер
        if (class_exists('\App\Services\BusinessLogic')) {
            \App\Services\BusinessLogic::frontPad()
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
                    'datetime' => $whenReady ? null : Carbon::parse($time)->format('Y-m-d H:i:s'),
                    'cash' => $cash,
                ]);
        } else {
            Log::warning('BusinessLogic не доступен, FrontPad заказ не создан');
        }
    }

    /**
     * Подготовка заметки для доставки
     * ИСПРАВЛЕНО: инвертированная логика is_null
     */
    private function fsPrepareDeliveryNote(): string
    {
        $disabilitiesText = $this->fsPrepareDisabilities();

        $persons = $this->data["persons"] ?? 1;
        $whenReady = ($this->data["when_ready"] ?? "false") === "true";
        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0] ?? 'Не указан';
        $time = $this->data["time"] ?? null;

        // ИСПРАВЛЕНИЕ: логика инвертирована — добавляем, если НЕ null
        $note = ($this->data["info"] ?? 'Не указано') . "\n";

        if (!is_null($this->data["entrance_number"] ?? null)) {
            $note .= "Номер подъезда: " . $this->data["entrance_number"] . "\n";
        }

        if (!is_null($this->data["floor_number"] ?? null)) {
            $note .= "Номер этажа: " . $this->data["floor_number"] . "\n";
        }

        $note .= "Тип оплаты: " . $cash . "\n";

        if (!is_null($this->data["money"] ?? null)) {
            $note .= "Сдача с: " . $this->data["money"] . "\n";
        }

        $note .= "Время доставки: " . ($whenReady ? "По готовности" : Carbon::parse($time)->format('Y-m-d H:i')) . "\n";
        $note .= "Число персон: " . $persons . "\n";
        $note .= "Ограничения:\n" . ($disabilitiesText ?: 'не указаны');

        return $note;
    }

    /**
     * Сохранение информации о клиенте
     */
    protected function storeClientInfoAsContact(): void
    {
        $vowels = ["(", ")", "-"];
        $phone = $this->data["phone"] ?? $this->tenantUser->phone ?? null;
        $filteredPhone = !is_null($phone) ? str_replace($vowels, "", $phone) : null;

        $this->tenantUser->name = $this->data["name"] ?? $this->tenantUser->name ?? null;
        $this->tenantUser->phone = $filteredPhone;
        $this->tenantUser->save();
    }

    /**
     * Использование кэшбэка для оплаты
     */
    private function useCashBackForPayment($discount): void
    {
        $useCashback = ($this->data["use_cashback"] ?? "false") === "true";

        if (!$useCashback) {
            return;
        }

        // TODO: Раскомментировать, когда CashBackService будет готов
        /* CashBackService::call()
            ->removeCashBack(
                $discount ?? 0,
                "Автоматическое списание скидки на покупку товара"
            );*/
    }

    /**
     * Расчёт скидки по кэшбэку
     */
    private function prepareCashbackDiscount($summaryPrice): float
    {
        $useCashback = ($this->data["use_cashback"] ?? "false") === "true";

        if (!$useCashback) {
            return 0.0;
        }

        $maxCashbackUsePercent = $this->tenant->settings["max_cashback_use_percent"] ?? 0;
        $maxUserCashback = $this->tenantUser->cashback?->amount ?? 0;
        $cashBackAmount = ($summaryPrice * ($maxCashbackUsePercent / 100));

        return min($cashBackAmount, $maxUserCashback);
    }

    /**
     * Отправка чека об оплате в канал
     * ИСПРАВЛЕНО: синтаксическая ошибка, добавлен метод
     */
    private function sendPaidReceiptToChannel($order, $message): void
    {
        $uploadedPhoto = $this->uploadedImage;
        $hasPhoto = !is_null($uploadedPhoto);
        $whenReady = ($this->data["when_ready"] ?? "false") === "true";

        $tmpMessage = '';

        if ($hasPhoto) {
            $ext = $uploadedPhoto->getClientOriginalExtension();
            $imageName = Str::uuid()->toString() . "." . $ext;
            $uploadedPhoto->storeAs($imageName);

            $photoPath = storage_path() . "/app/$imageName";

            // ИСПРАВЛЕНИЕ: убрана точка с запятой, разрывающая конкатенацию
            $tmpMessage =
                '<p><strong>#оплатачеком</strong></p>' .
                '<p>' . ($whenReady ? '🟢' : '🟡') .
                ' <strong>Заказ №:</strong> ' . ($order->id ?? '-') . '</p>' .
                '<p><strong>Идентификатор клиента:</strong> ' . ($this->tenantUser->id ?? '-') . '</p>' .
                '<p><strong>Пользователь:</strong> ' . ($order->receiver_name ?? '-') . '</p>' .
                '<p><strong>Телефон:</strong> ' . ($order->receiver_phone ?? '-') . '</p>' .
                '<br>' .
                '<p><strong>Пояснение к оплате:</strong> ' . ($this->data["image_info"] ?? 'не указано') . '</p>' .
                '<p><strong>Ссылка на фото:</strong> ' . $photoPath . '</p>';
        }

        $thread = $this->tenant->topics["orders"] ?? null;

        if ($tmpMessage && $thread) {
            MessageService::call()
                ->sendMessage([
                    "message" => $tmpMessage,
                    "thread_id" => $thread,
                ]);
        }

        // TODO: Доделать отправку фото в канал
    }

    /**
     * Оформление заказа из магазина еды
     * ОБНОВЛЕНО:
     * - Один диалог на заказ (не создаются лишние)
     * - Все сообщения клиенту помечены is_system: true
     * - Чёткое разделение: клиент / партнёры / CRM
     * @throws ValidationException
     */
    private function foodShopCheckout(): string
    {
        /*
        {
            "kanban": {
            "enabled": true,
            "base_url": "https://crm.mypwa.ru/api/v1",
            "token": "kb_SyXvkcnhRu7hD0nZAOwga6blD1TFSUEyXNdW9UyQ",
            "board_uuid": "928e6e06-b9b0-4cca-a45c-0926ba7539f6",
            "order_thread": 0,
            "auto_create_client": true,
            "sync_on_checkout": true
          }
        }*/
        // ==========================================
        // 0. 🆕 НАСТРОЙКА KANBAN CRM SDK
        // ==========================================
        $kanbanConfig = $this->tenant->settings['kanban'] ?? [];
        $kanbanEnabled = $kanbanConfig['enabled'] ?? false;
        $kanbanBoardUuid = $kanbanConfig['board_uuid'] ?? null;
        $kanbanBaseUrl = $kanbanConfig['base_url'] ?? config('kanban.base_url');
        $kanbanToken = $kanbanConfig['token'] ?? config('kanban.token');
        $kanbanThread = $kanbanConfig['order_thread'] ?? 0; // В какую колонку создавать заказы


        if ($kanbanEnabled && $kanbanBoardUuid && $kanbanToken) {
            try {
                \Exxxar\Kanban\Facades\Kanban::setBaseUrl($kanbanBaseUrl)
                    ->setToken($kanbanToken)
                    ->setTimeout(30)
                    ->setConnectTimeout(10)
                    ->setRetryTimes(3)
                    ->setRetrySleep(100)
                    ->setLoggingEnabled(true);
            } catch (\Throwable $e) {
                Log::error('[KanbanCRM] Ошибка настройки SDK: ' . $e->getMessage());
                $kanbanEnabled = false; // Отключаем при ошибке
            }
        } else {
            $kanbanEnabled = false;
        }

        $needPickup = ($this->data["need_pickup"] ?? "false") === "true";
        $deliveryPrice = $this->data["delivery_price"] ?? 0;
        $distance = $this->data["distance"] ?? 0;
        $lat = $this->data["lat"] ?? 0;
        $lng = $this->data["lng"] ?? 0;

        $locationId = $this->safeInt($this->data["location_id"] ?? null);
        $paymentType = $this->data["payment_type"] ?? 4;
        $deliveryDetails = json_decode($this->data["delivery_details"] ?? '[]', true) ?: [];
        $useCashback = ($this->data["use_cashback"] ?? "false") === "true";

        // ==========================================
        // 1. ОБРАБОТКА КОРЗИНЫ (без изменений)
        // ==========================================
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

            if ($isPartnersActive && !$isPartnersDisplaySelf && $partner->id == $this->tenant->id) {
                continue;
            }

            $deliveryDetails = (array) $deliveryDetails;

            if (empty($partnerProductBox[$partner->uuid])) {
                $partnerProductBox[$partner->uuid] = [
                    "id" => $partner->id,
                    "name" => $partner->name ?? $partner->slug ?? 'Без названия',
                    "title" => $partner->title ?? $partner->name ?? 'Без названия',
                    "message" => "",
                    "extra_charge" => (Partner::query()
                            ->where("tenant_id", $item->tenant_id)
                            ->where("tenant_partner_id", $item->tenant_partner_id)
                            ->first())?->extra_charge ?? 0,
                    "summary_price" => 0,
                    "summary_count" => 0,
                    "summary_discount" => 0,
                    "delivery_price" => $deliveryDetails[$partner->uuid]["price"] ?? 0,
                    "distance" => $deliveryDetails[$partner->uuid]["distance"] ?? 0,
                    "thread" => $partner->topics["delivery"] ?? $this->tenant->topics["delivery"] ?? null,
                    "products" => [],
                ];
            }

            $price = 0;
            $extraCharge = $partnerProductBox[$partner->uuid]["extra_charge"];
            $isWeightProduct = false;

            if (!is_null($product)) {
                $isWeightProduct = $product->is_weight_product ?? false;
                $count = $item->count;
                $currentPrice = $item->params["discount_price"] ?? $product->current_price ?? 0;

                $price = (($currentPrice) * (1 + $extraCharge / 100)) * $count;
                $unitOfMeasure = "ед.";

                if ($isWeightProduct) {
                    $weightConfig = $product->weight_config;
                    if (is_string($weightConfig)) {
                        $weightConfig = json_decode($weightConfig, true) ?? [];
                    }
                    $weightConfig = (object) ($weightConfig ?? []);
                    $step = $weightConfig->step ?? 100;

                    $price = ((($currentPrice) * (1 + $extraCharge / 100)) * $count) / $step;
                    $unitOfMeasure = "гр.";
                }

                $tmpMessage = is_null($comment)
                    ? sprintf("💎%s x%s %s=%s руб.\n", $product->name, $item->count, $unitOfMeasure, $price)
                    : sprintf("💎%s x%s %s=%s руб.\n<em>(%s)</em>\n", $product->name, $item->count, $unitOfMeasure, $price, $comment);

                $partnerProductBox[$partner->uuid]["message"] .= $tmpMessage;

                $tmpOrderProductInfo[] = (object) [
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
                $params = is_null($item->params) ? null : (is_array($item->params) ? (object) $item->params : $item->params);

                foreach (($collection->products ?? []) as $product) {
                    if (!in_array($product->id, $params->ids ?? [])) {
                        continue;
                    }

                    $collectionTitles .= "-" . $product->name . "\n";

                    $tmpOrderProductInfo[] = (object) [
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

                $tmpMessage = sprintf(
                    "💎Коллекция `%s` x%s=%s руб.:\n%s\n",
                    $collection->name,
                    $item->count,
                    $price,
                    $collectionTitles
                );

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
        $this->useCashBackForPayment($cashback);

        // ==========================================
        // 2. СОЗДАНИЕ ЗАКАЗА
        // ==========================================
        $order = Order::query()->create([
            'tenant_id' => $this->tenant->id,
            'tenant_user_id' => $this->tenantUser->id,
            'delivery_service_info' => null,
            'deliveryman_info' => null,
            'product_details' => [
                (object) [
                    "from" => $this->tenant->title ?? $this->tenant->bot_domain ?? $this->tenant->id,
                    "products" => $tmpOrderProductInfo,
                ],
            ],
            'product_count' => (int) $summaryCount,
            'summary_price' => $this->safeFloat($summaryPrice - $cashback),
            'delivery_price' => $this->safeFloat($deliveryPrice),
            'delivery_range' => $this->safeFloat($distance),
            'deliveryman_latitude' => $this->safeFloat($lat),
            'deliveryman_longitude' => $this->safeFloat($lng),
            'delivery_note' => $deliveryNote,
            'receiver_name' => $this->data["name"] ?? 'Нет имени',
            'receiver_phone' => $this->data["phone"] ?? 'Нет телефона',
            'location_id' => $locationId,
            'status' => OrderStatusEnum::NewOrder->value,
            'order_type' => OrderTypeEnum::InternalStore->value,
            'payed_at' => Carbon::now(),
        ]);

        // ==========================================
        // 3. СОЗДАНИЕ ДИАЛОГА ДЛЯ ЗАКАЗА
        // ==========================================
        $orderDialogService = app(\App\Services\OrderDialogService::class);
        $orderDialog = null;

        try {
            $orderDialog = $orderDialogService->getOrCreateDialog($order);

            $orderDialogService->addStatusMessage(
                $order,
                'new',
                "Сумма: " . number_format($summaryPrice - $cashback, 0, '.', ' ') . " ₽, товаров: {$summaryCount} шт."
            );

            if (in_array($paymentType, [1, 2, 3])) {
                $orderDialogService->addPaymentMessage($order, $summaryPrice - $cashback, $paymentType);
            }
        } catch (\Throwable $e) {
            Log::error('[Checkout] Ошибка создания диалога заказа: ' . $e->getMessage());
        }

        // ==========================================
        // 4. ОТПРАВКА В ИНТЕГРАЦИИ (FrontPad, IIKO)
        // ==========================================
        foreach ($partnerProductBox as $key => $box) {
            $box = (object) $box;
            $tenantInBox = Tenant::query()->find($box->id);

            if (is_null($tenantInBox)) {
                continue;
            }

            $this->fsPrepareFrontPad($order, $tmpOrderProductInfo, $tenantInBox->id);

            $iiko = $tenantInBox->iiko ?? null;
            if ($iiko && !is_null($iiko->api_login ?? null)) {
                Log::info('IIKO order created for order #' . $order->id);
            }
        }

        $needBill = false;
        ini_set('max_execution_time', 300);

        // ==========================================
        // 5. ФОРМИРОВАНИЕ СООБЩЕНИЙ
        // ==========================================
        $clientMessage = $this->buildClientMessage($order, $partnerProductBox, $summaryPrice, $cashback, $summaryCount, $summaryDiscount, $deliveryPrice, $distance, $needPickup);
        $partnerMessages = $this->buildPartnerMessages($order, $partnerProductBox, $cashback, $needPickup);
        $crmMessage = $this->buildCrmMessage($order, $partnerProductBox, $summaryPrice, $cashback, $summaryCount, $summaryDiscount, $deliveryPrice, $distance, $needPickup);

        // ==========================================
        // 6. ОТПРАВКА КЛИЕНТУ
        // ==========================================
        if ($orderDialog) {
            try {
                $orderDialogService->addOrderDetailsMessage(
                    $order,
                    $clientMessage,
                    [
                        'summary_price' => $summaryPrice - $cashback,
                        'summary_count' => $summaryCount,
                        'payment_type' => $paymentType,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('[Checkout] Ошибка отправки клиенту: ' . $e->getMessage());
            }
        }

        // ==========================================
        // 7. ОТПРАВКА ПАРТНЁРАМ
        // ==========================================
        foreach ($partnerMessages as $partnerUuid => $partnerData) {
            try {
                MessageService::call()->sendMessage([
                    "message" => $partnerData['message'],
                    "thread_id" => $partnerData['thread'],
                    "title" => "Заказ #{$order->id} — {$partnerData['name']}",
                    "recipients" => [
                        "partners" => true,
                    ],
                    "meta" => [
                        "order_id" => $order->id,
                        "partner_id" => $partnerData['id'] ?? null,
                        "partner_name" => $partnerData['name'] ?? null,
                        "type" => "partner_order",
                        "is_system" => true,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('[Checkout] Ошибка отправки партнёру: ' . $e->getMessage());
            }
        }

        // ==========================================
        // 8. 🆕 ОТПРАВКА В KANBAN CRM
        // ==========================================
        $kanbanTaskId = null;
        $kanbanMessageId = null;

        if ($kanbanEnabled) {
            try {
                $customerName = $this->data["name"] ?? 'Нет имени';
                $customerPhone = $this->data["phone"] ?? null;

                // === ПОИСК СУЩЕСТВУЮЩЕГО КЛИЕНТА ПО ТЕЛЕФОНУ ===
                $existingTaskId = $this->findKanbanClientByPhone($kanbanBoardUuid, $customerPhone);

                if ($existingTaskId) {
                    // === КЛИЕНТ НАЙДЕН — ОТПРАВЛЯЕМ СООБЩЕНИЕ В СУЩЕСТВУЮЩУЮ ЗАДАЧУ ===
                    $result = \Exxxar\Kanban\Facades\Kanban::query()
                        ->task($existingTaskId)
                        ->message($crmMessage)
                        ->senderType('system')
                        ->senderLabel('FoodShop Checkout')
                        ->payload([
                            'source' => 'foodshop',
                            'order_id' => $order->id,
                            'tenant_id' => $this->tenant->id,
                            'tenant_user_id' => $this->tenantUser->id,
                            'customer_name' => $customerName,
                            'customer_phone' => $customerPhone,
                            'summary_price' => $summaryPrice - $cashback,
                            'summary_count' => $summaryCount,
                            'payment_type' => $paymentType,
                            'type' => 'new_order',
                        ])
                        ->send();

                    $kanbanTaskId = $result['task_id'];
                    $kanbanMessageId = $result['message_id'];

                    Log::info('[KanbanCRM] Сообщение отправлено существующему клиенту', [
                        'task_id' => $kanbanTaskId,
                        'message_id' => $kanbanMessageId,
                        'order_id' => $order->id,
                    ]);
                } else {
                    // === КЛИЕНТ НЕ НАЙДЕН — СОЗДАЁМ НОВОГО КЛИЕНТА + ПЕРВОЕ СООБЩЕНИЕ ===
                    $result = \Exxxar\Kanban\Facades\Kanban::client()
                        ->board($kanbanBoardUuid)
                        ->thread($kanbanThread)
                        ->title($customerName)
                        ->priority('medium')
                        ->label('order')
                        ->label('foodshop')
                        ->clientData([
                            'company_name' => $customerName,
                            'contact_person' => $customerName,
                            'phone' => $customerPhone,
                            'source' => 'FoodShop',
                            'cost' => $summaryPrice - $cashback,
                            'placement_type' => $needPickup ? 'Самовывоз' : 'Доставка',
                            'custom_data' => [
                                'tenant_id' => $this->tenant->id,
                                'tenant_name' => $this->tenant->name ?? $this->tenant->title,
                                'tenant_user_id' => $this->tenantUser->id,
                                'last_order_id' => $order->id,
                                'last_order_date' => now()->toIso8601String(),
                                'total_orders' => 1,
                            ],
                        ])
                        ->message($crmMessage)
                        ->senderType('system')
                        ->senderLabel('FoodShop Checkout')
                        ->payload([
                            'source' => 'foodshop',
                            'order_id' => $order->id,
                            'tenant_id' => $this->tenant->id,
                            'tenant_user_id' => $this->tenantUser->id,
                            'customer_name' => $customerName,
                            'customer_phone' => $customerPhone,
                            'summary_price' => $summaryPrice - $cashback,
                            'summary_count' => $summaryCount,
                            'payment_type' => $paymentType,
                            'type' => 'new_client_and_order',
                        ])
                        ->send();

                    $kanbanTaskId = $result['task_id'];
                    $kanbanMessageId = $result['message_id'];

                    Log::info('[KanbanCRM] Создан новый клиент с заказом', [
                        'task_id' => $kanbanTaskId,
                        'message_id' => $kanbanMessageId,
                        'order_id' => $order->id,
                        'customer_name' => $customerName,
                    ]);
                }

                // Сохраняем связь заказа с Kanban задачей
                $order->update([
                    'meta' => array_merge($order->meta ?? [], [
                        'kanban_task_id' => $kanbanTaskId,
                        'kanban_message_id' => $kanbanMessageId,
                        'kanban_board_uuid' => $kanbanBoardUuid,
                    ]),
                ]);

            } catch (\Exxxar\Kanban\Exceptions\ValidationException $e) {
                Log::error('[KanbanCRM] Ошибка валидации: ' . $e->getMessage(), [
                    'errors' => $e->errors(),
                    'order_id' => $order->id,
                ]);
            } catch (\Exxxar\Kanban\Exceptions\KanbanException $e) {
                Log::error('[KanbanCRM] Ошибка API: ' . $e->getMessage(), [
                    'code' => $e->getCode(),
                    'order_id' => $order->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('[KanbanCRM] Неизвестная ошибка: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // ==========================================
        // 9. ОБРАБОТКА ОПЛАТЫ И ЧЕКА
        // ==========================================
        $order->delivery_price = $deliveryPrice;
        $order->save();

        switch ($paymentType) {
            case 0:
                // TODO: PaymentService::call()->checkout();
                break;
            case 1:
            case 2:
            case 3:
                $needBill = true;
                break;
            case 4:
                return PaymentService::call()
                    ->sbpForShop($order, $crmMessage);
        }

        // Генерация PDF-чека
        $invoicePath = null;
        if ($needBill) {
            $invoicePath = $this->fsPrintPDFInfo(
                order: $order,
                summaryPrice: $summaryPrice,
                summaryCount: $summaryCount,
                tmpOrderProductInfo: $tmpOrderProductInfo,
                cashback: $cashback
            );

            if ($invoicePath && $orderDialog) {
                try {
                    $orderDialogService->sendInvoiceToDialog(
                        $order,
                        $invoicePath,
                        [
                            'payment_type' => $paymentType,
                            'summary_price' => $summaryPrice - $cashback,
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::error('[Checkout] Ошибка отправки чека: ' . $e->getMessage());
                }
            }

            // 🆕 ОТПРАВКА ЧЕКА В KANBAN CRM
            if ($invoicePath && $kanbanEnabled && $kanbanTaskId) {
                try {
                    \Exxxar\Kanban\Facades\Kanban::query()
                        ->task($kanbanTaskId)
                        ->message("📄 Чек по заказу #{$order->id} прикреплён")
                        ->senderType('system')
                        ->senderLabel('FoodShop Checkout')
                        ->file($invoicePath)
                        ->payload([
                            'type' => 'invoice_attached',
                            'order_id' => $order->id,
                        ])
                        ->send();
                } catch (\Throwable $e) {
                    Log::error('[KanbanCRM] Ошибка отправки чека: ' . $e->getMessage());
                }
            }
        }

        $this->sendPaidReceiptToChannel($order, $crmMessage);

        // ==========================================
        // 10. ФИНАЛЬНОЕ СООБЩЕНИЕ КЛИЕНТУ
        // ==========================================
        if ($orderDialog) {
            try {
                $paymentInfo = sprintf(
                    $this->tenant->settings["payment_info"] ?? "Оплатите заказ по реквизитам, указав номер %s",
                    $order->id ?? '-'
                );

                $orderDialogService->addOrderMessage(
                    $order,
                    "📨 <strong>Инструкция по оплате</strong>\n\n{$paymentInfo}\n\n" .
                    "Вы можете оставить отзыв с фото и получить от нас дополнительный КэшБэк!",
                    [
                        'type' => 'payment_instruction',
                        'is_system' => true,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('[Checkout] Ошибка финального сообщения: ' . $e->getMessage());
            }
        }

        // ==========================================
        // 11. ОЧИСТКА ПРОМОКОДОВ
        // ==========================================
        $config = $this->tenantUser->meta ?? [];
        $config["current_promocodes"] = [];
        $this->tenantUser->meta = $config;
        $this->tenantUser->save();

        return $crmMessage;
    }

    /**
     * 🆕 Поиск клиента в KanbanCRM по телефону (серверный поиск)
     *
     * @return int|null ID задачи клиента или null если не найден
     */
    private function findKanbanClientByPhone(string $boardUuid, ?string $phone): ?int
    {
        if (empty($phone)) {
            return null;
        }

        try {
            // Используем серверный поиск вместо загрузки всех задач
            $taskId = \Exxxar\Kanban\Facades\Kanban::clients()
                ->getTaskIdByPhone($boardUuid, $phone);

            if ($taskId) {
                Log::info('[KanbanCRM] Клиент найден по телефону', [
                    'phone' => $phone,
                    'task_id' => $taskId,
                ]);
            } else {
                Log::info('[KanbanCRM] Клиент не найден по телефону', [
                    'phone' => $phone,
                ]);
            }

            return $taskId;
        } catch (\Exxxar\Kanban\Exceptions\KanbanException $e) {
            Log::warning('[KanbanCRM] Ошибка поиска клиента: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            Log::error('[KanbanCRM] Неизвестная ошибка поиска: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 🆕 Формирование сообщения для КЛИЕНТА
     */
    private function buildClientMessage($order, $partnerProductBox, $summaryPrice, $cashback, $summaryCount, $summaryDiscount, $deliveryPrice, $distance, $needPickup): string
    {
        $message = "<b>✅ Заказ #{$order->id} принят!</b>\n\n";
        $message .= "<b>Состав заказа:</b>\n";

        foreach ($partnerProductBox as $box) {
            $message .= $box["message"];
        }

        $message .= "\n<b>📊 Итого:</b>\n";
        $message .= "Товары: <b>" . number_format($summaryPrice, 0, '.', ' ') . " ₽</b>\n";

        if ($cashback > 0) {
            $message .= "Скидка (кэшбэк): <b>-" . number_format($cashback, 0, '.', ' ') . " ₽</b>\n";
        }

        if ($summaryDiscount > 0) {
            $message .= "Скидки: <b>-" . number_format($summaryDiscount, 0, '.', ' ') . " ₽</b>\n";
        }

        if ($deliveryPrice > 0) {
            $message .= "Доставка: <b>" . number_format($deliveryPrice, 0, '.', ' ') . " ₽</b>";
            if ($distance > 0) {
                $message .= " ({$distance} км)";
            }
            $message .= "\n";
        }

        $totalToPay = ($summaryPrice - $cashback) + $deliveryPrice;
        $message .= "\n💰 <b>К оплате: " . number_format($totalToPay, 0, '.', ' ') . " ₽</b>\n";
        $message .= "📦 Позиций: <b>{$summaryCount}</b>\n";

        $message .= "\n" . $this->fsPrepareUserInfo($order, $cashback);

        if ($this->fsPrepareDisabilities()) {
            $message .= "\n" . $this->fsPrepareDisabilities();
        }

        return $message;
    }

    /**
     * 🆕 Формирование сообщений для ПАРТНЁРОВ
     */
    private function buildPartnerMessages($order, $partnerProductBox, $cashback, $needPickup): array
    {
        $messages = [];

        foreach ($partnerProductBox as $uuid => $box) {
            $resultMessage = "🔔 <b>Новый заказ #{$order->id}</b>\n";
            $resultMessage .= (!$needPickup ? "#заказдоставка\n" : "#заказсамовывоз\n");
            $resultMessage .= $this->fsPrepareDisabilities();
            $resultMessage .= $box["message"] ?? 'Неуказанный продукт (ошибка)';

            $resultMessage .= $this->fsPrepareUserInfo($order, $cashback);

            if ($box["summary_discount"] > 0) {
                $resultMessage .= "\nСкидка по товарам: <b>-" . $box["summary_discount"] . " руб.</b>";
            }
            $resultMessage .= "\nИтого: <b>" . $box["summary_price"] . " руб.</b> за <b>" . $box["summary_count"] . " ед.</b>\n";

            if ($box["delivery_price"] > 0) {
                $resultMessage .= "\nДоставка: <b>" . $box["delivery_price"] . " руб.</b> за " . $box["distance"] . " км";
                $resultMessage .= "\nИтого c доставкой: <b>" . ($box["summary_price"] + $box["delivery_price"]) . " руб.</b>";
            }

            $messages[$uuid] = [
                'message' => $resultMessage,
                'thread' => $box["thread"],
                'id' => $box["id"] ?? null,
                'name' => $box["name"] ?? null,
            ];
        }

        return $messages;
    }

    /**
     * 🆕 Формирование сообщения для CRM
     */
    private function buildCrmMessage($order, $partnerProductBox, $summaryPrice, $cashback, $summaryCount, $summaryDiscount, $deliveryPrice, $distance, $needPickup): string
    {
        $message = "<b>⚠️⚠️⚠️Сводный заказ⚠️⚠️⚠️</b>\n";
        $message .= (!$needPickup ? "#заказдоставка\n" : "#заказсамовывоз\n");

        $recountDeliveryPrice = $deliveryPrice == 0;

        foreach ($partnerProductBox as $key => $box) {
            $message .= "\n<b>﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌</b>\n";
            $message .= "Заказ из <b>{$box['name']}</b>\n";
            $message .= $box["message"] ?? '';
            $message .= "\nСкидка по товарам: <b>-" . ($box["summary_discount"] ?? 0) . " руб.</b>";
            $message .= "\nИтого: <b>" . ($box["summary_price"] ?? 0) . " руб.</b> за <b>" . ($box["summary_count"] ?? 0) . " ед.</b>";

            if (($box["delivery_price"] ?? 0) > 0) {
                $message .= "\nДоставка: <b>" . $box["delivery_price"] . " руб.</b> за " . $box["distance"] . " км";
                $message .= "\nИтого c доставкой: <b>" . ($box["summary_price"] + $box["delivery_price"]) . " руб.</b>";

                if ($recountDeliveryPrice) {
                    $deliveryPrice += $box["delivery_price"];
                }
            }
        }

        $message .= "\n<b>﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌</b>\n";

        if ($cashback > 0) {
            $message .= "Использованы баллы: <b>-$cashback</b> руб.\n";
        }
        $message .= "Итоговая скидка: <b>-$summaryDiscount</b> руб.\n";
        $message .= "Итого по всем: <b>" . ($summaryPrice - $cashback) . " руб.</b> за <b>$summaryCount ед.</b>\n";

        if ($deliveryPrice > 0) {
            $message .= "Доставка: <b>" . $deliveryPrice . " руб.</b> за $distance км\n";
            $message .= "Итого c доставкой: <b>" . (($summaryPrice - $cashback) + $deliveryPrice) . " руб.</b>\n";
        }

        $message .= $this->fsPrepareUserInfo($order, $cashback);
        $message .= $this->fsPrepareDisabilities();

        return $message;
    }
}
