<?php

namespace App\Services;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

class ActualResultCalculationService
{
    private const INDIRECT_RATE = 0.10;

    private const PERFORMANCE_BONUS_RATE = 0.10;

    private const OPERATING_PROFIT_TOTAL_LABELS = [
        '営業損益金額',
        '営業利益',
        '営業損益',
    ];

    private const SPECIAL_PERFORMANCE_BONUS_RATES = [
        'BS関西(IWAN)' => [
            'rate' => 0.20,
            'through_month' => '2026-11',
        ],
        '熊本INSｻﾋﾞﾄﾗ' => [
            'rate' => 0.20,
            'through_month' => '2026-10'
        ]
    ];

    private const SOURCE_CSV_FINALIZED = 'csv_finalized';

    private const SOURCE_RESERVE_CSV_UPLOADED = 'reserve_csv_uploaded';

    private const SOURCE_AUTO_CALCULATED = 'auto_calculated';

    private const INDIRECT_DEPARTMENT = '間接費部門';

    private const RESERVE_DEPARTMENT = '積立部門';

    private const MANAGEMENT_DEPARTMENT = '経営管理本部';

    private const EXECUTIVE_DEPARTMENT = '役員部門';

    private const BONUS_ACCRUAL_EXPENSE_ACCOUNT = '賞与引当金繰入額';

    private const NO_INDIRECT_ALLOCATION_DEPARTMENTS = [
        self::INDIRECT_DEPARTMENT,
        self::RESERVE_DEPARTMENT,
        self::MANAGEMENT_DEPARTMENT,
        self::EXECUTIVE_DEPARTMENT,
    ];

    private const NO_PERFORMANCE_BONUS_DEPARTMENTS = [
        self::MANAGEMENT_DEPARTMENT,
        self::EXECUTIVE_DEPARTMENT,
        self::INDIRECT_DEPARTMENT,
        self::RESERVE_DEPARTMENT,
        'ｵﾝｻｲﾄ営業支援(物販)',
    ];

    private const DEPARTMENT_ALIASES = [
        '経営管理本部共通部門' => '経営管理本部',
        'ｵﾝｻｲﾄ営業支援(ｵﾌﾌﾟﾗ)' => 'ｵﾝｻｲﾄ営業支援(現調・設置・工事)',
    ];

    private const COGS_DEPARTMENTS = [
        'OptiLinkｱﾗｲｱﾝｽ営業担当',
        'ｵﾝｻｲﾄ営業支援(物販)',
    ];

    private const RESERVE_DEPARTMENT_SG_AND_A_ACCOUNTS = [
        '給料手当',
        '法定福利費',
        '福利厚生費',
        self::BONUS_ACCRUAL_EXPENSE_ACCOUNT,
    ];

    private const SALES_KEYWORDS = [
        '売上',
        '収入',
        '収益',
        '入金',
        '雑益',
    ];

    private const RESERVE_BUCKETS = [
        '基本賞与積立金' => 'basic_bonus_reserve',
        '業績連動賞与積立金' => 'performance_bonus_reserve',
        '有給積立金' => 'paid_leave_reserve',
        '有休積立金' => 'paid_leave_reserve',
        '福利厚生積立金' => 'welfare_reserve',
        '福利厚生費積立金' => 'welfare_reserve',
        'リフレッシュ補助積立金' => 'refresh_reserve',
        'リフレッシュ積立金' => 'refresh_reserve',
    ];

    private const BASE_RESERVE_BUCKETS = [
        'basic_bonus_reserve',
        'paid_leave_reserve',
        'welfare_reserve',
        'refresh_reserve',
    ];

    private const BUCKET_LABELS = [
        'operating_sales' => '売上',
        'reserve_transfer_sales' => '積立振替売上',
        'indirect_allocation_sales' => '間接配賦売上',
        'other_sales' => 'その他収入',
        'ordinary_expense' => '通常経費',
        'basic_bonus_reserve' => '基本賞与積立金',
        'performance_bonus_reserve' => '業績連動賞与積立金',
        'paid_leave_reserve' => '有給積立金',
        'welfare_reserve' => '福利厚生積立金',
        'refresh_reserve' => 'リフレッシュ積立金',
        'indirect_allocation_expense' => '間接配賦発注額',
        'beginning_inventory' => '期首商品棚卸高',
        'purchases' => '仕入高',
        'ending_inventory' => '期末商品棚卸高',
    ];

    private const RESERVE_TRANSFER_SALES_ACCOUNTS = [
        'basic_bonus_reserve' => '社内振替入金（基本賞与）',
        'paid_leave_reserve' => '社内振替入金（有給）',
        'performance_bonus_reserve' => '社内振替入金（業績連動賞与）',
        'welfare_reserve' => '社内振替入金（福利）',
        'refresh_reserve' => '社内振替入金（リフレッシュ）',
    ];

    private const GENERATED_DEPARTMENT_CHARGE_ACCOUNTS = [
        'basic_bonus_reserve' => '基本賞与積立金',
        'paid_leave_reserve' => '有給積立金',
        'performance_bonus_reserve' => '業績連動賞与積立金',
        'welfare_reserve' => '福利厚生積立金',
        'refresh_reserve' => 'リフレッシュ補助積立金',
        'indirect_allocation_expense' => '間接配賦発注額',
    ];

    private const GENERATED_AMOUNT_SOURCES = [
        'generated_charge',
        'generated_internal_sales',
        'generated_bonus_accrual',
        'timecard_kintone',
    ];

    public function __construct(private ?ActualReserveAllocationService $reserveAllocation = null)
    {
    }

    /**
     * freee会計APIから取り込んだ明細行から実績を計算する。
     *
     * @param array<int, array<string, mixed>> $detailRows accumulateDetailRow が受け取る形の明細
     * @param array<string, mixed> $fileMeta 画面表示用のメタ情報
     */
    public function calculateFromFreee(array $detailRows, string $calculationMonth, array $fileMeta = []): array
    {
        $departments = [];
        $accountTotals = [];

        foreach ($detailRows as $row) {
            $this->accumulateDetailRow($departments, $accountTotals, $row);
        }

        if ($departments === []) {
            throw $this->validationException([
                'file' => 'freeeから部門別の明細を取得できませんでした。対象月に仕訳があるか、プロジェクトとfreee部門が連携済みかを確認してください。',
            ]);
        }

        return $this->finalizeResult($departments, $accountTotals, $calculationMonth, $fileMeta + [
            'detail_rows' => count($detailRows),
            'skipped_rows' => 0,
        ]);
    }

    /**
     * GLOWDが計算してfreeeへ書き出す科目かどうか。
     *
     * 取込時にこれらを除外することで、自分が登録した値を読み戻して
     * 「計算せずfreeeの値をそのまま使う」状態に陥るのを防ぐ。
     */
    /**
     * 取込時に別名を畳んだ部門を、freeeの部門名に戻すための候補を返す。
     *
     * 例：取込では 経営管理本部共通部門 → 経営管理本部 に畳むので、
     * 送信時は 経営管理本部 → 経営管理本部共通部門 に戻さないと
     * freee側に該当する部門が無く、仕訳を送れない。
     * 同名の部門がfreeeにあればそちらが優先されるよう、自分自身を先頭に置く。
     *
     * @return array<int, string>
     */
    public function freeeSectionNameCandidates(string $department): array
    {
        $names = [$department];

        foreach (self::DEPARTMENT_ALIASES as $source => $target) {
            if ($target === $department) {
                $names[] = $source;
            }
        }

        return $names;
    }

    public function isGeneratedAccountName(string $accountName): bool
    {
        if (
            str_contains($accountName, '社内振替入金')
            || str_contains($accountName, '間接配賦売上高')
            || str_contains($accountName, '間接配賦発注額')
            || $accountName === self::BONUS_ACCRUAL_EXPENSE_ACCOUNT
        ) {
            return true;
        }

        foreach (array_keys(self::RESERVE_BUCKETS) as $needle) {
            if (str_contains($accountName, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 明細の集計が終わったあとの共通処理。取込元に依存しない。
     */
    private function finalizeResult(
        array $departments,
        array $accountTotals,
        ?string $calculationMonth,
        array $fileMeta
    ): array {
        $reserveAllocationResult = null;
        $calculationSources = $this->calculationSourcesFromDepartments($departments);

        if (
            $calculationMonth !== null
            && $this->reserveAllocation !== null
            && $calculationSources['base_reserve_expenses'] === self::SOURCE_AUTO_CALCULATED
        ) {
            $reserveAllocationResult = $this->reserveAllocation->allocationsForMonth($calculationMonth);

            $this->applyGeneratedReserveAllocations(
                $departments,
                $accountTotals,
                $reserveAllocationResult['allocations'] ?? []
            );
        }

        $reserveAllocationStats = $reserveAllocationResult['stats'] ?? [];
        $departmentRows = $this->finishDepartments($departments, $calculationSources, $reserveAllocationStats, $calculationMonth);
        usort($departmentRows, fn (array $a, array $b) => ($b['sales'] <=> $a['sales']) ?: strcmp($a['department'], $b['department']));

        $accountTotalRows = array_values($accountTotals);
        usort($accountTotalRows, fn (array $a, array $b) => strcmp($a['category'], $b['category']) ?: (abs($b['amount']) <=> abs($a['amount'])));

        $summary = $this->summary($departmentRows);
        $generatedBasicBonusAccrualTotal = (int) ($reserveAllocationStats['basic_bonus_accrual_total'] ?? 0);
        $generatedBonusAccrualExpense = $calculationSources['bonus_accrual_expense'] === self::SOURCE_AUTO_CALCULATED
            ? $this->generatedBonusAccrualExpenseFromRows($departmentRows)
            : 0;

        return [
            'file' => $fileMeta + [
                'name' => null,
                'title' => null,
                'period' => null,
                'calculation_month' => $calculationMonth,
                'encoding' => null,
                'source_rows' => 0,
                'detail_rows' => 0,
                'skipped_rows' => 0,
                'generated_reserve_rows' => $reserveAllocationStats['generated_rows'] ?? 0,
                'generated_reserve_total' => $reserveAllocationStats['generated_total'] ?? 0,
                'generated_basic_bonus_accrual_total' => $generatedBasicBonusAccrualTotal,
                'generated_bonus_accrual_expense' => $generatedBonusAccrualExpense,
                'generated_reserve_warnings' => $reserveAllocationResult['warnings'] ?? [],
                'calculation_source_mode' => $this->primaryCalculationSourceMode($calculationSources),
                'calculation_sources' => $calculationSources,
            ],
            'summary' => $summary,
            'departments' => $departmentRows,
            'account_totals' => $accountTotalRows,
        ];
    }

    /**
     * 明細1行を部門別・勘定科目別の集計に足し込む。
     *
     * @param array<string, mixed> $row
     */
    private function accumulateDetailRow(array &$departments, array &$accountTotals, array $row): void
    {
        $sourceDepartment = (string) ($row['source_department'] ?? '');
        $accountName = (string) ($row['account_name'] ?? '');

        if ($sourceDepartment === '' || $accountName === '') {
            return;
        }

        $department = $this->normalizeDepartment($sourceDepartment);
        $accountCode = (string) ($row['account_code'] ?? '');
        $accountMeta = $this->accountMeta($accountName, $accountCode, $department, $row['category_hint'] ?? null);
        $category = $accountMeta['category'];
        $amount = ($row['has_amount'] ?? true) ? (int) ($row['balance'] ?? 0) : 0;
        $endingBalance = (int) ($row['ending_balance'] ?? 0);

        if (! isset($departments[$department])) {
            $departments[$department] = $this->emptyDepartment($department);
        }

        $departments[$department]['row_count']++;
        $this->rememberSourceDepartment($departments[$department], $sourceDepartment);

        if ($category === 'sales') {
            $departments[$department]['sales'] += $amount;
            $departments[$department]['ending_sales'] += $endingBalance;
        } else {
            $departments[$department]['expenses'] += $amount;
            $departments[$department]['ending_expenses'] += $endingBalance;
        }

        $this->accumulateDepartmentBucket($departments[$department], $accountMeta['bucket'], $amount);

        $accountKey = "{$accountCode}|{$accountName}|{$category}|{$accountMeta['bucket']}";
        $payload = [
            'account_code' => $accountCode,
            'account_name' => $accountName,
            'category' => $category,
            'bucket' => $accountMeta['bucket'],
            'bucket_label' => $accountMeta['bucket_label'],
            'amount_source' => (string) ($row['amount_source'] ?? 'balance'),
            'amount' => $amount,
            'debit' => (int) ($row['debit'] ?? 0),
            'credit' => (int) ($row['credit'] ?? 0),
            'balance' => (int) ($row['balance'] ?? 0),
            'ending_balance' => $endingBalance,
            'source_department' => $sourceDepartment,
        ];

        $this->accumulateAccount($departments[$department]['accounts'], $accountKey, $payload);
        $this->accumulateAccount($accountTotals, $accountKey, $payload);
    }

    private function validationException(array $messages): ValidationException
    {
        $validator = new class($messages) implements ValidatorContract {
            private MessageBag $messages;

            public function __construct(array $messages)
            {
                $this->messages = new MessageBag($messages);
            }

            public function getMessageBag()
            {
                return $this->messages;
            }

            public function validate()
            {
                return [];
            }

            public function validated()
            {
                return [];
            }

            public function fails()
            {
                return true;
            }

            public function failed()
            {
                return [];
            }

            public function sometimes($attribute, $rules, callable $callback)
            {
                return $this;
            }

            public function after($callback)
            {
                return $this;
            }

            public function errors()
            {
                return $this->messages;
            }
        };

        return new ValidationException($validator);
    }

    private function calculationSourcesFromDepartments(array $departments): array
    {
        $hasBaseReserveExpenses = $this->hasCsvAccountAmount(
            $departments,
            self::BASE_RESERVE_BUCKETS,
            'expense',
            onlyChargeDepartments: true
        );
        $hasPerformanceBonusReserve = $this->hasCsvAccountAmount(
            $departments,
            ['performance_bonus_reserve'],
            'expense',
            onlyChargeDepartments: true
        );
        $hasIndirectAllocationExpense = $this->hasCsvAccountAmount(
            $departments,
            ['indirect_allocation_expense'],
            'expense',
            onlyChargeDepartments: true
        );
        $hasReserveTransferSales = $this->hasCsvAccountAmount(
            $departments,
            ['reserve_transfer_sales'],
            'sales',
            department: self::RESERVE_DEPARTMENT
        );
        $hasIndirectAllocationSales = $this->hasCsvAccountAmount(
            $departments,
            ['indirect_allocation_sales'],
            'sales',
            department: self::INDIRECT_DEPARTMENT
        );
        $hasBonusAccrualExpense = $this->hasCsvAccountNameAmount(
            $departments,
            [self::BONUS_ACCRUAL_EXPENSE_ACCOUNT],
            'expense'
        );
        $uploadedCalculationSource = $hasReserveTransferSales
            || $hasIndirectAllocationSales
            || $hasBonusAccrualExpense
                ? self::SOURCE_CSV_FINALIZED
                : self::SOURCE_RESERVE_CSV_UPLOADED;

        return $this->normalizeCalculationSources([
            'base_reserve_expenses' => $hasBaseReserveExpenses ? $uploadedCalculationSource : self::SOURCE_AUTO_CALCULATED,
            'performance_bonus_reserve' => $hasPerformanceBonusReserve ? $uploadedCalculationSource : self::SOURCE_AUTO_CALCULATED,
            'indirect_allocation_expense' => $hasIndirectAllocationExpense ? $uploadedCalculationSource : self::SOURCE_AUTO_CALCULATED,
            'reserve_transfer_sales' => $hasReserveTransferSales ? self::SOURCE_CSV_FINALIZED : self::SOURCE_AUTO_CALCULATED,
            'indirect_allocation_sales' => $hasIndirectAllocationSales ? self::SOURCE_CSV_FINALIZED : self::SOURCE_AUTO_CALCULATED,
            'bonus_accrual_expense' => $hasBonusAccrualExpense ? self::SOURCE_CSV_FINALIZED : self::SOURCE_AUTO_CALCULATED,
        ]);
    }

    private function normalizeCalculationSources(array $sources): array
    {
        $defaults = [
            'base_reserve_expenses' => self::SOURCE_AUTO_CALCULATED,
            'performance_bonus_reserve' => self::SOURCE_AUTO_CALCULATED,
            'indirect_allocation_expense' => self::SOURCE_AUTO_CALCULATED,
            'reserve_transfer_sales' => self::SOURCE_AUTO_CALCULATED,
            'indirect_allocation_sales' => self::SOURCE_AUTO_CALCULATED,
            'bonus_accrual_expense' => self::SOURCE_AUTO_CALCULATED,
        ];

        $validSources = [
            self::SOURCE_CSV_FINALIZED,
            self::SOURCE_RESERVE_CSV_UPLOADED,
            self::SOURCE_AUTO_CALCULATED,
        ];

        foreach ($defaults as $key => $default) {
            if (! in_array($sources[$key] ?? null, $validSources, true)) {
                $sources[$key] = $default;
            }
        }

        return array_intersect_key($sources, $defaults);
    }

    private function primaryCalculationSourceMode(array $sources): string
    {
        $sources = $this->normalizeCalculationSources($sources);

        if (in_array(self::SOURCE_RESERVE_CSV_UPLOADED, $sources, true)) {
            return self::SOURCE_RESERVE_CSV_UPLOADED;
        }

        if (in_array(self::SOURCE_CSV_FINALIZED, $sources, true)) {
            return self::SOURCE_CSV_FINALIZED;
        }

        return self::SOURCE_AUTO_CALCULATED;
    }

    private function hasCsvAccountAmount(
        array $departments,
        array $buckets,
        string $category,
        ?string $department = null,
        bool $onlyChargeDepartments = false
    ): bool {
        foreach ($departments as $departmentName => $departmentRow) {
            if ($department !== null && $departmentName !== $department) {
                continue;
            }

            if ($onlyChargeDepartments && ! $this->shouldGenerateDepartmentCharges((string) $departmentName)) {
                continue;
            }

            foreach ($departmentRow['accounts'] ?? [] as $account) {
                if (
                    ($account['category'] ?? null) === $category
                    && in_array($account['bucket'] ?? null, $buckets, true)
                    && (int) ($account['amount'] ?? 0) !== 0
                    && $this->isCsvFinalizedAccount($account)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasCsvAccountNameAmount(array $departments, array $accountNames, string $category): bool
    {
        foreach ($departments as $departmentRow) {
            foreach ($departmentRow['accounts'] ?? [] as $account) {
                if (
                    ($account['category'] ?? null) === $category
                    && in_array($account['account_name'] ?? null, $accountNames, true)
                    && (int) ($account['amount'] ?? 0) !== 0
                    && $this->isCsvFinalizedAccount($account)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isCsvFinalizedAccount(array $account): bool
    {
        return ! in_array((string) ($account['amount_source'] ?? ''), self::GENERATED_AMOUNT_SOURCES, true);
    }

    /**
     * @param array<int, array<string, mixed>> $allocations
     */
    private function applyGeneratedReserveAllocations(array &$departments, array &$accountTotals, array $allocations): void
    {
        foreach ($allocations as $allocation) {
            $department = $this->normalizeDepartment((string) ($allocation['department'] ?? ''));
            $bucket = (string) ($allocation['bucket'] ?? '');
            $amount = (int) round((float) ($allocation['amount'] ?? 0));

            if ($department === '' || $amount === 0 || ! isset(self::BUCKET_LABELS[$bucket])) {
                continue;
            }

            if (! isset($departments[$department])) {
                $departments[$department] = $this->emptyDepartment($department);
            }

            $accountName = (string) ($allocation['account_name'] ?? self::BUCKET_LABELS[$bucket]);
            $sourceDepartment = (string) ($allocation['source_department'] ?? 'timecard work time + Kintone app 96');
            $accountKey = "|{$accountName}|expense|{$bucket}";

            $departments[$department]['row_count']++;
            $this->rememberSourceDepartment($departments[$department], $department);
            $this->accumulateDepartmentBucket($departments[$department], $bucket, $amount);

            $row = [
                'account_code' => '',
                'account_name' => $accountName,
                'category' => 'expense',
                'bucket' => $bucket,
                'bucket_label' => self::BUCKET_LABELS[$bucket],
                'amount_source' => (string) ($allocation['amount_source'] ?? 'timecard_kintone'),
                'amount' => $amount,
                'debit' => $amount,
                'credit' => 0,
                'balance' => $amount,
                'ending_balance' => 0,
                'source_department' => $sourceDepartment,
            ];

            $this->accumulateAccount($departments[$department]['accounts'], $accountKey, $row);
            $this->accumulateAccount($accountTotals, $accountKey, $row);
        }
    }

    /**
     * @param string|null $categoryHint freee APIの勘定科目区分から判る収益/費用の別。
     *                                  勘定科目コードが無いfreee取込で 400番台/800番台の判定を代替する。
     */
    private function accountMeta(string $accountName, string $accountCode, string $department, ?string $categoryHint = null): array
    {
        if (str_contains($accountName, '期首商品棚卸高') || $accountCode === '720') {
            return $this->meta('expense', 'beginning_inventory');
        }

        if (str_contains($accountName, '期末商品棚卸高') || $accountCode === '737') {
            return $this->meta('expense', 'ending_inventory');
        }

        if (str_contains($accountName, '仕入高') || str_contains($accountName, '仕入') || in_array($accountCode, ['721', '725'], true)) {
            return $this->meta('expense', 'purchases');
        }

        if (str_contains($accountName, '間接配賦売上高')) {
            return $this->meta('sales', 'indirect_allocation_sales');
        }

        if (str_contains($accountName, '社内振替入金')) {
            return $this->meta('sales', 'reserve_transfer_sales');
        }

        if (str_contains($accountName, '間接配賦発注額')) {
            return $this->meta('expense', 'indirect_allocation_expense');
        }

        foreach (self::RESERVE_BUCKETS as $needle => $bucket) {
            if (str_contains($accountName, $needle)) {
                return $this->meta('expense', $bucket);
            }
        }

        // 名前で決まる科目を先に確定させたうえで、残りをfreeeの区分で振り分ける。
        // 区分が無ければ従来どおり名前とコードで判定する。
        if ($categoryHint === 'sales') {
            return $this->meta('sales', str_contains($accountName, '雑収入') ? 'other_sales' : 'operating_sales');
        }

        if ($categoryHint === 'expense') {
            return $this->meta('expense', 'ordinary_expense');
        }

        if ($this->isSalesAccount($accountName, $accountCode)) {
            return $this->meta('sales', str_contains($accountName, '雑収入') ? 'other_sales' : 'operating_sales');
        }

        return $this->meta('expense', 'ordinary_expense');
    }

    private function meta(string $category, string $bucket): array
    {
        return [
            'category' => $category,
            'bucket' => $bucket,
            'bucket_label' => self::BUCKET_LABELS[$bucket] ?? $bucket,
        ];
    }






    private function accountKey(array $account): string
    {
        return implode('|', [
            (string) ($account['account_code'] ?? ''),
            (string) ($account['account_name'] ?? ''),
            (string) ($account['category'] ?? ''),
            (string) ($account['bucket'] ?? ''),
        ]);
    }

    private function isSalesAccount(string $accountName, string $accountCode): bool
    {
        foreach (self::SALES_KEYWORDS as $keyword) {
            if (str_contains($accountName, $keyword)) {
                return true;
            }
        }

        if (ctype_digit($accountCode)) {
            $code = (int) $accountCode;

            if (($code >= 400 && $code < 500) || ($code >= 800 && $code < 825)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDepartment(string $department): string
    {
        return self::DEPARTMENT_ALIASES[$department] ?? $department;
    }

    private function isCogsProject(string $department): bool
    {
        return in_array($department, self::COGS_DEPARTMENTS, true);
    }

    private function shouldGenerateDepartmentCharges(string $department): bool
    {
        return ! in_array($department, [self::INDIRECT_DEPARTMENT, self::RESERVE_DEPARTMENT], true);
    }

    private function shouldGenerateIndirectAllocation(string $department): bool
    {
        return ! in_array($department, self::NO_INDIRECT_ALLOCATION_DEPARTMENTS, true);
    }

    private function shouldGeneratePerformanceBonus(string $department): bool
    {
        return $this->shouldGenerateDepartmentCharges($department)
            && ! in_array($department, self::NO_PERFORMANCE_BONUS_DEPARTMENTS, true);
    }

    private function roundRateAmount(int $amount, float $rate): int
    {
        return (int) round($amount * $rate);
    }

    private function performanceBonusRate(string $department, ?string $calculationMonth = null): float
    {
        $rule = self::SPECIAL_PERFORMANCE_BONUS_RATES[$department] ?? null;

        if ($rule !== null && $this->isMonthOnOrBefore($calculationMonth, $rule['through_month'])) {
            return (float) $rule['rate'];
        }

        return self::PERFORMANCE_BONUS_RATE;
    }

    private function isMonthOnOrBefore(?string $month, string $throughMonth): bool
    {
        return is_string($month)
            && preg_match('/^\d{4}-\d{2}$/', $month) === 1
            && strcmp($month, $throughMonth) <= 0;
    }

    private function emptyDepartment(string $department): array
    {
        return [
            'department' => $department,
            'source_departments' => [],
            'csv_sales' => 0,
            'csv_expenses' => 0,
            'csv_reserve_transfer_sales' => 0,
            'csv_indirect_allocation_sales' => 0,
            'csv_indirect_allocation_expense' => 0,
            'csv_performance_bonus_reserve' => 0,
            'external_sales' => 0,
            'internal_sales' => 0,
            'sales' => 0,
            'expenses' => 0,
            'operating_sales' => 0,
            'reserve_transfer_sales' => 0,
            'indirect_allocation_sales' => 0,
            'other_sales' => 0,
            'ordinary_expenses' => 0,
            'reserve_expenses' => 0,
            'basic_bonus_reserve' => 0,
            'performance_bonus_reserve' => 0,
            'paid_leave_reserve' => 0,
            'welfare_reserve' => 0,
            'refresh_reserve' => 0,
            'indirect_allocation_expense' => 0,
            'beginning_inventory' => 0,
            'purchases' => 0,
            'ending_inventory' => 0,
            'cost_of_goods_sold' => 0,
            'base_reserve_expenses' => 0,
            'sg_and_a_expenses' => 0,
            'normal_profit' => 0,
            'csv_profit' => 0,
            'profit_adjustment' => 0,
            'adjusted_expenses' => 0,
            'indirect_allocation' => 0,
            'total_expenses' => 0,
            'profit' => 0,
            'real_profit' => 0,
            'margin' => null,
            'real_margin' => null,
            'ending_sales' => 0,
            'ending_expenses' => 0,
            'ending_profit' => 0,
            'row_count' => 0,
            'accounts' => [],
        ];
    }

    private function finishDepartments(
        array $departments,
        array $calculationSources = [],
        array $reserveAllocationStats = [],
        ?string $calculationMonth = null
    ): array
    {
        $calculationSources = $this->normalizeCalculationSources($calculationSources);
        $this->transferManagementReserveDepartmentExpenses($departments);

        foreach ($departments as $key => $department) {
            $departments[$key] = $this->finishDepartmentBase($department, $calculationSources, $calculationMonth);

            if ($this->shouldGenerateDepartmentCharges($departments[$key]['department'])) {
                $this->replaceGeneratedDepartmentChargeAccounts($departments[$key], $calculationSources);
            }
        }

        $indirectAllocationSales = 0;
        $reserveTransferSales = 0;
        $performanceBonusAccrualTotal = 0;
        $reserveTransferSalesByBucket = array_fill_keys(array_keys(self::RESERVE_TRANSFER_SALES_ACCOUNTS), 0);

        foreach ($departments as $department) {
            if (! $this->shouldGenerateDepartmentCharges($department['department'])) {
                continue;
            }

            $indirectAllocationSales += $department['indirect_allocation_expense'];
            $reserveTransferSales += $department['reserve_expenses'];
            $performanceBonusAccrualTotal += $this->performanceBonusAccrualAmount($department, $calculationMonth);

            foreach (array_keys($reserveTransferSalesByBucket) as $bucket) {
                $reserveTransferSalesByBucket[$bucket] += $department[$bucket] ?? 0;
            }
        }

        if (! isset($departments[self::INDIRECT_DEPARTMENT])) {
            $departments[self::INDIRECT_DEPARTMENT] = $this->finishDepartmentBase($this->emptyDepartment(self::INDIRECT_DEPARTMENT), $calculationSources, $calculationMonth);
            $this->rememberSourceDepartment($departments[self::INDIRECT_DEPARTMENT], self::INDIRECT_DEPARTMENT);
        }

        if (! isset($departments[self::RESERVE_DEPARTMENT])) {
            $departments[self::RESERVE_DEPARTMENT] = $this->finishDepartmentBase($this->emptyDepartment(self::RESERVE_DEPARTMENT), $calculationSources, $calculationMonth);
            $this->rememberSourceDepartment($departments[self::RESERVE_DEPARTMENT], self::RESERVE_DEPARTMENT);
        }

        if ($calculationSources['indirect_allocation_sales'] === self::SOURCE_AUTO_CALCULATED) {
            $departments[self::INDIRECT_DEPARTMENT]['indirect_allocation_sales'] = $indirectAllocationSales;
            $this->recalculateDepartment($departments[self::INDIRECT_DEPARTMENT], $calculationSources, $calculationMonth);
        }

        if ($calculationSources['reserve_transfer_sales'] === self::SOURCE_AUTO_CALCULATED) {
            $departments[self::RESERVE_DEPARTMENT]['reserve_transfer_sales'] = $reserveTransferSales;
            $this->replaceReserveTransferSalesAccounts($departments[self::RESERVE_DEPARTMENT], $reserveTransferSalesByBucket);
        }

        if ($calculationSources['bonus_accrual_expense'] === self::SOURCE_AUTO_CALCULATED) {
            $this->replaceReserveBonusAccrualExpenseAccount(
                $departments[self::RESERVE_DEPARTMENT],
                $this->bonusAccrualExpenseAmount($performanceBonusAccrualTotal, $reserveAllocationStats)
            );
        }

        $this->recalculateDepartment($departments[self::RESERVE_DEPARTMENT], $calculationSources, $calculationMonth);

        $departmentRows = array_values($departments);

        foreach ($departmentRows as &$department) {
            $this->sortDepartmentAccounts($department);
        }
        unset($department);

        return $departmentRows;
    }

    /**
     * @param array<string, int> $amountsByBucket
     */
    private function replaceReserveTransferSalesAccounts(array &$reserveDepartment, array $amountsByBucket): void
    {
        foreach (self::RESERVE_TRANSFER_SALES_ACCOUNTS as $bucket => $accountName) {
            $amount = (int) ($amountsByBucket[$bucket] ?? 0);
            $accountKey = "|{$accountName}|sales|reserve_transfer_sales";
            $existing = $reserveDepartment['accounts'][$accountKey] ?? [];

            if ($amount === 0 && $existing === []) {
                continue;
            }

            $reserveDepartment['accounts'][$accountKey] = [
                'account_code' => '',
                'account_name' => $accountName,
                'category' => 'sales',
                'bucket' => 'reserve_transfer_sales',
                'bucket_label' => self::BUCKET_LABELS['reserve_transfer_sales'],
                'amount_source' => 'generated_internal_sales',
                'amount' => $amount,
                'debit' => 0,
                'credit' => $amount,
                'balance' => $amount,
                'ending_balance' => 0,
                'rows' => max((int) ($existing['rows'] ?? 0), 1),
                'source_departments' => ['計算結果'],
                'source_amounts' => [
                    '計算結果' => [
                        'amount' => $amount,
                        'debit' => 0,
                        'credit' => $amount,
                        'balance' => $amount,
                        'ending_balance' => 0,
                        'rows' => 1,
                    ],
                ],
            ];
        }
    }

    /**
     * @param array<string, int> $reserveAllocationStats
     */
    private function bonusAccrualExpenseAmount(int $performanceBonusAccrualTotal, array $reserveAllocationStats): int
    {
        return (int) ($reserveAllocationStats['basic_bonus_accrual_total'] ?? 0)
            + $performanceBonusAccrualTotal;
    }

    /**
     * 賞与引当金繰入額に積む業績連動賞与。
     *
     * 部門ごとの「業績連動賞与積立金」（＝各部門の支出）は赤字部門を0で止めるが、
     * 引当金の繰入額はマイナスも含めて合算する。会社全体の引当額は
     * 赤字部門のぶんだけ減る、という運用に合わせている。
     * ここに max(…, 0) を入れてはいけない。
     */
    private function performanceBonusAccrualAmount(array $department, ?string $calculationMonth = null): int
    {
        return $this->shouldGeneratePerformanceBonus($department['department'])
            ? $this->roundRateAmount(
                (int) ($department['normal_profit'] ?? 0),
                $this->performanceBonusRate($department['department'], $calculationMonth)
            )
            : 0;
    }

    private function replaceReserveBonusAccrualExpenseAccount(array &$reserveDepartment, int $amount): void
    {
        $accountKey = '|' . self::BONUS_ACCRUAL_EXPENSE_ACCOUNT . '|expense|ordinary_expense';
        $existingRows = 0;
        $existingAmount = 0;

        foreach ($reserveDepartment['accounts'] as $key => $account) {
            if (
                ($account['category'] ?? null) === 'expense'
                && ($account['bucket'] ?? null) === 'ordinary_expense'
                && ($account['account_name'] ?? null) === self::BONUS_ACCRUAL_EXPENSE_ACCOUNT
                && ($account['amount_source'] ?? null) === 'generated_bonus_accrual'
            ) {
                $existingRows = max($existingRows, (int) ($account['rows'] ?? 0));
                $existingAmount += (int) ($account['amount'] ?? 0);
                unset($reserveDepartment['accounts'][$key]);
            }
        }

        $reserveDepartment['ordinary_expenses'] -= $existingAmount;
        $reserveDepartment['sg_and_a_expenses'] -= $existingAmount;

        if ($amount === 0) {
            return;
        }

        $reserveDepartment['ordinary_expenses'] += $amount;
        $reserveDepartment['sg_and_a_expenses'] += $amount;
        $reserveDepartment['accounts'][$accountKey] = [
            'account_code' => '',
            'account_name' => self::BONUS_ACCRUAL_EXPENSE_ACCOUNT,
            'category' => 'expense',
            'bucket' => 'ordinary_expense',
            'bucket_label' => self::BUCKET_LABELS['ordinary_expense'],
            'amount_source' => 'generated_bonus_accrual',
            'amount' => $amount,
            'debit' => $amount,
            'credit' => 0,
            'balance' => $amount,
            'ending_balance' => 0,
            'rows' => max($existingRows, 1),
            'source_departments' => ['計算結果'],
            'source_amounts' => [
                '計算結果' => [
                    'amount' => $amount,
                    'debit' => $amount,
                    'credit' => 0,
                    'balance' => $amount,
                    'ending_balance' => 0,
                    'rows' => 1,
                ],
            ],
        ];
    }

    private function replaceGeneratedDepartmentChargeAccounts(array &$department, array $calculationSources): void
    {
        foreach (self::GENERATED_DEPARTMENT_CHARGE_ACCOUNTS as $bucket => $accountName) {
            if (! $this->shouldGenerateChargeAccount($bucket, $calculationSources)) {
                continue;
            }

            $amount = (int) ($department[$bucket] ?? 0);
            $accountKey = "|{$accountName}|expense|{$bucket}";
            $existing = $department['accounts'][$accountKey] ?? [];

            foreach ($department['accounts'] as $key => $account) {
                if (
                    ($account['bucket'] ?? null) === $bucket
                    && ($account['category'] ?? null) === 'expense'
                    && ($account['account_name'] ?? null) === $accountName
                    && ($account['amount_source'] ?? null) === 'generated_charge'
                ) {
                    $existing = $existing ?: $account;
                    unset($department['accounts'][$key]);
                }
            }

            if ($amount === 0) {
                unset($department['accounts'][$accountKey]);
                continue;
            }

            $preserveAllocationSources = in_array($bucket, self::BASE_RESERVE_BUCKETS, true)
                && ($existing['amount_source'] ?? null) === 'timecard_kintone'
                && (int) ($existing['amount'] ?? 0) === $amount
                && ! empty($existing['source_amounts']);

            $department['accounts'][$accountKey] = [
                'account_code' => '',
                'account_name' => $accountName,
                'category' => 'expense',
                'bucket' => $bucket,
                'bucket_label' => self::BUCKET_LABELS[$bucket],
                'amount_source' => $preserveAllocationSources ? 'timecard_kintone' : 'generated_charge',
                'amount' => $amount,
                'debit' => $amount,
                'credit' => 0,
                'balance' => $amount,
                'ending_balance' => 0,
                'rows' => max((int) ($existing['rows'] ?? 0), 1),
                'source_departments' => $preserveAllocationSources
                    ? $existing['source_departments']
                    : ['計算結果'],
                'source_amounts' => $preserveAllocationSources
                    ? $existing['source_amounts']
                    : [
                        '計算結果' => [
                            'amount' => $amount,
                            'debit' => $amount,
                            'credit' => 0,
                            'balance' => $amount,
                            'ending_balance' => 0,
                            'rows' => 1,
                        ],
                    ],
            ];
        }
    }

    private function shouldGenerateChargeAccount(string $bucket, array $calculationSources): bool
    {
        $sourceKey = match ($bucket) {
            'basic_bonus_reserve',
            'paid_leave_reserve',
            'welfare_reserve',
            'refresh_reserve' => 'base_reserve_expenses',
            'performance_bonus_reserve' => 'performance_bonus_reserve',
            'indirect_allocation_expense' => 'indirect_allocation_expense',
            default => null,
        };

        return $sourceKey === null
            || ($calculationSources[$sourceKey] ?? self::SOURCE_AUTO_CALCULATED) === self::SOURCE_AUTO_CALCULATED;
    }

    private function transferManagementReserveDepartmentExpenses(array &$departments): void
    {
        if (! isset($departments[self::MANAGEMENT_DEPARTMENT])) {
            return;
        }

        if (! isset($departments[self::RESERVE_DEPARTMENT])) {
            $departments[self::RESERVE_DEPARTMENT] = $this->emptyDepartment(self::RESERVE_DEPARTMENT);
            $this->rememberSourceDepartment($departments[self::RESERVE_DEPARTMENT], self::RESERVE_DEPARTMENT);
        }

        foreach ($departments[self::MANAGEMENT_DEPARTMENT]['accounts'] as $key => $account) {
            if (! $this->isReserveDepartmentSgAndAAccount($account)) {
                continue;
            }

            $transferAccount = $this->accountForSourceDepartment($account, '経営管理本部共通部門');

            if ($transferAccount === null) {
                continue;
            }

            $amount = $transferAccount['amount'];
            $departments[self::MANAGEMENT_DEPARTMENT]['ordinary_expenses'] -= $amount;
            $departments[self::MANAGEMENT_DEPARTMENT]['expenses'] -= $amount;
            $departments[self::RESERVE_DEPARTMENT]['ordinary_expenses'] += $amount;
            $departments[self::RESERVE_DEPARTMENT]['expenses'] += $amount;

            foreach ($transferAccount['source_departments'] as $sourceDepartment) {
                $this->rememberSourceDepartment($departments[self::RESERVE_DEPARTMENT], $sourceDepartment);
            }

            $this->subtractAccountFromManagementDepartment($departments, (string) $key, $transferAccount);
            $this->addAccountToReserveDepartment($departments, (string) $key, $transferAccount);
        }
    }

    private function isReserveDepartmentSgAndAAccount(array $account): bool
    {
        if (($account['bucket'] ?? null) !== 'ordinary_expense') {
            return false;
        }

        return in_array($account['account_name'] ?? '', self::RESERVE_DEPARTMENT_SG_AND_A_ACCOUNTS, true)
            && in_array('経営管理本部共通部門', $account['source_departments'] ?? [], true);
    }

    private function accountForSourceDepartment(array $account, string $sourceDepartment): ?array
    {
        if (! isset($account['source_amounts'][$sourceDepartment])) {
            return null;
        }

        $source = $account['source_amounts'][$sourceDepartment];

        return [
            'account_code' => $account['account_code'],
            'account_name' => $account['account_name'],
            'category' => $account['category'],
            'bucket' => $account['bucket'],
            'bucket_label' => $account['bucket_label'],
            'amount_source' => $account['amount_source'],
            'amount' => $source['amount'],
            'debit' => $source['debit'],
            'credit' => $source['credit'],
            'balance' => $source['balance'],
            'ending_balance' => $source['ending_balance'],
            'rows' => $source['rows'],
            'source_departments' => [$sourceDepartment],
            'source_amounts' => [
                $sourceDepartment => $source,
            ],
        ];
    }

    private function subtractAccountFromManagementDepartment(array &$departments, string $key, array $account): void
    {
        if (! isset($departments[self::MANAGEMENT_DEPARTMENT]['accounts'][$key])) {
            return;
        }

        $target = &$departments[self::MANAGEMENT_DEPARTMENT]['accounts'][$key];
        $target['amount'] -= $account['amount'];
        $target['debit'] -= $account['debit'];
        $target['credit'] -= $account['credit'];
        $target['balance'] -= $account['balance'];
        $target['ending_balance'] -= $account['ending_balance'];
        $target['rows'] -= $account['rows'];

        foreach ($account['source_departments'] as $sourceDepartment) {
            unset($target['source_amounts'][$sourceDepartment]);
        }

        $target['source_departments'] = array_keys($target['source_amounts']);

        if ($target['rows'] <= 0) {
            unset($departments[self::MANAGEMENT_DEPARTMENT]['accounts'][$key]);
        }
    }

    private function addAccountToReserveDepartment(array &$departments, string $key, array $account): void
    {
        if (! isset($departments[self::RESERVE_DEPARTMENT]['accounts'][$key])) {
            $departments[self::RESERVE_DEPARTMENT]['accounts'][$key] = $account;

            return;
        }

        $departments[self::RESERVE_DEPARTMENT]['accounts'][$key]['amount'] += $account['amount'];
        $departments[self::RESERVE_DEPARTMENT]['accounts'][$key]['debit'] += $account['debit'];
        $departments[self::RESERVE_DEPARTMENT]['accounts'][$key]['credit'] += $account['credit'];
        $departments[self::RESERVE_DEPARTMENT]['accounts'][$key]['balance'] += $account['balance'];
        $departments[self::RESERVE_DEPARTMENT]['accounts'][$key]['ending_balance'] += $account['ending_balance'];
        $departments[self::RESERVE_DEPARTMENT]['accounts'][$key]['rows'] += $account['rows'];

        foreach ($account['source_departments'] as $sourceDepartment) {
            $this->rememberSourceDepartment($departments[self::RESERVE_DEPARTMENT]['accounts'][$key], $sourceDepartment);
        }

        foreach ($account['source_amounts'] as $sourceDepartment => $source) {
            $this->accumulateAccountSource($departments[self::RESERVE_DEPARTMENT]['accounts'][$key], $sourceDepartment, $source);
        }
    }

    private function finishDepartmentBase(array $department, array $calculationSources = [], ?string $calculationMonth = null): array
    {
        $calculationSources = $this->normalizeCalculationSources($calculationSources);
        $department['csv_sales'] = $department['sales'];
        $department['csv_expenses'] = $department['expenses'];
        $department['csv_reserve_transfer_sales'] = $department['reserve_transfer_sales'];
        $department['csv_indirect_allocation_sales'] = $department['indirect_allocation_sales'];
        $department['csv_indirect_allocation_expense'] = $department['indirect_allocation_expense'];
        $department['csv_performance_bonus_reserve'] = $department['performance_bonus_reserve'];

        $department['external_sales'] = $department['operating_sales'];
        $department['reserve_transfer_sales'] = $calculationSources['reserve_transfer_sales'] !== self::SOURCE_AUTO_CALCULATED
            && $department['department'] === self::RESERVE_DEPARTMENT
            ? $department['csv_reserve_transfer_sales']
            : 0;
        $department['indirect_allocation_sales'] = $calculationSources['indirect_allocation_sales'] !== self::SOURCE_AUTO_CALCULATED
            && $department['department'] === self::INDIRECT_DEPARTMENT
            ? $department['csv_indirect_allocation_sales']
            : 0;
        $department['indirect_allocation_expense'] = $calculationSources['indirect_allocation_expense'] !== self::SOURCE_AUTO_CALCULATED
            ? $department['csv_indirect_allocation_expense']
            : 0;
        $department['performance_bonus_reserve'] = $calculationSources['performance_bonus_reserve'] !== self::SOURCE_AUTO_CALCULATED
            ? $department['csv_performance_bonus_reserve']
            : 0;
        $department['cost_of_goods_sold'] = $this->isCogsProject($department['department'])
            ? $department['beginning_inventory'] + $department['purchases'] - $department['ending_inventory']
            : 0;

        $department['base_reserve_expenses'] = $department['basic_bonus_reserve']
            + $department['paid_leave_reserve']
            + $department['welfare_reserve']
            + $department['refresh_reserve'];
        $department['sg_and_a_expenses'] = $department['ordinary_expenses'] + $department['base_reserve_expenses'];

        if (
            $calculationSources['indirect_allocation_expense'] === self::SOURCE_AUTO_CALCULATED
            && $this->shouldGenerateIndirectAllocation($department['department'])
        ) {
            $department['indirect_allocation_expense'] = $this->roundRateAmount($department['sg_and_a_expenses'], self::INDIRECT_RATE);
        }

        $this->recalculateDepartment($department, $calculationSources, $calculationMonth);

        return $department;
    }

    private function recalculateDepartment(array &$department, array $calculationSources = [], ?string $calculationMonth = null): void
    {
        $calculationSources = $this->normalizeCalculationSources($calculationSources);
        $department['internal_sales'] = $department['reserve_transfer_sales'] + $department['indirect_allocation_sales'];
        $department['sales'] = $department['external_sales'] + $department['internal_sales'];
        $department['normal_profit'] = $department['sales']
            - $department['cost_of_goods_sold']
            - $department['sg_and_a_expenses']
            - $department['indirect_allocation_expense'];

        if ($calculationSources['performance_bonus_reserve'] === self::SOURCE_AUTO_CALCULATED) {
            $department['performance_bonus_reserve'] = $this->shouldGeneratePerformanceBonus($department['department'])
                ? max($this->roundRateAmount(
                    $department['normal_profit'],
                    $this->performanceBonusRate($department['department'], $calculationMonth)
                ), 0)
                : 0;
        }

        $department['reserve_expenses'] = $department['base_reserve_expenses'] + $department['performance_bonus_reserve'];
        $department['expenses'] = $department['cost_of_goods_sold']
            + $department['sg_and_a_expenses']
            + $department['indirect_allocation_expense']
            + $department['performance_bonus_reserve'];
        $department['real_profit'] = $department['normal_profit'] - $department['performance_bonus_reserve'];
        $department['csv_profit'] = $department['csv_sales'] - $department['csv_expenses'];
        $department['profit_adjustment'] = $department['performance_bonus_reserve'];
        $department['adjusted_expenses'] = $department['expenses'];
        $department['indirect_allocation'] = $department['indirect_allocation_expense'];
        $department['total_expenses'] = $department['expenses'];
        $department['profit'] = $department['real_profit'];
        $department['margin'] = $this->margin($department['sales'], $department['normal_profit']);
        $department['real_margin'] = $this->margin($department['sales'], $department['real_profit']);
        $department['ending_profit'] = $department['ending_sales'] - $department['ending_expenses'];
    }

    private function sortDepartmentAccounts(array &$department): void
    {
        foreach ($department['accounts'] as $key => &$account) {
            $account['account_key'] = is_string($key) ? $key : $this->accountKey($account);
        }
        unset($account);

        $department['accounts'] = array_values($department['accounts']);
        usort(
            $department['accounts'],
            fn (array $a, array $b) => strcmp($a['category'], $b['category']) ?: (abs($b['amount']) <=> abs($a['amount']))
        );
    }

    private function summary(array $departments): array
    {
        $summary = [
            'departments' => count($departments),
            'csv_sales' => 0,
            'csv_expenses' => 0,
            'csv_reserve_transfer_sales' => 0,
            'csv_indirect_allocation_sales' => 0,
            'csv_indirect_allocation_expense' => 0,
            'csv_performance_bonus_reserve' => 0,
            'external_sales' => 0,
            'internal_sales' => 0,
            'sales' => 0,
            'expenses' => 0,
            'operating_sales' => 0,
            'reserve_transfer_sales' => 0,
            'indirect_allocation_sales' => 0,
            'other_sales' => 0,
            'ordinary_expenses' => 0,
            'reserve_expenses' => 0,
            'basic_bonus_reserve' => 0,
            'performance_bonus_reserve' => 0,
            'paid_leave_reserve' => 0,
            'welfare_reserve' => 0,
            'refresh_reserve' => 0,
            'indirect_allocation_expense' => 0,
            'beginning_inventory' => 0,
            'purchases' => 0,
            'ending_inventory' => 0,
            'cost_of_goods_sold' => 0,
            'base_reserve_expenses' => 0,
            'sg_and_a_expenses' => 0,
            'normal_profit' => 0,
            'csv_profit' => 0,
            'profit_adjustment' => 0,
            'adjusted_expenses' => 0,
            'indirect_allocation' => 0,
            'total_expenses' => 0,
            'profit' => 0,
            'real_profit' => 0,
            'margin' => null,
            'real_margin' => null,
            'ending_sales' => 0,
            'ending_expenses' => 0,
            'ending_profit' => 0,
        ];

        foreach ($departments as $department) {
            $summary['csv_sales'] += $department['csv_sales'];
            $summary['csv_expenses'] += $department['csv_expenses'];
            $summary['csv_reserve_transfer_sales'] += $department['csv_reserve_transfer_sales'];
            $summary['csv_indirect_allocation_sales'] += $department['csv_indirect_allocation_sales'];
            $summary['csv_indirect_allocation_expense'] += $department['csv_indirect_allocation_expense'];
            $summary['csv_performance_bonus_reserve'] += $department['csv_performance_bonus_reserve'];
            $summary['external_sales'] += $department['external_sales'];
            $summary['internal_sales'] += $department['internal_sales'];
            $summary['sales'] += $department['sales'];
            $summary['expenses'] += $department['expenses'];
            $summary['operating_sales'] += $department['operating_sales'];
            $summary['reserve_transfer_sales'] += $department['reserve_transfer_sales'];
            $summary['indirect_allocation_sales'] += $department['indirect_allocation_sales'];
            $summary['other_sales'] += $department['other_sales'];
            $summary['ordinary_expenses'] += $department['ordinary_expenses'];
            $summary['reserve_expenses'] += $department['reserve_expenses'];
            $summary['basic_bonus_reserve'] += $department['basic_bonus_reserve'];
            $summary['performance_bonus_reserve'] += $department['performance_bonus_reserve'];
            $summary['paid_leave_reserve'] += $department['paid_leave_reserve'];
            $summary['welfare_reserve'] += $department['welfare_reserve'];
            $summary['refresh_reserve'] += $department['refresh_reserve'];
            $summary['indirect_allocation_expense'] += $department['indirect_allocation_expense'];
            $summary['beginning_inventory'] += $department['beginning_inventory'];
            $summary['purchases'] += $department['purchases'];
            $summary['ending_inventory'] += $department['ending_inventory'];
            $summary['cost_of_goods_sold'] += $department['cost_of_goods_sold'];
            $summary['base_reserve_expenses'] += $department['base_reserve_expenses'];
            $summary['sg_and_a_expenses'] += $department['sg_and_a_expenses'];
            $summary['normal_profit'] += $department['normal_profit'];
            $summary['csv_profit'] += $department['csv_profit'];
            $summary['profit_adjustment'] += $department['profit_adjustment'];
            $summary['adjusted_expenses'] += $department['adjusted_expenses'];
            $summary['indirect_allocation'] += $department['indirect_allocation'];
            $summary['total_expenses'] += $department['total_expenses'];
            $summary['real_profit'] += $department['real_profit'];
            $summary['ending_sales'] += $department['ending_sales'];
            $summary['ending_expenses'] += $department['ending_expenses'];
        }

        $summary['profit'] = $summary['real_profit'];
        $summary['margin'] = $this->margin($summary['sales'], $summary['normal_profit']);
        $summary['real_margin'] = $this->margin($summary['sales'], $summary['real_profit']);
        $summary['ending_profit'] = $summary['ending_sales'] - $summary['ending_expenses'];

        return $summary;
    }

    private function generatedBonusAccrualExpenseFromRows(array $departments): int
    {
        foreach ($departments as $department) {
            if (($department['department'] ?? null) !== self::RESERVE_DEPARTMENT) {
                continue;
            }

            foreach ($department['accounts'] ?? [] as $account) {
                if (
                    ($account['account_name'] ?? null) === self::BONUS_ACCRUAL_EXPENSE_ACCOUNT
                    && ($account['amount_source'] ?? null) === 'generated_bonus_accrual'
                ) {
                    return (int) ($account['amount'] ?? 0);
                }
            }
        }

        return 0;
    }

    private function accumulateDepartmentBucket(array &$department, string $bucket, int $amount): void
    {
        if ($bucket === 'ordinary_expense') {
            $department['ordinary_expenses'] += $amount;

            return;
        }

        if (in_array($bucket, ['beginning_inventory', 'purchases', 'ending_inventory'], true)) {
            $department[$bucket] += $amount;

            return;
        }

        if (isset($department[$bucket])) {
            $department[$bucket] += $amount;
        }

        if (in_array($bucket, array_values(self::RESERVE_BUCKETS), true)) {
            $department['reserve_expenses'] += $amount;
        }
    }

    private function accumulateAccount(array &$accounts, string $key, array $row): void
    {
        if (! isset($accounts[$key])) {
            $accounts[$key] = [
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'category' => $row['category'],
                'bucket' => $row['bucket'],
                'bucket_label' => $row['bucket_label'],
                'amount_source' => $row['amount_source'],
                'amount' => 0,
                'debit' => 0,
                'credit' => 0,
                'balance' => 0,
                'ending_balance' => 0,
                'rows' => 0,
                'source_departments' => [],
                'source_amounts' => [],
            ];
        }

        $accounts[$key]['amount'] += $row['amount'];
        $accounts[$key]['debit'] += $row['debit'];
        $accounts[$key]['credit'] += $row['credit'];
        $accounts[$key]['balance'] += $row['balance'];
        $accounts[$key]['ending_balance'] += $row['ending_balance'];
        $rows = max((int) ($row['rows'] ?? 1), 1);
        $accounts[$key]['rows'] += $rows;
        $this->rememberSourceDepartment($accounts[$key], $row['source_department']);
        $this->accumulateAccountSource($accounts[$key], $row['source_department'], [
            'amount' => $row['amount'],
            'debit' => $row['debit'],
            'credit' => $row['credit'],
            'balance' => $row['balance'],
            'ending_balance' => $row['ending_balance'],
            'rows' => $rows,
        ]);
    }

    private function accumulateAccountSource(array &$account, string $sourceDepartment, array $source): void
    {
        if ($sourceDepartment === '') {
            return;
        }

        if (! isset($account['source_amounts'][$sourceDepartment])) {
            $account['source_amounts'][$sourceDepartment] = [
                'amount' => 0,
                'debit' => 0,
                'credit' => 0,
                'balance' => 0,
                'ending_balance' => 0,
                'rows' => 0,
            ];
        }

        $account['source_amounts'][$sourceDepartment]['amount'] += $source['amount'];
        $account['source_amounts'][$sourceDepartment]['debit'] += $source['debit'];
        $account['source_amounts'][$sourceDepartment]['credit'] += $source['credit'];
        $account['source_amounts'][$sourceDepartment]['balance'] += $source['balance'];
        $account['source_amounts'][$sourceDepartment]['ending_balance'] += $source['ending_balance'];
        $account['source_amounts'][$sourceDepartment]['rows'] += $source['rows'];
    }

    private function rememberSourceDepartment(array &$target, string $sourceDepartment): void
    {
        $sourceDepartments = $target['source_departments'] ?? [];

        if ($sourceDepartment === '' || in_array($sourceDepartment, $sourceDepartments, true)) {
            return;
        }

        $target['source_departments'][] = $sourceDepartment;
    }

    private function margin(int $sales, int $profit): ?float
    {
        if ($sales === 0) {
            return null;
        }

        return round($profit / $sales * 100, 2, PHP_ROUND_HALF_UP);
    }
}

