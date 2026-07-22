<?php

namespace App\Services\Banking;

class VtbBankService
{
    private string $apiUrl;
    private string $merchantId;
    private string $apiKey;

    protected string $error = '';
    protected string $response = '';

    protected ?string $orderId = null;
    protected ?string $paymentUrl = null;
    protected ?string $status = null;

    public function __construct(string $apiUrl, string $merchantId, string $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        $this->merchantId = $merchantId;
        $this->apiKey = $apiKey;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getResponse(): ?object
    {
        return json_decode($this->response ?: '[]');
    }

    /**
     * Генерация ссылки на оплату
     */
    public function paymentURL(array $payment, array $items): string|false
    {
        $amountInKopecks = (int)round((float)$payment['Amount'] * 100);
        $orderId = (string)$payment['OrderId'];
        $description = $payment['Description'] ?? 'Оплата заказа';
        $returnUrl = $payment['ReturnUrl'] ?? config('app.url');

        // Формируем параметры для подписи
        // ВТБ обычно требует: merchant_id + order_id + amount + return_url + api_key
        $signatureString = $this->merchantId . $orderId . $amountInKopecks . $returnUrl . $this->apiKey;
        $signature = hash('sha256', $signatureString);

        $params = [
            'merchant_id' => $this->merchantId,
            'order_id' => $orderId,
            'amount' => $amountInKopecks,
            'currency' => 'RUB',
            'description' => $description,
            'language' => $payment['Language'] ?? 'ru',
            'signature' => $signature,
            'return_url' => $returnUrl,
            'email' => $payment['Email'] ?? '',
            'phone' => $payment['Phone'] ?? '',
        ];

        // Добавляем данные для чека (54-ФЗ)
        if (!empty($items)) {
            $params['receipt'] = $this->buildVtbReceipt($payment, $items);
        }

        return $this->sendRequest('payment/create', $params);
    }

    /**
     * Проверка статуса заказа
     */
    public function getState(string $orderId): string|false
    {
        $signatureString = $this->merchantId . $orderId . $this->apiKey;
        $params = [
            'merchant_id' => $this->merchantId,
            'order_id' => $orderId,
            'signature' => hash('sha256', $signatureString),
        ];

        return $this->sendRequest('payment/status', $params) ? $this->status : false;
    }

    /**
     * Формирование структуры чека (54-ФЗ)
     */
    private function buildVtbReceipt(array $payment, array $items): array
    {
        $vtbItems = [];
        foreach ($items as $item) {
            $price = (int)round((float)$item['Price'] * 100);
            $quantity = (float)($item['Quantity'] ?? 1);
            $tax = $item['NDS'] ?? $item['Tax'] ?? 'none';

            $vtbItems[] = [
                'name' => mb_strimwidth((string)$item['Name'], 0, 128, ''),
                'quantity' => number_format($quantity, 2, '.', ''),
                'price' => $price,
                'amount' => (int)round($price * $quantity),
                'payment_method' => 'full_payment',
                'payment_object' => 'commodity',
                'vat_type' => $this->mapVatType($tax),
            ];
        }

        return [
            'tax_system' => $this->mapTaxSystem($payment['Taxation'] ?? 'osn'),
            'items' => $vtbItems,
            'email' => $payment['Email'] ?? '',
            'phone' => $payment['Phone'] ?? '',
        ];
    }

    /**
     * Маппинг типов НДС для ВТБ
     */
    private function mapVatType(string $tax): string
    {
        return match($tax) {
            'none' => 'none',
            'vat0' => 'vat_0',
            'vat10' => 'vat_10',
            'vat20' => 'vat_20',
            default => 'none',
        };
    }

    /**
     * Маппинг систем налогообложения для ВТБ
     */
    private function mapTaxSystem(string $taxation): int
    {
        return match($taxation) {
            'osn' => 1,
            'usn_income' => 2,
            'usn_income_outcome' => 3,
            'envd' => 4,
            'esn' => 5,
            'patent' => 6,
            default => 1,
        };
    }

    /**
     * Отправка запроса к API ВТБ
     */
    private function sendRequest(string $endpoint, array $params): string|false
    {
        $url = $this->apiUrl . $endpoint;
        $payload = json_encode($params, JSON_UNESCAPED_UNICODE);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-Merchant-Id: ' . $this->merchantId,
            ],
            CURLOPT_SSL_VERIFYPEER => false, // В продакшене: true + CURLOPT_CAINFO
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        curl_close($curl);

        $this->response = (string)$response;

        if ($curlError) {
            $this->error = "CURL Error: {$curlError}";
            return false;
        }

        $json = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = "Invalid JSON response: {$response}";
            return false;
        }

        // ВТБ возвращает 'status': 'success' или 'error_code': 0
        if (isset($json['status']) && strtolower($json['status']) !== 'success') {
            $this->error = "VTB Error: " . ($json['error_message'] ?? $json['message'] ?? 'Unknown error');
            return false;
        }

        if (isset($json['error_code']) && (int)$json['error_code'] !== 0) {
            $this->error = "VTB Error [{$json['error_code']}]: " . ($json['error_message'] ?? 'Unknown error');
            return false;
        }

        $this->orderId = (string)($json['order_id'] ?? $params['order_id'] ?? '');
        $this->paymentUrl = (string)($json['payment_url'] ?? $json['form_url'] ?? $json['redirect_url'] ?? '');
        $this->status = (string)($json['status'] ?? $json['order_status'] ?? '');

        return $this->paymentUrl ?: false;
    }
}
