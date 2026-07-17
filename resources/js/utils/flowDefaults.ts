import type { FlowField } from '@/types/flow'

/** Local now, formatted for the native date/time inputs. */
export const flowNow = () => {
    const d = new Date()
    const pad = (n: number) => String(n).padStart(2, '0')
    const date = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
    const time = `${pad(d.getHours())}:${pad(d.getMinutes())}`
    return { date, time, datetime: `${date}T${time}` }
}

/** Initial value for a field on record create (honours builder-configured defaults). */
export const resolveFieldDefault = (f: FlowField, meId?: number | null): any => {
    const v = f.validation || {}
    switch (f.input_type) {
        case 'checkbox':
            return Array.isArray(v.default) ? [...v.default] : []
        case 'user':
        case 'member':
            if (Array.isArray(v.default) && v.default.length) return [...v.default]
            return v.default_me && meId ? [meId] : []
        case 'file':
        case 'table':
            return []
        case 'reference':
            return null
        case 'toggle':
            return !!v.default
        case 'number':
            return v.default === undefined || v.default === null || v.default === '' ? null : v.default
        case 'date':
            return v.default_now ? flowNow().date : (v.default ?? '')
        case 'datetime':
            return v.default_now ? flowNow().datetime : (v.default ?? '')
        case 'time':
            return v.default_now ? flowNow().time : (v.default ?? '')
        case 'select':
        case 'radio':
            return v.default ?? null
        case 'short':
        case 'long':
            return v.default ?? ''
        default:
            return ''
    }
}
