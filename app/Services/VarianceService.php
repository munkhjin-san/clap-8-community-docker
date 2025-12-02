<?php

namespace App\Services;

final class VarianceService
{
    public static function pct(?float $num, ?float $den): ?float
    {   
        if ($den == 0.0 && $num > 0.0) return 0;
        if ($num === null || $den === null || $den == 0.0 || is_nan($num) || is_nan($den)) return null;
        return ($num / $den) * 100.0;
    }

    public static function achToVar(?float $ach): ?float
    {
        return $ach === null ? null : ($ach - 100.0);
    }

    public static function anyOverThreshold(array $v, int|float $threshold): bool
    {
        foreach (['sales','expense','profit'] as $k) {
            $x = $v[$k] ?? null;
            if ($x !== null && abs($x) >= $threshold) return true;
        }
        return false;
    }
}