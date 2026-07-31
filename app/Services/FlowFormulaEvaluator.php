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

    /**
     * References in $formula that don't resolve against $known (context keys — field ids/keys/labels).
     * A `table.column` ref counts as known when the table itself is known (column existence isn't
     * derivable from context keys alone). Used by the builder to flag deleted/typo'd fields —
     * at runtime these resolve to null and compute as 0 rather than erroring.
     */
    public function missingReferences(?string $formula, array $known): array
    {
        $missing = [];
        foreach ($this->referencedIdentifiers($formula) as $ref) {
            $base = str_contains($ref, '.') ? explode('.', $ref, 2)[0] : $ref;
            if (! array_key_exists($ref, $known) && ! array_key_exists($base, $known)) {
                $missing[] = $ref;
            }
        }

        return $missing;
    }

    /** Function names this evaluator can execute (see callFunction). */
    public function supportedFunctions(): array
    {
        return [
            'IF', 'CONTAINS', 'AND', 'OR', 'NOT', 'SUM',
            'ROUND', 'ROUNDUP', 'ROUNDDOWN', 'CEILING', 'FLOOR', 'ABS', 'MIN', 'MAX',
            // text
            'LEN', 'LEFT', 'RIGHT', 'MID', 'TRIM', 'REMOVESPACE', 'UPPER', 'LOWER', 'REPLACE', 'JOIN',
            // arrays (subtable columns, multi-select fields)
            'COUNT', 'COUNTA',
            // dates
            'TODAY', 'YEAR', 'MONTH', 'DAY', 'DATEDIF',
        ];
    }

    public function tokenize(string $formula): array
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

            if (preg_match('/\G(>=|<=|!=|<>|==|=|>|<|\+|-|\*|\/|%|&|\(|\)|,)/u', $formula, $match, 0, $offset)) {
                $tokens[] = ['type' => 'operator', 'value' => $match[0]];
                $offset += strlen($match[0]);

                continue;
            }

            if (preg_match('/\G[^\s(),+\-*\/%<>=!&]+/u', $formula, $match, 0, $offset)) {
                $tokens[] = ['type' => 'identifier', 'value' => trim($match[0])];
                $offset += strlen($match[0]);

                continue;
            }

            $offset++;
        }

        return $tokens;
    }

    /**
     * `&` joins text. Deliberately a separate operator rather than teaching `+` to concatenate: an
     * empty field is non-numeric, so an overloaded `+` would turn [数量] + [単価] into "5" the moment
     * one of them was blank. Same reason Excel and kintone keep `&` apart.
     */
    private function parseConcat(): mixed
    {
        $left = $this->parseAdditive();

        while ($this->matchOperator(['&'])) {
            $left = $this->toText($left).$this->toText($this->parseAdditive());
        }

        return $left;
    }

    private function parseComparison(): mixed
    {
        $left = $this->parseConcat();

        while ($this->matchOperator(['>', '<', '>=', '<=', '=', '==', '!=', '<>'])) {
            $operator = $this->previous()['value'];
            $right = $this->parseConcat();
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
            if (! $this->matchOperator([')'])) {
                throw new \RuntimeException('閉じかっこ ) が不足しています。');
            }

            return $value;
        }

        $token = $this->advance();
        if (! $token) {
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
                if (! $this->checkOperator(')')) {
                    do {
                        $args[] = $this->parseComparison();
                    } while ($this->matchOperator([',']));
                }
                if (! $this->matchOperator([')'])) {
                    throw new \RuntimeException('関数の閉じかっこ ) が不足しています。');
                }

                return $this->callFunction($token['value'], $args);
            }

            return $this->resolveIdentifier((string) $token['value']);
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
            'NOT' => ! $this->truthy($args[0] ?? null),
            // SUM/MIN/MAX flatten array args, so a subtable-column reference (which resolves
            // to an array of that column's per-row values) aggregates like kintone's SUM(col).
            'SUM' => collect($this->flattenNumbers($args))->sum(),
            'ROUND' => round($this->toNumber($args[0] ?? null), (int) ($args[1] ?? 0)),
            'ROUNDUP' => $this->roundUp($this->toNumber($args[0] ?? null), (int) ($args[1] ?? 0)),
            'ROUNDDOWN' => $this->roundDown($this->toNumber($args[0] ?? null), (int) ($args[1] ?? 0)),
            'CEILING' => $this->ceiling($this->toNumber($args[0] ?? null), $this->toNumber($args[1] ?? 1)),
            'FLOOR' => $this->floor($this->toNumber($args[0] ?? null), $this->toNumber($args[1] ?? 1)),
            'ABS' => abs($this->toNumber($args[0] ?? null)),
            'MIN' => ($nums = $this->flattenNumbers($args)) ? min($nums) : 0,
            'MAX' => ($nums = $this->flattenNumbers($args)) ? max($nums) : 0,

            // ---- text. All multibyte-aware: these run on Japanese values, where strlen/substr
            // would count and cut bytes and hand back broken characters. ----
            'LEN' => mb_strlen($this->toText($args[0] ?? null)),
            'LEFT' => mb_substr($this->toText($args[0] ?? null), 0, max(0, (int) $this->toNumber($args[1] ?? 0))),
            'RIGHT' => ($n = max(0, (int) $this->toNumber($args[1] ?? 0))) === 0
                ? ''
                : mb_substr($this->toText($args[0] ?? null), -$n),
            // 1-based start, like a spreadsheet — MID(text, 1, 3) takes the first three characters
            'MID' => mb_substr(
                $this->toText($args[0] ?? null),
                max(0, (int) $this->toNumber($args[1] ?? 1) - 1),
                max(0, (int) $this->toNumber($args[2] ?? 0))
            ),
            'TRIM' => $this->trimText($this->toText($args[0] ?? null)),
            // Every kind of blank, not just the one you can type: the full-width space is invisible in
            // the formula editor, so REPLACE(v, "　", "") is a guess about what you actually typed —
            // and tabs and newlines (from pasted values) survive it either way.
            'REMOVESPACE' => preg_replace('/[\s\x{3000}]+/u', '', $this->toText($args[0] ?? null)) ?? '',
            'UPPER' => mb_strtoupper($this->toText($args[0] ?? null)),
            'LOWER' => mb_strtolower($this->toText($args[0] ?? null)),
            // by value, not by position: REPLACE(text, 探す文字列, 置き換える文字列). Positional
            // replacement is what a spreadsheet calls REPLACE, but "swap this text for that" is the
            // thing people actually want here, and MID covers the positional case.
            'REPLACE' => str_replace(
                $this->toText($args[1] ?? null),
                $this->toText($args[2] ?? null),
                $this->toText($args[0] ?? null)
            ),
            // the separator is the LAST argument, so JOIN([担当], "、") reads naturally
            'JOIN' => implode(
                count($args) > 1 ? $this->toText(array_pop($args)) : ', ',
                array_map(fn ($v) => $this->toText($v), $this->flattenValues($args))
            ),

            // ---- arrays: a subtable column reference resolves to that column's per-row values ----
            'COUNT' => count(array_filter($this->flattenValues($args), fn ($v) => is_numeric($v))),
            'COUNTA' => count(array_filter(
                $this->flattenValues($args),
                fn ($v) => ! ($v === null || $v === '' || (is_array($v) && ! $v))
            )),

            // ---- dates. Values arrive as the stored strings: 'Y-m-d' for 日付,
            // 'Y-m-d\TH:i' for 日時. Everything is Asia/Tokyo (config('app.timezone')). ----
            'TODAY' => now()->toDateString(),
            'YEAR' => (int) ($this->toDate($args[0] ?? null)?->year ?? 0),
            'MONTH' => (int) ($this->toDate($args[0] ?? null)?->month ?? 0),
            'DAY' => (int) ($this->toDate($args[0] ?? null)?->day ?? 0),
            'DATEDIF' => $this->dateDiff($args[0] ?? null, $args[1] ?? null, $this->toText($args[2] ?? 'D')),

            default => throw new \RuntimeException("未対応の関数です: {$name}"),
        };
    }

    /**
     * Trim and collapse whitespace, full-width spaces included — pasted values are full of them.
     *
     * Regex rather than trim(): trim()'s charlist strips BYTES, so handing it U+3000's three bytes
     * (E3 80 80) also ate the leading byte of any adjacent kana or kanji and left broken UTF-8 —
     * '  ご確認　の　ほど  ' came back as an empty string.
     */
    private function trimText(string $text): string
    {
        $text = preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $text) ?? '';

        return preg_replace('/[\s\x{3000}]+/u', ' ', $text) ?? '';
    }

    /** Flatten args (arrays = subtable columns / multi-select fields) one level, values untouched. */
    private function flattenValues(array $args): array
    {
        $out = [];
        foreach ($args as $arg) {
            foreach (is_array($arg) ? $arg : [$arg] as $v) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /** Parse a date/日時 value. Returns null for anything unparseable, so callers can fall back. */
    private function toDate(mixed $value): ?\Illuminate\Support\Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return \Illuminate\Support\Carbon::instance($value);
        }
        $text = trim($this->toText($value));
        if ($text === '') {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($text);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Whole units from $start to $end — negative when $end is earlier. 'D' days (default), 'M'
     * months, 'Y' years. Returns 0 when either side can't be read as a date, which keeps a formula
     * on a half-filled record showing a number rather than an error.
     */
    private function dateDiff(mixed $start, mixed $end, string $unit): int
    {
        $a = $this->toDate($start);
        $b = $this->toDate($end);
        if (! $a || ! $b) {
            return 0;
        }

        return match (strtoupper(trim($unit)) ?: 'D') {
            'Y' => (int) $a->diffInYears($b, false),
            'M' => (int) $a->diffInMonths($b, false),
            default => (int) $a->startOfDay()->diffInDays($b->startOfDay(), false),
        };
    }

    /** Flatten args (arrays = subtable columns) into a flat list of numbers. */
    private function flattenNumbers(array $args): array
    {
        $out = [];
        foreach ($args as $arg) {
            foreach (is_array($arg) ? $arg : [$arg] as $v) {
                $out[] = $this->toNumber($v);
            }
        }

        return $out;
    }

    /** Resolve a bare field reference, or a `table.column` reference to that column's values. */
    private function resolveIdentifier(string $name): mixed
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }
        if (str_contains($name, '.')) {
            [$table, $column] = explode('.', $name, 2);
            $rows = $this->values[$table] ?? null;
            if (is_array($rows)) {
                return array_values(array_map(fn ($r) => is_array($r) ? ($r[$column] ?? null) : null, $rows));
            }
        }

        return null;
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

        return ! ($value === null || $value === false || $value === '' || $value === 0 || $value === 0.0 || $value === '0');
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
        if (! $this->checkOperator($operators)) {
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
