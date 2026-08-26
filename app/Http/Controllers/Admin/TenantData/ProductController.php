<?php

namespace App\Http\Controllers\Admin\TenantData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantData\StoreProductRequest;
use App\Http\Requests\Admin\TenantData\UpdateProductRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Models\Tenant\Product;
use App\Services\Admin\TenantData\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Список товаров с фильтрацией и пагинацией
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->only([
            'tenant_id',
            'is_active',
            'in_stop_list',
            'category_id',
            'search',
            'sort_by',
            'sort_dir',
        ]);
        $perPage = $request->input('per_page', 15);

        $products = $this->productService->getProducts($filters, $perPage);

        return ProductResource::collection($products);
    }

    /**
     * Создание нового товара
     */
    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $product = $this->productService->createProduct($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Просмотр товара с детальной информацией
     */
    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $product->load([
            'categories',
            'tenant',
            'ingredientGroups.ingredients',
            'components',
        ]);

        return new ProductResource($product);
    }

    /**
     * Обновление товара
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $product = $this->productService->updateProduct($product, $request->validated());

        return new ProductResource($product);
    }

    /**
     * Удаление товара (soft delete)
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $this->productService->deleteProduct($product);

        return response()->json([
            'success' => true,
            'message' => 'Товар успешно удален',
        ]);
    }

    /**
     * Переключение стоп-листа
     */
    public function toggleStopList(Product $product)
    {
        $this->authorize('toggleStopList', $product);

        $product = $this->productService->toggleStopList($product);

        return new ProductResource($product);
    }

    /**
     * Переключение активности
     */
    public function toggleActive(Product $product)
    {
        $this->authorize('toggleActive', $product);

        $product = $this->productService->toggleActive($product);

        return new ProductResource($product);
    }
}
