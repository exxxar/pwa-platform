<?php

namespace App\Enums;

enum IntegrationTypeEnum: string
{
    case AMO = 'amo';
    case BITRIX = 'bitrix';
    case IIKO = 'iiko';
    case YCLIENTS = 'yclients';
    case FRONTPAD = 'frontpad';
    case CDEK = 'cdek';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromMethod(string $method): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $method) {
                return $case;
            }
        }

        return null;
    }
}
