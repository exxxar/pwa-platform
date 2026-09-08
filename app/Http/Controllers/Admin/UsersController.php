<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantUser;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $query = TenantUser::with('tenant:id,name,slug')
            ->withCount('orders')
            ->withSum('orders as orders_sum', 'summary_price');

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }

        if ($request->filled('tenant_id'))  $query->where('tenant_id', $request->get('tenant_id'));
        if ($request->filled('is_active')) $query->where('is_active', $request->boolean('is_active'));
        if ($request->filled('is_vip'))    $query->where('is_vip', $request->boolean('is_vip'));

        $users = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json([
            'items' => $users->items(),
            'meta'  => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
            'stats' => [
                'total'  => TenantUser::count(),
                'active' => TenantUser::where('is_active', true)->count(),
                'vip'    => TenantUser::where('is_vip', true)->count(),
                'today'  => TenantUser::whereDate('created_at', today())->count(),
            ],
        ]);
    }

    public function show(TenantUser $user)
    {
        $user->load('tenant:id,name,slug')
            ->loadCount('orders')
            ->loadSum('orders as orders_sum', 'summary_price');

        return response()->json($user);
    }

    public function update(Request $request, TenantUser $user)
    {
        $data = $request->validate([
            'is_active' => 'sometimes|boolean',
            'is_vip'    => 'sometimes|boolean',
        ]);

        $user->update($data);

        return response()->json($user->fresh());
    }
}
