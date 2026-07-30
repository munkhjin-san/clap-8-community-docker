<template>
    <div class="rf-canvas" :class="{ stacked }">
        <div v-for="(row, ri) in fieldRows" :key="ri" class="rf-row">
            <div
                v-for="field in row"
                :key="field.id"
                class="rf-block"
                :class="{ 'rf-heading-block': isLayoutType(field.input_type) }"
                :style="{ width: field.input_type === 'heading' ? '100%' : field.width + 'px' }"
            >
                <template v-if="isLayoutType(field.input_type)">
                    <FlowFieldInput :field="field" :model-value="null" />
                </template>
                <template v-else>
                    <label class="rf-label truncate" :title="field.label">
                        {{ field.label }}
                        <span v-if="field.is_required" class="rf-req">*</span>
                    </label>
                    <div :class="{ 'rf-disabled': !readonly && uneditable(field) }" :title="readonly ? undefined : lockHint(field)">
                        <FlowFieldInput
                            :field="field"
                            :users="users"
                            :projects="projects"
                            :readonly="isReadonly(field)"
                            :preview="true"
                            :record-id="recordId"
                            :model-value="values[field.id!]"
                            @update:model-value="onInput(field, $event)"
                            @lookup="onLookup"
                        />
                    </div>
                    <div v-if="errors[field.id!]" class="rf-err">{{ errors[field.id!] }}</div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
/**
 * The record form — the field layout shared by the record detail screen and the record list's
 * inline row editor, so a new field type or a new lock rule only has to be taught once.
 *
 * `values` and `errors` are the caller's own reactive objects and are written through directly.
 * That is deliberate: a lookup field fills several *other* fields at once, which an
 * update:modelValue-per-field contract can't express without the parent re-implementing the copy.
 */
import { computed } from 'vue'
import FlowFieldInput from './FlowFieldInput.vue'
import { isLayoutType } from '@/types/flow'
import { lockedByServer } from '@/utils/flowValidation'
import type { FlowField, FlowOptionUser, FlowOptionProject } from '@/types/flow'

const props = defineProps<{
    fields: FlowField[]
    values: Record<string, any>
    errors: Record<string, string | null>
    /** whole-form view mode — every field renders as its read-only display */
    readonly?: boolean
    /** server's answer for this record (see FlowRecordDto.editable_field_ids); null = not resolved */
    editableFieldIds?: number[] | null
    /** a record being created has no per-record locks to honour yet */
    isNew?: boolean
    users?: FlowOptionUser[]
    projects?: FlowOptionProject[]
    recordId?: number | null
    /** one field per line (narrow screens, and the list's inline panel) */
    stacked?: boolean
}>()

const visibleFields = computed(() => props.fields.filter((f) => !f.hidden))

/** Layout rows come from the builder: group by layout_row, order within the row. */
const fieldRows = computed<FlowField[][]>(() => {
    const map = new Map<number, FlowField[]>()
    for (const f of visibleFields.value) {
        const r = f.layout_row ?? 0
        if (!map.has(r)) map.set(r, [])
        map.get(r)!.push(f)
    }
    return [...map.keys()].sort((a, b) => a - b)
        .map((k) => map.get(k)!.slice().sort((a, b) => (a.order_number ?? 0) - (b.order_number ?? 0)))
})

const uneditable = (f: FlowField) =>
    !!f.validation?.disabled || lockedByServer(f, props.editableFieldIds, props.isNew)

const isReadonly = (f: FlowField) => !!props.readonly || f.input_type === 'formula' || uneditable(f)

const lockHint = (f: FlowField) => {
    if (f.validation?.disabled) return '入力できません（自動入力のみ）'
    if (lockedByServer(f, props.editableFieldIds, props.isNew)) return '現在のステータスまたは権限では編集できません。'
    return undefined
}

const onInput = (f: FlowField, v: any) => {
    props.values[f.id!] = v
    props.errors[f.id!] = null
}

const emptyValue = (f: FlowField) => {
    if (['checkbox', 'user', 'member', 'file', 'table'].includes(f.input_type)) return []
    if (f.input_type === 'toggle') return false
    if (f.input_type === 'number' || f.input_type === 'reference') return null
    return ''
}

/**
 * Lookup field copy (kintone-style): the reference field emits its picked record's values keyed by
 * source field key; fill each mapped destination field here. Empty `source` (lookup cleared) blanks
 * them. Formula/layout destinations are skipped defensively (they can't take a copied value), and so
 * are server-locked ones — a lookup must not write what a direct edit isn't allowed to.
 */
const onLookup = (payload: { mappings: { from: string; to: string }[]; source: Record<string, any> }) => {
    const cleared = Object.keys(payload.source).length === 0
    for (const m of payload.mappings) {
        const dest = props.fields.find((f) => f.key === m.to)
        if (!dest?.id || dest.input_type === 'formula' || isLayoutType(dest.input_type)) continue
        if (lockedByServer(dest, props.editableFieldIds, props.isNew)) continue
        props.values[dest.id] = cleared ? emptyValue(dest) : (payload.source[m.from] ?? emptyValue(dest))
        props.errors[dest.id] = null
    }
}
</script>

<style scoped>
.rf-canvas { width: max-content; min-width: 100%; }
.rf-row { display: flex; gap: 20px; margin-bottom: 20px; align-items: stretch; }
.rf-block { flex: 0 0 auto; box-sizing: border-box !important; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 5px; padding: 15px; }
.rf-heading-block { border: none; background: none; padding: 4px 0; }
.rf-label { display: block; font-size: 13px; color: var(--sub-color); margin-bottom: 15px; }
.rf-req { color: #e2574c; }
.rf-err { font-size: 11px; color: #e2574c; margin-top: 3px; }
.rf-disabled { cursor: not-allowed; opacity: 0.6; }
.rf-disabled > * { pointer-events: none; }

/* one field per line — the narrow record screen, and the list's inline panel */
.rf-canvas.stacked { width: 100%; }
.rf-canvas.stacked .rf-row { flex-direction: column; align-items: stretch; }
.rf-canvas.stacked .rf-block { width: 100% !important; }
</style>
