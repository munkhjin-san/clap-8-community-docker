<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 200 200"
        :width="`${px}px`"
        :height="`${px}px`"
        :style="{ minWidth: `${px}px`, minHeight: `${px}px` }"
        class="rounded-full userDefaultIcon"
    >
        <circle cx="100" cy="100" r="100" :fill="bgColor" />
        <text
            v-if="glyph"
            x="100"
            :y="measured ? baseline : 100"
            text-anchor="middle"
            :dominant-baseline="measured ? 'auto' : 'central'"
            fill="#ffffff"
            :style="{ fontSize: '130px', fontWeight: 600, fontFamily }"
        >{{ glyph }}</text>
    </svg>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { centroidBaselineOffset } from '@/composables/glyphCentering'

const props = defineProps<{
    /** User / contact name — the first character is used, matching the old PHP renderer. */
    name?: string | null
    /** Background color (3- or 6-digit hex, with or without leading '#'). */
    bg?: string | null
    /** Rendered pixel size. Vector SVG, so it stays crisp at any size / DPR. */
    size?: string | number
}>()

const px = computed(() => Number(props.size ?? 30))

// Call sites pass colors both with and without a leading '#'; normalise for SVG.
const bgColor = computed(() => {
    const raw = props.bg || '#000'
    return raw.startsWith('#') ? raw : `#${raw}`
})

// First character of the name, uppercased (mirrors name.charAt(0).toUpperCase()).
const glyph = computed(() => (Array.from(props.name ?? '')[0] ?? '').toUpperCase())

// Latin + CJK use the shared "Noto Sans JP"; Cyrillic / Mongolian fall back to the
// system sans-serif. Matches BoardDefaultIcon.
const isCyrillic = computed(() => /[А-Яа-яЁёөү]/u.test(glyph.value))
const fontFamily = computed(() => (isCyrillic.value ? 'sans-serif' : "'Noto Sans JP', sans-serif"))

// Center on the glyph's visual mass (see glyphCentering.ts).
const baseline = ref(100)
const measured = ref(false)

const computeBaseline = () => {
    baseline.value = 100 - centroidBaselineOffset(glyph.value, 130, fontFamily.value, 600)
    measured.value = true
}

const measure = async () => {
    try {
        const fonts = (document as any).fonts
        if (fonts?.load && !isCyrillic.value) {
            await fonts.load('600 100px "Noto Sans JP"', glyph.value)
        }
    } catch { /* fall back to the available font */ }
    computeBaseline()
}

onMounted(measure)
watch([glyph, fontFamily], () => { measured.value ? computeBaseline() : measure() })
</script>

<style scoped>
.userDefaultIcon {
    display: block;
    border-radius: 50%;
}
</style>
