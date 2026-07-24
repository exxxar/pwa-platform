<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GEOService
{

    public static function call(): self
    {
        return app(self::class);
    }


    /**
     * Универсальный прокси (если вдруг хочешь динамику)
     */
    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    /**
     * @throws ValidationException
     */
    public function getCoords(array $data): object
    {

        $validator = Validator::make($data, [
            "address" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $address = $data["address"] ?? '';

        $url = "https://nominatim.openstreetmap.org/search";

        $params = [
            'q' => $address,
            'format' => 'jsonv2',
            'polygon_geojson' => 1,
            'accept-language' => "ru-RU",
        ];
        $options = [
            'http' => [
                'header' => "User-Agent: CashMan/1.0\r\n"
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url . '?' . http_build_query($params), false, $context);
        $data = json_decode($response, true);


        if (empty($data)) {
            return (object)[
                'lat' => 0,
                'lon' => 0
            ];
        }

        return (object)[
            'lat' => (float)($data[0]['lat']),
            'lon' => (float)($data[0]['lon'])
        ];


    }

    /**
     * @throws ValidationException
     */
    // В вашем GEOService
    public function getDistance(float $clientLat, float $clientLng, ?string $shopCoords): float
    {
        if (empty($shopCoords)) {
            return 0.0; // Или выбросить исключение, если координаты обязательны
        }

        $coords = array_map('trim', explode(',', $shopCoords));
        if (count($coords) < 2 || !is_numeric($coords[0]) || !is_numeric($coords[1])) {
            return 0.0;
        }

        $shopLat = (float) $coords[0];
        $shopLng = (float) $coords[1];

        $earthRadius = 6371000;

        $dLat = deg2rad($shopLat - $clientLat);
        $dLon = deg2rad($shopLng - $clientLng);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($clientLat)) * cos(deg2rad($shopLat)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
