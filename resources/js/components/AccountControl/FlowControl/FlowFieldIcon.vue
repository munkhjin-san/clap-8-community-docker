<template>
    <svg
        :width="`${size}px`"
        :height="`${size}px`"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.7"
        stroke-linecap="round"
        stroke-linejoin="round"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <template v-if="type === 'short'">
            <rect x="2.5" y="7.5" width="19" height="9" rx="2" />
            <line x1="6" y1="10.5" x2="6" y2="13.5" />
        </template>
        <template v-else-if="type === 'long'">
            <rect x="2.5" y="4.5" width="19" height="15" rx="2" />
            <line x1="6" y1="9" x2="18" y2="9" />
            <line x1="6" y1="12" x2="18" y2="12" />
            <line x1="6" y1="15" x2="13" y2="15" />
        </template>
        <template v-else-if="type === 'number'">
            <line x1="9" y1="4" x2="7" y2="20" />
            <line x1="17" y1="4" x2="15" y2="20" />
            <line x1="4" y1="9" x2="20" y2="9" />
            <line x1="4" y1="15" x2="20" y2="15" />
        </template>
        <template v-else-if="type === 'date'">
            <rect x="3.5" y="5" width="17" height="16" rx="2" />
            <line x1="3.5" y1="9.5" x2="20.5" y2="9.5" />
            <line x1="8" y1="3" x2="8" y2="6.5" />
            <line x1="16" y1="3" x2="16" y2="6.5" />
        </template>
        <template v-else-if="type === 'time'">
            <circle cx="12" cy="12" r="8.5" />
            <line x1="12" y1="12" x2="12" y2="7.5" />
            <line x1="12" y1="12" x2="15.5" y2="13.5" />
        </template>
        <template v-else-if="type === 'select'">
            <rect x="2.5" y="6.5" width="19" height="11" rx="2" />
            <polyline points="13.5,11 16,13.5 18.5,11" />
        </template>
        <template v-else-if="type === 'radio'">
            <circle cx="12" cy="12" r="8.5" />
            <circle cx="12" cy="12" r="3.2" fill="currentColor" stroke="none" />
        </template>
        <template v-else-if="type === 'checkbox'">
            <rect x="3.5" y="3.5" width="17" height="17" rx="3" />
            <polyline points="8,12 11,15 16,8.5" />
        </template>
        <template v-else-if="type === 'file'">
            <path d="M13.5 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8.5z" />
            <polyline points="13.5,3 13.5,8.5 19,8.5" />
        </template>
        <template v-else-if="type === 'table'">
            <rect x="3" y="4.5" width="18" height="15" rx="2" />
            <line x1="3" y1="9" x2="21" y2="9" />
            <line x1="3" y1="14.25" x2="21" y2="14.25" />
            <line x1="10" y1="4.5" x2="10" y2="19.5" />
        </template>
        <template v-else-if="type === 'reference'">
            <path d="M14 4.5h5.5V10" />
            <line x1="19.5" y1="4.5" x2="12.5" y2="11.5" />
            <path d="M11 6.5H6.5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V13" />
        </template>
        <template v-else-if="type === 'heading'">
            <line x1="7" y1="5" x2="7" y2="19" />
            <line x1="15" y1="5" x2="15" y2="19" />
            <line x1="7" y1="12" x2="15" y2="12" />
            <line x1="18" y1="9.5" x2="20" y2="9.5" />
            <line x1="20" y1="9.5" x2="20" y2="19" />
        </template>
        <template v-else-if="type === 'datetime'">
            <rect x="3" y="4.5" width="13.5" height="13" rx="2" />
            <line x1="3" y1="8.5" x2="16.5" y2="8.5" />
            <line x1="7" y1="3" x2="7" y2="6" />
            <line x1="13" y1="3" x2="13" y2="6" />
            <circle cx="17.5" cy="17" r="4.3" />
            <line x1="17.5" y1="17" x2="17.5" y2="15" />
            <line x1="17.5" y1="17" x2="19.5" y2="18" />
        </template>
        <template v-else-if="type === 'toggle'">
            <rect x="2.5" y="7.5" width="19" height="9" rx="4.5" />
            <circle cx="16.5" cy="12" r="2.8" fill="currentColor" stroke="none" />
        </template>
        <template v-else-if="type === 'user'">
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5.5 19.5a6.5 6.5 0 0 1 13 0" />
        </template>
        <template v-else-if="type === 'member'">
            <circle cx="9" cy="8.5" r="3" />
            <path d="M3.5 19a5.5 5.5 0 0 1 11 0" />
            <circle cx="16.5" cy="8" r="2.4" />
            <path d="M15 13.4a4.8 4.8 0 0 1 5.5 4.1" />
        </template>
        <template v-else-if="type === 'formula'">
            <rect x="3.5" y="3.5" width="17" height="17" rx="3" />
            <line x1="8" y1="10" x2="16" y2="10" />
            <line x1="8" y1="14" x2="16" y2="14" />
        </template>
        <template v-else-if="type === 'label'">
            <line x1="4" y1="7" x2="20" y2="7" />
            <line x1="4" y1="12" x2="20" y2="12" />
            <line x1="4" y1="17" x2="13" y2="17" />
        </template>
        <template v-else-if="type === 'spacer'">
            <rect x="3.5" y="3.5" width="17" height="17" rx="2" stroke-dasharray="3 2.5" />
            <polyline points="9,10 12,7 15,10" />
            <polyline points="9,14 12,17 15,14" />
        </template>
        <template v-else-if="type === 'divider'">
            <line x1="3" y1="12" x2="21" y2="12" stroke-dasharray="4 3" />
        </template>
        <template v-else>
            <circle cx="12" cy="12" r="2" fill="currentColor" stroke="none" />
        </template>
    </svg>
</template>

<script setup lang="ts">
import type { FlowInputType } from '@/types/flow'

withDefaults(defineProps<{ type: FlowInputType | string; size?: string | number }>(), {
    size: 18,
})
</script>
