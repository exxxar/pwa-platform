<?php

namespace App\Console\Commands\Referrals;

use App\Models\Tenant\ReferralReward;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\UserReferral;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTestReferrals extends Command
{
    protected $signature = 'test:clear-referrals
                            {--tenant= : ID тенанта}
                            {--force : Пропустить подтверждение}';

    protected $description = 'Удаляет всех тестовых пользователей и их реферальные связи';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        $query = TenantUser::where('name', 'like', 'Тест • %');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('✅ Тестовых пользователей не найдено');
            return Command::SUCCESS;
        }

        $this->warn("⚠️ Будет удалено {$count} тестовых пользователей");

        if (!$this->option('force') && !$this->confirm('Продолжить?', false)) {
            $this->info('Отменено');
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($query) {
            $userIds = $query->pluck('id')->toArray();

            // Удаляем реферальные связи
            UserReferral::whereIn('referred_id', $userIds)->delete();
            UserReferral::whereIn('referrer_id', $userIds)->delete();

            // Удаляем награды
            ReferralReward::whereIn('from_referral_id', $userIds)->delete();

            // Удаляем пользователей
            TenantUser::whereIn('id', $userIds)->delete();
        });

        $this->info("✅ Удалено {$count} тестовых пользователей");

        return Command::SUCCESS;
    }
}
