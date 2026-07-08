/**
 * Pick a readable text color (black or white) for a solid background color.
 * Uses the YIQ perceived-brightness formula (threshold 128) — the practical
 * standard for this, which keeps white text on saturated mid-tones (blue,
 * red, purple) and black on light tones (yellow, cyan, lime).
 * Falsy / non-hex input (e.g. a CSS var fallback like `var(--primary-button)`)
 * returns white.
 */
export function readableTextColor(hex?: string | null): string {
    if (!hex) return '#fff'
    let h = hex.trim().replace(/^#/, '')
    if (h.length === 3) h = h.split('').map((c) => c + c).join('')
    if (!/^[0-9a-fA-F]{6}$/.test(h)) return '#fff'

    const r = parseInt(h.slice(0, 2), 16)
    const g = parseInt(h.slice(2, 4), 16)
    const b = parseInt(h.slice(4, 6), 16)
    const yiq = (r * 299 + g * 587 + b * 114) / 1000
    return yiq >= 128 ? '#000' : '#fff'
}
