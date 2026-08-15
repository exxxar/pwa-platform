<?php

namespace App\Console\Commands\Referrals;

use App\Console\Commands\DB;
use App\Models\Tenant\ReferralReward;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\UserReferral;
use Illuminate\Console\Command;

class ShowReferralStats extends Command
{
    protected $signature = 'test:referral-stats
                            {referral_code : Реферальный код}
                            {--tenant= : ID тенанта}';

    protected $description = 'Показывает детальную статистику реферальной системы';

    public function handle(): int
    {
        $referralCode = $this->argument('referral_code');
        $tenantId = $this->option('tenant');

        $query = TenantUser::where('referral_code', $referralCode);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $referrer = $query->first();

        if (!$referrer) {
            $this->error("❌ Реферер с кодом '{$referralCode}' не найден");
            return Command::FAILURE;
        }

        $this->info("👤 Реферер: {$referrer->name} (ID: {$referrer->id})");
        $this->newLine();

        // Статистика по уровням
        $levelStats = UserReferral::where('referrer_id', $referrer->id)
            ->select('level', DB::raw('COUNT(*) as count'))
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        $this->info('📊 Рефералы по уровням:');
        $this->table(
            ['Уровень', 'Количество'],
            [
                ['1 уровень', $levelStats[1] ?? 0],
                ['2 уровень', $levelStats[2] ?? 0],
                ['3 уровень', $levelStats[3] ?? 0],
                ['Всего', array_sum($levelStats)],
            ]
        );

        // Статистика наград
        $rewardStats = ReferralReward::where('user_id', $referrer->id)
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get();

        $this->newLine();
        $this->info('💰 Награды:');
        $this->table(
            ['Тип', 'Количество', 'Сумма'],
            $rewardStats->map(fn($r) => [
                $r->type,
                $r->count,
                number_format($r->total, 2)
            ])->toArray()
        );

        $this->newLine();
        $this->info("💎 Общий заработок: " . number_format($referrer->total_referral_earnings, 2));
        $this->info("💳 Текущий кэшбэк: " . number_format($referrer->cashback_balance, 2));

        return Command::SUCCESS;
    }
}
