<?php

namespace App\Services;

use App\Models\Tenant\TenantUser;
use App\Models\Tenant\UserReferral;
use App\Models\Tenant\ReferralReward;
use App\Models\Tenant\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    /**
     * Процент бонусов по уровням (настраивается в tenant->settings)
     */
    private const DEFAULT_PERCENTS = [
        1 => 10.0, // 10% от реферала 1-го уровня
        2 => 5.0,  // 5% от реферала 2-го уровня
        3 => 2.0,  // 2% от реферала 3-го уровня
    ];

    /**
     * 🆕 Регистрация нового пользователя по реферальной ссылке
     */
    public function registerReferral(TenantUser $newUser, string $referralCode): bool
    {
        $referrer = TenantUser::where('referral_code', $referralCode)->first();

        if (!$referrer || $referrer->id === $newUser->id) {
            return false;
        }

        return DB::transaction(function () use ($newUser, $referrer) {
            // 1. Создаём прямую связь (уровень 1)
            UserReferral::create([
                'tenant_id' => $referrer->tenant_id,
                'referrer_id' => $referrer->id,
                'referred_id' => $newUser->id,
                'level' => 1,
                'is_active' => true,
                'registered_at' => now(),
            ]);

            // 2. Находим всех "предков" реферера и создаём связи
            $this->createAncestorReferrals($newUser, $referrer);

            // 3. Обновляем счётчики
            $referrer->increment('referrals_count');
            $newUser->update(['referred_by' => $referrer->id]);

            // 4. Начисляем приветственный бонус рефереру
            $this->giveWelcomeBonus($referrer, $newUser);

            Log::info("🎉 Реферал зарегистрирован: #{$newUser->id} по коду {$referralCode}");

            return true;
        });
    }

    /**
     * 🆕 Создание связей с предками (уровни 2 и 3)
     */
    private function createAncestorReferrals(TenantUser $newUser, TenantUser $directReferrer): void
    {
        $current = $directReferrer;
        $level = 2;

        while ($level <= 3 && $current->referred_by) {
            $ancestor = TenantUser::find($current->referred_by);

            if (!$ancestor || $ancestor->id === $newUser->id) {
                break;
            }

            UserReferral::create([
                'tenant_id' => $ancestor->tenant_id,
                'referrer_id' => $ancestor->id,
                'referred_id' => $newUser->id,
                'level' => $level,
                'is_active' => true,
                'registered_at' => now(),
            ]);

            $current = $ancestor;
            $level++;
        }
    }

    /**
     * 🆕 Приветственный бонус за регистрацию реферала
     */
    private function giveWelcomeBonus(TenantUser $referrer, TenantUser $newUser): void
    {
        $tenant = app('tenant');
        $bonus = $tenant->settings['referral']['welcome_bonus'] ?? 50;

        if ($bonus <= 0) return;

        // Начисляем кэшбэк
        $referrer->increment('cashback', $bonus);

        // Создаём запись о награде
        ReferralReward::create([
            'tenant_id' => $referrer->tenant_id,
            'user_id' => $referrer->id,
            'from_referral_id' => $newUser->id,
            'level' => 1,
            'type' => 'welcome_bonus',
            'amount' => $bonus,
            'percent' => 0,
            'description' => "Бонус за приглашение пользователя #{$newUser->id}",
        ]);
    }

    /**
     * 🆕 Начисление реферальных бонусов после оплаты заказа
     */
    public function processOrderRewards(Order $order): array
    {
        $buyer = $order->tenantUser;
        $orderAmount = $order->summary_price ?? 0;

        if ($orderAmount <= 0 || !$buyer) {
            return [];
        }

        $tenant = app('tenant');
        $percents = $tenant->settings['referral']['percents'] ?? self::DEFAULT_PERCENTS;

        $rewards = [];

        // Находим всех рефереров этого покупателя (до 3 уровней)
        $referrals = UserReferral::where('referred_id', $buyer->id)
            ->where('is_active', true)
            ->whereIn('level', [1, 2, 3])
            ->get();

        foreach ($referrals as $referral) {
            $level = $referral->level;
            $percent = $percents[$level] ?? 0;

            if ($percent <= 0) continue;

            $rewardAmount = round($orderAmount * $percent / 100, 2);

            if ($rewardAmount <= 0) continue;

            DB::transaction(function () use ($referral, $order, $rewardAmount, $percent, $level) {
                // Начисляем кэшбэк рефереру
                $referral->referrer->increment('cashback', $rewardAmount);
                $referral->referrer->increment('total_referral_earnings', $rewardAmount);

                // Создаём запись о награде
                ReferralReward::create([
                    'tenant_id' => $referral->tenant_id,
                    'user_id' => $referral->referrer_id,
                    'order_id' => $order->id,
                    'from_referral_id' => $order->tenant_user_id,
                    'level' => $level,
                    'type' => 'cashback',
                    'amount' => $rewardAmount,
                    'percent' => $percent,
                    'description' => "Реферальный бонус {$level}-го уровня от заказа #{$order->id}",
                ]);
            });

            $rewards[] = [
                'referrer_id' => $referral->referrer_id,
                'level' => $level,
                'amount' => $rewardAmount,
                'percent' => $percent,
            ];
        }

        return $rewards;
    }

    /**
     * 🆕 Получить дерево рефералов пользователя (3 уровня)
     */
    public function getReferralTree(int $userId): array
    {
        $tree = [
            'level_1' => [],
            'level_2' => [],
            'level_3' => [],
            'stats' => [
                'total' => 0,
                'by_level' => [1 => 0, 2 => 0, 3 => 0],
                'total_earnings' => 0,
            ],
        ];

        $referrals = UserReferral::where('referrer_id', $userId)
            ->where('is_active', true)
            ->with(['referred:id,name,avatar,phone,created_at'])
            ->get();

        foreach ($referrals as $referral) {
            $level = $referral->level;
            $tree["level_{$level}"][] = [
                'id' => $referral->id,
                'user' => $referral->referred,
                'level' => $level,
                'registered_at' => $referral->registered_at,
            ];
            $tree['stats']['by_level'][$level]++;
            $tree['stats']['total']++;
        }

        // Общая сумма заработков
        $tree['stats']['total_earnings'] = TenantUser::find($userId)?->total_referral_earnings ?? 0;

        return $tree;
    }

    /**
     * 🆕 Получить историю наград пользователя
     */
    public function getRewardsHistory(int $userId, int $limit = 50): array
    {
        return ReferralReward::where('user_id', $userId)
            ->with(['order:id,summary_price,created_at', 'fromReferral:id,name,avatar'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($reward) {
                return [
                    'id' => $reward->id,
                    'type' => $reward->type,
                    'level' => $reward->level,
                    'amount' => $reward->amount,
                    'percent' => $reward->percent,
                    'description' => $reward->description,
                    'from_user' => $reward->fromReferral,
                    'order' => $reward->order,
                    'created_at' => $reward->created_at,
                ];
            })
            ->toArray();
    }
}
