<?php

namespace App\Services\Admin\TenantData;

use App\Models\Tenant\UserReferral;
use App\Models\Tenant\TenantUser;

class ReferralService
{
    /**
     * Получить список реферальных связей
     */
    public function getReferrals(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = UserReferral::query()->with(['referrer', 'referred']);

        // Фильтр по tenant_id
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Фильтр по уровню
        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        // Фильтр по активности
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Фильтр по рефоводу
        if (!empty($filters['referrer_id'])) {
            $query->where('referrer_id', $filters['referrer_id']);
        }

        // Сортировка
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Получить цепочку рефералов пользователя
     */
    public function getUserChain(TenantUser $user): array
    {
        // Кто пригласил этого пользователя (родители)
        $parents = UserReferral::where('referred_id', $user->id)
            ->with('referrer')
            ->orderBy('level')
            ->get();

        // Кого пригласил этот пользователь (дети)
        $children = UserReferral::where('referrer_id', $user->id)
            ->with('referred')
            ->get();

        return [
            'user' => $user,
            'parents' => $parents,
            'children' => $children,
        ];
    }

    /**
     * Получить статистику по рефералам
     */
    public function getStats(array $filters = []): array
    {
        $query = UserReferral::query();

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        return [
            'total_referrals' => $query->count(),
            'active_referrals' => $query->where('is_active', true)->count(),
            'level_1' => $query->where('level', 1)->count(),
            'level_2' => $query->where('level', 2)->count(),
        ];
    }

    /**
     * Ручное изменение реферальной связи
     */
    public function manuallyAdjust(UserReferral $referral, array $data): UserReferral
    {
        $referral->update($data);
        return $referral;
    }
}
