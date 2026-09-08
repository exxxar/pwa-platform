<?php
// app/Http/Resources/Agent/InvoiceResource.php
namespace App\Http\Resources\Agent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'number'        => $this->number,
            'client_name'   => $this->client_name,
            'client_email'  => $this->client_email,
            'service_type'  => $this->service_type,
            'amount'        => (float) $this->amount,
            'description'   => $this->description,
            'status'        => $this->status,
            'sent_at'       => $this->sent_at?->toIso8601String(),
            'paid_at'       => $this->paid_at?->toIso8601String(),
            'due_date'      => $this->due_date?->toIso8601String(),
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
