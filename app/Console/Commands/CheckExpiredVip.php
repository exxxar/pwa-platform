<?php

namespace App\Console\Commands;

use App\Models\Tenant\TenantUser;
use Illuminate\Console\Command;

class CheckExpiredVip extends Command
{
    protected $signature = 'users:check-vip-expiry';
    protected $description = 'Проверка и отзыв истёкших VIP статусов';

    public function handle(): int
    {
        $expired = TenantUser::where('is_vip', true)
            ->whereNotNull('vip_expires_at')
            ->where('vip_expires_at', '<=', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('✅ Истёкших VIP статусов не найдено');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($expired as $user) {
            $user->revokeVip();
            $count++;

            $this->line("🔄 Отозван VIP у пользователя #{$user->id} ({$user->name})");
        }

        $this->info("✅ Отозвано VIP статусов: {$count}");

        return self::SUCCESS;
    }
}
