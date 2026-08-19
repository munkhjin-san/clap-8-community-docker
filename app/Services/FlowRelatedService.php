<?php

namespace App\Services;

use App\Models\FlowDefinition;
use App\Models\FlowField;
use App\Models\FlowRecord;
use App\Models\FlowRecordValue;
use App\Models\User;

/**
 * 「関連レコード」——このレコードを指している他アプリのレコードを一覧する。
 *
 * kintoneの関連レコード一覧との違いは、結び付けかた。あちらは「this.項目A = related.項目B」という
 * 値の一致で、型が違っても設定できてしまい、項目名を変えると黙って壊れる。
 *
 * こちらは既にある関係をそのまま裏返す：子アプリのルックアップ項目は参照先のレコードIDを
 * value_numeric に持っているので、「そのIDがこのレコードを指している行」を引くだけでよい。
 * 数値の完全一致なので速く、正確で、項目名を変えても壊れない。設定できるのは「どのアプリの
 * どのルックアップ項目か」だけなので、壊れた組み合わせを作れない。
 */
class FlowRelatedService
{
    /** 1レコードにつき返す上限の既定値。 */
    public const DEFAULT_LIMIT = 20;

    public function __construct(private readonly FlowService $flow) {}

    /**
     * 設定が完成しているか。未設定のブロックは画面に「設定してください」と出す。
     */
    public function isConfigured(FlowField $field): bool
    {
        $c = $this->config($field);

        return $c['child_definition_id'] !== null && $c['child_field_id'] !== null;
    }

    /**
     * @return array{
     *   ok: bool, message: ?string, rows: array<int, array<string, mixed>>, columns: array<int, array<string, mixed>>,
     *   total: int, shown: int, child: ?array{id: int, name: string}, aggregates: array<string, mixed>
     * }
     */
    public function listFor(User $user, FlowRecord $record, FlowField $field): array
    {
        $empty = [
            'ok' => false, 'message' => null, 'rows' => [], 'columns' => [],
            'total' => 0, 'shown' => 0, 'child' => null, 'aggregates' => [],
        ];

        $c = $this->config($field);
        if ($c['child_definition_id'] === null || $c['child_field_id'] === null) {
            return ['message' => 'この関連レコードは未設定です。フォーム設定で参照先を選んでください。'] + $empty;
        }

        $child = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets', 'fieldPermissions'])
            ->find($c['child_definition_id']);
        if (! $child) {
            return ['message' => '参照先のアプリが見つかりません。'] + $empty;
        }
        // 子アプリを見られない人には、件数も中身も出さない
        if (! $this->flow->effectiveAppPermissions($user, $child)['view']) {
            return ['message' => 'このアプリを参照する権限がありません。'] + $empty;
        }

        $linkField = $child->fields->firstWhere('id', $c['child_field_id']);
        if (! $linkField || $linkField->input_type !== 'reference') {
            return ['message' => '参照元のルックアップ項目が見つかりません。'] + $empty;
        }

        // 裏返し：子のルックアップ項目が、このレコードのIDを指している行
        $ids = FlowRecordValue::query()
            ->where('flow_field_id', $linkField->id)
            ->where('value_numeric', $record->id)
            ->pluck('flow_record_id');

        if ($ids->isEmpty()) {
            return ['ok' => true, 'child' => ['id' => $child->id, 'name' => $child->name]] + $empty;
        }

        $candidates = FlowRecord::whereIn('id', $ids)
            ->where('flow_definition_id', $child->id)
            ->with('values')
            ->orderByDesc('record_number')
            ->get()
            ->each(fn ($r) => $r->setRelation('definition', $child));

        // 行ごとの権限は子アプリの規則で判断する（見えないレコードは件数にも入れない）
        $visible = $candidates->filter(
            fn ($r) => $this->flow->recordPermissions($user, $r, $child)['view']
        )->values();

        $columns = $this->columns($child, $c['columns']);
        $limit = $c['limit'] > 0 ? $c['limit'] : self::DEFAULT_LIMIT;

        $rows = [];
        foreach ($visible->take($limit) as $rec) {
            $values = $this->flow->recordValues($rec, $child->fields);
            $cells = [];
            foreach ($columns as $col) {
                $cells[(string) $col['id']] = $values[(string) $col['id']] ?? null;
            }
            $rows[] = [
                'id' => $rec->id,
                'record_number' => $rec->record_number,
                'cells' => $cells,
            ];
        }

        return [
            'ok' => true,
            'message' => null,
            'child' => ['id' => $child->id, 'name' => $child->name],
            'link_field_id' => $linkField->id,
            // 子アプリに追加できるかはサーバーが答える（画面側の既定値に頼らない）
            'can_add' => $this->flow->effectiveAppPermissions($user, $child)['add'],
            'columns' => $columns,
            'rows' => $rows,
            'total' => $visible->count(),
            'shown' => count($rows),
            'aggregates' => $this->aggregates($visible, $child, $c['aggregates']),
        ];
    }

    /**
     * 数値項目の合計。kintoneの関連レコード一覧にはこれが無く、合計を見るには別のアプリか
     * 手計算になっていた。集計は表示している上限ではなく、権限を通った全件を対象にする。
     *
     * @param  array<int, int>  $fieldIds
     */
    private function aggregates($records, FlowDefinition $child, array $fieldIds): array
    {
        if ($fieldIds === []) {
            return [];
        }
        $byId = $child->fields->keyBy('id');
        $out = [];

        foreach ($fieldIds as $id) {
            $f = $byId->get($id);
            if (! $f || ! in_array($f->input_type, ['number', 'formula'], true)) {
                continue;
            }
            $sum = 0.0;
            foreach ($records as $rec) {
                $values = $this->flow->recordValues($rec, $child->fields);
                $v = $values[(string) $id] ?? null;
                if (is_numeric($v)) {
                    $sum += (float) $v;
                }
            }
            $out[] = ['id' => (int) $id, 'label' => $f->label, 'sum' => $sum];
        }

        return $out;
    }

    /** 表示する列。未設定なら子アプリの先頭から数項目を出す（空の表を見せない）。 */
    private function columns(FlowDefinition $child, array $ids): array
    {
        $usable = $child->fields
            ->reject(fn ($f) => FlowService::isLayoutType($f->input_type) || FlowService::isSecret($f->input_type))
            ->values();

        $picked = $ids !== []
            ? collect($ids)->map(fn ($id) => $usable->firstWhere('id', (int) $id))->filter()->values()
            : $usable->take(4);

        return $picked->map(fn ($f) => [
            'id' => $f->id,
            'label' => $f->label,
            'input_type' => $f->input_type,
            // 表示の設定（数値の桁区切り・単位など）も渡す。ここだけ素の数字になると、
            // 同じ項目が親と子で違う見え方になってしまう。
            'validation' => is_array($f->validation) ? $f->validation : [],
        ])->all();
    }

    /**
     * @return array{child_definition_id: ?int, child_field_id: ?int, columns: array<int, int>, aggregates: array<int, int>, limit: int}
     */
    private function config(FlowField $field): array
    {
        $v = is_array($field->validation) ? $field->validation : [];

        return [
            'child_definition_id' => isset($v['child_definition_id']) && $v['child_definition_id'] !== '' ? (int) $v['child_definition_id'] : null,
            'child_field_id' => isset($v['child_field_id']) && $v['child_field_id'] !== '' ? (int) $v['child_field_id'] : null,
            'columns' => array_map('intval', (array) ($v['related_columns'] ?? [])),
            'aggregates' => array_map('intval', (array) ($v['related_aggregates'] ?? [])),
            'limit' => (int) ($v['related_limit'] ?? self::DEFAULT_LIMIT),
        ];
    }
}
