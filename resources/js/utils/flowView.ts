import {
    isLayoutType, isSystemColumn,
    FLOW_SYS_RECORD_NUMBER, FLOW_SYS_STATUS, FLOW_SYS_CREATED_AT, FLOW_SYS_UPDATED_AT,
} from '@/types/flow'
import type {
    FlowField, FlowRecordDto, FlowViewApi, FlowViewFilter, FlowViewSort, FlowViewOperator,
} from '@/types/flow'

export interface ResolvedColumn {
    key: string          // unique key for v-for / sort tracking
    system: boolean
    label: string
    field?: FlowField
    ref: number | string // original column ref (field id or sentinel)
}

const SYS_LABEL: Record<string, string> = {
    [FLOW_SYS_RECORD_NUMBER]: 'ID',
    [FLOW_SYS_STATUS]: 'ステータス',
    [FLOW_SYS_CREATED_AT]: '作成日時',
    [FLOW_SYS_UPDATED_AT]: '更新日時',
}

/** Default (null-columns) view = 番号 (+ ステータス) + all non-layout fields + 作成日時 + 更新日時. */
export const allColumnRefs = (fields: FlowField[], hasStatus = false): (number | string)[] => [
    FLOW_SYS_RECORD_NUMBER,
    ...(hasStatus ? [FLOW_SYS_STATUS] : []),
    ...fields.filter((f) => !isLayoutType(f.input_type)).map((f) => f.id!),
    FLOW_SYS_CREATED_AT,
    FLOW_SYS_UPDATED_AT,
]

/** Turn a view's stored column list (or null=all) into renderable descriptors. */
export const resolveColumns = (view: FlowViewApi | null | undefined, fields: FlowField[], hasStatus = false): ResolvedColumn[] => {
    const refs = view?.columns && view.columns.length ? view.columns : allColumnRefs(fields, hasStatus)
    const byId = new Map(fields.map((f) => [f.id!, f]))
    const out: ResolvedColumn[] = []
    for (const ref of refs) {
        if (isSystemColumn(ref)) {
            if (ref === FLOW_SYS_STATUS && !hasStatus) continue // status column only when the flow is enabled
            const label = SYS_LABEL[ref]
            if (label) out.push({ key: ref, system: true, label, ref })
        } else {
            const f = byId.get(Number(ref))
            if (f && !isLayoutType(f.input_type)) out.push({ key: 'f' + f.id, system: false, label: f.label, field: f, ref: f.id! })
        }
    }
    return out
}

export const systemColumnValue = (rec: FlowRecordDto, key: string): any => {
    if (key === FLOW_SYS_RECORD_NUMBER) return rec.record_number
    if (key === FLOW_SYS_STATUS) return rec.current_status
    if (key === FLOW_SYS_CREATED_AT) return rec.created_at
    if (key === FLOW_SYS_UPDATED_AT) return rec.updated_at
    return null
}

const refValue = (rec: FlowRecordDto, ref: number | string): any =>
    isSystemColumn(ref) ? systemColumnValue(rec, ref) : rec.values?.[ref as number]

const isBlank = (v: any) => v == null || v === '' || (Array.isArray(v) && v.length === 0)

const numeric = (v: any): number => {
    if (v == null || v === '') return NaN
    const n = Number(v)
    if (!Number.isNaN(n)) return n
    const t = Date.parse(String(v))
    return Number.isNaN(t) ? NaN : t
}

export const matchesFilter = (rec: FlowRecordDto, f: FlowViewFilter): boolean => {
    const v = refValue(rec, f.field)
    const target = f.values ?? []
    const first = target[0]
    switch (f.operator) {
        case 'is_empty': return isBlank(v)
        case 'not_empty': return !isBlank(v)
        case 'equals': return String(v ?? '') === String(first ?? '')
        case 'not_equals': return String(v ?? '') !== String(first ?? '')
        case 'contains': return String(v ?? '').toLowerCase().includes(String(first ?? '').toLowerCase())
        case 'not_contains': return !String(v ?? '').toLowerCase().includes(String(first ?? '').toLowerCase())
        case 'includes_any': {
            const arr = (Array.isArray(v) ? v : [v]).map(String)
            return target.map(String).some((t) => arr.includes(t))
        }
        case 'gt': return numeric(v) > numeric(first)
        case 'gte': return numeric(v) >= numeric(first)
        case 'lt': return numeric(v) < numeric(first)
        case 'lte': return numeric(v) <= numeric(first)
        default: return true
    }
}

export const applyFilters = (records: FlowRecordDto[], filters?: FlowViewFilter[] | null): FlowRecordDto[] =>
    !filters?.length ? records : records.filter((r) => filters.every((f) => matchesFilter(r, f)))

const compare = (a: any, b: any): number => {
    const an = numeric(a), bn = numeric(b)
    if (!Number.isNaN(an) && !Number.isNaN(bn)) return an === bn ? 0 : an < bn ? -1 : 1
    const as = String(a ?? ''), bs = String(b ?? '')
    return as === bs ? 0 : as < bs ? -1 : 1
}

export const applySort = (records: FlowRecordDto[], sort?: FlowViewSort[] | null): FlowRecordDto[] => {
    if (!sort?.length) return records
    return [...records].sort((a, b) => {
        for (const s of sort) {
            const c = compare(refValue(a, s.field), refValue(b, s.field))
            if (c !== 0) return s.direction === 'desc' ? -c : c
        }
        return 0
    })
}

/** Operators offered per field type in the builder. */
export const operatorsForType = (type: string | undefined): FlowViewOperator[] => {
    switch (type) {
        case 'number':
            return ['equals', 'not_equals', 'gt', 'gte', 'lt', 'lte', 'is_empty', 'not_empty']
        case 'date': case 'datetime': case 'time':
            return ['equals', 'gt', 'gte', 'lt', 'lte', 'is_empty', 'not_empty']
        case 'select': case 'radio':
            return ['equals', 'not_equals', 'includes_any', 'is_empty', 'not_empty']
        case 'checkbox': case 'user': case 'member':
            return ['includes_any', 'is_empty', 'not_empty']
        case 'toggle':
            return ['equals']
        default:
            return ['contains', 'not_contains', 'equals', 'not_equals', 'is_empty', 'not_empty']
    }
}
