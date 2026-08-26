<?php

namespace App\Services\Tenants\Banking;

class TinkoffBankService
{
    private string $acquiring_url;
    private string $terminal_id;
    private string $secret_key;

    private string $url_init;
    private string $url_cancel;
    private string $url_confirm;
    private string $url_get_state;

    protected string $error = '';
    protected string $response = '';

    protected ?string $payment_id = null;
    protected ?string $payment_url = null;
    protected ?string $payment_status = null;

    public function getResponse(): ?object
    {
        return json_decode($this->response ?: '[]');
    }

    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Инициализация класса Tinkoff
     *
     * @param string $acquiring_url - URL API Т-Банка (например, https://securepay.tinkoff.ru/v2/)
     * @param string $terminal_id   - Номер терминала (TerminalKey)
     * @param string $secret_key    - Пароль терминала
     */
    public function __construct(string $acquiring_url, string $terminal_id, string $secret_key)
    {
        $this->acquiring_url = $acquiring_url;
        $this->terminal_id = $terminal_id;
        $this->secret_key = $secret_key;
        $this->setupUrls();
    }

    /**
     * Генерация ссылки на оплату
     *
     * @param array $payment Массив данных платежа
     * @param array $items   Массив товаров (для чека)
     * @return string|false Возвращает URL оплаты или false при ошибке
     */
    public function paymentURL(array $payment, array $items)
    {
        if (!$this->paymentArrayChecked($payment)) {
            $this->error = 'Incomplete payment data';
            return false;
        }

        $item_name_max_length = 64;
        $amount_multiplier = 100; // Множитель для перевода рублей в копейки

        $receiptItems = [];
        foreach ($items as $item) {
            if (!$this->itemsArrayChecked($item)) {
                $this->error = 'Incomplete items data';
                return false;
            }

            // ❗ КРИТИЧНО: Приводим к int, так как Т-Банк не принимает float в этих полях
            $priceInKopecks = (int)round((float)$item['Price'] * $amount_multiplier);
            $quantity = (int)($item['Quantity'] ?? 1);

            $receiptItems[] = [
                'Name'     => mb_strimwidth((string)$item['Name'], 0, $item_name_max_length - 1, ''),
                'Price'    => $priceInKopecks,
                'Quantity' => $quantity,
                'Amount'   => $priceInKopecks * $quantity,
                'Tax'      => $item['NDS'] ?? $item['Tax'] ?? 'none', // Т-Банк ожидает ключ 'Tax'
            ];
        }

        $params = [
            'OrderId'     => (string)$payment['OrderId'],
            'Amount'      => (int)round((float)$payment['Amount'] * $amount_multiplier),
            'Language'    => $payment['Language'],
            'Description' => $payment['Description'],
            'DATA'        => [
                'Email' => $payment['Email'] ?? '',
                'Phone' => $payment['Phone'] ?? '',
                'Name'  => $payment['Name'] ?? '',
            ],
            'Receipt'     => [
                'Email'    => $payment['Email'] ?? '',
                'Phone'    => $payment['Phone'] ?? '',
                'Taxation' => $payment['Taxation'],
                'Items'    => $receiptItems,
            ],
        ];

        // Добавляем Recurrent, если передан
        if (!empty($payment['Recurrent'])) {
            $params['Recurrent'] = $payment['Recurrent'];
        }

        if ($this->sendRequest($this->url_init, $params)) {
            return $this->payment_url;
        }

        return false;
    }

    /**
     * Проверка статуса платежа
     *
     * @param string $payment_id ID платежа Т-Банка
     * @return string|false Статус платежа или false
     */
    public function getState(string $payment_id)
    {
        $params = ['PaymentId' => $payment_id];
        if ($this->sendRequest($this->url_get_state, $params)) {
            return $this->payment_status;
        }
        return false;
    }

    /**
     * Подтверждение платежа
     *
     * @param string $payment_id ID платежа Т-Банка
     * @return string|false Статус платежа или false
     */
    public function confirmPayment(string $payment_id)
    {
        $params = ['PaymentId' => $payment_id];
        if ($this->sendRequest($this->url_confirm, $params)) {
            return $this->payment_status;
        }
        return false;
    }

    /**
     * Отмена платежа
     *
     * @param string $payment_id ID платежа Т-Банка
     * @return string|false Статус платежа или false
     */
    public function cancelPayment(string $payment_id) // Исправлена опечатка cencel -> cancel
    {
        $params = ['PaymentId' => $payment_id];
        if ($this->sendRequest($this->url_cancel, $params)) {
            return $this->payment_status;
        }
        return false;
    }

    /**
     * Отправка запроса к API банка
     *
     * @param string $path URL эндпоинта
     * @param array  $args Данные запроса
     * @return bool Успешность запроса
     */
    private function sendRequest(string $path, array $args): bool
    {
        $args['TerminalKey'] = $this->terminal_id;
        $args['Token'] = $this->generateToken($args);
        $payload = json_encode($args, JSON_UNESCAPED_UNICODE);

        $curl = curl_init();
        if (!$curl) {
            $this->error = "CURL init failed: $path";
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL            => $path,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            // ⚠️ ВНИМАНИЕ: Для production-среды рекомендуется установить CURLOPT_SSL_VERIFYPEER в true
            // и указать путь к актуальному CA-сертификату через CURLOPT_CAINFO.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        curl_close($curl);

        $this->response = (string)$response;

        if ($curlError) {
            $this->error = "CURL Error: $curlError | Path: $path";
            return false;
        }

        $json = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
            $this->error = "Invalid JSON response from: $path | Response: $response";
            return false;
        }

        if ($this->errorsFound($json)) {
            return false;
        }

        $this->payment_id = (string)($json['PaymentId'] ?? '');
        $this->payment_url = (string)($json['PaymentURL'] ?? '');
        $this->payment_status = (string)($json['Status'] ?? '');

        return true;
    }

    /**
     * Проверка ответа на наличие ошибок
     *
     * @param array $response Распарсенный ответ от API
     * @return bool true если есть ошибка, false если всё ОК
     */
    private function errorsFound(array $response): bool
    {
        $error_code = (int)($response['ErrorCode'] ?? 0);
        $error_msg = $response['Message'] ?? 'Unknown error.';
        $error_details = $response['Details'] ?? 'Unknown error.';

        // Т-Банк возвращает ErrorCode = 0 при успехе
        if ($error_code !== 0) {
            $this->error = "Error code: {$error_code} | Msg: {$error_msg} | Details: {$error_details}";
            return true;
        }

        return false;
    }

    /**
     * Генерация SHA-256 токена для API банка
     *
     * @param array $args Параметры запроса
     * @return string Хеш токена
     */
    private function generateToken(array $args): string
    {
        $args['Password'] = $this->secret_key;
        $args['TerminalKey'] = $this->terminal_id;

        // Сортируем ключи по алфавиту (требование Т-Банка)
        ksort($args);

        $token = '';
        foreach ($args as $value) {
            if (!is_array($value)) {
                $token .= (string)$value;
            }
        }

        return hash('sha256', $token);
    }

    /**
     * Настройка URL-адресов эндпоинтов
     */
    private function setupUrls(): void
    {
        // Надежный способ добавить слеш в конец, если его нет
        $baseUrl = rtrim($this->acquiring_url, '/') . '/';

        $this->url_init = $baseUrl . 'Init/';
        $this->url_cancel = $baseUrl . 'Cancel/';
        $this->url_confirm = $baseUrl . 'Confirm/';
        $this->url_get_state = $baseUrl . 'GetState/';
    }

    /**
     * Проверка наличия всех обязательных ключей в массиве платежа
     */
    private function paymentArrayChecked(array $array_for_check): bool
    {
        $keys = ['OrderId', 'Amount', 'Language', 'Description', 'Email', 'Phone', 'Name', 'Taxation'];
        return $this->allKeysIsExistInArray($keys, $array_for_check);
    }

    /**
     * Проверка наличия всех обязательных ключей в массиве товара
     */
    private function itemsArrayChecked(array $array_for_check): bool
    {
        $keys = ['Name', 'Price', 'Quantity']; // NDS/Tax не всегда обязателен на этапе проверки структуры, но обрабатывается выше
        return $this->allKeysIsExistInArray($keys, $array_for_check);
    }

    /**
     * Универсальная проверка существования ключей
     */
    private function allKeysIsExistInArray(array $keys, array $arr): bool
    {
        return count(array_diff_key(array_flip($keys), $arr)) === 0;
    }

    /**
     * Магический метод для получения защищенных свойств
     */
    public function __get(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        return null;
    }
}
