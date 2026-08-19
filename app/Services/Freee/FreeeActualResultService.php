<?php

namespace App\Services\Freee;

use App\Models\FreeeCredential;
use App\Models\ProjectRecord;
use App\Services\ActualResultCalculationService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * freee会計の損益計算書を取り込み、実績計算に流し込む。
 *
 * ここが担うのはfreeeの応答を明細行に直すところまで。積立金・間接配賦・
 * 業績連動賞与の生成は ActualResultCalculationService が担当する。
 */
class FreeeActualResultService
{
    /**
     * freeeの勘定科目区分から収益/費用を判定するための対応表。
     *
     * freee APIは勘定科目コード（410/621など）を返さないため、コード範囲による
     * 判定が使えない。代わりに区分名で振り分ける。
     */
    private const INCOME_CATEGORIES = [
        '売上高',
        '営業外収益',
        '特別利益',
    ];

    private const EXPENSE_CATEGORIES = [
        '売上原価',
        '販売管理費',
        '販売費及び一般管理費',
        '営業外費用',
        '特別損失',
        '法人税等',
    ];

    public function __construct(
        private readonly FreeeAccountingClient $accounting,
        private readonly ActualResultCalculationService $calculator,
    ) {}

    /**
     * 対象月の実績を計算する（保存はしない）。
     */
    public function calculateForMonth(FreeeCredential $credential, string $month): array
    {
        [$startDate, $endDate] = $this->monthRange($month);

        $trialPl = $this->accounting->trialPl($credential, $startDate, $endDate);
        $balances = $trialPl['balances'] ?? [];

        if ($balances === []) {
            throw ValidationException::withMessages([
                'message' => 'freeeから'.$month.'の損益計算書を取得できませんでした。対象月に仕訳があるか確認してください。',
            ]);
        }

        $projectNames = $this->projectNamesBySectionId();
        $detailRows = [];
        $unassignedTotal = 0;
        $unlinkedSections = [];
        $generatedAccounts = [];

        foreach ($balances as $line) {
            // freee自身の小計・合計行（売上高計、営業利益など）は取り込まない。
            if (filter_var($line['total_line'] ?? false, FILTER_VALIDATE_BOOL)) {
                continue;
            }

            $accountName = trim((string) ($line['account_item_name'] ?? ''));

            if ($accountName === '') {
                continue;
            }

            // 前回こちらから登録した積立金・間接配賦などは読み戻さない。
            // 読み戻すと「freeeに値がある＝計算済み」と判定され、勤怠を直しても
            // 金額が更新されなくなる。計算の持ち主は常にGLOWD側。
            if ($this->calculator->isGeneratedAccountName($accountName)) {
                $generatedAccounts[$accountName] = true;

                continue;
            }

            $categoryHint = $this->categoryHint($line);

            foreach ($line['sections'] ?? [] as $section) {
                $sectionId = (int) ($section['id'] ?? 0);

                // closing_balance は期首からの累計（CSVの「期末」に相当）で、
                // 指定期間の増減ではない。当月分は期末 − 期首で取る
                // （CSVの「貸借合計」に相当）。この差を取らないと前月分まで混ざる。
                $opening = (int) round((float) ($section['opening_balance'] ?? 0));
                $closing = (int) round((float) ($section['closing_balance'] ?? 0));
                $amount = $closing - $opening;

                if ($amount === 0) {
                    continue;
                }

                // 部門未選択の仕訳はプロジェクトに割り当てられない。黙って捨てず件数を返す。
                if ($sectionId === 0) {
                    $unassignedTotal += $amount;

                    continue;
                }

                $freeeName = trim((string) ($section['name'] ?? ''));
                $departmentName = $projectNames[$sectionId] ?? $freeeName;

                if ($departmentName === '') {
                    $unassignedTotal += $amount;

                    continue;
                }

                if (! isset($projectNames[$sectionId]) && $freeeName !== '') {
                    $unlinkedSections[$freeeName] = true;
                }

                $isSales = $categoryHint === 'sales';

                $detailRows[] = [
                    'source_department' => $departmentName,
                    'account_name' => $accountName,
                    // freeeのaccount_item_idは内部IDで、CSVの勘定科目コードとは別物。
                    // 400番台=売上のようなコード判定に誤って使われないよう空にする。
                    'account_code' => '',
                    'category_hint' => $categoryHint,
                    'debit' => (int) round((float) ($section['debit_amount'] ?? ($isSales ? 0 : $amount))),
                    'credit' => (int) round((float) ($section['credit_amount'] ?? ($isSales ? $amount : 0))),
                    'balance' => $amount,
                    // closing_balance は期首からの累計なので、そのまま期末残高として使える。
                    'ending_balance' => $closing,
                    'amount_source' => 'balance',
                    'has_amount' => true,
                ];
            }
        }

        $warnings = [];

        if ($unassignedTotal !== 0) {
            $warnings[] = 'freeeに部門が未設定の仕訳が '.number_format($unassignedTotal).'円 あります。プロジェクト別の実績には含めていません。';
        }

        if ($unlinkedSections !== []) {
            $names = collect(array_keys($unlinkedSections))->take(5)->implode('、');
            $warnings[] = 'プロジェクト未連携のfreee部門があります（'.$names.'）。freee側の部門名で集計しています。';
        }

        if ($generatedAccounts !== []) {
            $names = collect(array_keys($generatedAccounts));
            $shown = $names->take(5)->implode('、');
            $rest = $names->count() - min($names->count(), 5);

            $warnings[] = 'freee登録済みのGLOWD計算分（'.$shown.($rest > 0 ? ' 他'.$rest.'件' : '')
                .'）は取込対象から除外し、勤怠から再計算しました。';
        }

        return $this->calculator->calculateFromFreee($detailRows, $month, [
            'name' => 'freee 損益計算書',
            'title' => 'freee会計 損益計算書（'.$month.'）',
            'period' => $startDate.'～'.$endDate,
            'encoding' => null,
            'source' => 'freee',
            'source_rows' => count($balances),
            'freee_unassigned_total' => $unassignedTotal,
            'freee_warnings' => $warnings,
            'synced_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * 対象月の初日・末日。freeeのstart_month/end_monthは会計年度基準の月番号なので、
     * 暦月で確実に指定できる start_date / end_date を使う。
     *
     * @return array{0: string, 1: string}
     */
    private function monthRange(string $month): array
    {
        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'message' => '対象月の形式が正しくありません（YYYY-MM）。',
            ]);
        }

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }

    /**
     * freee部門ID → こちらのプロジェクト名。
     *
     * 実績はプロジェクト名をキーに集計・突合するため、連携済みならこちらの名称に寄せる。
     *
     * @return array<int, string>
     */
    private function projectNamesBySectionId(): array
    {
        return ProjectRecord::query()
            ->whereNotNull('freee_section_id')
            ->pluck('name', 'freee_section_id')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->all();
    }

    /**
     * 勘定科目区分から収益/費用を判定する。判定できなければ null（従来の名称判定に任せる）。
     */
    private function categoryHint(array $line): ?string
    {
        foreach (['account_category_name', 'parent_account_category_name', 'account_group_name'] as $field) {
            $value = trim((string) ($line[$field] ?? ''));

            if ($value === '') {
                continue;
            }

            if (in_array($value, self::INCOME_CATEGORIES, true)) {
                return 'sales';
            }

            if (in_array($value, self::EXPENSE_CATEGORIES, true)) {
                return 'expense';
            }
        }

        return null;
    }
}
