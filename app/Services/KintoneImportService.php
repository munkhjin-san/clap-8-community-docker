<?php

namespace App\Services;

use App\Infrastructure\Kintone\KintoneClient;

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
        'MULTI_LINE_TEXT'  => 'long',
        'RICH_TEXT'        => 'long',
        'NUMBER'           => 'number',
        'CALC'             => 'number',   // kintone formula isn't portable → import the field as a plain number
        'LINK'             => 'short',
        'RADIO_BUTTON'     => 'radio',
        'DROP_DOWN'        => 'select',
        'CHECK_BOX'        => 'checkbox',
        'MULTI_SELECT'     => 'checkbox',
        'DATE'             => 'date',
        'TIME'             => 'time',
        'DATETIME'         => 'datetime',
        'USER_SELECT'      => 'user',
        'FILE'             => 'file',
        'LABEL'            => 'label',
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
        $skipped = array_values(array_filter($fields, fn ($f) => !$f['supported']));

        return [
            'app' => [
                'id' => $app['appId'] ?? (string) $appId,
                'name' => $app['name'] ?? ('App ' . $appId),
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
        if (!is_array($states) || !$states) {
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
                    'note' => $columns ? (count($columns) . '列のテーブルとして取込') : 'テーブル内に取込可能な列がありません。',
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
                $f['note'] = '計算式は数値項目として取込（' . $res['reason'] . '）';
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
                if (!$expr) {
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
                $f['note'] = trim(($f['note'] ? $f['note'] . ' / ' : '') . "計算列 {$converted} を取込");
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

    /** kintone options { name: {label, index} } → ordered array of labels. */
    private function mapOptions(array $prop): array
    {
        $options = $prop['options'] ?? null;
        if (!is_array($options) || !$options) return [];

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
