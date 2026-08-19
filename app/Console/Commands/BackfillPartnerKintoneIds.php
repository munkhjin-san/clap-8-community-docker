<?php

namespace App\Console\Commands;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\PartnerRecord;
use Illuminate\Console\Command;
use Normalizer;

/**
 * 取り込み済みの取引先に kintone_partner_id（アプリ118の「取引先id」）を補充する。
 *
 * 取り込みコマンドを再実行すると手で直した内容まで戻ってしまうため、
 * この番号だけを名前で突き合わせて埋める。契約書（アプリ138）との紐付けに必要。
 */
class BackfillPartnerKintoneIds extends Command
{
    protected $signature = 'partners:backfill-kintone-ids
        {--app=118 : kintoneのアプリID}
        {--dry-run : 保存せず件数だけ表示する}';

    protected $description = '既存の取引先にkintoneの取引先id（契約書との突き合わせキー）を補充する';

    public function handle(KintoneClient $kintone): int
    {
        $records = $kintone->getAllRecords((int) $this->option('app'), '', ['$id', '会社名', '取引先id']);
        $this->info('kintone取得: '.count($records).'件');

        // 名前 => kintone取引先id。完全一致と、表記ゆれを畳んだ一致の2段構え。
        $exact = [];
        $loose = [];
        foreach ($records as $r) {
            $name = trim((string) ($r['会社名']['value'] ?? ''));
            $id = (int) preg_replace('/\D/', '', (string) ($r['取引先id']['value'] ?? ''));
            if ($name === '' || $id <= 0) {
                continue;
            }
            $exact[$name] ??= $id;
            $loose[$this->loosen($name)] ??= $id;
        }

        $filled = 0;
        $already = 0;
        $unmatched = [];
        $taken = PartnerRecord::whereNotNull('kintone_partner_id')->pluck('name', 'kintone_partner_id')->all();

        foreach (PartnerRecord::query()->cursor() as $partner) {
            if ($partner->kintone_partner_id !== null) {
                $already++;

                continue;
            }

            $name = trim((string) $partner->name);
            $id = $exact[$name] ?? $loose[$this->loosen($name)] ?? null;

            if ($id === null) {
                $unmatched[] = $partner->name;

                continue;
            }

            // 列にUNIQUE制約があるので、既に使われている番号は付けずに報告する。
            if (isset($taken[$id])) {
                $unmatched[] = "{$partner->name}（#{$id} は「{$taken[$id]}」が使用中）";

                continue;
            }

            $taken[$id] = $partner->name;
            $filled++;

            if (! $this->option('dry-run')) {
                $partner->forceFill(['kintone_partner_id' => $id])->saveQuietly();
            }
        }

        $this->table(
            ['補充', '設定済み', '未一致'],
            [[$filled, $already, count($unmatched)]],
        );

        foreach (array_slice($unmatched, 0, 10) as $name) {
            $this->warn('  未一致: '.$name);
        }
        if (count($unmatched) > 10) {
            $this->warn('  ほか'.(count($unmatched) - 10).'件');
        }

        if ($this->option('dry-run')) {
            $this->info('--dry-run のため保存しませんでした。');
        }

        return self::SUCCESS;
    }

    private function loosen(string $value): string
    {
        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        }

        return mb_strtolower(preg_replace('/[\s\x{3000}]+/u', '', $value) ?? $value);
    }
}
