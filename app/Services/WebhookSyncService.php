<?php

namespace App\Services;

use App\Models\Tenant\Category;
use App\Models\Tenant\Collection;
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
    protected function syncCollection(Tenant $tenant, array $data): array
    {
        $externalId = (string) ($data['id'] ?? '');
        if (!$externalId) {
            throw new \RuntimeException('Collection external_id is missing');
        }

        // Ищем существующую или создаем новую
        $collection = Collection::firstOrNew([
            'external_id' => $externalId,
            'tenant_id' => $tenant->id,
        ]);

        $action = $collection->exists ? 'updated' : 'created';

        // Маппинг данных
        // Поддерживаем разные варианты названий полей из вебхука
        $collection->fill([
            'name' => $data['name'] ?? 'Без названия',
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'image' => $data['image'] ?? $data['image_url'] ?? null,
            'discount' => $data['discount'] ?? 0,
            'order_position' => $data['sort_order'] ?? $data['order_position'] ?? 0,
            'type' => $data['type'] ?? 'manual',
            'pricing_type' => $data['pricing_type'] ?? 'sum',
            'fixed_price' => $data['fixed_price'] ?? null,
            'in_stop_list' => (bool) ($data['in_stop_list'] ?? false),
            'config' => $data['config'] ?? null,
            // ВОССТАНАВЛИВАЕМ АКТИВНОСТЬ
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $collection->save();

        // Синхронизация товаров внутри коллекции
        $productsInput = $data['products'] ?? [];
        $this->syncCollectionProducts($collection, $productsInput);

        return ['action' => $action];
    }

    /**
     * Привязка товаров к коллекции
     * Так как структура БД требует collection_categories, мы создаем/используем
     * одну техническую категорию ("main") для хранения товаров этой коллекции.
     */
    /**
     * Привязка товаров к коллекции через CollectionCategory
     */
    protected function syncCollectionProducts(Collection $collection, array $productsInput): void
    {
        // 1. Нормализуем вход: достаем external_id товаров
        $externalIds = collect($productsInput)->map(function ($item) {
            return is_array($item) ? ($item['id'] ?? null) : $item;
        })->filter()->values()->all();

        // 2. Находим реальные ID товаров в нашей БД
        $localProductIds = Product::where('tenant_id', $collection->tenant_id)
            ->whereIn('external_id', $externalIds)
            ->pluck('id')
            ->all();

        // 3. Получаем или создаем "дефолтную" группу для этой коллекции
        // Используем вашу модель CollectionCategory
        $mainCategory = $collection->collectionCategories()->firstOrCreate(
            ['category_name' => 'sync_default_group'], // Технический идентификатор группы
            [
                'category_id' => null, // Пустая связь с глобальной категорией
                'selection_rule' => 'all',
                'sort_order' => 0,
            ]
        );

        // 4. Синхронизируем pivot-таблицу collection_category_product
        $syncData = [];
        foreach ($localProductIds as $index => $pid) {
            $syncData[$pid] = ['sort_order' => $index];
        }

        // Метод sync() автоматически удалит старые связи и добавит новые с указанным sort_order
        $mainCategory->products()->sync($syncData);
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
     * Маппинг картинок — массив URL-строк
     */
    protected function mapImages(array $images): array
    {
        return collect($images)
            ->map(function ($img) {
                if (is_array($img)) {
                    return $img['url'] ?? null;
                }
                return is_string($img) ? $img : null;
            })
            ->filter()
            ->values()
            ->all();
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
