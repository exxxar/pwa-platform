<?php
// app/Http/Resources/Agent/TransactionResource.php
namespace App\Http\Resources\Agent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'amount'      => (float) $this->amount,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'date'        => $this->created_at?->format('d.m.Y H:i'),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
