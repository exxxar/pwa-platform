<?php
// app/Http/Resources/Agent/TenantResource.php
namespace App\Http\Resources\Agent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'status'      => $this->status,
            'client_name' => $this->client_name ?? $this->agentClient?->tenantUser?->name,
            'created_at'  => $this->created_at?->format('d.m.Y'),
            'earnings'    => $this->transactions()->sum('amount'),
        ];
    }
}
