<template>
    <div>
        <div v-if="modelValue.length" class="flex flex-wrap gap-[6px] mb-[8px]">
            <span
                v-for="title in modelValue"
                :key="title"
                class="inline-flex items-center gap-[6px] px-[10px] py-[3px] text-[12.5px] bg-[var(--kebab-bg1)] text-[var(--primary-color)] border border-solid border-[var(--normalBorder)]"
            >
                {{ title }}
                <button type="button" @click="remove(title)" class="inline-flex items-center justify-center w-[15px] h-[15px] bg-[var(--secondary-background)] cursor-pointer">
                    <svg viewBox="0 0 24 24" width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </span>
        </div>
        <div class="relative max-w-[400px]">
            <input
                v-model="input"
                @keydown.enter.prevent="add(input)"
                @focus="open = true"
                @blur="onBlur"
                :placeholder="placeholder"
                class="!box-border w-full border border-solid border-[var(--primary-color)] h-[40px] px-[10px] text-[var(--primary-color)] bg-transparent outline-none"
            />
            <div v-if="showDropdown" class="absolute left-0 right-0 top-[calc(100%+2px)] z-[5] max-h-[240px] overflow-y-auto bg-[var(--menu-bg)] border border-solid border-[var(--normalBorder)] shadow-lg">
                <button
                    v-for="s in suggestions"
                    :key="s.id ?? s.title"
                    type="button"
                    @mousedown.prevent="add(s.title)"
                    class="!box-border flex items-center w-full text-left px-[12px] py-[8px] text-[13px] text-[var(--primary-color)] hover:bg-[var(--selected-background)]"
                >
                    {{ s.title }}
                </button>
                <button
                    v-if="canCreate"
                    type="button"
                    @mousedown.prevent="add(input)"
                    class="!box-border flex items-center w-full text-left px-[12px] py-[8px] text-[13px] text-[var(--primary-color)] hover:bg-[var(--selected-background)] border-t border-solid border-[var(--normalBorder)]"
                >
                    「{{ input.trim() }}」を新規作成
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { ContactType } from '@/interface/contactInterface';

const props = withDefaults(defineProps<{
    modelValue: string[];
    options?: ContactType[];
    placeholder?: string;
}>(), {
    options: () => [],
    placeholder: 'コンタクト種類を入力してEnter、または候補から選択',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

const input = ref('');
const open = ref(false);

const suggestions = computed(() => {
    const q = input.value.trim().toLowerCase();
    const avail = (props.options ?? []).filter(t => t.title && !props.modelValue.includes(t.title));
    // Empty input (just focused) → show the available options to pick from.
    if (!q) return avail.slice(0, 30);
    return avail.filter(t => t.title.toLowerCase().includes(q)).slice(0, 30);
});

const canCreate = computed(() => {
    const q = input.value.trim();
    if (!q) return false;
    const exists = (props.options ?? []).some(t => t.title === q) || props.modelValue.includes(q);
    return !exists;
});

const showDropdown = computed(() => open.value && (suggestions.value.length > 0 || canCreate.value));

const add = (title: string) => {
    const value = (title ?? '').trim();
    if (!value || props.modelValue.includes(value)) {
        input.value = '';
        return;
    }
    emit('update:modelValue', [...props.modelValue, value]);
    input.value = '';
};

const remove = (title: string) => {
    emit('update:modelValue', props.modelValue.filter(t => t !== title));
};

const onBlur = () => {
    // Just close the dropdown; a typed-but-not-committed value is flushed on
    // save via commit() so we don't accidentally create partial types here.
    open.value = false;
};

// Let parents force-commit any pending text (e.g. right before save).
const commit = () => { if (input.value.trim()) add(input.value); };
defineExpose({ commit });
</script>

<style scoped>
[class~="border"] { border-style: solid; }
[class~="border-t"] { border-top-style: solid; }
[class*="border"] { box-sizing: border-box !important; }
</style>
