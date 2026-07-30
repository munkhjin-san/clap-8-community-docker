<template>
    <div class="lp" :class="{ 'lp-multi': multiple }">
        <!-- chips sit in the field's own flex flow (no wrapper): a wrapper that grew to the full width
             pushed the search box away from the last chip and left a gap between them -->
        <span v-for="c in chips" :key="c.id" class="lp-chip">
            <UserPanel v-if="avatar" :user="(c.raw as any)" :size="18" :disable-instant="true" class="lp-avatar" />
            <span class="lp-chiplabel">{{ c.label }}</span>
            <button type="button" class="lp-clear" title="解除" @click.stop="remove(c.id)">×</button>
        </span>

        <!-- the search box disappears once a single-select has its value: there is nothing more to pick
             until the chip is cleared (the same contract as the reference field's picker) -->
        <input
            v-if="multiple || !chips.length"
            ref="inputEl"
            type="text"
            class="lp-input"
            :value="query"
            :placeholder="chips.length ? '' : placeholder"
            :disabled="disabled"
            @focus="open"
            @input="onInput"
            @keydown="onKeydown"
            @blur="close"
        >

        <!-- a child of the field, not of the input, so it lines up with the field's edges rather than
             starting wherever the search box happens to have been pushed to -->
        <div v-if="isOpen" ref="menuEl" class="lp-menu" :class="placement">
            <button
                v-for="(o, i) in filtered"
                :key="o.id"
                type="button"
                class="lp-opt"
                :class="{ hl: i === highlighted }"
                @mousedown.prevent
                @click="pick(o)"
                @mousemove="highlighted = i"
            >
                <UserPanel v-if="avatar" :user="(o as any)" :size="20" :disable-instant="true" class="lp-avatar" />
                <span class="lp-optlabel">{{ o.name }}</span>
            </button>
            <div v-if="!filtered.length" class="lp-empty">{{ options.length ? '該当がありません' : '選択できる項目がありません' }}</div>
        </div>
    </div>
</template>

<script setup lang="ts">
/**
 * Flow's own project / user picker.
 *
 * Built to match the flow field styling rather than wrapping the app-wide MemberSelector /
 * ItemSelector: those are Vuetify autocompletes sized for roomy modal forms, and from the outside
 * only their *field* can be restyled — the dropdown is teleported to a container under <body>, so
 * scoped CSS can't reach it, and the selection and search box are competing flex children of
 * Vuetify's own layout, so a chip can't be made to fill its box. This is the same hand-rolled shape
 * the `reference` field in FlowFieldInput already uses, over a list that's already in memory.
 *
 * The shared components stay exactly as they are for the rest of the app.
 */
import { computed, nextTick, ref } from 'vue'
import { useFloatingMenu } from '@/composables/floatingMenu'
import UserPanel from '@/components/Global/UserPanel.vue'

type Option = { id: number; name: string }

const props = withDefaults(defineProps<{
    /** single: an id (or null). multiple: an array of ids. */
    modelValue: any
    options: Option[]
    multiple?: boolean
    /**
     * Store as an array even when only one may be picked. That is a separate question from
     * `multiple`: a ユーザー field always persists an id array (value_json / userIdArrayValue) whether
     * or not it accepts several, while a プロジェクト field persists a single scalar id.
     */
    arrayValue?: boolean
    /** render each row with the person's avatar (user / member fields) */
    avatar?: boolean
    placeholder?: string
    disabled?: boolean
}>(), { placeholder: '選択してください' })

const emit = defineEmits<{ 'update:modelValue': [any] }>()

const query = ref('')
const isOpen = ref(false)
const highlighted = ref(0)
const inputEl = ref<HTMLInputElement | null>(null)
const menuEl = ref<HTMLElement | null>(null)
// the menu is absolute inside the field; this only decides open-above vs open-below
const { placement } = useFloatingMenu(isOpen, inputEl)

const byId = computed<Record<number, Option>>(() => {
    const m: Record<number, Option> = {}
    for (const o of props.options) m[o.id] = o
    return m
})

/**
 * Selected ids, normalised — a single-select still reads as a one-item list here.
 *
 * The filter is not paranoia: an empty ユーザー field arrives as [], and `Number([])` is 0, so
 * treating a single-select value as a scalar invented a chip for user #0 on every empty field.
 * Ids are always positive, so anything else is noise from an empty or half-migrated value.
 */
const selectedIds = computed<number[]>(() => {
    const v = props.modelValue
    const raw = Array.isArray(v) ? v : (v === null || v === undefined || v === '' ? [] : [v])
    const ids = raw.map(Number).filter((n) => Number.isFinite(n) && n > 0)
    return props.multiple ? ids : ids.slice(0, 1)
})

const chips = computed(() => selectedIds.value.map((id) => ({
    id,
    label: byId.value[id]?.name ?? `#${id}`,
    // an id with no matching option (a retired user, a project the viewer can't see) still needs a
    // chip, so UserPanel gets a minimal stand-in rather than nothing
    raw: byId.value[id] ?? { id, name: `#${id}` },
})))

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase()
    const taken = new Set(selectedIds.value)
    return props.options
        .filter((o) => !taken.has(o.id))
        .filter((o) => !q || o.name.toLowerCase().includes(q))
        .slice(0, 50)
})

const emitIds = (ids: number[]) => {
    const next = props.multiple ? ids : ids.slice(0, 1)
    emit('update:modelValue', props.arrayValue ? next : (next[0] ?? null))
}

const open = () => { if (!props.disabled) { isOpen.value = true; highlighted.value = 0 } }
const close = () => { setTimeout(() => { isOpen.value = false }, 120) }
const onInput = (e: Event) => {
    query.value = (e.target as HTMLInputElement).value
    isOpen.value = true
    highlighted.value = 0
}

const pick = (o: Option) => {
    emitIds(props.multiple ? [...selectedIds.value, o.id] : [o.id])
    query.value = ''
    highlighted.value = 0
    // multi-select keeps picking; single-select is done and its input is about to disappear
    if (!props.multiple) isOpen.value = false
}
const remove = (id: number) => emitIds(selectedIds.value.filter((x) => x !== id))

const onKeydown = (e: KeyboardEvent) => {
    if (e.isComposing || e.keyCode === 229) return // never hijack keys while an IME is composing

    if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || (e.key === 'Enter' && isOpen.value) || (e.key === 'Escape' && isOpen.value)) {
        // The record list saves the row on Enter and cancels on Escape from a document listener, so
        // those keys have to stop here while the menu owns them — otherwise choosing an option would
        // also save the row.
        e.stopPropagation()
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault()
        if (!isOpen.value) { open(); return }
        highlighted.value = Math.min(filtered.value.length - 1, highlighted.value + 1)
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        highlighted.value = Math.max(0, highlighted.value - 1)
    } else if (e.key === 'Enter') {
        const o = filtered.value[highlighted.value]
        if (isOpen.value && o) { e.preventDefault(); pick(o) }
    } else if (e.key === 'Escape') {
        isOpen.value = false
        return
    } else if (e.key === 'Backspace' && !query.value && props.multiple && selectedIds.value.length) {
        remove(selectedIds.value[selectedIds.value.length - 1])
    }
    nextTick(() => menuEl.value?.querySelectorAll<HTMLElement>('.lp-opt')[highlighted.value]?.scrollIntoView({ block: 'nearest' }))
}
</script>

<style scoped>
/* Sized a little taller than .fi-input's 33px: the chips and avatars inside need room to breathe,
   and a picker that is flush to its own border reads as cramped next to a plain text input. */
.lp { position: relative; display: flex; flex-wrap: wrap; align-items: center; gap: 6px; box-sizing: border-box !important; min-height: 36px; padding: 5px; border: 1px solid var(--formBorder); border-radius: 6px; background: var(--background-color); }
.lp:focus-within { border-color: var(--primary-color); }

/* The chip is the width of its label — no stretching to fill the field. Filling made the whole field
   look like one grey pill, and a single-select chip that grows with its container reads as a bug. */
.lp-chip { display: inline-flex; align-items: center; gap: 6px; min-width: 0; max-width: 100%; box-sizing: border-box !important; padding: 5px; border-radius: 5px; background: var(--bg3); }
.lp-chiplabel { font-size: 12px; color: var(--primary-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
.lp-avatar { flex-shrink: 0; }
.lp-clear { flex-shrink: 0; border: none; background: none; color: gray; cursor: pointer; font-size: 13px; line-height: 1; padding: 0 4px; }
.lp-clear:hover { color: #e2574c; }

/* the field's border is the box; the input just lives in it, taking the rest of its line */
.lp-input { flex: 1 1 70px; min-width: 70px; box-sizing: border-box !important; border: none; background: transparent; color: var(--primary-color); font-size: 13px; padding: 2px 2px; outline: none; }
.lp-input::placeholder { color: gray; }

.lp-menu { position: absolute; left: 0; right: 0; z-index: 50; min-width: 170px; max-height: 240px; overflow-y: auto; overflow-x: hidden; box-sizing: border-box !important; padding: 5px; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 8px; box-shadow: 0 6px 20px rgba(0, 0, 0, .12); }
.lp-menu.bottom { top: calc(100% + 4px); }
.lp-menu.top { bottom: calc(100% + 4px); }
/* compact, but not cramped — the shared component's rows were 48px tall with 16px text */
.lp-opt { display: flex; align-items: center; gap: 8px; width: 100%; box-sizing: border-box !important; text-align: left; border: none; background: none; padding: 7px 10px; border-radius: 6px; cursor: pointer; font-size: 13px; color: var(--primary-color); }
.lp-opt.hl { background: var(--bg3); }
.lp-optlabel { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
.lp-empty { padding: 10px; font-size: 12px; color: gray; text-align: center; }
</style>
