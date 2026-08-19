<?php

namespace App\Console\Commands;

use App\Models\FreeeCredential;
use App\Services\Freee\FreeeAccountingClient;
use App\Services\Freee\FreeeJournalPostService;
use Illuminate\Console\Command;

/**
 * 計算済みの積立金を freee に振替伝票として登録する（CLI版）。
 *
 * 画面の「freeeへ送信」と同じ FreeeJournalPostService を使うので、
 * 冪等性（同じ内容なら送らない／登録済みなら更新する）の扱いも同じ。
 */
class FreeePostReserveJournal extends Command
{
    protected $signature = 'freee:post-reserve
        {month : 対象月 (YYYY-MM)}
        {--bucket=* : 送る積立金の種類（省略時は全種類）}
        {--confirm : 実際にfreeeへ登録する（省略時はドライラン）}
        {--delete= : 指定IDの振替伝票を削除して終了する}';

    protected $description = '計算済みの積立金をfreeeの振替伝票として登録する（既定はドライラン）';

    public function handle(FreeeJournalPostService $journals, FreeeAccountingClient $accounting): int
    {
        $credential = FreeeCredential::query()
            ->where('active', true)
            ->where('status', FreeeCredential::STATUS_CONNECTED)
            ->orderBy('id')
            ->first();

        if (! $credential) {
            $this->error('連携済みのfreee設定がありません。');

            return self::FAILURE;
        }

        if ($journalId = $this->option('delete')) {
            $accounting->deleteManualJournal($credential, (int) $journalId);
            $this->info("振替伝票 {$journalId} を削除しました。");

            return self::SUCCESS;
        }

        $dryRun = ! $this->option('confirm');
        $outcome = $journals->postForMonth(
            $credential,
            (string) $this->argument('month'),
            $dryRun,
            null,
            (array) $this->option('bucket'),
        );

        $this->line('');
        $this->info('対象月: '.$outcome['month'].($dryRun ? '（ドライラン）' : ''));

        $this->table(
            ['種類', '結果', '金額', '伝票ID', '備考'],
            collect($outcome['results'])->map(fn (array $row) => [
                $row['label'],
                $row['action'],
                number_format((int) ($row['amount'] ?? 0)),
                $row['freee_journal_id'] ?? '-',
                $row['reason'] ?? '',
            ])->all()
        );

        foreach ($outcome['warnings'] as $warning) {
            $this->warn($warning);
        }

        if ($dryRun) {
            $this->comment('ドライランです。実際に登録するには --confirm を付けて再実行してください。');
        }

        return self::SUCCESS;
    }
}
