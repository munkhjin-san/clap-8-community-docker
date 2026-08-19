<?php

namespace App\Services\Freee;

use App\Models\FreeeCredential;
use App\Models\FreeeJournalPost;
use App\Models\ProjectRecord;
use App\Services\ActualResultCalculationService;
use App\Services\ActualResultPersistenceService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * GLOWDが計算した積立金を、freeeの振替伝票として登録・更新する。
 *
 * freeeには冪等キーが無いので、(対象月, 種類) ごとに登録済みの伝票IDを
 * freee_journal_posts に持ち、2回目以降は新規登録ではなく更新する。
 * 内容が前回と同じなら何も送らない。これで「何度押しても増えない」を担保する。
 */
class FreeeJournalPostService
{
    /** 送信対象 → [借方の科目名, 貸方の科目名]。 */
    public const BUCKET_ACCOUNTS = [
        'basic_bonus_reserve' => ['基本賞与積立金', '社内振替入金（基本賞与）'],
        'paid_leave_reserve' => ['有給積立金', '社内振替入金（有給）'],
        'welfare_reserve' => ['福利厚生積立金', '社内振替入金（福利）'],
        'refresh_reserve' => ['リフレッシュ補助積立金', '社内振替入金（リフレッシュ）'],
        'performance_bonus_reserve' => ['業績連動賞与積立金', '社内振替入金（業績連動賞与）'],
        'indirect_allocation_expense' => ['間接配賦発注額', '間接配賦売上高'],
    ];

    /**
     * 貸方を受ける部門。積立金は積立部門、間接配賦は間接費部門に集まる。
     */
    private const BUCKET_COUNTERPART_SECTION = [
        'indirect_allocation_expense' => self::INDIRECT_SECTION,
    ];

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_UNCHANGED = 'unchanged';

    public const ACTION_SKIPPED = 'skipped';

    private const RESERVE_SECTION = '積立部門';

    private const INDIRECT_SECTION = '間接費部門';

    /**
     * 賞与引当金繰入額。プロジェクト別ではなく会社全体の引当なので、
     * 他の6種とは形が違う（部門別ではなく品目別の内訳、貸方は貸借対照表科目）。
     */
    public const BONUS_ACCRUAL_BUCKET = 'bonus_accrual';

    /** 事業所によって「賞与引当金繰入額」「賞与引当金繰入」の両方がありうる。 */
    private const BONUS_ACCRUAL_DEBIT_NAMES = ['賞与引当金繰入額', '賞与引当金繰入'];

    private const BONUS_ACCRUAL_CREDIT_NAME = '賞与引当金';

    /** 内訳の品目名。基本賞与は勤怠ベース、業績連動賞与はマイナス込みの合計。 */
    private const BONUS_ACCRUAL_ITEMS = [
        'basic' => '基本賞与',
        'performance' => '業績連動賞与',
    ];

    public function __construct(
        private readonly FreeeAccountingClient $accounting,
        private readonly ActualResultPersistenceService $actualResults,
        private readonly ActualResultCalculationService $calculator,
    ) {}

    /**
     * 対象月の積立金をfreeeへ反映する。
     *
     * @param array<int, string> $buckets 省略時は全種類
     * @return array{month: string, dry_run: bool, results: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function postForMonth(
        FreeeCredential $credential,
        string $month,
        bool $dryRun = true,
        ?int $actorId = null,
        array $buckets = [],
    ): array {
        $payload = $this->actualResults->payloadForMonth($month);

        if (! $payload || empty($payload['departments'])) {
            throw ValidationException::withMessages([
                'message' => $month.' の実績が保存されていません。先にfreee取込を実行してください。',
            ]);
        }

        $sectionIds = $this->sectionIdsByProjectName($credential);
        $issueDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $targets = $buckets !== [] ? $buckets : array_keys(self::BUCKET_ACCOUNTS);
        $results = [];
        $warnings = [];
        $unmapped = [];

        foreach ($targets as $bucket) {
            if (! isset(self::BUCKET_ACCOUNTS[$bucket])) {
                continue;
            }

            $results[] = $this->syncBucket(
                $credential,
                $payload,
                $bucket,
                $month,
                $issueDate,
                $sectionIds,
                $dryRun,
                $actorId,
                $unmapped,
            );
        }

        if ($buckets === [] || in_array(self::BONUS_ACCRUAL_BUCKET, $buckets, true)) {
            $results[] = $this->syncBonusAccrual(
                $credential,
                $payload,
                $month,
                $issueDate,
                $sectionIds,
                $dryRun,
                $actorId,
            );
        }

        foreach ($unmapped as $name => $amount) {
            $warnings[] = 'freee部門が未連携のため除外しました：'.$name.'（'.number_format($amount).'円）';
        }

        return [
            'month' => $month,
            'dry_run' => $dryRun,
            'results' => $results,
            'warnings' => $warnings,
        ];
    }

    /**
     * 1種類ぶんの積立金をfreeeへ反映する。
     *
     * @param array<string, int> $sectionIds
     * @param array<string, int> $unmapped
     * @return array<string, mixed>
     */
    private function syncBucket(
        FreeeCredential $credential,
        array $payload,
        string $bucket,
        string $month,
        string $issueDate,
        array $sectionIds,
        bool $dryRun,
        ?int $actorId,
        array &$unmapped,
    ): array {
        [$debitName, $creditName] = self::BUCKET_ACCOUNTS[$bucket];
        $base = ['bucket' => $bucket, 'label' => $debitName];

        $debitAccount = $this->accounting->findAccountItemByName($credential, $debitName);
        $creditAccount = $this->accounting->findAccountItemByName($credential, $creditName);

        if (! $debitAccount || ! $creditAccount) {
            $missing = ! $debitAccount ? $debitName : $creditName;

            return $base + [
                'action' => self::ACTION_SKIPPED,
                'reason' => 'freeeに勘定科目「'.$missing.'」がありません。',
                'amount' => 0,
            ];
        }

        // 貸方を受ける部門は種類ごとに違う（積立金→積立部門 / 間接配賦→間接費部門）。
        $counterpartSection = self::BUCKET_COUNTERPART_SECTION[$bucket] ?? self::RESERVE_SECTION;
        $counterpartSectionId = $this->sectionIdFor($counterpartSection, $sectionIds);

        if (! $counterpartSectionId) {
            return $base + [
                'action' => self::ACTION_SKIPPED,
                'reason' => 'freeeに部門「'.$counterpartSection.'」がありません。',
                'amount' => 0,
            ];
        }

        $details = [];
        $total = 0;

        foreach ($payload['departments'] as $department) {
            $name = (string) ($department['department'] ?? '');
            $amount = (int) ($department['metrics'][$bucket] ?? $department[$bucket] ?? 0);

            // 貸方を受ける部門自身は借方に並べない。
            if ($amount === 0 || $name === $counterpartSection) {
                continue;
            }

            $sectionId = $this->sectionIdFor($name, $sectionIds);

            if (! $sectionId) {
                // 同じ部門が複数バケットで漏れても1件にまとめる。
                $unmapped[$name] = max($unmapped[$name] ?? 0, $amount);

                continue;
            }

            $details[] = [
                'entry_side' => 'debit',
                'account_item_id' => (int) $debitAccount['id'],
                'tax_code' => (int) ($debitAccount['tax_code'] ?? 2),
                'amount' => $amount,
                'section_id' => (int) $sectionId,
                'description' => 'GLOWD '.$month.' '.$debitName,
            ];
            $total += $amount;
        }

        if ($details === [] || $total === 0) {
            return $base + [
                'action' => self::ACTION_SKIPPED,
                'reason' => '対象金額がありません。',
                'amount' => 0,
            ];
        }

        $details[] = [
            'entry_side' => 'credit',
            'account_item_id' => (int) $creditAccount['id'],
            'tax_code' => (int) ($creditAccount['tax_code'] ?? 2),
            'amount' => $total,
            'section_id' => $counterpartSectionId,
            'description' => 'GLOWD '.$month.' '.$creditName,
        ];

        return $this->persistJournal(
            $credential,
            $base,
            $bucket,
            $month,
            $issueDate,
            $details,
            $total,
            $dryRun,
            $actorId,
        );
    }

    /**
     * 伝票の登録・更新と台帳の記録。積立金も賞与引当金も同じ冪等ルールを通す。
     *
     * @param array<string, mixed> $base
     * @param array<int, array<string, mixed>> $details
     * @return array<string, mixed>
     */
    private function persistJournal(
        FreeeCredential $credential,
        array $base,
        string $bucket,
        string $month,
        string $issueDate,
        array $details,
        int $total,
        bool $dryRun,
        ?int $actorId,
    ): array {
        $fingerprint = hash('sha256', json_encode([$issueDate, $details], JSON_UNESCAPED_UNICODE));
        $targetMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $existing = FreeeJournalPost::query()
            ->where('target_month', $targetMonth)
            ->where('bucket', $bucket)
            ->first();

        // 前回と同じ内容なら送らない。これが「何度押しても増えない」の要。
        // ただし freee 側で伝票が消されていることがあるので、その場合は登録し直す。
        // 存在確認をしないと、台帳とfreeeがずれたまま永久に「変更なし」になる。
        if ($existing && $existing->fingerprint === $fingerprint) {
            if ($this->accounting->manualJournalExists($credential, $existing->freee_journal_id)) {
                return $base + [
                    'action' => self::ACTION_UNCHANGED,
                    'amount' => $total,
                    'lines' => $details,
                    'freee_journal_id' => $existing->freee_journal_id,
                ];
            }

            // freee側に無い＝作り直す。台帳の伝票IDはもう使えない。
            $existing->delete();
            $existing = null;
        }

        $action = $existing ? self::ACTION_UPDATED : self::ACTION_CREATED;

        if ($dryRun) {
            return $base + [
                'action' => $action,
                'amount' => $total,
                'lines' => $details,
                'freee_journal_id' => $existing?->freee_journal_id,
            ];
        }

        $journalId = $existing
            ? $this->updateOrRecreate($credential, $existing, $issueDate, $details)
            : (int) ($this->accounting->createManualJournal($credential, $issueDate, $details)['id'] ?? 0);

        if ($journalId === 0) {
            return $base + [
                'action' => self::ACTION_SKIPPED,
                'reason' => 'freeeが伝票IDを返しませんでした。',
                'amount' => $total,
            ];
        }

        FreeeJournalPost::query()->updateOrCreate(
            ['target_month' => $targetMonth, 'bucket' => $bucket],
            [
                'freee_journal_id' => $journalId,
                'freee_company_id' => $credential->company_id,
                'fingerprint' => $fingerprint,
                'amount' => $total,
                'details' => $details,
                'posted_at' => now(),
                'posted_by' => $actorId,
            ],
        );

        return $base + [
            'action' => $action,
            'amount' => $total,
            'lines' => $details,
            'freee_journal_id' => $journalId,
        ];
    }

    /**
     * 賞与引当金繰入額をfreeeへ反映する。
     *
     * 積立金6種と違い、プロジェクト別ではなく会社全体の引当。
     *  - 借方: 賞与引当金繰入額（品目＝基本賞与 / 業績連動賞与の2行）
     *  - 貸方: 賞与引当金（他流動負債）
     * 業績連動賞与はマイナスになりうるので、その場合は貸借を入れ替えて絶対値で積む
     * （freeeは負の金額を受け付けないため）。
     *
     * @param array<string, int> $sectionIds
     * @return array<string, mixed>
     */
    private function syncBonusAccrual(
        FreeeCredential $credential,
        array $payload,
        string $month,
        string $issueDate,
        array $sectionIds,
        bool $dryRun,
        ?int $actorId,
    ): array {
        $base = ['bucket' => self::BONUS_ACCRUAL_BUCKET, 'label' => self::BONUS_ACCRUAL_DEBIT_NAMES[0]];

        $debitAccount = null;
        foreach (self::BONUS_ACCRUAL_DEBIT_NAMES as $name) {
            if ($debitAccount = $this->accounting->findAccountItemByName($credential, $name)) {
                $base['label'] = $name;
                break;
            }
        }

        $creditAccount = $this->accounting->findAccountItemByName($credential, self::BONUS_ACCRUAL_CREDIT_NAME);

        if (! $debitAccount || ! $creditAccount) {
            return $base + [
                'action' => self::ACTION_SKIPPED,
                'reason' => 'freeeに勘定科目「'
                    .(! $debitAccount ? self::BONUS_ACCRUAL_DEBIT_NAMES[0] : self::BONUS_ACCRUAL_CREDIT_NAME)
                    .'」がありません。',
                'amount' => 0,
            ];
        }

        $total = (int) ($payload['file']['generated_bonus_accrual_expense'] ?? 0);
        $basic = (int) ($payload['file']['generated_basic_bonus_accrual_total'] ?? 0);
        $performance = $total - $basic;

        if ($total === 0 && $basic === 0) {
            return $base + [
                'action' => self::ACTION_SKIPPED,
                'reason' => '対象金額がありません。',
                'amount' => 0,
            ];
        }

        $sectionId = $this->sectionIdFor(self::RESERVE_SECTION, $sectionIds);
        $details = [];

        foreach (['basic' => $basic, 'performance' => $performance] as $key => $amount) {
            if ($amount === 0) {
                continue;
            }

            $item = $this->accounting->findItemByName($credential, self::BONUS_ACCRUAL_ITEMS[$key]);

            if (! $item) {
                return $base + [
                    'action' => self::ACTION_SKIPPED,
                    'reason' => 'freeeに品目「'.self::BONUS_ACCRUAL_ITEMS[$key].'」がありません。',
                    'amount' => 0,
                ];
            }

            $details[] = array_filter([
                // マイナスは貸借を入れ替えて絶対値で積む。
                'entry_side' => $amount > 0 ? 'debit' : 'credit',
                'account_item_id' => (int) $debitAccount['id'],
                'tax_code' => (int) ($debitAccount['tax_code'] ?? 2),
                'amount' => abs($amount),
                'item_id' => (int) $item['id'],
                'section_id' => $sectionId,
                'description' => 'GLOWD '.$month.' '.$base['label'].'（'.self::BONUS_ACCRUAL_ITEMS[$key].'）',
            ], fn ($v) => $v !== null);
        }

        if ($details === [] || $total === 0) {
            return $base + [
                'action' => self::ACTION_SKIPPED,
                'reason' => '対象金額がありません。',
                'amount' => 0,
            ];
        }

        $details[] = array_filter([
            'entry_side' => $total > 0 ? 'credit' : 'debit',
            'account_item_id' => (int) $creditAccount['id'],
            'tax_code' => (int) ($creditAccount['tax_code'] ?? 2),
            'amount' => abs($total),
            'section_id' => $sectionId,
            'description' => 'GLOWD '.$month.' '.self::BONUS_ACCRUAL_CREDIT_NAME,
        ], fn ($v) => $v !== null);

        return $this->persistJournal(
            $credential,
            $base,
            self::BONUS_ACCRUAL_BUCKET,
            $month,
            $issueDate,
            $details,
            $total,
            $dryRun,
            $actorId,
        );
    }

    /**
     * 登録済み伝票を更新する。freee側で消されていたら作り直す。
     */
    private function updateOrRecreate(
        FreeeCredential $credential,
        FreeeJournalPost $existing,
        string $issueDate,
        array $details,
    ): int {
        try {
            $this->accounting->updateManualJournal($credential, $existing->freee_journal_id, $issueDate, $details);

            return $existing->freee_journal_id;
        } catch (ValidationException $exception) {
            $message = (string) collect($exception->errors())->flatten()->first();

            // freee側で削除済みなら、更新ではなく新規登録に切り替える。
            if (! str_contains($message, 'HTTP 404')) {
                throw $exception;
            }

            return (int) ($this->accounting->createManualJournal($credential, $issueDate, $details)['id'] ?? 0);
        }
    }

    /**
     * 部門名からfreeeの部門IDを引く。
     *
     * 取込時に畳んだ別名（経営管理本部共通部門 → 経営管理本部 など）は、
     * そのままではfreeeに該当する部門が無い。元の名前まで遡って探す。
     *
     * @param array<string, int> $sectionIds
     */
    private function sectionIdFor(string $department, array $sectionIds): ?int
    {
        foreach ($this->calculator->freeeSectionNameCandidates($department) as $candidate) {
            if (isset($sectionIds[$candidate])) {
                return $sectionIds[$candidate];
            }
        }

        return null;
    }

    /**
     * プロジェクト名 → freee部門ID。連携済みを優先し、未連携でも同名部門を拾う。
     *
     * @return array<string, int>
     */
    private function sectionIdsByProjectName(FreeeCredential $credential): array
    {
        $byName = [];

        foreach ($this->accounting->sections($credential, fresh: true) as $section) {
            $name = trim((string) ($section['name'] ?? ''));

            if ($name !== '') {
                $byName[$name] = (int) $section['id'];
            }
        }

        ProjectRecord::query()
            ->whereNotNull('freee_section_id')
            ->get(['name', 'freee_section_id'])
            ->each(function (ProjectRecord $project) use (&$byName) {
                $name = trim((string) $project->name);

                if ($name !== '') {
                    $byName[$name] = (int) $project->freee_section_id;
                }
            });

        return $byName;
    }
}
