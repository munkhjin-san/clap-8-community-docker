<?php

namespace Tests\Unit;

use App\Services\FlowFormulaEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Semantics of the formula expression engine (standalone — no container/DB).
 * Full-pipeline coverage (recordValues, table calc columns) lives in
 * Tests\Feature\FlowFormulaComputeTest.
 */
class FlowFormulaEvaluatorTest extends TestCase
{
    private FlowFormulaEvaluator $ev;

    protected function setUp(): void
    {
        $this->ev = new FlowFormulaEvaluator;
    }

    /** @return array<string, array{0: string, 1: array, 2: mixed}> */
    public static function expressionProvider(): array
    {
        $rows = [['qty' => '5', 'price' => '200'], ['qty' => '2', 'price' => '100'], ['qty' => null, 'price' => 'abc']];

        return [
            // arithmetic & precedence
            'precedence 2+3*4' => ['2+3*4', [], 14.0],
            'parens (2+3)*4' => ['(2+3)*4', [], 20.0],
            'unary minus 2*-3' => ['2*-3', [], -6.0],
            'double unary -(-5)' => ['-(-5)', [], 5.0],
            'division 10/4' => ['10/4', [], 2.5],
            'division by zero guarded' => ['10/0', [], 0],
            'division by zero via fields' => ['[a]/[b]', ['a' => 10, 'b' => 0], 0],
            'percent postfix' => ['50%', [], 0.5],

            // comparisons (single = is equality, kintone-style)
            'single = equality' => ['IF([a]=5,1,0)', ['a' => 5], 1.0],
            'numeric-string compares numerically' => ['IF("10">"9",1,0)', [], 1.0],
            'text equality' => ['IF("abc"="abc",1,0)', [], 1.0],
            '<> not equal' => ['IF([a]<>3,1,0)', ['a' => 4], 1.0],
            'null equals empty string' => ['IF([a]="",1,0)', ['a' => null], 1.0],
            'value not empty' => ['IF([a]="",1,0)', ['a' => 7], 0.0],

            // functions
            'nested IF' => ['IF([n]>10,"big",IF([n]>5,"mid","small"))', ['n' => 7], 'mid'],
            'CONTAINS partial match' => ['IF(CONTAINS([s],"対象外"),1,0)', ['s' => '賞与対象外です'], 1.0],
            'CONTAINS over checkbox array' => ['IF(CONTAINS([tags],"B"),1,0)', ['tags' => ['A', 'B', 'C']], 1.0],
            'CONTAINS miss' => ['IF(CONTAINS([tags],"Z"),1,0)', ['tags' => ['A', 'B']], 0.0],
            'AND all truthy' => ['IF(AND(1,2,"x"),1,0)', [], 1.0],
            'AND one falsy' => ['IF(AND(1,0),1,0)', [], 0.0],
            'OR one truthy' => ['IF(OR(0,"",3),1,0)', [], 1.0],
            'NOT' => ['IF(NOT(0),1,0)', [], 1.0],
            'ROUND half away from zero' => ['ROUND(2.5,0)', [], 3.0],
            'ROUND precision' => ['ROUND(3.14159,2)', [], 3.14],
            'ROUNDUP away from zero (negative)' => ['ROUNDUP(-1.41,1)', [], -1.5],
            'ROUNDDOWN toward zero (negative)' => ['ROUNDDOWN(-1.49,1)', [], -1.4],
            'CEILING to significance' => ['CEILING(7,3)', [], 9.0],
            'FLOOR to significance' => ['FLOOR(7,3)', [], 6.0],
            'ABS' => ['ABS(0-42)', [], 42.0],
            'MIN scalars' => ['MIN(5,2,8)', [], 2.0],
            'MAX scalars' => ['MAX(5,2,8)', [], 8.0],

            // subtable column references ([table.column] resolves to the column's values)
            'SUM over table column' => ['SUM([t.qty])', ['t' => $rows], 7.0],
            'SUM over two table columns' => ['SUM([t.qty],[t.price])', ['t' => $rows], 307.0],
            'SUM mixing scalar and column' => ['SUM(100,[t.qty])', ['t' => $rows], 107.0],
            'MIN over column (non-numeric → 0)' => ['MIN([t.price])', ['t' => $rows], 0.0],
            'MAX over column' => ['MAX([t.price])', ['t' => $rows], 200.0],
            'SUM over empty table' => ['SUM([empty.col])', ['empty' => []], 0.0],
            'SUM over missing table' => ['SUM([nope.col])', [], 0.0],
            'column ref in arithmetic sums' => ['[t.qty]*2', ['t' => $rows], 14.0],

            // identifier resolution
            'unicode bare identifier' => ['売上*2', ['売上' => 21], 42.0],
            'bracketed identifier trims spaces' => ['[ 売上 ]*2', ['売上' => 21], 42.0],
            'missing identifier is 0 in arithmetic' => ['[ghost]+5', [], 5.0],
            'TRUE literal' => ['IF(TRUE,1,0)', [], 1.0],

            // error paths return null, never throw
            'unknown function' => ['FOO(1)', [], null],
            'unbalanced paren' => ['(1+2', [], null],
            'empty formula' => ['', [], null],
            'garbage' => ['***', [], null],
        ];
    }

    #[DataProvider('expressionProvider')]
    public function test_expression(string $formula, array $values, mixed $expected): void
    {
        $actual = $this->ev->evaluate($formula, $values);

        if (is_float($expected) || is_int($expected)) {
            $this->assertIsNumeric($actual, "formula: {$formula}");
            $this->assertEqualsWithDelta((float) $expected, (float) $actual, 1e-9, "formula: {$formula}");
        } else {
            $this->assertSame($expected, $actual, "formula: {$formula}");
        }
    }

    public function test_supported_functions_matches_call_function(): void
    {
        foreach ($this->ev->supportedFunctions() as $fn) {
            $result = $this->ev->evaluateWithError("{$fn}(1,1,1)", []);
            $this->assertTrue($result['ok'], "{$fn} is advertised but not callable: {$result['error']}");
        }
    }

    public function test_referenced_identifiers(): void
    {
        $this->assertSame(
            ['売上', '原価', 't.col'],
            $this->ev->referencedIdentifiers('IF([売上]>0, [売上]-[原価], SUM([t.col]))'),
        );
    }

    public function test_missing_references(): void
    {
        $known = ['売上' => 1, 't' => []];

        // resolvable: known field, known-table column ref, literals, function names
        $this->assertSame([], $this->ev->missingReferences('IF(TRUE,[売上]+SUM([t.col]),0)', $known));

        // a deleted/typo'd field and an unknown table are reported
        $this->assertSame(
            ['原価', 'ghost.col'],
            $this->ev->missingReferences('[売上]-[原価]+SUM([ghost.col])', $known),
        );
    }
}
