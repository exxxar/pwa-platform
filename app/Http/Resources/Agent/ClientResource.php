<?php
// app/Http/Resources/Agent/ClientResource.php
namespace App\Http\Resources\Agent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'status'     => $this->status,
            'notes'      => $this->notes,
            'tenant_user' => [
                'id'    => $this->tenantUser->id,
                'name'  => $this->tenantUser->name,
                'email' => $this->tenantUser->email,
                'phone' => $this->tenantUser->phone,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
