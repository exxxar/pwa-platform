<?php

namespace App\Services;

use App\Models\Tenant\Category;
use App\Models\Tenant\Collection;
use App\Models\Tenant\CollectionCategory;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductAttribute;
use App\Models\Tenant\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookSyncService
{
    /**
     * Синхронизация одного товара
     */
    public function syncSingleProduct(Tenant $tenant, array $payload): array
    {
        if (empty($payload['product'])) {
            throw new \RuntimeException('Product data is missing in payload');
        }

        DB::beginTransaction();
        try {
            $product = $this->syncProduct($tenant, $payload['product']);
            DB::commit();

            Log::info('Webhook: product synced', [
                'tenant_id' => $tenant->id,
                'product_id' => $product->id,
                'external_id' => $product->external_id,
                'action' => $product->wasRecentlyCreated ? 'created' : 'updated',
            ]);

            return [
                'event' => 'product.updated',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'action' => $product->wasRecentlyCreated ? 'created' : 'updated',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Полная синхронизация workspace
     */
    public function syncFullWorkspace(Tenant $tenant, array $payload): array
    {
        $products = $payload['workspace']['products'] ?? [];
        $collections = $payload['workspace']['collections'] ?? [];

        DB::beginTransaction();
        try {
            // --- ШАГ 1. "Гасим" всё перед синхронизацией ---
            // Это помечает как "удаленные" (неактивные) все сущности,
            // которые не придут в текущем вебхуке.
            Product::where('tenant_id', $tenant->id)->update(['is_active' => false]);
            Collection::where('tenant_id', $tenant->id)->update(['is_active' => false]);

            $stats = [
                'products' => ['total' => count($products), 'created' => 0, 'updated' => 0],
                'collections' => ['total' => count($collections), 'created' => 0, 'updated' => 0],
            ];

            // --- ШАГ 2. Синхронизируем Товары ---
            // ВАЖНО: Сначала товары, так как коллекции будут ссылаться на них по external_id
            foreach ($products as $productData) {
                $product = $this->syncProduct($tenant, $productData);
                $stats['products'][$product->wasRecentlyCreated ? 'created' : 'updated']++;
            }

            // --- ШАГ 3. Синхронизируем Коллекции ---
            foreach ($collections as $collectionData) {
                $result = $this->syncCollection($tenant, $collectionData);
                $stats['collections'][$result['action']]++;
            }

            DB::commit();

            Log::info('Webhook: full sync completed', ['tenant_id' => $tenant->id, 'stats' => $stats]);

            return array_merge(['event' => 'workspace.sync'], $stats);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Синхронизация одной коллекции
     */
    /**
     * Синхронизация одной коллекции
     */
    protected function syncCollection(Tenant $tenant, array $data): array
    {
        $externalId = (string)($data['id'] ?? '');
        if (!$externalId) {
            throw new \RuntimeException('Collection external_id is missing');
        }

        $collection = Collection::firstOrNew([
            'external_id' => $externalId,
            'tenant_id' => $tenant->id,
        ]);

        $action = $collection->exists ? 'updated' : 'created';

        $collection->fill([
            'name' => $data['name'] ?? 'Без названия',
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'image' => $data['image'] ?? $data['image_url'] ?? null,
            'discount' => $data['discount_percent'] ?? $data['discount'] ?? 0,
            'order_position' => $data['sort_order'] ?? $data['order_position'] ?? 0,
            'type' => $data['type'] ?? 'manual',
            'pricing_type' => $data['pricing_type'] ?? 'sum',
            'fixed_price' => $data['fixed_price'] ?? null,
            'in_stop_list' => (bool)($data['in_stop_list'] ?? false),
            'config' => $data['config'] ?? null,
            'is_active' => (bool)($data['is_active'] ?? true),
        ]);

        $collection->save();

        // 🆕 Синхронизация категорий и товаров внутри коллекции
        $categoriesData = $data['collection_categories'] ?? [];

        // Fallback для плоской структуры (если вдруг придет старый формат без вложенности)
        if (empty($categoriesData) && !empty($data['products'])) {
            $categoriesData = [
                [
                    'category_id' => null,
                    'category_name' => 'Основные товары',
                    'selection_rule' => 'several',
                    'sort_order' => 0,
                    'products' => $data['products'],
                ]
            ];
        }

        $this->syncCollectionCategories($collection, $tenant, $categoriesData);

        return ['action' => $action];
    }

    protected function syncCollectionCategories(Collection $collection, Tenant $tenant, array $categoriesData): void
    {
        $processedCollectionCategoryIds = [];

        foreach ($categoriesData as $catData) {
            $catExternalId = (string)($catData['category_id'] ?? ''); // Это external_id глобальной категории
            $catName = $catData['category_name'] ?? 'Без названия';

            // 1. Находим локальную категорию по external_id
            $localCategory = null;
            if ($catExternalId) {
                $localCategory = Category::where('tenant_id', $tenant->id)
                    ->where('external_id', $catExternalId)
                    ->first();
            }

            $localCategoryId = $localCategory ? $localCategory->id : null;

            // 2. Находим или создаем запись CollectionCategory
            // Используем category_id для поиска, если он найден. Иначе ищем по имени.
            $matchAttributes = ['collection_id' => $collection->id];
            if ($localCategoryId) {
                $matchAttributes['category_id'] = $localCategoryId;
            } else {
                $matchAttributes['category_name'] = $catName;
            }

            $collectionCategory = $collection->collectionCategories()->updateOrCreate(
                $matchAttributes,
                [
                    'category_name' => $catName,
                    'selection_rule' => $catData['selection_rule'] ?? 'one',
                    'sort_order' => $catData['sort_order'] ?? 0,
                ]
            );

            $processedCollectionCategoryIds[] = $collectionCategory->id;

            // 3. Синхронизация товаров для этой категории коллекции
            $productsData = $catData['products'] ?? [];
            $this->syncProductsForCollectionCategory($collectionCategory, $tenant, $productsData);
        }

        // 4. Удаляем категории коллекции, которых больше нет в вебхуке
        $collection->collectionCategories()
            ->whereNotIn('id', $processedCollectionCategoryIds)
            ->each(function ($cc) {
                $cc->products()->detach();
                $cc->delete();
            });
    }

    protected function syncProductsForCollectionCategory(CollectionCategory $collectionCategory, Tenant $tenant, array $productsData): void
    {
        $syncData = [];

        foreach ($productsData as $prodData) {
            $productExternalId = (string)($prodData['id'] ?? '');

            if (!$productExternalId) {
                continue;
            }

            // Находим локальный товар по external_id
            $localProduct = Product::where('tenant_id', $tenant->id)
                ->where('external_id', $productExternalId)
                ->first();

            if ($localProduct) {
                $syncData[$localProduct->id] = [
                    'sort_order' => $prodData['sort_order'] ?? 0,
                ];
            }
        }

        // sync() автоматически добавит новые связи, обновит sort_order и удалит старые,
        // которых нет в $syncData
        $collectionCategory->products()->sync($syncData);
    }

    /**
     * Привязка товаров к коллекции
     * Так как структура БД требует collection_categories, мы создаем/используем
     * одну техническую категорию ("main") для хранения товаров этой коллекции.
     */
    /**
     * Привязка товаров к коллекции через CollectionCategory
     */
    /**
     * Привязка товаров к коллекции с учетом структуры collection_categories
     */
    protected function syncCollectionProducts(Collection $collection, array $data): void
    {
        $collectionCategories = $data['collection_categories'] ?? [];
        $directProducts = $data['direct_products'] ?? [];

        // Если в вебхуке вообще нет данных о товарах/категориях, очищаем коллекцию
        if (empty($collectionCategories) && empty($directProducts)) {
            foreach ($collection->collectionCategories as $cat) {
                $cat->products()->detach();
                $cat->delete();
            }
            return;
        }

        $processedCategoryExternalIds = [];

        // 1. Синхронизируем категории коллекции и их товары
        foreach ($collectionCategories as $catData) {
            // Используем ID категории из внешней системы. Если его нет, генерируем хеш от имени
            $catExternalId = isset($catData['id']) ? (string)$catData['id'] : 'temp_' . md5($catData['category_name'] ?? 'unknown');
            $processedCategoryExternalIds[] = $catExternalId;

            // Находим или создаем запись CollectionCategory
            // ВАЖНО: Убедитесь, что в таблице collection_categories есть колонка external_id (см. примечание ниже)
            $collectionCategory = $collection->collectionCategories()->firstOrCreate(
                ['external_id' => $catExternalId],
                [
                    'category_id' => $catData['category_id'] ?? null,
                    'category_name' => $catData['category_name'] ?? 'Без названия',
                    'selection_rule' => $catData['selection_rule'] ?? 'one',
                    'sort_order' => $catData['sort_order'] ?? 0,
                ]
            );

            // Обновляем данные, если категория уже существовала
            $collectionCategory->update([
                'category_id' => $catData['category_id'] ?? $collectionCategory->category_id,
                'category_name' => $catData['category_name'] ?? $collectionCategory->category_name,
                'selection_rule' => $catData['selection_rule'] ?? $collectionCategory->selection_rule,
                'sort_order' => $catData['sort_order'] ?? $collectionCategory->sort_order,
            ]);

            // Извлекаем external_id продуктов внутри этой категории
            $productIdsInCat = [];
            if (isset($catData['products']) && is_array($catData['products'])) {
                foreach ($catData['products'] as $prod) {
                    $prodId = is_array($prod) ? ($prod['id'] ?? null) : $prod;
                    if ($prodId) {
                        $productIdsInCat[] = (string)$prodId;
                    }
                }
            }

            // Находим локальные ID этих продуктов в нашей БД
            $localIds = Product::where('tenant_id', $collection->tenant_id)
                ->whereIn('external_id', $productIdsInCat)
                ->pluck('id')
                ->all();

            // Синхронизируем продукты внутри этой конкретной категории
            $syncData = [];
            foreach ($localIds as $index => $pid) {
                $syncData[$pid] = ['sort_order' => $index];
            }
            $collectionCategory->products()->sync($syncData);
        }

        // 2. Синхронизируем прямые товары (direct_products), если они есть
        if (!empty($directProducts)) {
            $directCatExternalId = 'direct_products_group';
            $processedCategoryExternalIds[] = $directCatExternalId;

            $directCategory = $collection->collectionCategories()->firstOrCreate(
                ['external_id' => $directCatExternalId],
                [
                    'category_name' => 'Прямые товары',
                    'selection_rule' => 'all',
                    'sort_order' => 999,
                ]
            );

            $directProductIds = [];
            foreach ($directProducts as $prod) {
                $prodId = is_array($prod) ? ($prod['id'] ?? null) : $prod;
                if ($prodId) {
                    $directProductIds[] = (string)$prodId;
                }
            }

            $localDirectIds = Product::where('tenant_id', $collection->tenant_id)
                ->whereIn('external_id', $directProductIds)
                ->pluck('id')
                ->all();

            $syncData = [];
            foreach ($localDirectIds as $index => $pid) {
                $syncData[$pid] = ['sort_order' => $index];
            }
            $directCategory->products()->sync($syncData);
        }

        // 3. Очищаем категории коллекции, которые были удалены во внешней системе
        // (Они есть в БД, но их external_id не было в текущем вебхуке)
        $collection->collectionCategories()
            ->whereNotIn('external_id', $processedCategoryExternalIds)
            ->each(function ($cat) {
                $cat->products()->detach();
                $cat->delete();
            });
    }

    /**
     * Синхронизация одного товара
     */
    protected function syncProduct(Tenant $tenant, array $data): Product
    {
        $externalId = (string)($data['id'] ?? '');
        $sku = $data['sku'] ?? null;

        // 🔍 Поиск существующего товара
        $product = null;

        if ($externalId) {
            $product = Product::where('tenant_id', $tenant->id)
                ->where('external_id', $externalId)
                ->first();
        }

        if (!$product && $sku) {
            $product = Product::where('tenant_id', $tenant->id)
                ->where('sku', $sku)
                ->first();
        }

        // ✅ ВСЕ поля для обновления/создания
        $mappedData = $this->mapProductData($data, $externalId);

        if ($product) {
            // ✅ Полное обновление всех полей
            $product->update($mappedData);
        } else {
            $product = Product::create(array_merge($mappedData, [
                'tenant_id' => $tenant->id,
            ]));
        }

        // Синхронизируем связанные данные
        $this->syncCategories($tenant, $product, $data['categories'] ?? []);
        $this->syncAttributes($product, $data['attributes'] ?? []);
        $this->syncIngredients($product, $data['ingredients'] ?? []);

        return $product;
    }

    /**
     * ✅ Маппинг ВСЕХ полей товара
     */
    protected function mapProductData(array $data, string $externalId): array
    {
        return [
            'external_id' => $externalId,
            'name' => $data['name'] ?? '',
            'price' => (float)($data['price'] ?? 0),
            'old_price' => isset($data['old_price']) ? (float)$data['old_price'] : null,
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? '',
            'is_active' => (bool)($data['is_active'] ?? true),
            'in_stop_list' => (bool)($data['in_stop_list'] ?? false),
            'images' => $this->mapImages($data['images'] ?? []),
        ];
    }

    /**
     * ✅ Маппинг картинок с автоматическим добавлением хоста
     *
     * Принимает:
     *   - массив массивов [['url' => '...', 'name' => '...'], ...]
     *   - массив строк   ['images/photo.jpg', ...]
     *   - одну строку    'images/photo.jpg'
     */
    protected function mapImages($images): array
    {
        if (empty($images)) {
            return [];
        }

        // Если пришла одна строка — обернём в массив
        if (is_string($images)) {
            $images = [['url' => $images, 'name' => '']];
        }

        return collect($images)
            ->filter()
            ->map(function ($img) {
                // Нормализуем входные данные в массив
                if (is_string($img)) {
                    $img = ['url' => $img, 'name' => ''];
                }

                $url = $img['url'] ?? '';
                $name = $img['name'] ?? '';

                // Формируем полный URL
                $fullUrl = $this->resolveImageUrl($url);

                return [
                    'url' => $fullUrl,
                    'name' => $name ?: basename(parse_url($fullUrl, PHP_URL_PATH) ?? ''),
                ];
            })
            ->filter(fn($img) => !empty($img['url'])) // Убираем пустые записи
            ->values()
            ->all();
    }


    /**
     * ✅ Добавление хоста текущего запроса к относительному URL картинки
     */
    /**
     * ✅ Формирование полного URL картинки с фиксированным хостом из конфига
     */
    protected function resolveImageUrl(string $url): string
    {
        $url = trim($url);

        if (empty($url)) {
            return '';
        }

        // ✅ Если URL уже абсолютный — возвращаем как есть (на случай, если уже с CDN)
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        // ✅ data:image / base64 — не трогаем
        if (str_starts_with($url, 'data:')) {
            return $url;
        }

        // ✅ Берём хост из конфига (читается из .env)
        $host = rtrim(config('app.products_host', 'https://products.mypwa.ru'), '/');

        // Убираем ведущий слэш у относительного пути
        $path = ltrim($url, '/');

        return $host . '/' . $path;
    }

    /**
     * ✅ Синхронизация категорий с ПОЛНЫМ обновлением полей
     */
    protected function syncCategories(Tenant $tenant, Product $product, array $categories): void
    {
        $categoryIds = [];

        foreach ($categories as $catData) {
            $externalId = (string)($catData['id'] ?? '');
            $name = $catData['name'] ?? '';

            if (!$externalId && !$name) {
                continue;
            }

            // 🔍 Поиск категории
            $category = null;
            if ($externalId) {
                $category = Category::where('tenant_id', $tenant->id)
                    ->where('external_id', $externalId)
                    ->first();
            }

            if (!$category && $name) {
                $category = Category::where('tenant_id', $tenant->id)
                    ->where('name', $name)
                    ->first();
            }

            if (!$category) {
                // ✅ Создание новой категории
                $category = Category::create([
                    'tenant_id' => $tenant->id,
                    'name' => $name ?: "Категория #{$externalId}",
                    'external_id' => $externalId,
                    'is_active' => true,
                    'order_position' => 0,
                ]);
            } else {
                // ✅ ПОЛНОЕ обновление существующей категории
                $updateData = [];

                // Обновляем name, если он изменился
                if ($name && $category->name !== $name) {
                    $updateData['name'] = $name;
                }

                // Привязываем external_id, если его не было
                if ($externalId && !$category->external_id) {
                    $updateData['external_id'] = $externalId;
                }

                if (!empty($updateData)) {
                    $category->update($updateData);
                }
            }

            $categoryIds[] = $category->id;
        }

        $product->categories()->sync($categoryIds);
    }

    /**
     * Синхронизация атрибутов (полная замена)
     */
    protected function syncAttributes(Product $product, array $attributes): void
    {
        $product->attributes()
            ->where(function ($q) {
                $q->whereNull('section')
                    ->orWhere('section', '!=', 'ingredients');
            })
            ->delete();

        foreach ($attributes as $index => $attrData) {
            if (empty($attrData['name'])) {
                continue;
            }

            ProductAttribute::create([
                'product_id' => $product->id,
                'name' => $attrData['name'],
                'value' => $attrData['value'] ?? '',
                'section' => null,
                'order_position' => $index,
            ]);
        }
    }

    /**
     * Синхронизация ингредиентов через ProductAttribute
     */
    protected function syncIngredients(Product $product, array $ingredients): void
    {
        $product->attributes()
            ->where('section', 'ingredients')
            ->delete();

        foreach ($ingredients as $index => $ingData) {
            if (empty($ingData['name'])) {
                continue;
            }

            ProductAttribute::create([
                'product_id' => $product->id,
                'name' => $ingData['name'],
                'value' => $ingData['id'] ?? '',
                'section' => 'ingredients',
                'order_position' => $index,
            ]);
        }
    }
}
