<?php

namespace App\Console\Commands;

use App\Models\FlowField;
use App\Models\FlowRecordFile;
use App\Models\FlowRecordValue;
use App\Services\FlowFileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * 既存のファイル項目を新しい台帳（flow_record_files）と保管場所へ移す。
 *
 * 旧データには3つの形がある：
 *  1. `flow_record_files/{record_id}/{messageFileId}_{userId}.{ext}` — 保存済みのトップレベル項目
 *  2. `temp_upload/{messageFileId}.{ext}`                          — テーブル項目のファイル列
 *     （移す処理がどこにも無く、7日で掃除ジョブに消されていた分。実体が残っていないものが多い）
 *  3. 実体が既に無い                                                 — status=missing として残す
 *
 * 安全のための決め事：
 *  - **コピーしてから** value_json を書き換える。移動はしない。--prune を明示するまで旧ファイルは残る。
 *  - 何度でも実行できる。旧IDを legacy_message_file_id に残すので、2回目は既存行を見つけて作り直さない。
 *  - 実体が無い添付は**消さない**。「添付があった」という記録ごと消える方が困るので、missing で残す。
 */
class MigrateFlowRecordFiles extends Command
{
    protected $signature = 'flow:migrate-files
        {--dry-run : 変更を書かず、何が起きるかだけ表示する}
        {--verify : 移行後の全行について実体の有無とサイズを確認する}
        {--prune : 確認が通ったあと、旧い場所のファイルを削除する}';

    protected $description = 'カスタムアプリのファイル項目を flow_record_files へ移行する';

    private bool $dry = false;

    /** @var array<int, string> 移行できなかったもの（人が見る用） */
    private array $problems = [];

    private int $created = 0;

    private int $reused = 0;

    private int $copied = 0;

    private int $missing = 0;

    /** @var array<int, string> 旧い場所のパス（--prune の対象） */
    private array $legacyPaths = [];

    /** @var array<int, string> このコピーで中身が一致した行 id => md5 */
    private array $verifiedHashes = [];

    public function handle(FlowFileService $files): int
    {
        $this->dry = (bool) $this->option('dry-run');

        if ($this->option('verify') && ! $this->option('dry-run') && ! $this->hasAnythingToMigrate()) {
            return $this->verify();
        }

        $this->info($this->dry ? '=== 確認のみ（何も書き換えません） ===' : '=== 移行を実行します ===');

        $this->migrateTopLevelFields();
        $this->migrateTableColumns($files);

        $this->newLine();
        $this->line("台帳: 新規 {$this->created} 件 / 既存を再利用 {$this->reused} 件");
        $this->line("実体: コピー {$this->copied} 件 / 見つからず {$this->missing} 件");

        if ($this->problems !== []) {
            $this->newLine();
            $this->warn('実体が見つからなかった添付（status=missing で台帳に残しました）:');
            foreach ($this->problems as $line) {
                $this->line('  - '.$line);
            }
            $this->newLine();
            $this->warn('これらは旧 temp_upload 運用で7日の掃除ジョブに消された分です。復元はできません。');
        }

        if ($this->dry) {
            $this->newLine();
            $this->info('--dry-run のため何も書き換えていません。実行するには --dry-run を外してください。');

            return self::SUCCESS;
        }

        $this->newLine();
        $exit = $this->verify();

        if ($exit === self::SUCCESS && $this->option('prune')) {
            $this->pruneLegacy();
        } elseif ($this->option('prune')) {
            $this->error('確認に失敗したため、旧ファイルは削除しません。');
        } elseif ($exit === self::SUCCESS) {
            $this->info('旧い場所のファイルはそのまま残しています。削除するには --prune を付けて再実行してください。');
        }

        return $exit;
    }

    /** 未移行（legacy_message_file_id が付いていない value）が残っているか。 */
    private function hasAnythingToMigrate(): bool
    {
        foreach ($this->fileFieldValues() as [, $value]) {
            foreach ((array) $value->value_json as $entry) {
                if (is_array($entry) && ! empty($entry['id']) && $this->legacyRow((int) $entry['id']) === null) {
                    return true;
                }
            }
        }

        return false;
    }

    /* ------------------------------------------------------------------ */

    /** トップレベルのファイル項目。 */
    private function migrateTopLevelFields(): void
    {
        foreach ($this->fileFieldValues() as [$field, $value]) {
            $entries = (array) $value->value_json;
            if ($entries === []) {
                continue;
            }

            $next = [];
            foreach ($entries as $entry) {
                $file = $this->migrateEntry($field, $value, $entry, null);
                if ($file) {
                    $next[] = $file->valuePayload();
                }
            }

            $this->writeValue($value, $next, "レコード{$value->flow_record_id} / {$field->label}");
        }
    }

    /**
     * テーブル項目の中のファイル列。ここが元々どの移動処理も通っていなかった場所。
     */
    private function migrateTableColumns(FlowFileService $files): void
    {
        foreach (FlowField::where('input_type', 'table')->get() as $field) {
            $columns = $files->fileColumnKeys($field);
            if ($columns === []) {
                continue;
            }

            foreach (FlowRecordValue::where('flow_field_id', $field->id)->get() as $value) {
                $rows = (array) $value->value_json;
                if ($rows === []) {
                    continue;
                }
                $changed = false;

                foreach ($rows as $ri => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    foreach ($columns as $key) {
                        $cell = $row[$key] ?? null;
                        if (! is_array($cell) || $cell === []) {
                            continue;
                        }
                        $next = [];
                        foreach ($cell as $entry) {
                            $file = $this->migrateEntry($field, $value, $entry, $key);
                            if ($file) {
                                $next[] = $file->valuePayload();
                            }
                        }
                        $rows[$ri][$key] = $next;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $this->writeValue($value, $rows, "レコード{$value->flow_record_id} / {$field->label}（テーブル）");
                }
            }
        }
    }

    /**
     * 添付1件を移す。戻り値は新しい台帳の行（既に移行済みならその行）。
     */
    private function migrateEntry(FlowField $field, FlowRecordValue $value, mixed $entry, ?string $columnKey): ?FlowRecordFile
    {
        if (! is_array($entry) || empty($entry['id'])) {
            return null;
        }
        $legacyId = (int) $entry['id'];

        // 2回目以降：既に移した分はそのまま返す（作り直さない）
        if ($existing = $this->legacyRow($legacyId)) {
            $this->reused++;

            return $existing;
        }
        // すでに新IDで書かれている（部分的に移行済みの value を再処理した場合）
        if ($direct = FlowRecordFile::find($legacyId)) {
            if ($direct->flow_record_id === (int) $value->flow_record_id) {
                $this->reused++;

                return $direct;
            }
        }

        $recordId = (int) $value->flow_record_id;
        $definitionId = (int) $field->flow_definition_id;
        $extension = (string) ($entry['extension'] ?? '');
        $source = $this->locateLegacyFile($legacyId, $recordId, $entry);

        if ($this->dry) {
            $where = $source ?? '見つからず';
            $this->line(sprintf(
                '  %s レコード%d %s%s: %s ← %s',
                $source ? '·' : '!',
                $recordId,
                $field->label,
                $columnKey ? " › {$columnKey}" : '',
                (string) ($entry['name'] ?? $legacyId),
                $where,
            ));
            $source ? $this->copied++ : $this->missing++;
            if (! $source) {
                $this->problems[] = sprintf(
                    'レコード%d / %s%s / %s',
                    $recordId, $field->label, $columnKey ? " › {$columnKey}" : '', (string) ($entry['name'] ?? $legacyId)
                );
            }

            // dry-run では台帳を作らないので、元の entry をそのまま残す判断材料だけ出す
            return null;
        }

        $file = FlowRecordFile::create([
            'flow_definition_id' => $definitionId,
            'flow_record_id' => $recordId,
            'flow_field_id' => $field->id,
            'table_column_key' => $columnKey,
            'name' => (string) ($entry['name'] ?? "{$legacyId}.{$extension}"),
            'extension' => $extension,
            'mime_type' => $entry['mime_type'] ?? null,
            'size' => $entry['size'] ?? null,
            'disk_path' => '',
            'uploaded_by' => isset($entry['user_id']) ? (int) $entry['user_id'] : null,
            'status' => FlowRecordFile::STATUS_MISSING,
            'legacy_message_file_id' => $legacyId,
        ]);
        $this->created++;

        $dest = FlowRecordFile::pathFor($definitionId, $recordId, $file->id, $extension);

        if ($source === null) {
            // 実体が無い。台帳には残す——「添付があった」記録まで消すほうが損失が大きい。
            $file->update(['disk_path' => $dest, 'status' => FlowRecordFile::STATUS_MISSING]);
            $this->missing++;
            $this->problems[] = sprintf(
                'レコード%d / %s%s / %s',
                $recordId, $field->label, $columnKey ? " › {$columnKey}" : '', $file->name
            );

            return $file;
        }

        // コピー（移動ではない）。旧ファイルは --prune まで残す。
        $this->ensureDirectory($dest);
        $disk = Storage::disk('local');
        $disk->put($dest, $disk->get($source));

        // コピーが本当に同じ中身かをここで確かめる。ハッシュを突き合わせるのが要点——
        // 台帳のサイズをコピー後のファイルから取って後で見比べても、それは同じ数字を2回見ているだけ。
        $sourceHash = md5_file(storage_path('app/'.$source));
        $destHash = md5_file(storage_path('app/'.$dest));
        if ($sourceHash !== $destHash) {
            $disk->delete($dest);
            $file->update(['disk_path' => $dest, 'status' => FlowRecordFile::STATUS_MISSING]);
            $this->problems[] = "コピー内容が一致しませんでした: {$source} → {$dest}（この行は missing にしました）";
            $this->missing++;

            return $file;
        }

        $file->update([
            'disk_path' => $dest,
            // 元のサイズを正とする（台帳の値がコピー結果の写しにならないように）
            'size' => $disk->size($source),
            'status' => FlowRecordFile::STATUS_ATTACHED,
        ]);
        $this->copied++;
        $this->legacyPaths[] = $source;
        $this->verifiedHashes[$file->id] = $sourceHash;

        return $file;
    }

    /**
     * 旧い実体を探す。判明している3つの置き方を順に当たる。
     */
    private function locateLegacyFile(int $legacyId, int $recordId, array $entry): ?string
    {
        $disk = Storage::disk('local');
        $extension = (string) ($entry['extension'] ?? '');
        $userId = (int) ($entry['user_id'] ?? 0);

        $candidates = [];
        // value_json に url が入っていればそれが最も確か
        if (! empty($entry['url']) && is_string($entry['url'])) {
            $candidates[] = ltrim(preg_replace('#^/cdn/#', '', $entry['url']), '/');
        }
        // 旧トップレベル項目の置き方
        $candidates[] = "flow_record_files/{$recordId}/{$legacyId}_{$userId}.{$extension}";
        // テーブル列は temp_upload に置かれたまま
        $candidates[] = "temp_upload/{$legacyId}.{$extension}";

        foreach (array_unique($candidates) as $path) {
            if ($path !== '' && $disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function writeValue(FlowRecordValue $value, array $json, string $label): void
    {
        if ($this->dry) {
            return;
        }
        // value_json だけを差し替える（他の列に触らない）
        DB::table('flow_record_values')
            ->where('id', $value->id)
            ->update(['value_json' => json_encode($json, JSON_UNESCAPED_UNICODE)]);
        $this->line("  ✓ {$label}");
    }

    /* ------------------------------------------------------------------ */

    /**
     * 台帳の全行について実体があるかを確かめる。ここが通らないうちは --prune を実行しない。
     */
    private function verify(): int
    {
        $this->info('=== 確認 ===');
        $disk = Storage::disk('local');
        $ok = 0;
        $bad = [];
        $missing = 0;

        foreach (FlowRecordFile::all() as $file) {
            if ($file->status === FlowRecordFile::STATUS_MISSING) {
                $missing++;

                continue;
            }
            if ($file->disk_path === '' || ! $disk->exists($file->disk_path)) {
                $bad[] = "#{$file->id} {$file->name}: 実体が無い（{$file->disk_path}）";

                continue;
            }
            $size = $disk->size($file->disk_path);
            if ($file->size !== null && (int) $file->size !== $size) {
                $bad[] = "#{$file->id} {$file->name}: サイズ不一致（台帳 {$file->size} / 実体 {$size}）";

                continue;
            }
            $ok++;
        }

        $this->line("実体あり: {$ok} 件 / missing扱い: {$missing} 件 / 不整合: ".count($bad).' 件');

        // value_json から台帳に無いIDを参照していないかも見る（書き換え漏れの検出）
        $dangling = $this->danglingReferences();
        if ($dangling !== []) {
            $this->error('value_json が台帳に無いIDを参照しています（'.count($dangling).' 件）:');
            foreach (array_slice($dangling, 0, 20) as $line) {
                $this->line('  - '.$line);
            }
        }

        foreach ($bad as $line) {
            $this->error('  '.$line);
        }

        if ($bad === [] && $dangling === []) {
            $this->info('確認: 問題なし。');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    /** value_json にあって台帳に無いID（＝壊れた参照）。 */
    private function danglingReferences(): array
    {
        $known = FlowRecordFile::pluck('id')->all();
        $known = array_fill_keys($known, true);
        $out = [];

        foreach ($this->fileFieldValues() as [$field, $value]) {
            foreach ((array) $value->value_json as $entry) {
                if (is_array($entry) && ! empty($entry['id']) && ! isset($known[(int) $entry['id']])) {
                    $out[] = "レコード{$value->flow_record_id} / {$field->label} / id={$entry['id']}";
                }
            }
        }

        foreach (FlowField::where('input_type', 'table')->get() as $field) {
            $columns = app(FlowFileService::class)->fileColumnKeys($field);
            if ($columns === []) {
                continue;
            }
            foreach (FlowRecordValue::where('flow_field_id', $field->id)->get() as $value) {
                foreach ((array) $value->value_json as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    foreach ($columns as $key) {
                        foreach ((array) ($row[$key] ?? []) as $entry) {
                            if (is_array($entry) && ! empty($entry['id']) && ! isset($known[(int) $entry['id']])) {
                                $out[] = "レコード{$value->flow_record_id} / {$field->label}›{$key} / id={$entry['id']}";
                            }
                        }
                    }
                }
            }
        }

        return $out;
    }

    /**
     * 確認が通ったあとだけ、旧い場所のファイルを消す。
     *
     * 消す直前にもう一度、新しい場所に同じ中身が実在することを確かめる。ここを省くと
     * 「移行したつもり」で原本を消す事故が起きうる。
     */
    private function pruneLegacy(): void
    {
        $disk = Storage::disk('local');
        $deleted = 0;
        $skipped = 0;

        foreach (FlowRecordFile::whereIn('id', array_keys($this->verifiedHashes))->get() as $file) {
            if (! $disk->exists($file->disk_path)
                || md5_file(storage_path('app/'.$file->disk_path)) !== $this->verifiedHashes[$file->id]) {
                $this->error("  新しい場所の中身を確認できないため原本を残します: #{$file->id} {$file->name}");
                $skipped++;
            }
        }
        if ($skipped > 0) {
            $this->error('原本は削除しませんでした。');

            return;
        }

        foreach (array_unique($this->legacyPaths) as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
                $deleted++;
            }
        }

        // 空になった旧レコードフォルダも片付ける
        if ($disk->exists('flow_record_files')) {
            foreach ($disk->directories('flow_record_files') as $dir) {
                if ($disk->files($dir) === [] && $disk->directories($dir) === []) {
                    $disk->deleteDirectory($dir);
                }
            }
            if ($disk->directories('flow_record_files') === [] && $disk->files('flow_record_files') === []) {
                $disk->deleteDirectory('flow_record_files');
            }
        }

        $this->info("旧ファイルを {$deleted} 件削除しました。");
    }

    /* ------------------------------------------------------------------ */

    /** @return iterable<array{0: FlowField, 1: FlowRecordValue}> トップレベルのファイル項目の値 */
    private function fileFieldValues(): iterable
    {
        foreach (FlowField::where('input_type', 'file')->get() as $field) {
            foreach (FlowRecordValue::where('flow_field_id', $field->id)->get() as $value) {
                yield [$field, $value];
            }
        }
    }

    private function legacyRow(int $legacyId): ?FlowRecordFile
    {
        return FlowRecordFile::where('legacy_message_file_id', $legacyId)->first();
    }

    private function ensureDirectory(string $relativePath): void
    {
        $dir = storage_path('app/'.dirname($relativePath));
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }
    }
}
