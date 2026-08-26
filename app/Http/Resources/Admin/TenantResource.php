<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'description' => $this->description,
            'icon' => $this->icon ? asset('storage/' . $this->icon) : null,
            'theme_color' => $this->theme_color,
            'background_color' => $this->background_color,
            'app_type' => $this->app_type,
            'balance' => (float) $this->balance,
            'tax_per_day' => (float) $this->tax_per_day,
            'plan_slug' => $this->plan_slug,
            'is_active' => $this->is_active,
            'settings' => $this->settings,

            // Статистика (если загружена)
            'stats' => $this->when($this->resource->relationLoaded('users') || $request->has('with_stats'), function () {
                return [
                    'users_count' => $this->users_count ?? $this->users()->count(),
                    'active_users_count' => $this->active_users_count ?? $this->users()->where('is_active', true)->count(),
                    'orders_count' => $this->orders_count ?? 0,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
