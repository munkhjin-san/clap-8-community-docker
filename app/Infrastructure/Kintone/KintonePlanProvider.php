<?php

// app/Infrastructure/Kintone/KintonePlanProvider.php
// app/Infrastructure/Kintone/KintonePlanProvider.php
namespace App\Infrastructure\Kintone;

use App\Domain\Contracts\PlanProvider;
use Carbon\CarbonImmutable;

class KintonePlanProvider implements PlanProvider
{
    public function __construct(private KintoneClient $api) {}

    /**
     * Fetch monthly plans for a single department (部門) on a specific 日付 (month end).
     * Return shape: [ 部門 => ['sales'=>..., 'internal_sales'=>..., 'sga'=>..., 'indirect_alloc'=>..., 'profit'=>..., 'profit_rate'=>...] ]
     */
    public function fetchMonthlyPlans(CarbonImmutable $period, array $projectNames = []): array
    {
        // If you want ALL departments for that month, drop 部門 filter from $query.
        $endOfMonth = $period->endOfMonth()->format('Y-m-d');
        $appId  = env('KINTONE_PLANS_APP_ID', 1068);
        $fields = ['部門','日付','売上高合計','内部売上高合計','販売管理費合計','間接費配賦'];

        $out = [];
        $query = sprintf('日付 = "%s"', $this->esc($endOfMonth));
        $offset = 0; $limit = 500;
        // Example: only by date; add 部門 = "xxx" if you want per-dept filtering here
        // foreach (array_values(array_unique($projectNames)) as $name) {
            // $dept = $this->esc((string)$name);
            // $query = sprintf('部門 = "%s" and 日付 = "%s"', $dept, $endOfMonth);
            
            
            do {
                $recs = $this->api->getRecords($appId, $query . " limit {$limit} offset {$offset}", $fields);
                foreach ($recs as $r) {
                    $dept = (string)($r['部門']['value'] ?? '');
                    if ($dept === '') continue;
                    $totalSales = round((float) $r['売上高合計']['value'] + (float) $r['内部売上高合計']['value'], 0, PHP_ROUND_HALF_UP);
                    $totalExpense = round((float)  $r['販売管理費合計']['value'] + (float) $r['間接費配賦']['value'], 0, PHP_ROUND_HALF_UP);

                    $out[$dept] = [
                        'sales'          => $totalSales,
                        'expenses'        => $totalExpense,
                    ];
                }

                $count = count($recs);

                $offset += $count;

                usleep(120000); // 120ms
            } while ($count === $limit);
        // }
        return $out;
    }

    private function f($v): ?float
    {
        if ($v === null || $v === '') return null;
        $f = (float) $v;
        return is_finite($f) ? $f : null;
    }

    private function esc(string $v): string
    {
        return addcslashes($v, "\"\\");
    }
}

