<?php

namespace App\Services;

use App\Models\Tenant\CashBack;
use App\Models\Tenant\TenantUser;
use Illuminate\Support\Facades\Auth;

class CashBackService
{
    protected string $warnText = '';

    public static function call(): self
    {
        return app(self::class);
    }

    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    /*
     |--------------------------------------------------------------------------
     | 💰 НАЧИСЛЕНИЕ
     |--------------------------------------------------------------------------
     */
    public function addCashBack(
        float $amount,
        string $description,
        ?float $percent = null,
        bool $withLevels = true
    ): void {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            throw new \Exception('User not authenticated');
        }

        // базовый cashback
        $cashBack = $this->prepareUserCashBack($tenant->id, $user->id);
        $cashBack->amount += $amount;
        $cashBack->save();

        // история
        CashBackHistory::create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $user->id,
            'amount' => $amount,
            'type' => 'credit',
            'description' => $description,
            'level' => 1,
        ]);

        // рефералка
        if ($withLevels) {
            $this->applyLevels($user, $amount, $percent);
        }

        // предупреждения
        $this->checkWarnings($tenant, $amount, 'credit');
    }

    /*
     |--------------------------------------------------------------------------
     | 💸 СПИСАНИЕ
     |--------------------------------------------------------------------------
     */
    public function removeCashBack(float $amount, string $description): void
    {
        $tenant = app('tenant');
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            throw new \Exception('User not authenticated');
        }

        $cashBack = $this->prepareUserCashBack($tenant->id, $user->id);

        if ($cashBack->amount < $amount) {
            throw new \Exception(
                "Недостаточно средств. Баланс: {$cashBack->amount}, требуется: {$amount}"
            );
        }

        $cashBack->amount -= $amount;
        $cashBack->save();

        CashBackHistory::create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $user->id,
            'amount' => $amount,
            'type' => 'debit',
            'description' => $description,
            'level' => 1,
        ]);

        $this->checkWarnings($tenant, $amount, 'debit');
    }

    /*
     |--------------------------------------------------------------------------
     | 🧠 РЕФЕРАЛЬНЫЕ УРОВНИ
     |--------------------------------------------------------------------------
     */
    private function applyLevels($user, float $amount, ?float $percent = null): void
    {
        $tenant = app('tenant');

        // уровни
        if ($percent !== null) {
            $levels = [$percent];
        } else {
            $levels = [
                    $tenant->level_1 ?? 0,
                    $tenant->level_2 ?? 0,
                    $tenant->level_3 ?? 0,
            ];
        }

        $currentUser = $user;
        $levelIndex = 1;

        foreach ($levels as $levelPercent) {

            if (!$currentUser || $levelPercent == 0) {
                break;
            }

            $this->prepareLevel(
                $currentUser,
                $amount,
                $levelPercent,
                $levelIndex
            );

            // поднимаемся по рефералу
            $currentUser = $currentUser->parent ?? null;
            $levelIndex++;
        }
    }

    /*
     |--------------------------------------------------------------------------
     | 🔁 ОДИН УРОВЕНЬ
     |--------------------------------------------------------------------------
     */
    private function prepareLevel(
        $user,
        float $baseAmount,
        float $percent,
        int $level
    ): void {
        if ($percent == 0 || !$user) {
            return;
        }

        $tenant = app('tenant');

        $bonus = $baseAmount * ($percent / 100);

        $cashBack = $this->prepareUserCashBack($tenant->id, $user->id);
        $cashBack->amount += $bonus;
        $cashBack->save();

        CashBackHistory::create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $user->id,
            'amount' => $bonus,
            'type' => 'credit',
            'description' => "Реферальный бонус {$level} уровня",
            'level' => $level,
        ]);

        // warnings
        $this->checkWarnings($tenant, $baseAmount, 'check', $level);
        $this->checkWarnings($tenant, $bonus, 'credit', $level);
    }

    /*
     |--------------------------------------------------------------------------
     | ⚠️ WARNING RULES
     |--------------------------------------------------------------------------
     */
    private function checkWarnings(
        $tenant,
        float $amount,
        string $type,
        ?int $level = null
    ): void {
        $warnings = $tenant->warnings ?? [];

        foreach ($warnings as $warn) {
            if (!$warn->is_active) {
                continue;
            }

            if ($warn->rule_key === 'bill_sum_more_then'
                && $amount >= $warn->rule_value
                && $type === 'check'
            ) {
                $this->warnText .= "Сумма чека {$amount} > {$warn->rule_value} (уровень {$level})\n";
            }

            if ($warn->rule_key === 'cashback_up_sum_more_then'
                && $amount >= $warn->rule_value
                && $type === 'credit'
            ) {
                $this->warnText .= "Начисление {$amount} > {$warn->rule_value} (уровень {$level})\n";
            }

            if ($warn->rule_key === 'cashback_down_sum_more_then'
                && $amount >= $warn->rule_value
                && $type === 'debit'
            ) {
                $this->warnText .= "Списание {$amount} > {$warn->rule_value}\n";
            }
        }

        if (!empty($this->warnText)) {
            Log::warning($this->warnText);
        }
    }

    /*
     |--------------------------------------------------------------------------
     | 🗄 БАЛАНС
     |--------------------------------------------------------------------------
     */
    private function prepareUserCashBack($tenantId, $userId)
    {
        return CashBack::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'tenant_user_id' => $userId,
            ],
            [
                'amount' => 0,
            ]
        );
    }
}
