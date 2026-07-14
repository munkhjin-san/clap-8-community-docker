<template>
    <div class="fai" :style="boxStyle">
        <img v-if="iconImage" :src="iconImage" class="fai-img" alt="">
        <span v-else-if="iconSvg" class="fai-svg" v-html="iconSvg"></span>
        <!-- default icon: same client-side SVG technique as the board / user default icons
             (optically centered glyph, webfont-aware, contrast text) over the app's accent BG -->
        <svg
            v-else
            class="fai-default"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 200 200"
            :width="`${size}px`"
            :height="`${size}px`"
        >
            <text
                v-if="initial"
                x="100"
                :y="measured ? baseline : 100"
                text-anchor="middle"
                :dominant-baseline="measured ? 'auto' : 'central'"
                :fill="textColor"
                :style="{ fontSize: '100px', fontWeight: 500, fontFamily }"
            >{{ initial }}</text>
        </svg>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useTheme } from '@/store/theme'
import { flowColorValue } from '@/utils/flowColors'
import { centroidBaselineOffset } from '@/composables/glyphCentering'

const props = withDefaults(defineProps<{
    iconSvg?: string | null
    iconImage?: string | null
    colorId?: number | null
    name?: string
    seed?: number
    size?: number
    round?: boolean
}>(), { size: 44, round: false })

const theme = useTheme()
const initial = computed(() => (props.name?.trim()?.[0] ?? '?'))
const accentHex = computed(() => flowColorValue(props.colorId, theme.dark, props.seed ?? 0))

const boxStyle = computed(() => ({
    width: `${props.size}px`,
    height: `${props.size}px`,
    borderRadius: props.round ? '50%' : `${Math.round(props.size * 0.24)}px`,
    // the app's own color is the background (no border ring); an uploaded image sits on a neutral surface
    background: props.iconImage ? 'var(--bg3)' : accentHex.value,
}))

// Latin + CJK use the shared "Noto Sans JP"; Cyrillic / Mongolian fall back to system sans-serif.
const isCyrillic = computed(() => /[А-Яа-яЁёөү]/u.test(initial.value))
const fontFamily = computed(() => (isCyrillic.value ? 'sans-serif' : "'Noto Sans JP', sans-serif"))

// Luminance-based contrast: dark glyph on light accent, light glyph on dark accent.
const textColor = computed(() => {
    let hex = (accentHex.value || '').replace('#', '')
    if (hex.length === 3) hex = hex.split('').map((c) => c + c).join('')
    if (!/^[0-9a-fA-F]{6}$/.test(hex)) return '#ffffff'
    const r = parseInt(hex.slice(0, 2), 16)
    const g = parseInt(hex.slice(2, 4), 16)
    const b = parseInt(hex.slice(4, 6), 16)
    const luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255
    return luminance > 0.5 ? '#000000' : '#ffffff'
})

// Center the glyph on its visual mass (see glyphCentering.ts) so bottom-heavy glyphs don't sit low.
const baseline = ref(100)
const measured = ref(false)

const computeBaseline = () => {
    baseline.value = 100 - centroidBaselineOffset(initial.value, 100, fontFamily.value, 500)
    measured.value = true
}

const measure = async () => {
    try {
        const fonts = (document as any).fonts
        if (fonts?.load && !isCyrillic.value) {
            await fonts.load('500 100px "Noto Sans JP"', initial.value)
        }
    } catch { /* fall back to the available font */ }
    computeBaseline()
}

onMounted(measure)
watch([initial, fontFamily], () => { measured.value ? computeBaseline() : measure() })
</script>

<style scoped>
.fai { display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-sizing: border-box !important; }
.fai-img { width: 100%; height: 100%; object-fit: cover; }
.fai-svg { display: flex; align-items: center; justify-content: center; width: 60%; height: 60%; opacity: .8; color: var(--primary-color); }
.fai-svg :deep(svg) { width: 100% !important; height: 100% !important; }
.fai-default { display: block; }
</style>
