<template>
    <div ref="fieldEl" class="lp" :class="{ 'lp-multi': multiple }">
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

        <!-- Teleported to <body> and positioned against the field. Inside a テーブル the menu lives in
             .fi-tbl-scroll, whose overflow-x:auto forces overflow-y to auto as well — a 71px tall
             clipping box that left a 240px menu with 1px showing. No ancestor can clip it from here;
             the trade-off is that the coordinates are ours to maintain (see placeMenu). -->
        <Teleport to="body">
        <div v-if="isOpen" ref="menuEl" class="lp-menu" :style="menuStyle">
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
        </Teleport>
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
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import { searchKey } from '@/utils/searchText'

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
const fieldEl = ref<HTMLElement | null>(null)

/**
 * The menu is fixed-positioned against the field because it is teleported out of the DOM subtree —
 * the only way to escape a テーブル's scroll box. Recomputed whenever the field can have moved: any
 * scroll (captured, so inner scrollers count too) and any resize.
 */
const MENU_MIN_H = 140
/** A list is for scanning, not for filling the window — beyond this it scrolls inside itself. */
const MENU_MAX_H = 280
const MENU_MIN_W = 180
const menuStyle = ref<Record<string, string>>({})
/**
 * Which side the menu opened on. Decided once, when it opens, and kept while scrolling — otherwise
 * the menu flips from above the field to below it mid-scroll, which reads as a jump rather than as
 * following. It only switches if the chosen side genuinely stops fitting.
 */
let side: 'down' | 'up' = 'down'
const placeMenu = (decideSide = false) => {
    const el = fieldEl.value
    if (!el) return
    const r = el.getBoundingClientRect()
    const vh = window.innerHeight
    const vw = window.innerWidth

    // The field has been scrolled out of view. A menu pinned to a coordinate nobody can see reads as
    // a stray panel floating over the page, so close it rather than follow the field off-screen.
    if (r.bottom < 0 || r.top > vh) {
        isOpen.value = false

        return
    }

    // Space is measured against the VIEWPORT, and capped: without the cap a field low on a short
    // window reported ~800px of room above it and produced an 800px menu in a 420px window.
    const below = Math.max(0, vh - r.bottom - 8)
    const above = Math.max(0, r.top - 8)
    if (decideSide) side = below >= MENU_MIN_H || below >= above ? 'down' : 'up'
    else if (side === 'down' && below < MENU_MIN_H && above > below) side = 'up'
    else if (side === 'up' && above < MENU_MIN_H && below > above) side = 'down'
    const openDown = side === 'down'
    const maxHeight = Math.min(MENU_MAX_H, Math.max(MENU_MIN_H, openDown ? below : above))

    const width = Math.max(MENU_MIN_W, Math.round(r.width))
    const left = Math.min(Math.max(8, Math.round(r.left)), Math.max(8, vw - width - 8))

    menuStyle.value = openDown
        ? { left: `${left}px`, top: `${Math.round(r.bottom + 4)}px`, width: `${width}px`, maxHeight: `${maxHeight}px` }
        : { left: `${left}px`, bottom: `${Math.round(Math.max(8, vh - r.top + 4))}px`, width: `${width}px`, maxHeight: `${maxHeight}px` }
}
const onReflow = () => { if (isOpen.value) placeMenu() }
/**
 * Teleported to <body>, the menu is no longer inside anything that would hide it when attention moves
 * elsewhere — opening a dialog, or clicking into the side menu, used to leave it floating. Blur alone
 * doesn't cover it (the nav expands on hover, and a route-guard dialog steals no focus), so any
 * pointerdown outside the field and the menu closes it.
 */
const onOutsidePointer = (e: PointerEvent) => {
    if (!isOpen.value) return
    const t = e.target as Node | null
    if (t && (fieldEl.value?.contains(t) || menuEl.value?.contains(t))) return
    isOpen.value = false
}
watch(isOpen, (v) => {
    if (!v) return
    placeMenu(true)
    nextTick(() => placeMenu()) // the field may still be settling (a chip was just added/removed)
})
window.addEventListener('scroll', onReflow, true) // capture: inner scrollers don't bubble
window.addEventListener('resize', onReflow)
document.addEventListener('pointerdown', onOutsidePointer, true)
onBeforeUnmount(() => {
    window.removeEventListener('scroll', onReflow, true)
    window.removeEventListener('resize', onReflow)
    document.removeEventListener('pointerdown', onOutsidePointer, true)
})

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
    // searchKey, not toLowerCase: project names carry halfwidth katakana by house rule, so a raw
    // match could not find ﾃﾙｳｪﾙ from what a person types (テルウェル). Folded once here, not per option.
    const q = searchKey(query.value.trim())
    const taken = new Set(selectedIds.value)
    return props.options
        .filter((o) => !taken.has(o.id))
        .filter((o) => !q || searchKey(o.name).includes(q))
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

    if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || (e.key === 'Escape' && isOpen.value)) {
        // The record list cancels an inline row edit on Escape from a document listener, so while the
        // menu is open Escape has to stop here — closing it must not also abandon the row.
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

/* Position and size come from placeMenu.
   z-index 45 is chosen, not arbitrary: it clears the record screen (which is itself .overlay = 43 on
   a narrow screen) and the file preview (44), while staying UNDER the confirm dialog (.cu-toast-mask
   = 54) and the side menu (99+). A teleported menu escapes every clip, so the stacking order is the
   only thing left keeping it in its place. */
.lp-menu { position: fixed; z-index: 45; overflow-y: auto; overflow-x: hidden; box-sizing: border-box !important; padding: 5px; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 8px; box-shadow: 0 6px 20px rgba(0, 0, 0, .12); }
/* compact, but not cramped — the shared component's rows were 48px tall with 16px text */
.lp-opt { display: flex; align-items: center; gap: 8px; width: 100%; box-sizing: border-box !important; text-align: left; border: none; background: none; padding: 7px 10px; border-radius: 6px; cursor: pointer; font-size: 13px; color: var(--primary-color); }
.lp-opt.hl { background: var(--bg3); }
.lp-optlabel { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
.lp-empty { padding: 10px; font-size: 12px; color: gray; text-align: center; }
</style>
