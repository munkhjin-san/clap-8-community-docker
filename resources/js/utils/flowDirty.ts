import { isLayoutType, isSecretType } from '@/types/flow'
import type { FlowField } from '@/types/flow'

/**
 * A comparable string for a record's editable state, for "are there unsaved changes?".
 *
 * Deliberately not JSON.stringify(values):
 *
 *  - `values` keeps keys from a previously-viewed app (the record screen is reused across
 *    navigations and only ever writes the current app's field ids), so the raw object carries
 *    ids that have nothing to do with this record. Fields are walked from the definition instead,
 *    sorted by id so key order can never shift the result.
 *  - formula and layout fields are skipped — they hold no user input and are never submitted.
 *  - a secret needs normalising. Its value arrives as a boolean meaning "one is stored" and
 *    FlowFieldInput rewrites that to '' ("keep") the moment it mounts editable, with no user
 *    involved. Comparing raw would call a pristine form dirty depending on tick order. So the
 *    marker and the keep-instruction both collapse to 'keep', while a typed value or an explicit
 *    clear register as changes — which is the real question for a credential.
 */
export const recordFingerprint = (fields: FlowField[], values: Record<string, any>): string => {
    const parts: string[] = []
    const ordered = fields.filter((f) => f.id != null).slice().sort((a, b) => (a.id! - b.id!))

    for (const f of ordered) {
        if (f.input_type === 'formula' || isLayoutType(f.input_type)) continue
        const v = values[f.id!]

        if (isSecretType(f.input_type)) {
            const state = v && typeof v === 'object' && (v as any).clear
                ? 'clear'
                : (typeof v === 'string' && v.trim() !== '' ? 'set' : 'keep')
            parts.push(`${f.id}:${state}`)
            continue
        }
        parts.push(`${f.id}:${JSON.stringify(v ?? null)}`)
    }

    return parts.join('|')
}


/**
 * Client-only keys that ride along inside the builder's payload but say nothing about whether there
 * is unsaved work. `uid` is stamped onto fields by FlowFormTab for :key and selection tracking, and
 * it appears only once that tab has rendered — so a baseline taken at load time would lack it and
 * every app would look edited the moment its form tab opened.
 */
const BUILDER_TRANSIENT_KEYS = ['uid']

/** A comparable string for the app builder's saveable state (pass buildPayload()'s result). */
export const builderFingerprint = (payload: unknown): string =>
    JSON.stringify(payload, (key, value) => (BUILDER_TRANSIENT_KEYS.includes(key) ? undefined : value))
