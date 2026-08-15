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
    private const DEFAULT_PERCENTS = [
        1 => 10.0,
        2 => 5.0,
        3 => 2.0,
    ];

    public function registerReferral(TenantUser $newUser, string $referralCode): bool
    {
        // 🆕 1. Валидация входных данных
        $referralCode = trim($referralCode);
        if (empty($referralCode)) {
            Log::warning("⚠️ Пустой реферальный код", [
                'user_id' => $newUser->id
            ]);
            return false;
        }

        // 🆕 2. Проверка активности нового пользователя
        if ($newUser->isBlocked() || !$newUser->is_active) {
            Log::warning("⚠️ Заблокированный пользователь не может быть рефералом", [
                'user_id' => $newUser->id
            ]);
            return false;
        }

        // 3. Поиск реферера
        $referrer = TenantUser::where('referral_code', $referralCode)
            ->where('tenant_id', $newUser->tenant_id)
            ->where('is_active', true) // 🆕 Только активные пользователи могут приглашать
            ->first();

        if (!$referrer) {
            Log::warning("⚠️ Неверный реферальный код", [
                'code' => $referralCode,
                'user_id' => $newUser->id
            ]);
            return false;
        }

        // 4. Проверка самореферала
        if ($referrer->id === $newUser->id) {
            Log::warning("⚠️ Попытка самореферала", [
                'user_id' => $newUser->id
            ]);
            return false;
        }

        // 🆕 5. БЫСТРАЯ проверка вне транзакции (экономим блокировки)
        if (UserReferral::where('referred_id', $newUser->id)->exists()) {
            Log::warning("⚠️ Пользователь уже имеет реферера (быстрая проверка)", [
                'user_id' => $newUser->id
            ]);
            return false;
        }

        // 6. Транзакция с защитой от race condition
        return DB::transaction(function () use ($newUser, $referrer) {

            // 🆕 7. ПОВТОРНАЯ проверка ВНУТРИ транзакции с блокировкой
            // Это критически важно для защиты от параллельных запросов!
            $existingReferral = UserReferral::where('referred_id', $newUser->id)
                ->lockForUpdate() // 🔒 Блокируем строки до конца транзакции
                ->first();

            if ($existingReferral) {
                Log::warning("⚠️ Пользователь уже имеет реферера (race condition protection)", [
                    'user_id' => $newUser->id,
                    'existing_referrer_id' => $existingReferral->referrer_id
                ]);
                return false;
            }

            try {
                // 8. Создаём прямую связь (уровень 1)
                UserReferral::create([
                    'tenant_id' => $referrer->tenant_id,
                    'referrer_id' => $referrer->id,
                    'referred_id' => $newUser->id,
                    'level' => 1,
                    'is_active' => true,
                    'registered_at' => now(),
                ]);

                // 9. Создаём связи с предками (уровни 2 и 3)
                $this->createAncestorReferrals($newUser, $referrer);

                // 10. Обновляем счётчики
                $referrer->increment('referrals_count');
                $newUser->update(['referred_by' => $referrer->id]);

                // 11. Начисляем приветственный бонус
                $this->giveWelcomeBonus($referrer, $newUser);

                Log::info("🎉 Реферал зарегистрирован", [
                    'new_user_id' => $newUser->id,
                    'referrer_id' => $referrer->id,
                    'referral_code' => $referrer->referral_code
                ]);

                return true;

            } catch (\Illuminate\Database\QueryException $e) {
                // 🆕 12. Обработка нарушения уникального индекса
                if ($this->isDuplicateKeyException($e)) {
                    Log::warning("⚠️ Попытка дублирующей записи (unique constraint)", [
                        'user_id' => $newUser->id,
                        'error' => $e->getMessage()
                    ]);
                    return false;
                }
                throw $e;
            }
        });
    }

    /**
     * 🆕 Проверка, является ли исключение нарушением уникального индекса
     */
    private function isDuplicateKeyException(\Illuminate\Database\QueryException $e): bool
    {
        // MySQL: error code 23000 (Integrity constraint violation)
        // PostgreSQL: error code 23505 (unique_violation)
        $code = $e->errorInfo[0] ?? $e->getCode();
        return in_array($code, ['23000', '23505', 23000, 23505], true);
    }

    private function createAncestorReferrals(TenantUser $newUser, TenantUser $directReferrer): void
    {
        $current = $directReferrer;
        $level = 2;
        $visited = [$newUser->id, $directReferrer->id]; // Защита от циклов

        while ($level <= 3 && $current->referred_by) {
            $ancestor = TenantUser::find($current->referred_by);

            if (!$ancestor || in_array($ancestor->id, $visited)) {
                break;
            }

            // Проверка активности предка
            if (!$ancestor->is_active || $ancestor->isBlocked()) {
                $current = $ancestor;
                $level++;
                continue; // Пропускаем неактивного, но идём дальше по цепочке
            }

            $visited[] = $ancestor->id;

            // 🆕 firstOrCreate вместо create - защита от дубликатов
            UserReferral::firstOrCreate(
                [
                    'referrer_id' => $ancestor->id,
                    'referred_id' => $newUser->id,
                    'level' => $level,
                ],
                [
                    'tenant_id' => $ancestor->tenant_id,
                    'is_active' => true,
                    'registered_at' => now(),
                ]
            );

            $current = $ancestor;
            $level++;
        }
    }

    private function giveWelcomeBonus(TenantUser $referrer, TenantUser $newUser): void
    {
        $tenant = app('tenant');
        $bonus = $tenant->settings['referral']['welcome_bonus'] ?? 50;

        if ($bonus <= 0) return;

        CashBackService::call()
            ->addCashBack($bonus, "Реферальный бонус", $referrer);

        $referrer->increment('total_referral_earnings', $bonus); // 🆕 Увеличиваем общий заработок

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
     * 🆕 Оценка бонусов без реального начисления (для dry-run)
     */
    public function getEstimatedRewards(Order $order): array
    {
        $buyer = $order->tenantUser;
        $orderAmount = $order->summary_price ?? 0;

        if ($orderAmount <= 0 || !$buyer) {
            return [];
        }

        $tenant = app('tenant');
        $percents = $tenant->settings['referral']['percents'] ?? self::DEFAULT_PERCENTS;

        $rewards = [];

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

            $rewards[] = [
                'referrer_id' => $referral->referrer_id,
                'level' => $level,
                'amount' => $rewardAmount,
                'percent' => $percent,
            ];
        }

        return $rewards;
    }

    public function processOrderRewards(Order $order): array
    {
        $buyer = $order->tenantUser;
        $orderAmount = $order->summary_price ?? 0;

        if ($orderAmount <= 0 || !$buyer) {
            return [];
        }

        // 🆕 Проверка: не начислялись ли уже бонусы для этого заказа
        $existingRewards = ReferralReward::where('order_id', $order->id)->exists();
        if ($existingRewards) {
            Log::warning("⚠️ Бонусы для заказа #{$order->id} уже начислены");
            return [];
        }

        $tenant = app('tenant');
        $percents = $tenant->settings['referral']['percents'] ?? self::DEFAULT_PERCENTS;

        $rewards = [];

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

                CashBackService::call()
                    ->addCashBack($rewardAmount, "Реферальный бонус", $referral);

                $referral->referrer->increment('total_referral_earnings', $rewardAmount);

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

    public function getReferralTree(int $userId): array
    {
        $currentUser = TenantUser::find($userId);

        $tree = [
            'level_1' => [],
            'level_2' => [],
            'level_3' => [],
            'stats' => [
                'total' => 0,
                'by_level' => [1 => 0, 2 => 0, 3 => 0],
                'total_earnings' => 0,
                'active_referrals' => 0,
                'total_orders' => 0,
                'total_spent' => 0,
                'top_performers' => [],
            ],
        ];

        $referrals = UserReferral::where('referrer_id', $userId)
            ->where('is_active', true)
            ->with([
                'referred' => function ($query) {
                    $query->select('id', 'name', 'avatar', 'phone', 'created_at')
                        ->withCount('orders')
                        ->withSum('orders', 'summary_price');
                }
            ])
            ->get();

        $referralStats = [];

        foreach ($referrals as $referral) {
            $level = $referral->level;
            $referredUser = $referral->referred;

            if (!$referredUser) continue;

            $earnedFromThisReferral = ReferralReward::where('user_id', $userId)
                ->where('from_referral_id', $referredUser->id)
                ->where('type', 'cashback')
                ->sum('amount');

            $rewardsCount = ReferralReward::where('user_id', $userId)
                ->where('from_referral_id', $referredUser->id)
                ->where('type', 'cashback')
                ->count();

            $lastOrder = Order::where('tenant_user_id', $referredUser->id)
                ->whereNotNull('payed_at')
                ->orderByDesc('created_at')
                ->first(['id', 'summary_price', 'created_at', 'status']);

            $ordersCount = $referredUser->orders_count ?? 0;
            $totalSpent = $referredUser->orders_sum_summary_price ?? 0;

            $referralData = [
                'id' => $referral->id,
                'user' => $referredUser,
                'level' => $level,
                'registered_at' => $referral->registered_at,
                'orders_count' => $ordersCount,
                'total_spent' => (float) $totalSpent,
                'earned_from_this' => (float) $earnedFromThisReferral,
                'rewards_count' => $rewardsCount,
                'last_order' => $lastOrder ? [
                    'id' => $lastOrder->id,
                    'amount' => (float) $lastOrder->summary_price,
                    'date' => $lastOrder->created_at,
                    'status' => $lastOrder->status,
                ] : null,
                'is_active' => $ordersCount > 0,
                'is_profitable' => $earnedFromThisReferral > 0,
                'days_since_registration' => now()->diffInDays($referral->registered_at, false),
            ];

            $tree["level_{$level}"][] = $referralData;
            $tree['stats']['by_level'][$level]++;
            $tree['stats']['total']++;
            $tree['stats']['total_orders'] += $ordersCount;
            $tree['stats']['total_spent'] += $totalSpent;

            if ($ordersCount > 0) {
                $tree['stats']['active_referrals']++;
            }

            // 🆕 Для top_performers ищем того, кто пригласил этого человека
            $invitedBy = $this->getInvitedByInfo($referredUser->id, $level, $currentUser);

            $referralStats[] = [
                'user_id' => $referredUser->id,
                'user_name' => $referredUser->name,
                'level' => $level,
                'orders_count' => $ordersCount,
                'total_spent' => $totalSpent,
                'earned' => $earnedFromThisReferral,
                'invited_by' => $invitedBy, // 🆕 КТО ПРИГЛАСИЛ
            ];
        }

        // Сортируем топ по заработку (топ-5)
        usort($referralStats, fn($a, $b) => $b['earned'] <=> $a['earned']);
        $tree['stats']['top_performers'] = array_slice($referralStats, 0, 5);

        $tree['stats']['total_earnings'] = $currentUser?->total_referral_earnings ?? 0;

        return $tree;
    }

    /**
     * 🆕 Определяет, кто пригласил реферала
     * - Для 1-го уровня: "Вами" (текущий пользователь)
     * - Для 2-го уровня: имя его пригласившего (из 1-го уровня)
     * - Для 3-го уровня: имя его пригласившего (из 2-го уровня)
     */
    private function getInvitedByInfo(int $referredUserId, int $currentLevel, TenantUser $currentUser): array
    {
        // Для 1-го уровня - пригласил сам текущий пользователь
        if ($currentLevel === 1) {
            return [
                'name' => 'Вами',
                'is_you' => true,
            ];
        }

        // Для 2 и 3 уровня - ищем непосредственного пригласившего
        $directReferral = UserReferral::where('referred_id', $referredUserId)
            ->where('level', 1) // Ищем его прямого пригласившего
            ->where('is_active', true)
            ->first();

        if (!$directReferral) {
            return [
                'name' => 'Неизвестно',
                'is_you' => false,
            ];
        }

        // Если пригласивший - это текущий пользователь
        if ($directReferral->referrer_id === $currentUser->id) {
            return [
                'name' => 'Вами',
                'is_you' => true,
            ];
        }

        // Иначе получаем имя пригласившего
        $inviter = TenantUser::find($directReferral->referrer_id);

        return [
            'name' => $inviter?->name ?? 'Неизвестно',
            'is_you' => false,
            'user_id' => $inviter?->id,
        ];
    }

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
