<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Ad;
use App\Models\Tenant\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = app('tenant')->id;

        $ads = Ad::where('tenant_id', $tenantId)
            ->active()
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'items' => $ads,
            'stats' => [
                'total'    => $ads->count(),
                'active'   => $ads->where('is_active', true)->count(),
                'inactive' => $ads->where('is_active', false)->count(),
                'scheduled'=> $ads->where('is_active', true)->whereNotNull('starts_at')->count(),
            ],
        ]);
    }

    public function clientIndex()
    {
        $tenantId = app('tenant')->id;

        // Глобальные рекламы
        $globalAds = Ad::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->map(fn($ad) => [
                ...$ad->toArray(),
                'source' => 'global',  // ← Важно!
            ]);

        // Рекламы партнёров
        $partnerAds = Partner::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->flatMap(function ($partner) {
                $config = is_string($partner->config)
                    ? json_decode($partner->config, true)
                    : ($partner->config ?? []);

                $ads = $config['ads'] ?? [];

                return collect($ads)
                    ->filter(fn($ad) => ($ad['is_active'] ?? true) !== false)
                    ->map(fn($ad) => [
                        ...$ad,
                        'source' => 'partner',  // ← Важно!
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->title ?? $partner->name,
                    ]);
            });

        return response()->json([
            'ads' => $globalAds->concat($partnerAds)->values(),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = app('tenant')->id;

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'short_text'   => 'nullable|string|max:255',
            'full_text'    => 'nullable|string',
            'image'        => 'nullable|string',
            'badge'        => 'nullable|string|max:50',
            'button_text'  => 'nullable|string|max:50',
            'action_type'  => 'in:url,partner,none',
            'action_value' => 'nullable|string',
            'position'     => 'nullable|integer|min:0',
            'is_active'    => 'boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date|after_or_equal:starts_at',
        ]);

        // Обработка загрузки файла
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Валидация файла
            $request->validate([
                'file' => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120', // 5MB
            ]);

            // Сохраняем в папку media (не ads, чтобы не блокировал AdBlock)
            $path = $file->store('tenants/' . $tenantId . '/media', 'public');
            $data['image'] = '/storage/' . $path;
        }

        $data['tenant_id'] = $tenantId;
        $data['position'] = $data['position'] ?? Ad::where('tenant_id', $tenantId)->max('position') + 1;

        $ad = Ad::create($data);

        return response()->json($ad->fresh(), 201);
    }

    public function update(Request $request, Ad $ad)
    {
        $tenantId = app('tenant')->id;

        // Проверка принадлежности рекламы текущему тенанту
        if ($ad->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'short_text'   => 'nullable|string|max:255',
            'full_text'    => 'nullable|string',
            'image'        => 'nullable|string',
            'badge'        => 'nullable|string|max:50',
            'button_text'  => 'nullable|string|max:50',
            'action_type'  => 'in:url,partner,none',
            'action_value' => 'nullable|string',
            'position'     => 'nullable|integer|min:0',
            'is_active'    => 'boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date|after_or_equal:starts_at',
        ]);

        // Обработка загрузки нового файла
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            $request->validate([
                'file' => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            ]);

            // Удаляем старое изображение, если есть
            if ($ad->image && str_starts_with($ad->image, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $ad->image);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $file->store('tenants/' . $tenantId . '/media', 'public');
            $data['image'] = '/storage/' . $path;
        }

        $ad->update($data);

        return response()->json($ad->fresh());
    }

    public function destroy(Request $request, Ad $ad)
    {
        $tenantId = app('tenant')->id;

        // Проверка принадлежности
        if ($ad->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Удаляем файл изображения
        if ($ad->image && str_starts_with($ad->image, '/storage/')) {
            $path = str_replace('/storage/', '', $ad->image);
            Storage::disk('public')->delete($path);
        }

        $ad->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $tenantId = app('tenant')->id;

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:ads,id',
        ]);

        // Проверяем, что все ID принадлежат текущему тенанту
        $validIds = Ad::where('tenant_id', $tenantId)
            ->whereIn('id', $request->ids)
            ->pluck('id')
            ->toArray();

        if (count($validIds) !== count($request->ids)) {
            return response()->json(['message' => 'Invalid ad IDs'], 403);
        }

        foreach ($request->ids as $position => $id) {
            Ad::where('id', $id)->update(['position' => $position]);
        }

        return response()->json(['success' => true]);
    }
}
