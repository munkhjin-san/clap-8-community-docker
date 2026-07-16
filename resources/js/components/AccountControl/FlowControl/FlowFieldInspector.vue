<template>
    <div class="insp-inner">
        <!-- single table-column editor (a column is selected) -->
        <template v-if="columnMode">
            <div class="insp-h">
                <FlowFieldIcon :type="col0.input_type" :size="15" />
                <span>列の設定</span>
            </div>
            <button class="col-back" @click="emit('update:columnKey', null)">← 列一覧へ戻る</button>
            <div class="irow">
                <label>列名</label>
                <input type="text" v-model="col0.label" class="custom-a-input !box-border flex-1" placeholder="列名"
                    @focus="renameFrom = col0.label" @change="commitColumnRename(col0)">
            </div>
            <div class="irow">
                <label>種類</label>
                <select v-model="col0.input_type" @change="onColTypeChange(col0)" class="custom-a-input !box-border flex-1">
                    <option v-for="t in COLUMN_TYPES" :key="t.type" :value="t.type">{{ t.label }}</option>
                </select>
            </div>

            <template v-if="colHasOptions(col0)">
                <div class="sec" style="margin-top: 10px">選択肢</div>
                <div v-for="(o, oi) in col0.options || []" :key="oi" class="flex items-center gap-[6px] mt-[5px]">
                    <input type="text" :value="o" @input="setColOption(col0, oi, ($event.target as HTMLInputElement).value)" placeholder="選択肢" class="custom-a-input !box-border flex-1">
                    <button class="sremove" @click="removeColOption(col0, oi)"><CloseIcon size="9" /></button>
                </div>
                <button class="flow-ghost-btn mt-[6px]" @click="addColOption(col0)">＋ 選択肢</button>
            </template>

            <template v-if="col0.input_type === 'formula'">
                <div class="sec" style="margin-top: 10px">計算式</div>
                <FlowFormulaEditor v-model="col0.formula" :fields="colFormulaVars(col0)" :result-type="col0.result_type || 'number'" />
                <div class="irow" style="margin-top: 8px">
                    <label>結果の種類</label>
                    <select v-model="col0.result_type" class="custom-a-input !box-border flex-1">
                        <option value="number">数値</option>
                        <option value="text">文字</option>
                        <option value="toggle">オン/オフ</option>
                    </select>
                </div>
            </template>

            <template v-else-if="col0.input_type === 'reference'">
                <div class="sec" style="margin-top: 10px">参照先アプリ</div>
                <select v-model.number="col0.target_definition_id" @change="onColRefTarget(col0)" class="custom-a-input !box-border w-full">
                    <option :value="null">参照先アプリを選択</option>
                    <option v-for="a in refApps" :key="a.id" :value="a.id">{{ a.name }}</option>
                </select>
                <select v-if="col0.target_definition_id" v-model="col0.label_field" class="custom-a-input !box-border w-full mt-[6px]">
                    <option :value="null">レコード番号</option>
                    <option v-for="f in colRefFields(col0.target_definition_id)" :key="f.key" :value="f.key">{{ f.label }}</option>
                </select>
            </template>

            <div v-if="col0.input_type !== 'formula'" class="irow" style="margin-top: 10px">
                <label>必須</label>
                <span class="flow-sw" :class="{ on: col0.required }" @click="col0.required = !col0.required"></span>
            </div>

            <div class="divider"></div>
            <button class="col-del" :disabled="columns.length <= 1" @click="deleteSelectedColumn">この列を削除</button>
        </template>

        <template v-else>
        <div class="insp-h">
            <FlowFieldIcon :type="field.input_type" :size="15" />
            <span>{{ typeLabel(field.input_type) }}の設定</span>
        </div>

        <div class="irow" v-if="field.input_type !== 'spacer' && field.input_type !== 'divider'" :class="{ 'items-start': field.input_type === 'label' }">
            <label>{{ labelFieldName }}</label>
            <textarea v-if="field.input_type === 'label'" v-model="field.label" rows="3" class="custom-a-input !box-border flex-1" placeholder="説明や注意書きを入力"></textarea>
            <input v-else type="text" v-model="field.label" class="custom-a-input !box-border flex-1"
                @focus="renameFrom = field.label" @change="commitFieldRename(field.label)">
        </div>
        <div class="irow" v-if="!isLayout">
            <label>フィールドキー</label>
            <input type="text" v-model="field.key" class="custom-a-input !box-border flex-1"
                @focus="renameFrom = field.key" @change="commitFieldRename(field.key)">
        </div>
        <div class="irow" v-if="!isLayout">
            <label>必須</label>
            <span class="flow-sw" :class="{ on: field.is_required }" @click="field.is_required = !field.is_required"></span>
        </div>

        <template v-if="field.input_type === 'spacer' || field.input_type === 'divider'">
            <div class="irow">
                <label>幅</label>
                <div class="flex items-center gap-[6px]">
                    <input type="number" min="140" v-model.number="field.width" class="custom-a-input !box-border !w-[110px]">
                    <span class="text-[12px] text-gray-500">px</span>
                </div>
            </div>
            <div class="irow" v-if="field.input_type === 'spacer'">
                <label>高さ</label>
                <div class="flex items-center gap-[6px]">
                    <input type="number" min="1" v-model.number="v.height" class="custom-a-input !box-border !w-[110px]">
                    <span class="text-[12px] text-gray-500">px</span>
                </div>
            </div>
            <div class="irow" v-if="field.input_type === 'divider'">
                <label>線の種類</label>
                <select v-model="v.line_style" class="custom-a-input !box-border flex-1">
                    <option value="solid">実線</option>
                    <option value="dashed">破線</option>
                    <option value="dotted">点線</option>
                </select>
            </div>
        </template>

        <template v-if="hasRules">
            <div class="divider"></div>
            <div class="sec">入力ルール</div>

            <template v-if="field.input_type === 'short' || field.input_type === 'long'">
                <div class="irow">
                    <label>文字数</label>
                    <div class="minmax">
                        <input type="number" min="0" v-model.number="v.min_length" placeholder="最小" class="custom-a-input !box-border">
                        <span class="tilde">〜</span>
                        <input type="number" min="0" v-model.number="v.max_length" placeholder="最大" class="custom-a-input !box-border">
                    </div>
                </div>
                <div class="irow" v-if="field.input_type === 'short'">
                    <label>形式</label>
                    <select v-model="v.format" class="custom-a-input !box-border flex-1">
                        <option value="none">指定なし</option>
                        <option value="email">メールアドレス</option>
                        <option value="tel">電話番号</option>
                        <option value="url">URL</option>
                    </select>
                </div>
            </template>

            <template v-else-if="field.input_type === 'number'">
                <div class="irow">
                    <label>値の範囲</label>
                    <div class="minmax">
                        <input type="number" v-model.number="v.min" placeholder="最小" class="custom-a-input !box-border">
                        <span class="tilde">〜</span>
                        <input type="number" v-model.number="v.max" placeholder="最大" class="custom-a-input !box-border">
                    </div>
                </div>
                <div class="irow">
                    <label>整数のみ</label>
                    <span class="flow-sw" :class="{ on: v.integer_only }" @click="v.integer_only = !v.integer_only"></span>
                </div>
            </template>

            <template v-else-if="field.input_type === 'checkbox'">
                <div class="irow">
                    <label>選択数</label>
                    <div class="minmax">
                        <input type="number" min="0" v-model.number="v.min_select" placeholder="最小" class="custom-a-input !box-border">
                        <span class="tilde">〜</span>
                        <input type="number" min="0" v-model.number="v.max_select" placeholder="最大" class="custom-a-input !box-border">
                    </div>
                </div>
            </template>

            <template v-else-if="field.input_type === 'file'">
                <div class="vcol">
                    <label class="vlabel">受付形式</label>
                    <div class="chips">
                        <button v-for="a in fileAccepts" :key="a.value" class="achip" :class="{ on: (v.accept || []).includes(a.value) }" @click="toggleAccept(a.value)">{{ a.label }}</button>
                    </div>
                </div>
                <div class="irow">
                    <label>最大サイズ</label>
                    <div class="flex items-center gap-[6px]">
                        <input type="number" min="0" v-model.number="v.max_size_mb" placeholder="制限なし" class="custom-a-input !box-border !w-[100px]">
                        <span class="text-[12px] text-gray-500">MB</span>
                    </div>
                </div>
                <div class="irow">
                    <label>複数可</label>
                    <span class="flow-sw" :class="{ on: v.allow_multiple }" @click="v.allow_multiple = !v.allow_multiple"></span>
                </div>
            </template>

            <template v-else-if="field.input_type === 'user' || field.input_type === 'member'">
                <div class="irow">
                    <label>複数選択</label>
                    <span class="flow-sw" :class="{ on: v.multiple !== false }" @click="v.multiple = v.multiple === false"></span>
                </div>
            </template>

            <template v-else-if="field.input_type === 'date'">
                <div class="irow">
                    <label>日付の範囲</label>
                    <div class="minmax">
                        <input type="date" v-model="v.min_date" class="custom-a-input !box-border">
                        <span class="tilde">〜</span>
                        <input type="date" v-model="v.max_date" class="custom-a-input !box-border">
                    </div>
                </div>
            </template>

            <template v-else-if="field.input_type === 'datetime'">
                <div class="irow">
                    <label>日時の範囲</label>
                    <div class="minmax">
                        <input type="datetime-local" v-model="v.min_date" class="custom-a-input !box-border">
                        <span class="tilde">〜</span>
                        <input type="datetime-local" v-model="v.max_date" class="custom-a-input !box-border">
                    </div>
                </div>
            </template>

            <template v-else-if="field.input_type === 'time'">
                <div class="irow">
                    <label>時刻の範囲</label>
                    <div class="minmax">
                        <input type="time" v-model="v.min_time" class="custom-a-input !box-border">
                        <span class="tilde">〜</span>
                        <input type="time" v-model="v.max_time" class="custom-a-input !box-border">
                    </div>
                </div>
            </template>
        </template>

        <template v-if="hasOptions">
            <div class="divider"></div>
            <div class="sec">選択肢</div>
            <div v-for="(opt, oi) in field.options || []" :key="oi" class="flex items-center gap-[6px] mt-[6px]">
                <input type="text" :value="opt" @input="setOption(oi, ($event.target as HTMLInputElement).value)" class="custom-a-input !box-border flex-1">
                <button class="sremove" @click="removeOption(oi)"><CloseIcon size="9" /></button>
            </div>
            <button class="flow-ghost-btn mt-[8px]" @click="addOption">＋ 選択肢を追加</button>
        </template>

        <template v-if="hasDefault">
            <div class="divider"></div>
            <div class="sec">初期値（新規作成時）</div>

            <input v-if="field.input_type === 'short'" type="text" v-model="v.default" class="custom-a-input !box-border w-full" placeholder="初期テキスト">
            <textarea v-else-if="field.input_type === 'long'" v-model="v.default" rows="2" class="custom-a-input !box-border w-full" placeholder="初期テキスト"></textarea>
            <input v-else-if="field.input_type === 'number'" type="number" v-model.number="v.default" class="custom-a-input !box-border w-full" placeholder="初期値">

            <div v-else-if="field.input_type === 'toggle'" class="irow" style="margin: 0">
                <label>初期状態</label>
                <span class="flow-sw" :class="{ on: v.default }" @click="v.default = !v.default"></span>
            </div>

            <select v-else-if="field.input_type === 'select' || field.input_type === 'radio'" v-model="v.default" class="custom-a-input !box-border w-full">
                <option :value="null">なし</option>
                <option v-for="o in field.options || []" :key="o" :value="o">{{ o }}</option>
            </select>

            <div v-else-if="field.input_type === 'checkbox'" class="def-checks">
                <label v-for="o in field.options || []" :key="o" class="fi-opt">
                    <input type="checkbox" :checked="defaultArray.includes(o)" @change="toggleDefault(o)"> {{ o }}
                </label>
                <span v-if="!(field.options || []).length" class="text-[12px] text-gray-400">選択肢を先に追加してください。</span>
            </div>

            <template v-else-if="field.input_type === 'date' || field.input_type === 'datetime' || field.input_type === 'time'">
                <div class="irow" style="margin: 0">
                    <label>現在日時にする</label>
                    <span class="flow-sw" :class="{ on: v.default_now }" @click="v.default_now = !v.default_now"></span>
                </div>
                <p class="def-hint">オンにすると作成時の日時が自動で入ります。</p>
            </template>

            <div v-else-if="field.input_type === 'user' || field.input_type === 'member'" class="irow" style="margin: 0">
                <label>作成者を初期値</label>
                <span class="flow-sw" :class="{ on: v.default_me }" @click="v.default_me = !v.default_me"></span>
            </div>
        </template>

        <template v-if="field.input_type === 'formula'">
            <div class="divider"></div>
            <div class="sec">計算式</div>
            <FlowFormulaEditor v-model="field.formula" :fields="referenceableFields" :result-type="field.result_type" />
            <div class="irow" style="margin-top: 10px">
                <label>結果の種類</label>
                <select v-model="field.result_type" class="custom-a-input !box-border flex-1">
                    <option value="number">数値</option>
                    <option value="text">文字</option>
                    <option value="toggle">オン/オフ</option>
                </select>
            </div>
        </template>

        <template v-if="field.input_type === 'table'">
            <div class="divider"></div>
            <div class="sec">列</div>
            <div class="col-list">
                <div
                    v-for="(col, ci) in columns"
                    :key="col.key"
                    class="col-item"
                    :class="{ dragging: colDragIndex === ci, dropto: colOverIndex === ci && colDragIndex !== null }"
                    role="button"
                    draggable="true"
                    @dragstart="onColDragStart(ci, $event)"
                    @dragover="onColDragOver(ci, $event)"
                    @drop="onColDrop(ci, $event)"
                    @dragend="onColDragEnd"
                    @click="emit('update:columnKey', col.key)"
                >
                    <span class="col-grip" title="ドラッグで並べ替え" aria-hidden="true">⋮⋮</span>
                    <FlowFieldIcon :type="col.input_type" :size="13" />
                    <span class="col-item-lbl">{{ col.label || '列' + (ci + 1) }}</span>
                    <span class="col-item-type">{{ typeLabel(col.input_type) }}</span>
                    <span class="col-move" @click.stop>
                        <button type="button" class="col-arrow" :disabled="ci === 0" title="上へ" @click="moveColumn(ci, -1)">▲</button>
                        <button type="button" class="col-arrow" :disabled="ci === columns.length - 1" title="下へ" @click="moveColumn(ci, 1)">▼</button>
                    </span>
                </div>
            </div>
            <button class="flow-ghost-btn mt-[8px]" @click="addColumn">＋ 列を追加</button>
            <p class="def-hint">列名や表の列をクリックすると、その列の設定を編集できます。</p>
        </template>

        <template v-if="field.input_type === 'reference'">
            <div class="divider"></div>
            <div class="sec">参照先アプリ</div>
            <div class="irow">
                <label>アプリ</label>
                <select v-model.number="v.target_definition_id" @change="onRefTargetChange" class="custom-a-input !box-border flex-1">
                    <option :value="null">選択してください</option>
                    <option v-for="a in refApps" :key="a.id" :value="a.id">{{ a.name }}</option>
                </select>
            </div>
            <div class="irow" v-if="v.target_definition_id">
                <label>表示する項目</label>
                <select v-model="v.label_field" class="custom-a-input !box-border flex-1">
                    <option :value="null">レコード番号</option>
                    <option v-for="f in refTargetFields" :key="f.key" :value="f.key">{{ f.label }}</option>
                </select>
            </div>
            <p v-if="v.target_definition_id" class="def-hint">レコードを選ぶと、そのレコードの「{{ refLabelName }}」が表示されます。</p>
        </template>
        </template>
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { computed, ref, watch } from 'vue'
import { FLOW_TYPE_LABEL, FLOW_FILE_ACCEPT, FLOW_FIELD_TYPES, isLayoutType } from '@/types/flow'
import type { FlowField, FlowFieldValidation, TableColumn, FlowAppTool } from '@/types/flow'
import { referencingFormulas, referencedDeleteMessage, renameFieldRefEverywhere, renameColumnRefInTable, pdfToolsReferencingColumn } from '@/utils/flowFormulaRefs'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import FlowFieldIcon from './FlowFieldIcon.vue'
import FlowFormulaEditor from './FlowFormulaEditor.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'

const props = defineProps<{ field: FlowField; fields?: FlowField[]; tools?: FlowAppTool[]; columnKey?: string | null }>()
const emit = defineEmits<{ 'update:columnKey': [key: string | null] }>()
const api = useApi()
const dialog = useDialog()

// Renaming a field/column key or label would orphan formulas that reference the old name
// (they'd silently compute 0). Capture the value on focus, remap referencing formulas on commit.
const renameFrom = ref('')
const commitFieldRename = (newName: string) => {
    const from = renameFrom.value
    renameFrom.value = ''
    if (from && from !== newName) renameFieldRefEverywhere(props.fields ?? [], from, newName)
}
const commitColumnRename = (col: TableColumn) => {
    const from = renameFrom.value
    renameFrom.value = ''
    if (from && from !== col.label) renameColumnRefInTable(props.field, from, col.label)
}

/* ---- reference field: target app + label field ---- */
const refApps = ref<{ id: number; name: string }[]>([])
const refTargetFields = ref<{ key: string; label: string; input_type: string }[]>([])
const REF_LABEL_SKIP = ['heading', 'label', 'spacer', 'divider', 'table', 'reference', 'file']
const loadRefApps = async () => {
    if (refApps.value.length) return
    const data = await api.get('/flow_definitions')
    refApps.value = (data ?? []).map((d: any) => ({ id: d.id, name: d.name }))
}
const loadRefFields = async (id: number | null | undefined) => {
    refTargetFields.value = []
    if (!id) return
    const data = await api.get(`/flow_definition_fields/${id}`)
    refTargetFields.value = (data?.fields ?? []).filter((f: any) => !REF_LABEL_SKIP.includes(f.input_type))
}

const fileAccepts = FLOW_FILE_ACCEPT
const typeLabel = (t: string) => FLOW_TYPE_LABEL[t] ?? t
const hasOptions = computed(() => ['select', 'radio', 'checkbox'].includes(props.field.input_type))
const isLayout = computed(() => isLayoutType(props.field.input_type))
const DEFAULT_TYPES = ['short', 'long', 'number', 'select', 'radio', 'checkbox', 'toggle', 'date', 'datetime', 'time', 'user', 'member']
const hasDefault = computed(() => DEFAULT_TYPES.includes(props.field.input_type))
const defaultArray = computed<any[]>(() => (Array.isArray(v.value.default) ? v.value.default : []))
const toggleDefault = (o: string) => {
    const next = defaultArray.value.slice()
    const i = next.indexOf(o)
    if (i >= 0) next.splice(i, 1)
    else next.push(o)
    v.value.default = next
}
const labelFieldName = computed(() =>
    props.field.input_type === 'heading' ? '見出し文' : props.field.input_type === 'label' ? 'テキスト' : 'ラベル'
)
const RULE_TYPES = ['short', 'long', 'number', 'date', 'datetime', 'time', 'checkbox', 'file', 'user', 'member']
const hasRules = computed(() => RULE_TYPES.includes(props.field.input_type))

// Other formula fields ARE referenceable (chains compute multi-pass server-side) — only self and layout parts are excluded.
const referenceableFields = computed(() =>
    (props.fields ?? []).filter((f) => f.key !== props.field.key && !isLayoutType(f.input_type))
)

watch(() => props.field, (f) => {
    if (!f) return
    if (!f.validation) f.validation = {}
    if (f.input_type === 'formula' && !f.result_type) f.result_type = 'number'
    if (f.input_type === 'reference') {
        loadRefApps()
        loadRefFields(f.validation.target_definition_id ?? null)
    }
    if (f.input_type === 'table') {
        const cols = Array.isArray(f.validation.columns) ? f.validation.columns : []
        if (cols.some((c: any) => c.input_type === 'reference')) {
            loadRefApps()
            cols.forEach((c: any) => { if (c.input_type === 'reference') loadColRefFields(c.target_definition_id) })
        }
    }
}, { immediate: true })
const v = computed<FlowFieldValidation>(() => props.field.validation as FlowFieldValidation)

const onRefTargetChange = () => {
    v.value.label_field = null
    loadRefFields(v.value.target_definition_id ?? null)
}
const refLabelName = computed(() => {
    const k = v.value.label_field
    if (!k) return 'レコード番号'
    return refTargetFields.value.find((f) => f.key === k)?.label ?? k
})

const toggleAccept = (val: string) => {
    if (!v.value.accept) v.value.accept = []
    const i = v.value.accept.indexOf(val)
    if (i >= 0) v.value.accept.splice(i, 1)
    else v.value.accept.push(val)
}

const setOption = (oi: number, val: string) => { if (props.field.options) props.field.options[oi] = val }
const addOption = () => {
    if (!props.field.options) props.field.options = []
    props.field.options.push(`選択肢${props.field.options.length + 1}`)
}
const removeOption = (oi: number) => props.field.options?.splice(oi, 1)

/* ---- table columns ---- */
// 'table' stays excluded (no nested tables). formula + reference are allowed as columns.
const COLUMN_TYPES = FLOW_FIELD_TYPES.filter((t) => !isLayoutType(t.type) && t.type !== 'table')
// Variables offered to a calc column's formula editor: sibling columns + top-level fields.
// Formula columns/fields are referenceable (intra-row chains + cross-level refs compute
// multi-pass server-side); only the column itself, the owning table, and layout parts are excluded.
const colFormulaVars = (col: TableColumn) => [
    ...columns.value.filter((c) => c.key !== col.key && !isLayoutType(c.input_type)),
    ...((props.fields ?? []).filter((f) => f.key !== props.field.key && !isLayoutType(f.input_type))),
] as any
const OPTION_TYPES = ['select', 'radio', 'checkbox']
const colHasOptions = (col: TableColumn) => OPTION_TYPES.includes(col.input_type)
const columns = computed<TableColumn[]>({
    get: () => {
        if (!Array.isArray(v.value.columns)) v.value.columns = []
        return v.value.columns
    },
    set: (cols) => { v.value.columns = cols },
})
const genColKey = () => {
    const used = new Set(columns.value.map((c) => c.key))
    let i = 1
    while (used.has(`c${i}`)) i++
    return `c${i}`
}
const addColumn = () => {
    const key = genColKey()
    columns.value.push({ key, label: `列${columns.value.length + 1}`, input_type: 'short', options: null })
    emit('update:columnKey', key) // auto-select the new column for editing
}

/* ---- column reorder: up/down arrows + drag (scoped to this list) ---- */
const moveColumn = (ci: number, dir: -1 | 1) => {
    const to = ci + dir
    if (to < 0 || to >= columns.value.length) return
    const [col] = columns.value.splice(ci, 1)
    columns.value.splice(to, 0, col)
}
const colDragIndex = ref<number | null>(null)
const colOverIndex = ref<number | null>(null)
const onColDragStart = (ci: number, e: DragEvent) => {
    colDragIndex.value = ci
    e.stopPropagation() // keep this drag inside the column list (never reaches the form canvas)
    if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/flow-col', String(ci)) }
}
const onColDragOver = (ci: number, e: DragEvent) => {
    if (colDragIndex.value === null) return // ignore drags that didn't start in this list
    e.preventDefault()
    e.stopPropagation()
    colOverIndex.value = ci
}
const onColDrop = (ci: number, e: DragEvent) => {
    if (colDragIndex.value === null) return
    e.preventDefault()
    e.stopPropagation()
    const from = colDragIndex.value
    if (from !== ci) {
        const [col] = columns.value.splice(from, 1)
        columns.value.splice(ci, 0, col)
    }
    colDragIndex.value = null
    colOverIndex.value = null
}
const onColDragEnd = () => { colDragIndex.value = null; colOverIndex.value = null }
// Warn when deleting a column that other formulas reference: sibling calc columns resolve it
// bare ([列名]), top-level aggregates as [テーブル.列名]. Dangling refs compute as 0.
const confirmColumnDelete = async (col: TableColumn): Promise<boolean> => {
    const f = props.field
    const exclude = { fieldKey: f.key, columnKey: col.key }
    const hits = [
        // bare refs only resolve within the same table's rows — scan just this table
        ...referencingFormulas([f], [col.key, col.label], [], exclude),
        // dotted refs can appear anywhere
        ...referencingFormulas(props.fields ?? [f], [
            `${f.key}.${col.key}`, `${f.key}.${col.label}`, `${f.label}.${col.key}`, `${f.label}.${col.label}`,
        ], [], exclude),
    ]
    const formulaHits = [...new Set(hits)]
    const pdfHits = pdfToolsReferencingColumn(props.tools, f.key, col.key)
    return (!formulaHits.length && !pdfHits.length)
        || (await dialog.ask(referencedDeleteMessage(col.label || col.key, formulaHits, pdfHits))).value === true
}
const removeColumn = async (ci: number) => {
    if (columns.value.length <= 1) return
    if (!(await confirmColumnDelete(columns.value[ci]))) return
    columns.value.splice(ci, 1)
}

// single-column editing: which column (if any) is selected
const selectedColumn = computed<TableColumn | null>(() =>
    props.field.input_type === 'table' && props.columnKey
        ? columns.value.find((c) => c.key === props.columnKey) ?? null
        : null
)
const columnMode = computed(() => !!selectedColumn.value)
// always-non-null accessor for the column-mode template (falls back to a throwaway when nothing selected)
const col0 = computed<TableColumn>(() => selectedColumn.value ?? ({ key: '', label: '', input_type: 'short', options: null } as TableColumn))
const deleteSelectedColumn = async () => {
    const i = columns.value.findIndex((c) => c.key === props.columnKey)
    if (i >= 0 && columns.value.length > 1) {
        if (!(await confirmColumnDelete(columns.value[i]))) return
        columns.value.splice(i, 1)
        emit('update:columnKey', null)
    }
}
// per-target field cache so multiple reference columns can point at different apps
const colRefFieldsMap = ref<Record<number, { key: string; label: string; input_type: string }[]>>({})
const loadColRefFields = async (id: number | null | undefined) => {
    if (!id || colRefFieldsMap.value[id]) return
    const data = await api.get(`/flow_definition_fields/${id}`)
    colRefFieldsMap.value[id] = (data?.fields ?? []).filter((f: any) => !REF_LABEL_SKIP.includes(f.input_type))
}
const colRefFields = (id: number | null | undefined) => (id ? colRefFieldsMap.value[id] || [] : [])
const onColTypeChange = (col: TableColumn) => {
    // drop config that belongs to a different type so switching type never leaves stale settings
    if (col.input_type !== 'formula') { col.formula = null; col.result_type = null }
    if (col.input_type !== 'reference') { col.target_definition_id = null; col.label_field = null }
    if (colHasOptions(col) && !(col.options && col.options.length)) col.options = ['選択肢1']
    if (col.input_type === 'formula' && !col.result_type) col.result_type = 'number'
    if (col.input_type === 'reference') { loadRefApps(); loadColRefFields(col.target_definition_id) }
}
const onColRefTarget = (col: TableColumn) => {
    col.label_field = null
    loadColRefFields(col.target_definition_id)
}
const setColOption = (col: TableColumn, oi: number, val: string) => { if (col.options) col.options[oi] = val }
const addColOption = (col: TableColumn) => {
    if (!col.options) col.options = []
    col.options.push(`選択肢${col.options.length + 1}`)
}
const removeColOption = (col: TableColumn, oi: number) => col.options?.splice(oi, 1)
</script>

<style scoped>
.insp-inner { display: flex; flex-direction: column; }
.insp-h { font-size: 14px; font-weight: 500; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; color: var(--primary-color); }
.irow { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.irow label { font-size: 12px; color: gray; width: 86px; flex-shrink: 0; }
.vcol { margin-bottom: 10px; }
.vlabel { font-size: 12px; color: gray; display: block; margin-bottom: 6px; }
.minmax { display: flex; align-items: center; gap: 6px; flex: 1; }
.minmax input { width: 100%; min-width: 0; }
.tilde { color: gray; font-size: 12px; }
.chips { display: flex; flex-wrap: wrap; gap: 6px; }
.achip { font-size: 12px; padding: 5px 11px; border: 1px solid var(--calendarBorder); border-radius: 14px; background: var(--background-color); color: gray; cursor: pointer; }
.achip.on { border-color: var(--primary-color); background: var(--bg3); color: var(--primary-color); }
.sec { font-size: 12px; color: gray; margin: 0 0 8px; }
.divider { height: 1px; background: var(--calendarBorder); margin: 14px 0; }
.sremove { border: none; background: none; color: gray; cursor: pointer; padding: 4px; display: flex; }
.tcol-cfg { margin-top: 6px; padding-top: 6px; border-top: 1px dashed var(--calendarBorder); }
.col-list { display: flex; flex-direction: column; gap: 6px; }
.col-item { box-sizing: border-box !important; display: flex; align-items: center; gap: 8px; width: 100%; max-width: 100%; text-align: left; padding: 8px 10px; border: 1px solid var(--calendarBorder); border-radius: 8px; background: var(--background-color); cursor: pointer; font-size: 13px; }
.col-item:hover { border-color: var(--primary-color); background: var(--bg3); }
.col-item.dragging { opacity: 0.5; }
.col-item.dropto { border-color: var(--primary-color); box-shadow: 0 0 0 1px var(--primary-color); }
.col-item-lbl { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-item-type { font-size: 11px; color: gray; flex: none; }
.col-grip { color: gray; cursor: grab; flex: none; font-size: 11px; letter-spacing: -2px; user-select: none; }
.col-move { display: inline-flex; flex-direction: column; gap: 1px; flex: none; }
.col-arrow { border: none; background: none; color: gray; cursor: pointer; font-size: 8px; line-height: 1; padding: 1px 2px; }
.col-arrow:hover:not(:disabled) { color: var(--primary-color); }
.col-arrow:disabled { opacity: 0.3; cursor: default; }
.col-back { border: none; background: none; color: var(--primary-color); font-size: 12px; cursor: pointer; padding: 0; margin-bottom: 10px; text-align: left; }
.col-del { border: 1px solid var(--formBorder); background: var(--background-color); color: #dc2626; border-radius: 6px; padding: 7px 12px; font-size: 12px; cursor: pointer; }
.col-del:disabled { opacity: 0.4; cursor: not-allowed; }
.flow-ghost-btn { width: fit-content; }
.formula-area { width: 100%; min-height: 64px; font-family: ui-monospace, monospace; font-size: 13px; resize: vertical; }
.def-checks { display: flex; flex-direction: column; gap: 7px; }
.def-checks .fi-opt { font-size: 13px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
.def-hint { font-size: 11px; color: gray; margin-top: 6px; }
.achip { user-select: none; }
.tcols { display: flex; flex-direction: column; gap: 8px; }
.tcol { border: 1px solid var(--calendarBorder); border-radius: 8px; padding: 10px; background: var(--background-color); }
.tcol-h { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.tcol-n { font-size: 12px; color: gray; font-weight: 500; }
.tcol-opts { margin-top: 6px; padding-left: 10px; border-left: 2px solid var(--calendarBorder); }
.tcol-req { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 12px; color: gray; }
.sremove:disabled { opacity: .35; cursor: default; }
</style>
