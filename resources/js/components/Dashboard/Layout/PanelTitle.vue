<template>
    <div :class="{'bg-[var(--selected-background)]' : expanded}" class="select-none w-full h-[48px] flex items-center px-3 text-[13px] overflow-hidden whitespace-nowrap text-ellipsis">
        <slot></slot>
    </div>
</template>
<script setup lang="ts">
const props = defineProps<{
    expanded: boolean
}>()
</script>
