/**
 * Client-side slot aggregation.
 *
 * The server computes slot values for the paginated (server) mode, where it knows the whole filtered
 * set. In client mode it defers: the front end holds every visible record and narrows it further with
 * its own search / ad-hoc filter, so only the front end knows what the list is actually showing.
 *
 * ⚠ Mirror of FlowService::computeSlotAggregates — keep the two in step.
 */
import type { FlowField, FlowRecordDto, SlotAggFn } from '@/types/flow'

export interface SlotItemDto {
    source: string
    fn: SlotAggFn
    label: string
    value: number | null
    count: number | null
    computed?: boolean
    prefix?: string
    suffix?: string
}

export interface SlotDto {
    id: number
    name: string
    position: 'top' | 'bottom'
    items: SlotItemDto[]
}

const NUMERIC = ['number', 'formula']

/** Every number a source contributes across `records`. Blanks are skipped, not counted as 0. */
const numbersFor = (records: FlowRecordDto[], source: string, fields: FlowField[]): number[] => {
    const [idPart, colKey] = source.split(':')
    const field = fields.find((f) => String(f.id) === idPart)
    if (!field) return []

    const out: number[] = []
    for (const rec of records) {
        const raw = rec.values?.[field.id as number]
        if (!colKey) {
            if (!NUMERIC.includes(field.input_type)) return []
            if (raw !== null && raw !== undefined && raw !== '' && !Number.isNaN(Number(raw))) out.push(Number(raw))
            continue
        }
        if (field.input_type !== 'table') return []
        for (const row of Array.isArray(raw) ? raw : []) {
            const cell = row?.[colKey]
            if (cell !== null && cell !== undefined && cell !== '' && !Number.isNaN(Number(cell))) out.push(Number(cell))
        }
    }
    return out
}

/** null for an empty set — an average of nothing is not 0, and neither is a max. */
const apply = (fn: SlotAggFn, ns: number[]): number | null => {
    if (!ns.length) return fn === 'sum' ? 0 : null
    switch (fn) {
        case 'avg': return ns.reduce((a, b) => a + b, 0) / ns.length
        case 'max': return Math.max(...ns)
        case 'min': return Math.min(...ns)
        default: return ns.reduce((a, b) => a + b, 0)
    }
}

/** Fill in any item the server left deferred (`computed === false`). */
export const fillSlotValues = (slots: SlotDto[], records: FlowRecordDto[], fields: FlowField[]): SlotDto[] =>
    slots.map((s) => ({
        ...s,
        items: s.items.map((it) => {
            if (it.computed) return it
            const ns = numbersFor(records, it.source, fields)
            return { ...it, value: apply(it.fn, ns), count: ns.length, computed: true }
        }),
    }))

/** Sums/averages keep 2 decimals at most; whole numbers stay whole. */
export const formatSlotValue = (v: number | null): string => {
    if (v === null || v === undefined || Number.isNaN(v)) return '—'
    const rounded = Math.round(v * 100) / 100
    return rounded.toLocaleString('ja-JP', { maximumFractionDigits: 2 })
}

/**
 * The number with its prefix/suffix. An empty result stays a bare「—」: wrapping it would read as
 *「￥—件」, which looks like a value rather than the absence of one.
 */
export const formatSlotItem = (it: Pick<SlotItemDto, 'value' | 'prefix' | 'suffix'>): string => {
    if (it.value === null || it.value === undefined || Number.isNaN(it.value)) return '—'

    return `${it.prefix ?? ''}${formatSlotValue(it.value)}${it.suffix ?? ''}`
}
