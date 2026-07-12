<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromoCodeController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $query = PromoCode::where('tenant_id', $tenant->id);

        if ($request->filled('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }

        $promocodes = $query->orderByDesc('created_at')->get();

        return response()->json(['data' => $promocodes]);
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'code' => 'required|string|unique:promo_codes,code,NULL,id,tenant_id,' . $tenant->id,
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percent,fixed',
            'discount' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'required|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'usage_limit' => 'nullable|integer|min:0',
            'for_new_users' => 'boolean',
            'for_vip' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['code'] = strtoupper($validated['code']);

        $promo = PromoCode::create($validated);

        return response()->json([
            'success' => true,
            'data' => $promo,
            'message' => 'Промокод создан',
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $tenant = app('tenant');

        $promo = PromoCode::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string|max:500',
            'discount_type' => 'sometimes|in:percent,fixed',
            'discount' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'sometimes|date',
            'expires_at' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:0',
            'for_new_users' => 'boolean',
            'for_vip' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $promo->update($validated);

        return response()->json([
            'success' => true,
            'data' => $promo->fresh(),
            'message' => 'Промокод обновлён',
        ]);
    }

    public function destroy(int $id)
    {
        $tenant = app('tenant');

        $promo = PromoCode::where('tenant_id', $tenant->id)->findOrFail($id);
        $promo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Промокод удалён',
        ]);
    }

    public function toggleActive(int $id)
    {
        $tenant = app('tenant');

        $promo = PromoCode::where('tenant_id', $tenant->id)->findOrFail($id);
        $promo->update(['is_active' => !$promo->is_active]);

        return response()->json([
            'success' => true,
            'data' => $promo->fresh(),
        ]);
    }
}
