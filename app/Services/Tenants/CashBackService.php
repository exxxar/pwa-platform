<?php

namespace App\Services\Tenants;

use App\Models\Tenant\CashBack;
use App\Models\Tenant\CashBackHistory;
use App\Models\Tenant\TenantUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    /**
     * Валидация пользователя и суммы (DRY)
     */
    private function validateUserAndAmount(?TenantUser $user, float $amount): void
    {
        if (!$user) {
            throw new \InvalidArgumentException('Пользователь не аутентифицирован или не передан.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Сумма операции должна быть больше 0.');
        }
    }

    /**
     * Получить баланс пользователя
     */
    public function getBalance(?TenantUser $user = null): float
    {
        $user = $user ?? Auth::guard('tenant')->user();

        if (!$user) {
            return 0.0;
        }

        $tenant = app('tenant');
        $cashBack = CashBack::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->first();

        return $cashBack ? (float) $cashBack->amount : 0.0;
    }

    /**
     * Получить историю операций
     */
    public function getHistory(?TenantUser $user = null, int $limit = 50)
    {
        $user = $user ?? Auth::guard('tenant')->user();

        if (!$user) {
            return collect();
        }

        $tenant = app('tenant');

        return CashBackHistory::where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $user->id)
            ->with(['order' => function ($query) {
                $query->select('id', 'summary_price', 'created_at');
            }])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Начислить кэшбэк
     */
    public function addCashBack(
        float $amount,
        string $description,
        ?TenantUser $user = null,
        ?int $orderId = null,
        ?float $percent = null,
        bool $withLevels = true
    ): void {
        $user = $user ?? Auth::guard('tenant')->user();
        $this->validateUserAndAmount($user, $amount);

        $tenant = app('tenant');

        DB::transaction(function () use ($user, $tenant, $amount, $description, $orderId, $withLevels, $percent) {
            // 1. Атомарное обновление баланса (защита от race condition)
            $cashBack = $this->prepareUserCashBack($tenant->id, $user->id);
            $cashBack->increment('amount', $amount);

            // 2. Создание записи в истории
            CashBackHistory::create([
                'tenant_id' => $tenant->id,
                'tenant_user_id' => $user->id,
                'amount' => $amount,
                'type' => 'credit',
                'description' => $description,
                'level' => 1,
                'order_id' => $orderId,
            ]);

            // 3. Реферальные начисления (внутри той же транзакции)
            if ($withLevels) {
                $this->applyLevels($user, $amount, $percent);
            }
        });

        // 4. Проверка предупреждений (вне транзакции, чтобы не блокировать строки лишний раз)
        $this->checkWarnings($tenant, $amount, 'credit');
    }

    /**
     * Списать кэшбэк
     */
    public function removeCashBack(
        float $amount,
        string $description,
        ?TenantUser $user = null,
        ?int $orderId = null
    ): void {
        $user = $user ?? Auth::guard('tenant')->user();
        $this->validateUserAndAmount($user, $amount);

        $tenant = app('tenant');
        $cashBack = $this->prepareUserCashBack($tenant->id, $user->id);

        if ($cashBack->amount < $amount) {
            throw new \Exception("Недостаточно средств. Баланс: {$cashBack->amount}, требуется: {$amount}");
        }

        DB::transaction(function () use ($cashBack, $tenant, $user, $amount, $description, $orderId) {
            // 1. Атомарное списание
            $cashBack->decrement('amount', $amount);

            // 2. Создание записи в истории
            CashBackHistory::create([
                'tenant_id' => $tenant->id,
                'tenant_user_id' => $user->id,
                'amount' => $amount,
                'type' => 'debit',
                'description' => $description,
                'level' => 1,
                'order_id' => $orderId,
            ]);
        });

        // 3. Проверка предупреждений
        $this->checkWarnings($tenant, $amount, 'debit');
    }

    /**
     * Реферальные уровни
     */
    private function applyLevels(TenantUser $user, float $baseAmount, ?float $percent = null): void
    {
        $tenant = app('tenant');

        // Приводим к float, чтобы избежать ошибок сравнения строк и чисел
        $levels = $percent !== null
            ? [(float) $percent]
            : [
                (float) ($tenant->level_1 ?? 0),
                (float) ($tenant->level_2 ?? 0),
                (float) ($tenant->level_3 ?? 0),
            ];

        $currentUser = $user;
        $levelIndex = 1;

        foreach ($levels as $levelPercent) {
            if ($levelPercent <= 0) {
                break;
            }

            // Получаем родителя. Если relation 'parent' не загружен, Laravel сделает запрос.
            $currentUser = $currentUser->parent;

            if (!$currentUser) {
                break;
            }

            $this->processReferralLevel($currentUser, $baseAmount, $levelPercent, $levelIndex);
            $levelIndex++;
        }
    }

    /**
     * Обработка одного уровня реферальной системы
     */
    private function processReferralLevel(TenantUser $user, float $baseAmount, float $percent, int $level): void
    {
        $tenant = app('tenant');
        $bonus = $baseAmount * ($percent / 100);

        // Округляем до 2 знаков, чтобы избежать проблем с float в БД
        $bonus = round($bonus, 2);

        if ($bonus <= 0) {
            return;
        }

        $cashBack = $this->prepareUserCashBack($tenant->id, $user->id);
        $cashBack->increment('amount', $bonus);

        CashBackHistory::create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $user->id,
            'amount' => $bonus,
            'type' => 'credit',
            'description' => "Реферальный бонус {$level} уровня",
            'level' => $level,
        ]);

        $this->checkWarnings($tenant, $baseAmount, 'check', $level);
        $this->checkWarnings($tenant, $bonus, 'credit', $level);
    }

    /**
     * Проверка предупреждений
     */
    private function checkWarnings($tenant, float $amount, string $type, ?int $level = null): void
    {
        // 🛡️ СБРОС СОСТОЯНИЯ: предотвращает накопление текста между вызовами
        $this->warnText = '';

        $warnings = $tenant->warnings ?? [];

        foreach ($warnings as $warn) {
            if (empty($warn->is_active)) {
                continue;
            }

            if ($warn->rule_key === 'bill_sum_more_then' && $amount >= $warn->rule_value && $type === 'check') {
                $this->warnText .= "Сумма чека {$amount} >= {$warn->rule_value} (уровень {$level})\n";
            }

            if ($warn->rule_key === 'cashback_up_sum_more_then' && $amount >= $warn->rule_value && $type === 'credit') {
                $this->warnText .= "Начисление {$amount} >= {$warn->rule_value} (уровень {$level})\n";
            }

            if ($warn->rule_key === 'cashback_down_sum_more_then' && $amount >= $warn->rule_value && $type === 'debit') {
                $this->warnText .= "Списание {$amount} >= {$warn->rule_value}\n";
            }
        }

        if (!empty($this->warnText)) {
            Log::warning('Cashback Service Warning: ' . trim($this->warnText));
        }
    }

    /**
     * Получить или создать запись кэшбэка
     */
    private function prepareUserCashBack(int $tenantId, int $userId): CashBack
    {
        return CashBack::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'tenant_user_id' => $userId,
            ],
            [
                'amount' => 0.0,
            ]
        );
    }
}
