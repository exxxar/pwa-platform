<?php

namespace App\Services\Admin\TenantData;

use App\Models\Tenant\Product;
use App\Models\Tenant\Category;

class ProductService
{
    /**
     * Получить список товаров
     */
    public function getProducts(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Product::query()->with(['categories', 'tenant']);

        // Фильтр по tenant_id
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Фильтр по активности
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Фильтр по стоп-листу
        if (isset($filters['in_stop_list'])) {
            $query->where('in_stop_list', $filters['in_stop_list']);
        }

        // Фильтр по категории
        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        // Поиск
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Создать товар
     */
    public function createProduct(array $data): Product
    {
        $product = Product::create($data);

        // Привязка к категориям
        if (!empty($data['category_ids'])) {
            $product->categories()->sync($data['category_ids']);
        }

        return $product->load('categories');
    }

    /**
     * Обновить товар
     */
    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);

        if (isset($data['category_ids'])) {
            $product->categories()->sync($data['category_ids']);
        }

        return $product->fresh()->load('categories');
    }

    /**
     * Удалить товар (soft delete)
     */
    public function deleteProduct(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Переключить стоп-лист
     */
    public function toggleStopList(Product $product): Product
    {
        $product->update(['in_stop_list' => !$product->in_stop_list]);
        return $product;
    }

    /**
     * Переключить активность
     */
    public function toggleActive(Product $product): Product
    {
        $product->update(['is_active' => !$product->is_active]);
        return $product;
    }
}
