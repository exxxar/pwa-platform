<?php

namespace App\Services;

use App\Http\Resources\PartnerCollection;
use App\Http\Resources\PartnerResource;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Category;
use App\Models\Tenant\Partner;
use App\Models\Tenant\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PartnerService
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

    public function togglePartnerInFavorites($id): array
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $config = $tenantUser->meta ?? [];

        if (in_array($id, $config["fav_partners"] ?? [])) {
            $config["fav_partners"] = array_values(array_diff($config["fav_partners"], [$id]));
        } else {

            if (isset($config["fav_partners"]))
                $config["fav_partners"][] = $id;
            else
                $config["fav_partners"] = [$id];
        }

        $tenantUser->meta = $config;
        $tenantUser->save();

        return $config["fav_partners"];
    }


    public function list(array $data = null, $isForApi = false): PartnerCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        if (!$isForApi && is_null($tenantUser)) {
            throw new HttpException(404, "Бот и пользователь не найден!");
        }

        $config = $tenantUser->meta ?? [];
        $favPartners = !$isForApi ? ($config["fav_partners"] ?? []) : [];

        $partnersQuery = Partner::query()
            ->where("tenant_id", $tenant->id);

        // 🆕 Считаем количество активных товаров каждого партнёра
        // через подзапрос к таблице products по tenant_partner_id
        $partnersQuery->withCount([
            'partnerProducts as products_count' => function ($query) {
                $query->where('products.is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('products.in_stop_list')
                            ->orWhere('products.in_stop_list', false);
                    });
            }
        ]);

        // 🆕 Общая сумма товаров (для статистики)
        $partnersQuery->withSum([
            'partnerProducts as products_sum' => function ($query) {
                $query->where('products.is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('products.in_stop_list')
                            ->orWhere('products.in_stop_list', false);
                    });
            }
        ], 'price');

        // Сортировка
        if (!empty($favPartners)) {
            $ids = implode(',', $favPartners);
            $partnersQuery->orderByRaw("FIELD(id, $ids) desc");
        } else {
            $partnersQuery->orderBy("order_position", "DESC");
        }

        $partners = $partnersQuery->get();

        return new PartnerCollection($partners);
    }

    public function listOfPartnersCategories(): \Illuminate\Database\Eloquent\Collection|array
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $partnersId = $tenant->partners()->get()->pluck("tenant_partner_id");

        $categories = Category::query()
            ->with('tenant')
            ->whereIn('tenant_id', $partnersId)
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $grouped = [];

        foreach ($categories as $item) {
            $tenantId = $item->tenant->id ?? 'Без приложения'; // на всякий случай, если бот не подгружен

            if (!isset($grouped[$tenantId])) {
                $grouped[$tenantId]["categories"] = [];
                $grouped[$tenantId]["tenant"] = (object)[
                    "id" => $item->tenant->id,
                    "title" => $item->tenant->name,
                ];
            }

            $grouped[$tenantId]["categories"][] = $item;
        }


        return $grouped;

    }

    /**
     * @throws ValidationException
     */
    public function create(array $data): PartnerResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "uuid" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $botPartner = Tenant::query()
            ->where("uuid", $data["uuid"])
            ->first();

        if (is_null($botPartner))
            throw new HttpException(404, "Бот-партнер не найден в системе!");

        $partner = Partner::query()
            ->where("tenant_id", $tenant->id)
            ->where("tenant_partner_id", $botPartner->id)
            ->first();

        if (!is_null($partner))
            throw new HttpException(403, "Данные боты уже являются партнерами!");

        $partner = Partner::query()->create(
            [
                'tenant_id' => $tenant->id,
                'tenant_partner_id' => $botPartner->id,
                'title' => $botPartner->title,
                'description' => $botPartner->short_description,
                'image' => $botPartner->image,
                'is_active' => true,
                'extra_charge' => 0,
                'config' => [],
                'legal_info' => [],
            ]);

        return new PartnerResource($partner);
    }

    /**
     * @throws ValidationException
     */
    public function update(array $data, $file = null): PartnerResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            'id' => "required",
            'tenant_partner_id' => "required",
            'title' => "",
            'description' => "",
            'order_position' => "",
            'image' => "",
            'is_active' => "",
            'extra_charge' => "",
            'config' => "",
            'legal_info' => "",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $botPartner = Tenant::query()
            ->where("id", $data["tenant_partner_id"])
            ->first();

        if (is_null($botPartner))
            throw new HttpException(404, "Бот-партнер не найден в системе!");

        $partner = Partner::query()
            ->where("id", $data["id"])
            ->first();

        if (is_null($partner))
            throw new HttpException(403, "Данные боты уже являются партнерами!");

        if ($file) {
            $slug = $tenant->company->slug;
            $ext = $file->getClientOriginalExtension();
            $imageName = Str::uuid() . "." . $ext;
            $file->storeAs("/public/companies/$slug/$imageName");
            $data['image'] = $imageName;
        }

        $partner->update(
            [
                'title' => $data["title"] ?? $partner->title,
                'description' => $data["description"] ?? $partner->description,
                'image' => $data["image"] ?? $partner->image,
                'order_position' => $data["order_position"] ?? 0,
                'is_active' => ($data["is_active"] ?? false) == "true",
                'extra_charge' => $data["extra_charge"] ?? $partner->extra_charge ?? 0,
                'config' => isset($data["config"]) ? json_decode($data["config"] ?? '[]') : $partner->config,
                'legal_info' => isset($data["legal_info"]) ? json_decode($data["legal_info"] ?? '[]') : $partner->legal_info,
            ]);

        return new PartnerResource($partner);
    }

    /**
     * @throws ValidationException
     */
    public function updateSelf(array $data, $file = null)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            'title' => "",
            'description' => "",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $description = $data["description"] ?? null;
        $title = $data["title"] ?? null;

        $config = $tenant->config ?? [];
        $partnersConfig = $config["partners"] ?? [];

        $partnersConfig["title"] = $title;
        $partnersConfig["description"] = $description;

        if ($file) {
            $slug = $tenant->company->slug;
            $ext = $file->getClientOriginalExtension();
            $imageName = Str::uuid() . "." . $ext;
            $file->storeAs("/public/companies/$slug/$imageName");
            $partnersConfig["image"] = $imageName;
        }

        $categories = Category::query()
            ->where("tenant_id", $tenant->id)
            ->get()
            ->select("title");

        $partnersConfig["categories"] = $categories->toArray();
        $config["partners"] = $partnersConfig;

        $tenant->config = $config;
        $tenant->save();

        return $config["partners"];

    }

    /**
     * @throws ValidationException
     */
    public function changeStatus(array $data)
    {


        $validator = Validator::make($data, [
            "product_id" => "required",
            "partner_id" => "required",
            "status" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $productId = $data["product_id"];
        $partnerId = $data["partner_id"];
        $status = $data["status"] ?? 0;

        $partner = Partner::query()
            ->where("id", $partnerId)
            ->first();

        if (is_null($partner))
            throw new HttpException(404, "Бот-партнер не найден в системе!");


        $excludes = $partner->config["excludes"] ?? [];
        switch ($status) {
            default:
            case 0:
                $excludes = array_filter($excludes ?? [], fn($v) => $v !== $productId);
                break;
            case 1:
                $excludes[] = $productId;
                break;

        }

        $config = $partner->config;
        $config["excludes"] = $excludes;
        $partner->config = $config;
        $partner->save();

        return $config["excludes"];
    }


    /**
     * @throws ValidationException
     */
    public function updateActiveStatus(array $data)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "id" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

     //   $isActive = ($data["is_active"] ?? false) == "true";;
        $partnerId = $data["id"];

        $partner = Partner::query()
            ->where("id", $partnerId)
            ->first();

        if (is_null($partner))
            throw new HttpException(404, "Партнер не найден!");

        $partner->is_active = !$partner->is_active;
        $partner->save();

        if (!$partner->is_active) {
            $basket = Basket::query()
                ->where("tenant_id", $partner->tenant_partner_id)
                ->get();

            foreach ($basket as $item)
                $item->delete();


        }

        return new PartnerResource($partner);
    }

    /**
     * @throws ValidationException
     */
    public function updateSettings(array $data)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        if (!$tenantUser) {
            throw new ValidationException(
                Validator::make([], [], ['required' => 'Пользователь не авторизован'])
            );
        }

        // 🆕 Разрешённые поля для обновления
        $allowedFields = [
            'is_active' => 'sometimes|boolean',
            'display_self' => 'sometimes|boolean',
            // Можно добавить в будущем:
            // 'commission_rate' => 'sometimes|numeric|min:0|max:100',
            // 'auto_approve' => 'sometimes|boolean',
        ];

        $validator = Validator::make($data, $allowedFields);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 🆕 Получаем текущие настройки
        $meta = $tenant->meta ?? [];
        $partnersConfig = $meta["partners"] ?? [];

        // 🆕 Обновляем только переданные поля
        foreach (array_keys($allowedFields) as $field) {
            if (array_key_exists($field, $data)) {
                $partnersConfig[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        // 🆕 Логическая проверка: display_self требует is_active
        if (($partnersConfig['display_self'] ?? false) && !($partnersConfig['is_active'] ?? false)) {
            $partnersConfig['display_self'] = false;
        }

        $meta["partners"] = $partnersConfig;
        $tenant->meta = $meta;
        $tenant->save();

        // 🆕 При отключении программы — деактивируем партнёров
        if (!($partnersConfig['is_active'] ?? false)) {
            Partner::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return $meta["partners"];
    }

    /**
     * @throws ValidationException
     */
    public function destroy($id)
    {
        $partner = Partner::query()
            ->where("id", $id)
            ->first();

        if (is_null($partner))
            throw new HttpException(404, "Партнер не найден!");

        $partner->delete();

        $basket = Basket::query()
            ->where("tenant_id", $id)
            ->get();

        foreach ($basket as $item)
            $item->delete();


        return new PartnerResource($partner);
    }
}
