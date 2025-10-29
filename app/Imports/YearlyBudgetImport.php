<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class YearlyBudgetImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        private int $projectId,
        private int $fiscalYear,
        private string $projectName,
    ) {
        HeadingRowFormatter::default('none');
    }

    // Row 1 is headers: A1=Category (please don’t leave it blank), B1..M1 months
    public function headingRow(): int { return 1; }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Category cell. If A1 was left blank, this becomes $row[''].
            $category = trim((string)($row[$this->projectName] ?? $row[''] ?? ''));

            if ($category === '') continue;

            foreach ($row as $header => $value) {
                if (in_array(Str::lower($header), [$this->projectName, ''])) continue;
                if ($value === null || $value === '') continue;

                // accept 2025-03 or 2025_03
                if (!preg_match('/^\d{4}[-_]\d{2}$/', $header)) continue;

                $label  = str_replace('_', '-', $header);
                $period = Carbon::createFromFormat('Y-m', $label)->startOfMonth();

                // enforce FY Mar..Feb
                $fyStart = Carbon::create($this->fiscalYear, 3, 1);
                $fyEnd   = $fyStart->copy()->addMonths(12)->subDay();
                if (!$period->betweenIncluded($fyStart, $fyEnd)) continue;

                $amount = $this->toNumber($value);

                // Route by category
                if ($col = $this->salesColumn($category)) {
                    \DB::table('project_sales')->updateOrInsert(
                        ['project_record_id' => $this->projectId, 'period' => $period->toDateString()],
                        [$col => $amount, 'updated_at' => now(), 'created_at' => now()]
                    );
                    continue;
                }

                if ($col = $this->expenseColumn($category)) {
                    \DB::table('project_expenses')->updateOrInsert(
                        ['project_record_id' => $this->projectId, 'period' => $period->toDateString()],
                        [$col => $amount, 'updated_at' => now(), 'created_at' => now()]
                    );
                    continue;
                }

                // Unknown category: ignore or log
                // \Log::warning('Unknown budget category', ['category' => $category]);
            }
        }
    }

    private function toNumber(mixed $v): float
    {
        if (is_numeric($v)) return (float)$v;
        // handle "1,234" or "(1,234)" for negatives
        $s = trim((string)$v);
        $neg = false;
        if (preg_match('/^\((.*)\)$/', $s, $m)) { $s = $m[1]; $neg = true; }
        $s = str_replace([',', ' '], '', $s);
        $n = is_numeric($s) ? (float)$s : 0.0;
        return $neg ? -$n : $n;
    }

    private function salesColumn(string $category): ?string
    {
        return match(trim($category)) {
            '合計 売上高'         => 'sales',
            '合計 内部売上高合計' => 'internal_sales',
            default               => null,
        };
    }

    private function expenseColumn(string $category): ?string
    {
        return match(trim($category)) {
            '合計 給料手当'     => 'salaries',
            '合計 外注費'       => 'outsourcing',
            '合計 内部発注合計' => 'internal_orders',
            '合計 販管費その他' => 'sga_other',
            '合計 間接費配賦'   => 'indirect',
            '業績連動型賞与引当金' => 'bonus',
            default               => null,
        };
    }
}
