<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,

            // Разрешения
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),

            // Количество пользователей с этой ролью
            'users_count' => $this->when($this->resource->relationLoaded('users'), function () {
                return $this->users_count ?? $this->users()->count();
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
