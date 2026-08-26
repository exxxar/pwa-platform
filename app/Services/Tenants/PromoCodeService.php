<?php

namespace App\Services\Tenants;

use App\Http\Resources\PromoCodeCollection;
use App\Http\Resources\PromoCodeResource;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Partner;
use App\Models\Tenant\PromoCode;
use App\Services\BotMethods;
use App\Services\InputFile;
use App\Services\QrCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PromoCodeService
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
     * @throws HttpException
     */
    public function activatePromoCodeForDiscount(array $data): object
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            'code' => "required",

        ]);

        if ($validator->fails())
            throw new ValidationException($validator);


        $tenantPartnersIds = Partner::query()
            ->where("tenant_id", $tenant->id)
            ->get()
            ->pluck("tenant_partner_id");

        $codes = PromoCode::query()
            ->with(["tenantUser"])
            ->whereIn("tenant_id", [...$tenantPartnersIds->toArray(), $tenant->id])
            ->where("code", $data["code"])
            ->where("is_active", true)
            ->where('available_to', '>', Carbon::now())
            ->get();


        if (count($codes) == 0)
            throw new HttpException(404, "Промокод не найден");

        $basket = \App\Models\Basket::query()
            ->with('product')
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $tenantUser->id)
            ->whereNull('ordered_at')
            ->get();

        $basketPrice = $basket->sum(function ($basket) {
            return $basket->count * ($basket->product->price ?? 0);
        });

        $activatePrice = $codes->max('activate_price');

        if ($basketPrice < $activatePrice)
            throw new HttpException(404, "Недостаточно общей суммы покупок для активации промокода");

        $errors = [];

        $summaryDiscount = 0; //23456788999

        foreach ($codes as $code) {
            $isPromoActivated = !is_null($code->tenantUser()
                ->where("tenant_user_id", $tenantUser->id)
                ->first() ?? null);

            if ($isPromoActivated) {
                $errors[] = "Промокод уже активирован";
                continue;
            }

            $maxActivations = $code->max_activation_count ?? 0;
            $currentActivations = $code->tenantUser()->count() ?? 0;

            if ($currentActivations >= $maxActivations) {
                $code->is_active = false;
                $code->save();

                $errors[] = "Закончились попытки активации промокода!";
                continue;
            }

            $code->tenantUser()->attach([$tenantUser->id]);
            $code->save();


            if ($code->cashback_amount == 0) {
                $errors[] = "Данный промокод нельзя активировать как скидочный!";
                continue;
            }

            $config = $tenantUser->meta ?? [];
            $config["current_promocodes"][] = $code->id;
            $tenantUser->meta = $config;
            $tenantUser->save();

            $basketByPartner = Basket::query()
                ->with('product')
                ->where('tenant_id', $tenant->id)
                ->where('tenant_user_id', $tenantUser->id);

            if ($code->tenant_id != $tenant->id)
                $basketByPartner = $basketByPartner
                    ->where('tenant_partner_id', $code->tenant_id);

            $basketByPartner = $basketByPartner
                ->whereNull('ordered_at')
                ->get();


            if (count($basketByPartner) > 0)
                foreach ($basketByPartner as $item) {

                    $inPercent = $code->config["discount_in_percent"] ?? false;
                    $val = $code->cashback_amount;

                    $params = $item->params ?? [];

                    $params["discount_price"] = $inPercent ?
                        $item->product->price - ($item->product->price * ($val / 100)) :
                        $item->product->price - $val;

                    $params["discount_amount"] = $inPercent ? ($item->product->price * ($val / 100)) : $val;

                    $summaryDiscount += $params["discount_amount"] * $item->count;

                    $params["discount_object"] = (object)[
                        "code_id" => $code->id,
                        "amount" => $code->cashback_amount,
                        "in_percent" => $inPercent,
                    ];

                    $item->params = $params;
                    $item->save();

                }


        }


        return (object)[
            "messages" => $errors,
            "discount" => $summaryDiscount
        ];
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     * @throws \Exception
     */
    public function activatePromoCode(array $data): object
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            'code' => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);


        $code = PromoCode::query()
            ->with(["users"])
            ->where("tenant_id", $tenant->id)
            ->where("code", $data["code"])
            ->first();

        if (is_null($code))
            throw new HttpException(404, "Промокод не найден");

        if (!$code->is_active)
            throw new HttpException(403, "Промокод не активен");

        if (!is_null($code->available_to)) {
            if (Carbon::parse($code->available_to)->timestamp < Carbon::now()->timestamp)
                throw new HttpException(400, "Срок действия промокода закончен!");
        }


        $isPromoActivated = !is_null($code
            ->users()
            ->where("tenant_user_id", $tenantUser->id)
            ->first() ?? null);

        if ($isPromoActivated)
            throw new HttpException(400, "Промокод уже активирован");

        $maxActivations = $code->max_activation_count ?? 0;
        $currentActivations = $code->users()->count() ?? 0;

        if ($currentActivations >= $maxActivations) {
            $code->is_active = false;
            $code->save();
            throw new HttpException(400, "Закончились попытки активации промокода!");
        }

        $code->users()->attach([$tenantUser->id]);
        $code->save();

        $tmpSlotsCount = 0;
        $tmpCashBackCount = 0;


        if ($code->cashback_amount > 0) {

            $cashBackAmount = $code->cashback_amount;
            $tmpCashBackCount += $code->cashback_amount;

            CashBackService::call()
                ->addCashBack(
                    $cashBackAmount,
                    "Мгновенное начисление CashBack в размере $cashBackAmount руб.", 100);
        }


        return (object)[
            "cashback" => $tmpCashBackCount,
            "slots" => $tmpSlotsCount
        ];
    }


    /**
     * @throws HttpException
     */
    public function listOfPromoCodes($search = null, $size = null, $order = null, $direction = null): PromoCodeCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $size = $size ?? config('app.results_per_page');

        $codes = PromoCode::query()
            // ->withTrashed()
            ->where("tenant_id", $tenant->id);

        if (!is_null($search))
            $codes = $codes->where(function ($q) use ($search) {
                $q->where("description", 'like', "%$search%");
                $q->orWhere("code", 'like', "%$search%");
            });


        $codes = $codes
            ->orderBy($order ?? 'updated_at', $direction ?? 'DESC')
            ->paginate($size);

        return new PromoCodeCollection($codes);
    }


    /**
     * @throws HttpException
     */
    public function removePromoCode($promoCodeId): PromoCodeResource
    {
        $code = PromoCode::query()
            ->where("id", $promoCodeId)
            ->first();

        if (is_null($code))
            throw new HttpException(404, "Промокод не найден");

        $tmp = $code;

        $code->delete();

        return new PromoCodeResource($tmp);
    }

    public function generateCertificate($titleText, $prizeText, $finalDate = null)
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $date = $finalDate ?? date('d.m.Y');

        $templatePath = storage_path('app/public/certificates/certificate_template.png');

        // Загружаем изображение
        $image = imagecreatefrompng($templatePath);

        // Получаем размеры изображения
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        // Устанавливаем цвет текста (черный)
        $textColor = imagecolorallocate($image, 0, 0, 0);

        // Указываем путь к шрифту (TrueType)
        $fontPath = storage_path('app/public/certificates/Ura Bum Bum SP.ttf'); // Добавь свой шрифт в public/fonts

        // Размеры шрифта
        $fontSizeName = 20;
        $fontSizeInfo = 16;

        // Высоты строк
        $yName = $imageHeight / 2 - 30;
        $yCourse = $yName + 40;
        $yDate = $yCourse + 30;

        // Функция для отцентровки текста
        $centerText = function ($text, $fontSize, $y) use ($image, $imageWidth, $textColor, $fontPath) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = abs($box[2] - $box[0]);
            $x = ($imageWidth - $textWidth) / 2;
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $text);
        };

        $centerText($titleText, $fontSizeName, $yName);
        $centerText("Промокод: $prizeText", $fontSizeInfo, $yCourse);
        $centerText("Дата окончания: $date", $fontSizeInfo, $yDate);

        $qrText = "https://t.me/" . $tenant->bot_domain;
        // Генерируем QR в PNG и получаем как строку
        $qrPng = QrCode::format('png')->size(100)->margin(1)->generate($qrText);

        // Создаём изображение QR-кода из строки
        $qrImage = imagecreatefromstring($qrPng);

        // Координаты для размещения (правый нижний угол с отступами)
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $padding = 30;

        $qrX = ($imageWidth / 2) - $qrWidth + 50;
        $qrY = ($imageHeight / 2) - $qrHeight + 200;

        // Накладываем QR-код
        imagecopy($image, $qrImage, $qrX, $qrY, 0, 0, $qrWidth, $qrHeight);
        // Буферизуем вывод
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();

        // Освобождаем память
        imagedestroy($image);


   /*     BotMethods::bot()
            ->whereBot($tenant)
            ->sendPhoto(
                $tenantUser->telegram_chat_id,
                "Информация о сертификате",
                InputFile::createFromContents($imageData, "certificate.png")
            );*/
    }

    public function generateFreeCertificate($titleText, $prizeText)
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $date = $finalDate ?? date('d.m.Y');

        $templatePath = storage_path('app/public/certificates/certificate_template.png');

        // Загружаем изображение
        $image = imagecreatefrompng($templatePath);

        // Получаем размеры изображения
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        // Устанавливаем цвет текста (черный)
        $textColor = imagecolorallocate($image, 0, 0, 0);

        // Указываем путь к шрифту (TrueType)
        $fontPath = storage_path('app/public/certificates/Ura Bum Bum SP.ttf'); // Добавь свой шрифт в public/fonts

        // Размеры шрифта
        $fontSizeName = 20;
        $fontSizeInfo = 16;

        // Высоты строк
        $yName = $imageHeight / 2 - 30;
        $yCourse = $yName + 40;
        $yDate = $yCourse + 30;

        // Функция для отцентровки текста
        $centerText = function ($text, $fontSize, $y) use ($image, $imageWidth, $textColor, $fontPath) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = abs($box[2] - $box[0]);
            $x = ($imageWidth - $textWidth) / 2;
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $text);
        };

        $centerText($titleText, $fontSizeName, $yName);
        $centerText("$prizeText", $fontSizeInfo, $yCourse);
        $centerText("Дата получения: $date", $fontSizeInfo, $yDate);

        $qrText = "https://t.me/" . $tenant->bot_domain;
        // Генерируем QR в PNG и получаем как строку
        $qrPng = QrCode::format('png')->size(100)->margin(1)->generate($qrText);

        // Создаём изображение QR-кода из строки
        $qrImage = imagecreatefromstring($qrPng);

        // Координаты для размещения (правый нижний угол с отступами)
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $padding = 30;

        $qrX = ($imageWidth / 2) - $qrWidth + 50;
        $qrY = ($imageHeight / 2) - $qrHeight + 200;

        // Накладываем QR-код
        imagecopy($image, $qrImage, $qrX, $qrY, 0, 0, $qrWidth, $qrHeight);
        // Буферизуем вывод
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();

        // Освобождаем память
        imagedestroy($image);


        BotMethods::bot()
            ->whereBot($tenant)
            ->sendPhoto(
                $tenantUser->telegram_chat_id,
                "Информация о сертификате: $titleText\n$prizeText",
                InputFile::createFromContents($imageData, "certificate.png")
            );
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function store(array $data): PromoCodeResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            'code' => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $needCertificate = ($data["need_certificate"] ?? false) == "true";

        $tmp = [
            'tenant_id' => $tenant->id,
            'code' => $data["code"],
            'description' => $data["description"] ?? null,
            'slot_amount' =>$data["slot_amount"] ?? 0,
            'cashback_amount' => $data["cashback_amount"] ?? 0,
            'activate_price' => $data["activate_price"] ?? 0,
            'max_activation_count' => $data["max_activation_count"] ?? 1,
            'is_active' => ($data["is_active"] ?? false) == "true",
            'available_to' => !isset($data["available_to"]) ? null : Carbon::parse($data["available_to"]),
            'config' => isset($data["config"]) ? json_decode($data["config"]) : (object)[
                "discount_in_percent" => false
            ],
        ];

        if (is_null($data["id"] ?? null)) {

            $isCodeExist = !is_null(PromoCode::query()
                ->where("tenant_id", $tenant->id)
                ->where("code", $data["code"])
                ->first() ?? null);

            if ($isCodeExist)
                throw new HttpException(403, "Данный код уже существует в системе");

            $code = PromoCode::query()->create($tmp);
        } else {
            $code = PromoCode::query()
                ->where("tenant_id", $tenant->id)
                ->find($data["id"]);

            $code->update($tmp);
        }


        if ($needCertificate)
            $this->generateCertificate(
                $tmp["description"] ?? 'Промокод на приз',
                $tmp["code"] ?? 'Промокод не найден',
                $tmp["available_to"]);

        return new PromoCodeResource($code);
    }

}
