<?php
// app/DTOs/Agent/UpdateProfileDTO.php
namespace App\DTOs\Agent;

class UpdateProfileDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $legal_type = null,
        public readonly ?string $inn = null,
        public readonly ?string $ogrn = null,
        public readonly ?string $bank_account = null,
        public readonly ?string $bik = null,
        public readonly ?string $bank_name = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            legal_type: $data['legal_type'] ?? null,
            inn: $data['inn'] ?? null,
            ogrn: $data['ogrn'] ?? null,
            bank_account: $data['bank_account'] ?? null,
            bik: $data['bik'] ?? null,
            bank_name: $data['bank_name'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'legal_type' => $this->legal_type,
            'inn' => $this->inn,
            'ogrn' => $this->ogrn,
            'bank_account' => $this->bank_account,
            'bik' => $this->bik,
            'bank_name' => $this->bank_name,
        ], fn ($v) => $v !== null);
    }
}
