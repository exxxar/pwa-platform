<?php

namespace App\Services;

use App\Models\Tenant\Order;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Models\Tenant\TenantUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentService
{
    public static function call(): self
    {
        return app(self::class);
    }

    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    /**
     * 🆕 Единый источник конфигурации для любого банка
     */
    private function getPaymentConfig(string $bankKey): array
    {
        $tenant = app('tenant');
        $config = $tenant->settings['sbp'] ?? [];
        $bankConfig = $config[$bankKey] ?? [];

        return match ($bankKey) {
            'vtb' => [
                'terminal_key' => $bankConfig['terminal_key'] ?? env('VTB_MERCHANT_ID'),
                'terminal_password' => $bankConfig['terminal_password'] ?? env('VTB_API_KEY'),
                'tax' => $bankConfig['tax'] ?? env('VTB_TAXATION', 'osn'),
                'vat' => $bankConfig['vat'] ?? env('VTB_VAT', 'vat20'),
                'url' => $bankConfig['api_url'] ?? 'https://api.vtb.ru/acquiring/v1/',
            ],
            'yandex' => [
                'terminal_key' => $bankConfig['terminal_key'] ?? env('YOOKASSA_SHOP_ID'),
                'terminal_password' => $bankConfig['terminal_password'] ?? env('YOOKASSA_SECRET_KEY'),
                'tax' => $bankConfig['tax'] ?? env('YOOKASSA_TAXATION', 'osn'),
                'vat' => $bankConfig['vat'] ?? env('YOOKASSA_VAT', 'vat20'),
                'url' => $bankConfig['api_url'] ?? 'https://api.yookassa.ru/v3/',
            ],
            'psb' => [
                'terminal_key' => $bankConfig['terminal_key'] ?? env('PSB_MERCHANT_ID'),
                'terminal_password' => $bankConfig['terminal_password'] ?? env('PSB_SECRET_KEY'),
                'tax' => $bankConfig['tax'] ?? env('PSB_TAXATION', 'osn'),
                'vat' => $bankConfig['vat'] ?? env('PSB_VAT', 'vat20'),
                'url' => $bankConfig['api_url'] ?? 'https://pg.bspb.ru/psb-rest-acquiring/v2/',
            ],
            'sber' => [
                'terminal_key' => $bankConfig['terminal_key'] ?? env('SBER_API_LOGIN'),
                'terminal_password' => $bankConfig['terminal_password'] ?? env('SBER_API_PASSWORD'),
                'tax' => $bankConfig['tax'] ?? env('SBER_TAXATION', 'osn'),
                'vat' => $bankConfig['vat'] ?? env('SBER_VAT', 'vat20'),
                'url' => $bankConfig['api_url'] ?? 'https://securepayments.sberbank.ru/api/v2/',
            ],
            default => [ // Т-Банк по умолчанию
                'terminal_key' => $bankConfig['terminal_key'] ?? env('TINKOFF_TERMINAL_KEY'),
                'terminal_password' => $bankConfig['terminal_password'] ?? env('TINKOFF_TERMINAL_PASSWORD'),
                'tax' => $bankConfig['tax'] ?? env('TINKOFF_PAYMENT_TAX', 'osn'),
                'vat' => $bankConfig['vat'] ?? env('TINKOFF_PAYMENT_VAT', 'vat20'),
                'url' => $bankConfig['api_url'] ?? config('sbp.payments.tinkoff.url', 'https://securepay.tinkoff.ru/v2/'),
            ]
        };
    }

    /**
     * 🆕 Получает конфигурацию текущего выбранного банка тенантом
     */
    private function getCurrentBankConfig(): array
    {
        $tenant = app('tenant');
        $selectedBank = $tenant->settings['sbp']['selected_sbp_bank'] ?? 'tinkoff';
        $config = $this->getPaymentConfig($selectedBank);
        $config['bank_key'] = $selectedBank;
        return $config;
    }

    /**
     * 🆕 Фабрика платежных шлюзов (устраняет огромные if/else блоки)
     */
    private function getPaymentGateway(string $bankKey, array $config): object
    {
        $namespace = 'App\\Http\\BusinessLogic\\Methods\\Classes\\Banking\\';

        $className = match ($bankKey) {
            'yandex' => $namespace . 'YookassaService',
            'vtb' => $namespace . 'VtbBankService',
            'psb' => $namespace . 'PsbBankService',
            'sber' => $namespace . 'SberbankService',
            default => $namespace . 'TinkoffBankService',
        };

        return new $className($config['url'], $config['terminal_key'], $config['terminal_password']);
    }

    // ==========================================
    // 🆕 1. ВНУТРЕННИЕ ДИАЛОГИ
    // ==========================================

    private function getOrCreateDialog(TenantUser $user, string $type = 'payment', string $title = 'Оплата заказов'): TenantDialog
    {
        return TenantDialog::firstOrCreate(
            [
                'tenant_id' => $user->tenant_id,
                'tenant_user_id' => $user->id,
                'type' => $type,
            ],
            [
                'title' => $title,
                'is_closed' => false,
                'last_message_at' => now(),
            ]
        );
    }

    private function notifyUser(TenantUser $user, string $message, array $meta = []): void
    {
        $dialog = $this->getOrCreateDialog($user, 'payment', 'Оплата заказов');

        TenantMessage::create([
            'tenant_id' => $user->tenant_id,
            'tenant_user_id' => $user->id,
            'dialog_id' => $dialog->id,
            'message' => $message,
            'meta' => array_merge(['type' => 'text'], $meta),
            'is_read' => false,
        ]);

        $dialog->update(['last_message_at' => now()]);
    }

    // ==========================================
    // 🆕 2. KANBAN CRM ИНТЕГРАЦИЯ
    // ==========================================

    private function getKanbanConfig(): ?array
    {
        $tenant = app('tenant');
        $kanbanConfig = $tenant->settings['kanban'] ?? [];

        if (!($kanbanConfig['enabled'] ?? false)) {
            return null;
        }

        return [
            'enabled' => true,
            'board_uuid' => $kanbanConfig['board_uuid'] ?? null,
            'base_url' => $kanbanConfig['base_url'] ?? config('kanban.base_url'),
            'token' => $kanbanConfig['token'] ?? config('kanban.token'),
            'thread' => $kanbanConfig['order_thread'] ?? 0,
        ];
    }

    private function initKanbanSdk(array $config): void
    {
        try {
            \Exxxar\Kanban\Facades\Kanban::setBaseUrl($config['base_url'])
                ->setToken($config['token'])
                ->setTimeout(30)
                ->setConnectTimeout(10)
                ->setRetryTimes(3)
                ->setRetrySleep(100)
                ->setLoggingEnabled(true);
        } catch (\Throwable $e) {
            Log::error('[KanbanCRM] Ошибка настройки SDK: ' . $e->getMessage());
            throw $e;
        }
    }

    private function findKanbanClientByPhone(string $boardUuid, ?string $phone): ?string
    {
        if (!$phone) return null;

        try {
            $clients = \Exxxar\Kanban\Facades\Kanban::clients()
                ->board($boardUuid)
                ->search(['phone' => $phone])
                ->get();

            return !empty($clients) ? ($clients[0]['task_id'] ?? $clients[0]['id'] ?? null) : null;
        } catch (\Throwable $e) {
            Log::warning('[KanbanCRM] Не удалось найти клиента по телефону: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 🆕 Генерация сообщения для CRM (исправляет ошибку undefined $crmMessage)
     */
    private function buildCrmMessage(Order $order, array $tmpOrderProductInfo, float $summaryPrice, float $deliveryPrice, string $paymentType): string
    {
        $message = "<b>⚠️⚠️⚠️ Сводный заказ ⚠️⚠️⚠️</b>\n";
        $message .= "Номер заказа: <b>#{$order->id}</b>\n";
        $message .= "Способ оплаты: <b>{$paymentType}</b>\n\n";

        foreach ($tmpOrderProductInfo as $product) {
            $name = $product['title'] ?? $product['name'] ?? 'Товар';
            $count = $product['count'] ?? 1;
            $price = $product['price'] ?? 0;
            $message .= "• {$name} x{$count} = {$price} руб.\n";
        }

        $message .= "\nИтого: <b>" . ($summaryPrice + $deliveryPrice) . " руб.</b>\n";
        $message .= "Клиент: <b>{$order->receiver_name}</b>\n";
        $message .= "Телефон: <b>{$order->receiver_phone}</b>\n";
        if ($order->delivery_note) {
            $message .= "Комментарий: {$order->delivery_note}\n";
        }

        return $message;
    }

    private function sendToKanbanCrm(
        Order $order,
        TenantUser $tenantUser,
        array $tmpOrderProductInfo,
        float $summaryPrice,
        float $cashback,
        int $summaryCount,
        float $summaryDiscount,
        float $deliveryPrice,
        float $distance,
        bool $needPickup,
        string $paymentType = 'SBP'
    ): void {
        $kanbanConfig = $this->getKanbanConfig();
        if (!$kanbanConfig || !$kanbanConfig['board_uuid'] || !$kanbanConfig['token']) {
            return;
        }

        $this->initKanbanSdk($kanbanConfig);

        $kanbanBoardUuid = $kanbanConfig['board_uuid'];
        $kanbanThread = $kanbanConfig['thread'];
        $customerName = $order->receiver_name ?? 'Нет имени';
        $customerPhone = $order->receiver_phone ?? null;
        $deliveryNote = $order->delivery_note ?? '';

        $kanbanProductDetails = [
            [
                'from' => app('tenant')->title ?? app('tenant')->name ?? 'Магазин',
                'products' => $tmpOrderProductInfo,
            ]
        ];

        $kanbanCustomData = [
            'tenant_id' => $order->tenant_id,
            'tenant_name' => app('tenant')->name ?? app('tenant')->title,
            'tenant_user_id' => $tenantUser->id,
            'last_order_id' => $order->id,
            'last_order_date' => now()->toIso8601String(),
            'product_details' => $kanbanProductDetails,
            'product_count' => $summaryCount,
            'delivery_price' => $deliveryPrice,
            'delivery_note' => $deliveryNote,
            'payment_type' => $paymentType,
            'summary_price' => (float)($summaryPrice - $cashback),
            'summary_count' => $summaryCount,
        ];

        // ❗ ИСПРАВЛЕНО: Теперь $crmMessage определен
        $crmMessage = $this->buildCrmMessage($order, $tmpOrderProductInfo, $summaryPrice, $deliveryPrice, $paymentType);
        $existingTaskId = $this->findKanbanClientByPhone($kanbanBoardUuid, $customerPhone);

        try {
            if ($existingTaskId) {
                \Exxxar\Kanban\Facades\Kanban::clients()->updateCustomData($existingTaskId, $kanbanCustomData);

                $result = \Exxxar\Kanban\Facades\Kanban::query()
                    ->task($existingTaskId)
                    ->message($crmMessage)
                    ->senderType('system')
                    ->senderLabel('FoodShop Checkout')
                    ->payload(array_merge($kanbanCustomData, [
                        'source' => 'foodshop',
                        'order_id' => $order->id,
                        'customer_name' => $customerName,
                        'customer_phone' => $customerPhone,
                        'type' => 'new_order',
                    ]))
                    ->send();

                $kanbanTaskId = $result['task_id'] ?? $existingTaskId;
                $kanbanMessageId = $result['message_id'] ?? null;
            } else {
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
                        'cost' => (float)($summaryPrice - $cashback),
                        'placement_type' => $needPickup ? 'Самовывоз' : 'Доставка',
                        'address' => $deliveryNote,
                        'custom_data' => $kanbanCustomData,
                    ])
                    ->message($crmMessage)
                    ->senderType('system')
                    ->senderLabel('FoodShop Checkout')
                    ->payload(array_merge($kanbanCustomData, [
                        'source' => 'foodshop',
                        'order_id' => $order->id,
                        'customer_name' => $customerName,
                        'customer_phone' => $customerPhone,
                        'type' => 'new_client_and_order',
                    ]))
                    ->send();

                $kanbanTaskId = $result['task_id'] ?? null;
                $kanbanMessageId = $result['message_id'] ?? null;
            }

            if ($kanbanTaskId) {
                $order->update([
                    'meta' => array_merge($order->meta ?? [], [
                        'kanban_task_id' => $kanbanTaskId,
                        'kanban_message_id' => $kanbanMessageId,
                        'kanban_board_uuid' => $kanbanBoardUuid,
                    ]),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[KanbanCRM] Ошибка отправки: ' . $e->getMessage(), ['order_id' => $order->id]);
        }
    }

    // ==========================================
    // 🆕 3. ОСНОВНАЯ ЛОГИКА ОПЛАТЫ
    // ==========================================

    public function sbpNotificationProductsPayment(array $data): string
    {
        $orderId = $data['OrderId'] ?? null;
        $amount = isset($data['Amount']) && is_numeric($data['Amount']) ? $data['Amount'] / 100 : 0;
        $status = $data['Status'] ?? '';
        $customerKey = $data['CustomerKey'] ?? null;
        $rebillId = $data['RebillId'] ?? null;

        if (!$orderId) return "OK";

        $order = Order::query()->where("id", $orderId)->first();
        if (is_null($order)) {
            Log::warning("Payment webhook: Order #$orderId not found.");
            return "OK";
        }

        if (!isset($data['Success']) || $status !== 'CONFIRMED') {
            Log::info("Payment status for order #$orderId: " . print_r($data, true));

            if (in_array($status, ['REFUNDED', 'REJECTED'])) {
                if ($customerKey) {
                    $user = TenantUser::query()->where("id", $customerKey)->first();
                    if ($user) {
                        $this->notifyUser($user, "⛔ Оплата по заказу #$orderId в размере $amount руб. НЕ прошла или была отменена.");
                    }
                }
            }
            return "OK";
        }

        // Успешная оплата
        $order->payed_at = Carbon::now();
        $order->status = \App\Enums\OrderStatusEnum::Completed->value ?? 'completed';
        $order->save();

        $user = TenantUser::query()->find($order->tenant_user_id);
        if ($user) {
            if ($rebillId) {
                $meta = $user->meta ?? [];
                $meta['rebill_id'] = $rebillId;
                $user->meta = $meta;
                $user->save();
            }

            $this->notifyUser($user, "✅ Ваша оплата в размере $amount руб. по заказу №$orderId прошла успешно! Заказ принят в работу.", ['type' => 'success']);
        }

        // Отправка в CRM
        $productDetails = $order->product_details ?? [];
        $tmpOrderProductInfo = [];
        foreach ($productDetails as $detail) {
            if (isset($detail['products']) && is_array($detail['products'])) {
                $tmpOrderProductInfo = array_merge($tmpOrderProductInfo, $detail['products']);
            }
        }

        $this->sendToKanbanCrm(
            $order,
            $user,
            $tmpOrderProductInfo,
            $order->summary_price,
            0,
            $order->product_count,
            0,
            $order->delivery_price ?? 0,
            0,
            false,
            'SBP_WEBHOOK'
        );

        return "OK";
    }

    /**
     * Формирует тестовую ссылку на оплату (100 руб) с переданными настройками
     */
    public function testSbpPayment(array $bankConfig, string $bankKey): array
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        if (!$tenant || !$tenantUser) {
            throw new HttpException(404, "Пользователь или тенант не найдены!");
        }

        // 🆕 Используем фабрику вместо дублирующегося кода
        $config = $this->getPaymentConfig($bankKey);
        $paymentGateway = $this->getPaymentGateway($bankKey, $config);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $tenantUser->id,
            'product_count' => 1,
            'summary_price' => 100.00,
            'delivery_price' => 0,
            'delivery_note' => "ТЕСТ: Проверка настроек СБП ($bankKey)",
            'receiver_name' => $tenantUser->name ?? 'Тестовый клиент',
            'receiver_phone' => $tenantUser->phone ?? '+79990000000',
            'status' => \App\Enums\OrderStatusEnum::NewOrder->value,
            'order_type' => \App\Enums\OrderTypeEnum::InternalStore->value,
        ]);

        $items = [[
            'Name' => "Тестовая оплата (100 руб)",
            'Quantity' => 1,
            'Price' => 100.00,
            'NDS' => $config['vat'],
        ]];

        $payment = [
            'OrderId' => $order->id,
            'Amount' => 100.00,
            'Language' => 'ru',
            'Description' => "Тестовая проверка интеграции ({$bankKey})",
            'Email' => $tenantUser->email ?? 'test@test.com',
            'Phone' => $order->receiver_phone,
            'Name' => $order->receiver_name,
            'Taxation' => $config['tax'],
            'CustomerKey' => $tenantUser->id,
            'ReturnUrl' => route('home'),
        ];

        $paymentURL = $paymentGateway->paymentURL($payment, $items);

        if (!$paymentURL) {
            $order->delete();
            throw new HttpException(400, "Ошибка банка: " . ($paymentGateway->getError() ?: 'Неверные ключи'));
        }

        $payment_id = $paymentGateway->payment_id ?? Str::uuid()->toString();

        if (class_exists(\App\Services\TransactionService::class)) {
            \App\Services\TransactionService::call()->createPending(
                tenantId: $tenant->id,
                tenantUserId: $tenantUser->id,
                orderId: $order->id,
                externalPaymentId: $payment_id,
                amount: 100.00,
                metaData: [
                    'is_test' => true,
                    'bank_key' => $bankKey,
                    'terminal_key_masked' => substr($config['terminal_key'], 0, 4) . '***'
                ],
                provider: $bankKey
            );
        }

        return [
            'url' => $paymentURL,
            'order_id' => $order->id,
            'message' => "Тестовая ссылка успешно сформирована для заказа #$order->id"
        ];
    }

    public function invoiceServiceLink(array $data): object
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        if (!$tenant || !$tenantUser) {
            throw new HttpException(404, "Пользователь или тенант не найдены!");
        }

        // 🆕 Теперь использует текущий выбранный банк тенанта, а не хардкод Т-Банка
        $config = $this->getCurrentBankConfig();
        $paymentGateway = $this->getPaymentGateway($config['bank_key'], $config);

        $items = [[
            'Name' => "Оплата услуг сервиса",
            'Quantity' => 1,
            'Price' => $data["amount"],
            'NDS' => $config['vat'],
        ]];

        $payment = [
            'OrderId' => $data["order_id"] ?? Str::uuid(),
            'Amount' => $data["amount"],
            'Language' => 'ru',
            'Description' => "Оплата услуг сервиса",
            'Email' => $tenantUser->email ?? '',
            'Phone' => $tenantUser->phone ?? '',
            'Name' => $tenantUser->name ?? 'Клиент',
            'Taxation' => $config['tax'],
            'CustomerKey' => $tenantUser->id,
            'ReturnUrl' => route('home'),
        ];

        $paymentURL = $paymentGateway->paymentURL($payment, $items);

        if (!$paymentURL) {
            throw new HttpException(400, "Ошибка формирования ссылки: " . $paymentGateway->getError());
        }

        return (object)["url" => $paymentURL];
    }

    public function sbpTablePayment(array $data, object $table): string
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        if (!$tenant || !$tenantUser) {
            throw new HttpException(404, "Пользователь или тенант не найдены!");
        }

        $client = isset($data["client"]) ? json_decode($data["client"]) : null;
        if (json_last_error() !== JSON_ERROR_NONE || is_null($client)) {
            throw new HttpException(400, "Ошибка в JSON данных клиента");
        }

        $basketQuery = \App\Models\Tenant\Basket::query()
            ->where("tenant_id", $tenant->id)
            ->where("table_id", $table->id)
            ->whereNull("ordered_at");

        if (($data["is_self"] ?? "false") === "true") {
            $basketQuery->where("tenant_user_id", $tenantUser->id);
        }

        $basket = $basketQuery->get();
        if ($basket->isEmpty()) {
            throw new HttpException(400, "Корзина пуста");
        }

        $tmpOrderProductInfo = [];
        $summaryPrice = 0;
        $summaryCount = 0;

        foreach ($basket as $basketItem) {
            $product = $basketItem->product ?? null;
            $count = $basketItem->count ?? 0;
            $price = $product ? ($product->price ?? 0) : 0;

            if ($product) {
                $tmpOrderProductInfo[] = [
                    "title" => $product->title,
                    "count" => $count,
                    "price" => $price,
                ];
            }
            $summaryCount += $count;
            $summaryPrice += ($price * $count);
        }

        $priceWithDiscount = max(0, $summaryPrice);

        // 🆕 Динамический шлюз
        $config = $this->getCurrentBankConfig();
        $paymentGateway = $this->getPaymentGateway($config['bank_key'], $config);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $tenantUser->id,
            'product_details' => [[
                "data" => $data,
                "from" => $tenant->name ?? 'Tenant',
                "products" => $tmpOrderProductInfo,
            ]],
            'product_count' => $summaryCount,
            'summary_price' => $priceWithDiscount,
            'receiver_name' => $client->name ?? 'Нет имени',
            'receiver_phone' => $client->phone ?? 'Нет телефона',
            'table_id' => $table->id,
            'status' => \App\Enums\OrderStatusEnum::NewOrder->value,
            'order_type' => \App\Enums\OrderTypeEnum::InternalStore->value,
        ]);

        $items = [[
            'Name' => "Оплата столика №{$table->number}",
            'Quantity' => 1,
            'Price' => $priceWithDiscount,
            'NDS' => $config['vat'],
        ]];

        $payment = [
            'OrderId' => $order->id,
            'Amount' => $priceWithDiscount,
            'Language' => 'ru',
            'Description' => "Оплата за обслуживание столика №{$table->number}",
            'Email' => $tenantUser->email ?? '',
            'Phone' => $order->receiver_phone,
            'Name' => $order->receiver_name,
            'Taxation' => $config['tax'],
            'CustomerKey' => $tenantUser->id,
            'ReturnUrl' => route('home'),
        ];

        $paymentURL = $paymentGateway->paymentURL($payment, $items);
        if (!$paymentURL) {
            throw new HttpException(400, "Ошибка формирования ссылки: " . $paymentGateway->getError());
        }

        $payment_id = $paymentGateway->payment_id ?? Str::uuid()->toString();

        if (class_exists(\App\Services\TransactionService::class)) {
            \App\Services\TransactionService::call()->createPending(
                tenantId: $tenant->id,
                tenantUserId: $tenantUser->id,
                orderId: $order->id,
                externalPaymentId: $payment_id,
                amount: $priceWithDiscount,
                metaData: [
                    "order_id" => $order->id,
                    "table_id" => $table->id,
                    "products_info" => $tmpOrderProductInfo,
                    "terminal_key" => substr($config['terminal_key'], 0, 4) . '***',
                ],
                provider: $config['bank_key']
            );
        }

        $this->notifyUser($tenantUser, "💳 Для оплаты столика №{$table->number} перейдите по ссылке:\n<code>$paymentURL</code>\n\nСумма к оплате: <b>$priceWithDiscount руб.</b>\nЗаказ №$order->id принят в работу.", ['type' => 'payment_link', 'url' => $paymentURL]);

        $this->sendToKanbanCrm(
            $order,
            $tenantUser,
            $tmpOrderProductInfo,
            $summaryPrice,
            0,
            $summaryCount,
            0,
            0,
            0,
            true,
            $config['bank_key']
        );

        return $paymentURL;
    }

    public function createInvoiceLink(array $data): object
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        if (!$tenant || !$tenantUser) {
            throw new HttpException(404, "Пользователь или тенант не найдены!");
        }

        // 🆕 Динамический шлюз
        $config = $this->getCurrentBankConfig();
        $paymentGateway = $this->getPaymentGateway($config['bank_key'], $config);

        $isRecurrent = ($data["is_recurrent"] ?? false) == "true";
        $amount = (float)($data["amount"] ?? 0);

        if ($amount <= 0) {
            throw new HttpException(400, "Сумма оплаты должна быть больше 0");
        }

        $items[] = [
            'Name' => $data["description"] ?? "Оплата услуг",
            'Quantity' => 1,
            'Price' => $amount,
            'NDS' => $config['vat'],
        ];

        $orderId = $data["order_id"] ?? null;
        if (!$orderId) {
            $order = Order::query()->create([
                'tenant_id' => $tenant->id,
                'tenant_user_id' => $tenantUser->id,
                'product_count' => 1,
                'summary_price' => $amount,
                'delivery_note' => $data["description"] ?? '',
                'receiver_name' => $data["name"] ?? $tenantUser->name ?? 'Клиент',
                'receiver_phone' => $data["phone"] ?? $tenantUser->phone ?? '',
                'status' => \App\Enums\OrderStatusEnum::NewOrder->value,
            ]);
            $orderId = $order->id;
        } else {
            $order = Order::query()->findOrFail($orderId);
        }

        $payment = [
            'OrderId' => $orderId,
            'Amount' => $amount,
            'Language' => 'ru',
            'Description' => $data["description"] ?? "Оплата услуг сервиса",
            'Email' => $data["email"] ?? $tenantUser->email ?? '',
            'Phone' => $data["phone"] ?? $tenantUser->phone ?? '',
            'Name' => $data["name"] ?? $tenantUser->name ?? 'Клиент',
            'Taxation' => $config['tax'],
            'CustomerKey' => $tenantUser->id,
            'ReturnUrl' => route('home'),
        ];

        if ($isRecurrent) {
            $payment["Recurrent"] = 'Y';
        }

        $paymentURL = $paymentGateway->paymentURL($payment, $items);

        if (!$paymentURL) {
            throw new HttpException(400, "Ошибка формирования ссылки: " . $paymentGateway->getError());
        }

        $this->notifyUser($tenantUser, "💳 Ссылка на оплату сформирована:\n<code>$paymentURL</code>\n\nСумма: <b>$amount руб.</b>\nОписание: {$data['description']}", ['type' => 'payment_link', 'url' => $paymentURL]);

        return (object)[
            "url" => $paymentURL,
            "order_id" => $orderId
        ];
    }
}
