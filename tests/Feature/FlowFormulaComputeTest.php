<?php

namespace Tests\Feature;

use App\Models\FlowField;
use App\Models\FlowRecord;
use App\Models\FlowRecordValue;
use App\Services\FlowService;
use Tests\TestCase;

/**
 * Full formula compute pipeline (FlowService::recordValues) with fabricated in-memory
 * models — no DB. Covers: top-level formula chains in any declaration order, per-row
 * table calc columns (incl. intra-row chains), aggregates over calc columns, cross-level
 * references (row calc → top-level aggregate of another table), circular-reference
 * termination, result_type casting, and label/layout shadowing.
 */
class FlowFormulaComputeTest extends TestCase
{
    /**
     * Build fields + one record from a compact spec, run recordValues, return values by key.
     * spec: key => [type, label?, formula?, result_type?, columns?, value?]
     */
    private function compute(array $spec): array
    {
        $fields = collect();
        $values = collect();
        $id = 0;
        foreach ($spec as $key => $s) {
            $id++;
            $f = new FlowField([
                'key' => $key,
                'label' => $s['label'] ?? $key,
                'input_type' => $s['type'],
                'formula' => $s['formula'] ?? null,
                'result_type' => $s['result_type'] ?? null,
                'validation' => isset($s['columns']) ? ['columns' => $s['columns']] : [],
            ]);
            $f->id = $id;
            $fields->push($f);

            if (array_key_exists('value', $s)) {
                $v = new FlowRecordValue;
                $v->flow_field_id = $id;
                match ($s['type']) {
                    'number' => $v->value_numeric = $s['value'],
                    'toggle' => $v->value_boolean = $s['value'],
                    'date' => $v->value_date = $s['value'],
                    'checkbox', 'table', 'user' => $v->value_json = $s['value'],
                    default => $v->value_text = $s['value'],
                };
                $values->push($v);
            }
        }
        $record = new FlowRecord;
        $record->setRelation('values', $values);

        $out = app(FlowService::class)->recordValues($record, $fields);

        $byKey = [];
        foreach ($fields as $f) {
            $byKey[$f->key] = $out[(string) $f->id] ?? null;
        }

        return $byKey;
    }

    public function test_top_level_chain_resolves_regardless_of_declaration_order(): void
    {
        $r = $this->compute([
            'f3' => ['type' => 'formula', 'formula' => '[f2]-[f1]'],
            'f2' => ['type' => 'formula', 'formula' => '[f1]*2'],
            'f1' => ['type' => 'formula', 'formula' => '[base]+1'],
            'base' => ['type' => 'number', 'value' => 9],
        ]);
        $this->assertSame(10.0, $r['f1']);
        $this->assertSame(20.0, $r['f2']);
        $this->assertSame(10.0, $r['f3']);
    }

    public function test_aggregate_over_table_calc_column_and_dependent_ratio(): void
    {
        $r = $this->compute([
            'total' => ['type' => 'formula', 'formula' => 'SUM([items.amount])'],
            'ratio' => ['type' => 'formula', 'formula' => 'IF([total]<>0,[fee]/[total]*100,0)'],
            'fee' => ['type' => 'number', 'value' => 150],
            'items' => ['type' => 'table',
                'columns' => [
                    ['key' => 'qty', 'label' => '数量', 'input_type' => 'number'],
                    ['key' => 'price', 'label' => '単価', 'input_type' => 'number'],
                    ['key' => 'amount', 'label' => '金額', 'input_type' => 'formula', 'formula' => '[qty]*[price]', 'result_type' => 'number'],
                ],
                'value' => [['qty' => '5', 'price' => '200'], ['qty' => '3', 'price' => '100']],
            ],
        ]);
        $this->assertSame([1000.0, 300.0], array_column($r['items'], 'amount'));
        $this->assertSame(1300.0, $r['total']);
        $this->assertEqualsWithDelta(150 / 1300 * 100, $r['ratio'], 1e-9);
    }

    public function test_intra_row_calc_chain_declared_out_of_order(): void
    {
        $r = $this->compute([
            'items' => ['type' => 'table',
                'columns' => [
                    ['key' => 'base', 'label' => 'base', 'input_type' => 'number'],
                    ['key' => 'c3', 'label' => 'c3', 'input_type' => 'formula', 'formula' => '[c2]+1', 'result_type' => 'number'],
                    ['key' => 'c2', 'label' => 'c2', 'input_type' => 'formula', 'formula' => '[c1]*10', 'result_type' => 'number'],
                    ['key' => 'c1', 'label' => 'c1', 'input_type' => 'formula', 'formula' => '[base]+1', 'result_type' => 'number'],
                ],
                'value' => [['base' => '1'], ['base' => '2']],
            ],
            'total' => ['type' => 'formula', 'formula' => 'SUM([items.c3])'],
        ]);
        $this->assertSame(21.0, $r['items'][0]['c3']); // (1+1)*10+1
        $this->assertSame(31.0, $r['items'][1]['c3']);
        $this->assertSame(52.0, $r['total']);
    }

    public function test_row_calc_can_reference_top_level_aggregate_of_another_table(): void
    {
        $r = $this->compute([
            'salesTotal' => ['type' => 'formula', 'formula' => 'SUM([sales.amount])'],
            'sales' => ['type' => 'table',
                'columns' => [
                    ['key' => 'q', 'label' => 'q', 'input_type' => 'number'],
                    ['key' => 'p', 'label' => 'p', 'input_type' => 'number'],
                    ['key' => 'amount', 'label' => 'amount', 'input_type' => 'formula', 'formula' => '[q]*[p]', 'result_type' => 'number'],
                ],
                'value' => [['q' => '10', 'p' => '50']],
            ],
            'costs' => ['type' => 'table',
                'columns' => [
                    ['key' => 'rate', 'label' => 'rate', 'input_type' => 'number'],
                    ['key' => 'share', 'label' => 'share', 'input_type' => 'formula', 'formula' => '[salesTotal]*[rate]/100', 'result_type' => 'number'],
                ],
                'value' => [['rate' => '20'], ['rate' => '30']],
            ],
            'costTotal' => ['type' => 'formula', 'formula' => 'SUM([costs.share])'],
        ]);
        $this->assertSame([100.0, 150.0], array_column($r['costs'], 'share'));
        $this->assertSame(250.0, $r['costTotal']);
    }

    public function test_circular_reference_terminates(): void
    {
        $r = $this->compute([
            'a' => ['type' => 'formula', 'formula' => '[b]+1'],
            'b' => ['type' => 'formula', 'formula' => '[a]+1'],
        ]);
        $this->assertIsNumeric($r['a']);
        $this->assertIsNumeric($r['b']);
    }

    public function test_self_referencing_row_column_terminates(): void
    {
        $r = $this->compute([
            'items' => ['type' => 'table',
                'columns' => [
                    ['key' => 'c', 'label' => 'c', 'input_type' => 'formula', 'formula' => '[c]+1', 'result_type' => 'number'],
                ],
                'value' => [['x' => 1]],
            ],
        ]);
        $this->assertIsNumeric($r['items'][0]['c']);
    }

    public function test_result_type_casting(): void
    {
        $r = $this->compute([
            'grade' => ['type' => 'formula', 'formula' => 'IF([n]>100,"高","低")', 'result_type' => 'text'],
            'flag' => ['type' => 'formula', 'formula' => '[n]>100', 'result_type' => 'toggle'],
            'n' => ['type' => 'number', 'value' => 150],
        ]);
        $this->assertSame('高', $r['grade']);
        $this->assertTrue($r['flag']);
    }

    public function test_toggle_and_checkbox_inputs_in_formulas(): void
    {
        $r = $this->compute([
            'bonus' => ['type' => 'formula', 'formula' => 'IF([active],1000,0)'],
            'excluded' => ['type' => 'formula', 'formula' => 'IF(CONTAINS([tags],"対象外"),0,[salary]*0.1)'],
            'active' => ['type' => 'toggle', 'value' => true],
            'tags' => ['type' => 'checkbox', 'value' => ['正社員', '対象外']],
            'salary' => ['type' => 'number', 'value' => 3000],
        ]);
        $this->assertSame(1000.0, $r['bonus']);
        $this->assertSame(0.0, $r['excluded']);
    }

    public function test_references_resolve_by_label_top_level_and_in_rows(): void
    {
        $r = $this->compute([
            'amount' => ['type' => 'number', 'label' => '売上高', 'value' => 400],
            'doubled' => ['type' => 'formula', 'formula' => '[売上高]*2'],
            'items' => ['type' => 'table',
                'columns' => [
                    ['key' => 'k1', 'label' => '数量', 'input_type' => 'number'],
                    ['key' => 'k2', 'label' => '単価', 'input_type' => 'number'],
                    ['key' => 'k3', 'label' => '金額', 'input_type' => 'formula', 'formula' => '[数量]*[単価]', 'result_type' => 'number'],
                ],
                'value' => [['k1' => '3', 'k2' => '7']],
            ],
        ]);
        $this->assertSame(800.0, $r['doubled']);
        $this->assertSame(21.0, $r['items'][0]['k3']);
    }

    public function test_layout_part_sharing_a_data_field_label_does_not_shadow_it(): void
    {
        // The builder only enforces label uniqueness among DATA fields — a 見出し/ラベル may
        // legally repeat a data field's label and must not clobber its context entry.
        $r = $this->compute([
            '売上' => ['type' => 'number', 'label' => '売上', 'value' => 10],
            'hdr' => ['type' => 'label', 'label' => '売上'],
            'calc' => ['type' => 'formula', 'formula' => '[売上]*2'],
        ]);
        $this->assertSame(20.0, $r['calc']);
    }

    public function test_row_cell_wins_over_same_key_top_level_field(): void
    {
        $r = $this->compute([
            'qty' => ['type' => 'number', 'value' => 999],
            'items' => ['type' => 'table',
                'columns' => [
                    ['key' => 'qty', 'label' => 'qty', 'input_type' => 'number'],
                    ['key' => 'x2', 'label' => 'x2', 'input_type' => 'formula', 'formula' => '[qty]*2', 'result_type' => 'number'],
                ],
                'value' => [['qty' => '3']],
            ],
        ]);
        $this->assertSame(6.0, $r['items'][0]['x2']);
    }

    public function test_broken_formula_casts_to_zero_without_poisoning_siblings(): void
    {
        $r = $this->compute([
            'broken' => ['type' => 'formula', 'formula' => '((('],
            'okay' => ['type' => 'formula', 'formula' => '[n]*2'],
            'n' => ['type' => 'number', 'value' => 4],
        ]);
        $this->assertSame(0, $r['broken']);
        $this->assertSame(8.0, $r['okay']);
    }

    public function test_empty_table_and_missing_values_compute_to_zero(): void
    {
        $r = $this->compute([
            'total' => ['type' => 'formula', 'formula' => 'SUM([items.amount])+[n]'],
            'items' => ['type' => 'table',
                'columns' => [
                    ['key' => 'q', 'label' => 'q', 'input_type' => 'number'],
                    ['key' => 'amount', 'label' => 'a', 'input_type' => 'formula', 'formula' => '[q]*2', 'result_type' => 'number'],
                ],
                'value' => [],
            ],
            'n' => ['type' => 'number'], // never stored
        ]);
        $this->assertSame(0.0, $r['total']);
    }

    public function test_kintone_style_composite_scenario(): void
    {
        // Mirrors the 損益計画 patterns: aggregates over calc columns feeding
        // profit → nested IF/CONTAINS/ROUNDDOWN bonus computation.
        $spec = [
            '通常利益' => ['type' => 'formula', 'formula' => '[売上高合計]-[原価合計]'],
            '業績連動賞与' => ['type' => 'formula', 'formula' => 'IF(CONTAINS([積立対象],"対象外"),0,IF([通常利益]>0,ROUNDDOWN([通常利益]*0.1,0),0))'],
            '売上高合計' => ['type' => 'formula', 'formula' => 'SUM([売上.金額])'],
            '原価合計' => ['type' => 'formula', 'formula' => 'SUM([原価.金額],[固定費])'],
            '固定費' => ['type' => 'number', 'value' => 111],
            '積立対象' => ['type' => 'checkbox', 'value' => ['対象']],
            '売上' => ['type' => 'table',
                'columns' => [
                    ['key' => '数量', 'label' => '数量', 'input_type' => 'number'],
                    ['key' => '単価', 'label' => '単価', 'input_type' => 'number'],
                    ['key' => '金額', 'label' => '金額', 'input_type' => 'formula', 'formula' => '[数量]*[単価]', 'result_type' => 'number'],
                ],
                'value' => [['数量' => '10', '単価' => '100'], ['数量' => '5', '単価' => '30']],
            ],
            '原価' => ['type' => 'table',
                'columns' => [['key' => '金額', 'label' => '金額', 'input_type' => 'number']],
                'value' => [['金額' => '400']],
            ],
        ];
        $r = $this->compute($spec);
        $this->assertSame(1150.0, $r['売上高合計']);
        $this->assertSame(511.0, $r['原価合計']);
        $this->assertSame(639.0, $r['通常利益']);
        $this->assertSame(63.0, $r['業績連動賞与']);

        // flipping the checkbox to 対象外 gates the bonus to 0
        $spec['積立対象']['value'] = ['対象外'];
        $this->assertSame(0.0, $this->compute($spec)['業績連動賞与']);
    }

    public function test_deep_chain_converges_within_pass_bound(): void
    {
        // 12 formulas declared worst-case (each depends on the NEXT declared one).
        $spec = ['f12' => ['type' => 'formula', 'formula' => '[f11]+1']];
        for ($i = 11; $i >= 2; $i--) {
            $spec["f{$i}"] = ['type' => 'formula', 'formula' => '[f'.($i - 1).']+1'];
        }
        $spec['f1'] = ['type' => 'formula', 'formula' => '[seed]+1'];
        $spec['seed'] = ['type' => 'number', 'value' => 0];

        $this->assertSame(12.0, $this->compute($spec)['f12']);
    }

    public function test_text_row_calc_chained_into_numeric_row_calc(): void
    {
        $r = $this->compute([
            'items' => ['type' => 'table',
                'columns' => [
                    ['key' => 'n', 'label' => 'n', 'input_type' => 'number'],
                    ['key' => 'grade', 'label' => 'grade', 'input_type' => 'formula', 'formula' => 'IF([n]>=60,"合格","不合格")', 'result_type' => 'text'],
                    ['key' => 'passed', 'label' => 'passed', 'input_type' => 'formula', 'formula' => 'IF([grade]="合格",1,0)', 'result_type' => 'number'],
                ],
                'value' => [['n' => '75'], ['n' => '40']],
            ],
            'passCount' => ['type' => 'formula', 'formula' => 'SUM([items.passed])'],
        ]);
        $this->assertSame(['合格', '不合格'], array_column($r['items'], 'grade'));
        $this->assertSame(1.0, $r['passCount']);
    }
}
