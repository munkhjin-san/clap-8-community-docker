<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 200 200"
        :width="`${px}px`"
        :height="`${px}px`"
        :style="{ minWidth: `${px}px`, minHeight: `${px}px` }"
        class="rounded-full boardDefaultIcon"
    >
        <circle cx="100" cy="100" r="100" :fill="bgColor" />
        <text
            v-for="(run, i) in runs"
            :key="i"
            x="100"
            :y="measured ? (baselines[i] ?? run.y) : run.y"
            text-anchor="middle"
            :dominant-baseline="measured ? 'auto' : 'central'"
            :font-size="run.size"
            :fill="textColor"
            :font-family="fontFamily"
            font-weight="400"
        >{{ run.text }}</text>
    </svg>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { centroidBaselineOffset } from '@/composables/glyphCentering'

const props = defineProps<{
    /** Raw board name / icon text. Whitespace is stripped, matching the old PHP renderer. */
    text?: string | null
    /** Background color (3- or 6-digit hex). */
    bg?: string | null
    /** Rendered pixel size. The SVG is vector, so it stays crisp at any size / DPR. */
    size?: string | number
}>()

const px = computed(() => Number(props.size ?? 45))
// Colors may arrive with or without a leading '#'; normalise for SVG fill.
const bgColor = computed(() => {
    const raw = props.bg || '#000'
    return raw.startsWith('#') ? raw : `#${raw}`
})

// Strip regular + full-width whitespace (mirrors preg_replace('/\s+/', '') on the server).
const chars = computed(() => Array.from((props.text ?? '').replace(/[\s　]/g, '')))
const sub = (start: number, count: number) => chars.value.slice(start, start + count).join('')

// Text layout: 1-3 chars on one row, 4-6 across two rows (this replaced the old
// server-side board_default_thumbnail renderer). viewBox is 0..200, so x is 100.
const runs = computed(() => {
    const len = chars.value.length
    switch (len) {
        case 0: return []
        case 1: return [{ text: sub(0, 1), size: 100, y: 100 }]
        case 2: return [{ text: sub(0, 2), size: 80, y: 100 }]
        case 3: return [{ text: sub(0, 3), size: 60, y: 100 }]
        case 4: return [{ text: sub(0, 2), size: 60, y: 70 }, { text: sub(2, 2), size: 60, y: 130 }]
        case 5: return [{ text: sub(0, 3), size: 50, y: 75 }, { text: sub(3, 2), size: 50, y: 135 }]
        default: return [{ text: sub(0, 3), size: 50, y: 70 }, { text: sub(3, 3), size: 50, y: 130 }]
    }
})

// Latin + CJK use the shared "Noto Sans JP" (same family the old PHP renderer used).
// Cyrillic / Mongolian names fall back to the system sans-serif, which covers them at
// normal weight (no regular-weight Noto with Cyrillic is bundled).
const isCyrillic = computed(() => /[А-Яа-яЁёөү]/u.test(sub(0, 3)))
const fontFamily = computed(() => (isCyrillic.value ? 'sans-serif' : "'Noto Sans JP', sans-serif"))

// Luminance-based contrast color (black text on light backgrounds, white on dark).
const textColor = computed(() => {
    let hex = (bgColor.value || '').replace('#', '')
    if (hex.length === 3) hex = hex.split('').map((c) => c + c).join('')
    if (!/^[0-9a-fA-F]{6}$/.test(hex)) return '#FFFFFF'
    const r = parseInt(hex.slice(0, 2), 16)
    const g = parseInt(hex.slice(2, 4), 16)
    const b = parseInt(hex.slice(4, 6), 16)
    const luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255
    return luminance > 0.5 ? '#000000' : '#FFFFFF'
})

// Center each run on its center of visual mass (see glyphCentering.ts) so
// bottom-heavy glyphs don't read as sitting low.
const baselines = ref<number[]>([])
const measured = ref(false)

const computeBaselines = () => {
    baselines.value = runs.value.map((run) => run.y - centroidBaselineOffset(run.text, run.size, fontFamily.value))
    measured.value = true
}

const measure = async () => {
    // Center on the correct glyph shapes, so wait for the webfont before measuring.
    try {
        const fonts = (document as any).fonts
        // Only the Noto webfont needs loading; the Cyrillic fallback is a system font.
        if (fonts?.load && !isCyrillic.value) {
            await fonts.load('400 100px "Noto Sans JP"', chars.value.join(''))
        }
    } catch { /* fall back to the font that is available */ }
    computeBaselines()
}

onMounted(measure)
// After the first load the font is cached, so re-measuring on prop changes is synchronous.
watch([runs, fontFamily], () => { measured.value ? computeBaselines() : measure() })
</script>

<style scoped>
.boardDefaultIcon {
    display: block;
    border-radius: 50%;
}
</style>
