<?php

namespace App\Services;

use App\Models\FlowDefinition;
use App\Models\FlowField;
use App\Models\FlowRecord;
use App\Models\FlowRecordFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

/**
 * カスタムアプリのファイル項目の実体管理。
 *
 * 以前の作り：共通の /attach_upload_api が `message_files`（チャット用テーブル）に行を作って
 * IDを借り、`temp_upload/` に置き、保存時に `flow_record_files/{record_id}/` へ移していた。
 * 直したのは3点：
 *  1. 台帳を専用テーブル `flow_record_files` に移した（チャット側に無関係な行が溜まらない）
 *  2. 保存先を1000件ごとのフォルダで区切った（1つの親に何十万もの兄弟フォルダを作らない）
 *  3. **テーブル項目の中のファイル列も同じ道を通す** ——ここが元々どこも通っておらず、
 *     ファイルが temp_upload に置かれたまま7日で掃除ジョブに消されていた（実データ喪失）。
 */
class FlowFileService
{
    /** pending のまま放置されたファイルを消すまでの日数。フォーム入力中に消えない程度に長く取る。 */
    public const PENDING_TTL_DAYS = 3;

    /**
     * アップロードを受け取り pending として保管する。レコードはまだ無い。
     */
    public function storePending(
        UploadedFile $upload,
        FlowDefinition $definition,
        ?FlowField $field,
        ?string $columnKey,
        int $userId,
    ): FlowRecordFile {
        $mime = $upload->getMimeType() ?: 'application/octet-stream';
        [$kind] = explode('/', $mime);
        $extension = strtolower($upload->getClientOriginalExtension());

        // 先に行を作ってIDを確定させる（IDがファイル名になるので、名前の衝突も日本語も起きない）
        $file = FlowRecordFile::create([
            'flow_definition_id' => $definition->id,
            'flow_record_id' => null,
            'flow_field_id' => $field?->id,
            'table_column_key' => $columnKey,
            'name' => $upload->getClientOriginalName(),
            'extension' => $extension,
            'mime_type' => $kind,
            'size' => $upload->getSize(),
            'disk_path' => '',
            'uploaded_by' => $userId,
            'status' => FlowRecordFile::STATUS_PENDING,
        ]);

        $path = FlowRecordFile::pendingPathFor($definition->id, $file->id, $extension);
        $this->write($upload, $path, $kind === 'image');

        $file->update([
            'disk_path' => $path,
            // 実際に書けたサイズを持つ（画像は再エンコードで変わる）
            'size' => Storage::disk('local')->exists($path) ? Storage::disk('local')->size($path) : $file->size,
        ]);

        return $file;
    }

    /**
     * 外部から取り込んだファイルを、はじめからレコードに結び付いた状態で保管する。
     *
     * storePending はブラウザからの UploadedFile 前提だが、kintoneからの移行は生のバイト列で
     * 手に入る。pending を経由せず直接 attached にするのは、移行の途中で掃除ジョブに拾われる
     * 隙を作らないため。
     */
    public function storeImported(
        FlowRecord $record,
        FlowField $field,
        ?string $columnKey,
        string $name,
        string $contents,
        ?string $mimeType,
        ?int $userId,
        ?string $externalKey = null,
    ): FlowRecordFile {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        [$kind] = explode('/', $mimeType ?: 'application/octet-stream');

        $file = FlowRecordFile::create([
            'flow_definition_id' => $record->flow_definition_id,
            'flow_record_id' => $record->id,
            'flow_field_id' => $field->id,
            'table_column_key' => $columnKey,
            'name' => $name,
            'extension' => $extension,
            'mime_type' => $kind,
            'size' => strlen($contents),
            'disk_path' => '',
            'uploaded_by' => $userId,
            'status' => FlowRecordFile::STATUS_ATTACHED,
            // 取り込み元での識別子。再実行で「もう有る」を判定するのに使う。
            'kintone_file_key' => $externalKey,
        ]);

        $path = FlowRecordFile::pathFor($record->flow_definition_id, $record->id, $file->id, $extension);
        $this->ensureDirectory($path);
        Storage::disk('local')->put($path, $contents);

        $file->update(['disk_path' => $path, 'size' => Storage::disk('local')->size($path)]);

        return $file;
    }

    /**
     * 保存時：ファイル項目（トップレベル）の中身を確定させる。
     *
     * @param  array  $incoming  画面から来た一覧（少なくとも id を持つ）
     * @param  array  $old  直前の value_json
     * @return array 新しい value_json
     */
    public function syncFieldFiles(FlowRecord $record, FlowField $field, array $incoming, array $old): array
    {
        $files = $this->claim($record, $field, null, $this->idsOf($incoming));
        $this->deleteUnreferenced($record, $this->idsOf($old), $files->pluck('id')->all());

        return $files->map->valuePayload()->values()->all();
    }

    /**
     * 保存時：テーブル項目の中のファイル列を確定させる。
     *
     * ここが今回の本題。行は並べ替え・削除されるので、行番号は当てにせず「このテーブル全体で
     * 参照されているID」だけを見る。参照から外れたIDは実体ごと消す。
     *
     * @param  array  $rows  正規化済みの行（tableValue の出力）
     * @param  array  $oldRows  直前の value_json
     * @return array 新しい行
     */
    public function syncTableFiles(FlowRecord $record, FlowField $field, array $rows, array $oldRows): array
    {
        $fileColumns = $this->fileColumnKeys($field);
        if ($fileColumns === []) {
            return $rows;
        }

        $keptIds = [];
        foreach ($rows as $ri => $row) {
            foreach ($fileColumns as $key) {
                $cell = $row[$key] ?? null;
                if (! is_array($cell)) {
                    $rows[$ri][$key] = [];

                    continue;
                }
                $files = $this->claim($record, $field, $key, $this->idsOf($cell));
                $rows[$ri][$key] = $files->map->valuePayload()->values()->all();
                $keptIds = array_merge($keptIds, $files->pluck('id')->all());
            }
        }

        // 旧の行すべてから参照IDを集めて差分を消す（行が消えた／列が空になった分もここで拾う）
        $oldIds = [];
        foreach ($oldRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($fileColumns as $key) {
                $oldIds = array_merge($oldIds, $this->idsOf(is_array($row[$key] ?? null) ? $row[$key] : []));
            }
        }
        $this->deleteUnreferenced($record, $oldIds, $keptIds);

        return $rows;
    }

    /**
     * 送られてきたIDを、このレコードのファイルとして確定させる。
     *
     * 受け付ける条件を絞るのが要点：
     *  - 同じアプリのファイルであること（他アプリのIDを差し込んでも通らない）
     *  - pending なら、このレコードを保存している本人がアップロードしたものであること
     *  - すでに attached なら、このレコード自身のファイルであること（他レコードのファイルは奪えない）
     */
    private function claim(FlowRecord $record, FlowField $field, ?string $columnKey, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $actorId = (int) ($record->updated_by ?: $record->created_by);
        $rows = FlowRecordFile::whereIn('id', $ids)
            ->where('flow_definition_id', $record->flow_definition_id)
            ->get()
            ->keyBy('id');

        $out = collect();
        // 画面が並べた順を保つ
        foreach ($ids as $id) {
            $file = $rows->get($id);
            if (! $file) {
                continue;
            }

            if ($file->status === FlowRecordFile::STATUS_PENDING) {
                if ($actorId && (int) $file->uploaded_by !== $actorId) {
                    continue;
                }
                $this->attach($file, $record, $field, $columnKey);
                $out->push($file);

                continue;
            }

            // attached / missing: 自分のレコードのものだけ引き継ぐ
            if ((int) $file->flow_record_id !== (int) $record->id) {
                continue;
            }
            // テーブル内の列を移動した場合に追随する（実体は動かさない）
            if ($file->flow_field_id !== $field->id || $file->table_column_key !== $columnKey) {
                $file->update(['flow_field_id' => $field->id, 'table_column_key' => $columnKey]);
            }
            $out->push($file);
        }

        return $out;
    }

    /** pending → attached。実体を pending 置き場からレコードのフォルダへ移す。 */
    private function attach(FlowRecordFile $file, FlowRecord $record, FlowField $field, ?string $columnKey): void
    {
        $dest = FlowRecordFile::pathFor($record->flow_definition_id, $record->id, $file->id, $file->extension);
        $disk = Storage::disk('local');

        if ($file->disk_path !== $dest) {
            if ($disk->exists($file->disk_path)) {
                $this->ensureDirectory($dest);
                $disk->move($file->disk_path, $dest);
            } else {
                // 実体が無いまま attached にすると壊れたリンクになる。台帳には残して状態で示す。
                Log::warning('Flow file missing while attaching; recorded as missing.', [
                    'flow_record_file_id' => $file->id,
                    'expected_path' => $file->disk_path,
                ]);
                $file->update([
                    'flow_record_id' => $record->id,
                    'flow_field_id' => $field->id,
                    'table_column_key' => $columnKey,
                    'status' => FlowRecordFile::STATUS_MISSING,
                ]);

                return;
            }
        }

        $file->update([
            'flow_record_id' => $record->id,
            'flow_field_id' => $field->id,
            'table_column_key' => $columnKey,
            'disk_path' => $dest,
            'status' => FlowRecordFile::STATUS_ATTACHED,
        ]);
    }

    /** 項目から外されたファイルを、台帳ごと実体ごと消す。 */
    private function deleteUnreferenced(FlowRecord $record, array $oldIds, array $keptIds): void
    {
        $gone = array_values(array_diff(array_unique($oldIds), $keptIds));
        if ($gone === []) {
            return;
        }

        $rows = FlowRecordFile::whereIn('id', $gone)
            ->where('flow_record_id', $record->id)
            ->get();

        foreach ($rows as $file) {
            $this->forget($file);
        }
    }

    /** レコード削除時：そのレコードのファイルをすべて消す（フォルダごと）。 */
    public function deleteForRecord(FlowRecord $record): void
    {
        $this->deleteForRecordIds($record->flow_definition_id, [$record->id]);
    }

    /**
     * まとめ削除（レコード一括削除 / アプリのレコード全削除）。
     * 台帳を消してからフォルダを落とす。台帳に無い残骸もフォルダ削除で一緒に片付く。
     */
    public function deleteForRecordIds(int $definitionId, array $recordIds): void
    {
        if ($recordIds === []) {
            return;
        }

        FlowRecordFile::whereIn('flow_record_id', $recordIds)->delete();

        $disk = Storage::disk('local');
        foreach ($recordIds as $recordId) {
            $dir = FlowRecordFile::recordDirFor($definitionId, (int) $recordId);
            if ($disk->exists($dir)) {
                $disk->deleteDirectory($dir);
            }
        }
    }

    /** 未保存のまま取り消されたファイル（画面で×を押した分）。 */
    public function discardPending(FlowRecordFile $file): void
    {
        if ($file->status !== FlowRecordFile::STATUS_PENDING) {
            return;
        }
        $this->forget($file);
    }

    /** 期限切れの pending を掃除する。戻り値は消した件数。 */
    public function purgePending(int $days = self::PENDING_TTL_DAYS): int
    {
        $rows = FlowRecordFile::where('status', FlowRecordFile::STATUS_PENDING)
            ->where('created_at', '<=', now()->subDays($days))
            ->get();

        foreach ($rows as $file) {
            $this->forget($file);
        }

        return $rows->count();
    }

    /**
     * 読み出し時：value_json の一覧に URL と状態を付ける。
     *
     * URLは保存していない（保管場所を変えるたびにデータ移行が要る作りにしたくない）。
     * 台帳が無いIDは、旧データの取りこぼしとして status=missing で返す——黙って消すと
     * 「添付があったこと」の記録まで消えてしまう。
     */
    public function decorate(array $entries): array
    {
        $ids = $this->idsOf($entries);
        if ($ids === []) {
            return [];
        }
        $rows = FlowRecordFile::whereIn('id', $ids)->get()->keyBy('id');

        $out = [];
        foreach ($entries as $entry) {
            if (! is_array($entry) || empty($entry['id'])) {
                continue;
            }
            $file = $rows->get((int) $entry['id']);
            if ($file) {
                $payload = $file->apiPayload();
                // 台帳が attached と言っていても実体が無いことはありうる（外部での削除、移行漏れ）。
                // 読むたびに1回 stat するだけなので、開けないリンクを出すより確かめる方が安い。
                if ($payload['status'] === FlowRecordFile::STATUS_ATTACHED
                    && ! Storage::disk('local')->exists($file->disk_path)) {
                    $payload['status'] = FlowRecordFile::STATUS_MISSING;
                    $payload['url'] = null;
                }
                $out[] = $payload;

                continue;
            }
            $out[] = [
                'id' => (int) $entry['id'],
                'name' => (string) ($entry['name'] ?? ''),
                'extension' => $entry['extension'] ?? null,
                'mime_type' => $entry['mime_type'] ?? null,
                'size' => $entry['size'] ?? null,
                'user_id' => $entry['user_id'] ?? null,
                'url' => null,
                'status' => FlowRecordFile::STATUS_MISSING,
            ];
        }

        return $out;
    }

    /** テーブル項目のうちファイル列のキー。 */
    public function fileColumnKeys(FlowField $field): array
    {
        return collect($field->validation['columns'] ?? [])
            ->filter(fn ($c) => ($c['input_type'] ?? null) === 'file')
            ->pluck('key')
            ->filter()
            ->values()
            ->all();
    }

    /** 台帳と実体をまとめて消す。 */
    /** 1件だけ台帳と実体から取り除く（取り込み元から消えた添付など）。 */
    public function deleteRecordFile(FlowRecordFile $file): void
    {
        $this->forget($file);
    }

    private function forget(FlowRecordFile $file): void
    {
        if ($file->disk_path !== '' && Storage::disk('local')->exists($file->disk_path)) {
            Storage::disk('local')->delete($file->disk_path);
        }
        $file->delete();
    }

    /** @return array<int> 一覧から数値IDだけを、重複なく順序どおりに */
    private function idsOf(array $entries): array
    {
        $ids = [];
        foreach ($entries as $entry) {
            $id = is_array($entry) ? ($entry['id'] ?? null) : $entry;
            if (is_numeric($id) && ! in_array((int) $id, $ids, true)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    private function ensureDirectory(string $relativePath): void
    {
        $dir = storage_path('app/'.dirname($relativePath));
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }
    }

    /**
     * 実体を書く。画像は既存の運用どおり再圧縮する（旧 attachUpload と同じ品質30）。
     */
    private function write(UploadedFile $upload, string $path, bool $isImage): void
    {
        $this->ensureDirectory($path);

        if ($isImage) {
            try {
                Image::read($upload)->save(storage_path('app/'.$path), 30);

                return;
            } catch (\Throwable $e) {
                // 画像として読めない（拡張子だけ画像、破損など）ならそのまま置く。
                Log::info('Flow file image re-encode failed; storing as-is.', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Storage::disk('local')->putFileAs(dirname($path), $upload, basename($path));
    }
}
