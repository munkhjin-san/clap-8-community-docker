<template>
    <div class="flow-view-tab">
        <!-- view list -->
        <div class="vt-list">
            <div class="vt-list-h">ビュー</div>
            <div
                v-for="(v, i) in def.views"
                :key="v.id ?? 'new' + i"
                class="vt-item"
                :class="{ on: i === selected }"
                @click="selected = i"
            >
                <span class="vt-item-name">{{ v.name || '(名称未設定)' }}</span>
                <span v-if="v.is_default" class="vt-badge">既定</span>
                <button class="vt-dup" @click.stop="duplicateView(i)" title="複製"><Duplicate :size="9" /></button>
                <button v-if="def.views.length > 1" class="vt-del" @click.stop="removeView(i)" title="削除"><CloseIcon size="9" /></button>
            </div>
            <button class="vt-add" @click="addView">＋ ビューを追加</button>
        </div>

        <!-- editor -->
        <div v-if="current" class="vt-editor">
            <div class="vt-row">
                <label>ビュー名</label>
                <input type="text" v-model="current.name" class="custom-a-input !box-border flex-1" placeholder="ビュー名">
            </div>
            <div class="vt-row">
                <label>既定のビュー</label>
                <span class="flow-sw flow-sw-lg" :class="{ on: current.is_default }" @click="makeDefault(selected)"></span>
                <span class="vt-hint">全員に最初に表示されます。</span>
            </div>

            <div class="vt-divider"></div>
            <div class="vt-sec">表示する列と順序</div>
            <div class="vt-cols">
                <div class="vt-col-panel">
                    <div class="vt-col-h">利用可能</div>
                    <div class="vt-col-scroll">
                        <div v-for="ref in availableRefs" :key="String(ref)" class="vt-chip" @click="addColumn(ref)">
                            <span>{{ refLabel(ref) }}</span><span class="vt-chip-add">＋</span>
                        </div>
                        <p v-if="!availableRefs.length" class="vt-empty">すべて追加済みです。</p>
                        <p v-if="hasUnsavedFields" class="vt-empty">未保存の項目は保存後に選択できます。</p>
                    </div>
                </div>
                <div class="vt-col-panel">
                    <div class="vt-col-h">表示中（上から順に列表示）</div>
                    <div class="vt-col-scroll">
                        <div v-for="(ref, ci) in selectedRefs" :key="String(ref)" class="vt-chip on">
                            <span>{{ refLabel(ref) }}</span>
                            <span class="vt-chip-tools">
                                <button :disabled="ci === 0" @click="moveColumn(ci, -1)" title="上へ">▲</button>
                                <button :disabled="ci === selectedRefs.length - 1" @click="moveColumn(ci, 1)" title="下へ">▼</button>
                                <button @click="removeColumn(ci)" title="外す"><CloseIcon size="8" /></button>
                            </span>
                        </div>
                        <p v-if="!selectedRefs.length" class="vt-empty">列を追加してください。</p>
                    </div>
                </div>
            </div>

            <div class="vt-divider"></div>
            <div class="vt-sec">フィルター（すべての条件に一致）</div>
            <div v-for="(f, fi) in current.filters" :key="fi" class="vt-cond">
                <FlowSearchSelect class="vt-cond-field" :model-value="f.field" :options="refOptions" :clearable="false" @update:model-value="(val) => onFilterField(f, val as number | string)" />
                <select v-model="f.operator" class="custom-a-input !box-border vt-cond-op">
                    <option v-for="op in operatorsFor(f.field)" :key="op" :value="op">{{ opLabel(op) }}</option>
                </select>
                <div class="vt-cond-val">
                    <FilterValue v-if="needsValue(f.operator)" :field-ref="f.field" :operator="f.operator" :fields="def.fields" :users="users" :statuses="statusNames" v-model="f.values" />
                </div>
                <button class="vt-del" @click="current.filters.splice(fi, 1)"><CloseIcon size="9" /></button>
            </div>
            <button class="vt-add-cond" @click="addFilter" :disabled="!filterableRefs.length">＋ 条件を追加</button>

            <div class="vt-divider"></div>
            <div class="vt-sec">並び替え</div>
            <div v-for="(s, si) in current.sort" :key="si" class="vt-cond">
                <FlowSearchSelect class="vt-cond-field" :model-value="s.field" :options="refOptions" :clearable="false" @update:model-value="(val) => s.field = val as number | string" />
                <select v-model="s.direction" class="custom-a-input !box-border vt-cond-op">
                    <option value="asc">昇順</option>
                    <option value="desc">降順</option>
                </select>
                <button class="vt-del" @click="current.sort.splice(si, 1)"><CloseIcon size="9" /></button>
            </div>
            <button class="vt-add-cond" @click="addSort" :disabled="!filterableRefs.length">＋ 並び替えを追加</button>
        </div>
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { computed, ref, watch } from 'vue'
import {
    isLayoutType, isSystemColumn, flowSystemColumns, flowSystemColumnLabel, FLOW_SYS_STATUS, FLOW_VIEW_OPERATOR_LABEL,
} from '@/types/flow'
import type { BuilderDefinition, BuilderView, FlowViewOperator, FlowOptionUser } from '@/types/flow'
import { operatorsForType, allColumnRefs } from '@/utils/flowView'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import Duplicate from '@/components/Icons/Duplicate.vue'
import FilterValue from './FlowViewFilterValue.vue'
import FlowSearchSelect from './FlowSearchSelect.vue'

const props = defineProps<{ def: BuilderDefinition; users?: FlowOptionUser[] }>()

const selected = ref(0)
const current = computed<BuilderView | null>(() => props.def.views[selected.value] ?? null)

const savedFields = computed(() => props.def.fields.filter((f) => f.id && !isLayoutType(f.input_type)))
const hasUnsavedFields = computed(() => props.def.fields.some((f) => !f.id && !isLayoutType(f.input_type)))

const hasStatus = computed(() => !!props.def.use_status_flow)
const statusNames = computed(() => props.def.statuses.map((s) => s.name).filter(Boolean))
const systemRefs = computed(() => flowSystemColumns(hasStatus.value).map((c) => c.key))
const allRefs = computed<(number | string)[]>(() => [...systemRefs.value, ...savedFields.value.map((f) => f.id!)])

// Columns shown as "selected" — empty stored list means "all" (the seeded すべて view).
const selectedRefs = computed<(number | string)[]>(() =>
    current.value && current.value.columns.length ? current.value.columns : (current.value ? allColumnRefs(savedFields.value, hasStatus.value) : [])
)
const availableRefs = computed<(number | string)[]>(() => {
    const set = new Set(selectedRefs.value.map(String))
    return allRefs.value.filter((r) => !set.has(String(r)))
})
const filterableRefs = computed<(number | string)[]>(() => allRefs.value)

const refLabel = (ref: number | string): string =>
    isSystemColumn(ref)
        ? (flowSystemColumnLabel(String(ref)) ?? String(ref))
        : (props.def.fields.find((f) => f.id === Number(ref))?.label ?? '#' + ref)
const refType = (ref: number | string): string | undefined =>
    isSystemColumn(ref)
        ? (ref === '$record_number' ? 'number' : ref === FLOW_SYS_STATUS ? undefined : 'datetime')
        : props.def.fields.find((f) => f.id === Number(ref))?.input_type
const opLabel = (op: FlowViewOperator) => FLOW_VIEW_OPERATOR_LABEL[op]
const operatorsFor = (ref: number | string) => operatorsForType(refType(ref))
const needsValue = (op: FlowViewOperator) => op !== 'is_empty' && op !== 'not_empty'
// field/system-column options for the searchable field pickers (native ref values: id number or $key)
const refOptions = computed(() => filterableRefs.value.map((r) => ({ value: r, label: refLabel(r) })))

/* ---- column ops (materialise "all" to explicit before mutating) ---- */
const ensureExplicit = () => {
    if (current.value && !current.value.columns.length) {
        current.value.columns = allColumnRefs(savedFields.value, hasStatus.value)
    }
}
const addColumn = (ref: number | string) => { ensureExplicit(); current.value!.columns.push(ref) }
const removeColumn = (ci: number) => { ensureExplicit(); current.value!.columns.splice(ci, 1) }
const moveColumn = (ci: number, dir: number) => {
    ensureExplicit()
    const cols = current.value!.columns
    const j = ci + dir
    if (j < 0 || j >= cols.length) return
    ;[cols[ci], cols[j]] = [cols[j], cols[ci]]
}

/* ---- filters / sort ---- */
const onFilterField = (f: { field: number | string; operator: FlowViewOperator; values: any[] }, val: number | string) => {
    f.field = val
    const ops = operatorsFor(f.field)
    if (!ops.includes(f.operator)) f.operator = ops[0]
    f.values = []
}
const addFilter = () => {
    const ref = filterableRefs.value[0]
    if (ref === undefined) return
    current.value!.filters.push({ field: ref, operator: operatorsFor(ref)[0], values: [] })
}
const addSort = () => {
    const ref = filterableRefs.value[0]
    if (ref === undefined) return
    current.value!.sort.push({ field: ref, direction: 'asc' })
}

/* ---- view list ops ---- */
const addView = () => {
    props.def.views.push({ name: 'ビュー' + (props.def.views.length + 1), is_default: false, columns: [], filters: [], sort: [] })
    selected.value = props.def.views.length - 1
}
/** Copy a view's columns/filters/sort into a new one. Deep-cloned so editing the copy can't
 *  mutate the source's nested filter/sort objects; never inherits 既定 (only one view holds it). */
const duplicateView = (i: number) => {
    const src = props.def.views[i]
    props.def.views.splice(i + 1, 0, {
        name: `${src.name || 'ビュー'} のコピー`,
        is_default: false,
        columns: [...src.columns],
        filters: src.filters.map((f) => ({ ...f, values: [...f.values] })),
        sort: src.sort.map((s) => ({ ...s })),
    })
    selected.value = i + 1
}

const removeView = (i: number) => {
    const wasDefault = props.def.views[i].is_default
    props.def.views.splice(i, 1)
    if (selected.value >= props.def.views.length) selected.value = props.def.views.length - 1
    if (wasDefault && props.def.views.length && !props.def.views.some((v) => v.is_default)) {
        props.def.views[0].is_default = true
    }
}
const makeDefault = (i: number) => {
    props.def.views.forEach((v, j) => (v.is_default = j === i))
}

watch(() => props.def.views.length, () => {
    if (selected.value >= props.def.views.length) selected.value = Math.max(0, props.def.views.length - 1)
})
</script>

<style scoped>
.flow-view-tab { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 16px; align-items: start; }
.vt-list { background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 8px; }
.vt-list-h { font-size: 12px; color: gray; padding: 4px 6px 8px; }
.vt-item { display: flex; align-items: center; gap: 6px; padding: 9px 10px; border-radius: 7px; cursor: pointer; font-size: 13px; }
.vt-item:hover { background: var(--bg3); }
.vt-item.on { background: var(--bg3); border: 1px solid var(--primary-color); }
.vt-item-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vt-badge { font-size: 10px; color: var(--primary-color); background: var(--bg3); border: 1px solid var(--primary-color); padding: 0 5px; border-radius: 8px; }
.vt-del { border: none; background: none; color: gray; cursor: pointer; padding: 2px; display: flex; }
.vt-dup { border: none; background: none; color: gray; cursor: pointer; padding: 2px; display: flex; fill: currentColor; }
.vt-dup:hover { color: var(--primary-color); }
.vt-add { width: 100%; box-sizing: border-box !important; margin-top: 6px; background: none; border: 1px dashed var(--formBorder); border-radius: 7px; padding: 8px; font-size: 12px; color: var(--primary-color); cursor: pointer; }

.vt-editor { background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 16px; }
.vt-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.vt-row label { font-size: 12px; color: gray; width: 96px; flex-shrink: 0; }
.vt-hint { font-size: 11px; color: gray; }
.vt-sec { font-size: 13px; font-weight: 500; margin-bottom: 10px; color: var(--primary-color); }
.vt-divider { height: 1px; background: var(--calendarBorder); margin: 16px 0; }
.vt-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.vt-col-panel { border: 1px solid var(--calendarBorder); border-radius: 8px; overflow: hidden; }
.vt-col-h { font-size: 11px; color: gray; padding: 8px 10px; background: var(--bg3); border-bottom: 1px solid var(--calendarBorder); }
.vt-col-scroll { max-height: 300px; overflow-y: auto; padding: 8px; display: flex; flex-direction: column; gap: 5px; }
.vt-chip { display: flex; align-items: center; gap: 6px; padding: 7px 10px; border: 1px solid var(--calendarBorder); border-radius: 6px; font-size: 13px; cursor: pointer; background: var(--background-color); }
.vt-chip span:first-child { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vt-chip:hover { border-color: var(--primary-color); }
.vt-chip.on { cursor: default; }
.vt-chip.on:hover { border-color: var(--calendarBorder); }
.vt-chip-add { color: var(--primary-color); font-weight: 600; }
.vt-chip-tools { display: flex; gap: 2px; flex-shrink: 0; }
.vt-chip-tools button { border: none; background: none; color: gray; cursor: pointer; font-size: 10px; padding: 2px 4px; border-radius: 4px; display: flex; align-items: center; }
.vt-chip-tools button:hover:not(:disabled) { background: var(--bg3); color: var(--primary-color); }
.vt-chip-tools button:disabled { opacity: 0.3; cursor: default; }
.vt-empty { font-size: 11px; color: gray; padding: 6px; }
.vt-cond { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
.vt-cond-field { min-width: 150px; }
.vt-cond-op { min-width: 120px; }
.vt-cond-val { flex: 1; min-width: 160px; }
.vt-add-cond { background: none; border: 1px dashed var(--formBorder); border-radius: 7px; padding: 7px 12px; font-size: 12px; color: var(--primary-color); cursor: pointer; }
.vt-add-cond:disabled { opacity: 0.5; cursor: default; }
@media (max-width: 900px) { .flow-view-tab { grid-template-columns: 1fr; } .vt-cols { grid-template-columns: 1fr; } }
</style>
