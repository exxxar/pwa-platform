<?php

declare(strict_types=1);

namespace App\Services\Tenants\Banking;

class PsbBankService
{
    private string $apiUrl;
    private string $merchantId;
    private string $secretKey;

    protected string $error = '';
    protected string $response = '';

    protected ?string $orderId = null;
    protected ?string $paymentUrl = null;
    protected ?string $status = null;

    public function __construct(string $apiUrl, string $merchantId, string $secretKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        $this->merchantId = $merchantId;
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
        $amountInKopecks = (int)round((float)$payment['Amount'] * 100);
        $orderId = (string)$payment['OrderId'];
        $description = $payment['Description'] ?? 'Оплата заказа';

        // Формируем базовые параметры для подписи
        // ВНИМАНИЕ: Порядок конкатенации может отличаться в зависимости от версии API ПСБ.
        // Стандартный: merchant_id + order_id + amount + description + secret_key
        $signatureString = $this->merchantId . $orderId . $amountInKopecks . $description . $this->secretKey;
        $signature = md5($signatureString); // Если ПСБ требует SHA256, замените на hash('sha256', $signatureString)

        $params = [
            'merchant_id' => $this->merchantId,
            'order_id' => $orderId,
            'amount' => $amountInKopecks,
            'currency' => 'RUB',
            'description' => $description,
            'language' => $payment['Language'] ?? 'ru',
            'signature' => $signature,
            'return_url' => $payment['ReturnUrl'] ?? config('app.url'),
        ];

        // Добавляем данные для чека (54-ФЗ), если ПСБ это поддерживает в вашей версии API
        if (!empty($items)) {
            $params['receipt'] = json_encode($this->buildPsbReceipt($payment, $items), JSON_UNESCAPED_UNICODE);
        }

        // Дополнительные данные (email, телефон)
        $data = [];
        if (!empty($payment['Email'])) $data['email'] = $payment['Email'];
        if (!empty($payment['Phone'])) $data['phone'] = $payment['Phone'];
        if (!empty($data)) {
            $params['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        return $this->sendRequest('payment/init', $params); // Эндпоинт может быть 'v2/payment' или 'acquiring'
    }

    /**
     * Проверка статуса заказа
     */
    public function getState(string $orderId): string|false
    {
        $signatureString = $this->merchantId . $orderId . $this->secretKey;
        $params = [
            'merchant_id' => $this->merchantId,
            'order_id' => $orderId,
            'signature' => md5($signatureString),
        ];

        return $this->sendRequest('payment/status', $params) ? $this->status : false;
    }

    /**
     * Формирование структуры чека (аналогично Сберу/Т-Банку)
     */
    private function buildPsbReceipt(array $payment, array $items): array
    {
        $psbItems = [];
        foreach ($items as $item) {
            $price = (int)round((float)$item['Price'] * 100);
            $quantity = (float)($item['Quantity'] ?? 1);
            $tax = $item['NDS'] ?? $item['Tax'] ?? 'none';

            $psbItems[] = [
                'name' => mb_strimwidth((string)$item['Name'], 0, 128, ''),
                'quantity' => number_format($quantity, 2, '.', ''),
                'sum' => (int)round($price * $quantity),
                'payment_method' => 'full_payment',
                'payment_object' => 'commodity',
                'tax' => $tax,
            ];
        }

        return [
            'sno' => $payment['Taxation'] ?? 'osn',
            'items' => $psbItems,
        ];
    }

    /**
     * Отправка запроса к API ПСБ
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

        // ПСБ обычно возвращает 'status': 'success' или 'code': 0
        if (isset($json['status']) && strtolower($json['status']) !== 'success' && isset($json['code']) && (int)$json['code'] !== 0) {
            $this->error = "PSB Error: " . ($json['message'] ?? 'Unknown error');
            return false;
        }

        $this->orderId = (string)($json['order_id'] ?? $params['order_id'] ?? '');
        // ПСБ может вернуть payment_url, form_url или redirect_url
        $this->paymentUrl = (string)($json['payment_url'] ?? $json['form_url'] ?? $json['redirect_url'] ?? '');
        $this->status = (string)($json['status'] ?? '');

        return $this->paymentUrl ?: false;
    }
}
