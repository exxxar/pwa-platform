<?php
// app/DTOs/Agent/CreateInvoiceDTO.php
namespace App\DTOs\Agent;

class CreateInvoiceDTO
{
    public function __construct(
        public readonly string $client_name,
        public readonly ?string $client_email,
        public readonly ?string $client_phone,
        public readonly float $amount,
        public readonly string $service_type = 'bot',
        public readonly ?string $description = null,
        public readonly ?int $tenant_user_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            client_name: $data['client_name'],
            client_email: $data['client_email'] ?? null,
            client_phone: $data['client_phone'] ?? null,
            amount: (float) $data['amount'],
            service_type: $data['service_type'] ?? 'bot',
            description: $data['description'] ?? null,
            tenant_user_id: $data['tenant_user_id'] ?? null,
        );
    }
}
