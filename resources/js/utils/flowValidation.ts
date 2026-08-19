import { isLayoutType, isSecretType } from '@/types/flow'
import { emptyFieldValue } from './flowDefaults'
import type { FlowField } from '@/types/flow'

const isEmpty = (v: any): boolean =>
    v === null || v === undefined || v === '' || (Array.isArray(v) && v.length === 0)

/** Returns a Japanese error message for an invalid value, or null when valid. */
export function validateFlowField(field: FlowField, value: any): string | null {
    const rules = field.validation ?? {}

    if (field.is_required && isEmpty(value)) {
        return '必須項目です。'
    }
    if (isEmpty(value)) {
        return null
    }

    switch (field.input_type) {
        case 'short':
        case 'long': {
            const len = String(value).length
            if (rules.min_length != null && len < rules.min_length) return `${rules.min_length}文字以上で入力してください。`
            if (rules.max_length != null && len > rules.max_length) return `${rules.max_length}文字以内で入力してください。`
            if (field.input_type === 'short' && rules.format && rules.format !== 'none') {
                const ok = matchFormat(rules.format, String(value))
                if (!ok) return formatLabel(rules.format) + 'の形式で入力してください。'
            }
            break
        }
        case 'number': {
            const n = Number(value)
            if (Number.isNaN(n)) return '数値で入力してください。'
            if (rules.integer_only && !Number.isInteger(n)) return '整数で入力してください。'
            if (rules.min != null && n < rules.min) return `${rules.min}以上で入力してください。`
            if (rules.max != null && n > rules.max) return `${rules.max}以下で入力してください。`
            break
        }
        case 'checkbox': {
            const count = Array.isArray(value) ? value.length : 0
            if (rules.min_select != null && count < rules.min_select) return `${rules.min_select}個以上選択してください。`
            if (rules.max_select != null && count > rules.max_select) return `${rules.max_select}個以内で選択してください。`
            break
        }
        case 'date':
        case 'datetime':
            if (rules.min_date && value < rules.min_date) return `${rules.min_date} 以降で入力してください。`
            if (rules.max_date && value > rules.max_date) return `${rules.max_date} 以前で入力してください。`
            break
        case 'time':
            if (rules.min_time && value < rules.min_time) return `${rules.min_time} 以降で入力してください。`
            if (rules.max_time && value > rules.max_time) return `${rules.max_time} 以前で入力してください。`
            break
    }

    return null
}

function matchFormat(format: string, value: string): boolean {
    switch (format) {
        case 'email': return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
        case 'tel': return /^[\d\-+()\s]+$/.test(value)
        case 'url': return /^https?:\/\/.+/i.test(value)
        default: return true
    }
}

function formatLabel(format: string): string {
    return ({ email: 'メールアドレス', tel: '電話番号', url: 'URL' } as Record<string, string>)[format] ?? format
}


/**
 * Locked by the server: updateAppRecord() filters writes through editableFieldIdsForRecord(), so a
 * field missing from `ids` cannot be written no matter what the client submits. `ids` of null means
 * the payload didn't resolve it — fall back to the field definition alone rather than locking
 * everything. A record being created has no per-record locks yet.
 */
export const lockedByServer = (f: FlowField, ids: number[] | null | undefined, isNew?: boolean): boolean =>
    !isNew && Array.isArray(ids) && !ids.includes(Number(f.id))

/**
 * Value withheld by the server for lack of 閲覧 (FlowRecordDto.unviewable_field_ids).
 *
 * Such a field must be skipped by BOTH validation and submission, or hiding a value breaks the record:
 * a 必須 field whose value the user cannot see would fail validation and block a save they have no way
 * to fix, and submitting the absent value would write a blank over what is stored.
 */
export const isWithheld = (f: FlowField, unviewable: number[] | null | undefined): boolean =>
    f.id != null && Array.isArray(unviewable) && unviewable.includes(Number(f.id))

/** A field the user could never have typed into, so a save must neither validate nor submit it. */
export const isUnsubmittable = (f: FlowField, ids: number[] | null | undefined, isNew?: boolean): boolean =>
    f.input_type === 'formula' || isLayoutType(f.input_type) || !!f.validation?.disabled
    || lockedByServer(f, ids, isNew)

/**
 * Save-time validation for a whole record. Shared by the record screen and the list's inline row
 * editor so the two can't drift on what counts as valid.
 *
 * `stored` is the record's current server-side values (null when creating): a secret field submitted
 * blank keeps whatever is stored, so 必須 has to ask "will a value exist after this save?" rather
 * than "was one typed?".
 */
export function validateRecordValues(
    fields: FlowField[],
    values: Record<string, any>,
    opts: { editableFieldIds?: number[] | null; isNew?: boolean; stored?: Record<string, any> | null; unviewableFieldIds?: number[] | null } = {},
): Record<string, string | null> {
    const errors: Record<string, string | null> = {}
    for (const f of fields) {
        if (f.hidden || isUnsubmittable(f, opts.editableFieldIds, opts.isNew)) continue
        // no 閲覧 → no value was sent, so there is nothing to judge and 必須 must not fire
        if (isWithheld(f, opts.unviewableFieldIds)) continue
        if (isSecretType(f.input_type)) {
            const v = values[f.id!]
            const clearing = !!(v && typeof v === 'object' && (v as any).clear)
            const incoming = typeof v === 'string' ? v.trim() : ''
            const alreadySet = opts.stored?.[f.id!] === true
            errors[f.id!] = f.is_required && (clearing || (incoming === '' && !alreadySet)) ? '必須項目です。' : null
            continue
        }
        errors[f.id!] = validateFlowField(f, values[f.id!])
    }
    return errors
}

const REQUIRED_MESSAGE = '必須項目です。'
/** Past this many names the list stops being readable and starts being a wall. */
const MAX_LISTED = 6

/**
 * Labels are authored in the app builder, and the ping renders with v-html — so anyone who can name
 * a field could otherwise run script in the browser of everyone who trips that field's validation.
 */
const escapeHtml = (s: string): string =>
    s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c] as string))

/**
 * What to tell the user when validation stops a save, or '' when nothing is wrong.
 *
 * Both save paths already write a message under each bad input, but that is invisible when the field
 * is scrolled off a long form or sits in a column that is off to the side in the list's inline row —
 * so pressing 保存 looked like it did nothing at all. Naming the fields is the part the user can act
 * on. 必須 is separated from the rest because "fill this in" and "this value is wrong" are different
 * jobs, and 必須 is the overwhelmingly common case.
 *
 * Fields are walked in definition order so the names read in the same order as the form.
 */
export function validationSummary(fields: FlowField[], errors: Record<string, string | null>): string {
    const missing: string[] = []
    const invalid: string[] = []

    for (const f of fields) {
        const message = errors[f.id!]
        if (!message) continue
        const label = String(f.label ?? '').trim() || `#${f.id}`
        ;(message === REQUIRED_MESSAGE ? missing : invalid).push(label)
    }

    const list = (names: string[]): string => {
        const shown = names.slice(0, MAX_LISTED).map(escapeHtml).join('、')
        return names.length > MAX_LISTED ? `${shown} ほか${names.length - MAX_LISTED}件` : shown
    }

    const lines: string[] = []
    if (missing.length) lines.push(`必須項目が未入力です：${list(missing)}`)
    if (invalid.length) lines.push(`入力内容を確認してください：${list(invalid)}`)

    return lines.join('<br>')
}

/** The values a save may actually send: everything the user could edit, formulas and locks excluded. */
export function submittableValues(
    fields: FlowField[],
    values: Record<string, any>,
    opts: { editableFieldIds?: number[] | null; isNew?: boolean; unviewableFieldIds?: number[] | null } = {},
): Record<string, any> {
    const payload: Record<string, any> = {}
    for (const f of fields) {
        if (f.hidden || f.input_type === 'formula' || isLayoutType(f.input_type)) continue
        if (lockedByServer(f, opts.editableFieldIds, opts.isNew)) continue
        // never round-trip a value the server withheld: that would blank the stored one
        if (isWithheld(f, opts.unviewableFieldIds)) continue
        payload[f.id!] = values[f.id!]
    }
    return payload
}


/**
 * Lookup field copy (kintone-style): a reference field hands over its picked record's values keyed by
 * source field key, and each mapping fills a destination field *of this record*. Empty `source` means
 * the lookup was cleared, which blanks the destinations.
 *
 * Shared because the destinations are other fields — the record form and the list's inline cells both
 * need to write across the whole record, not just the field that was touched. Formula and layout
 * destinations can't hold a copied value, and a server-locked one is skipped: a lookup must not write
 * what a direct edit isn't allowed to.
 */
export function applyLookupCopy(
    fields: FlowField[],
    values: Record<string, any>,
    errors: Record<string, string | null>,
    payload: { mappings: { from: string; to: string }[]; source: Record<string, any> },
    opts: { editableFieldIds?: number[] | null; isNew?: boolean } = {},
): void {
    const cleared = Object.keys(payload.source).length === 0
    for (const m of payload.mappings) {
        const dest = fields.find((f) => f.key === m.to)
        if (!dest?.id || dest.input_type === 'formula' || isLayoutType(dest.input_type)) continue
        if (lockedByServer(dest, opts.editableFieldIds, opts.isNew)) continue
        values[dest.id] = cleared ? emptyFieldValue(dest) : (payload.source[m.from] ?? emptyFieldValue(dest))
        errors[dest.id] = null
    }
}
