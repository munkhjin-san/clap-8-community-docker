<template>
    <span
        class="inline-flex w-fit max-w-full items-center gap-[5px] whitespace-nowrap rounded-full px-2 py-1 text-[11px] leading-none"
        :style="chipStyle"
    >
        <svg width="13" height="13" viewBox="0 0 24 24" aria-hidden="true" class="shrink-0 fill-current">
            <path :d="meta.icon" />
        </svg>
        <span>{{ label }}</span>
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { SystemUpdateDetailType } from '@/interface/supportInterface';
import { detailTypeMeta, detailTypeOptions, labelFromOptions } from './options';

const props = defineProps<{
    type: SystemUpdateDetailType;
}>();

const meta = computed(() => detailTypeMeta[props.type] ?? detailTypeMeta.other);
const label = computed(() => labelFromOptions(detailTypeOptions, props.type));
const chipStyle = computed(() => ({
    color: meta.value.color,
    backgroundColor: `color-mix(in srgb, ${meta.value.color} 12%, transparent)`,
    border: `1px solid color-mix(in srgb, ${meta.value.color} 25%, transparent)`,
}));
</script>
