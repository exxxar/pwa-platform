<?php

namespace App\Services\Agent;

use App\Models\Agent\Agent;

use App\DTOs\Agent\UpdateProfileDTO;
use App\Events\Agent\ProfileUpdated;
use App\Models\Tenant\TenantUser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AgentService
{
    /**
     * Создать агентский профиль для TenantUser
     */
    public function createProfile(TenantUser $user): Agent
    {
        return DB::transaction(function () use ($user) {
            return Agent::create([
                'tenant_user_id'      => $user->id,
                'status'              => 'pending',
                'verification_status' => 'not_started',
                'referral_code'       => $this->generateReferralCode(),
            ]);
        });
    }

    /**
     * Обновить профиль и реквизиты
     */
    public function updateProfile(Agent $agent, UpdateProfileDTO $dto): Agent
    {
        DB::transaction(function () use ($agent, $dto) {
            $agent->fill($dto->toArray());

            // Если изменился legal_type — сбрасываем верификацию
            if ($agent->isDirty('legal_type')) {
                $agent->verification_status = 'partial';
                $agent->status = 'pending';
            }

            $agent->save();
        });

        event(new ProfileUpdated($agent));

        return $agent->refresh();
    }

    /**
     * Пересчитать все счётчики агента (денормализация)
     */
    public function recalculateCounters(Agent $agent): void
    {
        $agent->update([
            'clients_count'   => $agent->clients()->count(),
            'tenant_count'    => $agent->tenants()->count(),
            'referrals_count' => $agent->referrals()->count(),
        ]);
    }

    /**
     * Получить сводные метрики для дашборда
     */
    public function getDashboardMetrics(Agent $agent): array
    {
        return [
            'balance'         => (float) $agent->balance,
            'pending_balance' => (float) $agent->pending_balance,
            'total_earned'    => (float) $agent->total_earned,
            'tenant_count'    => $agent->tenant_count,
            'clients_count'   => $agent->clients_count,
            'referrals_count' => $agent->referrals_count,
            'verification'    => [
                'status'    => $agent->verification_status,
                'uploaded'  => $agent->documents()->where('is_uploaded', true)->count(),
                'required'  => $agent->documents()->where('is_required', true)->count(),
            ],
        ];
    }

    /**
     * Проверить, может ли агент совершать операции
     */
    public function ensureCanOperate(Agent $agent): void
    {
        if ($agent->status === 'suspended') {
            throw new \App\Exceptions\Agent\AgentNotVerifiedException(
                'Ваш аккаунт приостановлен. Обратитесь в поддержку.'
            );
        }
    }

    /**
     * Сгенерировать уникальный реферальный код
     */
    private function generateReferralCode(): string
    {
        do {
            $code = 'agent_' . Str::upper(Str::random(8));
        } while (Agent::where('referral_code', $code)->exists());

        return $code;
    }
}
