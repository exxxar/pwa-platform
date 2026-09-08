<?php

namespace App\Services\Agent;

use App\Models\Agent;
use App\Models\AgentReferral;
use App\Models\TenantUser;
use Illuminate\Http\Request;

class AgentReferralService
{
    /**
     * Зафиксировать реферальный переход
     */
    public function trackReferral(string $referralCode, Request $request): ?AgentReferral
    {
        $agent = Agent::where('referral_code', $referralCode)->first();
        if (!$agent) {
            return null;
        }

        return AgentReferral::create([
            'agent_id'                => $agent->id,
            'referral_code'           => $referralCode,
            'ip_address'              => $request->ip(),
            'user_agent'              => $request->userAgent(),
            'utm_source'              => $request->input('utm_source'),
            'utm_medium'              => $request->input('utm_medium'),
            'utm_campaign'            => $request->input('utm_campaign'),
        ]);
    }

    /**
     * Конвертировать реферала (когда он зарегистрировался)
     */
    public function convertReferral(AgentReferral $referral, TenantUser $user): void
    {
        $referral->update([
            'referred_tenant_user_id' => $user->id,
            'converted'               => true,
            'converted_at'            => now(),
        ]);

        // Начисляем бонус агенту (если настроено)
        $bonus = config('agents.referral_bonus', 0);
        if ($bonus > 0) {
            $referral->update(['bonus_amount' => $bonus]);
            app(AgentFinanceService::class)->creditIncome(
                $referral->agent,
                $bonus,
                "Реферальный бонус за {$user->name}",
                ['related_type' => AgentReferral::class, 'related_id' => $referral->id]
            );
        }

        $referral->agent->increment('referrals_count');
    }

    /**
     * Статистика рефералов
     */
    public function getStats(Agent $agent): array
    {
        $referrals = $agent->referrals();

        return [
            'total_clicks' => $referrals->count(),
            'conversions'  => $referrals->where('converted', true)->count(),
            'total_bonus'  => (float) $referrals->sum('bonus_amount'),
        ];
    }
}
