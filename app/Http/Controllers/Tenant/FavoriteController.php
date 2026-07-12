<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * 🆕 Получить список ID избранного
     */
    public function index()
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['data' => ['ids' => []]]);
        }

        $favorites = $user->settings["settings"]['favorites'] ?? [];


        return response()->json([
            'data' => [
                'ids' => $favorites,
                'count' => count($favorites),
            ],
        ]);
    }



    /**
     * 🆕 Получить ПОЛНЫЕ данные избранных товаров
     */
    public function products()
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['data' => []]);
        }

        // Получаем ID из настроек пользователя
        $favorites = $user->settings["settings"]['favorites'] ?? [];

        if (empty($favorites)) {
            return response()->json(['data' => []]);
        }

        // 🆕 Загружаем товары с отношениями
        $products = Product::where('tenant_id', $user->tenant_id)
            ->whereIn('id', $favorites)
            ->where('is_active', true)
            ->with([
                'categories',
            ])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'current_price' => $product->current_price ?? $product->price,
                    'old_price' => $product->old_price,
                    'sku' => $product->sku,
                    'rating' => $product->rating ?? 0,
                    'is_available' => $product->is_available ?? true,
                    'discount_percent' => $product->discount_percent ?? 0,
                    'is_new' => $product->is_new ?? false,

                    // Изображения
                    'images' => $product->images ?? [],
                    'main_image' => $product->main_image ?? ($product->images[0] ?? null),

                    // Категории
                    'categories' => $product->categories->pluck('id')->toArray(),
                    'category_names' => $product->categories->pluck('name')->toArray(),
                ];
            });

        return response()->json([
            'data' => $products,
            'count' => $products->count(),
        ]);
    }
    /**
     * 🆕 Добавить товар в избранное
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Необходима авторизация',
            ], 401);
        }

        // Проверяем, что товар принадлежит тенанту
        $product = Product::where('id', $request->product_id)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не найден',
            ], 404);
        }

        // Обновляем избранное
        $settings = $user->settings["settings"];
        $favorites = $settings['favorites'] ?? [];

        if (!in_array($request->product_id, $favorites)) {
            $favorites[] = $request->product_id;
            $settings['favorites'] = array_values(array_unique($favorites));

            $user->update(['meta' => array_merge($user->meta ?? [], ['settings' => $settings])]);
        }


        return response()->json([
            'success' => true,
            'data' => [
                'ids' => $settings['favorites'],
                'count' => count($settings['favorites']),
            ],
            'message' => 'Товар добавлен в избранное',
        ]);
    }

    /**
     * 🆕 Удалить товар из избранного
     */
    public function destroy(int $productId)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Необходима авторизация',
            ], 401);
        }

        $settings = $user->settings;
        $favorites = $settings['favorites'] ?? [];
        $favorites = array_values(array_filter($favorites, fn($id) => $id != $productId));

        $settings['favorites'] = $favorites;
        $user->update(['meta' => array_merge($user->meta ?? [], ['settings' => $settings])]);

        return response()->json([
            'success' => true,
            'data' => [
                'ids' => $favorites,
                'count' => count($favorites),
            ],
            'message' => 'Товар удалён из избранного',
        ]);
    }

    /**
     * 🆕 Очистить избранное
     */
    public function clear()
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Необходима авторизация',
            ], 401);
        }

        $settings = $user->settings;
        $settings['favorites'] = [];
        $user->update(['meta' => array_merge($user->meta ?? [], ['settings' => $settings])]);

        return response()->json([
            'success' => true,
            'data' => [
                'ids' => [],
                'count' => 0,
            ],
            'message' => 'Избранное очищено',
        ]);
    }
}
