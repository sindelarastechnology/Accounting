<?php

namespace App\Support;

class AccountingPrecision
{
    const SCALE = 4;

    public static function format(float $value): string
    {
        return number_format($value, self::SCALE, '.', '');
    }

    public static function compare(float $a, float $b): int
    {
        return bccomp(self::format($a), self::format($b), self::SCALE);
    }
}
