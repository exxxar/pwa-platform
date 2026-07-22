<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\Order;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class PaymentCallbackController extends Controller
{
    /**
     * 🆕 Универсальный обработчик callback'ов для всех банков
     *
     * @param Request $request
     * @param string $bank Ключ банка (tinkoff, sber, psb, vtb, yandex)
     * @param string $tenantSlug Slug или ID тенанта
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function handleProductsCallback(Request $request, string $bank, string $tenantSlug)
    {
        // Валидация ключа банка
        $allowedBanks = ['tinkoff', 'sber', 'psb', 'vtb', 'yandex'];

        if (!in_array($bank, $allowedBanks)) {
            Log::error("[PaymentCallback] Unknown bank: {$bank}");
            return response()->json(['message' => 'Unknown payment provider'], 400);
        }

        // Находим тенант
        $tenant = Tenant::query()
            ->where('slug', $tenantSlug)
            ->orWhere('id', $tenantSlug)
            ->first();

        if (!$tenant) {
            Log::error("[PaymentCallback] Tenant not found: {$tenantSlug}");
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        // Устанавливаем текущий тенант в контейнер
        app()->instance('tenant', $tenant);

        Log::info("[PaymentCallback] {$bank} webhook received", [
            'tenant_id' => $tenant->id,
            'bank' => $bank,
            'data' => $request->all()
        ]);

        // Вызываем соответствующий метод обработки в зависимости от банка
        return match ($bank) {
            'tinkoff' => $this->tinkoffProductsCallback($request, $tenantSlug),
            'sber' => $this->sberProductsCallback($request, $tenantSlug),
            'psb' => $this->psbProductsCallback($request, $tenantSlug),
            'vtb' => $this->vtbProductsCallback($request, $tenantSlug),
            'yandex' => $this->yookassaProductsCallback($request, $tenantSlug),
            default => response()->json(['message' => 'Unsupported bank'], 400),
        };
    }

    /**
     * Callback для оплаты товаров/заказов от Т-Банка
     */
    public function tinkoffProductsCallback(Request $request, string $tenantSlug)
    {
        try {
            $result = PaymentService::call()->sbpNotificationProductsPayment($request->all());
            return $result;
        } catch (\Throwable $e) {
            Log::error("[TinkoffCallback] Error", [
                'tenant_slug' => $tenantSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error processing payment'], 500);
        }
    }

    /**
     * Callback для оплаты товаров от Сбера
     */
    public function sberProductsCallback(Request $request, string $tenantSlug)
    {
        try {
            $tenant = app('tenant');
            $data = $request->all();

            $orderId = $data['orderNumber'] ?? null;
            $status = (int)($data['orderStatus'] ?? 0);
            $amount = isset($data['amount']) ? (float)$data['amount'] / 100 : 0;

            if (!$orderId) {
                return response()->json(['message' => 'Missing orderNumber'], 400);
            }

            $order = Order::query()->where('id', $orderId)->first();
            if (!$order) return response()->json(['message' => 'Order not found'], 404);

            // Статус 2 = Успешно оплачен
            if ($status === 2) {
                $order->payed_at = Carbon::now();
                $order->status = \App\Enums\OrderStatusEnum::Completed->value;
                $order->save();

                if (class_exists(TransactionService::class)) {
                    TransactionService::call()->markAsSuccessful(
                        externalPaymentId: (string)$orderId,
                        tenantId: $tenant->id,
                        webhookData: $data
                    );
                }

                Log::info("[SberCallback] Order #{$orderId} paid successfully");
                return response()->json(['message' => 'OK'], 200);
            }

            return response()->json(['message' => 'Status ignored'], 200);

        } catch (\Throwable $e) {
            Log::error("[SberCallback] Error", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error'], 500);
        }
    }

    /**
     * Callback для оплаты товаров от Промсвязьбанка (ПСБ)
     */
    public function psbProductsCallback(Request $request, string $tenantSlug)
    {
        try {
            $tenant = app('tenant');
            $data = $request->all();

            $orderId = $data['order_id'] ?? $data['OrderId'] ?? null;
            $status = strtoupper($data['status'] ?? '');
            $amount = isset($data['amount']) ? (float)$data['amount'] / 100 : 0;
            $signature = $data['signature'] ?? '';

            if (!$orderId) {
                return response()->json(['message' => 'Missing order_id'], 400);
            }

            $order = Order::query()->where('id', $orderId)->first();
            if (!$order) return response()->json(['message' => 'Order not found'], 404);

            // Проверка подписи от ПСБ
            $config = $tenant->settings['sbp']['psb'] ?? [];
            $secretKey = $config['terminal_password'] ?? env('PSB_SECRET_KEY');
            $expectedSignature = md5($orderId . $status . $amount . $secretKey);

            if ($signature !== $expectedSignature) {
                Log::error("[PsbCallback] Invalid signature");
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            if ($status === 'SUCCESS') {
                $order->payed_at = Carbon::now();
                $order->status = \App\Enums\OrderStatusEnum::Completed->value;
                $order->save();

                if (class_exists(TransactionService::class)) {
                    TransactionService::call()->markAsSuccessful(
                        externalPaymentId: (string)$orderId,
                        tenantId: $tenant->id,
                        webhookData: $data
                    );
                }

                Log::info("[PsbCallback] Order #{$orderId} paid successfully");
                return response()->json(['status' => 'OK'], 200);
            }

            return response()->json(['status' => 'IGNORED'], 200);

        } catch (\Throwable $e) {
            Log::error("[PsbCallback] Error", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error'], 500);
        }
    }

    /**
     * Callback для оплаты товаров от ВТБ Банка
     */
    public function vtbProductsCallback(Request $request, string $tenantSlug)
    {
        try {
            $tenant = app('tenant');
            $data = $request->all();

            $orderId = $data['order_id'] ?? $data['OrderId'] ?? null;
            $status = strtoupper($data['status'] ?? '');
            $amount = isset($data['amount']) ? (float)$data['amount'] / 100 : 0;
            $signature = $data['signature'] ?? '';

            if (!$orderId) {
                return response()->json(['message' => 'Missing order_id'], 400);
            }

            $order = Order::query()->where('id', $orderId)->first();
            if (!$order) return response()->json(['message' => 'Order not found'], 404);

            // Проверка подписи от ВТБ
            $config = $tenant->settings['sbp']['vtb'] ?? [];
            $apiKey = $config['terminal_password'] ?? env('VTB_API_KEY');
            $expectedSignature = hash('sha256', $orderId . $status . $amount . $apiKey);

            if ($signature !== $expectedSignature) {
                Log::error("[VtbCallback] Invalid signature");
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            if ($status === 'AUTHORIZED') {
                $order->payed_at = Carbon::now();
                $order->status = \App\Enums\OrderStatusEnum::Completed->value;
                $order->save();

                if (class_exists(TransactionService::class)) {
                    TransactionService::call()->markAsSuccessful(
                        externalPaymentId: (string)$orderId,
                        tenantId: $tenant->id,
                        webhookData: $data
                    );
                }

                Log::info("[VtbCallback] Order #{$orderId} paid successfully");
                return response()->json(['status' => 'OK'], 200);
            }

            if (in_array($status, ['DECLINED', 'REVERSED', 'REFUNDED'])) {
                if (class_exists(TransactionService::class)) {
                    TransactionService::call()->markAsFailed(
                        externalPaymentId: (string)$orderId,
                        tenantId: $tenant->id,
                        reason: $status
                    );
                }
            }

            return response()->json(['status' => 'IGNORED'], 200);

        } catch (\Throwable $e) {
            Log::error("[VtbCallback] Error", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error'], 500);
        }
    }

    /**
     * Callback для оплаты товаров от ЮKassa (Яндекс.Деньги)
     */
    public function yookassaProductsCallback(Request $request, string $tenantSlug)
    {
        try {
            // Проверка IP-адреса (защита от поддельных вебхуков)
            $allowedIps = [
                '185.71.76.0/27',
                '185.71.88.0/27',
                '77.75.153.0/25',
                '77.75.154.128/25',
                '77.75.156.11',
                '77.75.156.35',
            ];

            $clientIp = $request->ip();
            if (!$this->isIpAllowed($clientIp, $allowedIps)) {
                Log::error("[YookassaCallback] Request from unauthorized IP: {$clientIp}");
                return response()->json(['message' => 'Unauthorized IP'], 403);
            }

            $tenant = app('tenant');
            $data = $request->all();

            $event = $data['event'] ?? '';
            $paymentData = $data['object'] ?? $data;

            $orderId = $paymentData['metadata']['order_id'] ?? null;
            $paymentId = $paymentData['id'] ?? null;
            $status = $paymentData['status'] ?? '';
            $amount = isset($paymentData['amount']['value']) ? (float)$paymentData['amount']['value'] : 0;

            if (!$orderId) {
                return response()->json(['message' => 'Missing order_id in metadata'], 400);
            }

            $order = Order::query()->where('id', $orderId)->first();
            if (!$order) return response()->json(['message' => 'Order not found'], 404);

            if ($event === 'payment.succeeded' || $status === 'succeeded') {
                $order->payed_at = Carbon::now();
                $order->status = \App\Enums\OrderStatusEnum::Completed->value;
                $order->save();

                if (class_exists(TransactionService::class)) {
                    TransactionService::call()->markAsSuccessful(
                        externalPaymentId: (string)$paymentId,
                        tenantId: $tenant->id,
                        webhookData: $data
                    );
                }

                Log::info("[YookassaCallback] Order #{$orderId} paid successfully", [
                    'payment_id' => $paymentId,
                    'amount' => $amount
                ]);

                return response()->json(['status' => 'OK'], 200);
            }

            if ($event === 'payment.canceled' || $status === 'canceled') {
                if (class_exists(TransactionService::class)) {
                    TransactionService::call()->markAsFailed(
                        externalPaymentId: (string)$paymentId,
                        tenantId: $tenant->id,
                        reason: $paymentData['cancellation_details']['reason'] ?? 'canceled'
                    );
                }
            }

            return response()->json(['status' => 'OK'], 200);

        } catch (\Throwable $e) {
            Log::error("[YookassaCallback] Error", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error'], 500);
        }
    }

    /**
     * Callback для оплаты услуг сервиса (пополнение баланса менеджера) - Т-Банк
     */
    public function tinkoffServiceCallback(Request $request)
    {
        try {
            $data = $request->all();

            Log::info("[TinkoffServiceCallback] Webhook received", ['data' => $data]);

            if (!isset($data['Success']) || $data['Success'] !== true ||
                !isset($data['Status']) || $data['Status'] !== 'CONFIRMED') {
                return response()->json(['message' => 'Payment not confirmed'], 400);
            }

            $requiredFields = ['OrderId', 'PaymentId', 'Amount', 'CustomerKey'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return response()->json(['message' => "Missing required field: {$field}"], 400);
                }
            }

            $amount = (float)$data['Amount'] / 100;
            $customerKey = $data['CustomerKey'];

            if ($amount <= 0) {
                return response()->json(['message' => 'Invalid payment amount'], 400);
            }

            $tenantUser = TenantUser::query()->where('id', $customerKey)->first();
            if (!$tenantUser) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $tenantUser->balance = ($tenantUser->balance ?? 0) + $amount;
            $this->applyTariff($tenantUser, $amount);
            $tenantUser->save();

            Log::info("[TinkoffServiceCallback] Balance updated", [
                'user_id' => $tenantUser->id,
                'amount' => $amount,
                'new_balance' => $tenantUser->balance
            ]);

            return response()->json(['message' => 'OK'], 200);

        } catch (\Throwable $e) {
            Log::error("[TinkoffServiceCallback] Error", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error processing payment'], 500);
        }
    }

    /**
     * Применение тарифа при пополнении баланса
     */
    private function applyTariff(TenantUser $user, float $amount): void
    {
        try {
            $tariffsFile = base_path('config/tariffs.php');

            if (!File::exists($tariffsFile)) {
                return;
            }

            $tariffsData = include $tariffsFile;

            if (!isset($tariffsData['tariffs']) || !is_array($tariffsData['tariffs'])) {
                return;
            }

            $tariffs = $tariffsData['tariffs'];
            usort($tariffs, fn($a, $b) => $a['price'] <=> $b['price']);

            $selectedTariff = $tariffs[0];
            foreach ($tariffs as $tariff) {
                if ($amount >= $tariff['price']) {
                    $selectedTariff = $tariff;
                } else {
                    break;
                }
            }

            $user->max_company_slot_count = ($user->max_company_slot_count ?? 0) + ($selectedTariff['slots'] ?? 0);
            $user->max_bot_slot_count = ($user->max_bot_slot_count ?? 0) + ($selectedTariff['slots'] ?? 0);
            $user->permanent_personal_discount = max(
                $selectedTariff['discount'] ?? 0,
                $user->permanent_personal_discount ?? 0
            );

        } catch (\Throwable $e) {
            Log::error("[ApplyTariff] Error", ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Проверка IP-адреса по белому списку (с поддержкой CIDR)
     */
    private function isIpAllowed(string $ip, array $allowedIps): bool
    {
        foreach ($allowedIps as $cidr) {
            if (strpos($cidr, '/') === false) {
                if ($ip === $cidr) return true;
                continue;
            }

            list($subnet, $mask) = explode('/', $cidr);

            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet)) {
                    return true;
                }
            }
        }
        return false;
    }
}
