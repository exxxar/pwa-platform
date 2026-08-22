<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantExportController extends Controller
{
    /**
     * Экспорт списка тенантов в заданном формате
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        // При необходимости здесь можно добавить фильтрацию или пагинацию
        // Например: $query = Tenant::query()->where('is_disabled', false);

        $tenants = Tenant::all()->map(function (Tenant $tenant) {
            // Аксессор settings автоматически объединяет meta с defaultSettings()
            $settings = $tenant->settings;

            return [
                'uuid'        => $tenant->uuid,
                'slug'        => $tenant->slug,
                'name'        => $tenant->name,
                'short_name'  => $tenant->short_name,
                'description' => $tenant->description,
                'meta'        => [
                    'address'     => $settings['address'] ?? '',
                    'shop_coords' => $settings['shop_coords'] ?? '0,0',
                    'schedule'    => $settings['schedule'] ?? [],
                    'manager'     => [
                        'name'  => $settings['manager']['name'] ?? '',
                        'phone' => $settings['manager']['phone'] ?? '',
                        'email' => $settings['manager']['email'] ?? '',
                    ],
                ],
            ];
        });

        return response()->json([
            'data' => $tenants->toArray(),
        ]);
    }

    public function products(Tenant $tenant)
    {
        $products = $tenant->products()
            ->where('is_active', true)
            ->with(['category', 'images'])
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'price' => $p->price,
                'weight' => $p->weight,
                'image' => $p->images->first()?->url,
                'category' => $p->category?->name,
            ]);

        return response()->json(['data' => $products]);
    }
}
