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
                'sku' => $product->sku,
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
            $syncedSkus = [];

            foreach ($products as $productData) {
                // SKU = внешний ID с платформы
                $sku = (string) ($productData['id'] ?? '');
                if ($sku) {
                    $syncedSkus[] = $sku;
                }

                $product = $this->syncProduct($tenant, $productData);

                if ($product->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            }

            // ✅ Опционально: деактивируем товары, которых нет в payload
            if ($deactivateMissing && !empty($syncedSkus)) {
                $stats['deactivated'] = Product::where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->whereNotIn('sku', $syncedSkus)
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
        // ✅ SKU = внешний ID с платформы
        $sku = (string) ($data['id'] ?? '');

        // Ищем существующий товар по SKU
        $product = null;
        if ($sku) {
            $product = Product::where('tenant_id', $tenant->id)
                ->where('sku', $sku)
                ->first();
        }

        $mappedData = $this->mapProductData($data, $sku);

        if ($product) {
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
     * Маппинг данных товара
     */
    protected function mapProductData(array $data, string $sku): array
    {
        return [
            'sku' => $sku, // ✅ Внешний ID с платформы
            'name' => $data['name'] ?? '',
            'price' => (float) ($data['price'] ?? 0),
            'old_price' => isset($data['old_price']) ? (float) $data['old_price'] : null,
            'description' => $data['description'] ?? '',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'in_stop_list' => (bool) ($data['in_stop_list'] ?? false),
            'images' => $this->mapImages($data['images'] ?? []),
        ];
    }

    /**
     * Маппинг картинок
     */
    protected function mapImages(array $images): array
    {
        return collect($images)->map(function ($img) {
            if (is_array($img)) {
                return [
                    'url' => $img['url'] ?? '',
                    'name' => $img['name'] ?? '',
                    'size' => $img['size'] ?? 0,
                ];
            }
            return ['url' => (string) $img, 'name' => '', 'size' => 0];
        })->all();
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

            // Ищем по external_id
            $category = null;
            if ($externalId) {
                $category = Category::where('tenant_id', $tenant->id)
                    ->where('external_id', $externalId)
                    ->first();
            }

            // Фоллбэк: по имени
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
     * Синхронизация атрибутов (полная замена)
     */
    protected function syncAttributes(Product $product, array $attributes): void
    {
        $product->attributes()->delete();

        foreach ($attributes as $index => $attrData) {
            if (empty($attrData['name'])) {
                continue;
            }

            ProductAttribute::create([
                'product_id' => $product->id,
                'name' => $attrData['name'],
                'value' => $attrData['value'] ?? '',
                'order_position' => $index,
            ]);
        }
    }

    /**
     * Синхронизация ингредиентов (сохраняем в config)
     */
    protected function syncIngredients(Product $product, array $ingredients): void
    {
        if (empty($ingredients)) {
            return;
        }

        $mapped = collect($ingredients)->map(function ($ing) {
            return [
                'id' => $ing['id'] ?? null,
                'name' => $ing['name'] ?? '',
            ];
        })->filter(fn($i) => !empty($i['name']))->values()->all();

        $config = $product->config ?? [];
        $config['ingredients'] = $mapped;
        $product->update(['config' => $config]);
    }
}
