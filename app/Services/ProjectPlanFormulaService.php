<?php

namespace App\Services;

use App\Models\ProjectAccount;
use App\Models\ProjectPlanAmount;

class ProjectPlanFormulaService
{
    public function buildMonthlyBalance(
        int $projectId,
        int $planYearId,
        int $startMonth,
        int $scenarioKey,
        array $codeMap,
        ?float $bonusRate = null
    ): array {
        $bonusRate = $bonusRate ?? 0.1;
        $accounts = ProjectAccount::query()
            ->where('project_record_id', $projectId)
            ->where('is_active', 1)
            ->get(['id', 'code', 'path', 'is_postable', 'is_formula', 'formula']);

        if ($accounts->isEmpty()) {
            return [];
        }

        $amountRows = ProjectPlanAmount::query()
            ->where('project_record_id', $projectId)
            ->where('project_plan_year_id', $planYearId)
            ->where('scenario_key', $scenarioKey)
            ->get(['project_account_id', 'period_index', 'amount']);

        $amounts = [];
        foreach ($amountRows as $row) {
            $amounts[(int) $row->period_index][(int) $row->project_account_id] = (float) $row->amount;
        }

        $accountByCode = [];
        $postableAccounts = [];
        foreach ($accounts as $acct) {
            $accountByCode[(string) $acct->code] = $acct;
            if ($acct->is_postable) {
                $postableAccounts[] = $acct;
            }
        }

        $out = [];
        $memo = [];
        $startMonth = max(1, min(12, $startMonth));

        for ($periodIndex = 1; $periodIndex <= 12; $periodIndex++) {
            $month = (($startMonth - 1 + ($periodIndex - 1)) % 12) + 1;
            $vals = [];

            foreach ($codeMap as $key => $code) {
                $acct = $accountByCode[$code] ?? null;
                $val = 0.0;
                if ($acct) {
                    $stack = [];
                    $val = $this->evaluateAccount(
                        $acct,
                        $periodIndex,
                        $amounts,
                        $accountByCode,
                        $postableAccounts,
                        $memo,
                        $stack,
                        $bonusRate
                    );
                }
                $vals[$key] = (int) round($val, 0, PHP_ROUND_HALF_UP);
            }

            $sales = $vals['sales'] ?? 0;
            $profit = $vals['profit'] ?? 0;
            $t_expense = $vals['t_expense'] ?? 0;
            $bonus = $vals['bonus'] ?? 0;
            $vals['profit_rate'] = $sales !== 0
                ? round(($profit / $sales) * 100, 2, PHP_ROUND_HALF_UP)
                : null;
            $vals['expense'] = $t_expense + $bonus;
            $out[$month] = $vals;
        }

        return $out;
    }

    private function evaluateAccount(
        ProjectAccount $acct,
        int $periodIndex,
        array $amounts,
        array $accountByCode,
        array $postableAccounts,
        array &$memo,
        array &$stack,
        float $bonusRate
    ): float {
        $key = $acct->id . ':' . $periodIndex;
        if (array_key_exists($key, $memo)) {
            return $memo[$key];
        }
        if (isset($stack[$acct->id])) {
            $memo[$key] = 0.0;
            return 0.0;
        }
        if (! $acct->is_formula) {
            $val = (float) ($amounts[$periodIndex][$acct->id] ?? 0);
            $memo[$key] = $val;
            return $val;
        }

        $stack[$acct->id] = true;
        $expr = (string) ($acct->formula ?? '');
        if ($acct->code === '9120' && !str_contains($expr, '{bonus_rate}')) {
            $normalized = preg_replace('/\s+/', '', $expr);
            if ($normalized === '[9110]*0.2' || $normalized === '[9110]*0.1') {
                $expr = '[9110]*{bonus_rate}';
            }
        }
        $expr = str_replace('{bonus_rate}', (string) $bonusRate, $expr);

        $replaced = preg_replace_callback('/\[([0-9]{4})(\/\*)?\]/', function ($m) use (
            $periodIndex,
            $amounts,
            $accountByCode,
            $postableAccounts,
            &$memo,
            &$stack,
            $bonusRate
        ) {
            $code = $m[1];
            $isSection = $m[2] ?? null;
            if ($isSection) {
                return (string) $this->sumByPath($periodIndex, $code, $postableAccounts, $amounts);
            }
            $dep = $accountByCode[$code] ?? null;
            if (! $dep) {
                return '0';
            }
            return (string) $this->evaluateAccount(
                $dep,
                $periodIndex,
                $amounts,
                $accountByCode,
                $postableAccounts,
                $memo,
                $stack,
                $bonusRate
            );
        }, $expr);

        $replaced = preg_replace('/\s+/', '', $replaced ?? '');
        $val = $this->safeEval($replaced);

        unset($stack[$acct->id]);
        $memo[$key] = $val;

        return $val;
    }

    private function sumByPath(
        int $periodIndex,
        string $code,
        array $postableAccounts,
        array $amounts
    ): float {
        $prefix = '/' . $code . '/';
        $sum = 0.0;
        foreach ($postableAccounts as $acct) {
            if (str_starts_with((string) $acct->path, $prefix)) {
                $sum += (float) ($amounts[$periodIndex][$acct->id] ?? 0);
            }
        }
        return $sum;
    }

    private function safeEval(string $expr): float
    {
        if ($expr === '' || ! preg_match('/^[0-9+\-*\/().]+$/', $expr)) {
            return 0.0;
        }
        try {
            $val = eval('return ' . $expr . ';');
        } catch (\Throwable $e) {
            return 0.0;
        }
        if (! is_numeric($val)) {
            return 0.0;
        }
        $num = (float) $val;
        return is_finite($num) ? $num : 0.0;
    }
}
