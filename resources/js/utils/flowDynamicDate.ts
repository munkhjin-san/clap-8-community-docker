/**
 * Dynamic date values for Flow filters — 今日 / 今月 / 今年 … instead of a hard-coded date, so a
 * saved view keeps meaning the same thing tomorrow.
 *
 * Stored in a filter's `values` as the sentinel string "@today", "@this_month", … . The "@" prefix
 * cannot collide with a real value: fixed dates arrive as "2026-07-28".
 *
 * Every token resolves to a [start, end] RANGE, not a single date — "今日" on a datetime column has
 * to cover 00:00:00–23:59:59, so one code path serves both `date` and `datetime` fields.
 *
 * ⚠ Mirror of app/Support/FlowDynamicDate.php. Server mode filters in SQL, client mode (apps with
 * record-level permissions) filters here, and the two must agree. Change both together.
 * Weeks start MONDAY on both sides.
 */

export const DYNAMIC_DATE_PREFIX = '@'

/** token => label, in picker order. Keep in step with FlowDynamicDate::TOKENS. */
export const DYNAMIC_DATE_TOKENS: Record<string, string> = {
    today: '今日',
    yesterday: '昨日',
    tomorrow: '明日',
    this_week: '今週',
    last_week: '先週',
    next_week: '来週',
    this_month: '今月',
    last_month: '先月',
    next_month: '来月',
    this_year: '今年',
    last_year: '昨年',
    next_year: '来年',
}

export const isDynamicDate = (v: unknown): v is string =>
    typeof v === 'string' && v.startsWith(DYNAMIC_DATE_PREFIX) && v.slice(1) in DYNAMIC_DATE_TOKENS

export const dynamicDateLabel = (v: string): string => DYNAMIC_DATE_TOKENS[v.slice(1)] ?? v

const pad = (n: number) => String(n).padStart(2, '0')
const ymd = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

/** Monday-start week containing `d` (JS getDay(): 0=Sun). */
const weekRange = (d: Date): [Date, Date] => {
    const start = new Date(d)
    start.setDate(d.getDate() - ((d.getDay() + 6) % 7))
    const end = new Date(start)
    end.setDate(start.getDate() + 6)
    return [start, end]
}

const shiftMonth = (d: Date, delta: number) => new Date(d.getFullYear(), d.getMonth() + delta, 1)

/** Inclusive [start, end] for a token, or null if it isn't one. */
export const resolveDynamicDate = (value: unknown, withTime: boolean): [string, string] | null => {
    if (!isDynamicDate(value)) return null
    const now = new Date()
    const day = (delta: number) => {
        const d = new Date(now)
        d.setDate(now.getDate() + delta)
        return [d, d] as [Date, Date]
    }

    let range: [Date, Date]
    switch (value.slice(1)) {
        case 'today': range = day(0); break
        case 'yesterday': range = day(-1); break
        case 'tomorrow': range = day(1); break
        case 'this_week': range = weekRange(now); break
        case 'last_week': { const d = new Date(now); d.setDate(now.getDate() - 7); range = weekRange(d); break }
        case 'next_week': { const d = new Date(now); d.setDate(now.getDate() + 7); range = weekRange(d); break }
        case 'this_month': range = [shiftMonth(now, 0), new Date(now.getFullYear(), now.getMonth() + 1, 0)]; break
        case 'last_month': range = [shiftMonth(now, -1), new Date(now.getFullYear(), now.getMonth(), 0)]; break
        case 'next_month': range = [shiftMonth(now, 1), new Date(now.getFullYear(), now.getMonth() + 2, 0)]; break
        case 'this_year': range = [new Date(now.getFullYear(), 0, 1), new Date(now.getFullYear(), 11, 31)]; break
        case 'last_year': range = [new Date(now.getFullYear() - 1, 0, 1), new Date(now.getFullYear() - 1, 11, 31)]; break
        case 'next_year': range = [new Date(now.getFullYear() + 1, 0, 1), new Date(now.getFullYear() + 1, 11, 31)]; break
        default: return null
    }

    const [s, e] = range
    return withTime ? [`${ymd(s)} 00:00:00`, `${ymd(e)} 23:59:59`] : [ymd(s), ymd(e)]
}

/**
 * Compare a stored value against a resolved range. A period is a span, so operators read against
 * its edges: 以上 今月 = from the 1st onwards, より大きい 今月 = strictly after the month ends.
 * Mirrors FlowService::applyDynamicDateOp.
 */
export const matchesDynamicDate = (raw: unknown, op: string, range: [string, string]): boolean => {
    const [start, end] = range
    // stored datetimes come back as "YYYY-MM-DDTHH:mm"; normalise so string compare is chronological
    const v = String(raw ?? '').replace('T', ' ').trim()
    if (!v) return op === 'not_equals'
    // compare on the value's own granularity — a date value must not lose to "… 00:00:00"
    const cut = (bound: string) => (v.length <= 10 ? bound.slice(0, 10) : bound.padEnd(19, ' ').slice(0, 19))

    switch (op) {
        case 'not_equals': return v < cut(start) || v > cut(end)
        case 'gt': return v > cut(end)
        case 'gte': return v >= cut(start)
        case 'lt': return v < cut(start)
        case 'lte': return v <= cut(end)
        default: return v >= cut(start) && v <= cut(end)
    }
}
