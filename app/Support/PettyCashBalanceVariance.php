<?php

namespace App\Support;

final class PettyCashBalanceVariance
{
    public const TOLERANCE_GREEN = 1000;

    public const TOLERANCE_YELLOW = 1000000;

    public static function level(?float $selisih): ?string
    {
        if ($selisih === null) {
            return null;
        }

        $absolute = abs($selisih);

        if ($absolute <= self::TOLERANCE_GREEN) {
            return 'green';
        }

        if ($absolute <= self::TOLERANCE_YELLOW) {
            return 'yellow';
        }

        return 'red';
    }

    public static function bootstrapTextClass(?string $level): string
    {
        return match ($level) {
            'green' => 'text-success',
            'yellow' => 'text-warning',
            'red' => 'text-danger',
            default => '',
        };
    }
}
