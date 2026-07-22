<template>
    <svg xmlns="http://www.w3.org/2000/svg" :width="`${size}px`" :height="`${size}px`" :fill="color" viewBox="0 0 24 24">
        <path d="M15 1H4a2 2 0 0 0-2 2v13h2V3h11V1z" />
        <path d="M20 5H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm0 16H8V7h12v14z" />
    </svg>
</template>
<script setup lang="ts">
interface Props {
    size?: string | number
    color?: string
}
const props = withDefaults(defineProps<Props>(), {
    size: 8,
    color: 'var(--primary-color)',
})
</script>
