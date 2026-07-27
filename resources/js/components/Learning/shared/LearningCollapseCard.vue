<template>
    <div class="m-[20px] border border-solid border-[var(--formBorder)] bg-[var(--background-color)]">
        <button
            type="button"
            class="w-full flex items-center gap-[10px] px-[15px] py-[20px] border-0 bg-transparent cursor-pointer text-[var(--primary-color)]"
            @click="open = !open"
        >
            <Back size="11" class="transition-transform duration-200" :class="open ? 'rotate-[270deg]' : 'rotate-180'" />
            <span>{{ label }}</span>
        </button>
        <div v-show="open" class="lcc-body">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Back from '@/components/Icons/Back.vue'

const props = withDefaults(defineProps<{
    label: string
    defaultOpen?: boolean
}>(), {
    defaultOpen: false,
})

const open = ref(props.defaultOpen)
</script>

<style scoped>
.lcc-body {
    padding: 24px;
}
</style>
