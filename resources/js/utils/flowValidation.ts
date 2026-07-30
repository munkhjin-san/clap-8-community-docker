import { isLayoutType, isSecretType } from '@/types/flow'
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
    opts: { editableFieldIds?: number[] | null; isNew?: boolean; stored?: Record<string, any> | null } = {},
): Record<string, string | null> {
    const errors: Record<string, string | null> = {}
    for (const f of fields) {
        if (f.hidden || isUnsubmittable(f, opts.editableFieldIds, opts.isNew)) continue
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

/** The values a save may actually send: everything the user could edit, formulas and locks excluded. */
export function submittableValues(
    fields: FlowField[],
    values: Record<string, any>,
    opts: { editableFieldIds?: number[] | null; isNew?: boolean } = {},
): Record<string, any> {
    const payload: Record<string, any> = {}
    for (const f of fields) {
        if (f.hidden || f.input_type === 'formula' || isLayoutType(f.input_type)) continue
        if (lockedByServer(f, opts.editableFieldIds, opts.isNew)) continue
        payload[f.id!] = values[f.id!]
    }
    return payload
}
