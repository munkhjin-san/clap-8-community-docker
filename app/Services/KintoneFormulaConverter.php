<?php

namespace App\Services;

/**
 * Deterministically converts a kintone CALC field's `expression` into a formula our
 * FlowFormulaEvaluator can run. The two grammars are near-identical (same functions,
 * operators, and infix arithmetic), so conversion is: re-tokenise, wrap every field
 * reference as [code] (our evaluator resolves by key/label), keep supported functions,
 * and prove the result actually parses/evaluates before accepting it.
 *
 * kintone references fields by CODE; our imported fields keep that code as their key,
 * so [code] resolves at runtime. Anything we can't represent (unsupported function,
 * string concat, or a reference to a field that isn't being imported) is reported as a
 * reason and the caller falls back to a plain number field.
 */
class KintoneFormulaConverter
{
    public function __construct(private FlowFormulaEvaluator $evaluator) {}

    /**
     * @param  string  $expression        kintone CALC expression
     * @param  array<string,bool>  $importableCodes  set of top-level field codes being imported (code => true)
     * @param  array<string,string>  $columnOwner  subtable inner-field code => owning table field key
     * @return array{ok: bool, formula: ?string, reason: ?string}
     */
    public function convert(?string $expression, array $importableCodes, array $columnOwner = []): array
    {
        $expr = trim((string) $expression);
        if ($expr === '') {
            return $this->fail('計算式が空です。');
        }
        // kintone string concatenation has no equivalent in our engine.
        if (str_contains($expr, '&')) {
            return $this->fail('文字列結合（&）は未対応です。');
        }

        $tokens = $this->evaluator->tokenize($expr);
        if (!$tokens) {
            return $this->fail('計算式を解析できませんでした。');
        }

        $supported = array_flip($this->evaluator->supportedFunctions());
        $literals = ['TRUE' => true, 'FALSE' => true, 'NULL' => true];

        $out = '';
        $referencedKeys = [];
        $tableRefs = [];
        $unknownFns = [];
        $missingRefs = [];

        foreach ($tokens as $i => $tok) {
            $type = $tok['type'];
            $val = $tok['value'];

            if ($type === 'number') {
                $out .= $this->numberLiteral((float) $val);
                continue;
            }
            if ($type === 'string') {
                $out .= '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $val) . '"';
                continue;
            }
            if ($type === 'operator') {
                $out .= $val;
                continue;
            }

            // identifier: either a function call, a literal, or a field reference
            $upper = strtoupper((string) $val);
            $isCall = (($tokens[$i + 1]['type'] ?? null) === 'operator') && (($tokens[$i + 1]['value'] ?? null) === '(');

            if ($isCall) {
                if (!isset($supported[$upper])) {
                    $unknownFns[] = (string) $val;
                }
                $out .= $upper;
                continue;
            }
            if (isset($literals[$upper])) {
                $out .= $upper;
                continue;
            }

            // field reference
            $code = (string) $val;
            if (isset($importableCodes[$code])) {
                $referencedKeys[] = $code;
                $out .= '[' . $code . ']';
            } elseif (isset($columnOwner[$code])) {
                // subtable column → aggregate reference [table.column] (SUM/MIN/MAX flatten it)
                $table = $columnOwner[$code];
                $tableRefs[$table] = true;
                $out .= '[' . $table . '.' . $code . ']';
            } else {
                $missingRefs[] = $code;
                $out .= '[' . $code . ']';
            }
        }

        if ($unknownFns) {
            return $this->fail('未対応の関数: ' . implode(', ', array_unique($unknownFns)));
        }
        if ($missingRefs) {
            return $this->fail('取込対象外の項目を参照: ' . implode(', ', array_unique($missingRefs)));
        }

        // prove it runs against our engine (dummy values: scalars=0, subtable refs=empty rows)
        $context = [];
        foreach (array_unique($referencedKeys) as $k) {
            $context[$k] = 0;
        }
        foreach (array_keys($tableRefs) as $t) {
            $context[$t] = [];
        }
        $check = $this->evaluator->evaluateWithError($out, $context);
        if (!$check['ok']) {
            return $this->fail($check['error'] ?? '計算式を検証できませんでした。');
        }

        return ['ok' => true, 'formula' => $out, 'reason' => null];
    }

    private function fail(string $reason): array
    {
        return ['ok' => false, 'formula' => null, 'reason' => $reason];
    }

    private function numberLiteral(float $value): string
    {
        if ($value === floor($value) && abs($value) < 1e15) {
            return (string) (int) $value;
        }
        return rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');
    }
}
