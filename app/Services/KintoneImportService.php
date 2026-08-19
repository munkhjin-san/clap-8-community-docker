<?php

namespace App\Services;

use App\Infrastructure\Kintone\KintoneClient;
use App\Support\FlowRichText;

/**
 * Reads a kintone app's config + form fields and maps them onto our ワークフロー (flow_*) schema.
 * This class only PREVIEWS (no writes) — the actual import/commit is a separate step.
 */
class KintoneImportService
{
    public function __construct(
        private KintoneClient $kintone,
        private KintoneFormulaConverter $formulaConverter,
    ) {}

    /** kintone field type → our input_type. Types not listed are unsupported (skipped). */
    private const TYPE_MAP = [
        'SINGLE_LINE_TEXT' => 'short',
        'MULTI_LINE_TEXT' => 'long',
        'RICH_TEXT' => 'long',
        'NUMBER' => 'number',
        'CALC' => 'number',   // kintone formula isn't portable → import the field as a plain number
        'LINK' => 'short',
        'RADIO_BUTTON' => 'radio',
        'DROP_DOWN' => 'select',
        'CHECK_BOX' => 'checkbox',
        'MULTI_SELECT' => 'checkbox',
        'DATE' => 'date',
        'TIME' => 'time',
        'DATETIME' => 'datetime',
        'USER_SELECT' => 'user',
        'FILE' => 'file',
        'LABEL' => 'label',
    ];

    /** kintone system fields — we already provide equivalents, so skip silently (not "unsupported"). */
    private const SYSTEM_TYPES = [
        'RECORD_NUMBER', 'CATEGORY', 'STATUS', 'STATUS_ASSIGNEE',
        'CREATOR', 'CREATED_TIME', 'MODIFIER', 'UPDATED_TIME',
    ];

    /** Fetch the app + its form fields and return a review payload (no writes). */
    public function preview(int|string $appId): array
    {
        $app = $this->kintone->getApp($appId);
        $properties = $this->kintone->getAppFields($appId);

        $fields = [];
        $this->collectFields($properties, $fields);
        $this->convertFormulas($fields);

        $supported = array_values(array_filter($fields, fn ($f) => $f['supported']));
        $skipped = array_values(array_filter($fields, fn ($f) => ! $f['supported']));

        return [
            'app' => [
                'id' => $app['appId'] ?? (string) $appId,
                'name' => $app['name'] ?? ('App '.$appId),
                'description' => $app['description'] ?? null,
            ],
            'fields' => $fields,
            'summary' => [
                'total' => count($fields),
                'supported' => count($supported),
                'skipped' => count($skipped),
            ],
            'status_flow' => $this->mapStatusFlow($appId),
        ];
    }

    /**
     * フォームを「見た目どおり」に写した取込計画を作る。
     *
     * preview() との違いは、レイアウト（app/form/layout.json）も読むこと。kintone は
     * ラベル・罫線・スペースを項目として持たず、レイアウト上の要素としてしか持たないので、
     * fields.json だけを見ると装飾が全部落ちる。行の並び・同じ行に何が並ぶか・グループの区切りも
     * レイアウト側にしかない。
     *
     * @return array{fields: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>, groups: int}
     */
    public function formPlan(int|string $appId, array $exclude = []): array
    {
        $properties = $this->kintone->getAppFields($appId);
        $layout = $this->kintone->getFormLayout($appId);

        $ctx = [
            'fields' => [],
            'skipped' => [],
            'groups' => 0,
            'row' => 0,
            'order' => 0,
            'seen' => [],   // 取り込んだkintoneコード
            // 取り込まないもの。運用で「不要」と判断した項目を毎回手で消さずに済むよう、
            // 取込側に持たせる（消したあと再取込したら戻ってくる、を防ぐ）。
            'skipGroups' => array_fill_keys($exclude['groups'] ?? [], true),
            'skipFields' => array_fill_keys($exclude['fields'] ?? [], true),
            'skipColumns' => array_fill_keys($exclude['columns'] ?? [], true),
            'excluded' => [],
        ];
        $this->walkLayout($layout, $properties, $ctx);

        // レイアウトに現れない項目（あってはならないが、黙って落とすほうが困る）を末尾に足す
        foreach ($properties as $code => $prop) {
            $code = $prop['code'] ?? (string) $code;
            $type = $prop['type'] ?? '';
            if (isset($ctx['seen'][$code]) || in_array($type, self::SYSTEM_TYPES, true) || $type === 'GROUP') {
                continue;
            }
            if (isset($ctx['skipFields'][$code])) {
                $ctx['excluded'][] = ['kind' => 'field', 'code' => $code, 'label' => $prop['label'] ?? $code];

                continue;
            }
            $ctx['row']++;
            $this->pushDataField($prop, $code, null, $ctx, orphan: true);
        }

        $renamed = $this->dedupeLabels($ctx['fields']);

        // 全部が除外された行（見出しだけ残る等）は畳んで、空の行を作らない
        $this->compactRows($ctx['fields']);

        return [
            'fields' => $ctx['fields'],
            'skipped' => $ctx['skipped'],
            'groups' => $ctx['groups'],
            'renamed' => $renamed,
            'excluded' => $ctx['excluded'],
        ];
    }

    /**
     * 値を持つ項目のラベルを一意にする。
     *
     * kintoneはコードだけを一意にしていて、ラベルは重複できる（このアプリには「締め日」が甲用・乙用で
     * 2つある）。こちら側のビルダーはラベル重複を保存時に弾くので、そのまま入れると
     * 「設定を開いて保存」ができないアプリになる。連番を付けて避け、どれを変えたかは呼び出し側に返す。
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array{key: string, from: string, to: string}>
     */
    private function dedupeLabels(array &$fields): array
    {
        $used = [];
        $renamed = [];

        foreach ($fields as &$f) {
            if (in_array($f['input_type'], ['heading', 'label', 'divider', 'spacer'], true)) {
                continue;   // 装飾は表示テキストであって識別子ではない
            }
            $base = trim((string) $f['label']);
            if ($base === '') {
                continue;
            }
            if (! isset($used[$base])) {
                $used[$base] = 1;

                continue;
            }
            $n = ++$used[$base];
            $next = $base.' ('.$n.')';
            while (isset($used[$next])) {
                $next = $base.' ('.(++$n).')';
            }
            $used[$next] = 1;
            $renamed[] = ['key' => $f['key'], 'from' => $base, 'to' => $next];
            $f['label'] = $next;
        }
        unset($f);

        return $renamed;
    }

    /**
     * 除外したまとまりの中にある項目コードを「見た」ことにする。
     * レイアウトに現れない項目を拾う処理から隠すためだけの印。
     */
    private function markSeen(array $items, array &$ctx): void
    {
        foreach ($items as $item) {
            $type = $item['type'] ?? '';
            if ($type === 'GROUP') {
                $this->markSeen($item['layout'] ?? [], $ctx);

                continue;
            }
            if ($type === 'SUBTABLE') {
                if (filled($item['code'] ?? null)) {
                    $ctx['seen'][$item['code']] = true;
                }

                continue;
            }
            foreach ($item['fields'] ?? [] as $el) {
                if (filled($el['code'] ?? null)) {
                    $ctx['seen'][$el['code']] = true;
                }
            }
        }
    }

    /**
     * 除外で歯抜けになった行番号を詰める。番号自体に意味は無いが、空いた番号をそのまま残すと
     * フォームに何も無い行が生まれる。
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function compactRows(array &$fields): void
    {
        $rows = array_values(array_unique(array_map(fn ($f) => $f['layout_row'], $fields)));
        sort($rows);
        $remap = array_flip($rows);
        foreach ($fields as &$f) {
            $f['layout_row'] = $remap[$f['layout_row']];
        }
        unset($f);
    }

    /** レイアウトを順に歩いて、行番号を振りながら項目を並べる。 */
    private function walkLayout(array $items, array $properties, array &$ctx): void
    {
        foreach ($items as $item) {
            $type = $item['type'] ?? '';

            if ($type === 'GROUP') {
                // kintoneのグループは折りたためる枠。こちらに枠は無いので見出しで区切る。
                $code = $item['code'] ?? '';
                // グループ単位の除外：中の項目もラベルも見出しも丸ごと取り込まない。
                // 中の項目を「見た」印を付けておくのが要点——付けないと、あとでレイアウトに
                // 現れない項目を拾う処理が、除外したはずの項目を末尾に足してしまう。
                if (isset($ctx['skipGroups'][$code])) {
                    $ctx['excluded'][] = ['kind' => 'group', 'code' => $code, 'label' => $properties[$code]['label'] ?? $code];
                    $this->markSeen($item['layout'] ?? [], $ctx);

                    continue;
                }
                $label = $properties[$code]['label'] ?? $code;
                $ctx['groups']++;
                $ctx['row']++;
                $ctx['fields'][] = $this->layoutField('heading', $label, 1080, $ctx);
                $this->walkLayout($item['layout'] ?? [], $properties, $ctx);

                continue;
            }

            if ($type === 'SUBTABLE') {
                $code = $item['code'] ?? '';
                $prop = $properties[$code] ?? null;
                if (! $prop) {
                    continue;
                }
                if (isset($ctx['skipFields'][$code])) {
                    $ctx['excluded'][] = ['kind' => 'field', 'code' => $code, 'label' => $prop['label'] ?? $code];
                    $ctx['seen'][$code] = true;

                    continue;
                }
                // 列の並びはレイアウト側が正しい（fields.json は連想配列で順序が当てにならない）
                $ordered = [];
                foreach ($item['fields'] ?? [] as $col) {
                    $cc = $col['code'] ?? null;
                    if ($cc !== null && isset($prop['fields'][$cc])) {
                        $ordered[$cc] = $prop['fields'][$cc];
                    }
                }
                $ordered += $prop['fields'] ?? [];
                // テーブル内の列も個別に除外できる
                foreach (array_keys($ordered) as $cc) {
                    if (isset($ctx['skipColumns'][$cc])) {
                        $ctx['excluded'][] = ['kind' => 'column', 'code' => $cc, 'label' => ($prop['label'] ?? $code).' › '.($ordered[$cc]['label'] ?? $cc)];
                        unset($ordered[$cc]);
                    }
                }
                $ctx['row']++;
                $this->pushDataField(['fields' => $ordered] + $prop, $code, 1080, $ctx);

                continue;
            }

            if ($type !== 'ROW') {
                continue;
            }

            $ctx['row']++;
            foreach ($item['fields'] ?? [] as $el) {
                $elType = $el['type'] ?? '';
                $width = $this->widthOf($el);

                if ($elType === 'LABEL') {
                    // kintoneのラベルは色・太字・下線つきのHTML。こちらのラベル項目もHTMLを
                    // 持てるようになったので、素のテキストに潰さずそのまま移す
                    // （保存できる形に削るのは FlowRichText の仕事）。
                    $html = FlowRichText::sanitize($el['label'] ?? '');
                    if (FlowRichText::toPlainText($html) !== '' || str_contains($html, '<img')) {
                        $ctx['fields'][] = $this->layoutField('label', $html, $width, $ctx);
                    }

                    continue;
                }
                if ($elType === 'HR') {
                    $ctx['fields'][] = $this->layoutField('divider', '', $width, $ctx);

                    continue;
                }
                if ($elType === 'SPACER') {
                    // elementId が付いたスペースは kintone のカスタムJSの取り付け位置
                    // （custom-space / freee-button など）。こちらに動かすJSは無いので、
                    // 空の灰色枠を並べるだけになる。取り込まない。
                    if (filled($el['elementId'] ?? null)) {
                        $ctx['excluded'][] = ['kind' => 'spacer', 'code' => (string) $el['elementId'], 'label' => 'カスタムJSの取り付け位置'];

                        continue;
                    }
                    $spacer = $this->layoutField('spacer', '', $width, $ctx);
                    $spacer['validation'] = ['height' => (int) ($el['size']['height'] ?? 24)];
                    $ctx['fields'][] = $spacer;

                    continue;
                }

                $code = $el['code'] ?? null;
                $prop = $code !== null ? ($properties[$code] ?? null) : null;
                if ($prop) {
                    if (isset($ctx['skipFields'][$code])) {
                        $ctx['excluded'][] = ['kind' => 'field', 'code' => $code, 'label' => $prop['label'] ?? $code];
                        $ctx['seen'][$code] = true;

                        continue;
                    }
                    $this->pushDataField($prop, $code, $width, $ctx);
                }
            }
        }
    }

    /** データ項目を1つ計画に積む。対応できない型は skipped に回す。 */
    private function pushDataField(array $prop, string $code, ?int $width, array &$ctx, bool $orphan = false): void
    {
        $type = $prop['type'] ?? '';
        $ctx['seen'][$code] = true;

        if (in_array($type, self::SYSTEM_TYPES, true)) {
            return;   // こちらが標準で持っている（レコード番号・作成者など）
        }

        if ($type === 'SUBTABLE') {
            $columns = $this->mapSubtableColumns($prop['fields'] ?? []);
            if (! $columns) {
                $ctx['skipped'][] = ['code' => $code, 'label' => $prop['label'] ?? $code, 'type' => $type, 'reason' => 'テーブル内に取込可能な列がありません。'];

                return;
            }
            $ctx['fields'][] = [
                'key' => $code,
                'label' => $prop['label'] ?? $code,
                'input_type' => 'table',
                'is_required' => false,
                'options' => null,
                'validation' => ['columns' => array_map(fn ($c) => [
                    'key' => $c['key'],
                    'label' => $c['label'],
                    'input_type' => $c['input_type'],
                    'options' => $c['options'] ?: null,
                    'required' => $c['required'],
                ], $columns)],
                'width' => 1080,
                'layout_row' => $ctx['row'],
                'order_number' => $ctx['order']++,
                'kintone_type' => $type,
                'orphan' => $orphan,
            ];

            return;
        }

        $mapped = self::TYPE_MAP[$type] ?? null;
        if ($mapped === null) {
            $ctx['skipped'][] = ['code' => $code, 'label' => $prop['label'] ?? $code, 'type' => $type, 'reason' => $this->unsupportedReason($type)];

            return;
        }

        $options = $this->mapOptions($prop);
        $ctx['fields'][] = [
            'key' => $code,
            'label' => $prop['label'] ?? $code,
            'input_type' => $mapped,
            'is_required' => (bool) ($prop['required'] ?? false),
            'options' => in_array($mapped, ['select', 'radio', 'checkbox'], true) ? ($options ?: ['選択肢1']) : null,
            'validation' => $mapped === 'number' ? $this->numberDisplay($prop) : [],
            'width' => $width ?? 240,
            'layout_row' => $ctx['row'],
            'order_number' => $ctx['order']++,
            'kintone_type' => $type,
            'orphan' => $orphan,
        ];
    }

    /** ラベル・罫線・スペースは値を持たない見た目の部品。キーは重複しない連番で作る。 */
    private function layoutField(string $inputType, string $text, int $width, array &$ctx): array
    {
        return [
            'key' => $inputType.'_'.($ctx['order'] + 1),
            'label' => $text,
            'input_type' => $inputType,
            'is_required' => false,
            'options' => null,
            'validation' => [],
            'width' => $width,
            'layout_row' => $ctx['row'],
            'order_number' => $ctx['order']++,
            'kintone_type' => strtoupper($inputType),
            'orphan' => false,
        ];
    }

    private function widthOf(array $el): int
    {
        $w = (int) ($el['size']['width'] ?? 0);

        return $w > 0 ? max(60, min($w, 1080)) : 240;
    }

    /**
     * Map kintone Process Management (states + actions) → our status-flow shape (structure only; assignees ignored).
     * Statuses are ordered by kintone's `index`; the first is the initial status.
     */
    private function mapStatusFlow(int|string $appId): array
    {
        try {
            $status = $this->kintone->getProcessManagement($appId);
        } catch (\Throwable $e) {
            return ['enable' => false, 'statuses' => [], 'actions' => []];
        }

        $states = $status['states'] ?? [];
        if (! is_array($states) || ! $states) {
            return ['enable' => false, 'statuses' => [], 'actions' => []];
        }

        $statuses = [];
        foreach ($states as $st) {
            $statuses[] = ['name' => $st['name'] ?? '', 'index' => (int) ($st['index'] ?? 0)];
        }
        usort($statuses, fn ($a, $b) => $a['index'] <=> $b['index']);
        foreach ($statuses as $i => &$s) {
            $s['is_initial'] = $i === 0;
        }
        unset($s);

        $actions = [];
        foreach ($status['actions'] ?? [] as $a) {
            $actions[] = ['name' => $a['name'] ?? '', 'from' => $a['from'] ?? '', 'to' => $a['to'] ?? ''];
        }

        return [
            'enable' => (bool) ($status['enable'] ?? false),
            'statuses' => $statuses,
            'actions' => $actions,
        ];
    }

    /** Map each kintone field; GROUP wrappers are flattened into their inner fields. SUBTABLE is skipped whole. */
    private function collectFields(array $properties, array &$out): void
    {
        foreach ($properties as $code => $prop) {
            $type = $prop['type'] ?? '';

            if ($type === 'GROUP') {
                $this->collectFields($prop['fields'] ?? [], $out); // flatten group contents

                continue;
            }
            if (in_array($type, self::SYSTEM_TYPES, true)) {
                continue; // provided natively — don't surface
            }

            if ($type === 'SUBTABLE') {
                $columns = $this->mapSubtableColumns($prop['fields'] ?? []);
                $out[] = [
                    'code' => $prop['code'] ?? (string) $code,
                    'label' => $prop['label'] ?? (string) $code,
                    'kintone_type' => $type,
                    'mapped_type' => $columns ? 'table' : null,
                    'supported' => count($columns) > 0,
                    'required' => false,
                    'options' => [],
                    'columns' => $columns,
                    'note' => $columns ? (count($columns).'列のテーブルとして取込') : 'テーブル内に取込可能な列がありません。',
                ];

                continue;
            }

            $mapped = self::TYPE_MAP[$type] ?? null;
            $out[] = [
                'code' => $prop['code'] ?? (string) $code,
                'label' => $prop['label'] ?? (string) $code,
                'kintone_type' => $type,
                'mapped_type' => $mapped,
                'supported' => $mapped !== null,
                'required' => (bool) ($prop['required'] ?? false),
                'options' => $this->mapOptions($prop),
                'expression' => $type === 'CALC' ? ($prop['expression'] ?? null) : null,
                'note' => $mapped === null ? $this->unsupportedReason($type) : null,
            ];
        }
    }

    /**
     * Second pass: try to port each CALC field's kintone expression into our formula engine.
     * Convertible → import as a `formula` field; otherwise it stays a plain number (with a reason).
     * Runs after collectFields so a formula can reference any other imported field.
     */
    private function convertFormulas(array &$fields): void
    {
        $importable = [];
        $columnOwner = []; // subtable inner-field code => owning table field code (kintone codes are app-unique)
        foreach ($fields as $f) {
            if (($f['mapped_type'] ?? null) !== null) {
                $importable[$f['code']] = true;
            }
            if (($f['mapped_type'] ?? null) === 'table') {
                foreach ($f['columns'] ?? [] as $c) {
                    $columnOwner[$c['key']] = $f['code'];
                }
            }
        }

        foreach ($fields as &$f) {
            if (($f['kintone_type'] ?? null) !== 'CALC') {
                continue;
            }
            $res = $this->formulaConverter->convert($f['expression'] ?? null, $importable, $columnOwner);
            if ($res['ok']) {
                $f['mapped_type'] = 'formula';
                $f['formula'] = $res['formula'];
                $f['result_type'] = 'number';
                $f['supported'] = true;
                $f['formula_status'] = 'ok';
                $f['note'] = '計算式を取り込みます';
            } else {
                $f['formula_status'] = 'fallback';
                $f['formula_reason'] = $res['reason'];
                $f['note'] = '計算式は数値項目として取込（'.$res['reason'].'）';
            }
        }
        unset($f);

        // Subtable CALC columns → per-row formula columns. A subtable calc may reference its
        // own row's sibling columns (bare) or top-level fields; both resolve as [code] at runtime.
        foreach ($fields as &$f) {
            if (($f['mapped_type'] ?? null) !== 'table' || empty($f['columns'])) {
                continue;
            }
            $siblings = [];
            foreach ($f['columns'] as $c) {
                $siblings[$c['key']] = true;
            }
            $scope = $siblings + $importable;
            $converted = 0;
            foreach ($f['columns'] as &$c) {
                $expr = $c['expression'] ?? null;
                unset($c['expression']); // don't leak the raw kintone expression to the client
                if (! $expr) {
                    continue;
                }
                $res = $this->formulaConverter->convert($expr, $scope, []);
                if ($res['ok']) {
                    $c['input_type'] = 'formula';
                    $c['formula'] = $res['formula'];
                    $c['result_type'] = 'number';
                    $converted++;
                }
                // else: column stays a plain number
            }
            unset($c);
            if ($converted > 0) {
                $f['note'] = trim(($f['note'] ? $f['note'].' / ' : '')."計算列 {$converted} を取込");
            }
        }
        unset($f);
    }

    /**
     * Map a kintone SUBTABLE's inner fields → our table columns.
     * Inner fields with an unsupported type (or LABEL layout) are dropped from the table.
     */
    private function mapSubtableColumns(array $innerFields): array
    {
        $columns = [];
        foreach ($innerFields as $code => $prop) {
            $type = $prop['type'] ?? '';
            if (in_array($type, self::SYSTEM_TYPES, true)) {
                continue;
            }
            $mapped = self::TYPE_MAP[$type] ?? null;
            if ($mapped === null || $mapped === 'label') {
                continue; // no sensible column representation
            }
            $columns[] = [
                'key' => $prop['code'] ?? (string) $code,
                'label' => $prop['label'] ?? (string) $code,
                'input_type' => $mapped,
                'options' => $this->mapOptions($prop),
                'required' => (bool) ($prop['required'] ?? false),
                'expression' => $type === 'CALC' ? ($prop['expression'] ?? null) : null,
            ];
        }

        return $columns;
    }

    /**
     * 数値の見せ方（桁区切り・小数桁・単位）。
     *
     * kintoneは項目ごとに持っている設定で、金額とIDを同じ「数値」で扱う以上ここが分かれる。
     * 取り込まないと、取引先ID が「88,745,493」のように量として見えてしまう。
     */
    private function numberDisplay(array $prop): array
    {
        $scale = $prop['displayScale'] ?? '';
        $unit = trim((string) ($prop['unit'] ?? ''));

        return array_filter([
            // kintone の digit は「桁区切りを表示する」。既定（未設定）はこちらの既定に合わせて true。
            'thousand_separator' => filter_var($prop['digit'] ?? true, FILTER_VALIDATE_BOOL),
            'decimals' => $scale === '' || $scale === null ? null : (int) $scale,
            'unit' => $unit !== '' ? $unit : null,
            'unit_position' => $unit !== '' && ($prop['unitPosition'] ?? 'AFTER') === 'BEFORE' ? 'before' : null,
        ], fn ($v) => $v !== null);
    }

    /** kintone options { name: {label, index} } → ordered array of labels. */
    private function mapOptions(array $prop): array
    {
        $options = $prop['options'] ?? null;
        if (! is_array($options) || ! $options) {
            return [];
        }

        $rows = [];
        foreach ($options as $opt) {
            $rows[] = ['label' => $opt['label'] ?? '', 'index' => (int) ($opt['index'] ?? 0)];
        }
        usort($rows, fn ($a, $b) => $a['index'] <=> $b['index']);

        return array_values(array_map(fn ($r) => $r['label'], $rows));
    }

    private function unsupportedReason(string $type): string
    {
        return match ($type) {
            'SUBTABLE' => 'テーブル項目は未対応です。',
            'REFERENCE_TABLE' => '関連レコード一覧は未対応です。',
            'ORGANIZATION_SELECT' => '組織選択は未対応です。',
            'GROUP_SELECT' => 'グループ選択は未対応です。',
            default => 'この項目タイプは未対応です。',
        };
    }
}
