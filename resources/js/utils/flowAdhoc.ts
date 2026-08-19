import type { FlowAdhocFilter } from '@/types/flow'

// ?f= carries the ad-hoc filter as base64url of a compact array form
// (["and", [field, op, ...values], …]) — raw JSON in the URL reads as a wall of %22
export const encodeAdhoc = (f: FlowAdhocFilter): string => {
    const compact = [f.logic, ...f.conditions.map((c) => [c.field, c.operator, ...(c.values ?? [])])]
    const bytes = new TextEncoder().encode(JSON.stringify(compact))
    return btoa(String.fromCharCode(...bytes)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
}
export const decodeAdhoc = (s: string): FlowAdhocFilter | null => {
    try {
        // legacy links carried raw JSON — keep reading them
        const json = /^[{[]/.test(s) ? s : new TextDecoder().decode(
            Uint8Array.from(atob(s.replace(/-/g, '+').replace(/_/g, '/')), (ch) => ch.charCodeAt(0)),
        )
        const parsed = JSON.parse(json)
        if (Array.isArray(parsed)) {
            const [logic, ...conds] = parsed
            return {
                logic: logic === 'or' ? 'or' : 'and',
                conditions: conds.filter(Array.isArray).map((c: any[]) => ({ field: c[0], operator: c[1], values: c.slice(2) })),
            }
        }
        if (parsed && Array.isArray(parsed.conditions)) {
            return { logic: parsed.logic === 'or' ? 'or' : 'and', conditions: parsed.conditions }
        }
    } catch { /* malformed ?f= — start unfiltered */ }
    return null
}
