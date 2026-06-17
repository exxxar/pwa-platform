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
    public function getDistance($latA, $longA): float
    {
        $tenant = app('tenant');
        $shopCoords = $tenant->settings["shop_coords"] ?? null;

        $coords = explode(',', $shopCoords);

        $latB = floatval($coords[0] ?? 0);
        $longB = floatval($coords[1] ?? 0);

        $earth_radius = 6372795;
        // перевести координаты в радианы
        $lat1 = $latA * M_PI / 180;
        $lat2 = $latB * M_PI / 180;
        $long1 = $longA * M_PI / 180;
        $long2 = $longB * M_PI / 180;

// косинусы и синусы широт и разницы долгот
        $cl1 = cos($lat1);
        $cl2 = cos($lat2);
        $sl1 = sin($lat1);
        $sl2 = sin($lat2);
        $delta = $long2 - $long1;
        $cdelta = cos($delta);
        $sdelta = sin($delta);

// вычисления длины большого круга
        $y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
        $x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;

//
        $ad = atan2($y, $x);
        return $ad * $earth_radius;

    }
}
