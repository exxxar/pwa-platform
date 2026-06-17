<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Location;
use App\Models\Tenant\TenantUserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Список адресов пользователя
     */
    public function index(Request $request)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        return TenantUserAddress::query()
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $tenantUser->id)
            ->orderByDesc('is_default')
            ->get();
    }

    /**
     * Создание адреса
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'is_default' => 'boolean',
            'meta' => 'nullable|array',
        ]);

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        // если ставим дефолт — сбрасываем старые
        if (!empty($data['is_default'])) {
            TenantUserAddress::query()
                ->where('tenant_user_id', $tenant->id)
                ->update(['is_default' => false]);
        }

        $address = TenantUserAddress::create([
            ...$data,
            'tenant_user_id' => $tenantUser->id,
            'tenant_id' => $tenant->id,
        ]);

        return response()->json($address);
    }

    /**
     * Удаление
     */
    public function destroy($tenant, $id)
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();


        $address = TenantUserAddress::query()
            ->where('tenant_user_id',$tenantUser->id)
            ->where("id", $id)
            ->firstOrFail();


        $address->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Сделать адрес дефолтным
     */
    public function setDefault($tenant, $id)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        TenantUserAddress::where('tenant_user_id', $tenantUser->id)
            ->update(['is_default' => false]);

        $address = TenantUserAddress::query()
            ->where('tenant_user_id', $tenantUser->id)
            ->findOrFail($id);

        $address->update(['is_default' => true]);

        return response()->json(['success' => true]);
    }
}
