<?php

namespace App\Console\Commands;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\FlowDefinition;
use App\Models\FlowField;
use App\Models\FlowRecord;
use App\Models\FlowRecordFile;
use App\Services\FlowFileService;
use App\Services\FlowService;
use App\Support\FlowProjectResolver;
use App\Support\FlowRichText;
use App\Support\FlowUserResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * kintoneアプリのレコードと添付ファイルを、カスタムアプリへ取り込む。
 *
 * 対応付けはフィールドコード。フォームを取り込んだときにkintoneのコードをそのまま
 * フィールドキーにしてあるので、名前で素直に突き合わせられる。
 *
 * **レコード番号は kintone の業務キーをそのまま使う。**
 * 取引先アプリでいえば「取引先id」——契約書(138)・仕様書(156)がこの値で取引先を参照しているので、
 * 番号が変わると後からそれらを移行したときに繋ぎ直せない。こちらの record_number は元々
 * アプリごとの連番なので、意味も作りも同じものに揃える形になる。
 *
 * 何度実行しても同じ結果になる：レコード番号で突き合わせ、既にあれば中身を入れ替える。
 */
class ImportKintoneRecords extends Command
{
    protected $signature = 'flow:import-kintone-records
        {kintone_app : kintone側のアプリID}
        {--definition= : 取り込み先のカスタムアプリID}
        {--number-field= : レコード番号に使うkintoneの項目コード（省略時は$idを使う）}
        {--dry-run : 書き込まず、取り込む内容を確認する}
        {--limit= : 先頭から指定件数だけ処理する（試すとき用）}
        {--skip-files : 添付ファイルを取り込まない}
        {--only= : この項目コードだけを取り込む（カンマ区切り。後から足した項目の穴埋め用）}
        {--status-field= : ステータスに使うkintoneの項目コード（プロセス管理なら「ステータス」）}
        {--only-files : 添付だけを取り込む（値は触らない。途中で止まっても続きから流せる）}
        {--force : 既にレコードがあっても続行する}';

    protected $description = 'kintoneアプリのレコードと添付ファイルを取り込む';

    private array $stats = [
        'created' => 0, 'updated' => 0, 'files' => 0, 'file_bytes' => 0,
        'file_failed' => 0, 'rows' => 0, 'skipped_values' => 0,
        'files_kept' => 0, 'files_removed' => 0,
    ];

    /** @var array<string, int> 取り込み先が無かったkintoneコード => 値を持っていたレコード数 */
    private array $unmapped = [];

    /** @var array<int, string> */
    private array $problems = [];

    /** @var array<int, array<int, int>> 参照先アプリID => レコード番号 => レコードID */
    private array $referenceMaps = [];

    /** @var array<string, array<int, int>> 参照が引けなかった番号（項目コード => 番号 => 件数） */
    private array $unresolvedRefs = [];

    /** @var array<int, string> kintoneでリッチエディタの項目コード（HTMLを外して入れる） */
    private array $richTextKeys = [];

    /** @var array<string, int> ステータス名 => flow_statuses.id */
    private array $statusIds = [];

    /** @var array<string, int> 割り当てられなかったステータス名 => 件数 */
    private array $unknownStatuses = [];

    public function handle(KintoneClient $kintone, FlowService $flow, FlowFileService $files): int
    {
        $appId = $this->argument('kintone_app');
        $dry = (bool) $this->option('dry-run');

        $definition = FlowDefinition::with('fields')->find($this->option('definition'));
        if (! $definition) {
            $this->error('--definition で取り込み先のカスタムアプリIDを指定してください。');

            return self::FAILURE;
        }

        $existing = $definition->records()->count();
        if ($existing > 0 && ! $this->option('force') && ! $dry) {
            $this->error("カスタムアプリ #{$definition->id} には既に {$existing} 件のレコードがあります。");
            $this->line('同じレコード番号は上書きされます。続けるなら --force を付けてください。');

            return self::FAILURE;
        }

        // 値を持てる項目だけ（見出し・ラベル・関連レコードは対象外）
        $targets = $definition->fields
            ->reject(fn ($f) => FlowService::isLayoutType($f->input_type) || $f->input_type === 'formula')
            ->keyBy('key');

        // --only：後から項目を足したときの穴埋め用。全項目を入れ直すと、こちらで書いた値
        // （カスタムボタンが入れた取引先IDなど）まで kintone の内容で上書きしてしまう。
        // --only-files：ファイルを持つ項目だけに絞る。値には触らないので、
        // 何度流しても既に入っている添付は飛ばして続きから進む。
        if ($this->option('only-files')) {
            $this->onlyKeysCache = $targets
                ->filter(fn ($f) => $f->input_type === 'file'
                    || ($f->input_type === 'table' && $files->fileColumnKeys($f) !== []))
                ->keys()->values()->all();
            if ($this->onlyKeysCache === []) {
                $this->error('このアプリにはファイル項目がありません。');

                return self::FAILURE;
            }
        }

        if ($only = $this->onlyKeys()) {
            $missing = array_diff($only, $targets->keys()->all());
            if ($missing !== []) {
                $this->error('--only に指定された項目が取り込み先にありません: '.implode('、', $missing));

                return self::FAILURE;
            }
            $targets = $targets->filter(fn ($f) => in_array($f->key, $only, true));
        }

        $this->info("kintone app {$appId} → カスタムアプリ #{$definition->id}「{$definition->name}」");
        $this->line('  取り込み先の項目: '.$targets->count().' 件'.($only ? '（--only 指定: '.implode('、', $only).'）' : ''));

        // プロセス管理を取り込んであるアプリでは、レコードの現在の状態も移す
        $statusField = (string) $this->option('status-field');
        if ($statusField !== '') {
            $this->statusIds = $definition->statuses()->pluck('id', 'name')->all();
            if ($this->statusIds === []) {
                $this->error("--status-field を指定しましたが、アプリ #{$definition->id} にステータスがありません。");

                return self::FAILURE;
            }
            $this->line('  ステータス: kintoneの「'.$statusField.'」→ '.count($this->statusIds).' 状態');
        }

        // kintoneのリッチエディタはHTMLを返す。こちらの複数行項目は素のテキストなので、
        // そのまま入れるとタグが本文として表示される。どの項目がそれかはkintone側にしか無い。
        $this->richTextKeys = collect($kintone->getAppFields($appId))
            ->filter(fn ($p) => ($p['type'] ?? '') === 'RICH_TEXT')
            ->keys()->values()->all();
        if ($this->richTextKeys !== []) {
            $this->line('  リッチエディタの項目（HTMLを外します）: '.implode('、', $this->richTextKeys));
        }

        $numberField = $this->option('number-field');
        $this->line('  レコード番号: '.($numberField ? "kintoneの「{$numberField}」" : 'kintoneの $id'));

        $this->line('  レコードを取得しています…');
        $records = $kintone->getAllRecords($appId);
        if ($limit = (int) $this->option('limit')) {
            $records = array_slice($records, 0, $limit);
        }
        $this->line('  取得: '.count($records).' 件');

        $numbers = $this->resolveNumbers($records, $numberField);
        if ($numbers === null) {
            return self::FAILURE;
        }

        $this->newLine();
        $bar = $this->output->createProgressBar(count($records));
        $bar->start();

        foreach ($records as $i => $kr) {
            $this->importOne($definition, $targets, $kr, $numbers[$i], $flow, $files, $dry);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        if (! $dry) {
            // 続きの番号が業務キーの続きになるよう、採番カウンタを最大値に合わせる
            $max = max(array_merge([0], $numbers));
            if ($max > (int) $definition->record_seq) {
                DB::table('flow_definitions')->where('id', $definition->id)->update(['record_seq' => $max]);
                $this->line("  採番カウンタを {$max} に合わせました（次のレコードは ".($max + 1).'）');
            }
        }

        $this->report($dry);

        return $this->problems === [] ? self::SUCCESS : self::SUCCESS;
    }

    /**
     * レコード番号を決める。業務キーを使う場合は、重複と欠損をここで弾く——
     * 途中まで入れてから気づくと、片付けるほうが高くつく。
     *
     * @return array<int, int>|null
     */
    private function resolveNumbers(array $records, ?string $field): ?array
    {
        $numbers = [];
        $missing = 0;
        foreach ($records as $kr) {
            $raw = $field ? ($kr[$field]['value'] ?? null) : ($kr['$id']['value'] ?? null);
            if (! is_numeric($raw)) {
                $missing++;
                $numbers[] = 0;

                continue;
            }
            $numbers[] = (int) $raw;
        }

        if ($missing > 0) {
            $this->error("レコード番号にする値が空のレコードが {$missing} 件あります（項目: ".($field ?: '$id').'）。');

            return null;
        }
        $dupes = array_keys(array_filter(array_count_values($numbers), fn ($n) => $n > 1));
        if ($dupes !== []) {
            $this->error('レコード番号が重複しています: '.implode(', ', array_slice($dupes, 0, 10)));

            return null;
        }

        return $numbers;
    }

    private function importOne(
        FlowDefinition $definition,
        $targets,
        array $kr,
        int $number,
        FlowService $flow,
        FlowFileService $files,
        bool $dry,
    ): void {
        // 取り込み先が無いkintone項目を数えておく（黙って落とさない）
        foreach ($kr as $code => $cell) {
            if (str_starts_with($code, '$') || $targets->has($code)) {
                continue;
            }
            if (filled($cell['value'] ?? null)) {
                $this->unmapped[$code] = ($this->unmapped[$code] ?? 0) + 1;
            }
        }

        if ($dry) {
            return;
        }

        $record = FlowRecord::firstOrNew([
            'flow_definition_id' => $definition->id,
            'record_number' => $number,
        ]);
        $isNew = ! $record->exists;
        // 穴埋めのときは、こちらに無いレコードを新しく作らない（既にあるものを埋めるだけ）
        if ($isNew && $this->onlyKeys() !== []) {
            $this->stats['skipped_values']++;

            return;
        }
        $record->created_by ??= null;
        $record->updated_by = null;

        // 現在の状態。遷移を通さず直接入れる——移行で通知を飛ばしたくないし、
        // kintone側の履歴どおりの経路を再生できるわけでもない。
        if ($this->statusIds !== [] && $this->onlyKeys() === []) {
            $name = trim((string) ($kr[(string) $this->option('status-field')]['value'] ?? ''));
            if ($name !== '') {
                $id = $this->statusIds[$name] ?? null;
                $id === null
                    ? $this->unknownStatuses[$name] = ($this->unknownStatuses[$name] ?? 0) + 1
                    : $record->current_status_id = $id;
            }
        }

        $record->save();
        $isNew ? $this->stats['created']++ : $this->stats['updated']++;

        foreach ($targets as $key => $field) {
            $cell = $kr[$key] ?? null;
            if ($cell === null) {
                continue;
            }

            if ($field->input_type === 'table') {
                $rows = $this->tableRows($record, $field, $cell['value'] ?? [], $files);
                $flow->saveFieldValue($record, $field, $rows);
                $this->stats['rows'] += count($rows);

                continue;
            }

            if ($field->input_type === 'file') {
                $this->attachFiles($record, $field, null, $cell['value'] ?? [], $files);

                continue;
            }

            $flow->saveFieldValue($record, $field, $this->scalar($field, $cell['value'] ?? null));
        }
    }

    /**
     * テーブルの行。ファイル列はここで実体を取り込み、行にはそのIDを入れる
     * （saveFieldValue → syncTableFiles が「このレコードのファイルか」を見て引き継ぐ）。
     */
    private function tableRows(FlowRecord $record, FlowField $field, array $value, FlowFileService $files): array
    {
        $columns = collect($field->validation['columns'] ?? [])->keyBy('key');

        // 前回分の片付けは**列ごとに1回**、行を回す前に。行の中でやると、ファイルの無い2行目が
        // 1行目の添付を消してしまう（--skip-files のときは purgeExisting 自身が何もしない）。
        foreach ($columns as $key => $col) {
            if (($col['input_type'] ?? '') === 'file') {
                $this->purgeExisting($record, $field, $key);
            }
        }
        $out = [];

        foreach ($value as $krow) {
            $cells = $krow['value'] ?? [];
            $row = [];
            foreach ($columns as $key => $col) {
                $cell = $cells[$key] ?? null;
                if ($cell === null) {
                    $row[$key] = ($col['input_type'] ?? '') === 'file' ? [] : null;

                    continue;
                }
                if (($col['input_type'] ?? '') === 'file') {
                    $stored = $this->attachFiles($record, $field, $key, $cell['value'] ?? [], $files);
                    $row[$key] = array_map(fn (FlowRecordFile $f) => ['id' => $f->id], $stored);

                    continue;
                }
                $row[$key] = $this->cellScalar($col['input_type'] ?? 'short', $cell['value'] ?? null);
            }
            $out[] = $row;
        }

        return $out;
    }

    /** @var array<int, string>|null 解決済みの --only / --only-files のキー */
    private ?array $onlyKeysCache = null;

    /** @return array<int, string> 対象を絞る項目コード（空なら全項目）。 */
    private function onlyKeys(): array
    {
        return $this->onlyKeysCache ??= collect(explode(',', (string) $this->option('only')))
            ->map(fn ($k) => trim($k))->filter()->values()->all();
    }

    /**
     * この場所（項目・列）に前回入れた添付を片付ける。
     *
     * 再実行できるようにするために要る。テーブルの列については**行ごとではなく列ごとに1回**
     * 呼ぶこと——行ごとに呼ぶと、2行目の取り込みが1行目の添付を消してしまう。
     */
    private function purgeExisting(FlowRecord $record, FlowField $field, ?string $columnKey): void
    {
        // --skip-files は「添付には触らない」という意味。ここで消してしまうと、取り込み直しの
        // ない削除になる（value_json は消えたIDを指したまま残り、実体だけが無くなる）。
        if ($this->option('skip-files')) {
            return;
        }

        $previous = FlowRecordFile::where('flow_record_id', $record->id)
            ->where('flow_field_id', $field->id)
            ->where('table_column_key', $columnKey)
            ->get();

        foreach ($previous as $old) {
            if ($old->disk_path && Storage::disk('local')->exists($old->disk_path)) {
                Storage::disk('local')->delete($old->disk_path);
            }
            $old->delete();
        }
    }

    /**
     * 添付をkintoneから落として保管する。
     *
     * @return array<int, FlowRecordFile>
     */
    private function attachFiles(FlowRecord $record, FlowField $field, ?string $columnKey, $value, FlowFileService $files): array
    {
        if ($this->option('skip-files')) {
            return [];
        }
        if (! is_array($value) || $value === []) {
            // kintone側が空。トップレベル項目はここで前回分を片付ける（テーブル列は
            // tableRows が列ごとに済ませているので、ここで触ると他の行の分まで消える）。
            if ($columnKey === null) {
                $this->purgeExisting($record, $field, null);
            }

            return [];
        }

        $kintone = app(KintoneClient::class);

        // 既に取り込んである分は fileKey で分かる。数GBの取り込みは必ず一度は途中で止まるので、
        // 「有るものは飛ばす」で続きから流せるようにしておく（消してから取り直さない）。
        $existing = FlowRecordFile::where('flow_record_id', $record->id)
            ->where('flow_field_id', $field->id)
            ->where('table_column_key', $columnKey)
            ->get()
            ->keyBy('kintone_file_key');

        $stored = [];
        $seenKeys = [];

        foreach ($value as $f) {
            $key = $f['fileKey'] ?? null;
            $name = $f['name'] ?? 'file';
            if (! $key) {
                continue;
            }
            $seenKeys[] = $key;

            $have = $existing->get($key);
            if ($have && Storage::disk('local')->exists($have->disk_path)) {
                $stored[] = $have;
                $this->stats['files_kept']++;

                continue;
            }

            try {
                // 1件ずつ取って、その場で保存する。1レコードに95件ぶら下がっているものがあるので、
                // レコード分をまとめてメモリに載せない。
                $body = (string) $kintone->getFiles($key)->getBody();
                $expected = (int) ($f['size'] ?? 0);
                if ($expected > 0 && strlen($body) !== $expected) {
                    $this->problems[] = "レコード#{$record->record_number} {$name}: サイズ不一致（kintone {$expected} / 取得 ".strlen($body).'）';
                    $this->stats['file_failed']++;

                    continue;
                }
                $stored[] = $files->storeImported($record, $field, $columnKey, $name, $body, $f['contentType'] ?? null, null, $key);
                $this->stats['files']++;
                $this->stats['file_bytes'] += strlen($body);
                unset($body);
            } catch (\Throwable $e) {
                $this->problems[] = "レコード#{$record->record_number} {$name}: 取得失敗 — ".mb_substr($e->getMessage(), 0, 120);
                $this->stats['file_failed']++;
                // 取れなかったものは既存の並びから外さない（次の実行で拾う）
                if ($have) {
                    $stored[] = $have;
                }
            }
        }

        // kintone側から消えた添付は、こちらからも外す。取得に失敗しただけのものは
        // $seenKeys に入っているので巻き添えにならない。
        foreach ($existing as $key => $old) {
            if ($key !== null && $key !== '' && ! in_array($key, $seenKeys, true)) {
                $files->deleteRecordFile($old);
                $this->stats['files_removed']++;
            }
        }

        if ($stored === []) {
            return [];
        }

        // トップレベルのファイル項目は value_json も自分で組む（saveFieldValue を通すと
        // 「画面から来たID一覧」として扱われ、取り込んだばかりのファイルと二重になる）
        if ($columnKey === null && $stored !== []) {
            $payload = array_map(fn (FlowRecordFile $f) => $f->valuePayload(), $stored);
            DB::table('flow_record_values')->updateOrInsert(
                ['flow_record_id' => $record->id, 'flow_field_id' => $field->id],
                ['value_json' => json_encode($payload, JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now()],
            );
        }

        return $stored;
    }

    private ?FlowUserResolver $userResolver = null;

    private ?FlowProjectResolver $projectResolver = null;

    private function users(): FlowUserResolver
    {
        return $this->userResolver ??= new FlowUserResolver;
    }

    private function projects(): FlowProjectResolver
    {
        return $this->projectResolver ??= new FlowProjectResolver;
    }

    /** kintoneの値をこちらの型に合わせる。 */
    private function scalar(FlowField $field, $value)
    {
        if ($field->input_type === 'reference') {
            return $this->reference($field, $value);
        }

        if (in_array($field->key, $this->richTextKeys, true)) {
            return FlowRichText::toPlainText(is_array($value) ? ($value['value'] ?? null) : $value);
        }

        return $this->cellScalar($field->input_type, $value);
    }

    /**
     * kintoneが持っている「相手の業務キー」を、こちらのレコードへの参照にする。
     *
     * 契約書の 取引先id は、取引先アプリのレコード番号そのもの——取り込みのときに
     * kintoneの業務キーをレコード番号にしたのはこのためで、ここで素直に引ける。
     * 参照は相手のレコードIDで持つので、番号からIDに直して渡す（表示名は保存側が付ける）。
     */
    private function reference(FlowField $field, $value): ?array
    {
        $number = is_array($value) ? ($value['value'] ?? null) : $value;
        $number = (int) $number;
        if ($number <= 0) {
            return null;
        }

        $targetId = (int) ($field->validation['target_definition_id'] ?? 0);
        if ($targetId <= 0) {
            return null;
        }

        $map = $this->referenceMaps[$targetId] ??= FlowRecord::where('flow_definition_id', $targetId)
            ->pluck('id', 'record_number')->all();

        $id = $map[$number] ?? null;
        if ($id === null) {
            // 相手が居ない。黙って空にすると、後から「なぜ紐付いていないのか」を追えなくなる。
            $this->unresolvedRefs[$field->key][$number] = ($this->unresolvedRefs[$field->key][$number] ?? 0) + 1;

            return null;
        }

        return ['id' => (int) $id];
    }

    private function cellScalar(string $type, $value)
    {
        if ($value === null) {
            return in_array($type, ['checkbox', 'user', 'member'], true) ? [] : null;
        }

        return match ($type) {
            'checkbox' => is_array($value) ? array_values($value) : (filled($value) ? [$value] : []),
            'number' => is_numeric($value) ? $value + 0 : null,
            'toggle' => (bool) $value,
            'date' => filled($value) ? substr((string) $value, 0, 10) : null,
            'datetime' => filled($value) ? substr(str_replace('Z', '', (string) $value), 0, 16) : null,
            // ユーザー項目：kintone側がユーザー選択でも、ルックアップでコピーした「氏名の文字列」でも、
            // どちらもこちらのユーザーIDに直す。取り込み先がユーザー項目かどうかで判断するので、
            // 文字列の列をユーザー項目に変えれば、次の取り込みから自動で追従する
            // （設定の二重管理を作らないための決め方）。
            'user', 'member' => $this->users()->resolveMany($value),
            // プロジェクト項目：kintoneの部門名（文字列）からプロジェクトを引く。
            // ユーザー項目と同じ考え方で、取り込み先の型を変えれば次から自動で追従する。
            'project' => $this->projects()->resolve(is_array($value) ? ($value['value'] ?? null) : (string) $value),
            default => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
        };
    }

    private function report(bool $dry): void
    {
        $this->info('=== 結果 ===');
        if ($dry) {
            $this->line('  --dry-run のため何も書き込んでいません。');
        } else {
            $this->line("  レコード: 新規 {$this->stats['created']} 件 / 更新 {$this->stats['updated']} 件");
            $this->line("  テーブル行: {$this->stats['rows']} 行");
            $this->line('  据え置き: '.$this->stats['files_kept'].' 件（取り込み済み）'
                .($this->stats['files_removed'] ? ' / 取り除き: '.$this->stats['files_removed'].' 件（kintone側から消えた分）' : ''));
            $this->line("  添付: {$this->stats['files']} 件 / ".number_format($this->stats['file_bytes'] / 1048576, 1).' MB'
                .($this->stats['file_failed'] ? " / 失敗 {$this->stats['file_failed']} 件" : ''));
        }

        if ($this->unmapped !== []) {
            arsort($this->unmapped);
            $this->newLine();
            $this->line('取り込み先が無く、値を捨てたkintone項目（除外指定・未対応の項目）:');
            foreach (array_slice($this->unmapped, 0, 30, true) as $code => $count) {
                $this->line("  - {$code}  （値のあるレコード {$count} 件）");
            }
        }

        if ($this->unresolvedRefs !== []) {
            $this->newLine();
            $this->line('参照先が見つからなかった番号（相手のレコードがこちらに無い）:');
            foreach ($this->unresolvedRefs as $key => $numbers) {
                arsort($numbers);
                $shown = collect($numbers)->take(10)->map(fn ($c, $n) => "#{$n}（{$c}件）")->implode('、');
                $this->line("  - {$key}: ".count($numbers).' 種類 → '.$shown.(count($numbers) > 10 ? ' …' : ''));
            }
        }

        if ($this->unknownStatuses !== []) {
            $this->newLine();
            $this->line('こちらに無いステータス名（そのレコードは未設定のまま）:');
            foreach ($this->unknownStatuses as $name => $count) {
                $this->line("  - {$name}  （{$count} 件）");
            }
        }

        $projectIssues = $this->projectResolver?->problems() ?? [];
        if ($projectIssues !== []) {
            $this->newLine();
            $this->line('部門名からプロジェクトを引いた結果:');
            foreach (array_slice($projectIssues, 0, 20, true) as $name => $why) {
                $this->line("  - {$name}: {$why}");
            }
            if (count($projectIssues) > 20) {
                $this->line('  … ほか '.(count($projectIssues) - 20).' 件');
            }
        }

        $userIssues = $this->userResolver?->problems() ?? [];
        if ($userIssues !== []) {
            $this->newLine();
            $this->line('氏名からユーザーを引いた結果:');
            foreach ($userIssues as $name => $why) {
                $this->line("  - {$name}: {$why}");
            }
        }

        if ($this->problems !== []) {
            $this->newLine();
            $this->warn('取り込めなかったもの:');
            foreach (array_slice($this->problems, 0, 20) as $p) {
                $this->line('  - '.$p);
            }
        }
    }
}
