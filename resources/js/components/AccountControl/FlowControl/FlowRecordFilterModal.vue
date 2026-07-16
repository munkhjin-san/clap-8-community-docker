<template>
    <Modal persist @close="emit('close')">
        <template #title>フィルター設定</template>
        <template #content>
            <div class="rf-logic">
                <span class="rf-logic-label">条件の一致</span>
                <div class="rf-seg">
                    <button type="button" :class="{ on: local.logic === 'and' }" @click="local.logic = 'and'">すべて一致（AND）</button>
                    <button type="button" :class="{ on: local.logic === 'or' }" @click="local.logic = 'or'">いずれかに一致（OR）</button>
                </div>
            </div>

            <div v-if="!local.conditions.length" class="rf-empty">条件を追加してください。</div>
            <div v-for="(f, fi) in local.conditions" :key="fi" class="rf-cond">
                <FlowSearchSelect class="rf-cond-field" :model-value="f.field" :options="refOptions" :clearable="false" @update:model-value="(val) => onFieldChange(f, val as number | string)" />
                <select v-model="f.operator" class="custom-a-input !box-border rf-cond-op">
                    <option v-for="op in operatorsFor(f.field)" :key="op" :value="op">{{ opLabel(op) }}</option>
                </select>
                <div class="rf-cond-val">
                    <FilterValue v-if="needsValue(f.operator)" :field-ref="f.field" :operator="f.operator" :fields="fields" :users="users" :statuses="statusNames" v-model="f.values" />
                </div>
                <button class="rf-del" @click="local.conditions.splice(fi, 1)" title="削除"><CloseIcon size="9" /></button>
            </div>
            <button class="rf-add-cond" @click="addCondition" :disabled="!filterableRefs.length">＋ 条件を追加</button>

            <div class="rf-actions">
                <button v-if="local.conditions.length" class="rf-btn" @click="clearAll">条件をクリア</button>
                <div class="rf-actions-right">
                    <button class="rf-btn" @click="emit('close')">キャンセル</button>
                    <button class="rf-btn rf-primary" @click="apply">適用</button>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue'
import {
    isSystemColumn, isLayoutType, flowSystemColumns, flowSystemColumnLabel, FLOW_SYS_STATUS, FLOW_VIEW_OPERATOR_LABEL,
} from '@/types/flow'
import type { FlowField, FlowOptionUser, FlowViewOperator, FlowAdhocFilter } from '@/types/flow'
import { operatorsForType } from '@/utils/flowView'
import Modal from '@/components/Global/Modal.vue'
import FlowSearchSelect from './FlowSearchSelect.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import FilterValue from './FlowViewFilterValue.vue'

const props = defineProps<{
    fields: FlowField[]
    users?: FlowOptionUser[]
    hasStatus: boolean
    statusNames: string[]
    modelValue: FlowAdhocFilter
}>()
const emit = defineEmits<{ apply: [FlowAdhocFilter]; close: [] }>()

// edit a local copy — the parent only learns of changes when 適用 is pressed (キャンセル discards them)
const local = reactive<FlowAdhocFilter>({
    logic: props.modelValue.logic,
    conditions: props.modelValue.conditions.map((c) => ({ ...c, values: [...c.values] })),
})

const savedFields = computed(() => props.fields.filter((f) => f.id && !isLayoutType(f.input_type)))
const filterableRefs = computed<(number | string)[]>(() => [
    ...flowSystemColumns(props.hasStatus).map((c) => c.key),
    ...savedFields.value.map((f) => f.id!),
])
const refLabel = (ref: number | string): string =>
    isSystemColumn(ref)
        ? (flowSystemColumnLabel(String(ref)) ?? String(ref))
        : (props.fields.find((f) => f.id === Number(ref))?.label ?? '#' + ref)
const refType = (ref: number | string): string | undefined =>
    isSystemColumn(ref)
        ? (ref === '$record_number' ? 'number' : ref === FLOW_SYS_STATUS ? undefined : 'datetime')
        : props.fields.find((f) => f.id === Number(ref))?.input_type
const opLabel = (op: FlowViewOperator) => FLOW_VIEW_OPERATOR_LABEL[op]
const operatorsFor = (ref: number | string) => operatorsForType(refType(ref))
const needsValue = (op: FlowViewOperator) => op !== 'is_empty' && op !== 'not_empty'
const refOptions = computed(() => filterableRefs.value.map((r) => ({ value: r, label: refLabel(r) })))
const onFieldChange = (f: { field: number | string; operator: FlowViewOperator; values: any[] }, val: number | string) => {
    f.field = val
    const ops = operatorsFor(f.field)
    if (!ops.includes(f.operator)) f.operator = ops[0]
    f.values = []
}
const addCondition = () => {
    const ref = filterableRefs.value[0]
    if (ref === undefined) return
    local.conditions.push({ field: ref, operator: operatorsFor(ref)[0], values: [] })
}
const clearAll = () => { local.conditions.splice(0, local.conditions.length) }
const apply = () => emit('apply', { logic: local.logic, conditions: local.conditions })
</script>

<style scoped>
.rf-modal-title { font-size: 16px; }
.rf-logic { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.rf-logic-label { font-size: 12px; color: gray; flex-shrink: 0; }
.rf-seg { display: inline-flex; border: 1px solid var(--formBorder); border-radius: 7px; overflow: hidden; }
.rf-seg button { border: none; background: var(--background-color); color: var(--primary-color); font-size: 12px; padding: 7px 14px; cursor: pointer; }
.rf-seg button + button { border-left: 1px solid var(--formBorder); }
.rf-seg button.on { background: var(--primary-button, var(--primary-color)); color: #fff; }
.rf-empty { font-size: 12px; color: gray; padding: 6px 0 12px; }
.rf-cond { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
.rf-cond-field { min-width: 150px; }
.rf-cond-op { min-width: 130px; }
.rf-cond-val { flex: 1; min-width: 160px; }
.rf-del { border: none; background: none; color: gray; cursor: pointer; padding: 2px; display: flex; flex-shrink: 0; }
.rf-del:hover { color: tomato; }
.rf-add-cond { background: none; border: 1px dashed var(--formBorder); border-radius: 7px; padding: 7px 12px; font-size: 12px; color: var(--primary-color); cursor: pointer; }
.rf-add-cond:disabled { opacity: 0.5; cursor: default; }
.rf-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--calendarBorder); }
.rf-actions-right { display: flex; gap: 10px; margin-left: auto; }
.rf-btn { font-size: 13px; padding: 8px 18px; border-radius: 7px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; }
.rf-btn:hover { background: var(--bg3); }
.rf-primary { background: var(--primary-button, var(--primary-color)); color: #fff; border-color: transparent; }
.rf-primary:hover { background: var(--primary-button, var(--primary-color)); opacity: 0.88; }
</style>
