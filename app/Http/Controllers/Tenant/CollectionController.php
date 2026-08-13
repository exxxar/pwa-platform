<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    protected CollectionService $service;

    public function __construct(CollectionService $service)
    {
        $this->service = $service;
    }

    /**
     * Список коллекций (с пагинацией)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->service->list(
                $request->input('search'),
                $request->only(['is_active', 'in_stop_list', 'type']),
                $request->input('size')
            );

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }

    /**
     * Активные коллекции для фронта
     */
    public function active(Request $request)
    {

        try {
            $result = $this->service->activeList($request->input('partner_id'));
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }

    /**
     * Одна коллекция со всеми деталями
     */
    public function show(Request $request, int $id): JsonResponse
    {


        try {
            $result = $this->service->show($id, $request->partner_id);
            return response()->json(['data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }

    /**
     * Создание или обновление коллекции
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $result = $this->service->createOrUpdate(
                $request->all(),
                $request->file('image')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => method_exists($e, 'errors') ? $e->errors() : [],
            ], $e->getCode() ?? 422);
        }
    }

    /**
     * Удаление коллекции
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->service->destroy($id);
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }

    /**
     * Переключение активности
     */
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $result = $this->service->toggleActive($id);
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }

    /**
     * Переключение стоп-листа
     */
    public function toggleStopList(int $id): JsonResponse
    {
        try {
            $result = $this->service->toggleStopList($id);
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }

    // ==========================================
    // КАТЕГОРИИ
    // ==========================================

    public function addCategory(int $collectionId, Request $request): JsonResponse
    {
        try {
            $result = $this->service->addCategory($collectionId, $request->all());
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 422);
        }
    }

    public function updateCategory(int $categoryId, Request $request): JsonResponse
    {
        try {
            $result = $this->service->updateCategory($categoryId, $request->all());
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 422);
        }
    }

    public function removeCategory(int $categoryId): JsonResponse
    {
        try {
            $result = $this->service->removeCategory($categoryId);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }

    // ==========================================
    // ТОВАРЫ В КАТЕГОРИЯХ
    // ==========================================

    public function addProducts(int $categoryId, Request $request): JsonResponse
    {
        try {
            $result = $this->service->addProductsToCategory(
                $categoryId,
                $request->input('product_ids', [])
            );
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 422);
        }
    }

    public function removeProduct(int $categoryId, int $productId): JsonResponse
    {
        try {
            $result = $this->service->removeProductFromCategory($categoryId, $productId);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }

    public function reorderProducts(int $categoryId, Request $request): JsonResponse
    {
        try {
            $result = $this->service->reorderProducts(
                $categoryId,
                $request->input('order', [])
            );
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 422);
        }
    }

    // ==========================================
    // МАССОВЫЕ ОПЕРАЦИИ
    // ==========================================

    public function removeAll(): JsonResponse
    {
        try {
            $this->service->removeAll();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }

    public function duplicate(int $id): JsonResponse
    {
        try {
            $result = $this->service->duplicate($id);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?? 500);
        }
    }
}
