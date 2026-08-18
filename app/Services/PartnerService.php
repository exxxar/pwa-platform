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
        $tenantUser = Auth::guard('tenant')->user();

        // Если пользователя нет, возвращаем пустой массив (или кидаем исключение, зависит от твоей логики)
        if (!$tenantUser) {
            return [];
        }

        $config = $tenantUser->meta ?? [];

        // 1. Приводим входящий ID к строке для гарантированного совпадения
        $id = (string)$id;

        // 2. Получаем текущие избранные, гарантируем, что это коллекция строк
        $favPartners = collect($config['fav_partners'] ?? [])
            ->map(fn($item) => (string)$item);

        // 3. Проверяем наличие и добавляем/удаляем
        if ($favPartners->contains($id)) {
            // Удаляем и переиндексируем массив (чтобы не было дырок в ключах [0 => 1, 2 => 3])
            $favPartners = $favPartners->reject(fn($item) => $item === $id)
                ->values()
                ->toArray();
        } else {
            // Добавляем и переиндексируем
            $favPartners = $favPartners->push($id)
                ->values()
                ->toArray();
        }

        // 4. Сохраняем
        $config['fav_partners'] = $favPartners;
        $tenantUser->meta = $config;
        $tenantUser->save();

        return $config['fav_partners'];
    }

    /**
     * Получение списка партнеров с фильтрацией и агрегацией
     *
     * @param array|null $data Параметры фильтрации (tag, tags, per_page и т.д.)
     * @param bool $isForApi Флаг API-запроса
     * @return PartnerCollection
     * @throws HttpException
     */
    public function list(?array $data = [], bool $isForApi = false): PartnerCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        // 1. Валидация доступа
        if (!$isForApi && !$tenantUser) {
            throw new HttpException(404, "Бот и пользователь не найдены!");
        }

        // 2. Извлечение избранных партнеров (только для не-API запросов, если нужно)
        $favPartners = !$isForApi && $tenantUser ? ($tenantUser->meta['fav_partners'] ?? []) : [];

        // 3. Базовый запрос с использованием скоупов модели
        $query = Partner::query()
            ->where('tenant_id', $tenant->id)
            ->active(); // 🆕 Используем скоуп active() из модели

        // 4. 🆕 Фильтрация по тегам
        if (!empty($data['tag'])) {
            $query =   $query->whereTag($data['tag']);
        } elseif (!empty($data['tags']) && is_array($data['tags'])) {
            $query =  $query->whereTags($data['tags']);
        }

        // 5. 🆕 DRY: Выносим повторяющееся условие активных товаров в переменную
        $activeProductsCondition = function ($q) {
            $q->where('is_active', true)
                ->where(function ($subQuery) {
                    $subQuery->whereNull('in_stop_list')
                        ->orWhere('in_stop_list', false);
                });
        };

        // 6. Агрегация данных (количество и сумма)
        $query = $query->withCount([
            'partnerProducts as products_count' => $activeProductsCondition
        ])
            ->withSum([
                'partnerProducts as products_sum' => $activeProductsCondition
            ], 'price');

        // 7. 🆕 Умная и безопасная сортировка
        if (!empty($favPartners)) {
            // Защита от SQL-инъекций: приводим все ID к целым числам
            $safeIds = implode(',', array_map('intval', $favPartners));

            // Сортируем так, чтобы избранные были вверху (FIELD возвращает 0, если ID нет в списке)
            $query =   $query->orderByRaw("FIELD(id, {$safeIds}) DESC");
        }

        // Вторичная сортировка всегда применяется (даже если есть избранные)
        $query =  $query->orderBy('id', 'DESC')
            ->orderBy('order_position', 'DESC'); // Дополнительная стабилизация сортировки

        // 8. Выполнение запроса
        // Примечание: если нужна пагинация, замените ->get() на ->paginate($data['per_page'] ?? 15)
        // и измените возвращаемый тип на \Illuminate\Pagination\LengthAwarePaginator
        $partners = $query->get();

        return new PartnerCollection($partners);
    }

    public function listForAdmins(?array $data = [], bool $isForApi = false): PartnerCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        // 1. Валидация доступа
        if (!$isForApi && !$tenantUser) {
            throw new HttpException(404, "Бот и пользователь не найдены!");
        }

        // 2. Извлечение избранных партнеров (только для не-API запросов, если нужно)
        $favPartners = !$isForApi && $tenantUser ? ($tenantUser->meta['fav_partners'] ?? []) : [];

        // 3. Базовый запрос с использованием скоупов модели
        $query = Partner::query()
            ->where('tenant_id', $tenant->id); // 🆕 Используем скоуп active() из модели

        // 4. 🆕 Фильтрация по тегам
        if (!empty($data['tag'])) {
            $query =   $query->whereTag($data['tag']);
        } elseif (!empty($data['tags']) && is_array($data['tags'])) {
            $query =  $query->whereTags($data['tags']);
        }

        // 5. 🆕 DRY: Выносим повторяющееся условие активных товаров в переменную
        $activeProductsCondition = function ($q) {
            $q->where('is_active', true)
                ->where(function ($subQuery) {
                    $subQuery->whereNull('in_stop_list')
                        ->orWhere('in_stop_list', false);
                });
        };

        // 6. Агрегация данных (количество и сумма)
        $query = $query->withCount([
            'partnerProducts as products_count' => $activeProductsCondition
        ])
            ->withSum([
                'partnerProducts as products_sum' => $activeProductsCondition
            ], 'price');

        // 7. 🆕 Умная и безопасная сортировка
        if (!empty($favPartners)) {
            // Защита от SQL-инъекций: приводим все ID к целым числам
            $safeIds = implode(',', array_map('intval', $favPartners));

            // Сортируем так, чтобы избранные были вверху (FIELD возвращает 0, если ID нет в списке)
            $query =   $query->orderByRaw("FIELD(id, {$safeIds}) DESC");
        }

        // Вторичная сортировка всегда применяется (даже если есть избранные)
        $query =  $query->orderBy('id', 'DESC')
            ->orderBy('order_position', 'DESC'); // Дополнительная стабилизация сортировки

        // 8. Выполнение запроса
        // Примечание: если нужна пагинация, замените ->get() на ->paginate($data['per_page'] ?? 15)
        // и измените возвращаемый тип на \Illuminate\Pagination\LengthAwarePaginator
        $partners = $query->get();

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
    /**
     * @throws ValidationException
     */
    public function create(array $data): PartnerResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "url"    => "required|url",
            "slug"   => "required|string|max:255",
            "tags"   => "nullable|array",
            "tags.*" => "string|max:50",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 🆕 Ищем тенант (приложение) по slug, а не по uuid
        $urlPartner = Tenant::query()
            ->where("slug", $data["slug"])
            ->first();

        if (is_null($urlPartner)) {
            throw new HttpException(404, "Приложение-партнер не найдено в системе!");
        }

        $partner = Partner::query()
            ->where("tenant_id", $tenant->id)
            ->where("tenant_partner_id", $urlPartner->id)
            ->first();

        if (!is_null($partner)) {
            throw new HttpException(403, "Данное приложение уже является партнером!");
        }

        // 🆕 Исправлено использование несуществующей переменной $botPartner
        $partner = Partner::query()->create([
            'tenant_id'         => $tenant->id,
            'tenant_partner_id' => $urlPartner->id,
            'title'             => $urlPartner->name ?? $data['slug'],
            'description'       => $urlPartner->description ?? '',
            'image'             => $urlPartner->image ?? null,
            'is_active'         => true,
            'extra_charge'      => 0,
            'config'            => [],
            'legal_info'        => [],
            'tags'              => $data['tags'] ?? [], // 🆕 Сохраняем теги
        ]);

        return new PartnerResource($partner);
    }

    /**
     * @throws ValidationException
     */
    /**
     * Обновление данных партнёра и его настроек (адрес, координаты)
     *
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|null $file
     * @return \App\Http\Resources\PartnerResource
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function update(array $data, $file = null): PartnerResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        if (!$tenantUser) {
            throw new HttpException(401, 'Пользователь не авторизован');
        }

        // 🆕 1. Валидация
        $rules = [
            'id' => 'required|integer|exists:partners,id',
            'tenant_partner_id' => 'nullable|integer|exists:tenants,id', // Делаем nullable
            'url' => 'nullable|string', // 🆕
            'slug' => 'nullable|string|max:255', // 🆕
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_position' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
            'extra_charge' => 'nullable|numeric|min:0',
            'config' => 'nullable|string',
            'legal_info' => 'nullable|string',
            'tags' => 'nullable',
            'address' => 'nullable|string|max:255',
            'shop_coords' => 'nullable|string|max:50',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $partner = Partner::query()
            ->where('id', $validated['id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        // 🆕 ЛОГИКА СМЕНЫ ПРИЛОЖЕНИЯ-ПАРТНЁРА
        $newSlug = $validated['slug'] ?? null;
        $partnerTenant = null;

        if ($newSlug) {
            $newPartnerTenant = Tenant::where('slug', $newSlug)->first();
            if (!$newPartnerTenant) {
                throw new HttpException(404, "Приложение по новой ссылке не найдено в системе!");
            }

            // Если slug реально изменился
            if ($newPartnerTenant->id !== $partner->tenant_partner_id) {
                $alreadyPartner = Partner::where('tenant_id', $tenant->id)
                    ->where('tenant_partner_id', $newPartnerTenant->id)
                    ->exists();

                if ($alreadyPartner) {
                    throw new HttpException(403, "Это приложение уже добавлено в ваши партнёры!");
                }

                $partner->tenant_partner_id = $newPartnerTenant->id;
            }
            $partnerTenant = $newPartnerTenant;
        } else {
            // Если slug не передан, берем текущего партнера
            $partnerTenant = Tenant::find($partner->tenant_partner_id);
            if (!$partnerTenant && !empty($validated['tenant_partner_id'])) {
                $partnerTenant = Tenant::findOrFail($validated['tenant_partner_id']);
            } elseif (!$partnerTenant) {
                throw new HttpException(404, "Приложение-партнер не найдено!");
            }
        }

        // 2. Работа с файлами (Используем $partnerTenant, который может быть новым)
        $imageName = $partner->image;

        if ($file) {
            if ($partner->image && Str::contains($partner->image, '/storage/companies/')) {
                $oldPath = str_replace('/storage/', 'public/', $partner->image);
                \Illuminate\Support\Facades\Storage::disk('local')->delete($oldPath);
            }

            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $newFileName = 'companies/' . $partnerTenant->slug . '/' . Str::uuid() . '.' . $ext;

            $file->storeAs('public', $newFileName);
            $imageName = '/storage/' . $newFileName;
        }

        // 3. Нормализация типов данных
        $isActive = filter_var($validated['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $existingConfig = $partner->config ?? [];
        if (is_string($existingConfig)) $existingConfig = json_decode($existingConfig, true) ?? [];
        elseif (!is_array($existingConfig)) $existingConfig = [];

        $incomingConfig = $validated['config'] ?? null;
        if (is_string($incomingConfig)) $incomingConfig = json_decode($incomingConfig, true) ?? [];
        elseif (!is_array($incomingConfig)) $incomingConfig = [];

        $mergedConfig = array_merge($existingConfig, $incomingConfig);

        $legalInfo = $validated['legal_info'] ?? null;
        if (is_string($legalInfo)) $legalInfo = json_decode($legalInfo, true) ?? [];
        elseif (!is_array($legalInfo)) $legalInfo = [];

        $tags = $validated['tags'] ?? [];
        if (is_string($tags)) $tags = json_decode($tags, true) ?? [];
        elseif (!is_array($tags)) $tags = [];

        // 5. Обновление модели (Добавляем tenant_partner_id для сохранения изменений)
        $partner->update([
            'tenant_partner_id' => $partnerTenant->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'image' => $imageName,
            'order_position' => $validated['order_position'] ?? 0,
            'is_active' => $isActive,
            'extra_charge' => $validated['extra_charge'] ?? 0,
            'config' => $mergedConfig,
            'legal_info' => $legalInfo,
            'tags' => $tags,
        ]);

        // 6. Обновление настроек связанного Tenant
        $tenantSettingsToUpdate = [];
        if (array_key_exists('address', $validated)) $tenantSettingsToUpdate['address'] = $validated['address'];
        if (array_key_exists('shop_coords', $validated)) $tenantSettingsToUpdate['shop_coords'] = $validated['shop_coords'];

        if (!empty($tenantSettingsToUpdate)) {
            $this->mergeIntoMeta($partnerTenant, $tenantSettingsToUpdate);
        }

        return new PartnerResource($partner->fresh());
    }

    /**
     * Ваш хелпер для безопасного обновления meta
     */
    protected function mergeIntoMeta(Tenant $tenant, array $newData): void
    {
        $meta = $tenant->meta ?? [];

        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?? [];
        }

        $meta = array_replace_recursive($meta, $newData);
        $tenant->update(['meta' => $meta]);
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

        // ==========================================
        // 🆕 РАЗДЕЛЯЕМ ПОЛЯ ПО ТИПАМ
        // ==========================================

        // Булевые поля (программа лояльности)
        $booleanFields = [
            'is_active' => 'sometimes|boolean',
            'display_self' => 'sometimes|boolean',
        ];

        // Строковые поля
        $stringFields = [
            'address' => 'nullable|string|max:255',
            'shop_coords' => 'nullable|string|max:50',
        ];

        // UI-настройки (из модалки PartnersUiSettingsModal)
        $uiFields = [
            // Hero секция
            'hero.enabled' => 'sometimes|boolean',
            'hero.title' => 'nullable|string|max:255',
            'hero.subtitle' => 'nullable|string|max:500',
            'hero.search_placeholder' => 'nullable|string|max:100',
            'hero.bg_image_1' => 'nullable|image|max:2048',
            'hero.bg_image_2' => 'nullable|image|max:2048',
            'hero.bg_image_3' => 'nullable|image|max:2048',
            'hero.bg_image_4' => 'nullable|image|max:2048',
            'hero.bg_image_1_url' => 'nullable|string|max:500',
            'hero.bg_image_2_url' => 'nullable|string|max:500',
            'hero.bg_image_3_url' => 'nullable|string|max:500',
            'hero.bg_image_4_url' => 'nullable|string|max:500',

            // Категории
            'categories_title' => 'nullable|string|max:100',
            'categories' => 'nullable|string', // JSON-строка

            // Сервисы
            'services' => 'nullable|string', // JSON-строка

            // Фильтры
            'filters' => 'nullable|string', // JSON-строка
        ];

        // Объединяем все правила валидации
        $validationRules = array_merge($booleanFields, $stringFields, $uiFields);

        $validator = Validator::make($data, $validationRules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // ==========================================
        // 🆕 ПОЛУЧАЕМ ТЕКУЩИЕ НАСТРОЙКИ
        // ==========================================

        $meta = $tenant->meta ?? [];

        if (is_string($meta)) {
            $meta = (array) json_decode($meta, true);
        }

        $partnersConfig = $meta['partners'] ?? [];
        $uiConfig = $partnersConfig['ui'] ?? [];
        $heroConfig = $uiConfig['hero'] ?? [];

        // ==========================================
        // 🆕 ОБНОВЛЯЕМ БУЛЕВЫЕ ПОЛЯ (программа)
        // ==========================================

        foreach (array_keys($booleanFields) as $field) {
            if (array_key_exists($field, $data)) {
                $partnersConfig[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        // ==========================================
        // 🆕 ОБНОВЛЯЕМ СТРОКОВЫЕ ПОЛЯ
        // ==========================================

        foreach (array_keys($stringFields) as $field) {
            if (array_key_exists($field, $data)) {
                $partnersConfig[$field] = $data[$field];
            }
        }

        // ==========================================
        // 🆕 ЛОГИЧЕСКАЯ ПРОВЕРКА: display_self требует is_active
        // ==========================================

        if (($partnersConfig['display_self'] ?? false) && !($partnersConfig['is_active'] ?? false)) {
            $partnersConfig['display_self'] = false;
        }

        // ==========================================
        // 🆕 ОБНОВЛЯЕМ UI-НАСТРОЙКИ
        // ==========================================

        // Hero секция
        if (isset($data['hero'])) {
            $heroData = $data['hero'];

            // Булевые поля
            if (array_key_exists('enabled', $heroData)) {
                $heroConfig['enabled'] = filter_var($heroData['enabled'], FILTER_VALIDATE_BOOLEAN);
            }

            // Строковые поля
            foreach (['title', 'subtitle', 'search_placeholder'] as $textKey) {
                if (array_key_exists($textKey, $heroData)) {
                    $heroConfig[$textKey] = $heroData[$textKey];
                }
            }

            // Обработка изображений (файлы или URL)
            foreach (['bg_image_1', 'bg_image_2', 'bg_image_3', 'bg_image_4'] as $imgKey) {
                // Если пришёл файл — загружаем
                if (isset($data["hero_{$imgKey}"]) && $data["hero_{$imgKey}"] instanceof \Illuminate\Http\UploadedFile) {
                    $file = $data["hero_{$imgKey}"];
                    $path = $file->store("tenants/{$tenant->id}/partners/hero", 'public');
                    $heroConfig[$imgKey] = \Illuminate\Support\Facades\Storage::url($path);
                }
                // Если пришёл URL (существующая картинка) — сохраняем
                elseif (isset($data["hero_{$imgKey}_url"])) {
                    $heroConfig[$imgKey] = $data["hero_{$imgKey}_url"];
                }
                // Если файл в самом hero (альтернативный формат)
                elseif (isset($heroData[$imgKey]) && $heroData[$imgKey] instanceof \Illuminate\Http\UploadedFile) {
                    $file = $heroData[$imgKey];
                    $path = $file->store("tenants/{$tenant->id}/partners/hero", 'public');
                    $heroConfig[$imgKey] = \Illuminate\Support\Facades\Storage::url($path);
                }
            }

            $uiConfig['hero'] = $heroConfig;
        }

        // Заголовок категорий
        if (array_key_exists('categories_title', $data)) {
            $uiConfig['categories_title'] = $data['categories_title'];
        }

        // Категории (JSON)
        if (array_key_exists('categories', $data)) {
            $categories = $data['categories'];
            if (is_string($categories)) {
                $decoded = json_decode($categories, true);
                $uiConfig['categories'] = is_array($decoded) ? $decoded : [];
            } elseif (is_array($categories)) {
                $uiConfig['categories'] = $categories;
            }
            // Поддержка старого имени cuisines
            unset($uiConfig['cuisines']);
        }

        // Сервисы (JSON)
        if (array_key_exists('services', $data)) {
            $services = $data['services'];
            if (is_string($services)) {
                $decoded = json_decode($services, true);
                $uiConfig['services'] = is_array($decoded) ? $decoded : [];
            } elseif (is_array($services)) {
                $uiConfig['services'] = $services;
            }
        }

        // Фильтры (JSON)
        if (array_key_exists('filters', $data)) {
            $filters = $data['filters'];
            if (is_string($filters)) {
                $decoded = json_decode($filters, true);
                $uiConfig['filters'] = is_array($decoded) ? $decoded : [];
            } elseif (is_array($filters)) {
                $uiConfig['filters'] = $filters;
            }
        }

        // ==========================================
        // 🆕 СОБИРАЕМ И СОХРАНЯЕМ
        // ==========================================

        $partnersConfig['ui'] = $uiConfig;
        $meta['partners'] = $partnersConfig;

        $tenant->meta = $meta;
        $tenant->save();

        // ==========================================
        // 🆕 ПРИ ОТКЛЮЧЕНИИ ПРОГРАММЫ — ДЕАКТИВИРУЕМ ПАРТНЁРОВ
        // ==========================================

        if (!($partnersConfig['is_active'] ?? false)) {
            Partner::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return $meta['partners'];
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
