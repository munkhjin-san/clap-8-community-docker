<?php

namespace App\Services;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

class ActualResultCsvService
{
    private const ENCODINGS = ['UTF-8', 'SJIS-win', 'CP932', 'EUC-JP', 'ISO-2022-JP'];

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

    public function calculateFromUploadedFile(UploadedFile $file): array
    {
        return $this->calculateFromPath($file->getRealPath(), $file->getClientOriginalName());
    }

    public function calculateFromPath(string $path, ?string $originalName = null): array
    {
        if (! is_readable($path)) {
            throw $this->validationException([
                'file' => 'CSVファイルを読み込めませんでした。',
            ]);
        }

        $rawContents = file_get_contents($path);

        if ($rawContents === false || $rawContents === '') {
            throw $this->validationException([
                'file' => 'CSVファイルが空です。',
            ]);
        }

        [$contents, $encoding] = $this->normalizeEncoding($rawContents);
        $rows = $this->parseRows($contents);

        if (count($rows) < 2) {
            throw $this->validationException([
                'file' => '試算表CSVの行数が不足しています。',
            ]);
        }

        [$headerIndex, $header] = $this->findHeader($rows);
        $indexes = $this->columnIndexes($header);
        $title = $this->firstFilledCell($rows[0] ?? []);
        $this->validateCsvIdentity($title);
        $period = $this->periodLabel($title);
        $calculationMonth = $this->singleCalculationMonth($title);
        $reserveAllocationResult = null;
        $departments = [];
        $accountTotals = [];
        $detailRowCount = 0;
        $skippedRows = 0;

        for ($rowIndex = $headerIndex + 1; $rowIndex < count($rows); $rowIndex++) {
            $row = $rows[$rowIndex];

            if ($this->isOperatingProfitTotalRow($row)) {
                $skippedRows += count($rows) - $rowIndex;
                break;
            }

            $sourceDepartment = $this->cell($row, $indexes['department']);
            $department = $this->normalizeDepartment($sourceDepartment);
            $accountName = $this->cell($row, $indexes['account_name']);

            if ($sourceDepartment === '' || $accountName === '') {
                $skippedRows++;
                continue;
            }

            $detailRowCount++;
            $accountCode = $this->cell($row, $indexes['account_code']);
            $debitText = $this->cell($row, $indexes['debit']);
            $creditText = $this->cell($row, $indexes['credit']);
            $balanceText = $this->cell($row, $indexes['balance']);
            $endingText = $this->cell($row, $indexes['ending']);
            $debit = $this->money($debitText);
            $credit = $this->money($creditText);
            $balance = $this->money($balanceText);
            $endingBalance = $this->money($endingText);
            $accountMeta = $this->accountMeta($accountName, $accountCode, $department);
            $category = $accountMeta['category'];
            $hasBalance = $this->hasNumericValue($balanceText);
            $amount = $hasBalance ? $balance : 0;
            $amountSource = $hasBalance ? 'balance' : 'blank';

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
            $this->accumulateAccount($departments[$department]['accounts'], $accountKey, [
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'category' => $category,
                'bucket' => $accountMeta['bucket'],
                'bucket_label' => $accountMeta['bucket_label'],
                'amount_source' => $amountSource,
                'amount' => $amount,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
                'ending_balance' => $endingBalance,
                'source_department' => $sourceDepartment,
            ]);
            $this->accumulateAccount($accountTotals, $accountKey, [
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'category' => $category,
                'bucket' => $accountMeta['bucket'],
                'bucket_label' => $accountMeta['bucket_label'],
                'amount_source' => $amountSource,
                'amount' => $amount,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
                'ending_balance' => $endingBalance,
                'source_department' => $sourceDepartment,
            ]);
        }

        if ($detailRowCount === 0) {
            throw $this->validationException([
                'file' => '部門別の明細行が見つかりませんでした。',
            ]);
        }

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
            'file' => [
                'name' => $originalName,
                'title' => $title,
                'period' => $period,
                'calculation_month' => $calculationMonth,
                'encoding' => $encoding,
                'source_rows' => max(count($rows) - ($headerIndex + 1), 0),
                'detail_rows' => $detailRowCount,
                'skipped_rows' => $skippedRows,
                'generated_reserve_rows' => $reserveAllocationResult['stats']['generated_rows'] ?? 0,
                'generated_reserve_total' => $reserveAllocationResult['stats']['generated_total'] ?? 0,
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

    public function recalculateResultPayload(array $result): array
    {
        $departments = [];
        $accountTotals = [];
        $csvSnapshots = [];
        $storedDepartmentsByName = [];

        foreach ($result['departments'] ?? [] as $storedDepartment) {
            $departmentName = (string) ($storedDepartment['department'] ?? '');

            if ($departmentName !== '') {
                $storedDepartmentsByName[$departmentName] = $storedDepartment;
            }
        }

        $calculationSources = $this->normalizeCalculationSources(
            $result['file']['calculation_sources']
                ?? $this->calculationSourcesFromDepartments($storedDepartmentsByName)
        );

        foreach ($result['departments'] ?? [] as $storedDepartment) {
            $departmentName = (string) ($storedDepartment['department'] ?? '');

            if ($departmentName === '') {
                continue;
            }

            $department = $this->emptyDepartment($departmentName);
            $department['source_departments'] = $storedDepartment['source_departments'] ?? [$departmentName];
            $department['row_count'] = (int) ($storedDepartment['row_count'] ?? count($storedDepartment['accounts'] ?? []));
            $csvSnapshots[$departmentName] = $this->csvSnapshot($storedDepartment);

            foreach ($storedDepartment['accounts'] ?? [] as $account) {
                if ($this->shouldSkipStoredGeneratedAccount($account, $calculationSources)) {
                    continue;
                }

                foreach ($this->storedAccountRows($account, $departmentName) as $row) {
                    if ($row['account_name'] === '') {
                        continue;
                    }

                    if ($row['category'] === 'sales') {
                        $department['sales'] += $row['amount'];
                        $department['ending_sales'] += $row['ending_balance'];
                    } else {
                        $department['expenses'] += $row['amount'];
                        $department['ending_expenses'] += $row['ending_balance'];
                    }

                    $this->accumulateDepartmentBucket($department, $row['bucket'], $row['amount']);
                    $this->accumulateAccount($department['accounts'], $this->accountKey($row), $row);
                    $this->accumulateAccount($accountTotals, $this->accountKey($row), $row);
                }
            }

            $departments[$departmentName] = $department;
        }

        $reserveAllocationStats = [
            'basic_bonus_accrual_total' => (int) ($result['file']['generated_basic_bonus_accrual_total'] ?? 0),
        ];
        $departmentRows = $this->finishDepartments(
            $departments,
            $calculationSources,
            $reserveAllocationStats,
            $result['file']['calculation_month'] ?? null
        );

        foreach ($departmentRows as &$departmentRow) {
            $departmentName = (string) ($departmentRow['department'] ?? '');

            foreach ($csvSnapshots[$departmentName] ?? [] as $key => $value) {
                $departmentRow[$key] = $value;
            }
        }
        unset($departmentRow);

        usort($departmentRows, fn (array $a, array $b) => ($b['sales'] <=> $a['sales']) ?: strcmp($a['department'], $b['department']));

        $accountTotalRows = array_values($accountTotals);
        usort($accountTotalRows, fn (array $a, array $b) => strcmp($a['category'], $b['category']) ?: (abs($b['amount']) <=> abs($a['amount'])));

        $result['departments'] = $departmentRows;
        $result['account_totals'] = $accountTotalRows;
        $result['summary'] = $this->summary($departmentRows);
        $result['file']['generated_basic_bonus_accrual_total'] = $reserveAllocationStats['basic_bonus_accrual_total'];
        $result['file']['generated_bonus_accrual_expense'] = $calculationSources['bonus_accrual_expense'] === self::SOURCE_AUTO_CALCULATED
            ? $this->generatedBonusAccrualExpenseFromRows($departmentRows)
            : 0;
        $result['file']['calculation_source_mode'] = $this->primaryCalculationSourceMode($calculationSources);
        $result['file']['calculation_sources'] = $calculationSources;

        return $result;
    }

    private function normalizeEncoding(string $contents): array
    {
        $encoding = mb_detect_encoding($contents, self::ENCODINGS, true) ?: 'SJIS-win';

        if ($encoding !== 'UTF-8') {
            $contents = mb_convert_encoding($contents, 'UTF-8', $encoding);
        }

        return [$this->stripBom($contents), $encoding];
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

    private function parseRows(string $contents): array
    {
        $stream = fopen('php://temp/maxmemory:10485760', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $rows = [];

        while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
            $cleaned = array_map(fn ($cell) => $this->cleanCell((string) $cell), $row);

            if (count($cleaned) === 1 && $cleaned[0] === '') {
                continue;
            }

            $rows[] = $cleaned;
        }

        fclose($stream);

        return $rows;
    }

    private function isOperatingProfitTotalRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (in_array((string) $cell, self::OPERATING_PROFIT_TOTAL_LABELS, true)) {
                return true;
            }
        }

        return false;
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

    private function findHeader(array $rows): array
    {
        foreach ($rows as $index => $row) {
            if (
                $this->indexOf($row, '部門') !== null
                && $this->indexOf($row, '貸借合計') !== null
            ) {
                return [$index, $row];
            }
        }

        throw $this->validationException([
            'file' => '試算表CSVのヘッダー行を判定できませんでした。',
        ]);
    }

    private function columnIndexes(array $header): array
    {
        $accountCode = $this->indexOf($header, '勘定科目コード');
        $department = $this->indexOf($header, '部門');
        $debit = $this->indexOf($header, '借方金額');
        $credit = $this->indexOf($header, '貸方金額');
        $balance = $this->indexOf($header, '貸借合計');
        $ending = $this->lastIndexMatching($header, '/期末/u');

        if ($accountCode === null || $department === null || $balance === null || $ending === null) {
            throw $this->validationException([
                'file' => '試算表CSVに必要な列がありません。',
            ]);
        }

        $accountName = $this->accountNameIndex($header, $accountCode, $department);

        if ($accountName === null) {
            throw $this->validationException([
                'file' => '試算表CSVの勘定科目列を判定できませんでした。',
            ]);
        }

        return [
            'account_code' => $accountCode,
            'account_name' => $accountName,
            'department' => $department,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $balance,
            'ending' => $ending,
        ];
    }

    private function accountNameIndex(array $header, int $accountCode, int $department): ?int
    {
        $namedIndex = $this->indexOf($header, '勘定科目');

        if ($namedIndex !== null) {
            return $namedIndex;
        }

        for ($index = $accountCode + 1; $index < $department; $index++) {
            if (($header[$index] ?? '') === '') {
                return $index;
            }
        }

        return null;
    }

    private function accountMeta(string $accountName, string $accountCode, string $department): array
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

    private function storedAccountRow(array $account, string $department): array
    {
        $accountCode = (string) ($account['account_code'] ?? '');
        $accountName = (string) ($account['account_name'] ?? '');
        $category = (string) ($account['category'] ?? '');
        $bucket = (string) ($account['bucket'] ?? '');

        if (! in_array($category, ['sales', 'expense'], true) || ! isset(self::BUCKET_LABELS[$bucket])) {
            $meta = $this->accountMeta($accountName, $accountCode, $department);
            $category = $meta['category'];
            $bucket = $meta['bucket'];
        }

        $amount = (int) round((float) ($account['amount'] ?? $account['balance'] ?? 0));

        return [
            'account_code' => $accountCode,
            'account_name' => $accountName,
            'category' => $category,
            'bucket' => $bucket,
            'bucket_label' => self::BUCKET_LABELS[$bucket] ?? (string) ($account['bucket_label'] ?? $bucket),
            'amount_source' => (string) ($account['amount_source'] ?? 'stored'),
            'amount' => $amount,
            'debit' => (int) round((float) ($account['debit'] ?? ($category === 'expense' ? $amount : 0))),
            'credit' => (int) round((float) ($account['credit'] ?? ($category === 'sales' ? $amount : 0))),
            'balance' => (int) round((float) ($account['balance'] ?? $amount)),
            'ending_balance' => (int) round((float) ($account['ending_balance'] ?? 0)),
            'rows' => max((int) ($account['rows'] ?? 1), 1),
            'source_department' => (string) ($account['source_department'] ?? $department),
        ];
    }

    /**
     * Rehydrate aggregate accounts by source so edit recalculation preserves the
     * source-department split used by management-headquarters transfers.
     *
     * @return array<int, array<string, mixed>>
     */
    private function storedAccountRows(array $account, string $department): array
    {
        $sourceAmounts = $account['source_amounts'] ?? [];

        if (! is_array($sourceAmounts) || $sourceAmounts === []) {
            return [$this->storedAccountRow($account, $department)];
        }

        $rows = [];

        foreach ($sourceAmounts as $sourceDepartment => $source) {
            if (! is_array($source)) {
                continue;
            }

            $rows[] = $this->storedAccountRow(array_merge($account, $source, [
                'source_department' => (string) $sourceDepartment,
            ]), $department);
        }

        return $rows ?: [$this->storedAccountRow($account, $department)];
    }

    private function shouldSkipStoredGeneratedAccount(array $account, array $calculationSources): bool
    {
        $amountSource = (string) ($account['amount_source'] ?? '');

        if (in_array($amountSource, ['generated_internal_sales', 'generated_bonus_accrual'], true)) {
            return true;
        }

        if ($amountSource !== 'generated_charge') {
            return false;
        }

        $bucket = (string) ($account['bucket'] ?? '');

        return in_array($bucket, ['performance_bonus_reserve', 'indirect_allocation_expense'], true)
            && $this->shouldGenerateChargeAccount($bucket, $calculationSources);
    }

    /**
     * @return array<string, int>
     */
    private function csvSnapshot(array $department): array
    {
        $keys = [
            'csv_sales',
            'csv_expenses',
            'csv_reserve_transfer_sales',
            'csv_indirect_allocation_sales',
            'csv_indirect_allocation_expense',
            'csv_performance_bonus_reserve',
            'csv_profit',
        ];
        $snapshot = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $department)) {
                $snapshot[$key] = (int) $department[$key];
            }
        }

        return $snapshot;
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

    private function money(string $value): int
    {
        $normalized = $this->normalizeMoneyText($value);

        if ($normalized === '') {
            return 0;
        }

        $negative = false;

        if (str_starts_with($normalized, '△')) {
            $negative = true;
            $normalized = substr($normalized, strlen('△'));
        }

        if (preg_match('/^\((.*)\)$/', $normalized, $matches)) {
            $negative = true;
            $normalized = $matches[1];
        }

        if (str_starts_with($normalized, '-')) {
            $negative = true;
            $normalized = substr($normalized, 1);
        }

        $normalized = preg_replace('/[^0-9.]/', '', $normalized) ?? '';

        if ($normalized === '') {
            return 0;
        }

        $amount = (int) round((float) $normalized);

        return $negative ? -$amount : $amount;
    }

    private function hasNumericValue(string $value): bool
    {
        return preg_match('/[0-9０-９]/u', $value) === 1;
    }

    private function normalizeMoneyText(string $value): string
    {
        $value = trim(mb_convert_kana($value, 'as', 'UTF-8'));

        return str_replace([',', '￥', '¥', '円', ' '], '', $value);
    }

    private function cleanCell(string $value): string
    {
        return trim(str_replace("\u{3000}", ' ', $value));
    }

    private function cell(array $row, ?int $index): string
    {
        if ($index === null) {
            return '';
        }

        return $this->cleanCell((string) ($row[$index] ?? ''));
    }

    private function indexOf(array $row, string $label): ?int
    {
        $index = array_search($label, $row, true);

        return $index === false ? null : $index;
    }

    private function lastIndexMatching(array $row, string $pattern): ?int
    {
        for ($index = count($row) - 1; $index >= 0; $index--) {
            if (preg_match($pattern, (string) ($row[$index] ?? '')) === 1) {
                return $index;
            }
        }

        return null;
    }

    private function firstFilledCell(array $row): string
    {
        foreach ($row as $cell) {
            $cell = $this->cleanCell((string) $cell);

            if ($cell !== '') {
                return $cell;
            }
        }

        return '';
    }

    private function periodLabel(string $title): ?string
    {
        if (preg_match('/期間[:：]([^、）]+)/u', $title, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function validateCsvIdentity(string $title): void
    {
        if (! str_contains($title, '損益計算書')) {
            throw $this->validationException([
                'file' => '損益計算書の試算表CSVを選択してください。',
            ]);
        }

        if (preg_match('/表示単位[:：]\s*円/u', $title) !== 1) {
            throw $this->validationException([
                'file' => '表示単位が「円」の損益計算書CSVを選択してください。',
            ]);
        }
    }

    private function singleCalculationMonth(string $title): string
    {
        if (preg_match(
            '/期間[:：]\s*(\d{4})年\s*(\d{1,2})月\s*[～〜~]\s*(\d{4})年\s*(\d{1,2})月/u',
            $title,
            $matches
        ) !== 1) {
            throw $this->validationException([
                'file' => 'CSVタイトルから対象期間を判定できませんでした。',
            ]);
        }

        $startYear = (int) $matches[1];
        $startMonth = (int) $matches[2];
        $endYear = (int) $matches[3];
        $endMonth = (int) $matches[4];

        if (
            ! checkdate($startMonth, 1, $startYear)
            || ! checkdate($endMonth, 1, $endYear)
        ) {
            throw $this->validationException([
                'file' => 'CSVの対象期間が正しくありません。',
            ]);
        }

        if ($startYear !== $endYear || $startMonth !== $endMonth) {
            throw $this->validationException([
                'file' => '月次実績には開始月と終了月が同じCSVを選択してください。',
            ]);
        }

        return sprintf('%04d-%02d', $startYear, $startMonth);
    }

    private function margin(int $sales, int $profit): ?float
    {
        if ($sales === 0) {
            return null;
        }

        return round($profit / $sales * 100, 2, PHP_ROUND_HALF_UP);
    }

    private function stripBom(string $contents): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
    }
}
