<?php

namespace App\Services\Helpers;

use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Order;
use App\Models\Tenant\Partner;
use App\Models\Tenant\Tenant;
use App\Services\MessageService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait BasketHelper
{

    /**
     * 🎯 ОТПРАВКА УВЕДОМЛЕНИЯ О НОВОМ ЗАКАЗЕ В TELEGRAM КАНАЛ
     */
    /**
     * 🎯 ОТПРАВКА УВЕДОМЛЕНИЯ О НОВОМ ЗАКАЗЕ В TELEGRAM КАНАЛ
     */
    private function sendTelegramNotification(Order $order, array $context, array $basketData): void
    {
        // 1. Получаем настройки Telegram из настроек тенанта
        $tgSettings = $this->tenant->settings['telegram'] ?? [];
        $token = $tgSettings['token'] ?? null;
        $channelId = $tgSettings['channel_id'] ?? null;

        // Если настройки не заполнены, тихо выходим, не ломая процесс оформления
        if (!$token || !$channelId) {
            return;
        }

        // 2. Получаем умный адрес (из БД или из формы)
        $addr = $this->getResolvedAddress();
        $orderType = $context['need_pickup'] ? '🏪 Самовывоз' : '🚚 Доставка';
        $addressText = $context['need_pickup'] ? 'Не требуется' : $addr['address'];

        $message = "🔔 <b>НОВЫЙ ЗАКАЗ #{$order->id}</b>\n";
        $message .= "📅 " . now()->format('d.m.Y H:i') . "\n\n";
        $message .= "👤 <b>Клиент:</b> {$order->receiver_name}\n";
        $message .= "📞 <b>Телефон:</b> <code>{$order->receiver_phone}</code>\n";
        $message .= "📦 <b>Тип:</b> {$orderType}\n";

        if (!$context['need_pickup']) {
            $message .= "📍 <b>Адрес:</b> {$addressText}\n";
            if (!empty($addr['entrance_number'])) $message .= "🚪 Подъезд: {$addr['entrance_number']}\n";
            if (!empty($addr['floor_number'])) $message .= "🏢 Этаж: {$addr['floor_number']}\n";
            if (!empty($addr['flat_number'])) $message .= "🏠 Кв/Офис: {$addr['flat_number']}\n";
        }

        // 🆕 3. Формируем состав заказа с группировкой по заведениям, суммой и доставкой для каждого
        $message .= "\n🛒 <b>Состав заказа:</b>\n";

        foreach ($basketData['partner_boxes'] as $box) {
            $message .= "\n🏪 <b>{$box['name']}:</b>\n";

            // Товары конкретного заведения
            foreach ($box['products'] as $product) {
                $priceFormatted = number_format($product['price'], 0, '.', ' ');
                $message .= "  • {$product['name']} x{$product['count']} = {$priceFormatted} ₽\n";
            }

            // 🆕 Сумма по этому заведению
            $boxSubtotal = number_format($box['summary_price'], 0, '.', ' ');
            $message .= "  └─ <b>Итого по заведению:</b> {$boxSubtotal} ₽\n";

            // 🆕 Доставка по этому заведению (только если это не самовывоз и доставка > 0)
            if (!$context['need_pickup'] && ($box['delivery_price'] ?? 0) > 0) {
                $boxDelivery = number_format($box['delivery_price'], 0, '.', ' ');
                $distText = ($box['distance'] > 0) ? " ({$box['distance']} км)" : "";
                $message .= "  └─ <b>Доставка:</b> {$boxDelivery} ₽{$distText}\n";
            }
        }

        // 4. Общий итог по всему заказу
        $totalToPay = $order->summary_price + $order->delivery_price;
        $message .= "\n━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💰 <b>ВСЕГО К ОПЛАТЕ:</b> " . number_format($totalToPay, 0, '.', ' ') . " ₽\n";

        if ($order->delivery_price > 0 && !$context['need_pickup']) {
            $message .= "🚚 <b>Общая доставка:</b> " . number_format($order->delivery_price, 0, '.', ' ') . " ₽";
            if ($context['distance'] > 0) {
                $message .= " ({$context['distance']} км)";
            }
            $message .= "\n";
        }

        if (!empty($this->data['info'])) {
            $message .= "\n📝 <b>Комментарий:</b> {$this->data['info']}\n";
        }

        // 5. Формируем payload для Telegram API
        $payload = [
            'chat_id' => $channelId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        // Если в настройках указан ID темы (треда) в канале/группе
        if (!empty($tgSettings['thread_id'])) {
            $payload['message_thread_id'] = (int) $tgSettings['thread_id'];
        }

        // 6. Отправляем запрос через cURL (или Http фасад, если вы его уже заменили)
        $this->sendTelegramCurlRequest($token, $payload);
    }

    /**
     * Вспомогательный метод для отправки запроса в Telegram API через cURL
     */
    private function sendTelegramCurlRequest(string $token, array $payload): bool
    {
        $ch = curl_init();
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Таймаут 10 секунд, чтобы не вешать оформление заказа

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            Log::warning('[Telegram Notification] Ошибка отправки. HTTP: ' . $httpCode . ' | Error: ' . $curlError . ' | Response: ' . $response);
            return false;
        }

        return true;
    }

    /**
     * 🆕 Получает данные адреса: сначала из БД по location_id, иначе из данных формы
     */
    private function getResolvedAddress(): array
    {
        $locationId = $this->safeInt($this->data["location_id"] ?? null);

        // Резервные данные из формы
        $fallback = [
            'address' => $this->data["address"] ?? '',
            'city' => $this->data["city"] ?? '',
            'lat' => $this->safeFloat($this->data["lat"] ?? 0),
            'lng' => $this->safeFloat($this->data["lng"] ?? 0),
            'entrance_number' => $this->data["entrance_number"] ?? null,
            'floor_number' => $this->data["floor_number"] ?? null,
            'flat_number' => $this->data["flat_number"] ?? null,
        ];

        if ($locationId) {
            $location = \App\Models\Tenant\TenantUserAddress::find($locationId);
            if ($location) {
                // Объединяем: данные из формы имеют приоритет (вдруг пользователь что-то изменил в момент заказа)
                return array_merge($fallback, [
                    'address' => $location->address ?: $fallback['address'],
                    'city' => $location->city ?: $fallback['city'],
                    'lat' => $location->lat ?: $fallback['lat'],
                    'lng' => $location->lng ?: $fallback['lng'],
                    'entrance_number' => $this->data["entrance_number"] ?? ($location->meta['entrance_number'] ?? null),
                    'floor_number' => $this->data["floor_number"] ?? ($location->meta['floor_number'] ?? null),
                    'flat_number' => $this->data["flat_number"] ?? ($location->meta['flat_number'] ?? null),
                ]);
            }
        }

        return $fallback;
    }

    protected function safeInt($value): ?int
    {
        if (is_null($value) || $value === '' || $value === false) return null;
        return is_numeric($value) ? (int) $value : null;
    }

    protected function safeFloat($value): ?float
    {
        if (is_null($value) || $value === '' || $value === false) return null;
        return is_numeric($value) ? (float) $value : null;
    }

    private function fsPrepareDisabilities(): string
    {
        $hasDisability = ($this->data["has_disability"] ?? "false") === "true";
        if (!$hasDisability) return '';

        $disabilities = json_decode($this->data["disabilities"] ?? '[]', true) ?: [];
        $allergy = $this->data["allergy"] ?? 'не указана';
        $text = "<b>Внимание!</b> у клиента присутствуют ограничения по здоровью!\n";

        foreach ($disabilities as $disability) {
            $text .= $disability === "пищевая аллергия"
                ? "-<em>$disability на: $allergy</em>\n"
                : "-<em>$disability</em>\n";
        }
        return $text . "\n";
    }

    private function fsPrepareUserInfo($order, $cashback = 0): string
    {
        $time = $this->data["time"] ?? null;
        $persons = $this->data["persons"] ?? 1;
        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0] ?? 'Не указан';
        $whenReady = ($this->data["when_ready"] ?? "false") === "true";
        $needPickup = ($this->data["need_pickup"] ?? "false") === "true";
        $useCashback = ($this->data["use_cashback"] ?? "false") === "true";

        $orderId = $order->id ?? '-';
        $userId = $this->tenantUser->id ?? '-';
        $name = $this->data["name"] ?? 'Не указано';
        $phone = $this->data["phone"] ?? 'Не указано';
        $money = $this->data["money"] ?? 'Не указано';
        $info = $this->data["info"] ?? 'Не указано';
        $cashbackText = $useCashback ? $cashback : "нет";
        $timeText = $whenReady ? "По готовности" : Carbon::parse($time)->format('Y-m-d H:i');
        $statusIcon = $whenReady ? "🟢" : "🟡";

        // 🆕 Получаем корректный адрес
        $addr = $this->getResolvedAddress();

        if (!$needPickup) {
            return sprintf(
                "\n%s Заказ №: <b>%s</b>\nИдентификатор клиента: <b>%s</b>\n\n" .
                "<b>Данные для доставки:</b>\n" .
                "Ф.И.О.: <b>%s</b>\nНомер телефона: <b>%s</b>\n" .
                "Адрес: <code>%s</code><code> (%s, %s)</code>\n" .
                "Цена доставки: %s руб.\nДистанция: %s км\n" .
                "Подъезд: %s\nЭтаж: %s\nТип оплаты: <b>%s</b>\n" .
                "Сдача с: %s руб.\nДоп.инфо: %s\n" .
                "Кэшбэк: %s\nДоставить ко времени: %s\nПерсон: <b>%s</b>\n",
                $statusIcon, $orderId, $userId, $name, $phone,
                $addr['address'], $addr['lat'], $addr['lng'],
                $order->delivery_price ?? 0, $order->delivery_range ?? 0,
                $addr['entrance_number'] ?? 'Не указано',
                $addr['floor_number'] ?? 'Не указано',
                $cash, $money, $info, $cashbackText, $timeText, $persons
            );
        }

        return sprintf(
            "\n%s Заказ №: <b>%s</b>\nИдентификатор: <b>%s</b>\n\n" .
            "<b>Данные для самовывоза:</b>\n" .
            "Ф.И.О.: <b>%s</b>\nНомер телефона: <b>%s</b>\n" .
            "Тип оплаты: <b>%s</b>\nСдача с: %s руб.\n" .
            "Доп.инфо: %s\nКэшбэк: %s\nЗаберу в: %s\nПерсон: <b>%s</b>\n",
            $statusIcon, $orderId, $userId, $name, $phone,
            $cash, $money, $info, $cashbackText, $timeText, $persons
        );
    }

    private function ensureCityPrefix(string $address): string
    {
        $patterns = ['/г\./ui', '/город\b/ui', '/с\./ui', '/село\b/ui', '/пос\./ui', '/пос[её]лок\b/ui', '/пгт\b/ui'];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $address)) return trim($address);
        }
        return 'г. ' . trim($address);
    }

    private function ensureStreetPrefix(string $street): string
    {
        $patterns = ['/ул\./ui', '/улица\b/ui', '/пр-т\b/ui', '/просп\./ui', '/проспект\b/ui', '/пер\./ui', '/переулок\b/ui', '/бул\./ui', '/бульвар\b/ui', '/проезд\b/ui', '/ш\./ui', '/шоссе\b/ui', '/наб\./ui', '/набережная\b/ui', '/пл\./ui', '/площадь\b/ui', '/тракт\b/ui', '/туп\./ui', '/тупик\b/ui'];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $street)) return trim($street);
        }
        return 'ул. ' . trim($street);
    }

    private function fsPrepareAddress(): string
    {
        $addr = $this->getResolvedAddress();

        // Если в базе уже сохранен полный адрес, используем его
        if (!empty($addr['address'])) {
            return $addr['address'];
        }

        // Иначе собираем из частей (для обратной совместимости)
        $city = $this->ensureCityPrefix($addr["city"] ?? "");
        $street = $this->ensureStreetPrefix($this->data["street"] ?? "");
        return "$city, $street, " . ($this->data["building"] ?? "");
    }

    private function fsPrintPDFInfo($order, $summaryPrice, $summaryCount, $tmpOrderProductInfo, $cashback = 0, $paymentStatusText = ''): ?string
    {
        $useCashback = ($this->data["use_cashback"] ?? "false") === "true";
        $cash = self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0] ?? 'Не указан';
        $address = $this->fsPrepareAddress();
        $disabilitiesText = $this->fsPrepareDisabilities() ?: 'не указаны';
        $distance = $this->data["distance"] ?? 0;
        $deliveryPrice = $this->data["delivery_price"] ?? 0;
        $currentDate = Carbon::now("+3:00")->format("Y-m-d H:i:s");
        $number = Str::uuid()->toString();
        $tenantTitle = $this->tenant->title ?? $this->tenant->bot_domain ?? 'CashMan';

        $paymentInfo = sprintf(
            $this->tenant->settings["payment_info"] ?? "Оплатите заказ по реквизитам, указав номер %s",
            $order->id ?? '-'
        );

        if (!class_exists('\Mpdf\Mpdf')) {
            Log::warning('[PDF] Mpdf не установлен, чек не сгенерирован');
            return null;
        }

        try {
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans']);
            $html = view("pdf.order", [
                "title" => $tenantTitle, "uniqNumber" => $number, "orderId" => $order->id,
                "name" => $order->receiver_name, "phone" => $order->receiver_phone,
                "address" => $address . "," . ($this->data["flat_number"] ?? ""),
                "message" => ($this->data["info"] ?? 'Не указано'),
                "entranceNumber" => ($this->data["entrance_number"] ?? 'Не указано'),
                "floorNumber" => ($this->data["floor_number"] ?? 'Не указано'),
                "cashType" => $cash, "money" => ($this->data["money"] ?? 'Не указано'),
                "disabilitiesText" => $disabilitiesText, "totalPrice" => $summaryPrice,
                "discount" => $useCashback ? $cashback : 0, "totalCount" => $summaryCount,
                "distance" => $distance, "deliveryPrice" => $deliveryPrice,
                "currentDate" => $currentDate, "code" => "Без промокода", "promoCount" => "0",
                "paymentInfo" => $paymentInfo, "products" => $tmpOrderProductInfo,
                "paymentStatusText" => $paymentStatusText, "info" => $this->data["info"] ?? 'Не указано',
            ])->render();

            $mpdf->WriteHTML($html);
            $fileName = "order-{$order->id}-{$number}.pdf";
            $fullPath = "orders/{$order->tenant_id}/{$order->id}/{$fileName}";

            \Illuminate\Support\Facades\Storage::disk('public')->put($fullPath, $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));

            if (Schema::hasColumn('orders', 'invoice_path')) {
                $order->invoice_path = $fullPath;
                $order->saveQuietly(); // 🎯 Используем saveQuietly, чтобы не триггерить Observer повторно
            }

            Log::info("[PDF] Чек сохранён: {$fullPath}");
            return $fullPath;
        } catch (\Throwable $e) {
            Log::error('[PDF] Ошибка генерации чека: ' . $e->getMessage());
            return null;
        }
    }

    private function fsPrepareFrontPad($order, $tmpOrderProductInfo, $partnerId = null): void
    {
        $tenant = $this->tenant;
        $bot = is_null($partnerId) ? $tenant : Tenant::query()->find($partnerId);
        if (is_null($bot) || is_null($bot->frontPad)) return;

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
                    'person' => $this->data["persons"] ?? 1,
                    'datetime' => (($this->data["when_ready"] ?? "false") === "true") ? null : Carbon::parse($this->data["time"] ?? null)->format('Y-m-d H:i:s'),
                    'cash' => self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0] ?? 'Не указан',
                ]);
        }
    }

    private function fsPrepareDeliveryNote(): string
    {
        $note = ($this->data["info"] ?? 'Не указано') . "\n";
        if (!is_null($this->data["entrance_number"] ?? null)) $note .= "Подъезд: " . $this->data["entrance_number"] . "\n";
        if (!is_null($this->data["floor_number"] ?? null)) $note .= "Этаж: " . $this->data["floor_number"] . "\n";

        $note .= "Оплата: " . (self::PAYMENT_TYPES[$this->data["payment_type"] ?? 0] ?? 'Не указан') . "\n";
        if (!is_null($this->data["money"] ?? null)) $note .= "Сдача с: " . $this->data["money"] . "\n";

        $whenReady = ($this->data["when_ready"] ?? "false") === "true";
        $note .= "Время: " . ($whenReady ? "По готовности" : Carbon::parse($this->data["time"] ?? null)->format('Y-m-d H:i')) . "\n";
        $note .= "Персон: " . ($this->data["persons"] ?? 1) . "\n";
        $note .= "Ограничения:\n" . ($this->fsPrepareDisabilities() ?: 'не указаны');

        return $note;
    }

    protected function storeClientInfoAsContact(): void
    {
        $phone = $this->data["phone"] ?? $this->tenantUser->phone ?? null;
        $this->tenantUser->name = $this->data["name"] ?? $this->tenantUser->name;
        $this->tenantUser->phone = $phone ? str_replace(["(", ")", "-"], "", $phone) : $this->tenantUser->phone;
        $this->tenantUser->saveQuietly();
    }

    private function useCashBackForPayment($discount): void
    {
        if (($this->data["use_cashback"] ?? "false") !== "true") return;
        // TODO: Раскомментировать при готовности CashBackService
    }

    private function prepareCashbackDiscount($summaryPrice): float
    {
        if (($this->data["use_cashback"] ?? "false") !== "true") return 0.0;

        $maxPercent = $this->tenant->settings["max_cashback_use_percent"] ?? 0;
        $maxUserCashback = $this->tenantUser->cashback?->amount ?? 0;
        return min(($summaryPrice * ($maxPercent / 100)), $maxUserCashback);
    }



    private function sendPaidReceiptToChannel($order, $message): void
    {
        $uploadedPhoto = $this->uploadedImage ?? null;
        if (!$uploadedPhoto) return;

        $ext = $uploadedPhoto->getClientOriginalExtension();
        $imageName = Str::uuid()->toString() . "." . $ext;
        $uploadedPhoto->storeAs($imageName);
        $photoPath = storage_path() . "/app/$imageName";
        $whenReady = ($this->data["when_ready"] ?? "false") === "true";

        $tmpMessage = '<p><strong>#оплатачеком</strong></p>' .
            '<p>' . ($whenReady ? '🟢' : '🟡') . ' <strong>Заказ №:</strong> ' . ($order->id ?? '-') . '</p>' .
            '<p><strong>Клиент:</strong> ' . ($order->receiver_name ?? '-') . ' | ' . ($order->receiver_phone ?? '-') . '</p>' .
            '<p><strong>Пояснение:</strong> ' . ($this->data["image_info"] ?? 'не указано') . '</p>' .
            '<p><strong>Фото:</strong> ' . $photoPath . '</p>';

        $thread = $this->tenant->topics["orders"] ?? null;
        if ($tmpMessage && $thread) {
            MessageService::call()->sendMessage([
                "message" => $tmpMessage,
                "thread_id" => $thread,
                "recipients" => ["partners" => true]
            ]);
        }
    }

    private function updateUserProfileFromCheckout(array $checkoutData): void
    {
        $user = $this->tenantUser;
        $isUpdated = false;
        foreach (['name', 'phone', 'email'] as $field) {
            if (!empty($checkoutData[$field]) && ($field === 'phone' || empty($user->$field))) {
                $user->$field = $checkoutData[$field];
                $isUpdated = true;
                if ($field === 'phone') $user->phone = str_replace(["(", ")", "-"], "", $user->phone);
            }
        }

        if ($isUpdated) {
            $meta = $user->meta ?? [];
            $meta['profile_auto_filled_at'] = now()->toIso8601String();
            $user->meta = $meta;
            $user->saveQuietly();
        }
    }

    private function grantFirstOrderVipReward(): void
    {
        $user = $this->tenantUser;
        if (!$user->is_vip) {
            $user->grantVip(30);
            Log::info("[Checkout] Пользователю #{$user->id} выдан VIP на 30 дней.");

            try {
                // 🎯 Берем последний заказ, у которого уже есть dialog_id благодаря Observer
                $lastOrder = Order::query()->where('tenant_user_id', $user->id)->latest('id')->first();
                if ($lastOrder && $lastOrder->dialog_id) {
                    MessageService::call()->sendMessage([
                        'message' => "🎉 Поздравляем! За этот заказ вам начислен <b>VIP-статус</b> на 30 дней!",
                        'dialog_id' => $lastOrder->dialog_id,
                        'meta' => ['is_system' => true, 'type' => 'vip_granted'],
                        'recipients' => ['client' => true],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("[Checkout] Не удалось уведомить о VIP: " . $e->getMessage());
            }
        }
    }

    // ==========================================
    // 🎯 ОСНОВНОЙ МЕТОД (ОРКЕСТРАТОР)
    // ==========================================
    private function foodShopCheckout(): array
    {
        try {
            $context = $this->prepareCheckoutContext();
            $basketData = $this->processBasketAndCalculateTotals($context);

            // 1. Создаем заказ.
            // 🎯 OrderObserver автоматически перехватит событие 'created' и создаст TenantDialog!
            $order = $this->createOrderRecord($context, $basketData);

            // 2. Принудительно подгружаем связь dialog, чтобы она была доступна в памяти сразу
            $order->load('dialog');

            // 3. Уведомления (теперь метод сам возьмет $order->dialog)
            $kanbanTaskId = $this->notifyStakeholders($order, $context, $basketData);

            // 4. Оплата и чеки (тоже сам возьмет $order->dialog)
            $paymentData = $this->processPaymentAndReceipt($order, $context, $basketData, $kanbanTaskId);

            $this->finalizeOrder($order);

            return [
                'success' => true,
                'order_id' => $order->id,
                'dialog_id' => $order->dialog_id, // 🆕 Обязательно добавляем это поле!
                'summary_price' => $basketData['final_price'],
                'payment_type' => $context['payment_type'],
                'payment_status_text' => $this->getPaymentStatusText($context['payment_type']),
                'status' => $order->status,
                'payment_data' => $paymentData,
                'delivery_price' => $context['delivery_price'],
                'message' => 'Заказ успешно оформлен',
            ];
        } catch (\Throwable $e) {
            Log::error('[Checkout] Критическая ошибка: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => 'Ошибка при оформлении заказа. Попробуйте позже.'];
        }
    }

    // ==========================================
    // 🎯 ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ==========================================

    private function prepareCheckoutContext(): array
    {
        $data = $this->data;
        $kanbanConfig = $this->tenant->settings['kanban'] ?? [];
        $kanbanEnabled = !empty($kanbanConfig['enabled']) && !empty($kanbanConfig['board_uuid']) && !empty($kanbanConfig['token']);

        if ($kanbanEnabled) {
            try {
                \Exxxar\Kanban\Facades\Kanban::setBaseUrl($kanbanConfig['base_url'] ?? config('kanban.base_url'))
                    ->setToken($kanbanConfig['token'])->setTimeout(30)->setConnectTimeout(10)
                    ->setRetryTimes(3)->setRetrySleep(100)->setLoggingEnabled(true);
            } catch (\Throwable $e) {
                Log::error('[KanbanCRM] Ошибка настройки SDK: ' . $e->getMessage());
                $kanbanEnabled = false;
            }
        }

        $this->updateUserProfileFromCheckout($data);

        return [
            'kanban_enabled' => $kanbanEnabled,
            'kanban_board_uuid' => $kanbanConfig['board_uuid'] ?? null,
            'kanban_thread' => $kanbanConfig['order_thread'] ?? 0,
            'need_pickup' => ($data["need_pickup"] ?? "false") === "true",
            'delivery_price' => $this->safeFloat($data["delivery_price"] ?? 0),
            'distance' => $this->safeFloat($data["distance"] ?? 0),
            'lat' => $this->safeFloat($data["lat"] ?? 0),
            'lng' => $this->safeFloat($data["lng"] ?? 0),
            'location_id' => $this->safeInt($data["location_id"] ?? null),
            'payment_type' => $this->safeInt($data["payment_type"] ?? 4),
            'delivery_details' => json_decode($data["delivery_details"] ?? '[]', true) ?: [],
            'use_cashback' => ($data["use_cashback"] ?? "false") === "true",
            'customer_name' => $data["name"] ?? 'Нет имени',
            'customer_phone' => $data["phone"] ?? null,
        ];
    }

    private function processBasketAndCalculateTotals(array $context): array
    {
        $basket = Basket::query()->where("tenant_id", $this->tenant->id)
            ->where("tenant_user_id", $this->tenantUser->id)->whereNull("ordered_at")->get();

        $isPartnersActive = $this->tenant->settings["partners"]["is_active"] ?? false;
        $isPartnersDisplaySelf = $this->tenant->settings["partners"]["display_self"] ?? false;

        $summaryPrice = 0; $summaryCount = 0; $summaryDiscount = 0;
        $tmpOrderProductInfo = []; $partnerProductBox = []; $processedProductIds = [];

        foreach ($basket as $item) {
            $productTenantId = $item->product?->tenant_id ?? $this->tenant->id;
            if ($isPartnersActive && !$isPartnersDisplaySelf && $productTenantId == $this->tenant->id) continue;

            $partner = Tenant::query()->find($productTenantId) ?? $this->tenant;
            $uuid = $partner->uuid;

            if (empty($partnerProductBox[$uuid])) {
                $partnerProductBox[$uuid] = [
                    "id" => $partner->id, "name" => $partner->name ?? $partner->slug ?? 'Без названия',
                    "title" => $partner->title ?? $partner->name ?? 'Без названия', "message" => "",
                    "extra_charge" => (Partner::query()->where("tenant_id", $item->tenant_id)->where("tenant_partner_id", $item->tenant_partner_id)->first())?->extra_charge ?? 0,
                    "summary_price" => 0, "summary_count" => 0, "summary_discount" => 0,
                    "delivery_price" => $context['delivery_details'][$uuid]["price"] ?? 0,
                    "distance" => $context['delivery_details'][$uuid]["distance"] ?? 0,
                    "thread" => $partner->topics["delivery"] ?? $this->tenant->topics["delivery"] ?? null,
                    "products" => [],
                ];
            }

            $price = 0; $extraCharge = $partnerProductBox[$uuid]["extra_charge"]; $isWeightProduct = false;

            if ($item->product) {
                $isWeightProduct = $item->product->is_weight_product ?? false;
                $currentPrice = $item->params["discount_price"] ?? $item->product->price ?? 0;
                $unitOfMeasure = "ед.";

                if ($isWeightProduct) {
                    $weightConfig = is_string($item->product->weight_config) ? json_decode($item->product->weight_config, true) : ($item->product->weight_config ?? []);
                    $step = $weightConfig['step'] ?? 100;
                    $price = (($currentPrice * (1 + $extraCharge / 100)) * $item->count) / $step;
                    $unitOfMeasure = "гр.";
                } else {
                    $price = ($currentPrice * (1 + $extraCharge / 100)) * $item->count;
                }

                $comment = $item->comment ? "\n<em>({$item->comment})</em>" : "";
                $partnerProductBox[$uuid]["message"] .= sprintf("💎%s x%s %s=%s руб.%s\n", $item->product->name, $item->count, $unitOfMeasure, $price, $comment);

                $tmpOrderProductInfo[] = [
                    "id"=>$item->product->id,
                    "name" => $item->product->name, "count" => $item->count, "price" => $this->safeFloat($price),
                    'external_source' => $item->product->external_source ?? null, 'external_id' => $item->product->external_id ?? null,
                ];

                if (!in_array($item->product->id, $processedProductIds)) {
                    $processedProductIds[] = $item->product->id;
                    $partnerProductBox[$uuid]["products"][] = end($tmpOrderProductInfo);
                }
            }

            if ($item->collection) {
                $params = is_array($item->params) ? (object)$item->params : $item->params;
                $collectionTitles = ""; $collectionPrice = 0;

                foreach (($item->collection->products ?? []) as $product) {
                    if (!in_array($product->id, $params->ids ?? [])) continue;
                    $collectionTitles .= "-" . $product->name . "\n";
                    $collectionPrice += ($product->price ?? 0) * (1 + $extraCharge / 100);

                    $tmpOrderProductInfo[] = [
                        "id"=>$product->id,
                        "name" => "Коллекция `{$item->collection->name}`: {$product->name}", "count" => 1,
                        "price" => $this->safeFloat($product->price ?? 0),
                        'external_source' => $product->external_source ?? null, 'external_id' => $product->external_id ?? null,
                    ];

                    if (!in_array($product->id, $processedProductIds)) {
                        $processedProductIds[] = $product->id;
                        $partnerProductBox[$uuid]["products"][] = end($tmpOrderProductInfo);
                    }
                }
                $price += $collectionPrice * $item->count;
                $partnerProductBox[$uuid]["message"] .= sprintf("💎Коллекция `%s` x%s=%s руб.:\n%s\n", $item->collection->name, $item->count, $price, $collectionTitles);
            }

            $countToAdd = $isWeightProduct ? 1 : $item->count;
            $discountToAdd = $item->params["discount_amount"] ?? 0;

            $partnerProductBox[$uuid]["summary_count"] += $countToAdd;
            $partnerProductBox[$uuid]["summary_price"] += $price;
            $partnerProductBox[$uuid]["summary_discount"] += $discountToAdd;

            $summaryCount += $countToAdd; $summaryPrice += $price; $summaryDiscount += $discountToAdd;

            $item->ordered_at = env("APP_DEBUG") ? null : Carbon::now();
            $item->saveQuietly();
        }

        $cashback = $this->prepareCashbackDiscount($summaryPrice);
        $this->useCashBackForPayment($cashback);

        return [
            'summary_price' => $summaryPrice, 'summary_count' => $summaryCount, 'summary_discount' => $summaryDiscount,
            'final_price' => $this->safeFloat($summaryPrice - $cashback), 'cashback' => $cashback,
            'product_info' => $tmpOrderProductInfo, 'partner_boxes' => $partnerProductBox,
        ];
    }

    private function createOrderRecord(array $context, array $basketData): Order
    {
        return Order::query()->create([
            'tenant_id' => $this->tenant->id,
            'tenant_user_id' => $this->tenantUser->id,
            'delivery_service_info' => null,
            'deliveryman_info' => null,
            'product_details' => [
                "from" => $this->tenant->name ?? 'Магазин',
                "products" => $basketData['product_info']
            ],
            'product_count' => (int) $basketData['summary_count'],
            'summary_price' => $basketData['final_price'],
            'delivery_price' => $context['delivery_price'],
            'delivery_range' => $context['distance'],
            'deliveryman_latitude' => $context['lat'],
            'deliveryman_longitude' => $context['lng'],
            'delivery_note' => $this->fsPrepareDeliveryNote(),
            'receiver_name' => $context['customer_name'],
            'receiver_phone' => $context['customer_phone'],
            'location_id' => $context['location_id'],
            'status' => OrderStatusEnum::NewOrder->value,
            'order_type' => OrderTypeEnum::InternalStore->value,
            'payed_at' => in_array($context['payment_type'], [0, 4]) ? Carbon::now() : null,
        ]);
    }

    // 🎯 МЕТОД initializeOrderDialog УДАЛЕН. Эту работу теперь выполняет OrderObserver.

    // 🎯 ЕДИНЫЙ КОНТУР УВЕДОМЛЕНИЙ ЧЕРЕЗ MESSAGE SERVICE
    private function notifyStakeholders(Order $order, array $context, array $basketData): ?string
    {
        $kanbanTaskId = null;
        $paymentStatusText = $this->getPaymentStatusText($context['payment_type']);

        // 🎯 Получаем диалог, созданный Observer-ом
        $dialog = $order->dialog;

        // 1. Интеграции (FrontPad, IIKO)
        foreach ($basketData['partner_boxes'] as $box) {
            $tenantInBox = Tenant::query()->find($box['id']);
            if ($tenantInBox) {
                $this->fsPrepareFrontPad($order, $basketData['product_info'], $tenantInBox->id);
                if (!empty($tenantInBox->iiko?->api_login)) Log::info('IIKO order created for order #' . $order->id);
            }
        }

        // 2. Подготовка сообщений
        $clientMessage = $this->buildClientMessage($order, $basketData['partner_boxes'], $basketData['summary_price'], $basketData['cashback'], $basketData['summary_count'], $basketData['summary_discount'], $context['delivery_price'], $context['distance'], $context['need_pickup']);
        $crmMessage = $this->buildCrmMessage($order, $basketData['partner_boxes'], $basketData['summary_price'], $basketData['cashback'], $basketData['summary_count'], $basketData['summary_discount'], $context['delivery_price'], $context['distance'], $context['need_pickup']);
        $partnerMessages = $this->buildPartnerMessages($order, $basketData['partner_boxes'], $basketData['cashback'], $context['need_pickup']);

        // 3. Отправка КЛИЕНТУ через MessageService
        if ($dialog) {
            MessageService::call()->sendMessage([
                'message' => $clientMessage,
                'dialog_id' => $dialog->id,
                'meta' => [
                    'order_id' => $order->id,
                    'payment_status' => $paymentStatusText,
                    'is_system' => false,
                ],
                'recipients' => ['client' => true],
            ]);
        }

        // 4. Отправка в CRM (Kanban) через MessageService
        if ($context['kanban_enabled']) {
            $kanbanCustomData = [
                'tenant_id' => $this->tenant->id, 'tenant_name' => $this->tenant->name ?? $this->tenant->title,
                'tenant_user_id' => $this->tenantUser->id, 'last_order_id' => $order->id,
                'last_order_date' => now()->toIso8601String(),
                'product_details' => [['from' => $this->tenant->title ?? $this->tenant->bot_domain ?? 'Магазин', 'products' => $basketData['product_info']]],
                'product_count' => $basketData['summary_count'], 'delivery_price' => $context['delivery_price'],
                'delivery_note' => $this->fsPrepareDeliveryNote(), 'payment_type' => $context['payment_type'],
                'payment_status' => $paymentStatusText, 'summary_price' => $basketData['final_price'],
            ];

            $crmResult = MessageService::call()->sendMessage([
                'message' => $crmMessage,
                'title' => "Заказ #{$order->id} — {$context['customer_name']}",
                'meta' => [
                    'order_id' => $order->id, 'customer_name' => $context['customer_name'],
                    'customer_phone' => $context['customer_phone'], 'summary_price' => $basketData['final_price'],
                    'need_pickup' => $context['need_pickup'], 'delivery_note' => $this->fsPrepareDeliveryNote(),
                    'kanban_board_uuid' => $context['kanban_board_uuid'], 'kanban_thread' => $context['kanban_thread'],
                    'kanban_custom_data' => $kanbanCustomData,
                    'kanban_payload' => array_merge($kanbanCustomData, ['source' => 'foodshop', 'type' => 'new_order']),
                ],
                'recipients' => ['crm' => true],
            ]);

            if (!empty($crmResult['crm']['task_id'])) {
                $kanbanTaskId = $crmResult['crm']['task_id'];
                $order->updateQuietly([ // 🎯 updateQuietly, чтобы не триггерить Observer на обновлении, если это не нужно
                    'meta' => array_merge($order->meta ?? [], [
                        'kanban_task_id' => $kanbanTaskId,
                        'kanban_message_id' => $crmResult['crm']['message_id'],
                        'kanban_board_uuid' => $context['kanban_board_uuid'],
                    ]),
                ]);
            }
        }

        // 5. Отправка ПАРТНЁРАМ через MessageService (цикл, т.к. у каждого свой thread_id)
        foreach ($partnerMessages as $partnerData) {
            MessageService::call()->sendMessage([
                'message' => $partnerData['message'],
                'thread_id' => $partnerData['thread'],
                'title' => "Заказ #{$order->id} — {$partnerData['name']}",
                'meta' => [
                    'order_id' => $order->id, 'partner_id' => $partnerData['id'] ?? null,
                    'partner_name' => $partnerData['name'] ?? null, 'type' => 'partner_order', 'is_system' => true,
                ],
                'recipients' => ['partners' => true],
            ]);
        }

        // 🎯 НОВЫЙ ВЫЗОВ: Отправка уведомления в Telegram канал
        $this->sendTelegramNotification($order, $context, $basketData);

        return $kanbanTaskId;
    }

    // 🎯 ЕДИНЫЙ КОНТУР ОПЛАТЫ И ЧЕКОВ
    private function processPaymentAndReceipt(Order $order, array $context, array $basketData, ?string $kanbanTaskId): ?array
    {
        $paymentType = $context['payment_type'];
        $paymentStatusText = $this->getPaymentStatusText($paymentType);
        $paymentData = null;

        // 🎯 Получаем диалог, созданный Observer-ом
        $dialog = $order->dialog;

        if (in_array($paymentType, [1, 2, 3])) {
            // Логика для оплаты курьеру/при получении
        } elseif ($paymentType === 4) {
            $paymentData = PaymentService::call()->sbpForShop($order, '');
        }

        // 🎯 ГЕНЕРАЦИЯ ЧЕКА ВСЕГДА
        $invoicePath = $this->fsPrintPDFInfo(
            order: $order, summaryPrice: $basketData['summary_price'], summaryCount: $basketData['summary_count'],
            tmpOrderProductInfo: $basketData['product_info'], cashback: $basketData['cashback'],
            paymentStatusText: $paymentStatusText
        );

        // 🎯 ОТПРАВКА ЧЕКА ЧЕРЕЗ MESSAGE SERVICE
        if ($invoicePath) {
            $receiptMeta = [
                'order_id' => $order->id, 'payment_type' => $paymentType,
                'payment_status_text' => $paymentStatusText, 'summary_price' => $basketData['final_price'], 'is_system' => true,
            ];

            // Клиенту
            if ($dialog) {
                MessageService::call()->sendMessage([
                    'message' => "📄 Чек по заказу #{$order->id} (Статус: {$paymentStatusText})",
                    'file_path' => $invoicePath, 'dialog_id' => $dialog->id,
                    'meta' => $receiptMeta, 'recipients' => ['client' => true],
                ]);
            }

            // В CRM (если задача уже создана)
            if ($kanbanTaskId && $context['kanban_enabled']) {
                MessageService::call()->sendMessage([
                    'message' => "📄 Чек по заказу #{$order->id} прикреплён",
                    'file_path' => $invoicePath,
                    'meta' => array_merge($receiptMeta, [
                        'kanban_board_uuid' => $context['kanban_board_uuid'],
                        'kanban_payload' => ['type' => 'invoice_attached', 'order_id' => $order->id],
                    ]),
                    'recipients' => ['crm' => true],
                ]);
            }
        }

        // Отправка в канал (уже использует MessageService внутри)
        $this->sendPaidReceiptToChannel($order, '');

        return $paymentData;
    }

    private function finalizeOrder(Order $order): void
    {
        $config = $this->tenantUser->meta ?? [];
        $config["current_promocodes"] = [];
        $this->tenantUser->meta = $config;
        $this->tenantUser->saveQuietly();
        $this->grantFirstOrderVipReward();
    }

    private function findKanbanClientByPhone(string $boardUuid, ?string $phone): ?int
    {
        if (empty($phone)) return null;
        try {
            $taskId = \Exxxar\Kanban\Facades\Kanban::clients()->getTaskIdByPhone($boardUuid, $phone);
            if ($taskId) Log::info('[KanbanCRM] Клиент найден', ['phone' => $phone, 'task_id' => $taskId]);
            return $taskId;
        } catch (\Throwable $e) {
            Log::warning('[KanbanCRM] Ошибка поиска: ' . $e->getMessage());
            return null;
        }
    }

    private function getPaymentStatusText(int $paymentType): string
    {
        return match($paymentType) {
            0 => 'Оплачено онлайн (Карта)',
            1, 2, 3 => 'Оплата курьеру / При получении',
            4 => 'Ожидает оплаты по счету СБП',
            default => 'Статус оплаты уточняется',
        };
    }

    private function buildClientMessage($order, $partnerProductBox, $summaryPrice, $cashback, $summaryCount, $summaryDiscount, $deliveryPrice, $distance, $needPickup): string
    {
        $message = "<b>✅ Заказ #{$order->id} принят!</b>\n\n<b>Состав заказа:</b>\n";
        foreach ($partnerProductBox as $box) $message .= $box["message"];

        $message .= "\n<b>📊 Итого:</b>\nТовары: <b>" . number_format($summaryPrice, 0, '.', ' ') . " ₽</b>\n";
        if ($cashback > 0) $message .= "Скидка (кэшбэк): <b>-" . number_format($cashback, 0, '.', ' ') . " ₽</b>\n";
        if ($summaryDiscount > 0) $message .= "Скидки: <b>-" . number_format($summaryDiscount, 0, '.', ' ') . " ₽</b>\n";
        if ($deliveryPrice > 0) $message .= "Доставка: <b>" . number_format($deliveryPrice, 0, '.', ' ') . " ₽</b>" . ($distance > 0 ? " ({$distance} км)" : "") . "\n";

        $totalToPay = ($summaryPrice - $cashback) + $deliveryPrice;
        $message .= "\n💰 <b>К оплате: " . number_format($totalToPay, 0, '.', ' ') . " ₽</b>\n📦 Позиций: <b>{$summaryCount}</b>\n";
        $message .= "\n" . $this->fsPrepareUserInfo($order, $cashback);
        if ($this->fsPrepareDisabilities()) $message .= "\n" . $this->fsPrepareDisabilities();

        return $message;
    }

    private function buildPartnerMessages($order, $partnerProductBox, $cashback, $needPickup): array
    {
        $messages = [];
        foreach ($partnerProductBox as $uuid => $box) {
            $resultMessage = "🔔 <b>Новый заказ #{$order->id}</b>\n" . (!$needPickup ? "#заказдоставка\n" : "#заказсамовывоз\n");
            $resultMessage .= $this->fsPrepareDisabilities() . ($box["message"] ?? 'Неуказанный продукт') . $this->fsPrepareUserInfo($order, $cashback);

            if ($box["summary_discount"] > 0) $resultMessage .= "\nСкидка: <b>-" . $box["summary_discount"] . " руб.</b>";
            $resultMessage .= "\nИтого: <b>" . $box["summary_price"] . " руб.</b> за <b>" . $box["summary_count"] . " ед.</b>\n";

            if ($box["delivery_price"] > 0) {
                $resultMessage .= "\nДоставка: <b>" . $box["delivery_price"] . " руб.</b> за " . $box["distance"] . " км";
                $resultMessage .= "\nИтого c доставкой: <b>" . ($box["summary_price"] + $box["delivery_price"]) . " руб.</b>";
            }

            $messages[$uuid] = ['message' => $resultMessage, 'thread' => $box["thread"], 'id' => $box["id"] ?? null, 'name' => $box["name"] ?? null];
        }
        return $messages;
    }

    private function buildCrmMessage($order, $partnerProductBox, $summaryPrice, $cashback, $summaryCount, $summaryDiscount, $deliveryPrice, $distance, $needPickup): string
    {
        $message = "<b>⚠️⚠️⚠️Сводный заказ⚠️⚠️⚠️</b>\n" . (!$needPickup ? "#заказдоставка\n" : "#заказсамовывоз\n");
        $recountDeliveryPrice = $deliveryPrice == 0;

        foreach ($partnerProductBox as $box) {
            $message .= "\n<b>﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌</b>\nЗаказ из <b>{$box['name']}</b>\n" . ($box["message"] ?? '');
            $message .= "\nСкидка: <b>-" . ($box["summary_discount"] ?? 0) . " руб.</b>";
            $message .= "\nИтого: <b>" . ($box["summary_price"] ?? 0) . " руб.</b> за <b>" . ($box["summary_count"] ?? 0) . " ед.</b>";
            if (($box["delivery_price"] ?? 0) > 0) {
                $message .= "\nДоставка: <b>" . $box["delivery_price"] . " руб.</b> за " . $box["distance"] . " км";
                $message .= "\nИтого c доставкой: <b>" . ($box["summary_price"] + $box["delivery_price"]) . " руб.</b>";
                if ($recountDeliveryPrice) $deliveryPrice += $box["delivery_price"];
            }
        }

        $message .= "\n<b>﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌﹌</b>\n";
        if ($cashback > 0) $message .= "Использованы баллы: <b>-$cashback</b> руб.\n";
        $message .= "Итоговая скидка: <b>-$summaryDiscount</b> руб.\n";
        $message .= "Итого по всем: <b>" . ($summaryPrice - $cashback) . " руб.</b> за <b>$summaryCount ед.</b>\n";
        if ($deliveryPrice > 0) {
            $message .= "Доставка: <b>" . $deliveryPrice . " руб.</b> за $distance км\n";
            $message .= "Итого c доставкой: <b>" . (($summaryPrice - $cashback) + $deliveryPrice) . " руб.</b>\n";
        }

        $message .= $this->fsPrepareUserInfo($order, $cashback) . $this->fsPrepareDisabilities();
        return $message;
    }
}
