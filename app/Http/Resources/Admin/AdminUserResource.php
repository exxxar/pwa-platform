<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),

            // Роли
            'roles' => RoleResource::collection($this->whenLoaded('roles')),

            // Права (агрегированные из ролей)
            'permissions' => $this->when($this->resource->relationLoaded('roles'), function () {
                return $this->roles->flatMap->permissions->pluck('name')->unique()->values()->toArray();
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
