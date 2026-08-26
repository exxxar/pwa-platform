<?php

declare(strict_types=1);

namespace App\Services\Tenants\Banking;

class YookassaService
{
    private string $apiUrl;
    private string $shopId;
    private string $secretKey;

    protected string $error = '';
    protected string $response = '';

    protected ?string $paymentId = null; // В ЮKassa это UUID, а не числовой ID
    protected ?string $paymentUrl = null;
    protected ?string $status = null;

    public function __construct(string $apiUrl, string $shopId, string $secretKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        $this->shopId = $shopId;
        $this->secretKey = $secretKey;
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
        // ❗ ЮKassa принимает сумму в рублях (строка "100.00"), а не в копейках!
        $amount = number_format((float)$payment['Amount'], 2, '.', '');
        $orderId = (string)$payment['OrderId'];
        $description = $payment['Description'] ?? 'Оплата заказа';
        $returnUrl = $payment['ReturnUrl'] ?? config('app.url');

        // Формируем тело запроса по спецификации ЮKassa v3
        $body = [
            'amount' => [
                'value' => $amount,
                'currency' => 'RUB',
            ],
            'capture' => true, // Сразу списываем деньги (не двухстадийная оплата)
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $returnUrl,
            ],
            'description' => mb_strimwidth($description, 0, 128, ''),
            'metadata' => [
                'order_id' => $orderId,
                'customer_key' => $payment['CustomerKey'] ?? '',
            ],
        ];

        // Добавляем email/phone для отправки чека
        $receiptItems = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                $price = number_format((float)$item['Price'], 2, '.', '');
                $quantity = (float)($item['Quantity'] ?? 1);

                $receiptItems[] = [
                    'description' => mb_strimwidth((string)$item['Name'], 0, 128, ''),
                    'quantity' => number_format($quantity, 2, '.', ''),
                    'amount' => [
                        'value' => $price,
                        'currency' => 'RUB',
                    ],
                    'vat_code' => $this->mapVatCode($item['NDS'] ?? $item['Tax'] ?? 'none'),
                    'payment_mode' => 'full_payment',
                    'payment_subject' => 'commodity',
                ];
            }

            $body['receipt'] = [
                'customer' => [
                    'email' => $payment['Email'] ?? '',
                    'phone' => $payment['Phone'] ?? '',
                ],
                'tax_system_code' => $this->mapTaxSystemCode($payment['Taxation'] ?? 'osn'),
                'items' => $receiptItems,
            ];
        }

        return $this->sendRequest('payments', $body);
    }

    /**
     * Проверка статуса платежа
     */
    public function getState(string $paymentId): string|false
    {
        return $this->sendRequest("payments/{$paymentId}", [], 'GET') ? $this->status : false;
    }

    /**
     * Маппинг НДС для ЮKassa (vat_code - числовой)
     */
    private function mapVatCode(string $tax): int
    {
        return match($tax) {
            'none' => 1,        // Без НДС
            'vat0' => 3,        // НДС 0%
            'vat10' => 4,       // НДС 10%
            'vat20' => 2,       // НДС 20%
            default => 1,
        };
    }

    /**
     * Маппинг системы налогообложения (числовой код)
     */
    private function mapTaxSystemCode(string $taxation): int
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
     * Отправка запроса к API ЮKassa
     */
    private function sendRequest(string $endpoint, array $body, string $method = 'POST'): string|false
    {
        $url = $this->apiUrl . $endpoint;
        $payload = !empty($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : null;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                // ❗ HTTP Basic Auth
                'Authorization: Basic ' . base64_encode("{$this->shopId}:{$this->secretKey}"),
                // ❗ Обязательный заголовок идемпотентности (UUID v4)
                'Idempotence-Key: ' . $this->generateUuidV4(),
            ],
            CURLOPT_SSL_VERIFYPEER => false, // В продакшене: true + CURLOPT_CAINFO
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($payload !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        $this->response = (string)$response;

        if ($curlError) {
            $this->error = "CURL Error: {$curlError}";
            return false;
        }

        $json = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = "Invalid JSON response (HTTP {$httpCode}): {$response}";
            return false;
        }

        // ЮKassa возвращает ошибки в поле 'error' или 'description'
        if (isset($json['type']) && $json['type'] === 'error') {
            $this->error = "YooKassa Error [{$json['code']}]: " . ($json['description'] ?? 'Unknown error');
            return false;
        }

        if ($httpCode >= 400) {
            $this->error = "HTTP Error {$httpCode}: " . ($json['description'] ?? $response);
            return false;
        }

        $this->paymentId = (string)($json['id'] ?? '');
        // URL для редиректа клиента на оплату
        $this->paymentUrl = (string)($json['confirmation']['confirmation_url'] ?? '');
        $this->status = (string)($json['status'] ?? '');

        return $this->paymentUrl ?: false;
    }

    /**
     * Генерация UUID v4 для Idempotence-Key
     */
    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
