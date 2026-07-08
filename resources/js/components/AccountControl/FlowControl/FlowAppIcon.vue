<template>
    <div class="fai" :style="boxStyle">
        <img v-if="iconImage" :src="iconImage" class="fai-img" alt="">
        <span v-else-if="iconSvg" class="fai-svg" v-html="iconSvg"></span>
        <span v-else class="fai-initial" :style="{ fontSize: Math.round(size * 0.42) + 'px' }">{{ initial }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useTheme } from '@/store/theme'
import { flowColorValue } from '@/utils/flowColors'

const props = withDefaults(defineProps<{
    iconSvg?: string | null
    iconImage?: string | null
    colorId?: number | null
    name?: string
    seed?: number
    size?: number
    onBand?: boolean
}>(), { size: 44, onBand: false })

const theme = useTheme()
const initial = computed(() => (props.name?.trim()?.[0] ?? '?'))
const accentHex = computed(() => flowColorValue(props.colorId, theme.dark, props.seed ?? 0))
const boxStyle = computed(() => {
    const base: Record<string, string> = {
        width: `${props.size}px`,
        height: `${props.size}px`,
        borderRadius: `${Math.round(props.size * 0.24)}px`,
    }
    if (props.onBand) {
        // sits on a colored band → surface square with the symbol in a deep tint of the accent
        base.background = 'var(--background-color)'
        base.border = '1px solid var(--calendarBorder)'
        base.color = `color-mix(in srgb, ${accentHex.value} 45%, var(--primary-color))`
    } else {
        base.background = props.iconImage ? 'var(--bg3)' : accentHex.value
        base.color = 'var(--primary-color)'
    }
    return base
})
</script>

<style scoped>
.fai { display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; color: var(--primary-color); box-sizing: border-box !important; }
.fai-img { width: 100%; height: 100%; object-fit: cover; }
.fai-svg { display: flex; align-items: center; justify-content: center; width: 60%; height: 60%; opacity: .8; }
.fai-svg :deep(svg) { width: 100% !important; height: 100% !important; }
.fai-initial { font-weight: 700; line-height: 1; }
</style>
