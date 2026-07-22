<template>
    <div class="fss" :class="{ 'fss-disabled': disabled }">
        <!-- selected: show a chip; click the label to change, × to clear -->
        <div v-if="selected && !editing" class="fss-chip">
            <span class="fss-label" :title="selected.label" @click="reopen">{{ selected.label }}</span>
            <button v-if="clearable" type="button" class="fss-clear" title="解除" @click="clear">×</button>
        </div>
        <!-- empty or editing: searchable input + dropdown -->
        <div v-else class="fss-search">
            <input
                ref="inputEl"
                type="text"
                :value="query"
                class="fss-input"
                :placeholder="editing && selected ? selected.label : placeholder"
                :disabled="disabled"
                @focus="open = true"
                @input="onType"
                @keydown="onKeydown"
                @blur="onBlur"
            >
            <div v-if="open" ref="menuEl" class="fss-menu" :class="placement" @mousedown.prevent>
                <template v-for="(o, i) in filtered" :key="o.value">
                    <!-- divider between groups (e.g. normal apps vs system sources) -->
                    <div v-if="i > 0 && o.group !== filtered[i - 1].group" class="fss-divider" aria-hidden="true"></div>
                    <button
                        type="button"
                        class="fss-opt"
                        :class="{ on: o.value === modelValue, hl: i === highlighted }"
                        @click="pick(o)"
                        @mousemove="highlighted = i"
                    >
                        <span class="fss-opt-label">{{ o.label }}</span>
                        <span v-if="o.sub" class="fss-opt-sub">{{ o.sub }}</span>
                    </button>
                </template>
                <div v-if="!filtered.length" class="fss-empty">該当なし</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useFloatingMenu } from '@/composables/floatingMenu'

export interface SearchSelectOption {
    value: string | number
    label: string
    /** optional right-aligned muted sub-label (e.g. "#12") */
    sub?: string
    /** optional group key — a divider is drawn where consecutive options' groups differ */
    group?: string
}

const props = withDefaults(defineProps<{
    modelValue: string | number | null
    options: SearchSelectOption[]
    placeholder?: string
    disabled?: boolean
    clearable?: boolean
}>(), { placeholder: '選択…', disabled: false, clearable: true })

const emit = defineEmits<{ 'update:modelValue': [string | number | null] }>()

const open = ref(false)
const editing = ref(false)
const query = ref('')
const highlighted = ref(0)
const inputEl = ref<HTMLInputElement | null>(null)
const menuEl = ref<HTMLElement | null>(null)
// menu is position:absolute inside the field; this just decides open above vs. below
const { placement } = useFloatingMenu(open, inputEl)

const selected = computed(() => props.options.find((o) => o.value === props.modelValue) ?? null)
// :value + @input (instead of v-model) so the filter reacts live during an IME composition
const onType = (e: Event) => { query.value = (e.target as HTMLInputElement).value; open.value = true }
const filtered = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return props.options
    return props.options.filter((o) => o.label.toLowerCase().includes(q))
})
// keep the highlight in range as the list changes
watch(filtered, () => { highlighted.value = 0 })

const scrollHighlightIntoView = () => nextTick(() => {
    menuEl.value?.querySelectorAll<HTMLElement>('.fss-opt')[highlighted.value]?.scrollIntoView({ block: 'nearest' })
})
const move = (delta: number) => {
    const n = filtered.value.length
    if (!n) return
    highlighted.value = Math.max(0, Math.min(n - 1, highlighted.value + delta))
    scrollHighlightIntoView()
}
const onKeydown = (e: KeyboardEvent) => {
    if (e.isComposing || e.keyCode === 229) return // don't hijack Enter/arrows while an IME is composing
    if (e.key === 'ArrowDown') { e.preventDefault(); if (!open.value) { open.value = true } else move(1) }
    else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1) }
    else if (e.key === 'Enter') { if (open.value && filtered.value[highlighted.value]) { e.preventDefault(); pick(filtered.value[highlighted.value]) } }
    else if (e.key === 'Escape') { open.value = false }
}

const reopen = () => {
    if (props.disabled) return
    editing.value = true
    query.value = ''
    highlighted.value = 0
    open.value = true
    nextTick(() => inputEl.value?.focus())
}
const pick = (o: SearchSelectOption) => {
    emit('update:modelValue', o.value)
    query.value = ''
    open.value = false
    editing.value = false
}
const clear = () => { emit('update:modelValue', null); query.value = ''; editing.value = false }
// delay so an option's @click lands before the menu closes on blur
const onBlur = () => setTimeout(() => { open.value = false; editing.value = false; query.value = '' }, 120)
</script>

<style scoped>
.fss { position: relative; }
.fss-disabled { opacity: .6; pointer-events: none; }
.fss-search { position: relative; }
/* chip + input are ONE identical box (mirrors .custom-a-input), so switching states never shifts
   size/shape/position. Deliberately no min-height/line-height: let natural metrics match the plain
   inputs beside it (a global rule forces content-box, so an explicit min-height would inflate it). */
.fss-input,
.fss-chip {
    /* !important: the app's global box model is content-box, which makes width:100% + padding
       overflow the parent (fits when idle, grows past it when focused). border-box fixes both. */
    box-sizing: border-box !important;
    width: 100%;
    min-height: 31px; /* total height (border-box) — matches the sibling .custom-a-input (~31px) */
    padding: 5px 10px;
    border: solid thin var(--formBorder);
    border-radius: 5px;
    background: var(--background-color);
    font-size: 13px;
    line-height: 19px;
    /* inputs reset letter-spacing to normal while the chip label inherits the global 1px — pin both
       to normal (like the app's other form inputs) so text doesn't shift between states */
    letter-spacing: normal;
    color: var(--primary-color);
}
.fss-input { display: block; }
.fss-input:focus { border-color: var(--primary-color); outline: none; }
/* absolute within .fss-search; useFloatingMenu toggles .top/.bottom for the flip */
.fss-menu { position: absolute; left: 0; right: 0; z-index: 50; max-height: 260px; overflow-y: auto; overflow-x: hidden; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,.12); padding: 4px; box-sizing: border-box !important; }
.fss-menu.bottom { top: calc(100% + 4px); }
.fss-menu.top { bottom: calc(100% + 4px); }
.fss-opt { display: flex; align-items: center; justify-content: space-between; gap: 10px; width: 100%; box-sizing: border-box !important; text-align: left; border: none; background: none; padding: 7px 9px; border-radius: 6px; cursor: pointer; font-size: 13px; letter-spacing: normal; color: var(--primary-color); }
.fss-opt.hl { background: var(--bg3); }
.fss-opt.on { background: var(--bg3); }
.fss-divider { height: 1px; background: var(--formBorder); margin: 5px 6px; }
.fss-opt-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
.fss-opt-sub { flex-shrink: 0; font-size: 11px; color: gray; }
.fss-empty { padding: 9px; font-size: 12px; color: gray; text-align: center; }
.fss-chip { display: flex; align-items: center; gap: 8px; padding-right: 6px; cursor: default; }
.fss-label { font-size: 13px; color: var(--primary-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; flex: 1; cursor: pointer; }
.fss-clear { border: none; background: none; color: gray; cursor: pointer; font-size: 15px; line-height: 1; padding: 0 4px; flex-shrink: 0; }
.fss-clear:hover { color: #e2574c; }
</style>
