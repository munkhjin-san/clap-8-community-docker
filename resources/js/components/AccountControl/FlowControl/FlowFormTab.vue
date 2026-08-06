<template>
    <div class="flow-form-tab" :class="{ narrow: isNarrow }">
        <div class="palette" :class="{ open: paletteOpen }">
            <div v-if="isNarrow" class="pal-toggle" @click="paletteOpen = !paletteOpen">
                <span>＋ パーツを追加</span>
                <span class="pal-caret">{{ paletteOpen ? '▲' : '▼' }}</span>
            </div>
            <div class="pal-groups" v-show="!isNarrow || paletteOpen">
                <template v-for="group in groups" :key="group">
                    <div class="pal-sec">{{ group }}</div>
                    <div
                        v-for="t in typesByGroup(group)"
                        :key="t.type"
                        class="chip"
                        draggable="true"
                        @dragstart="onPaletteDragStart($event, t.type)"
                        @dragend="clearDrag"
                        @click="pickField(t.type)"
                    >
                        <span class="chip-ico"><FlowFieldIcon :type="t.type" :size="17" /></span>{{ t.label }}
                    </div>
                </template>
                <p class="pal-hint">クリックで末尾に追加、ドラッグでスロットへ配置</p>
            </div>
        </div>

        <div class="work">
            <div class="work-h">
                <span>フォーム本文</span>
                <span class="text-[11px] text-gray-400">横にも並べられます · 右端をドラッグで幅調整</span>
            </div>
            <div ref="canvasEl" class="canvas" @dragover.prevent="onCanvasOver" @drop.prevent="commitDrop">
                <template v-for="(row, r) in rows" :key="r">
                    <div
                        class="rowsep"
                        :class="{ over: newRowOverIndex === r }"
                        @dragover.stop.prevent="onSepOver(r)"
                    ></div>
                    <div class="frow" @dragover.stop.prevent="onRowOver(row)">
                        <template v-for="field in row" :key="field.uid || field.key">
                            <div class="ins-bar" v-if="isInsBefore(row, field.key)"></div>
                            <div
                                class="field"
                                :class="{ sel: selectedUid === field.uid }"
                                :style="{ width: field.width + 'px' }"
                                draggable="true"
                                @click="selectField(field.uid || null)"
                                @dragstart="onFieldDragStart($event, field)"
                                @dragend="clearDrag"
                                @dragover.stop.prevent="onItemOver($event, row, field)"
                            >
                                <div class="fhandle">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="13" class="dot-menu" viewBox="0 0 7 32"><path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path><path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path><path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path></svg>
                                </div>
                                <div class="fmain">
                                    <div class="ftop">
                                        <span class="badge"><FlowFieldIcon :type="field.input_type" :size="13" />{{ typeLabel(field.input_type) }}</span>
                                        <span v-if="!isDecorationType(field.input_type)" class="lbl">{{ field.label }}</span>
                                        <span v-if="field.is_required" class="req">必須</span>
                                        <div class="tools" @click.stop>
                                            <button @click="duplicate(field)" title="複製">
                                                <svg class="dot-menu" xmlns="http://www.w3.org/2000/svg" width="8" viewBox="0 0 17.85612 23.5403"><path d="M6.60832.8297c-.5011-.05275-.52747-.73846,0-.79121,6.14506-.60659,12.81758,6.06593,10.91868,12.29011-1.5033,4.82637-6.72528,6.40879-11.39341,5.67033,1.55604,1.0022,3.05934,2.05714,4.37802,3.34945,1.18681,1.16044-.63297,2.98022-1.81978,1.81978-2.50549-2.47912-5.3011-4.48352-8.22857-6.40879-.71209-.44835-.58022-1.5033.23736-1.76703,3.34945-1.05495,5.98681-2.9011,8.94066-4.74725.73846-.44835,1.3978.55385.8967,1.16044-1.3978,1.63517-3.24396,2.87473-5.22198,3.85055,3.34945.84396,7.85934.5011,9.6-2.61099C17.73799,7.50223,11.56656,1.40992,6.60832.8297Z"></path></svg>
                                            </button>
                                            <button @click="remove(field)" title="削除"><CloseIcon size="9" /></button>
                                        </div>
                                    </div>
                                    <div v-if="field.input_type === 'divider'" class="prev-divider" :style="{ borderTopStyle: field.validation?.line_style || 'solid' }"></div>
                                    <div v-else-if="field.input_type === 'spacer'" class="prev-spacer" :style="{ height: (field.validation?.height || 24) + 'px' }"><span>スペース {{ field.width }}×{{ field.validation?.height || 24 }}</span></div>
                                    <div v-else-if="field.input_type === 'table'" class="prev-table">
                                        <div class="prev-thead">
                                            <span
                                                v-for="(c, ci) in (field.validation?.columns || [])"
                                                :key="ci"
                                                class="prev-th prev-th-click"
                                                :class="{ 'prev-th-sel': selectedUid === field.uid && selectedColumnKey === c.key }"
                                                @click.stop="selectColumn(field, c.key)"
                                            >
                                                <FlowFieldIcon :type="c.input_type" :size="11" />{{ c.label || '列' }}<i v-if="c.required" class="prev-th-req">*</i>
                                            </span>
                                            <span class="prev-th prev-th-add" title="列を追加" @click.stop="addTableColumn(field)">＋</span>
                                        </div>
                                        <div class="prev-trow">
                                            <span v-for="(c, ci) in (field.validation?.columns || [])" :key="ci" class="prev-td"></span>
                                            <span class="prev-td prev-td-add"></span>
                                        </div>
                                    </div>
                                    <!-- 関連レコード: 中身は保存済みレコードでしか出せないので、下絵では設定の要約を出す -->
                                    <div v-else-if="field.input_type === 'related'" class="prev-related">{{ relatedSummary(field) }}</div>
                                    <!-- ラベルだけは装飾込みで見せる（保存時に無害化済み） -->
                                    <div v-else-if="field.input_type === 'label'" class="prev labeltext" v-html="field.label"></div>
                                    <div v-else class="prev" :class="{ heading: field.input_type === 'heading' }">{{ previewText(field) }}</div>
                                </div>
                                <div class="fresize" draggable="false" @dragstart.stop.prevent @pointerdown.stop="startResize($event, field)" @click.stop title="幅をドラッグで調整"></div>
                            </div>
                        </template>
                        <div class="ins-bar" v-if="isInsEnd(row)"></div>
                        <div class="row-tail"></div>
                    </div>
                </template>
                <div
                    class="rowsep last"
                    :class="{ over: newRowOverIndex === rows.length }"
                    @dragover.stop.prevent="onSepOver(rows.length)"
                ></div>
                <div v-if="!rows.length" class="drop">左のパーツをここへドラッグ、またはクリックで追加</div>
            </div>
        </div>

        <!-- 項目の設定は画面幅にかかわらずモーダル。横に細い柱として置くと、ラベルの
             リッチテキスト編集のように場所を要るものが入らない。 -->
        <Modal v-if="current && inspectorOpen" size="large" persist @close="closeInspector">
            <template #title>
                <b class="ffm-modal-title"><FlowFieldIcon :type="current.input_type" :size="15" /> {{ typeLabel(current.input_type) }}の設定</b>
            </template>
            <template #content>
                <FlowFieldInspector :field="current" :fields="allFields" :tools="def.tools" :definition-id="def.id ?? null" v-model:columnKey="selectedColumnKey" />
                <div class="ffm-modal-foot">
                    <button class="ffm-done" @click="closeInspector">完了</button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { FLOW_FIELD_TYPES, FLOW_TYPE_LABEL, FLOW_FIELD_MIN_WIDTH, defaultWidthFor, isDecorationType, isLayoutType } from '@/types/flow'
import type { BuilderDefinition, FlowField, FlowInputType } from '@/types/flow'
import { referencingFormulas, referencedDeleteMessage, pdfToolsReferencingField } from '@/utils/flowFormulaRefs'
import { useDialog } from '@/composables/dialog'
import FlowFieldIcon from './FlowFieldIcon.vue'
import FlowFieldInspector from './FlowFieldInspector.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import Modal from '@/components/Global/Modal.vue'

const props = defineProps<{ def: BuilderDefinition }>()

const rows = ref<FlowField[][]>([])
const canvasEl = ref<HTMLElement | null>(null)
const selectedUid = ref<string | null>(null)
const selectedColumnKey = ref<string | null>(null)
const inspectorOpen = ref(false) // 項目の設定モーダル（PC・モバイル共通）
const closeInspector = () => { inspectorOpen.value = false }
let uidSeq = 0
const nextUid = () => `fuid_${++uidSeq}`

// select a field (clearing any column selection unless a column of it is explicitly chosen)
const selectField = (uid: string | null) => { selectedUid.value = uid; selectedColumnKey.value = null; if (uid) inspectorOpen.value = true }
// select a specific column inside a table field
const selectColumn = (field: FlowField, key: string) => { selectedUid.value = field.uid || null; selectedColumnKey.value = key; inspectorOpen.value = true }
// add a column to a table field from the canvas and select it
const addTableColumn = (field: FlowField) => {
    if (!field.validation) field.validation = {}
    if (!Array.isArray(field.validation.columns)) field.validation.columns = []
    const used = new Set(field.validation.columns.map((c) => c.key))
    let i = 1
    while (used.has(`c${i}`)) i++
    const key = `c${i}`
    field.validation.columns.push({ key, label: `列${field.validation.columns.length + 1}`, input_type: 'short', options: null })
    selectColumn(field, key)
}

const groups = ['入力', '選択', '高度', 'レイアウト', 'その他'] as const
const typesByGroup = (group: string) =>
    FLOW_FIELD_TYPES.filter((t) => t.group === group && !t.deprecated && (!t.projectOnly || !!props.def.project_record_id))
const typeLabel = (t: string) => FLOW_TYPE_LABEL[t] ?? t
const hasOptions = (t: string) => ['select', 'radio', 'checkbox'].includes(t)

const current = computed<FlowField | null>(() => rows.value.flat().find((f) => f.uid === selectedUid.value) ?? null)
const allFields = computed<FlowField[]>(() => rows.value.flat())

const buildRows = () => {
    const map = new Map<number, FlowField[]>()
    for (const f of props.def.fields) {
        if (!f.uid) f.uid = nextUid() // stable id so editing `key` never breaks selection/focus
        const r = f.layout_row ?? 0
        if (!map.has(r)) map.set(r, [])
        map.get(r)!.push(f)
    }
    rows.value = [...map.keys()].sort((a, b) => a - b)
        .map((k) => map.get(k)!.sort((a, b) => (a.order_number ?? 0) - (b.order_number ?? 0)))
    if (!selectedUid.value && rows.value.length) selectedUid.value = rows.value[0][0]?.uid ?? null
}
watch(() => props.def, buildRows, { immediate: true })

const sync = () => {
    rows.value = rows.value.filter((r) => r.length)
    const flat: FlowField[] = []
    rows.value.forEach((row, r) => row.forEach((f, k) => {
        f.layout_row = r
        f.order_number = k
        flat.push(f)
    }))
    props.def.fields.splice(0, props.def.fields.length, ...flat)
}

const allKeys = () => new Set(rows.value.flat().map((f) => f.key))
const genKey = (type: string) => {
    let n = 1
    const keys = allKeys()
    while (keys.has(`${type}_${n}`)) n++
    return `${type}_${n}`
}
// Default labels are type names ("数値", "計算"), which collide fast — suffix a number so each is unique.
const uniqueLabel = (base: string) => {
    if (!base) return base
    const labels = new Set(rows.value.flat().map((f) => f.label))
    if (!labels.has(base)) return base
    let n = 2
    while (labels.has(`${base}${n}`)) n++
    return `${base}${n}`
}
const makeLabel = (type: FlowInputType) => {
    if (type === 'label') return '説明文をここに入力'
    if (type === 'spacer' || type === 'divider') return ''
    const base = FLOW_TYPE_LABEL[type] ?? type
    return isLayoutType(type) ? base : uniqueLabel(base) // data fields get a unique label
}
const defaultValidation = (type: FlowInputType) => {
    if (type === 'spacer') return { height: 24 }
    if (type === 'divider') return { line_style: 'solid' as const }
    if (type === 'table') return {
        columns: [
            { key: 'c1', label: '列1', input_type: 'short' as FlowInputType, options: null },
            { key: 'c2', label: '列2', input_type: 'short' as FlowInputType, options: null },
        ],
    }
    return {}
}
const makeField = (type: FlowInputType): FlowField => ({
    uid: nextUid(),
    key: genKey(type),
    label: makeLabel(type),
    input_type: type,
    width: defaultWidthFor(type),
    is_required: false,
    options: hasOptions(type) ? ['選択肢1', '選択肢2'] : null,
    validation: defaultValidation(type),
    layout_row: 0,
    order_number: 0,
})

/**
 * 関連レコードの下絵。実際の一覧は保存済みレコードでしか出せないので、ここでは何を出す設定かを示す。
 * 未設定のまま置き忘れたブロックがすぐ分かるようにするのが主目的。
 */
const relatedSummary = (f: FlowField) => {
    const v: any = f.validation ?? {}
    if (!v.child_definition_id || !v.child_field_id) return '未設定 — 右の設定で参照先アプリと結び付けを選んでください'
    const cols = Array.isArray(v.related_columns) && v.related_columns.length ? `${v.related_columns.length}列` : '列は自動'
    const sums = Array.isArray(v.related_aggregates) && v.related_aggregates.length ? ` / 合計 ${v.related_aggregates.length}件` : ''
    return `このレコードを参照しているレコードを一覧（${cols}${sums}）`
}

const previewText = (f: FlowField) => {
    if (f.input_type === 'heading' || f.input_type === 'label') return f.label
    if (f.input_type === 'file') return 'ファイルを選択'
    if (hasOptions(f.input_type)) {
        const opts = f.options || []
        if (!opts.length) return '選択肢未設定'
        const head = opts.slice(0, 4).join(' / ')
        return opts.length > 4 ? `${head} ほか${opts.length - 4}件` : head
    }
    if (f.input_type === 'date') return '年 / 月 / 日'
    if (f.input_type === 'time') return '時 : 分'
    if (f.input_type === 'datetime') return '年 / 月 / 日  時 : 分'
    if (f.input_type === 'number') return '0'
    if (f.input_type === 'toggle') return 'オン / オフ'
    if (f.input_type === 'user' || f.input_type === 'member') return 'ユーザーを選択'
    if (f.input_type === 'reference') return (f.validation?.target_definition_id || f.validation?.target_source) ? 'レコードを検索' : '参照先未設定'
    if (f.input_type === 'formula') return f.formula ? '= ' + f.formula : '計算結果'
    if (f.input_type === 'password') return '••••••••（暗号化して保存）'
    return 'テキストを入力'
}

// place a new field on the last row if it fits the canvas width, otherwise start a new row.
// GAP must match the .frow visual gap (and the record view's .rd-row gap) so "fits" is accurate.
const placeField = (field: FlowField) => {
    const GAP = 20
    const target = canvasEl.value?.clientWidth || 1000
    const last = rows.value[rows.value.length - 1]
    const fieldIsLayout = isLayoutType(field.input_type)
    if (!fieldIsLayout && last && last.length && !last.some((f) => isLayoutType(f.input_type))) {
        const used = last.reduce((s, f) => s + f.width + GAP, 0)
        if (used + field.width <= target) { last.push(field); return }
    }
    rows.value.push([field])
}
const addByClick = (type: FlowInputType) => {
    const field = makeField(type)
    placeField(field)
    sync()
    selectedUid.value = field.uid || null
}
const pickField = (type: FlowInputType) => {
    addByClick(type)
    paletteOpen.value = false
    inspectorOpen.value = true
}
const duplicate = (field: FlowField) => {
    // Deep-copy validation.columns (a shallow spread would share the array → editing the copy's
    // columns mutates the original). Dedup the label so the copy doesn't hit the save-time
    // uniqueness check; layout parts (見出し/ラベル) may keep their text.
    const validation = { ...(field.validation || {}) }
    if (Array.isArray(validation.columns)) {
        validation.columns = validation.columns.map((c) => ({ ...c, options: c.options ? [...c.options] : c.options }))
    }
    const copy: FlowField = {
        ...field,
        uid: nextUid(),
        id: undefined,
        key: genKey(field.input_type),
        label: isLayoutType(field.input_type) ? field.label : uniqueLabel(field.label),
        options: field.options ? [...field.options] : null,
        validation,
    }
    for (const row of rows.value) {
        const i = row.indexOf(field)
        if (i >= 0) { row.splice(i + 1, 0, copy); break }
    }
    sync()
    selectedUid.value = copy.uid || null
    inspectorOpen.value = true
}
const dialog = useDialog()
const remove = async (field: FlowField) => {
    // Warn when another formula references this field — deleting would silently turn
    // those results wrong (the dangling ref computes as 0). The user may still proceed.
    const names = [field.key, field.label]
    const prefixes = field.input_type === 'table' ? names : [] // a table delete also breaks [table.column] refs
    const formulaHits = referencingFormulas(rows.value.flat(), names, prefixes, { fieldKey: field.key })
    const pdfHits = pdfToolsReferencingField(props.def.tools, field.key)
    if ((formulaHits.length || pdfHits.length)
        && !(await dialog.ask(referencedDeleteMessage(field.label || field.key, formulaHits, pdfHits))).value) return

    removeFromRows(field)
    if (selectedUid.value === field.uid) selectedUid.value = rows.value.flat()[0]?.uid ?? null
    sync()
}
const removeFromRows = (field: FlowField) => {
    for (const row of rows.value) {
        const i = row.indexOf(field)
        if (i >= 0) { row.splice(i, 1); return }
    }
}

/* ---- drag & drop ---- */
const dragData = ref<{ kind: 'palette'; type: FlowInputType } | { kind: 'field'; key: string } | null>(null)
const insert = ref<{ row: FlowField[]; beforeKey: string | null } | null>(null)
const newRowOverIndex = ref<number | null>(null)

const onPaletteDragStart = (e: DragEvent, type: FlowInputType) => {
    dragData.value = { kind: 'palette', type }
    e.dataTransfer && (e.dataTransfer.effectAllowed = 'copy')
}
const onFieldDragStart = (e: DragEvent, field: FlowField) => {
    if (resizing) { e.preventDefault(); return }
    dragData.value = { kind: 'field', key: field.key }
    e.dataTransfer && (e.dataTransfer.effectAllowed = 'move', e.dataTransfer.setData('text/plain', field.key))
}
const onItemOver = (e: DragEvent, row: FlowField[], field: FlowField) => {
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
    const after = e.clientX - rect.left > rect.width / 2
    const idx = row.indexOf(field)
    insert.value = { row, beforeKey: after ? (row[idx + 1]?.key ?? null) : field.key }
    newRowOverIndex.value = null
}
const onRowOver = (row: FlowField[]) => {
    insert.value = { row, beforeKey: null }
    newRowOverIndex.value = null
}
const onSepOver = (at: number) => {
    newRowOverIndex.value = at
    insert.value = null
}
const onCanvasOver = () => {
    insert.value = null
    newRowOverIndex.value = null
}
const isInsBefore = (row: FlowField[], key: string) => insert.value?.row === row && insert.value?.beforeKey === key
const isInsEnd = (row: FlowField[]) => insert.value?.row === row && insert.value?.beforeKey === null

const commitDrop = () => {
    const d = dragData.value
    if (!d) return clearDrag()

    let field: FlowField
    if (d.kind === 'palette') {
        field = makeField(d.type)
    } else {
        const found = rows.value.flat().find((f) => f.key === d.key)
        if (!found) return clearDrag()
        if (insert.value && insert.value.beforeKey === found.key) return clearDrag()
        field = found
        removeFromRows(field)
    }

    if (newRowOverIndex.value !== null) {
        rows.value.splice(newRowOverIndex.value, 0, [field])
    } else if (insert.value) {
        const row = insert.value.row
        const pos = insert.value.beforeKey === null ? row.length : row.findIndex((f) => f.key === insert.value!.beforeKey)
        row.splice(pos < 0 ? row.length : pos, 0, field)
    } else {
        rows.value.push([field])
    }
    sync()
    selectedUid.value = field.uid || null
    clearDrag()
}
const clearDrag = () => {
    dragData.value = null
    insert.value = null
    newRowOverIndex.value = null
}

/* ---- free-pixel resize ---- */
let resizing: { field: FlowField; startX: number; startW: number } | null = null
const startResize = (e: PointerEvent, field: FlowField) => {
    e.preventDefault()
    resizing = { field, startX: e.clientX, startW: field.width }
    selectedUid.value = field.uid || null
    window.addEventListener('pointermove', onResize)
    window.addEventListener('pointerup', endResize)
}
const onResize = (e: PointerEvent) => {
    if (!resizing) return
    resizing.field.width = Math.max(FLOW_FIELD_MIN_WIDTH, Math.round(resizing.startW + (e.clientX - resizing.startX)))
}
const endResize = () => {
    if (resizing) sync()
    resizing = null
    window.removeEventListener('pointermove', onResize)
    window.removeEventListener('pointerup', endResize)
}

/* ---- responsive ---- */
const paletteOpen = ref(false)
const isNarrow = ref(false)
let mq: MediaQueryList | null = null
const onMq = (e: MediaQueryListEvent) => (isNarrow.value = e.matches)
onMounted(() => {
    mq = window.matchMedia('(max-width: 1024px)')
    isNarrow.value = mq.matches
    mq.addEventListener('change', onMq)
})
onUnmounted(() => {
    mq?.removeEventListener('change', onMq)
    endResize()
})
</script>

<style scoped>
.flow-form-tab { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 14px; align-items: start; }
.flow-form-tab.narrow { grid-template-columns: 1fr; }
.palette { display: flex; flex-direction: column; position: sticky; top: 0; align-self: start; }
.pal-toggle { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; font-size: 13px; font-weight: 500; cursor: pointer; }
.pal-caret { color: gray; font-size: 11px; }
.flow-form-tab.narrow .palette { position: relative; align-self: stretch; border: 1px solid var(--calendarBorder); border-radius: 8px; background: var(--background-color); z-index: 30; }
/* the expanded picker floats over the canvas instead of pushing the work bench down */
.flow-form-tab.narrow .pal-groups { position: absolute; top: calc(100% + 4px); left: 0; right: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 6px; padding: 12px; max-height: 60vh; overflow-y: auto; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 8px; box-shadow: 0 12px 32px rgba(0,0,0,.22); }
.flow-form-tab.narrow .pal-sec { grid-column: 1 / -1; margin: 8px 2px 0; }
.flow-form-tab.narrow .pal-hint { grid-column: 1 / -1; }
.flow-form-tab.narrow .chip { margin-bottom: 0; }
.pal-sec { font-size: 12px; color: gray; margin: 10px 2px 6px; }
.pal-sec:first-child { margin-top: 0; }
.pal-hint { font-size: 11px; color: gray; margin-top: 8px; line-height: 1.5; }
.chip { display: flex; align-items: center; gap: 7px; padding: 8px 10px; border: 1px solid var(--calendarBorder); border-radius: 6px; background: var(--background-color); font-size: 13px; margin-bottom: 6px; cursor: grab; }
.chip:hover { border-color: var(--primary-color); background: var(--bg3); }
.chip:active { cursor: grabbing; }
.chip-ico { width: 18px; display: inline-flex; align-items: center; justify-content: center; color: var(--primary-color); flex-shrink: 0; }
.work { background: var(--bg3); border: 1px solid var(--calendarBorder); border-radius: 12px; padding: 12px; min-height: 420px; min-width: 0; }
.work-h { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: gray; margin: 0 2px 10px; gap: 8px; }
.canvas { overflow: auto; max-height: calc(100vh - 230px); padding-bottom: 4px; }
.rowsep { height: 14px; border-radius: 4px; transition: background .1s; }
.rowsep.over { background: var(--primary-color); opacity: 0.5; }
.rowsep.last { min-height: 34px; }
.frow { display: flex; gap: 20px; align-items: stretch; width: max-content; min-width: 100%; min-height: 66px; }
.row-tail { flex: 1; min-width: 24px; }
.ins-bar { width: 3px; background: var(--primary-color); border-radius: 2px; align-self: stretch; flex-shrink: 0; }
.field { position: relative; flex: 0 0 auto; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 8px; padding: 12px 18px 13px 8px; display: flex; gap: 6px; cursor: grab; }
.field:hover { border-color: var(--formBorder); }
.field.sel { border-color: var(--primary-color); }
.field:active { cursor: grabbing; }
.fhandle { display: flex; align-items: center; gap: 1px; padding: 0 2px; }
.fmain { flex: 1; min-width: 0; }
.ftop { display: flex; align-items: center; gap: 6px; margin-bottom: 9px; }
.badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: var(--primary-color); background: var(--bg3); padding: 2px 7px; border-radius: 5px; flex-shrink: 0; }
.lbl { flex: 1 1 auto; min-width: 0; font-size: 13px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.req { font-size: 11px; color: #e2574c; background: rgba(226, 87, 76, 0.14); padding: 1px 6px; border-radius: 5px; flex-shrink: 0; }
.prev { height: 32px; line-height: 30px; border: 1px solid var(--calendarBorder); border-radius: 5px; background: var(--bg3); padding: 0 9px; font-size: 12px; color: gray; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; min-width: 0; }
.prev.heading { border: none; background: none; font-size: 15px; font-weight: 500; color: var(--primary-color); padding-left: 0; }
.prev.labeltext { border: none; background: none; height: auto; min-height: 20px; padding-left: 0; color: var(--primary-color); white-space: pre-wrap; line-height: 1.5; }
.prev-spacer { display: flex; align-items: center; justify-content: center; border: 1px dashed var(--formBorder); border-radius: 5px; background: repeating-linear-gradient(45deg, transparent, transparent 6px, var(--bg3) 6px, var(--bg3) 12px); font-size: 11px; color: gray; }
.prev-divider { border-top-width: 2px; border-top-color: var(--formBorder); margin: 14px 0; }
.prev-table { border: 1px solid var(--calendarBorder); border-radius: 5px; overflow: hidden; }
.prev-thead { display: flex; background: var(--bg3); }
.prev-th { flex: 1; min-width: 0; box-sizing: border-box !important; display: flex; align-items: center; gap: 4px; font-size: 11px; color: gray; padding: 6px 8px; border-right: 1px solid var(--calendarBorder); overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.prev-th:last-child { border-right: none; }
.prev-th-empty { color: #b0b0b0; font-style: italic; }
.prev-th-req { color: #e2574c; font-style: normal; margin-left: 1px; }
.prev-th-click { cursor: pointer; }
.prev-th-click:hover { background: var(--background-color); color: var(--primary-color); }
.prev-th-sel { background: var(--background-color); color: var(--primary-color); box-shadow: inset 0 -2px 0 var(--primary-color); font-weight: 500; }
.prev-th-add { flex: 0 0 26px; min-width: 0; padding: 6px 0; justify-content: center; cursor: pointer; color: var(--primary-color); font-weight: 600; }
.prev-th-add:hover { background: var(--background-color); }
.prev-td.prev-td-add { flex: 0 0 26px; }
.prev-trow { display: flex; }
.prev-td { flex: 1; box-sizing: border-box !important; height: 26px; border-right: 1px solid var(--calendarBorder); border-top: 1px solid var(--calendarBorder); }
.prev-td:last-child { border-right: none; }
.tools { display: flex; flex-direction: row; gap: 5px; align-items: center; margin-left: auto; flex-shrink: 0; }
.tools button { border: none; background: none; cursor: pointer; padding: 2px; display: flex; align-items: center; justify-content: center; border-radius: 4px; }
.tools button:hover { background: var(--bg3); }
.fresize { position: absolute; top: 0; right: 0; width: 8px; height: 100%; cursor: ew-resize; border-radius: 0 6px 6px 0; }
.field:hover .fresize, .field.sel .fresize { background: var(--primary-color); opacity: 0.25; }
.fresize:hover { opacity: 0.5 !important; }
.drop { border: 1.5px dashed var(--formBorder); border-radius: 6px; padding: 30px; text-align: center; font-size: 12px; color: gray; }
.ffm-modal-title { display: inline-flex; align-items: center; gap: 7px; font-size: 15px; font-weight: 600; }
.ffm-modal-foot { display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--calendarBorder); }
.ffm-done { border: none; background: var(--primary-button, var(--primary-color)); color: #fff; border-radius: 7px; padding: 8px 24px; font-size: 13px; cursor: pointer; }
/* 関連レコードの下絵 */
.prev-related { font-size: 11.5px; color: gray; border: 1.5px dashed var(--formBorder); border-radius: 7px; padding: 9px 11px; line-height: 1.6; }
</style>
