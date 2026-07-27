<template>
    <div class="relative">
        <button
            type="button"
            @click="toggle"
            :class="[
                'h-[34px] px-[12px] inline-flex items-center gap-[7px] text-[13px] font-medium cursor-pointer border transition-colors',
                (open || modelValue.length)
                    ? 'border-[var(--formBorder)] bg-[var(--kebab-bg1)] text-[var(--primary-color)]'
                    : 'border-[var(--normalBorder)] bg-[var(--message-background)] text-[gray]'
            ]"
        >
            {{ label }}
            <span
                v-if="modelValue.length"
                class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-[5px] text-[11px] font-bold bg-[var(--primary-color)] text-[var(--background-color)]"
            >{{ modelValue.length }}</span>
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        </button>

        <template v-if="open">
            <div class="fixed inset-0 z-[55]" @click="open = false"></div>
            <div class="absolute top-[calc(100%+6px)] left-0 w-[300px] z-[60] overflow-hidden border border-[var(--formBorder)] bg-[var(--menu-bg)] shadow-[0_18px_44px_rgba(0,0,0,0.45)]">
                <div class="p-[10px]">
                    <input
                        v-model="query"
                        :placeholder="searchPlaceholder"
                        class="!box-border w-full h-[36px] px-[12px] text-[13px] outline-none bg-[var(--inactive-background)] border border-[var(--normalBorder)] text-[var(--primary-color)] focus:border-[var(--formBorder)]"
                    />
                </div>
                <div class="max-h-[300px] overflow-y-auto px-[6px]">
                    <button
                        v-for="opt in filteredOptions"
                        :key="opt.value"
                        type="button"
                        @click="toggleValue(opt.value)"
                        :class="[
                            '!box-border flex items-center gap-[10px] w-full p-[8px] my-[1px] cursor-pointer text-[13px] text-[var(--primary-color)] transition-colors',
                            isSelected(opt.value) ? 'bg-[var(--selected-background)]' : 'hover:bg-[var(--selected-background)]'
                        ]"
                    >
                        <span
                            :class="[
                                'w-[17px] h-[17px] shrink-0 inline-flex items-center justify-center border',
                                isSelected(opt.value) ? 'bg-[var(--primary-color)] border-[var(--primary-color)]' : 'border-[var(--formBorder)]'
                            ]"
                        >
                            <svg v-if="isSelected(opt.value)" viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="var(--background-color)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 6"/></svg>
                        </span>
                        <span class="flex-1 text-left overflow-hidden text-ellipsis whitespace-nowrap">{{ opt.label }}</span>
                        <span class="text-[11.5px] text-[gray] shrink-0">{{ opt.count ?? 0 }}</span>
                    </button>
                    <div v-if="!filteredOptions.length" class="p-[16px] text-center text-[gray] text-[12.5px]">
                        一致する{{ totalLabel }}がありません
                    </div>
                </div>
                <div class="flex items-center justify-between px-[12px] py-[9px] border-t border-[var(--normalBorder)]">
                    <button type="button" @click="clear" class="bg-transparent border-none text-[gray] text-[12.5px] cursor-pointer p-0 hover:text-[var(--primary-color)]">
                        {{ totalLabel }}をクリア
                    </button>
                    <span class="text-[11.5px] text-[gray]">全 {{ options.length }} {{ totalLabel }}</span>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

interface Option {
    value: number;
    label: string;
    count?: number;
}

const props = withDefaults(defineProps<{
    modelValue: number[];
    options: Option[];
    label: string;
    searchPlaceholder?: string;
    totalLabel?: string;
}>(), {
    searchPlaceholder: '検索',
    totalLabel: '項目',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number[]): void;
}>();

const open = ref(false);
const query = ref('');

const toggle = () => {
    open.value = !open.value;
    if (open.value) query.value = '';
};

const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter(opt => opt.label.toLowerCase().includes(q));
});

const isSelected = (value: number) => props.modelValue.includes(value);

const toggleValue = (value: number) => {
    const next = isSelected(value)
        ? props.modelValue.filter(v => v !== value)
        : [...props.modelValue, value];
    emit('update:modelValue', next);
};

const clear = () => emit('update:modelValue', []);
</script>

<style scoped>
/* Tailwind preflight is disabled app-wide, so border-width utilities have no
   border-style and render nothing. Give bordered elements an explicit style. */
[class~="border"],
[class~="border-2"] { border-style: solid; }
[class~="border-t"] { border-top-style: solid; }
[class~="border-b"],
[class~="border-b-2"] { border-bottom-style: solid; }
[class~="border-l"] { border-left-style: solid; }
[class~="border-r"] { border-right-style: solid; }
[class*="border"] { box-sizing: border-box !important; }
</style>
