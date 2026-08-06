<?php

namespace App\Services;

use App\Http\Resources\CollectionCollection;
use App\Http\Resources\CollectionResource;
use App\Models\Tenant\Category;
use App\Models\Tenant\Collection;
use App\Models\Tenant\CollectionCategory;
use App\Models\Tenant\CollectionCategoryProduct;
use App\Models\Tenant\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CollectionService
{
    public static function call(): self
    {
        return app(self::class);
    }

    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    // ==========================================
    // СПИСКИ
    // ==========================================

    public function list(?string $search = null, ?array $filters = null, ?int $size = null): CollectionCollection
    {
        $tenant = app('tenant');
        $size = $size ?? config('app.results_per_page', 20);

        $query = Collection::query()
            ->with(['collectionCategories' => function ($q) {
                $q->orderBy('sort_order')->orderBy('id');
            }])
            ->where('tenant_id', $tenant->id);

        // 🆕 Безопасная проверка булевых значений
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['in_stop_list'])) {
            $query->where('in_stop_list', filter_var($filters['in_stop_list'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        $collections = $query
            ->orderBy('order_position')
            ->orderBy('created_at', 'desc')
            ->paginate($size);

        return new CollectionCollection($collections);
    }

    public function activeList(?int $partnerId = null): CollectionCollection
    {
        $tenant = app('tenant');
        $tenantId = $partnerId ?? $tenant->id;

        $collections = Collection::query()
            ->with(['collectionCategories' => function ($q) {
                $q->with(['products' => function ($pq) {
                    $pq->where('in_stop_list', false)
                        ->whereNull('deleted_at')
                        ->orderBy('collection_category_product.sort_order');
                }])
                    ->orderBy('sort_order');
            }])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('in_stop_list', false)
            ->orderBy('order_position')
            ->orderBy('created_at', 'desc')
            ->get();



        return new CollectionCollection($collections);
    }

    public function show(int $id,?int $partnerId = null): CollectionResource
    {
        $tenant = app('tenant');

        $collection = Collection::query()
            ->with([
                'collectionCategories' => function ($q) {
                    $q->with(['category', 'products' => function ($pq) {
                        $pq->where('in_stop_list', false)
                            ->whereNull('deleted_at')
                            ->orderBy('collection_category_product.sort_order');
                    }])
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->where('tenant_id', $partnerId ?? $tenant->id)
            ->where('id', $id)
            ->first();



        if (!$collection) {
            throw new HttpException(404, 'Коллекция не найдена');
        }

        return new CollectionResource($collection);
    }

    // ==========================================
    // CRUD
    // ==========================================

    public function createOrUpdate(array $data, $uploadedImage = null): CollectionResource
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'type' => 'sometimes|string|in:manual,automatic',
            'pricing_type' => 'sometimes|string|in:sum,fixed',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $imageUrl = $data['image'] ?? null;
        if ($uploadedImage) {
            $path = $uploadedImage->store("public/tenants/{$tenant->id}/collections", 'public');
            $imageUrl = Storage::url($path);
        }

        $collectionId = $data['id'] ?? null;

        // 🆕 Более надежный парсинг config
        $parsedConfig = null;
        if (!empty($data['config'])) {
            if (is_array($data['config'])) {
                $parsedConfig = $data['config'];
            } elseif (is_string($data['config'])) {
                $parsedConfig = json_decode($data['config'], true) ?: null;
            }
        }

        $fields = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'type' => $data['type'] ?? 'manual',
            'pricing_type' => $data['pricing_type'] ?? 'sum',
            'fixed_price' => $data['fixed_price'] ?? null,
            'discount' => $data['discount'] ?? 0,
            'image' => $imageUrl,
            'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'in_stop_list' => filter_var($data['in_stop_list'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'order_position' => $data['order_position'] ?? 0,
            'config' => $parsedConfig,
        ];

        if ($collectionId) {
            $collection = Collection::query()
                ->where('tenant_id', $tenant->id)
                ->findOrFail($collectionId);
            $collection->update($fields);
        } else {
            $fields['tenant_id'] = $tenant->id;
            $collection = Collection::query()->create($fields);
        }

        if (isset($data['categories']) && is_array($data['categories'])) {
            $this->syncCategories($collection, $data['categories']);
        }

        return new CollectionResource($collection->fresh([
            'collectionCategories.products',
        ]));
    }

    public function destroy(int $id): CollectionResource
    {
        $tenant = app('tenant');

        $collection = Collection::query()
            ->with('collectionCategories.products')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        $snapshot = $collection->replicate(); // Сохраняем снимок для возврата в Resource

        DB::transaction(function () use ($collection) {
            foreach ($collection->collectionCategories as $cat) {
                $cat->products()->detach();
                $cat->delete();
            }
            $collection->delete();
        });

        return new CollectionResource($snapshot);
    }

    public function toggleActive(int $id): CollectionResource
    {
        $tenant = app('tenant');
        $collection = Collection::query()->where('tenant_id', $tenant->id)->findOrFail($id);

        $collection->is_active = !$collection->is_active;
        $collection->save();

        return new CollectionResource($collection);
    }

    public function toggleStopList(int $id): CollectionResource
    {
        $tenant = app('tenant');
        $collection = Collection::query()->where('tenant_id', $tenant->id)->findOrFail($id);

        $collection->in_stop_list = !$collection->in_stop_list;
        $collection->save();

        return new CollectionResource($collection);
    }

    // ==========================================
    // КАТЕГОРИИ ВНУТРИ КОЛЛЕКЦИИ
    // ==========================================

    protected function syncCategories(Collection $collection, array $categories): void
    {
        $existingIds = [];

        foreach ($categories as $index => $catData) {
            $catId = $catData['id'] ?? null;

            $catFields = [
                'collection_id' => $collection->id,
                'category_id' => $catData['category_id'] ?? 0,
                'category_name' => $catData['category_name'] ?? 'Категория',
                'selection_rule' => $catData['selection_rule'] ?? 'one',
                'sort_order' => $catData['sort_order'] ?? $index,
            ];

            if ($catId) {
                // 🛡️ Безопасность: обновляем только если категория принадлежит этой коллекции
                $cat = CollectionCategory::query()->find($catId);
                if ($cat && $cat->collection_id === $collection->id) {
                    $cat->update($catFields);
                } else {
                    $cat = CollectionCategory::query()->create($catFields);
                }
            } else {
                $cat = CollectionCategory::query()->create($catFields);
            }

            $existingIds[] = $cat->id;

            if (isset($catData['products']) && is_array($catData['products'])) {
                $this->syncProductsForCategory($cat, $catData['products']);
            }
        }

        // Удаляем категории, которых нет в payload
        CollectionCategory::query()
            ->where('collection_id', $collection->id)
            ->whereNotIn('id', $existingIds)
            ->each(function ($cat) {
                $cat->products()->detach();
                $cat->delete();
            });
    }

    public function addCategory(int $collectionId, array $data): CollectionResource
    {
        $tenant = app('tenant');
        $collection = Collection::query()->where('tenant_id', $tenant->id)->findOrFail($collectionId);

        $validator = Validator::make($data, [
            'category_name' => 'required|string|max:255',
            'selection_rule' => 'sometimes|in:one,all,several',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $category = CollectionCategory::query()->create([
            'collection_id' => $collection->id,
            'category_id' => $data['category_id'] ?? 0,
            'category_name' => $data['category_name'],
            'selection_rule' => $data['selection_rule'] ?? 'one',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        if (!empty($data['products'])) {
            $this->syncProductsForCategory($category, $data['products']);
        }

        return new CollectionResource($collection->fresh(['collectionCategories.products']));
    }

    public function updateCategory(int $categoryId, array $data): CollectionResource
    {
        $tenant = app('tenant');
        $category = CollectionCategory::query()
            ->whereHas('collection', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->findOrFail($categoryId);

        $category->update([
            'category_name' => $data['category_name'] ?? $category->category_name,
            'category_id' => $data['category_id'] ?? $category->category_id,
            'selection_rule' => $data['selection_rule'] ?? $category->selection_rule,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        if (isset($data['products']) && is_array($data['products'])) {
            $this->syncProductsForCategory($category, $data['products']);
        }

        return new CollectionResource($category->collection->fresh(['collectionCategories.products']));
    }

    public function removeCategory(int $categoryId): CollectionResource
    {
        $tenant = app('tenant');
        $category = CollectionCategory::query()
            ->whereHas('collection', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->findOrFail($categoryId);

        $collection = $category->collection;

        $category->products()->detach();
        $category->delete();

        return new CollectionResource($collection->fresh(['collectionCategories.products']));
    }

    // ==========================================
    // ТОВАРЫ В КАТЕГОРИЯХ
    // ==========================================

    protected function syncProductsForCategory(CollectionCategory $category, array $products): void
    {
        $syncData = [];
        foreach ($products as $index => $product) {
            $productId = is_array($product) ? ($product['id'] ?? $product['product_id'] ?? null) : $product;
            if ($productId) {
                $syncData[$productId] = [
                    'sort_order' => is_array($product) ? ($product['sort_order'] ?? $index) : $index,
                ];
            }
        }
        $category->products()->sync($syncData);
    }

    public function addProductsToCategory(int $categoryId, array $productIds): CollectionResource
    {
        $tenant = app('tenant');
        $category = CollectionCategory::query()
            ->whereHas('collection', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->findOrFail($categoryId);

        $existing = $category->products()->pluck('product_id')->toArray();
        $maxSort = CollectionCategoryProduct::query()
            ->where('collection_category_id', $categoryId)
            ->max('sort_order') ?? 0;

        foreach ($productIds as $i => $pid) {
            if (!in_array($pid, $existing)) {
                $category->products()->attach($pid, [
                    'sort_order' => $maxSort + $i + 1,
                ]);
            }
        }

        return new CollectionResource($category->collection->fresh(['collectionCategories.products']));
    }

    public function removeProductFromCategory(int $categoryId, int $productId): CollectionResource
    {
        $tenant = app('tenant');
        $category = CollectionCategory::query()
            ->whereHas('collection', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->findOrFail($categoryId);

        $category->products()->detach($productId);

        return new CollectionResource($category->collection->fresh(['collectionCategories.products']));
    }

    public function reorderProducts(int $categoryId, array $order): CollectionResource
    {
        $tenant = app('tenant');
        $category = CollectionCategory::query()
            ->whereHas('collection', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->findOrFail($categoryId);

        // Примечание: для очень больших списков (>100 товаров) лучше использовать bulk update через CASE,
        // но для административных задач цикл вполне допустим и сохраняет читаемость кода.
        foreach ($order as $index => $productId) {
            CollectionCategoryProduct::query()
                ->where('collection_category_id', $categoryId)
                ->where('product_id', $productId)
                ->update(['sort_order' => $index]);
        }

        return new CollectionResource($category->collection->fresh(['collectionCategories.products']));
    }

    // ==========================================
    // МАССОВЫЕ ОПЕРАЦИИ
    // ==========================================

    public function removeAll(): void
    {
        $tenant = app('tenant');
        $collections = Collection::query()->where('tenant_id', $tenant->id)->get();

        DB::transaction(function () use ($collections) {
            foreach ($collections as $collection) {
                foreach ($collection->collectionCategories as $cat) {
                    $cat->products()->detach();
                    $cat->delete();
                }
                $collection->delete();
            }
        });
    }

    public function duplicate(int $id): CollectionResource
    {
        $tenant = app('tenant');
        $collection = Collection::query()
            ->with('collectionCategories.products')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        $newCollection = DB::transaction(function () use ($collection) {
            $new = $collection->replicate();
            $new->name = $collection->name . ' (копия)';
            $new->is_active = false;
            $new->save();

            foreach ($collection->collectionCategories as $cat) {
                $newCat = $cat->replicate();
                $newCat->collection_id = $new->id;
                $newCat->save();

                $syncData = [];
                foreach ($cat->products as $product) {
                    $syncData[$product->id] = [
                        'sort_order' => $product->pivot->sort_order ?? 0,
                    ];
                }
                $newCat->products()->sync($syncData);
            }

            return $new;
        });

        return new CollectionResource($newCollection->fresh(['collectionCategories.products']));
    }
}
