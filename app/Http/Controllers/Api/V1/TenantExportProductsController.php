<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantExportProductsController extends Controller
{

    public function __invoke(Request $request, $id): JsonResponse
    {
        $tenant = Tenant::query()
            ->with(["products"])
            ->where("uuid", $id)
            ->firstOrFail();

        $products = $tenant->products()
            ->where('is_active', true)
            ->with(['categories'])
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'price' => $p->price,
                'weight' => $p->weight,
                'images' => $p->images,
                'category' => $p->category?->name,
            ]);

        return response()->json(['data' => $products]);
    }
}
