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

            // ✅ Опционально: деактивируем товары, которых нет в payload
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
     * ✅ Синхронизация одного товара
     * Поиск: external_id → sku → создание нового
     */
    protected function syncProduct(Tenant $tenant, array $data): Product
    {
        $externalId = (string) ($data['id'] ?? '');
        $sku = $data['sku'] ?? null;

        // 🔍 Ищем существующий товар
        $product = null;

        // Приоритет 1: по external_id
        if ($externalId) {
            $product = Product::where('tenant_id', $tenant->id)
                ->where('external_id', $externalId)
                ->first();
        }

        // Приоритет 2: по SKU (фоллбэк)
        if (!$product && $sku) {
            $product = Product::where('tenant_id', $tenant->id)
                ->where('sku', $sku)
                ->first();
        }

        $mappedData = $this->mapProductData($data, $externalId);

        if ($product) {
            // ✅ Обновляем существующий товар
            $product->update($mappedData);
        } else {
            // ✅ Создаём новый товар
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
     * Маппинг данных товара
     */
    protected function mapProductData(array $data, string $externalId): array
    {
        return [
            'external_id' => $externalId, // ✅ ID с платформы
            'name' => $data['name'] ?? '',
            'price' => (float) ($data['price'] ?? 0),
            'old_price' => isset($data['old_price']) ? (float) $data['old_price'] : null,
            'sku' => $data['sku'] ?? null, // ✅ SKU остаётся отдельным полем
            'description' => $data['description'] ?? '',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'in_stop_list' => (bool) ($data['in_stop_list'] ?? false),
            'images' => $this->mapImages($data['images'] ?? []),
        ];
    }

    /**
     * ✅ Маппинг картинок — возвращаем массив URL-строк
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
     * Синхронизация категорий
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
                $category = Category::create([
                    'tenant_id' => $tenant->id,
                    'name' => $name ?: "Категория #{$externalId}",
                    'external_id' => $externalId,
                    'is_active' => true,
                    'order_position' => 0,
                ]);
            } elseif ($externalId && !$category->external_id) {
                $category->update(['external_id' => $externalId]);
            }

            $categoryIds[] = $category->id;
        }

        $product->categories()->sync($categoryIds);
    }

    /**
     * ✅ Синхронизация атрибутов + ингредиентов
     * Атрибуты: section = null (или 'attributes')
     * Ингридиенты: section = 'ingredients'
     */
    protected function syncAttributes(Product $product, array $attributes): void
    {
        // Удаляем только обычные атрибуты (не ингредиенты)
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
                'section' => null, // Обычные атрибуты
                'order_position' => $index,
            ]);
        }
    }

    /**
     * ✅ Синхронизация ингредиентов через ProductAttribute
     * section = 'ingredients'
     */
    protected function syncIngredients(Product $product, array $ingredients): void
    {
        // Удаляем старые ингредиенты
        $product->attributes()
            ->where('section', 'ingredients')
            ->delete();

        // Создаём новые
        foreach ($ingredients as $index => $ingData) {
            if (empty($ingData['name'])) {
                continue;
            }

            ProductAttribute::create([
                'product_id' => $product->id,
                'name' => $ingData['name'],
                'value' => $ingData['id'] ?? '', // ID ингредиента с платформы
                'section' => 'ingredients', // ✅ Маркер секции
                'order_position' => $index,
            ]);
        }
    }
}
