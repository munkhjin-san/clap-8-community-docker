<?php

namespace App\Console\Commands;

use App\Models\FlowRecordFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * status=missing の添付に、バックアップから戻した実体を結び付け直す。
 *
 * flow:migrate-files ではこれはできない。あちらは value_json を辿るが、value_json は移行時に
 * 既に新しいIDへ書き換わっており、`migrateEntry()` は「移行済み」と判断してそのまま返すだけになる。
 * 台帳側から探すこの経路が要る。
 *
 * 台帳の行には移行時に disk_path（本来の置き場）と legacy_message_file_id（旧 temp_upload での
 * ファイル名）の両方が入っているので、戻す先も探す名前も既に分かっている。
 *
 *   php artisan flow:restore-missing-files --dry-run
 *   php artisan flow:restore-missing-files --from=/var/backups/0803/temp_upload
 */
class RestoreFlowMissingFiles extends Command
{
    protected $signature = 'flow:restore-missing-files
        {--from= : 復元したファイルが置いてあるフォルダ（既定: storage/app/temp_upload）}
        {--from-copies : 同名のファイルが他の機能（チャット・報告・名刺など）に残っていないか探す}
        {--dry-run : 何が戻せるかだけを表示する}';

    protected $description = 'バックアップから戻した実体を、missing の添付に結び付け直す';

    private bool $dry = false;

    private int $restored = 0;

    private int $stillMissing = 0;

    private array $problems = [];

    /** @var array<string, string>|null 同名で生き残っている実体（ファイル名 => 相対パス） */
    private ?array $copyIndex = null;

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry-run');
        $from = rtrim((string) ($this->option('from') ?: storage_path('app/temp_upload')), '/');

        $useCopies = (bool) $this->option('from-copies');

        if (! File::isDirectory($from)) {
            if (! $useCopies) {
                $this->error("探索フォルダがありません: {$from}");

                return self::FAILURE;
            }
            $from = null;
        }

        $rows = FlowRecordFile::where('status', FlowRecordFile::STATUS_MISSING)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            $this->info('missing の添付はありません。');

            return self::SUCCESS;
        }

        $where = $from ?? '(フォルダ指定なし)';
        if ($useCopies) {
            $where .= ' ＋ 他機能に残る同名ファイル';
        }
        $this->info(($this->dry ? '=== 確認のみ ===' : '=== 復元 ===')." 対象 {$rows->count()} 件 / 探索先 {$where}");

        foreach ($rows as $file) {
            $this->restoreOne($file, $from, $useCopies);
        }

        $this->newLine();
        $this->info("戻した: {$this->restored} 件 / まだ見つからない: {$this->stillMissing} 件");
        foreach ($this->problems as $p) {
            $this->warn('  ! '.$p);
        }
        if ($this->dry) {
            $this->comment('--dry-run のため何も書いていません。');
        }

        return self::SUCCESS;
    }

    private function restoreOne(FlowRecordFile $file, ?string $from, bool $useCopies): void
    {
        $source = $this->locate($file, $from, $useCopies);

        if ($source === null) {
            $this->stillMissing++;
            $this->line("  · #{$file->id} {$file->name}: 見つからず");

            return;
        }

        // 空ファイルは「戻った」ことにしない——欠損より質が悪い（開けるが中身が無い）。
        if (filesize($source) === 0) {
            $this->stillMissing++;
            $this->problems[] = "#{$file->id} {$file->name}: 中身が空（{$source}）";

            return;
        }

        $dest = $file->disk_path !== ''
            ? $file->disk_path
            : FlowRecordFile::pathFor($file->flow_definition_id, $file->flow_record_id, $file->id, $file->extension);

        $this->line(sprintf('  ✓ #%d %s ← %s', $file->id, $file->name, $source));

        if ($this->dry) {
            $this->restored++;

            return;
        }

        $disk = Storage::disk('local');
        // 既に何かある場合は触らない。missing のはずの場所に実体があるのは想定外なので、
        // 上書きするより人が見た方がいい。
        if ($disk->exists($dest)) {
            $this->stillMissing++;
            $this->problems[] = "#{$file->id}: 復元先に既にファイルがあります（{$dest}）— 手で確認してください";

            return;
        }

        File::isDirectory(dirname(storage_path("app/{$dest}")))
            or File::makeDirectory(dirname(storage_path("app/{$dest}")), 0755, true, true);

        $disk->put($dest, file_get_contents($source));

        // 中身が同じであることを確かめてから attached にする。ここを通っていない行を
        // 「戻った」と記録すると、次に誰かが困るまで嘘が残る。
        if (md5_file($source) !== md5_file(storage_path("app/{$dest}"))) {
            $disk->delete($dest);
            $this->stillMissing++;
            $this->problems[] = "#{$file->id}: コピー内容が一致しませんでした（{$source}）";

            return;
        }

        $file->update([
            'disk_path' => $dest,
            'size' => filesize($source),
            'status' => FlowRecordFile::STATUS_ATTACHED,
        ]);
        $this->restored++;
    }

    /**
     * 実体を探す。旧 temp_upload の名前（ID.拡張子）が第一候補、
     * 人がバックアップから抜いたときは元のファイル名で置かれることもあるので、そちらも見る。
     */
    private function locate(FlowRecordFile $file, ?string $from, bool $useCopies): ?string
    {
        $candidates = [];
        if ($from !== null) {
            if ($file->legacy_message_file_id) {
                $candidates[] = "{$from}/{$file->legacy_message_file_id}.{$file->extension}";
            }
            $candidates[] = "{$from}/{$file->name}";
        }
        if ($useCopies && ($copy = $this->copyIndex()[$file->name] ?? null)) {
            $candidates[] = storage_path("app/{$copy}");
        }

        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * 同じファイルが他の機能にも上げられていた場合、そちらの実体は temp_upload から
     * 移されているので生き残っている。ファイル名で突き合わせる——同じ書類を
     * チャットにも貼っていた、という拾い方が現実には一番よく当たる。
     *
     * @return array<string, string> ファイル名 => storage/app からの相対パス
     */
    private function copyIndex(): array
    {
        if ($this->copyIndex !== null) {
            return $this->copyIndex;
        }

        $names = FlowRecordFile::where('status', FlowRecordFile::STATUS_MISSING)->pluck('name')->unique()->all();
        $disk = Storage::disk('local');
        $index = [];

        DB::table('message_files')
            ->whereIn('name', $names)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get()
            ->each(function ($row) use (&$index, $disk) {
                if (isset($index[$row->name])) {
                    return;
                }
                foreach ($this->pathsForLegacyOwner($row) as $rel) {
                    if ($disk->exists($rel)) {
                        $index[$row->name] = $rel;

                        return;
                    }
                }
            });

        return $this->copyIndex = $index;
    }

    /**
     * 旧 message_files の持ち主ごとの置き場。機能ごとに命名が違うので、ここに並べるしかない。
     *
     * @return array<int, string>
     */
    private function pathsForLegacyOwner(object $row): array
    {
        $stem = "{$row->id}_{$row->user_id}.{$row->extension}";
        $paths = [];

        if ($row->message_id && $row->board_id) {
            $paths[] = "shared_files/{$row->board_id}/{$row->id}_{$row->user_id}_{$row->message_id}.{$row->extension}";
        }
        if ($row->project_checkitem_report_id) {
            $paths[] = "project_checkitem_report_files/{$stem}";
        }
        if ($row->project_goal_report_id || $row->salary_issue_report_id) {
            $paths[] = "project_goal_report_files/{$stem}";
        }
        if ($row->contact_record_id) {
            $paths[] = "contact_files/{$stem}";
        }
        if ($row->comment_record_id) {
            $paths[] = "contact_comment_files/{$stem}";
        }
        if ($row->app_comment_id) {
            $paths[] = "app_comment_files/{$row->app_comment_id}/{$stem}";
        }

        return $paths;
    }
}
