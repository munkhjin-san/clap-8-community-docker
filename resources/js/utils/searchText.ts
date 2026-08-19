/**
 * Text folding for type-to-search over Japanese data.
 *
 * Project names are written with halfwidth katakana by house rule — 65 of 122 at the time of writing
 * — so a raw substring match could never find ﾃﾙｳｪﾙ from "テルウェル", which is what anyone actually
 * types. Better than half the list was effectively unsearchable by its readable name.
 *
 * NFKC is the whole job for width. It maps halfwidth katakana to fullwidth *and* composes the
 * trailing voiced marks, which is the part a hand-rolled character map gets wrong: ﾊﾞ is ﾊ followed
 * by a standalone ﾞ, and only composition turns that into the single character バ. It also folds
 * fullwidth latin and digits, so ＮＴＴ finds NTT.
 *
 * Hiragana folds to katakana on top of that, because kana is usually typed before it is converted —
 * てるうぇる should find the same row as テルウェル.
 */

/** ぁ…ゖ. Stops at ゖ (U+3096): ゛゜ and the iteration marks just above it are not letters. */
const HIRAGANA_BLOCK = /[ぁ-ゖ]/g
const TO_KATAKANA = 0x60

/**
 * Reduce a string to the form both sides of a search comparison are compared in.
 *
 * Not trimmed on purpose: trimming the middle of a haystack would join words that are not adjacent.
 * Callers trim the query themselves, as they did before this existed.
 */
export const searchKey = (v: unknown): string =>
    String(v ?? '')
        .normalize('NFKC')
        .replace(HIRAGANA_BLOCK, (c) => String.fromCharCode(c.charCodeAt(0) + TO_KATAKANA))
        .toLowerCase()

/**
 * True when `needle` occurs in `haystack` with both folded. An empty needle matches everything.
 *
 * Fold the needle once with searchKey() and compare directly when looping a list — this helper is for
 * one-off checks.
 */
export const searchMatches = (haystack: unknown, needle: string): boolean => {
    const q = searchKey(needle.trim())

    return !q || searchKey(haystack).includes(q)
}
