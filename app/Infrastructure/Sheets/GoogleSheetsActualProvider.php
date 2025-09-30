<?php

// app/Infrastructure/Sheets/GoogleSheetsActualProvider.php
namespace App\Infrastructure\Sheets;

use App\Domain\Contracts\ActualProvider;
use Carbon\CarbonImmutable;
use Google\Service\Sheets as GoogleSheets;

class GoogleSheetsActualProvider implements ActualProvider
{
    public function __construct(private GoogleSheetsClient $client) {}

    public function fetchMonthlyActuals(CarbonImmutable $period, array $projectNames = []): array
    {
        $svc    = $this->client->svc;
        $sheet  = config('services.google.spreadsheet_id');
        $tab   = $period->format('Ym');
        $range = $tab.'!A:Z';

        $resp   = $svc->spreadsheets_values->get($sheet, $range);
        $rows   = $resp->getValues() ?? [];
        if (count($rows) < 2) return [];

        // header row at index 1, data from index 2 onward (0-based)
        $headers = $rows[1];
        $data    = array_slice($rows, 2);

        // make an index map: '列名' => index
        $idx = fn(string $col) => array_search($col, $headers, true);

        $iProject   = $idx('部門') ?? $idx('案件名') ?? 1; // fallback to column 1 like your code
        $iSales     = $idx('収入');
        $iExpense   = $idx('支出');
        $iIndirect  = $idx('間接費配賦'); // if you want to include it

        // sanity checks
        if ($iProject === null) return [];

        // optional project filter set (normalize to a set for O(1) lookups)
        $filterSet = [];
        if (!empty($projectNames)) {
            foreach ($projectNames as $p) $filterSet[(string)$p] = true;
        }

        $out = []; // [project_name => ['sales'=>.., 'expense'=>.., 'profit'=>..]]

        foreach ($data as $row) {
            $project = $row[$iProject] ?? null;
            if (!$project) continue;
            if ($filterSet && !isset($filterSet[(string)$project])) continue;

            $sales   = $this->f($row[$iSales]   ?? null);
            $expense = $this->f($row[$iExpense] ?? null) + $this->f($row[$iIndirect] ?? null);
            

            $out[(string)$project] = [
                'sales'   => $sales,
                'expenses' => $expense,
            ];
        }

        return $out;
    }

    private function f($v): ?float
    {
        if ($v === null || $v === '') return null;
        // Google returns numbers as strings; be forgiving with commas
        $v = is_string($v) ? str_replace([','], '', $v) : $v;
        $f = (float)$v;
        return is_finite($f) ? $f : null;
    }
}

