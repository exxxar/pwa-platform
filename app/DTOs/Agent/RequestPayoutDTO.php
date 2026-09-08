<?php
// app/DTOs/Agent/RequestPayoutDTO.php
namespace App\DTOs\Agent;

class RequestPayoutDTO
{
    public function __construct(
        public readonly float $amount,
        public readonly ?string $comment = null,
    ) {}
}
