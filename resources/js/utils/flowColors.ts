import colors from 'assets/colors.json'

export interface FlowColor { light: string; dark: string; id: number; name: string }

export const FLOW_COLORS = colors as FlowColor[]

/** Resolve an app's accent color. Falls back to a stable color derived from `seed` (e.g. the app id). */
export const flowColor = (colorId: number | null | undefined, seed = 0): FlowColor => {
    if (colorId != null) {
        const found = FLOW_COLORS.find((c) => c.id === colorId)
        if (found) return found
    }
    return FLOW_COLORS[Math.abs(seed) % FLOW_COLORS.length]
}

/** The themed hex for an app's accent (light or dark variant). */
export const flowColorValue = (colorId: number | null | undefined, dark: boolean, seed = 0): string => {
    const c = flowColor(colorId, seed)
    return dark ? c.dark : c.light
}
