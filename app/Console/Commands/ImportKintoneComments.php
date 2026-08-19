<?php

namespace App\Console\Commands;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\AppComment;
use App\Models\FlowDefinition;
use App\Models\FlowRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * kintoneのレコードコメントを取り込む。
 *
 * **書き手は結び付けない。** kintoneのコメントはあちらのアカウントが書いたもので、
 * こちらのユーザーとは一致しない。無理に近い名前へ寄せると「その人が言っていない事」を
 * その人の発言として残すことになる。user_id は空、元の名前は文字として残す。
 *
 * 日時は元のまま入れる。並べ直したときにやり取りの順序が保てないと、読み返せない。
 *
 * 何度でも流せる：入れたコメントには kintone 側のIDを控えてあるので、二度目からは飛ばす。
 * 1レコードにつき最低1回の問い合わせが要るので（コメントの有無はレコード単位でしか分からない）、
 * 1792件なら1792回。時間はかかるが、途中で止めて再開できる。
 *
 *   php artisan flow:import-kintone-comments 138 --definition=56 --number-field=契約書id --dry-run
 */
class ImportKintoneComments extends Command
{
    protected $signature = 'flow:import-kintone-comments
        {kintone_app : kintone側のアプリID}
        {--definition= : 取り込み先のカスタムアプリID}
        {--number-field= : レコード番号に使ったkintoneの項目コード（省略時は $id）}
        {--limit= : 先頭から指定件数のレコードだけ見る（試すとき用）}
        {--dry-run : 書き込まず、取り込む件数だけ数える}';

    protected $description = 'kintoneのレコードコメントを、書き手を結び付けずに取り込む';

    public function handle(KintoneClient $kintone): int
    {
        $appId = $this->argument('kintone_app');
        $dry = (bool) $this->option('dry-run');

        $definition = FlowDefinition::find($this->option('definition'));
        if (! $definition) {
            $this->error('--definition で取り込み先のカスタムアプリIDを指定してください。');

            return self::FAILURE;
        }

        $numberField = (string) $this->option('number-field');
        $this->info("kintone app {$appId} → カスタムアプリ #{$definition->id}「{$definition->name}」");
        $this->line('  レコード番号: '.($numberField !== '' ? "kintoneの「{$numberField}」" : 'kintoneの $id'));

        // こちらのレコード番号 => レコードID
        $idByNumber = FlowRecord::where('flow_definition_id', $definition->id)
            ->pluck('id', 'record_number')->all();
        if ($idByNumber === []) {
            $this->error('取り込み先にレコードがありません。先にレコードを取り込んでください。');

            return self::FAILURE;
        }

        $records = $kintone->getAllRecords($appId, '', $numberField !== '' ? ['$id', $numberField] : ['$id']);
        if ($limit = (int) $this->option('limit')) {
            $records = array_slice($records, 0, $limit);
        }
        $this->line('  kintone側のレコード: '.count($records).' 件');

        $done = collect(AppComment::where('commentable_type', FlowRecord::class)
            ->whereIn('commentable_id', array_values($idByNumber))
            ->whereNotNull('kintone_comment_key')
            ->pluck('kintone_comment_key'))->flip();
        if ($done->isNotEmpty()) {
            $this->line('  取り込み済みのコメント: '.$done->count().' 件（飛ばします）');
        }

        $bar = $this->output->createProgressBar(count($records));
        $bar->start();

        $found = 0;
        $created = 0;
        $skipped = 0;
        $noRecord = [];
        $authors = [];

        foreach ($records as $kr) {
            $bar->advance();

            $number = (int) ($numberField !== '' ? ($kr[$numberField]['value'] ?? 0) : ($kr['$id']['value'] ?? 0));
            $recordId = $idByNumber[$number] ?? null;

            $comments = $kintone->getComments($appId, (int) $kr['$id']['value']);
            if ($comments === []) {
                continue;
            }
            $found += count($comments);

            if ($recordId === null) {
                // コメントは在るのに、こちらに相手のレコードが無い。数えて後で知らせる。
                $noRecord[$number] = count($comments);

                continue;
            }

            foreach ($comments as $c) {
                $key = "kintone:{$appId}:{$kr['$id']['value']}:{$c['id']}";
                if ($done->has($key)) {
                    $skipped++;

                    continue;
                }
                $author = trim((string) ($c['creator']['name'] ?? ''));
                if ($author !== '') {
                    $authors[$author] = ($authors[$author] ?? 0) + 1;
                }

                if (! $dry) {
                    AppComment::create([
                        'commentable_type' => FlowRecord::class,
                        'commentable_id' => $recordId,
                        'user_id' => null,
                        'legacy_author' => $author !== '' ? $author : null,
                        'kintone_comment_key' => $key,
                        'content' => (string) ($c['text'] ?? ''),
                        // 元の投稿日時のまま。並び替えのために書き換えない。
                        'created_at' => Carbon::parse($c['createdAt'] ?? now()),
                        'updated_at' => Carbon::parse($c['createdAt'] ?? now()),
                    ]);
                }
                $created++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('=== 結果 ===');
        $this->line("  kintone側のコメント: {$found} 件");
        $this->line('  '.($dry ? '取り込む予定' : '取り込んだ').": {$created} 件".($skipped ? " / 取り込み済みで飛ばした: {$skipped} 件" : ''));

        if ($noRecord !== []) {
            $this->newLine();
            $this->warn('相手のレコードがこちらに無く、入れられなかったコメント:');
            foreach (array_slice($noRecord, 0, 10, true) as $n => $c) {
                $this->line("  - レコード番号 {$n}: {$c} 件");
            }
        }

        if ($authors !== []) {
            arsort($authors);
            $this->newLine();
            $this->line('元の書き手（こちらのユーザーには結び付けていません）:');
            foreach (array_slice($authors, 0, 15, true) as $name => $count) {
                $this->line("  - {$name}  （{$count} 件）");
            }
        }

        if ($dry) {
            $this->newLine();
            $this->comment('--dry-run のため何も書き込んでいません。');
        }

        return self::SUCCESS;
    }
}
