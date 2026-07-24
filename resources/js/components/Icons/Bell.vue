<template>
    <svg xmlns="http://www.w3.org/2000/svg" :width="`${size}px`" :height="`${size}px`" :fill="color" viewBox="0 0 24 24">
        <path d="M12 2a7 7 0 0 0-7 7v3.3c0 .5-.2 1-.6 1.4L3 15.1c-.9.9-.2 2.4 1 2.4h16c1.2 0 1.9-1.5 1-2.4l-1.4-1.4a2 2 0 0 1-.6-1.4V9a7 7 0 0 0-7-7zm0 2a5 5 0 0 1 5 5v3.3c0 1 .4 2 1.2 2.8l.4.4H5.4l.4-.4A4 4 0 0 0 7 12.3V9a5 5 0 0 1 5-5z" />
        <path d="M9.5 19a2.5 2.5 0 0 0 5 0h-5z" />
    </svg>
</template>
<script setup lang="ts">
interface Props {
    size?: string | number
    color?: string
}
const props = withDefaults(defineProps<Props>(), {
    size: 14,
    color: 'var(--primary-color)',
})
</script>
