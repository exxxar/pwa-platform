<?php

namespace App\Services\Agent;

use App\Models\Agent;
use App\Models\AgentClient;
use App\Models\TenantUser;

class AgentClientService
{
    /**
     * Добавить клиента к агенту
     */
    public function addClient(Agent $agent, TenantUser $tenantUser, ?string $notes = null): AgentClient
    {
        $client = AgentClient::firstOrCreate(
            ['agent_id' => $agent->id, 'tenant_user_id' => $tenantUser->id],
            ['status' => 'active', 'notes' => $notes]
        );

        $agent->increment('clients_count');

        return $client;
    }

    /**
     * Получить клиентов агента с фильтрами
     */
    public function getClients(Agent $agent, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $agent->clients()->with('tenantUser');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Деактивировать клиента
     */
    public function deactivateClient(AgentClient $client): void
    {
        $client->update(['status' => 'inactive']);
        $client->agent->decrement('clients_count');
    }
}
