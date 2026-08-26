<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'referrer_id' => $this->referrer_id,
            'referred_id' => $this->referred_id,
            'level' => (int) $this->level,
            'is_active' => $this->is_active,

            // Рефовод (кто пригласил)
            'referrer' => $this->when($this->resource->relationLoaded('referrer') && $this->referrer, function () {
                return [
                    'id' => $this->referrer->id,
                    'name' => $this->referrer->name,
                    'phone' => $this->referrer->phone,
                    'avatar' => $this->referrer->avatar ? asset('storage/' . $this->referrer->avatar) : null,
                ];
            }),

            // Реферал (кого пригласили)
            'referred' => $this->when($this->resource->relationLoaded('referred') && $this->referred, function () {
                return [
                    'id' => $this->referred->id,
                    'name' => $this->referred->name,
                    'phone' => $this->referred->phone,
                    'avatar' => $this->referred->avatar ? asset('storage/' . $this->referred->avatar) : null,
                ];
            }),

            'registered_at' => $this->registered_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
