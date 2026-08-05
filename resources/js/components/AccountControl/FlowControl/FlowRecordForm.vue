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
                    <!-- The server withholds the value of a field this user has no 閲覧 on. Say so
                         explicitly: an empty input would read as "nobody filled this in", which is a
                         different and misleading statement. While creating, an auto-fill destination
                         says what WILL happen instead — otherwise the creator cannot tell the field is
                         filled for them and reasonably assumes it is broken. -->
                    <div v-if="hidden(field)" class="rf-hidden" :class="{ mask: hiddenText(field) === '••••••••' }" :title="hiddenHint(field)">{{ hiddenText(field) }}</div>
                    <div v-else class="rf-inputwrap" :class="{ 'rf-disabled': !readonly && uneditable(field), masked: autoFillMaskShown(field) }" :title="readonly ? undefined : lockHint(field)">
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
                        <!-- 暗号化フィールドの平文はブラウザに渡らないので、他の項目のように選んだ瞬間
                             値が現れない。長い注意書きを置くより、伏せ字を重ねて「入るもの」だと一目で
                             分かるようにする。pointer-events:none なので手入力の邪魔はしない。 -->
                        <span
                            v-if="autoFillMaskShown(field)"
                            class="rf-fauxmask"
                            title="選択したユーザーの値が保存時に自動入力されます（暗号化のため表示されません）"
                        >••••••••</span>
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
import { isLayoutType, isSecretType } from '@/types/flow'
import { applyLookupCopy, lockedByServer } from '@/utils/flowValidation'
import type { FlowField, FlowOptionUser, FlowOptionProject } from '@/types/flow'

const props = defineProps<{
    fields: FlowField[]
    values: Record<string, any>
    errors: Record<string, string | null>
    /** whole-form view mode — every field renders as its read-only display */
    readonly?: boolean
    /** server's answer for this record (see FlowRecordDto.editable_field_ids); null = not resolved */
    editableFieldIds?: number[] | null
    /** fields whose value the server withheld for lack of 閲覧 (FlowRecordDto.unviewable_field_ids) */
    unviewableFieldIds?: number[] | null
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

/**
 * No 閲覧 on this field, so the server sent no value for it.
 *
 * Driven by the server's list rather than re-deriving the permission here: the client has no business
 * deciding this, and a record being created has nothing withheld yet.
 */
const hidden = (f: FlowField) =>
    f.id != null && (props.unviewableFieldIds ?? []).includes(f.id)

/**
 * Destination field ids of every ユーザー/プロジェクト auto-fill mapping in this app.
 *
 * Read off the definition rather than passed in: the mappings are already here, and the form is the
 * only place that needs to phrase a withheld field differently depending on whether something is
 * going to fill it.
 */
const autoFillSourceOf = computed<Map<number, number>>(() => {
    const byKey = new Map(props.fields.filter((f) => f.id != null).map((f) => [f.key, f.id as number]))
    const out = new Map<number, number>()
    for (const f of props.fields) {
        if (!['user', 'member', 'project'].includes(f.input_type) || f.id == null) continue
        for (const m of f.validation?.field_mappings ?? []) {
            const destId = byKey.get(m.to)
            if (destId != null) out.set(destId, f.id)
        }
    }
    return out
})
const autoFilledIds = computed<number[]>(() => [...autoFillSourceOf.value.keys()])

/** Does the field that fills this one currently have something selected? */
const sourcePicked = (f: FlowField): boolean => {
    const srcId = f.id != null ? autoFillSourceOf.value.get(f.id) : undefined
    if (srcId == null) return false
    const v = props.values[srcId]
    return Array.isArray(v) ? v.length > 0 : (v !== null && v !== undefined && v !== '')
}

/**
 * 暗号化フィールドが自動入力先になっている場合。
 *
 * 平文はブラウザに渡らない（/flow_system_record は秘密の列を返さない）ので、ユーザーを選んだ瞬間に
 * 他の項目が埋まるのと違って、この欄だけ何も起きない。実際にはサーバが保存時に入れるのだが、画面上は
 * 「反応しない壊れた欄」と見分けが付かないので、そう明記する。入力欄は残す：自分で入力した値は
 * サーバ側でもマスタより優先される。
 */
const autoFilledSecret = (f: FlowField) =>
    !props.readonly && f.id != null && isSecretType(f.input_type) && autoFilledIds.value.includes(f.id)

/**
 * 伏せ字を重ねる条件：コピー元が選ばれていて、まだ自分で入力していないとき。
 *
 * 自分で入力し始めたら消す — そこは本物の入力値が優先される場所なので、偽の伏せ字が残ると
 * 「入力したのに反映されない」ように見える。
 */
const autoFillMaskShown = (f: FlowField): boolean => {
    if (!autoFilledSecret(f) || !sourcePicked(f)) return false
    const own = props.values[f.id!]
    const typed = (typeof own === 'string' || typeof own === 'number') && String(own).trim() !== ''
    return !typed
}

/**
 * 閲覧できない項目の表示。
 *
 * 自動入力先なら伏せ字だけにする：狭い項目では説明文が2行に折り返して読みづらく、「入るもの」だと
 * 一目で分かるほうが情報として足りている（詳細は title に出す）。保存済みで単に閲覧権限が無い場合は
 * 伏せ字にしない — そちらは「値があるが見せない」ではなく「権限が無い」という別の事実なので、
 * 言葉で言う必要がある。
 */
const hiddenText = (f: FlowField) =>
    props.isNew && f.id != null && autoFilledIds.value.includes(f.id)
        ? '••••••••'
        : '閲覧権限がありません'

const hiddenHint = (f: FlowField) =>
    props.isNew && f.id != null && autoFilledIds.value.includes(f.id)
        ? '保存時に自動入力されます。このフィールドの閲覧権限がないため値は表示されません。'
        : 'このフィールドの閲覧権限がありません'

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

const onLookup = (payload: { mappings: { from: string; to: string }[]; source: Record<string, any> }) =>
    applyLookupCopy(props.fields, props.values, props.errors, payload,
        { editableFieldIds: props.editableFieldIds, isNew: props.isNew })
</script>

<style scoped>
.rf-canvas { width: max-content; min-width: 100%; }
.rf-row { display: flex; gap: 20px; margin-bottom: 20px; align-items: stretch; }
.rf-block { flex: 0 0 auto; box-sizing: border-box !important; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 5px; padding: 15px; }
.rf-heading-block { border: none; background: none; padding: 4px 0; }
.rf-label { display: block; font-size: 13px; color: var(--sub-color); margin-bottom: 15px; }
.rf-req { color: #e2574c; }
.rf-inputwrap { position: relative; }
/* 入力欄のプレースホルダ位置に重ねる。クリックやフォーカスは下の input に通す */
.rf-fauxmask { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 13px; letter-spacing: 2px; color: var(--sub-color); }
/* 伏せ字を重ねている間は input 自身のプレースホルダを消す。両方が同じ位置に描かれて重なり、
   どちらも読めなくなっていた。フォーカスすれば伏せ字は消えるので、そこで本来の案内が戻る。 */
.rf-inputwrap.masked :deep(input)::placeholder { color: transparent; }
.rf-err { font-size: 11px; color: #e2574c; margin-top: 3px; }
/* reads as an absent value, not as a field you could fill: no input chrome, muted, italic */
.rf-hidden { font-size: 12px; color: var(--sub-color); font-style: italic; padding: 6px 0; cursor: default; }
/* 伏せ字のときは斜体をやめて字間を空ける（文章ではなく値の代わりなので） */
.rf-hidden.mask { font-style: normal; letter-spacing: 2px; font-size: 13px; }
.rf-disabled { cursor: not-allowed; opacity: 0.6; }
.rf-disabled > * { pointer-events: none; }

/* one field per line — the narrow record screen, and the list's inline panel */
.rf-canvas.stacked { width: 100%; }
.rf-canvas.stacked .rf-row { flex-direction: column; align-items: stretch; }
.rf-canvas.stacked .rf-block { width: 100% !important; }
</style>
