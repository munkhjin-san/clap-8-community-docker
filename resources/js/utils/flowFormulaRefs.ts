import type { FlowField, TableColumn, FlowAppTool } from '@/types/flow'

/**
 * Builder-side detection of formulas that reference a field/column about to be deleted.
 * Matches both bracketed refs (`[名前]`, `[テーブル.列]`) and bare identifiers (`名前 * 2`),
 * mirroring how FlowFormulaEvaluator resolves them at runtime.
 */

const escapeRe = (s: string) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

/** True when `formula` references any of `names` (whole ref) or `prefixes` (as `prefix.column`). */
export const formulaRefersTo = (
    formula: string | null | undefined,
    names: string[],
    prefixes: string[] = [],
): boolean => {
    const f = (formula ?? '').trim()
    if (!f) return false
    for (const name of names.filter(Boolean)) {
        const n = escapeRe(name.trim())
        // [name] (spaces tolerated) — the evaluator trims inside brackets
        if (new RegExp(`\\[\\s*${n}\\s*\\]`, 'u').test(f)) return true
        // bare identifier with non-identifier boundaries (identifier chars ≒ tokenizer's charset)
        if (new RegExp(`(^|[\\s(),+\\-*/%<>=!\\[])${n}($|[\\s(),+\\-*/%<>=!\\]])`, 'u').test(f)) return true
    }
    for (const prefix of prefixes.filter(Boolean)) {
        if (new RegExp(`\\[\\s*${escapeRe(prefix.trim())}\\s*\\.`, 'u').test(f)) return true
    }
    return false
}

/**
 * Labels of formula fields / table formula columns whose formula references the target.
 * `names` = identifiers the target resolves as (its key + label; for columns also `table.col` forms).
 * `prefixes` = set when deleting a whole table (any `[table.column]` ref counts).
 */
export const referencingFormulas = (
    fields: FlowField[],
    names: string[],
    prefixes: string[] = [],
    exclude?: { fieldKey?: string; columnKey?: string },
): string[] => {
    const hits: string[] = []
    for (const f of fields) {
        if (f.key === exclude?.fieldKey && !exclude?.columnKey) continue // the target itself
        if (f.input_type === 'formula' && formulaRefersTo(f.formula, names, prefixes)) {
            hits.push(f.label || f.key)
        }
        if (f.input_type === 'table') {
            for (const c of (f.validation?.columns ?? []) as TableColumn[]) {
                if (f.key === exclude?.fieldKey && c.key === exclude?.columnKey) continue
                if (c.input_type === 'formula' && formulaRefersTo(c.formula, names, prefixes)) {
                    hits.push(`${f.label || f.key}（${c.label || c.key}）`)
                }
            }
        }
    }
    return [...new Set(hits)]
}

/**
 * Rewrite every reference to `oldName` → `newName` inside one formula, covering the bracket forms
 * the evaluator resolves: bare `[name]`, dotted-prefix `[name.col]`, and dotted-suffix `[table.name]`.
 * Matches whole tokens only, so unrelated fields sharing a substring are untouched.
 */
export const renameInFormula = (
    formula: string | null | undefined,
    oldName: string,
    newName: string,
    opts: { bare?: boolean; dotted?: boolean } = {},
): string => {
    const { bare = true, dotted = true } = opts
    const f = formula ?? ''
    const o = (oldName ?? '').trim()
    const n = (newName ?? '').trim()
    if (!f || !o || o === n) return f
    // A name with bracket/dot chars can't be written as a clean [ref] (dot would parse as
    // table.column). Skip rather than emit a broken formula — such a field is unreferenceable
    // anyway, and the editor will flag the now-stale reference.
    if (/[[\].]/.test(n)) return f
    return f.replace(/\[([^\]]+)\]/g, (m, inner) => {
        const raw = String(inner).trim()
        const dot = raw.indexOf('.')
        if (dot > 0) {
            if (!dotted) return m
            const a = raw.slice(0, dot).trim()
            const b = raw.slice(dot + 1).trim()
            const na = a === o ? newName : a
            const nb = b === o ? newName : b
            return na !== a || nb !== b ? `[${na}.${nb}]` : m
        }
        return bare && raw === o ? `[${newName}]` : m
    })
}

/** Renaming a top-level field's key/label: remap every referencing formula (fields + table columns). */
export const renameFieldRefEverywhere = (fields: FlowField[], oldName: string, newName: string): void => {
    if (!oldName || oldName === newName) return
    for (const f of fields) {
        if (f.input_type === 'formula' && f.formula) f.formula = renameInFormula(f.formula, oldName, newName)
        if (f.input_type === 'table') {
            for (const c of (f.validation?.columns ?? []) as TableColumn[]) {
                if (c.input_type === 'formula' && c.formula) c.formula = renameInFormula(c.formula, oldName, newName)
            }
        }
    }
}

/**
 * Renaming a table column's label: sibling calc columns may reference it bare (`[列名]`). The column
 * KEY is unchanged, so top-level `[table.colKey]` aggregates are unaffected — only bare sibling refs
 * inside this same table need remapping.
 */
export const renameColumnRefInTable = (tableField: FlowField, oldName: string, newName: string): void => {
    if (!oldName || oldName === newName) return
    for (const c of (tableField.validation?.columns ?? []) as TableColumn[]) {
        if (c.input_type === 'formula' && c.formula) c.formula = renameInFormula(c.formula, oldName, newName, { bare: true, dotted: false })
    }
}

/** PDF帳票 tools whose template binds `fieldKey` (as a field element or a 明細 table source). */
export const pdfToolsReferencingField = (tools: FlowAppTool[] | undefined, fieldKey: string): string[] => {
    const hits: string[] = []
    for (const t of tools ?? []) {
        const els = t.config?.elements ?? []
        if (els.some((e) => (e.type === 'field' && e.fieldKey === fieldKey) || (e.type === 'table' && e.sourceFieldKey === fieldKey))) {
            hits.push(t.name || 'PDF帳票')
        }
    }
    return hits
}

/** PDF帳票 tools whose 明細 table (source = `tableKey`) shows the column `colKey`. */
export const pdfToolsReferencingColumn = (tools: FlowAppTool[] | undefined, tableKey: string, colKey: string): string[] => {
    const hits: string[] = []
    for (const t of tools ?? []) {
        const els = t.config?.elements ?? []
        if (els.some((e) => e.type === 'table' && e.sourceFieldKey === tableKey && (e.columns ?? []).some((c) => c.colKey === colKey))) {
            hits.push(t.name || 'PDF帳票')
        }
    }
    return hits
}

/** Confirm message for deleting a field/column referenced by formulas and/or PDF帳票. */
export const referencedDeleteMessage = (target: string, formulaHits: string[], pdfHits: string[] = []): string => {
    const lines = [
        ...formulaHits.map((h) => `・計算式「${h}」`),
        ...pdfHits.map((h) => `・PDF帳票「${h}」`),
    ]
    return `「${target}」は以下で参照されています:\n${lines.join('\n')}\n\n`
        + '削除すると、これらが正しく表示されなくなります（計算式は参照部分を0として計算）。\n削除しますか？'
}
