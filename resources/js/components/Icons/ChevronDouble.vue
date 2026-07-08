<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="`${size}px`"
        :height="`${size}px`"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        :stroke-width="stroke"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <polyline points="7 5 14 12 7 19"></polyline>
        <polyline points="13 5 20 12 13 19"></polyline>
    </svg>
</template>
<script setup lang="ts">
withDefaults(defineProps<{ size?: string | number; stroke?: string | number }>(), {
    size: 16,
    stroke: 2.2,
})
</script>
