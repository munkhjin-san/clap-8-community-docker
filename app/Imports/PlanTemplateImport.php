<?php

namespace App\Imports;

use App\Models\ProjectAccount;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Illuminate\Support\Facades\DB;

HeadingRowFormatter::default('none');

class PlanTemplateImport implements ToCollection, WithHeadingRow
{
    /**
     * @param array<int,array{label:string,period_index:int}> $periods
     */
    public function __construct(
        private int $projectId,
        private int $planYearId,
        private ?int $scenarioId,
        private array $periods,
        private bool $dryRun = false
    ) {}

    public array $summary = [
        'rows_total' => 0,
        'rows_matched' => 0,
        'cells_total' => 0,
        'cells_applied' => 0,
        'unknown_accounts' => [],
        'skipped_empty' => 0,
    ];

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows)
    {
        $accountMap = ProjectAccount::where('project_record_id', $this->projectId)
            ->pluck('id', 'code')
            ->all();
        // build name map only for unique names to avoid ambiguity
        $nameMap = [];
        $nameCounts = ProjectAccount::where('project_record_id', $this->projectId)
            ->select('name', DB::raw('COUNT(*) as c'))
            ->groupBy('name')
            ->pluck('c', 'name')
            ->all();
        $nameToId = ProjectAccount::where('project_record_id', $this->projectId)
            ->pluck('id', 'name')
            ->all();
        foreach ($nameToId as $name => $id) {
            if (($nameCounts[$name] ?? 0) === 1) {
                $nameMap[$name] = $id;
            }
        }

        $periodMap = [];
        foreach ($this->periods as $p) {
            $periodMap[$p['label']] = $p['period_index'];
        }

        $upserts = [];

        foreach ($rows as $row) {
            $this->summary['rows_total']++;
            $code = trim((string) ($row['account_code'] ?? $row['code'] ?? ''));
            $name = trim((string) ($row['account_name'] ?? $row['name'] ?? ''));

            if ($code === '' && $name === '') {
                continue; // blank row
            }
            $accountId = $accountMap[$code] ?? ($name !== '' ? ($nameMap[$name] ?? null) : null);
            if (! $accountId) {
                $this->summary['unknown_accounts'][] = $code !== '' ? $code : $name;
                continue; // unknown account code
            }
            $this->summary['rows_matched']++;

            foreach ($row as $header => $value) {
                if (!isset($periodMap[$header])) {
                    continue;
                }
                $this->summary['cells_total']++;
                if ($value === null || $value === '') {
                    $this->summary['skipped_empty']++;
                    continue;
                }
                $amount = $this->toNumber($value);
                $this->summary['cells_applied']++;
                $upserts[] = [
                    'project_record_id'        => $this->projectId,
                    'project_plan_year_id'     => $this->planYearId,
                    'project_account_id'       => $accountId,
                    'project_plan_scenario_id' => $this->scenarioId,
                    'scenario_key'             => $this->scenarioId ?? 0,
                    'period_index'             => $periodMap[$header],
                    'amount'                   => $amount,
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ];
            }
        }

        if ($upserts && ! $this->dryRun) {
            DB::table('project_plan_amounts')->upsert(
                $upserts,
                ['project_record_id','project_plan_year_id','project_account_id','period_index','scenario_key'],
                ['amount','updated_at']
            );
        }
    }

    private function toNumber(mixed $v): float
    {
        if (is_numeric($v)) return (float) $v;
        $s = trim((string)$v);
        $neg = false;
        if (preg_match('/^\((.*)\)$/', $s, $m)) { $s = $m[1]; $neg = true; }
        $s = str_replace([',', ' '], '', $s);
        $n = is_numeric($s) ? (float)$s : 0.0;
        return $neg ? -$n : $n;
    }
}
