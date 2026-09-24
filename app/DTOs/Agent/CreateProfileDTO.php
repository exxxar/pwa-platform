<?php

namespace App\DTOs\Agent;

use App\Http\Requests\Agent\CreateProfileRequest;

class CreateProfileDTO
{
    public function __construct(
        public readonly string $legal_type,
        public readonly string $inn,
        public readonly ?string $ogrn,
        public readonly string $bank_account,
        public readonly string $bik,
        public readonly string $bank_name,
    ) {}

    /**
     * Создаем DTO из валидированного запроса
     */
    public static function fromRequest(CreateProfileRequest $request): self
    {
        return new self(
            legal_type: $request->validated('legal_type'),
            inn: $request->validated('inn'),
            ogrn: $request->validated('ogrn'),
            bank_account: $request->validated('bank_account'),
            bik: $request->validated('bik'),
            bank_name: $request->validated('bank_name'),
        );
    }

    // 🆕 ДОБАВЛЯЕМ ЭТОТ МЕТОД ДЛЯ СОВМЕСТИМОСТИ
    /**
     * Создаем DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            legal_type: $data['legal_type'] ?? 'self_employed',
            inn: $data['inn'] ?? '',
            ogrn: $data['ogrn'] ?? null,
            bank_account: $data['bank_account'] ?? '',
            bik: $data['bik'] ?? '',
            bank_name: $data['bank_name'] ?? '',
        );
    }

    /**
     * Преобразуем DTO в массив для массового заполнения модели
     */
    public function toArray(): array
    {
        return [
            'legal_type' => $this->legal_type,
            'inn' => $this->inn,
            'ogrn' => $this->ogrn,
            'bank_account' => $this->bank_account,
            'bik' => $this->bik,
            'bank_name' => $this->bank_name,
        ];
    }
}
