<?php
// app/Http/Resources/Agent/PayoutResource.php
namespace App\Http\Resources\Agent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'number'           => $this->number,
            'amount'           => (float) $this->amount,
            'status'           => $this->status,
            'bank_details'     => $this->bank_details,
            'comment'          => $this->comment,
            'rejection_reason' => $this->rejection_reason,
            'processed_at'     => $this->processed_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
