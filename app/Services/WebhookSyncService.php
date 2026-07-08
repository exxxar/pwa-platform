<?php

namespace App\Services;

use App\Models\Tenant\Category;
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
    public function syncFullWorkspace(Tenant $tenant, array $payload, bool $deactivateMissing = false): array
    {
        $products = $payload['workspace']['products'] ?? [];

        DB::beginTransaction();
        try {
            $stats = ['total' => count($products), 'created' => 0, 'updated' => 0, 'deactivated' => 0];
            $syncedExternalIds = [];

            foreach ($products as $productData) {
                $externalId = (string) ($productData['id'] ?? '');
                if ($externalId) {
                    $syncedExternalIds[] = $externalId;
                }

                $product = $this->syncProduct($tenant, $productData);

                if ($product->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            }

            if ($deactivateMissing && !empty($syncedExternalIds)) {
                $stats['deactivated'] = Product::where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->whereNotIn('external_id', $syncedExternalIds)
                    ->update(['is_active' => false]);
            }

            DB::commit();

            Log::info('Webhook: full sync completed', [
                'tenant_id' => $tenant->id,
                'stats' => $stats,
            ]);

            return array_merge(['event' => 'workspace.sync'], $stats);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Синхронизация одного товара
     */
    protected function syncProduct(Tenant $tenant, array $data): Product
    {
        $externalId = (string) ($data['id'] ?? '');
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
            'price' => (float) ($data['price'] ?? 0),
            'old_price' => isset($data['old_price']) ? (float) $data['old_price'] : null,
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? '',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'in_stop_list' => (bool) ($data['in_stop_list'] ?? false),
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
            $externalId = (string) ($catData['id'] ?? '');
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
