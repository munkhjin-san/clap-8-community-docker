<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Dynamic date values for Flow filters — 今日 / 今月 / 今年 … instead of a hard-coded date, so a
 * saved view keeps meaning the same thing tomorrow.
 *
 * Stored in a filter's `values` as the sentinel string "@today", "@this_month", … . The "@" prefix
 * can never collide with a real value: fixed dates arrive as "2026-07-28".
 *
 * Every token resolves to a [start, end] RANGE, not a single date. That is deliberate — "今日" on a
 * datetime column has to cover 00:00:00–23:59:59, so treating single days and periods the same way
 * lets one code path serve both `date` and `datetime` fields.
 *
 * ⚠ Mirror of resources/js/utils/flowDynamicDate.ts — client mode (apps with record-level
 * permissions) filters in the browser, so the two must resolve identically. Change both together.
 * Weeks start MONDAY on both sides.
 */
class FlowDynamicDate
{
    public const PREFIX = '@';

    /** token => Japanese label (order is the order shown in the picker). */
    public const TOKENS = [
        'today' => '今日',
        'yesterday' => '昨日',
        'tomorrow' => '明日',
        'this_week' => '今週',
        'last_week' => '先週',
        'next_week' => '来週',
        'this_month' => '今月',
        'last_month' => '先月',
        'next_month' => '来月',
        'this_year' => '今年',
        'last_year' => '昨年',
        'next_year' => '来年',
    ];

    public static function isDynamic($value): bool
    {
        return is_string($value)
            && str_starts_with($value, self::PREFIX)
            && array_key_exists(substr($value, 1), self::TOKENS);
    }

    /**
     * [start, end] for a token, inclusive on both ends.
     * $withTime → datetime bounds ('Y-m-d H:i:s'); otherwise plain dates ('Y-m-d').
     */
    public static function resolve(string $value, bool $withTime): ?array
    {
        if (! self::isDynamic($value)) {
            return null;
        }
        $now = Carbon::now();

        [$start, $end] = match (substr($value, 1)) {
            'today' => [$now->copy(), $now->copy()],
            'yesterday' => [$now->copy()->subDay(), $now->copy()->subDay()],
            'tomorrow' => [$now->copy()->addDay(), $now->copy()->addDay()],
            'this_week' => self::week($now),
            'last_week' => self::week($now->copy()->subWeek()),
            'next_week' => self::week($now->copy()->addWeek()),
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'next_month' => [$now->copy()->addMonthNoOverflow()->startOfMonth(), $now->copy()->addMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'next_year' => [$now->copy()->addYear()->startOfYear(), $now->copy()->addYear()->endOfYear()],
            default => [null, null],
        };

        if (! $start || ! $end) {
            return null;
        }

        return $withTime
            ? [$start->startOfDay()->format('Y-m-d H:i:s'), $end->endOfDay()->format('Y-m-d H:i:s')]
            : [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    /** Monday-start week containing $day (Carbon's default first-day is locale-dependent — pin it). */
    private static function week(CarbonInterface $day): array
    {
        return [$day->copy()->startOfWeek(CarbonInterface::MONDAY), $day->copy()->endOfWeek(CarbonInterface::SUNDAY)];
    }
}
