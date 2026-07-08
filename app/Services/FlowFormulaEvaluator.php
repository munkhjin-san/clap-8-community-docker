<?php

namespace App\Services;

/**
 * Expression evaluator for `formula` fields. Ported from the list-management
 * feature (commit 8b613a40). Standalone — no model/project dependencies.
 * Field references via [key] or bare identifier resolve against $values
 * (keyed by field id / key / label). See FlowService::formulaContext().
 */
class FlowFormulaEvaluator
{
    private array $tokens = [];
    private int $position = 0;
    private array $values = [];

    public function evaluate(?string $formula, array $values): mixed
    {
        $result = $this->evaluateWithError($formula, $values);

        return $result['ok'] ? $result['value'] : null;
    }

    public function evaluateWithError(?string $formula, array $values): array
    {
        $formula = trim((string) $formula);
        if ($formula === '') {
            return ['ok' => false, 'value' => null, 'error' => '計算式を入力してください。'];
        }

        $this->tokens = $this->tokenize($formula);
        $this->position = 0;
        $this->values = $values;

        try {
            $value = $this->parseComparison();

            if ($this->peek()) {
                throw new \RuntimeException('式の後ろに不要な文字があります。');
            }

            return ['ok' => true, 'value' => $value, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'value' => null, 'error' => $e->getMessage() ?: '計算式を確認してください。'];
        }
    }

    public function referencedIdentifiers(?string $formula): array
    {
        $formula = trim((string) $formula);
        if ($formula === '') {
            return [];
        }

        $references = [];
        $tokens = $this->tokenize($formula);

        foreach ($tokens as $index => $token) {
            if (($token['type'] ?? null) !== 'identifier') {
                continue;
            }

            $literal = strtoupper((string) $token['value']);
            if (in_array($literal, ['TRUE', 'FALSE', 'NULL'], true)) {
                continue;
            }

            $next = $tokens[$index + 1] ?? null;
            if (($next['type'] ?? null) === 'operator' && ($next['value'] ?? null) === '(') {
                continue;
            }

            $references[] = (string) $token['value'];
        }

        return array_values(array_unique($references));
    }

    private function tokenize(string $formula): array
    {
        $tokens = [];
        $offset = 0;
        $length = strlen($formula);

        while ($offset < $length) {
            if (preg_match('/\G\s+/u', $formula, $match, 0, $offset)) {
                $offset += strlen($match[0]);
                continue;
            }

            if (preg_match('/\G("(?:\\\\.|[^"])*"|\'(?:\\\\.|[^\'])*\')/u', $formula, $match, 0, $offset)) {
                $tokens[] = ['type' => 'string', 'value' => stripcslashes(substr($match[0], 1, -1))];
                $offset += strlen($match[0]);
                continue;
            }

            if (preg_match('/\G\[([^\]]+)\]/u', $formula, $match, 0, $offset)) {
                $tokens[] = ['type' => 'identifier', 'value' => trim($match[1])];
                $offset += strlen($match[0]);
                continue;
            }

            if (preg_match('/\G\d+(?:\.\d+)?/u', $formula, $match, 0, $offset)) {
                $tokens[] = ['type' => 'number', 'value' => (float) $match[0]];
                $offset += strlen($match[0]);
                continue;
            }

            if (preg_match('/\G(>=|<=|!=|<>|==|=|>|<|\+|-|\*|\/|%|\(|\)|,)/u', $formula, $match, 0, $offset)) {
                $tokens[] = ['type' => 'operator', 'value' => $match[0]];
                $offset += strlen($match[0]);
                continue;
            }

            if (preg_match('/\G[^\s(),+\-*\/%<>=!]+/u', $formula, $match, 0, $offset)) {
                $tokens[] = ['type' => 'identifier', 'value' => trim($match[0])];
                $offset += strlen($match[0]);
                continue;
            }

            $offset++;
        }

        return $tokens;
    }

    private function parseComparison(): mixed
    {
        $left = $this->parseAdditive();

        while ($this->matchOperator(['>', '<', '>=', '<=', '=', '==', '!=', '<>'])) {
            $operator = $this->previous()['value'];
            $right = $this->parseAdditive();
            $left = $this->compare($left, $right, $operator);
        }

        return $left;
    }

    private function parseAdditive(): mixed
    {
        $left = $this->parseMultiplicative();

        while ($this->matchOperator(['+', '-'])) {
            $operator = $this->previous()['value'];
            $right = $this->parseMultiplicative();
            $left = $operator === '+'
                ? $this->toNumber($left) + $this->toNumber($right)
                : $this->toNumber($left) - $this->toNumber($right);
        }

        return $left;
    }

    private function parseMultiplicative(): mixed
    {
        $left = $this->parseUnary();

        while ($this->matchOperator(['*', '/'])) {
            $operator = $this->previous()['value'];
            $right = $this->parseUnary();
            $left = $operator === '*'
                ? $this->toNumber($left) * $this->toNumber($right)
                : ($this->toNumber($right) == 0.0 ? 0 : $this->toNumber($left) / $this->toNumber($right));
        }

        return $left;
    }

    private function parseUnary(): mixed
    {
        if ($this->matchOperator(['-'])) {
            return -1 * $this->toNumber($this->parseUnary());
        }

        return $this->parsePostfix();
    }

    private function parsePostfix(): mixed
    {
        $value = $this->parsePrimary();

        while ($this->matchOperator(['%'])) {
            $value = $this->toNumber($value) / 100;
        }

        return $value;
    }

    private function parsePrimary(): mixed
    {
        if ($this->matchOperator(['('])) {
            $value = $this->parseComparison();
            if (!$this->matchOperator([')'])) {
                throw new \RuntimeException('閉じかっこ ) が不足しています。');
            }
            return $value;
        }

        $token = $this->advance();
        if (!$token) {
            throw new \RuntimeException('式が途中で終わっています。');
        }

        if ($token['type'] === 'number' || $token['type'] === 'string') {
            return $token['value'];
        }

        if ($token['type'] === 'identifier') {
            $literal = strtoupper($token['value']);
            if (in_array($literal, ['TRUE', 'FALSE', 'NULL'], true)) {
                return match ($literal) {
                    'TRUE' => true,
                    'FALSE' => false,
                    default => null,
                };
            }

            if ($this->matchOperator(['('])) {
                $args = [];
                if (!$this->checkOperator(')')) {
                    do {
                        $args[] = $this->parseComparison();
                    } while ($this->matchOperator([',']));
                }
                if (!$this->matchOperator([')'])) {
                    throw new \RuntimeException('関数の閉じかっこ ) が不足しています。');
                }
                return $this->callFunction($token['value'], $args);
            }

            return $this->values[$token['value']] ?? null;
        }

        throw new \RuntimeException('式の形式を確認してください。');
    }

    private function callFunction(string $name, array $args): mixed
    {
        $name = strtoupper($name);

        return match ($name) {
            'IF' => $this->truthy($args[0] ?? null) ? ($args[1] ?? null) : ($args[2] ?? null),
            'CONTAINS' => str_contains($this->toText($args[0] ?? null), $this->toText($args[1] ?? null)),
            'AND' => collect($args)->every(fn ($arg) => $this->truthy($arg)),
            'OR' => collect($args)->contains(fn ($arg) => $this->truthy($arg)),
            'NOT' => !$this->truthy($args[0] ?? null),
            'SUM' => collect($args)->sum(fn ($arg) => $this->toNumber($arg)),
            'ROUND' => round($this->toNumber($args[0] ?? null), (int) ($args[1] ?? 0)),
            'ROUNDUP' => $this->roundUp($this->toNumber($args[0] ?? null), (int) ($args[1] ?? 0)),
            'ROUNDDOWN' => $this->roundDown($this->toNumber($args[0] ?? null), (int) ($args[1] ?? 0)),
            'CEILING' => $this->ceiling($this->toNumber($args[0] ?? null), $this->toNumber($args[1] ?? 1)),
            'FLOOR' => $this->floor($this->toNumber($args[0] ?? null), $this->toNumber($args[1] ?? 1)),
            'ABS' => abs($this->toNumber($args[0] ?? null)),
            'MIN' => count($args) ? min(array_map(fn ($arg) => $this->toNumber($arg), $args)) : 0,
            'MAX' => count($args) ? max(array_map(fn ($arg) => $this->toNumber($arg), $args)) : 0,
            default => throw new \RuntimeException("未対応の関数です: {$name}"),
        };
    }

    private function compare(mixed $left, mixed $right, string $operator): bool
    {
        $numeric = is_numeric($left) && is_numeric($right);
        $a = $numeric ? $this->toNumber($left) : $this->toText($left);
        $b = $numeric ? $this->toNumber($right) : $this->toText($right);

        return match ($operator) {
            '>' => $a > $b,
            '<' => $a < $b,
            '>=' => $a >= $b,
            '<=' => $a <= $b,
            '!=', '<>' => $a != $b,
            default => $a == $b,
        };
    }

    private function truthy(mixed $value): bool
    {
        if (is_array($value)) {
            return count($value) > 0;
        }

        return !($value === null || $value === false || $value === '' || $value === 0 || $value === 0.0 || $value === '0');
    }

    private function toNumber(mixed $value): float
    {
        if (is_array($value)) {
            return (float) collect($value)->sum(fn ($item) => $this->toNumber($item));
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function toText(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => (string) $item, $value));
        }

        return (string) ($value ?? '');
    }

    private function roundUp(float $value, int $precision = 0): float
    {
        $factor = 10 ** $precision;
        if ($factor == 0.0) {
            return 0.0;
        }

        $rounded = $value >= 0 ? ceil($value * $factor) : floor($value * $factor);

        return $rounded / $factor;
    }

    private function roundDown(float $value, int $precision = 0): float
    {
        $factor = 10 ** $precision;
        if ($factor == 0.0) {
            return 0.0;
        }

        $rounded = $value >= 0 ? floor($value * $factor) : ceil($value * $factor);

        return $rounded / $factor;
    }

    private function ceiling(float $value, float $significance = 1): float
    {
        $significance = abs($significance) ?: 1.0;

        return ceil($value / $significance) * $significance;
    }

    private function floor(float $value, float $significance = 1): float
    {
        $significance = abs($significance) ?: 1.0;

        return floor($value / $significance) * $significance;
    }

    private function matchOperator(array $operators): bool
    {
        if (!$this->checkOperator($operators)) {
            return false;
        }

        $this->position++;
        return true;
    }

    private function checkOperator(array|string $operators): bool
    {
        $operators = (array) $operators;
        $token = $this->peek();
        return $token && $token['type'] === 'operator' && in_array($token['value'], $operators, true);
    }

    private function advance(): ?array
    {
        return $this->tokens[$this->position++] ?? null;
    }

    private function peek(): ?array
    {
        return $this->tokens[$this->position] ?? null;
    }

    private function previous(): ?array
    {
        return $this->tokens[$this->position - 1] ?? null;
    }
}
