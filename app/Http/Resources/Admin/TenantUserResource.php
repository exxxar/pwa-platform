<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'email' => $this->email,
            'phone' => $this->phone,
            'sex' => $this->sex,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'city' => $this->city,
            'country' => $this->country,
            'address' => $this->address,

            // Статусы
            'is_active' => $this->is_active,
            'is_vip' => $this->is_vip,
            'has_active_vip' => $this->has_active_vip,
            'vip_activated_at' => $this->vip_activated_at?->format('Y-m-d H:i:s'),
            'vip_expires_at' => $this->vip_expires_at?->format('Y-m-d H:i:s'),
            'vip_days_left' => $this->vip_days_left,

            // Блокировка
            'is_blocked' => $this->isBlocked(),
            'blocked_at' => $this->blocked_at?->format('Y-m-d H:i:s'),
            'blocked_message' => $this->blocked_message,

            // Рефералка
            'referral_code' => $this->referral_code,
            'referred_by' => $this->referred_by,
            'referrals_count' => (int) $this->referrals_count,
            'friends_count' => (int) $this->friends_count,
            'total_referral_earnings' => (float) $this->total_referral_earnings,
            'referral_link' => $this->referral_link,

            // Кэшбэк
            'cashback_balance' => (float) $this->cashback_balance,
            'cashback_subs' => $this->cashback_subs,

            // Роли внутри тенанта
            'roles' => $this->when($this->resource->relationLoaded('roles'), function () {
                return $this->roles->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label,
                ]);
            }),

            'role_names' => $this->role_names,
            'permission_names' => $this->permission_names,

            // Адреса
            'addresses' => $this->when($this->resource->relationLoaded('addresses'), function () {
                return $this->addresses->map(fn($addr) => [
                    'id' => $addr->id,
                    'title' => $addr->title,
                    'address' => $addr->address,
                    'is_default' => $addr->is_default,
                ]);
            }),

            'default_address' => $this->when($this->resource->relationLoaded('addresses'), function () {
                $default = $this->default_address;
                return $default ? [
                    'id' => $default->id,
                    'title' => $default->title,
                    'address' => $default->address,
                    'city' => $default->city,
                    'lat' => $default->lat,
                    'lng' => $default->lng,
                ] : null;
            }),

            // Статистика
            'orders_count' => (int) $this->orders_count,

            // Реферер (кто пригласил)
            'referrer' => $this->when($this->resource->relationLoaded('referrer') && $this->referrer, function () {
                return [
                    'id' => $this->referrer->id,
                    'name' => $this->referrer->name,
                    'phone' => $this->referrer->phone,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
