<?php
// app/Http/Resources/Agent/AgentResource.php
namespace App\Http\Resources\Agent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->tenantUser->name ?? null,
            'phone'               => $this->tenantUser->phone ?? null,
            'email'               => $this->tenantUser->email ?? null,
            'status'              => $this->status,
            'verification_status' => $this->verification_status,
            'balance'             => (float) $this->balance,
            'pending_balance'     => (float) $this->pending_balance,
            'total_earned'        => (float) $this->total_earned,
            'legal_type'          => $this->legal_type,
            'inn'                 => $this->inn,
            'ogrn'                => $this->ogrn,
            'bank_account'        => $this->bank_account,
            'bik'                 => $this->bik,
            'bank_name'           => $this->bank_name,
            'referral_code'       => $this->referral_code,
            'referral_url'        => $this->referral_url,
            'referrals_count'     => $this->referrals_count,
            'clients_count'       => $this->clients_count,
            'tenant_count'        => $this->tenant_count,
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
