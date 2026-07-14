// Optical (center-of-visual-mass) centering for letter avatars.
//
// Geometric bounding-box centering makes bottom-heavy glyphs (A, kanji, the g
// bowl) read as sitting low — the "mathematical vs visual center" problem. This
// instead measures each run's alpha-weighted vertical centroid on an offscreen
// canvas (the browser equivalent of sampling ink density) and returns the offset
// of that centroid from the text's alphabetic baseline, in the same units as
// `size`. Callers place their SVG text baseline at `targetY - offset` so the
// visual mass lands on `targetY`.
//
// Results are cached per (text, size, fontFamily, fontWeight) and shared across
// every avatar on the page.

const centroidCache = new Map<string, number>()

export function centroidBaselineOffset(
    text: string,
    size: number,
    fontFamily: string,
    fontWeight: number | string = 400,
): number {
    if (!text) return 0
    const key = `${text}|${size}|${fontFamily}|${fontWeight}`
    const cached = centroidCache.get(key)
    if (cached !== undefined) return cached

    const glyphCount = Array.from(text).length
    const w = Math.ceil(size * (glyphCount + 2))
    const h = Math.ceil(size * 2.2)
    const canvas = document.createElement('canvas')
    canvas.width = w
    canvas.height = h
    const ctx = canvas.getContext('2d', { willReadFrequently: true })
    if (!ctx) return 0

    ctx.font = `${fontWeight} ${size}px ${fontFamily}`
    ctx.textAlign = 'center'
    ctx.textBaseline = 'alphabetic'
    const baselineY = size * 1.6
    ctx.fillStyle = '#000'
    ctx.fillText(text, w / 2, baselineY)

    const data = ctx.getImageData(0, 0, w, h).data
    let sum = 0
    let weighted = 0
    for (let y = 0; y < h; y++) {
        let rowAlpha = 0
        const rowStart = y * w * 4
        for (let x = 0; x < w; x++) {
            rowAlpha += data[rowStart + x * 4 + 3]
        }
        if (rowAlpha) {
            sum += rowAlpha
            weighted += rowAlpha * y
        }
    }
    if (!sum) return 0

    // Offset of the visual centroid from the baseline (negative = above it).
    const offset = weighted / sum - baselineY
    centroidCache.set(key, offset)
    return offset
}
