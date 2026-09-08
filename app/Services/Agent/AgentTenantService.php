<?php

namespace App\Services\Agent;

use App\Models\Agent;
use App\Models\Tenant;
use App\Models\AgentClient;

class AgentTenantService
{
    /**
     * Получить приложения агента с фильтрами
     */
    public function getAgentTenants(Agent $agent, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Tenant::where('agent_id', $agent->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Привязать приложение к клиенту
     */
    public function assignClient(Tenant $tenant, AgentClient $client): void
    {
        $tenant->update([
            'agent_id'  => $client->agent_id,
            'client_id' => $client->id,
        ]);
    }

    /**
     * Подсчитать доход по приложению (через транзакции)
     */
    public function calculateTenantEarnings(Tenant $tenant): float
    {
        return (float) $tenant->transactions()->sum('amount');
    }
}
