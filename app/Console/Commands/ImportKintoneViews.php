<?php

namespace App\Console\Commands;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\FlowDefinition;
use Illuminate\Console\Command;

/**
 * kintoneの一覧（ビュー）設定を取り込む。
 *
 * 列・絞り込み条件・並び順が対象。kintoneのカスタマイズ一覧（type=CUSTOM）とカレンダーは
 * こちらに対応する表示が無いので取り込まない。
 *
 * **絞り込み条件が再現できないビューは作らない。** 列が1つ欠けるのは見た目の問題だが、
 * 条件が欠けたビューは「全件が出る一覧」に化ける。「宛名ラベル作成」がその例で、
 * チェックの付いたレコードだけを出すはずの一覧が、黙って891件を出すようになってしまう。
 * 足りない項目を知らせて、そのビューは飛ばす。
 */
class ImportKintoneViews extends Command
{
    protected $signature = 'flow:import-kintone-views
        {kintone_app : kintone側のアプリID}
        {--definition= : 取り込み先のカスタムアプリID}
        {--dry-run : 書き込まず、取り込む内容を確認する}
        {--replace : 既存の同名ビューを置き換える（既定では新規のみ追加）}';

    protected $description = 'kintoneの一覧（列・絞り込み・並び順）を取り込む';

    /** kintoneのシステム項目 => こちらの列センチネル。 */
    private const SYSTEM_COLUMNS = [
        'レコード番号' => '$record_number',
        '作成日時' => '$created_at',
        '更新日時' => '$updated_at',
        'ステータス' => '$status',
    ];

    public function handle(KintoneClient $kintone): int
    {
        $appId = $this->argument('kintone_app');
        $dry = (bool) $this->option('dry-run');

        $definition = FlowDefinition::with(['fields', 'views'])->find($this->option('definition'));
        if (! $definition) {
            $this->error('--definition で取り込み先のカスタムアプリIDを指定してください。');

            return self::FAILURE;
        }

        $byKey = $definition->fields->keyBy('key');
        $views = collect($kintone->getViews($appId))->sortBy(fn ($v) => (int) ($v['index'] ?? 0));

        $this->info("kintone app {$appId} → カスタムアプリ #{$definition->id}「{$definition->name}」");
        $this->line('  kintoneの一覧: '.$views->count().' 件');
        $this->newLine();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($views as $v) {
            $name = (string) ($v['name'] ?? '');
            $type = (string) ($v['type'] ?? '');

            if ($type !== 'LIST') {
                $this->line("  － {$name}: {$type} は対応する表示がないため取り込みません。");
                $skipped++;

                continue;
            }

            // 列：無い項目は落とす（見た目だけの欠けなので、ビュー自体は作る）
            $columns = [];
            $missingCols = [];
            foreach ($v['fields'] ?? [] as $code) {
                if (isset(self::SYSTEM_COLUMNS[$code])) {
                    $columns[] = self::SYSTEM_COLUMNS[$code];

                    continue;
                }
                $f = $byKey->get($code);
                $f ? $columns[] = $f->id : $missingCols[] = $code;
            }

            // 絞り込み：1つでも再現できなければ、このビューは作らない
            $parsed = $this->parseFilter((string) ($v['filterCond'] ?? ''), $byKey);
            if ($parsed['unresolved'] !== []) {
                $this->warn("  ✕ {$name}: 絞り込みに使う項目がありません（".implode('、', $parsed['unresolved']).'）。');
                $this->line('     条件が抜けたまま作ると全件が出る一覧になるので飛ばしました。');
                $skipped++;

                continue;
            }

            $sort = $this->parseSort((string) ($v['sort'] ?? ''), $byKey);

            $existing = $definition->views->firstWhere('name', $name);
            if ($existing && ! $this->option('replace')) {
                $this->line("  － {$name}: 同名のビューが既にあるため触れていません（--replace で置き換え）。");
                $skipped++;

                continue;
            }

            $note = [];
            if ($missingCols !== []) {
                $note[] = '列 '.count($missingCols).' 件を除外（'.implode('、', array_slice($missingCols, 0, 3))
                    .(count($missingCols) > 3 ? '…' : '').'）';
            }
            if ($parsed['conditions'] !== []) {
                $note[] = '条件 '.count($parsed['conditions']).' 件';
            }
            if ($sort !== []) {
                $note[] = '並び順あり';
            }

            $this->line('  '.($existing ? '↻' : '＋')." {$name}: 列 ".count($columns).($note ? '  ／'.implode(' ／', $note) : ''));

            if ($dry) {
                continue;
            }

            $payload = [
                'name' => $name,
                'view_mode' => 'table',
                'columns' => $columns,
                'filters' => $parsed['conditions'],
                'filter_logic' => $parsed['logic'],
                'sort' => $sort,
            ];

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                $definition->views()->create($payload + ['is_default' => false, 'created_by' => null]);
                $created++;
            }
        }

        $this->newLine();
        $this->info($dry
            ? '--dry-run のため何も書き込んでいません。'
            : "追加 {$created} 件 / 更新 {$updated} 件 / 見送り {$skipped} 件");

        return self::SUCCESS;
    }

    /**
     * kintoneの絞り込み条件をこちらの形にする。
     *
     * 対応するのは `項目 演算子 値` を and / or で繋いだ形。kintoneの条件式はこれ以上に
     * 書けるが（括弧の入れ子など）、このアプリの一覧はすべてこの範囲に収まっている。
     * 解釈できない部分は unresolved に入れて、呼び出し側がビューごと見送る。
     *
     * @return array{conditions: array<int, array<string, mixed>>, logic: string, unresolved: array<int, string>}
     */
    private function parseFilter(string $cond, $byKey): array
    {
        $cond = trim($cond);
        if ($cond === '') {
            return ['conditions' => [], 'logic' => 'and', 'unresolved' => []];
        }

        $logic = preg_match('/\bor\b/i', $cond) && ! preg_match('/\band\b/i', $cond) ? 'or' : 'and';
        $parts = preg_split('/\s+(?:and|or)\s+/i', $cond) ?: [];

        $conditions = [];
        $unresolved = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (! preg_match('/^(.+?)\s+(not in|in|not like|like|>=|<=|!=|=|>|<)\s+(.+)$/iu', $part, $m)) {
                $unresolved[] = $part;

                continue;
            }
            [$code, $op, $raw] = [trim($m[1]), strtolower(trim($m[2])), trim($m[3])];

            $field = $byKey->get($code);
            if (! $field) {
                $unresolved[] = $code;

                continue;
            }

            $values = $this->parseValues($raw);
            // kintone の `in ("")` は「空であること」を意味する
            $isEmptyCheck = $values === [''] || $values === [];

            $operator = match ($op) {
                'in' => $isEmptyCheck ? 'is_empty' : 'includes_any',
                'not in' => $isEmptyCheck ? 'not_empty' : 'not_equals',
                '=' => $isEmptyCheck ? 'is_empty' : 'equals',
                '!=' => $isEmptyCheck ? 'not_empty' : 'not_equals',
                'like' => 'contains',
                'not like' => 'not_contains',
                '>' => 'gt', '>=' => 'gte', '<' => 'lt', '<=' => 'lte',
                default => null,
            };
            if ($operator === null) {
                $unresolved[] = $part;

                continue;
            }

            $conditions[] = [
                'field' => $field->id,
                'operator' => $operator,
                'values' => in_array($operator, ['is_empty', 'not_empty'], true) ? [] : $values,
            ];
        }

        return ['conditions' => $conditions, 'logic' => $logic, 'unresolved' => array_values(array_unique($unresolved))];
    }

    /** `("A","B")` / `"A"` / `5` → 値の配列。 */
    private function parseValues(string $raw): array
    {
        $raw = trim($raw);
        if (str_starts_with($raw, '(')) {
            $raw = trim($raw, '()');
        }
        if ($raw === '') {
            return [''];
        }
        preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"|([^,\s]+)/u', $raw, $m, PREG_SET_ORDER);

        $out = [];
        foreach ($m as $one) {
            $out[] = isset($one[2]) && $one[2] !== '' ? $one[2] : stripslashes($one[1]);
        }

        return $out === [] ? [''] : $out;
    }

    /** `会社名 asc` / `レコード番号 desc` → こちらの並び順。 */
    private function parseSort(string $sort, $byKey): array
    {
        $out = [];
        foreach (explode(',', $sort) as $part) {
            $part = trim($part);
            if ($part === '' || ! preg_match('/^(.+?)\s+(asc|desc)$/iu', $part, $m)) {
                continue;
            }
            $code = trim($m[1]);
            $ref = self::SYSTEM_COLUMNS[$code] ?? ($byKey->get($code)->id ?? null);
            if ($ref === null) {
                continue;
            }
            $out[] = ['field' => $ref, 'direction' => strtolower($m[2])];
        }

        return $out;
    }
}
