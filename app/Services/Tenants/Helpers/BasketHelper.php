<?php

namespace App\Services\Tenants\Helpers;

use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Order;
use App\Models\Tenant\Partner;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Services\Tenants\MessageService;
use App\Services\Tenants\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait BasketHelper
{
    /**
     * 🎯 Универсальная очистка номера телефона
     * Удаляет: скобки, пробелы, тире, точки, подчёркивания
     * Оставляет: только цифры и символ "+" (для международного формата)
     *
     * Примеры:
     *   "+7 (999) 123-45-67" → "+79991234567"
     *   "8 (999) 123 45 67"  → "89991234567"
     *   "8-999-123-45-67"    → "89991234567"
     *   "+7_999_123_45_67"   → "+79991234567"
     */
    protected function cleanPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Удаляем всё, кроме цифр и знака "+"
        return preg_replace('/[^\d+]/', '', $phone);
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
        return is_numeric($value) ? (int)$value : null;
    }

    protected function safeFloat($value): ?float
    {
        if (is_null($value) || $value === '' || $value === false) return null;
        return is_numeric($value) ? (float)$value : null;
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
        $phone = $this->cleanPhone($this->data["phone"] ?? 'Не указано');
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
        $tenantTitle = $this->tenant->name ?? $this->tenant->uuid ?? 'CashMan';

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
                "title" => $tenantTitle,
                "uniqNumber" => $number,
                "orderId" => $order->id,
                "name" => $order->receiver_name ?? '-',
                "phone" => $order->receiver_phone ?? '-',
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

        // 🎯 Используем универсальный метод
        $this->tenantUser->phone = $phone ? $this->cleanPhone($phone) : $this->tenantUser->phone;
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
                // 🎯 Для телефона — применяем очистку
                $user->$field = $field === 'phone'
                    ? $this->cleanPhone($checkoutData[$field])
                    : $checkoutData[$field];
                $isUpdated = true;
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

    /**
     * 🎯 ПРОВЕРКА МИНИМАЛЬНЫХ СУММ ЗАКАЗА ПО ЗАВЕДЕНИЯМ
     */
    private function validateMinimumOrderAmounts(array $basketData): array
    {
        $errors = [];

        foreach ($basketData['partner_boxes'] as $uuid => $box) {
            $partnerId = $box['id'];
            $partner = Tenant::query()->find($partnerId);

            if (!$partner) {
                continue;
            }

            // Получаем минимальную сумму заказа из настроек заведения
            $settings = $partner->settings ?? [];
            $minPrice = $this->safeFloat($settings['min_price'] ?? 0);

            // Если минимальная сумма не установлена, пропускаем
            if ($minPrice <= 0) {
                continue;
            }

            $currentAmount = $box['summary_price'];

            // Проверяем, не меньше ли текущая сумма минимальной
            if ($currentAmount < $minPrice) {
                $errors[] = [
                    'partner_id' => $partnerId,
                    'partner_name' => $partner->title ?? 'Заведение',
                    'partner_slug' => $partner->slug ?? '',
                    'min_required' => $minPrice,
                    'current_amount' => $currentAmount,
                    'shortage' => $minPrice - $currentAmount,
                ];
            }
        }

        return $errors;
    }

    private function markBasketItemsAsOrdered(array $basketIds): void
    {
        if (empty($basketIds)) {
            return;
        }

        Basket::query()
            ->whereIn('id', $basketIds)
            ->where('tenant_id', $this->tenant->id)
            ->where('tenant_user_id', $this->tenantUser->id)
            ->whereNull('ordered_at')
            ->update([
                'ordered_at' => Carbon::now('+3:00'),
            ]);
    }

    private function foodShopCheckout(): array
    {
        try {
            $context = $this->prepareCheckoutContext();

            $basketData = $this->processBasketAndCalculateTotals($context);

            $minOrderErrors = $this->validateMinimumOrderAmounts($basketData);
            $scheduleErrors = $this->validateSchedule($basketData);

            if (!empty($scheduleErrors)) {
                return [
                    'success' => false,
                    'message' => 'Некоторые заведения или служба доставки сейчас закрыты',
                    'schedule_errors' => $scheduleErrors,
                ];
            }

            if (!empty($minOrderErrors)) {
                return [
                    'success' => false,
                    'message' => 'Не достигнута минимальная сумма заказа',
                    'minimum_order_errors' => $minOrderErrors,
                ];
            }

            /*
             * Создаём заказ только после успешной проверки корзины.
             */
            $order = $this->createOrderRecord(
                $context,
                $basketData
            );

            /*
             * После создания заказа можно пометить реальные позиции
             * корзины как заказанные.
             */
            $this->markBasketItemsAsOrdered(
                $basketData['basket_ids']
            );

            $kanbanTaskId = $this->notifyStakeholders(
                $order,
                $context,
                $basketData
            );

            $paymentData = $this->processPaymentAndReceipt(
                $order,
                $context,
                $basketData,
                $kanbanTaskId
            );

            $this->finalizeOrder($order);

            return [
                'success' => true,
                'order_id' => $order->id,
                'dialog_id' => $order->dialog_id,
                'summary_price' => $basketData['final_price'],
                'summary_count' => $basketData['summary_count'],
                'delivery_price' => $context['delivery_price'],
                'payment' => $paymentData,
            ];

        } catch (\Throwable $e) {
            Log::error(
                '[Checkout] Критическая ошибка: ' . $e->getMessage(),
                [
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return [
                'success' => false,
                'message' => 'Ошибка при оформлении заказа. Попробуйте позже.',
            ];
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
            'customer_phone' => $data["phone"] ? $this->cleanPhone($data["phone"]) : $this->tenantUser->phone,
            'persons' => $data["persons"] ?? null,
        ];
    }

    private function processBasketAndCalculateTotals(array $context): array
    {
        $basket = Basket::query()
            ->with([
                'collection',
                'product.ingredientGroups.ingredients',
                'product.components',
            ])
            ->where('tenant_id', $this->tenant->id)
            ->where('tenant_user_id', $this->tenantUser->id)
            ->whereNull('ordered_at')
            ->get();

        /*
         * Пустая корзина.
         */
        if ($basket->isEmpty()) {
            return [
                'summary_price' => 0.0,
                'summary_count' => 0,
                'summary_discount' => 0.0,
                'final_price' => 0.0,
                'cashback' => 0.0,

                'product_info' => [],
                'partner_boxes' => [],

                'basket_ids' => [],
                'basket_count' => 0,
                'processed_basket_count' => 0,
                'filtered_basket_count' => 0,
                'filtered_items' => [],
            ];
        }

        $summaryPrice = 0.0;
        $summaryCount = 0;
        $summaryDiscount = 0.0;

        $tmpOrderProductInfo = [];
        $partnerProductBox = [];

        $basketIds = [];
        $filteredItems = [];

        foreach ($basket as $item) {

            /*
             * =========================================================
             * ОБЫЧНЫЙ ТОВАР
             * =========================================================
             */
            if ($item->product_id) {

                $product = $item->product;

                /*
                 * Товар мог быть удалён через SoftDeletes
                 * или физически отсутствовать.
                 */
                if (!$product) {
                    $filteredItems[] = [
                        'basket_id' => $item->id,
                        'product_id' => $item->product_id,
                        'reason' => 'product_not_found',
                    ];

                    Log::warning('[Checkout] Товар из корзины не найден', [
                        'basket_id' => $item->id,
                        'product_id' => $item->product_id,
                        'tenant_id' => $this->tenant->id,
                        'tenant_user_id' => $this->tenantUser->id,
                    ]);

                    continue;
                }

                /*
                 * ВАЖНО:
                 *
                 * Здесь НЕ фильтруем товар по:
                 *
                 * - partners.is_active
                 * - partners.display_self
                 *
                 * Checkout должен рассчитывать фактическое содержимое
                 * корзины.
                 *
                 * Владелец товара определяется непосредственно
                 * через Product::tenant_id.
                 */
                $productTenantId = (int) $product->tenant_id;

                /*
                 * Basket::$casts уже преобразует params в array.
                 */
                $params = is_array($item->params)
                    ? $item->params
                    : [];

                /*
                 * =====================================================
                 * КОМПОНЕНТЫ СОСТАВНОГО ТОВАРА
                 * =====================================================
                 */
                $componentTotal = 0.0;
                $componentsInfo = [];

                if ($product->is_composite) {

                    foreach ($product->components ?? collect() as $component) {

                        $componentPrice = $this->safeFloat(
                            $component->price ?? 0
                        );

                        $componentCount = max(
                            1,
                            (int) (
                                $params['components'][$component->id] ?? 1
                            )
                        );

                        $componentTotal +=
                            $componentPrice * $componentCount;

                        $componentsInfo[] = [
                            'id' => $component->id,
                            'name' => $component->name ?? '',
                            'price' => $componentPrice,
                            'count' => $componentCount,
                        ];
                    }
                }

                /*
                 * =====================================================
                 * БАЗОВАЯ ЦЕНА
                 * =====================================================
                 */
                $basePrice = $this->safeFloat(
                    $product->price ?? 0
                );

                if ($product->is_composite) {
                    $basePrice += $componentTotal;
                }

                /*
                 * =====================================================
                 * ДОБАВКИ ИНГРЕДИЕНТОВ
                 * =====================================================
                 */
                $ingredientExtra = 0.0;
                $ingredientsInfo = [];

                $selectedIngredients = $params['ingredients'] ?? [];

                if (is_array($selectedIngredients)) {

                    foreach ($product->ingredientGroups ?? [] as $group) {

                        foreach ($group->ingredients ?? [] as $ingredient) {

                            if (!in_array(
                                $ingredient->id,
                                $selectedIngredients
                            )) {
                                continue;
                            }

                            $ingredientPrice = $this->safeFloat(
                                $ingredient->price ?? 0
                            );

                            $ingredientExtra += $ingredientPrice;

                            $ingredientsInfo[] = [
                                'id' => $ingredient->id,
                                'name' => $ingredient->name ?? '',
                                'price' => $ingredientPrice,
                            ];
                        }
                    }
                }

                /*
                 * Цена единицы товара с ингредиентами.
                 */
                $finalUnitPrice =
                    $basePrice + $ingredientExtra;

                /*
                 * =====================================================
                 * ПАРТНЁРСКАЯ НАЦЕНКА
                 * =====================================================
                 */
                $extraCharge = 0.0;

                if ($item->tenant_partner_id) {

                    $partner = Partner::query()
                        ->where(
                            'tenant_id',
                            $item->tenant_id
                        )
                        ->where(
                            'tenant_partner_id',
                            $item->tenant_partner_id
                        )
                        ->first();

                    if ($partner) {

                        $extraCharge = $this->safeFloat(
                            $partner->extra_charge ?? 0
                        );

                    } else {

                        Log::warning('[Checkout] Партнёр не найден', [
                            'basket_id' => $item->id,
                            'tenant_id' => $item->tenant_id,
                            'tenant_partner_id' => $item->tenant_partner_id,
                            'product_id' => $product->id,
                        ]);
                    }
                }

                if ($extraCharge != 0) {
                    $finalUnitPrice *=
                        1 + ($extraCharge / 100);
                }

                /*
                 * =====================================================
                 * КОЛИЧЕСТВО
                 * =====================================================
                 */
                $count = max(
                    1,
                    (int) $item->count
                );

                if ($product->is_weight_product) {

                    $step = $this->safeFloat(
                        $params['step'] ?? 1
                    );

                    if ($step <= 0) {
                        $step = 1;
                    }

                    $price =
                        $finalUnitPrice * ($count / $step);

                    /*
                     * Для весового товара одна позиция.
                     */
                    $summaryItemCount = 1;

                } else {

                    $price =
                        $finalUnitPrice * $count;

                    $summaryItemCount = $count;
                }

                $price = max(
                    0.0,
                    $price
                );

                /*
                 * =====================================================
                 * СКИДКА ТОВАРА
                 * =====================================================
                 */
                $discount = $this->safeFloat(
                    $params['discount_amount'] ?? 0
                );

                /*
                 * =====================================================
                 * ИНФОРМАЦИЯ ТОВАРА
                 * =====================================================
                 */
                $productInfo = [
                    'basket_id' => $item->id,

                    'product_id' => $product->id,

                    /*
                     * Фактический владелец товара.
                     */
                    'tenant_id' => $productTenantId,

                    'name' => $product->name,

                    'price' => $price,
                    'unit_price' => $finalUnitPrice,

                    'count' => $count,

                    'is_weight_product' =>
                        (bool) $product->is_weight_product,

                    'is_composite' =>
                        (bool) $product->is_composite,

                    'comment' => $item->comment,

                    'params' => $params,

                    'components' => $componentsInfo,

                    'ingredients' => $ingredientsInfo,

                    'discount' => $discount,
                ];

                $tmpOrderProductInfo[] =
                    $productInfo;

                /*
                 * =====================================================
                 * PARTNER BOX
                 * =====================================================
                 *
                 * Сохраняем старый контракт:
                 *
                 * $box['id']
                 *
                 * используется далее в:
                 *
                 * - validateMinimumOrderAmounts()
                 * - validateSchedule()
                 * - notifyStakeholders()
                 *
                 * Поэтому id = ID Tenant-владельца товара.
                 */
                $partnerKey = implode(':', [
                    $productTenantId,
                    (int) ($item->tenant_partner_id ?? 0),
                ]);

                if (!isset($partnerProductBox[$partnerKey])) {
                    $partnerTenant = Tenant::query()->find($productTenantId);
                    $partnerProductBox[$partnerKey] = [
                        'id' => $productTenantId,
                        'tenant_id' => $productTenantId,
                        'tenant_partner_id' => $item->tenant_partner_id,
                        'name' => $partnerTenant?->name
                                ?? $partnerTenant?->title
                                ?? 'Магазин',
                        'thread' => $partnerTenant?->topics['orders'] ?? null,
                        /*
                         * Доставка является общей стоимостью доставки текущего заказа.
                         * Сохраняем её в box, поскольку downstream-код её ожидает.
                         */
                        'delivery_price' => $this->safeFloat(
                                $context['delivery_price'] ?? 0
                            ) ?? 0.0,

                        'distance' => $this->safeFloat(
                                $context['distance'] ?? 0
                            ) ?? 0.0,

                        'products' => [],

                        'summary_count' => 0,
                        'summary_price' => 0.0,
                        'summary_discount' => 0.0,
                    ];
                }

                $partnerProductBox[$partnerKey]['products'][] =
                    $productInfo;

                $partnerProductBox[$partnerKey]['summary_count'] +=
                    $summaryItemCount;

                $partnerProductBox[$partnerKey]['summary_price'] +=
                    $price;

                $partnerProductBox[$partnerKey]['summary_discount'] +=
                    $discount;

                /*
                 * =====================================================
                 * ОБЩИЕ ИТОГИ
                 * =====================================================
                 */
                $summaryCount +=
                    $summaryItemCount;

                $summaryPrice +=
                    $price;

                $summaryDiscount +=
                    $discount;

                /*
                 * Запоминаем только реально обработанную
                 * строку корзины.
                 *
                 * ordered_at здесь НЕ меняем.
                 */
                $basketIds[] = $item->id;

                continue;
            }

            /*
             * =========================================================
             * КОЛЛЕКЦИЯ
             * =========================================================
             */
            if ($item->collection_id) {

                $collection = $item->collection;

                if (!$collection) {

                    $filteredItems[] = [
                        'basket_id' => $item->id,
                        'collection_id' => $item->collection_id,
                        'reason' => 'collection_not_found',
                    ];

                    Log::warning(
                        '[Checkout] Коллекция из корзины не найдена',
                        [
                            'basket_id' => $item->id,
                            'collection_id' => $item->collection_id,
                            'tenant_id' => $this->tenant->id,
                            'tenant_user_id' => $this->tenantUser->id,
                        ]
                    );

                    continue;
                }

                $params = is_array($item->params)
                    ? $item->params
                    : [];

                /*
                 * ID выбранных товаров коллекции.
                 */
                $selectedIds = $params['ids'] ?? [];

                if (!is_array($selectedIds) || empty($selectedIds)) {

                    $filteredItems[] = [
                        'basket_id' => $item->id,
                        'collection_id' => $item->collection_id,
                        'reason' => 'collection_without_products',
                    ];

                    Log::warning(
                        '[Checkout] Пустая коллекция в корзине',
                        [
                            'basket_id' => $item->id,
                            'collection_id' => $item->collection_id,
                        ]
                    );

                    continue;
                }

                /*
                 * Collection::products() возвращает Builder,
                 * поэтому явно вызываем ->get().
                 */
                $collectionProducts = $collection
                    ->products()
                    ->get();

                $selectedProducts = $collectionProducts
                    ->whereIn('id', $selectedIds);

                if ($selectedProducts->isEmpty()) {

                    $filteredItems[] = [
                        'basket_id' => $item->id,
                        'collection_id' => $item->collection_id,
                        'reason' => 'collection_products_not_found',
                    ];

                    Log::warning(
                        '[Checkout] Товары коллекции не найдены',
                        [
                            'basket_id' => $item->id,
                            'collection_id' => $item->collection_id,
                            'selected_ids' => $selectedIds,
                        ]
                    );

                    continue;
                }

                /*
                 * =====================================================
                 * ЦЕНА КОЛЛЕКЦИИ
                 * =====================================================
                 */
                $collectionPrice = 0.0;

                $collectionProductsInfo = [];

                foreach ($selectedProducts as $product) {

                    $productPrice = $this->safeFloat(
                        $product->price ?? 0
                    );

                    $collectionPrice +=
                        $productPrice;

                    $collectionProductsInfo[] = [
                        'id' => $product->id,

                        'name' =>
                                $product->name ?? '',

                        'price' =>
                            $productPrice,
                    ];
                }

                /*
                 * FIXED — фиксированная цена коллекции.
                 */
                if (
                    $collection->pricing_type ===
                    Collection::PRICING_TYPE_FIXED
                ) {

                    $collectionPrice =
                        $this->safeFloat(
                            $collection->fixed_price ?? 0
                        );
                }

                /*
                 * SUM — сумма выбранных товаров.
                 */
                elseif (
                    $collection->pricing_type ===
                    Collection::PRICING_TYPE_SUM
                ) {
                    // Уже рассчитано выше.
                }

                /*
                 * =====================================================
                 * СКИДКА КОЛЛЕКЦИИ
                 * =====================================================
                 */
                $collectionDiscount = $this->safeFloat(
                    $collection->discount ?? 0
                );

                if ($collectionDiscount > 0) {

                    $collectionDiscount =
                        min(100, $collectionDiscount);

                    $collectionPrice *=
                        1 - ($collectionDiscount / 100);
                }

                /*
                 * Дополнительная наценка, если она сохранена
                 * в params корзины.
                 */
                $collectionExtraCharge = $this->safeFloat(
                    $params['extra_charge'] ?? 0
                );

                if ($collectionExtraCharge != 0) {

                    $collectionPrice *=
                        1 + ($collectionExtraCharge / 100);
                }

                $collectionPrice = max(
                    0.0,
                    $collectionPrice
                );

                /*
                 * Защита от пустой / нулевой коллекции.
                 */
                if ($collectionPrice <= 0) {

                    $filteredItems[] = [
                        'basket_id' => $item->id,
                        'collection_id' => $item->collection_id,
                        'reason' => 'collection_zero_price',
                    ];

                    Log::warning(
                        '[Checkout] Стоимость коллекции равна 0',
                        [
                            'basket_id' => $item->id,
                            'collection_id' => $item->collection_id,
                            'pricing_type' =>
                                $collection->pricing_type,
                            'selected_ids' => $selectedIds,
                        ]
                    );

                    continue;
                }

                /*
                 * =====================================================
                 * КОЛИЧЕСТВО
                 * =====================================================
                 */
                $count = max(
                    1,
                    (int) $item->count
                );

                $totalCollectionPrice =
                    $collectionPrice * $count;

                /*
                 * =====================================================
                 * ИНФОРМАЦИЯ КОЛЛЕКЦИИ
                 * =====================================================
                 */
                $collectionTenantId = (int) (
                    $collection->tenant_id ??
                    $this->tenant->id
                );

                $collectionInfo = [
                    'basket_id' => $item->id,

                    'collection_id' =>
                        $collection->id,

                    'tenant_id' =>
                        $collectionTenantId,

                    'name' =>
                            $collection->name ?? 'Коллекция',

                    'price' =>
                        $totalCollectionPrice,

                    'unit_price' =>
                        $collectionPrice,

                    'count' =>
                        $count,

                    'pricing_type' =>
                        $collection->pricing_type,

                    'discount' =>
                        $collectionDiscount,

                    'products' =>
                        $collectionProductsInfo,

                    'params' =>
                        $params,

                    'comment' =>
                        $item->comment,
                ];

                $tmpOrderProductInfo[] =
                    $collectionInfo;

                /*
                 * =====================================================
                 * PARTNER BOX КОЛЛЕКЦИИ
                 * =====================================================
                 */
                $partnerKey = implode(':', [
                    $collectionTenantId,
                    (int) ($item->tenant_partner_id ?? 0),
                ]);

                if (!isset($partnerProductBox[$partnerKey])) {

                    $partnerTenant = Tenant::query()->find($collectionTenantId);

                    $partnerProductBox[$partnerKey] = [
                        'id' => $collectionTenantId,
                        'tenant_id' => $collectionTenantId,
                        'tenant_partner_id' => $item->tenant_partner_id,

                        'name' => $partnerTenant?->name
                                ?? $partnerTenant?->title
                                ?? 'Магазин',

                        'thread' => $partnerTenant?->topics['orders'] ?? null,

                        'delivery_price' => $this->safeFloat(
                                $context['delivery_price'] ?? 0
                            ) ?? 0.0,

                        'distance' => $this->safeFloat(
                                $context['distance'] ?? 0
                            ) ?? 0.0,

                        'products' => [],

                        'summary_count' => 0,
                        'summary_price' => 0.0,
                        'summary_discount' => 0.0,
                    ];
                }

                $partnerProductBox[$partnerKey]['products'][] =
                    $collectionInfo;

                $partnerProductBox[$partnerKey]['summary_count'] +=
                    $count;

                $partnerProductBox[$partnerKey]['summary_price'] +=
                    $totalCollectionPrice;

                $partnerProductBox[$partnerKey]['summary_discount'] +=
                    $collectionDiscount;

                /*
                 * =====================================================
                 * ОБЩИЕ ИТОГИ
                 * =====================================================
                 */
                $summaryCount +=
                    $count;

                $summaryPrice +=
                    $totalCollectionPrice;

                $summaryDiscount +=
                    $collectionDiscount;

                $basketIds[] =
                    $item->id;

                continue;
            }

            /*
             * =========================================================
             * НЕКОРРЕКТНАЯ СТРОКА КОРЗИНЫ
             * =========================================================
             */
            $filteredItems[] = [
                'basket_id' => $item->id,

                'reason' =>
                    'basket_item_without_product_or_collection',
            ];

            Log::warning(
                '[Checkout] Некорректная строка корзины',
                [
                    'basket_id' => $item->id,
                    'tenant_id' => $this->tenant->id,
                    'tenant_user_id' => $this->tenantUser->id,
                ]
            );
        }

        /*
         * =============================================================
         * ЗАЩИТА ОТ ЗАКАЗА ТОЛЬКО С ДОСТАВКОЙ
         * =============================================================
         *
         * Если после обработки корзины товаров нет,
         * возвращаем пустой результат.
         *
         * Это не позволит создать заказ:
         *
         * summary_price = 0
         * delivery_price > 0
         */
        if (
            $summaryCount <= 0 ||
            $summaryPrice <= 0 ||
            empty($tmpOrderProductInfo)
        ) {

            Log::error(
                '[Checkout] Корзина не содержит валидных товаров',
                [
                    'tenant_id' =>
                        $this->tenant->id,

                    'tenant_user_id' =>
                        $this->tenantUser->id,

                    'basket_count' =>
                        $basket->count(),

                    'processed_basket_count' =>
                        count($basketIds),

                    'filtered_basket_count' =>
                        count($filteredItems),

                    'summary_count' =>
                        $summaryCount,

                    'summary_price' =>
                        $summaryPrice,

                    'delivery_price' =>
                            $context['delivery_price'] ?? 0,

                    'filtered_items' =>
                        $filteredItems,
                ]
            );

            return [
                'summary_price' => 0.0,
                'summary_count' => 0,
                'summary_discount' => 0.0,
                'final_price' => 0.0,
                'cashback' => 0.0,

                'product_info' => [],
                'partner_boxes' => [],

                'basket_ids' =>
                    array_values(
                        array_unique($basketIds)
                    ),

                'basket_count' =>
                    $basket->count(),

                'processed_basket_count' =>
                    count($basketIds),

                'filtered_basket_count' =>
                    count($filteredItems),

                'filtered_items' =>
                    $filteredItems,
            ];
        }

        /*
         * =============================================================
         * CASHBACK
         * =============================================================
         */
        $cashback = 0.0;

        $cashbackResult =
            $this->useCashBackForPayment(
                $summaryPrice
            );

        $cashback = $this->safeFloat(
            $cashbackResult
        );

        /*
         * Cashback не может быть больше суммы товаров.
         */
        $cashback = min(
            max(0.0, $cashback),
            $summaryPrice
        );

        $finalPrice = max(
            0.0,
            $summaryPrice - $cashback
        );

        /*
         * =============================================================
         * ФИНАЛЬНЫЙ РЕЗУЛЬТАТ
         * =============================================================
         */
        return [
            'summary_price' =>
                $summaryPrice,

            'summary_count' =>
                $summaryCount,

            'summary_discount' =>
                $summaryDiscount,

            'final_price' =>
                $finalPrice,

            'cashback' =>
                $cashback,

            'product_info' =>
                $tmpOrderProductInfo,

            'partner_boxes' =>
                $partnerProductBox,

            'basket_ids' =>
                array_values(
                    array_unique($basketIds)
                ),

            'basket_count' =>
                $basket->count(),

            'processed_basket_count' =>
                count($basketIds),

            'filtered_basket_count' =>
                count($filteredItems),

            'filtered_items' =>
                $filteredItems,
        ];
    }

    private function createOrderRecord(
        array $context,
        array $basketData
    ): Order {
        if (
            empty($basketData['product_info']) ||
            (int) ($basketData['summary_count'] ?? 0) <= 0 ||
            (float) ($basketData['final_price'] ?? 0) <= 0
        ) {
            Log::error('[Checkout] Попытка создать заказ без товаров', [
                'tenant_id' => $this->tenant->id,
                'tenant_user_id' => $this->tenantUser->id,
                'basket_count' => $basketData['basket_count'] ?? null,
                'processed_basket_count' => $basketData['processed_basket_count'] ?? null,
                'summary_count' => $basketData['summary_count'] ?? null,
                'summary_price' => $basketData['summary_price'] ?? null,
                'final_price' => $basketData['final_price'] ?? null,
                'delivery_price' => $context['delivery_price'] ?? null,
                'filtered_items' => $basketData['filtered_items'] ?? [],
            ]);

            throw new \RuntimeException(
                'Невозможно создать заказ: в корзине нет товаров.'
            );
        }

        return Order::query()->create([
            'tenant_id' => $this->tenant->id,
            'tenant_user_id' => $this->tenantUser->id,

            'delivery_service_info' => null,
            'deliveryman_info' => null,

            'product_details' => [
                'from' => $this->tenant->name ?? 'Магазин',
                'products' => $basketData['product_info'],
            ],

            'product_count' => (int) $basketData['summary_count'],

            'summary_price' => (float) $basketData['final_price'],
            'delivery_price' => (float) ($context['delivery_price'] ?? 0),

            // Остальные поля оставь из своего текущего метода
            // без изменений.
        ]);
    }

    /**
     * 🎯 Формирует красивое сообщение для Telegram канала о новом заказе
     */
    private function buildTelegramOrderNotification(Order $order, array $context, array $basketData): string
    {
        $addr = $this->getResolvedAddress();
        $orderType = $context['need_pickup'] ? '🏪 Самовывоз' : '🚚 Доставка';
        $addressText = $context['need_pickup'] ? 'Не требуется' : $addr['address'];

        $persons = $context["persons"] ?? 1;
        $money   = $context["money"]   ?? 'Не указано';
        $info    = $context["info"]    ?? 'Не указано';
        $cash    = self::PAYMENT_TYPES[$context["payment_type"] ?? 0] ?? 'Не указан';

        $phone = $this->cleanPhone($order->receiver_phone);
        $baseUrl = request()->getSchemeAndHttpHost() ?? 'не указано';

        $clientName = $order->receiver_name ?? 'Клиент';

        $message  = "🔔 <b>ЗАКАЗ #{$order->id}</b>\n";
        $message .= "📅 " . now("+3:00")->format('d.m.Y H:i') . "\n\n";
        $message .= "👤 <b>Клиент:</b> {$clientName}\n";
        $message .= "📞 <b>Телефон:</b> {$phone}\n";
        $message .= "📦 <b>Способ получения:</b> {$orderType}\n";
        $message .= "💳 <b>Тип оплаты:</b> {$cash}\n";
        $message .= "💵 <b>Сдачи с:</b> {$money}\n";
        $message .= "🙍🏻‍♂️ <b>Число людей:</b> {$persons}\n";
        $message .= "🌐 <b>Источник:</b> {$baseUrl}\n";

        if (!$context['need_pickup']) {
            $message .= "📍 <b>Адрес:</b> {$addressText}\n";
            if (!empty($addr['entrance_number'])) $message .= "🚪 Подъезд: {$addr['entrance_number']}\n";
            if (!empty($addr['floor_number']))    $message .= "🏢 Этаж: {$addr['floor_number']}\n";
            if (!empty($addr['flat_number']))     $message .= "🏠 Кв/Офис: {$addr['flat_number']}\n";
        }

        $message .= "\n🛒 <b>Состав заказа:</b>\n";

        foreach ($basketData['partner_boxes'] as $box) {
            $message .= "\n🏪 <b>{$box['name']}:</b>\n";

            foreach ($box['products'] as $product) {
                $priceFormatted = number_format($product['price'], 0, '.', ' ');
                $message .= "  • {$product['name']} x{$product['count']} = {$priceFormatted} ₽\n";

                // 🆕 Показываем составные компоненты
                if (!empty($product['is_composite']) && !empty($product['selected_components'])) {
                    $message .= "    <b>Состав:</b>\n";
                    foreach ($product['selected_components'] as $comp) {
                        $compTotal = number_format($comp['price'] * $comp['quantity'], 0, '.', ' ');
                        $message .= "      ├─ {$comp['name']} x{$comp['quantity']} = {$compTotal} ₽\n";
                    }
                }

                // 🆕 Показываем выбранные ингредиенты
                if (!empty($product['selected_ingredients'])) {
                    foreach ($product['selected_ingredients'] as $ing) {
                        $ingText = $ing['extra_price'] > 0
                            ? "      ├─ {$ing['name']} (+{$ing['extra_price']} ₽)\n"
                            : "      ├─ {$ing['name']}\n";
                        $message .= $ingText;
                    }
                }

                if (!empty($product['details'])) {
                    $message .= $product['details'] . "\n";
                }
            }

            $boxSubtotal = number_format($box['summary_price'], 0, '.', ' ');
            $message .= "  └─ <b>Итого по заведению:</b> {$boxSubtotal} ₽\n";

            if (!$context['need_pickup'] && ($box['delivery_price'] ?? 0) > 0) {
                $boxDelivery = number_format($box['delivery_price'], 0, '.', ' ');
                $distText    = ($box['distance'] > 0) ? " ({$box['distance']} км)" : "";
                $message .= "  └─ <b>Доставка:</b> {$boxDelivery} ₽{$distText}\n";
            }
        }

        $totalToPay = $order->summary_price + $order->delivery_price;
        $message .= "\n━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💰 <b>ВСЕГО К ОПЛАТЕ:</b> " . number_format($totalToPay, 0, '.', ' ') . " ₽\n";

        if ($order->delivery_price > 0 && !$context['need_pickup']) {
            $message .= "🚚 <b>Общая доставка:</b> " . number_format($order->delivery_price, 0, '.', ' ') . " ₽";
            if ($context['distance'] > 0) $message .= " ({$context['distance']} км)";
            $message .= "\n";
        }

        if (!empty($this->data['info'])) {
            $message .= "\n📝 <b>Комментарий:</b> {$this->data['info']}\n";
        }

        $baseUrl = request()->getSchemeAndHttpHost();
        if ($baseUrl) {
            $chatUrl = "{$baseUrl}/pwa#/chat/{$order->dialog_id}";
            $message .= "🔗 <a href=\"{$chatUrl}\">Открыть чат</a>\n";

            $client     = TenantUser::query()->where("id", $order->tenant_user_id)->first();
            $clientInfo = $client ? $client->getTelegramInfo() : [
                'name'  => 'Неизвестный клиент',
                'phone' => 'Не указан',
                'id'    => $order->tenant_user_id,
            ];

            if (!empty($clientInfo['profile_url'])) {
                $message .= "👤 <a href=\"{$clientInfo['profile_url']}\">Профиль клиента</a>\n";
            }
        }

        return $message;
    }

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
                if (!empty($tenantInBox->iiko?->api_login)) {
                    Log::info('IIKO order created for order #' . $order->id);
                }
            }
        }

        // 2. 🎯 Формируем ВСЕ тексты заранее
        $clientMessage   = $this->buildClientMessage(
            $order,
            $basketData['partner_boxes'],
            $basketData['summary_price'],
            $basketData['cashback'],
            $basketData['summary_count'],
            $basketData['summary_discount'],
            $context['delivery_price'],
            $context['distance'],
            $context['need_pickup']
        );

        $crmMessage = $this->buildCrmMessage(
            $order,
            $basketData['partner_boxes'],
            $basketData['summary_price'],
            $basketData['cashback'],
            $basketData['summary_count'],
            $basketData['summary_discount'],
            $context['delivery_price'],
            $context['distance'],
            $context['need_pickup']
        );

        $partnerMessages = $this->buildPartnerMessages(
            $order,
            $basketData['partner_boxes'],
            $basketData['cashback'],
            $context['need_pickup']
        );

        $telegramMessage = $this->buildTelegramOrderNotification($order, $context, $basketData);

        // 3. 🎯 Kanban-данные (общие для CRM)
        $kanbanCustomData = [
            'tenant_id'       => $this->tenant?->id,
            'tenant_name'     => $this->tenant?->name ?? $this->tenant?->uuid,
            'tenant_user_id'  => $this->tenantUser->id,
            'last_order_id'   => $order->id,
            'last_order_date' => now()->toIso8601String(),
            'product_details' => [[
                'from'     => $this->tenant?->name ?? $this->tenant->uuid ?? 'Магазин',
                'products' => $basketData['product_info'],
            ]],
            'product_count'   => $basketData['summary_count'],
            'delivery_price'  => $context['delivery_price'],
            'delivery_note'   => $this->fsPrepareDeliveryNote(),
            'payment_type'    => $context['payment_type'],
            'payment_status'  => $paymentStatusText,
            'summary_price'   => $basketData['final_price'],
        ];

        // 4. 🚀 ОДНИМ ВЫЗОВОМ: Клиент + CRM + Telegram-канал уведомлений
        //    MessageService сам разберётся, куда что доставлять
        $crmResult = MessageService::call()->sendMessage([
            // Клиенту — в диалог
            'client_message'   => $clientMessage,
            'telegram_message' => $telegramMessage,
            'crm_message'      => $crmMessage,
            'dialog_id' => $dialog?->id,

            // Для CRM / Telegram — заголовок
            'title'     => "Заказ #{$order->id} — {$context['customer_name']}",

            // Метаданные
            'meta'      => [
                'order_id'           => $order->id,
                'payment_status'     => $paymentStatusText,
                'is_system'          => false,
                'tenant_user_id'     => $this->tenantUser->id, // 🎯 Добавляем для доступа к профилю
                'dialog_id'          => $dialog?->id,
                // CRM-specific
                'customer_name'      => $context['customer_name'],
                'customer_phone'     => $context['customer_phone'],
                'summary_price'      => $basketData['final_price'],
                'need_pickup'        => $context['need_pickup'],
                'delivery_note'      => $this->fsPrepareDeliveryNote(),
                'kanban_board_uuid'  => $context['kanban_board_uuid'],
                'kanban_thread'      => $context['kanban_thread'],
                'kanban_custom_data' => $kanbanCustomData,
                'kanban_payload'     => array_merge($kanbanCustomData, [
                    'source' => 'foodshop',
                    'type'   => 'new_order',
                ]),
            ],

            // 🎯 Главное: говорим сервису, куда отправлять
            'recipients' => [
                'client'   => true,                       // → запись в TenantMessage
                'crm'      => (bool) $context['kanban_enabled'], // → Kanban
                'telegram' => true,                       // → Telegram channel 🎯
            ],
        ]);

        // 5. Сохраняем ID задачи Kanban в заказ
        if (!empty($crmResult['crm']['task_id'])) {
            $kanbanTaskId = $crmResult['crm']['task_id'];
            $order->updateQuietly([
                'meta' => array_merge($order->meta ?? [], [
                    'kanban_task_id'    => $kanbanTaskId,
                    'kanban_message_id' => $crmResult['crm']['message_id'] ?? null,
                    'kanban_board_uuid' => $context['kanban_board_uuid'],
                ]),
            ]);
        }

        // 6. 📣 Партнёрам — отдельными вызовами (у каждого свой thread_id)
        foreach ($partnerMessages as $partnerData) {
            MessageService::call()->sendMessage([
                'message'   => $partnerData['message'],
                'thread_id' => $partnerData['thread'],
                'title'     => "Заказ #{$order->id} — {$partnerData['name']}",
                'meta'      => [
                    'order_id'     => $order->id,
                    'partner_id'   => $partnerData['id'] ?? null,
                    'partner_name' => $partnerData['name'] ?? null,
                    'type'         => 'partner_order',
                    'is_system'    => true,
                ],
                'recipients' => ['partners' => true], // → в Telegram thread партнёра
            ]);
        }

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
        return match ($paymentType) {
            0 => 'Оплачено онлайн (Карта)',
            1, 2, 3 => 'Оплата курьеру / При получении',
            4 => 'Ожидает оплаты по счету СБП',
            default => 'Статус оплаты уточняется',
        };
    }

    private function buildClientMessage($order, $partnerProductBox, $summaryPrice, $cashback, $summaryCount, $summaryDiscount, $deliveryPrice, $distance, $needPickup): string
    {
        $message = "<b>✅ Заказ #{$order->id} принят!</b>\n\n<b>🛒 Состав заказа:</b>\n";

        // 🎯 Перебираем все магазины и добавляем заголовок перед товарами
        $shopCount = count($partnerProductBox);

        foreach ($partnerProductBox as $uuid => $box) {
            // 🎯 Добавляем заголовок с названием магазина
            $shopName = e($box['name'] ?? 'Магазин');
            $shopEmoji = $shopCount > 1 ? '🏪' : '🛍️';

            if ($shopCount > 1) {
                $message .= "\n━━━━━━━━━━━━━━━━━━━━━━\n";
                $message .= "{$shopEmoji} <b>{$shopName}</b>\n";
                $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            } else {
                $message .= "{$shopEmoji} <b>{$shopName}</b>\n";
            }

            // Добавляем сами товары
            $message .= $box["message"] ?? '';
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "\n<b>📊 Итого:</b>\n";
        $message .= "Товары: <b>" . number_format($summaryPrice, 0, '.', ' ') . " ₽</b>\n";

        if ($cashback > 0) {
            $message .= "Скидка (кэшбэк): <b>-" . number_format($cashback, 0, '.', ' ') . " ₽</b>\n";
        }
        if ($summaryDiscount > 0) {
            $message .= "Скидки: <b>-" . number_format($summaryDiscount, 0, '.', ' ') . " ₽</b>\n";
        }
        if ($deliveryPrice > 0) {
            $message .= "Доставка: <b>" . number_format($deliveryPrice, 0, '.', ' ') . " ₽</b>" . ($distance > 0 ? " ({$distance} км)" : "") . "\n";
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

    private function buildPartnerMessages(
        $order,
        $partnerProductBox,
        $cashback,
        $needPickup
    ): array {
        $messages = [];

        foreach ($partnerProductBox as $uuid => $box) {
            $partnerId = (int) ($box['id'] ?? $box['tenant_id'] ?? 0);
            $partner = $partnerId
                ? Tenant::query()->find($partnerId)
                : null;

            $shopName = e(
                $box['name']
                ?? $partner?->name
                ?? $partner?->title
                ?? 'Магазин'
            );

            /*
             * ---------------------------------------------------------
             * Thread партнёра
             * ---------------------------------------------------------
             *
             * В текущем partner_boxes thread не формируется.
             * Поэтому получаем его непосредственно из Tenant.
             */
            $thread = $box['thread']
                ?? ($partner?->topics['orders'] ?? null);

            /*
             * ---------------------------------------------------------
             * Доставка
             * ---------------------------------------------------------
             *
             * В некоторых старых структурах partner_boxes этих ключей
             * нет. Никогда не обращаемся к ним напрямую.
             */
            $deliveryPrice = $this->safeFloat(
                $box['delivery_price'] ?? 0
            ) ?? 0.0;

            $distance = $this->safeFloat(
                $box['distance'] ?? 0
            ) ?? 0.0;

            $summaryPrice = $this->safeFloat(
                $box['summary_price'] ?? 0
            ) ?? 0.0;

            $summaryCount = (int) (
                $box['summary_count'] ?? 0
            );

            $summaryDiscount = $this->safeFloat(
                $box['summary_discount'] ?? 0
            ) ?? 0.0;

            /*
             * ---------------------------------------------------------
             * Формируем список товаров непосредственно из products.
             *
             * Раньше здесь использовался $box["message"], но текущий
             * processBasketAndCalculateTotals() такого ключа не создаёт.
             * Поэтому сообщение строим здесь.
             * ---------------------------------------------------------
             */
            $productMessage = '';

            foreach (($box['products'] ?? []) as $product) {
                $productName = e(
                    $product['name'] ?? 'Неуказанный товар'
                );

                $productCount = $product['count'] ?? 1;

                $productPrice = $this->safeFloat(
                    $product['price'] ?? 0
                ) ?? 0.0;

                $priceFormatted = number_format(
                    $productPrice,
                    2,
                    '.',
                    ' '
                );

                $productMessage .=
                    "• <b>{$productName}</b> × {$productCount} — {$priceFormatted} руб.\n";

                /*
                 * Состав составного товара.
                 */
                if (!empty($product['components'])) {
                    foreach ($product['components'] as $component) {
                        $componentName = e(
                            $component['name'] ?? 'Компонент'
                        );

                        $componentCount = $component['count'] ?? 1;

                        $componentPrice = $this->safeFloat(
                            $component['price'] ?? 0
                        ) ?? 0.0;

                        $componentPriceFormatted = number_format(
                            $componentPrice,
                            2,
                            '.',
                            ' '
                        );

                        $productMessage .=
                            "  └─ {$componentName} × {$componentCount} — {$componentPriceFormatted} руб.\n";
                    }
                }

                /*
                 * Ингредиенты.
                 */
                if (!empty($product['ingredients'])) {
                    foreach ($product['ingredients'] as $ingredient) {
                        $ingredientName = e(
                            $ingredient['name'] ?? 'Ингредиент'
                        );

                        $ingredientPrice = $this->safeFloat(
                            $ingredient['price'] ?? 0
                        ) ?? 0.0;

                        if ($ingredientPrice > 0) {
                            $ingredientPriceFormatted = number_format(
                                $ingredientPrice,
                                2,
                                '.',
                                ' '
                            );

                            $productMessage .=
                                "  └─ {$ingredientName} (+{$ingredientPriceFormatted} руб.)\n";
                        } else {
                            $productMessage .=
                                "  └─ {$ingredientName}\n";
                        }
                    }
                }

                if (!empty($product['comment'])) {
                    $productMessage .=
                        "  💬 " . e($product['comment']) . "\n";
                }

                $productMessage .= "\n";
            }

            /*
             * Если по какой-то причине products отсутствуют,
             * не создаём пустое сообщение.
             */
            if ($productMessage === '') {
                $productMessage = "• Товары отсутствуют в данных заказа.\n";
            }

            /*
             * ---------------------------------------------------------
             * Основное сообщение партнёру
             * ---------------------------------------------------------
             */
            $resultMessage =
                "🔔 <b>Новый заказ #{$order->id}</b>\n";

            $resultMessage .= $needPickup
                ? "#заказсамовывоз\n"
                : "#заказдоставка\n";

            $resultMessage .=
                "\n🏪 <b>{$shopName}</b>\n";

            $resultMessage .=
                "━━━━━━━━━━━━━━━━━━━━━━\n";

            /*
             * Ограничения здоровья.
             */
            $disabilities = $this->fsPrepareDisabilities();

            if ($disabilities) {
                $resultMessage .= $disabilities;
            }

            /*
             * Товары.
             */
            $resultMessage .= "\n🛒 <b>Товары:</b>\n";
            $resultMessage .= $productMessage;

            /*
             * Данные клиента.
             */
            $resultMessage .=
                $this->fsPrepareUserInfo(
                    $order,
                    $cashback
                );

            /*
             * Скидка.
             */
            if ($summaryDiscount > 0) {
                $resultMessage .=
                    "\nСкидка: <b>-" .
                    number_format(
                        $summaryDiscount,
                        2,
                        '.',
                        ' '
                    ) .
                    " руб.</b>";
            }

            /*
             * Итого по заведению.
             */
            $resultMessage .=
                "\nИтого: <b>" .
                number_format(
                    $summaryPrice,
                    2,
                    '.',
                    ' '
                ) .
                " руб.</b> за <b>{$summaryCount} ед.</b>\n";

            /*
             * Доставка.
             *
             * Главное: никаких обращений $box["delivery_price"].
             */
            if (!$needPickup && $deliveryPrice > 0) {
                $distanceText = $distance > 0
                    ? " за {$distance} км"
                    : '';

                $resultMessage .=
                    "\nДоставка: <b>" .
                    number_format(
                        $deliveryPrice,
                        2,
                        '.',
                        ' '
                    ) .
                    " руб.</b>{$distanceText}";

                $resultMessage .=
                    "\nИтого с доставкой: <b>" .
                    number_format(
                        $summaryPrice + $deliveryPrice,
                        2,
                        '.',
                        ' '
                    ) .
                    " руб.</b>";
            }

            /*
             * Даже если у партнёра нет thread, не падаем.
             * MessageService получит null и сам решит, куда отправлять.
             */
            $messages[$uuid] = [
                'message' => $resultMessage,
                'thread' => $thread,
                'id' => $partnerId ?: null,
                'name' => $partner?->name
                        ?? $partner?->title
                        ?? ($box['name'] ?? 'Магазин'),

                /*
                 * Возвращаем нормализованные данные дальше,
                 * чтобы они были доступны остальному коду.
                 */
                'delivery_price' => $deliveryPrice,
                'distance' => $distance,
                'summary_price' => $summaryPrice,
                'summary_count' => $summaryCount,
            ];
        }

        return $messages;
    }

    private function buildCrmMessage($order, $partnerProductBox, $summaryPrice, $cashback, $summaryCount, $summaryDiscount, $deliveryPrice, $distance, $needPickup): string
    {
        $message = "<b>⚠️⚠️⚠️Сводный заказ #{$order->id}⚠️⚠️⚠️</b>\n";
        $message .= (!$needPickup ? "#заказдоставка\n" : "#заказсамовывоз\n");
        $recountDeliveryPrice = $deliveryPrice == 0;

        foreach ($partnerProductBox as $uuid => $box) {
            $shopName = e($box['name'] ?? 'Магазин');

            $message .= "\n<b>━━━━━━━━━━━━━━━━━━━━━━━━</b>\n";
            $message .= "🏪 <b>{$shopName}</b>\n";
            $message .= "<b>━━━━━━━━━━━━━━━━━━━━━━━━</b>\n";
            $message .= ($box["message"] ?? '');
            $message .= "\nСкидка: <b>-" . ($box["summary_discount"] ?? 0) . " руб.</b>";
            $message .= "\nИтого: <b>" . ($box["summary_price"] ?? 0) . " руб.</b> за <b>" . ($box["summary_count"] ?? 0) . " ед.</b>";

            if (($box["delivery_price"] ?? 0) > 0) {
                $message .= "\nДоставка: <b>" . $box["delivery_price"] . " руб.</b> за " . $box["distance"] . " км";
                $message .= "\nИтого c доставкой: <b>" . ($box["summary_price"] + $box["delivery_price"]) . " руб.</b>";
                if ($recountDeliveryPrice) $deliveryPrice += $box["delivery_price"];
            }
        }

        $message .= "\n<b>━━━━━━━━━━━━━━━━━━━━━━━━</b>\n";
        $message .= "<b>📊 ИТОГО ПО ВСЕМ ЗАКАЗАМ:</b>\n";

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
    /**
     * 🎯 ПРОВЕРКА ГРАФИКА РАБОТЫ (Главный тенант + Партнеры)
     */
    private function validateSchedule(array $basketData): array
    {
        $errors = [];

        // 1. Проверка основного тенанта (Служба доставки)
        $mainTenantSchedule = $this->tenant->settings['schedule'] ?? null;



        if (!$this->isCurrentlyOpen($mainTenantSchedule)) {
            $errors[] = [
                'type' => 'main_tenant',
                'name' => $this->tenant->name ?? 'Служба доставки',
                'message' => 'Служба доставки в данный момент не принимает заказы.',
                'closes_at' => $this->getClosingTime($mainTenantSchedule),
            ];
        }

        // 2. Проверка партнеров в корзине
        foreach ($basketData['partner_boxes'] as $box) {
            $partnerId = $box['id'];
            $partner = Tenant::query()->find($partnerId);

            if (!$partner) continue;

            $partnerSchedule = $partner->settings['schedule'] ?? null;


            if (!$this->isCurrentlyOpen($partnerSchedule)) {
                $errors[] = [
                    'type' => 'partner',
                    'name' => $partner->name ?? $partner->title ?? 'Заведение',
                    'message' => 'Это заведение сейчас закрыто и не принимает заказы.',
                    'closes_at' => $this->getClosingTime($partnerSchedule),
                ];
            }
        }

        return !is_null($mainTenantSchedule) ? $errors : [];
    }

    /**
     * Проверяет, открыто ли заведение прямо сейчас
     */
    private function isCurrentlyOpen(?array $schedule): bool
    {
        if (empty($schedule) || !is_array($schedule)) {
            return true; // Если расписания нет, считаем, что работает 24/7
        }

        $now = now();
        // Laravel dayOfWeek: 0 = Понедельник, 6 = Воскресенье.
        // (Если ваш фронтенд сохраняет 0=Воскресенье, замените на: $now->format('w'))
        $currentDayIndex = $now->dayOfWeek;

        if (!isset($schedule[$currentDayIndex])) {
            return true;
        }

        $daySchedule = $schedule[$currentDayIndex];

        if (!empty($daySchedule['closed'])) {
            return false;
        }

        $currentTime = $now->setTimezone("+3:00")->format('H:i');
        $startTime = $daySchedule['start_at'] ?? '00:00';
        $endTime = $daySchedule['end_at'] ?? '23:59';

        // Обработка ночных смен (например, 22:00 до 04:00)
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Получает время закрытия для сообщения пользователю
     */
    private function getClosingTime(?array $schedule): ?string
    {
        if (empty($schedule) || !is_array($schedule)) return null;
        $currentDayIndex = now()->dayOfWeek;
        if (isset($schedule[$currentDayIndex]) && !empty($schedule[$currentDayIndex]['end_at'])) {
            return $schedule[$currentDayIndex]['end_at'];
        }
        return null;
    }
}
