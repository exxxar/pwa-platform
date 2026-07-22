<?php

declare(strict_types=1);

namespace App\Services\Banking;

class SberbankService
{
    private string $apiUrl;
    private string $userName; // В Сбербанке это "Логин API" (не путать с TerminalKey из Т-Банка)
    private string $password; // Пароль API

    protected string $error = '';
    protected string $response = '';

    protected ?string $orderId = null;
    protected ?string $paymentUrl = null;
    protected ?string $status = null;

    public function __construct(string $apiUrl, string $userName, string $password)
    {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        $this->userName = $userName;
        $this->password = $password;
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
        // Сбер требует обязательный returnUrl
        $returnUrl = $payment['ReturnUrl'] ?? config('app.url');

        $params = [
            'orderNumber' => (string)$payment['OrderId'],
            'amount' => (int)round((float)$payment['Amount'] * 100), // В копейках
            'returnUrl' => $returnUrl,
            'description' => $payment['Description'] ?? 'Оплата заказа',
        ];

        // Дополнительные параметры (email, телефон) передаем в jsonParams
        $jsonParams = [];
        if (!empty($payment['Email'])) $jsonParams['email'] = $payment['Email'];
        if (!empty($payment['Phone'])) $jsonParams['phone'] = $payment['Phone'];
        if (!empty($payment['CustomerKey'])) $jsonParams['customerKey'] = $payment['CustomerKey'];

        if (!empty($jsonParams)) {
            $params['jsonParams'] = json_encode($jsonParams, JSON_UNESCAPED_UNICODE);
        }

        // Формирование данных для чека (54-ФЗ)
        if (!empty($items)) {
            $params['receipt'] = json_encode($this->buildSberReceipt($payment, $items), JSON_UNESCAPED_UNICODE);
        }

        return $this->sendRequest('register.do', $params);
    }

    /**
     * Проверка статуса заказа
     */
    public function getState(string $orderNumber): string|false
    {
        $params = ['orderNumber' => $orderNumber];
        return $this->sendRequest('getOrderStatusExtended.do', $params) ? $this->status : false;
    }

    /**
     * Формирование структуры чека для Сбера
     */
    private function buildSberReceipt(array $payment, array $items): array
    {
        $sberItems = [];
        foreach ($items as $item) {
            $price = (int)round((float)$item['Price'] * 100);
            $quantity = (float)($item['Quantity'] ?? 1);

            // Маппинг налогов: Т-Банк 'vat20' -> Сбер 'vat20', 'none' -> 'none'
            $tax = $item['NDS'] ?? $item['Tax'] ?? 'none';
            if ($tax === 'vat0') $tax = 'vat0';
            elseif ($tax === 'vat10') $tax = 'vat10';
            elseif ($tax === 'vat20') $tax = 'vat20';
            else $tax = 'none';

            $sberItems[] = [
                'name' => mb_strimwidth((string)$item['Name'], 0, 128, ''),
                'quantity' => number_format($quantity, 2, '.', ''),
                'sum' => (int)round($price * $quantity),
                'paymentMethod' => 'full_payment', // full_payment, full_prepayment, и т.д.
                'paymentObject' => 'commodity',    // commodity, job, service, и т.д.
                'tax' => $tax,
            ];
        }

        return [
            'sno' => $payment['Taxation'] ?? 'osn', // osn, usn_income, usn_income_outcome, patent
            'items' => $sberItems,
        ];
    }

    /**
     * Отправка запроса к API Сбера
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
            // ❗ HTTP Basic Auth для Сбера
            CURLOPT_USERPWD => "{$this->userName}:{$this->password}",
            CURLOPT_SSL_VERIFYPEER => false, // В продакшене лучше true с CURLOPT_CAINFO
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

        // Сбер возвращает errorCode: 0 при успехе
        if (isset($json['errorCode']) && (int)$json['errorCode'] !== 0) {
            $this->error = "Sber Error [{$json['errorCode']}]: {$json['errorMessage']}";
            return false;
        }

        $this->orderId = (string)($json['orderId'] ?? '');
        $this->paymentUrl = (string)($json['formUrl'] ?? $json['paymentUrl'] ?? '');
        $this->status = (string)($json['orderStatus'] ?? '');

        return $this->paymentUrl ?: false;
    }
}
