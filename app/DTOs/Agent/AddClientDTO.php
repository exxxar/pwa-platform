<?php
// app/DTOs/Agent/AddClientDTO.php
namespace App\DTOs\Agent;

class AddClientDTO
{
    public function __construct(
        public readonly int $tenant_user_id,
        public readonly ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_user_id: (int) $data['tenant_user_id'],
            notes: $data['notes'] ?? null,
        );
    }
}
