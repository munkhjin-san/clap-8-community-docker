<?php

namespace App\Support;

/**
 * Canonical 社員コード normalization, shared by the cost-master sync, the
 * actuals allocation engine, and ProfitPlan v2's plan-vs-actual join.
 * Handles float-formatted codes ("123.0") and leading zeros ("0049" == "49").
 */
class EmployeeCode
{
    public static function normalize(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        if (preg_match('/^\d+\.0+$/', $code) === 1) {
            $code = (string) (int) $code;
        }

        $withoutLeadingZeros = ltrim($code, '0');

        return $withoutLeadingZeros === '' ? '0' : $withoutLeadingZeros;
    }
}
