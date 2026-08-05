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
                <FlowSearchSelect
                    class="w-full"
                    :model-value="col0.target_definition_id ?? null"
                    :options="refAppOptions"
                    placeholder="参照先アプリを選択"
                    @update:model-value="(val) => onColRefAppChange(col0, val)"
                />
                <FlowSearchSelect
                    v-if="col0.target_definition_id"
                    class="w-full mt-[6px]"
                    :model-value="col0.label_field ?? ''"
                    :options="colLabelFieldOptions(col0.target_definition_id)"
                    :clearable="false"
                    placeholder="レコード番号"
                    @update:model-value="(val) => col0.label_field = val ? String(val) : null"
                />
            </template>

            <div v-if="col0.input_type !== 'formula' && !isLayoutType(col0.input_type)" class="irow" style="margin-top: 10px">
                <label>無効化</label>
                <span class="flow-sw" :class="{ on: colV(col0).disabled }" @click="setColDisabled(col0, !colV(col0).disabled)"></span>
            </div>
            <div class="irow">
                <label>列幅</label>
                <div class="flex items-center gap-[6px]">
                    <input type="number" min="60" v-model.number="col0.width" placeholder="自動" class="custom-a-input !box-border !w-[110px]">
                    <span class="text-[12px] text-gray-500">px</span>
                </div>
            </div>
            <div v-if="col0.input_type !== 'formula'" class="irow" style="margin-top: 10px">
                <label>必須</label>
                <span class="flow-sw" :class="{ on: col0.required }" @click="col0.required = !col0.required"></span>
            </div>

            <div class="divider"></div>
            <FlowFieldRules :input-type="col0.input_type" :validation="colV(col0)" :options="col0.options" />

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
            <span class="flow-sw" :class="{ on: field.is_required }" @click="setRequired(!field.is_required)"></span>
        </div>
        <div class="irow" v-if="!isLayout && field.input_type !== 'formula'">
            <label>無効化</label>
            <span class="flow-sw" :class="{ on: v.disabled }" @click="setDisabled(!v.disabled)"></span>
        </div>
        <p v-if="v.disabled && !isLayout && field.input_type !== 'formula'" class="def-hint">フォームに表示されますが入力できません。ルックアップの自動入力は反映されます。</p>

        <!-- この項目が他のフィールドの自動入力先になっている場合の逆引き表示 -->
        <template v-if="autoFillIntoThis.length">
            <div class="divider"></div>
            <div class="sec">自動入力されます</div>
            <div v-for="(a, ai) in autoFillIntoThis" :key="ai" class="af-src">
                <FlowFieldIcon :type="a.type" :size="13" />
                <span class="af-src-name">{{ a.label }}</span>
                <span class="af-src-type">{{ typeLabel(a.type) }}</span>
            </div>
        </template>

        <template v-if="field.input_type === 'password'">
            <div class="divider"></div>
            <div class="sec">暗号化について</div>
            <p class="def-hint">値は暗号化して保存され、一覧・CSV出力・検索・PDF・計算式には表示されません。</p>
            <p class="def-hint">表示するには「表示」ボタンを押す必要があります。</p>
        </template>

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

        <template v-if="hasOptions">
            <div class="divider"></div>
            <div class="sec">選択肢</div>
            <div v-for="(opt, oi) in field.options || []" :key="oi" class="flex items-center gap-[6px] mt-[6px]">
                <input type="text" :value="opt" @input="setOption(oi, ($event.target as HTMLInputElement).value)" class="custom-a-input !box-border flex-1">
                <button class="sremove" @click="removeOption(oi)"><CloseIcon size="9" /></button>
            </div>
            <button class="flow-ghost-btn mt-[8px]" @click="addOption">＋ 選択肢を追加</button>
        </template>

        <FlowFieldRules :input-type="field.input_type" :validation="v" :options="field.options" />

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

        <!-- ユーザー/プロジェクト auto-fill. Same editor as the 参照 field's field copy: the source options
             are this master's allowlisted columns, the destinations and type rules are shared. -->
        <template v-if="autoFillSource && refTargetFields.length">
            <div class="divider"></div>
            <div class="sec">フィールドのコピー（自動入力）</div>
            <!-- 縦積み。インスペクタは固定幅のサイドバーなので、2つのセレクトを横に並べると
                 日本語のラベルが4文字ほどで切れて選べなくなる。 -->
            <div v-for="(m, mi) in (v.field_mappings || [])" :key="mi" class="map-row">
                <div class="map-line">
                    <FlowSearchSelect
                        class="map-sel"
                        :model-value="m.from || null"
                        :options="refFieldOptions"
                        :clearable="false"
                        :placeholder="autoFillSource === 'user' ? 'ユーザーの項目' : 'プロジェクトの項目'"
                        @update:model-value="(val) => { m.from = String(val ?? ''); onMappingFromChange(m) }"
                    />
                    <button class="map-del" @click="removeMapping(mi)" title="削除"><CloseIcon size="9" /></button>
                </div>
                <div class="map-line">
                    <span class="map-arrow">↓</span>
                    <FlowSearchSelect
                        class="map-sel"
                        :model-value="m.to || null"
                        :options="destOptionsFor(m.from)"
                        :clearable="false"
                        placeholder="このアプリの項目"
                        @update:model-value="(val) => m.to = String(val ?? '')"
                    />
                </div>
            </div>
            <button class="flow-ghost-btn mt-[20px]" :disabled="!mappingDestFields.length" @click="addMapping">＋ コピーを追加</button>
            <p class="def-hint">
                選んだときの値がコピーされます。あとでマスタ側が変わっても、保存済みのレコードはそのままです。コピーされた項目は手で修正できます。
            </p>
            <p v-if="autoFillMultiHint" class="def-hint">複数選択のため、1人だけ選ばれているときに自動入力されます。</p>
        </template>

        <template v-if="field.input_type === 'reference'">
            <div class="divider"></div>
            <div class="sec">参照先</div>
            <div class="irow">
                <label>参照先</label>
                <FlowSearchSelect
                    class="flex-1"
                    :model-value="refSelectValue"
                    :options="refAppOptions"
                    placeholder="アプリ / システムを選択"
                    @update:model-value="onRefAppChange"
                />
            </div>
            <div class="irow" v-if="hasRefTarget">
                <label>表示する項目</label>
                <FlowSearchSelect
                    class="flex-1"
                    :model-value="v.label_field ?? ''"
                    :options="labelFieldOptions"
                    :clearable="false"
                    placeholder="レコード番号"
                    @update:model-value="(val) => v.label_field = val ? String(val) : null"
                />
            </div>
            <p v-if="hasRefTarget" class="def-hint">レコードを選ぶと、そのレコードの「{{ refLabelName }}」が表示されます。</p>

            <template v-if="hasRefTarget && refTargetFields.length">
                <div class="divider"></div>
                <div class="sec">フィールドのコピー（自動入力）</div>
                <div v-for="(m, mi) in (v.field_mappings || [])" :key="mi" class="map-row">
                    <div class="map-line">
                        <FlowSearchSelect
                            class="map-sel"
                            :model-value="m.from || null"
                            :options="refFieldOptions"
                            :clearable="false"
                            placeholder="参照先の項目"
                            @update:model-value="(val) => { m.from = String(val ?? ''); onMappingFromChange(m) }"
                        />
                        <button class="map-del" @click="removeMapping(mi)" title="削除"><CloseIcon size="9" /></button>
                    </div>
                    <div class="map-line">
                        <span class="map-arrow">↓</span>
                        <FlowSearchSelect
                            class="map-sel"
                            :model-value="m.to || null"
                            :options="destOptionsFor(m.from)"
                            :clearable="false"
                            placeholder="このアプリの項目"
                            @update:model-value="(val) => m.to = String(val ?? '')"
                        />
                    </div>
                </div>
                <button class="flow-ghost-btn mt-[20px]" :disabled="!mappingDestFields.length" @click="addMapping">＋ コピーを追加</button>
            </template>
        </template>
        </template>
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { computed, ref, watch } from 'vue'
import { FLOW_TYPE_LABEL, FLOW_FILE_ACCEPT, FLOW_FIELD_TYPES, isLayoutType, isSecretType } from '@/types/flow'
import type { FlowField, FlowFieldValidation, TableColumn, FlowAppTool } from '@/types/flow'
import { referencingFormulas, referencedDeleteMessage, renameFieldRefEverywhere, renameColumnRefInTable, pdfToolsReferencingColumn } from '@/utils/flowFormulaRefs'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import FlowFieldIcon from './FlowFieldIcon.vue'
import FlowFieldRules from './FlowFieldRules.vue'
import FlowFormulaEditor from './FlowFormulaEditor.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import FlowSearchSelect from './FlowSearchSelect.vue'
import { useTheme } from '@/store/theme'

const props = defineProps<{ field: FlowField; fields?: FlowField[]; tools?: FlowAppTool[]; columnKey?: string | null }>()
const theme = useTheme()
// native date/time pickers render their icon per `color-scheme`; follow the app theme (dark-mode visibility)
const nativeScheme = computed(() => (theme.dark ? 'dark' : 'light'))
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
    if (from && from !== col.label) renameColumnRefInTable(props.field, from, col.label, props.fields)
}

/* ---- reference field: target app / system source + label field ---- */
const refApps = ref<{ id: number; name: string }[]>([])
// built-in system sources (e.g. offices) selectable as a reference target alongside Flow apps
const refSystemSources = ref<{ key: string; label: string }[]>([])
const refTargetFields = ref<{ key: string; label: string; input_type: string; result_type?: string | null }[]>([])
const REF_LABEL_SKIP = ['heading', 'label', 'spacer', 'divider', 'table', 'reference', 'file', 'password']
/**
 * ユーザー/プロジェクト自動入力の「コピー元」候補。REF_LABEL_SKIP を流用してはいけない。
 *
 * あちらは「参照レコードの何を“ラベル”として表示するか」の選択肢で、パスワードを除くのは当然
 * （伏せ字をラベルにする意味がない）。こちらはコピー元の選択肢で、暗号化された口座番号を
 * 暗号化フィールドへ渡すことがこの機能の主目的なので、password を除いてしまうと肝心の列が
 * 一覧から消える。実際に消えていた。
 */
const AUTOFILL_SOURCE_SKIP = REF_LABEL_SKIP.filter((t) => t !== 'password')
// a reference targets either a Flow app (target_definition_id) or a system source (target_source)
const hasRefTarget = computed(() => v.value.target_definition_id != null || !!v.value.target_source)
const loadRefApps = async () => {
    if (!refApps.value.length) {
        const data = await api.get('/flow_definitions')
        refApps.value = (data ?? []).map((d: any) => ({ id: d.id, name: d.name }))
    }
    if (!refSystemSources.value.length) {
        const sys = await api.get('/flow_system_sources')
        refSystemSources.value = sys?.sources ?? []
    }
}
// load the target's fields for the label + field-copy pickers (system source or Flow app).
// Reads props.field.validation (not `v`, which is declared below and would be in the TDZ when the
// immediate watch invokes this during setup).
const loadRefFields = async () => {
    refTargetFields.value = []
    const val = (props.field?.validation ?? {}) as FlowFieldValidation
    const url = val.target_source
        ? `/flow_system_fields/${val.target_source}`
        : (val.target_definition_id ? `/flow_definition_fields/${val.target_definition_id}` : null)
    if (!url) return
    const data = await api.get(url)
    refTargetFields.value = (data?.fields ?? []).filter((f: any) => !REF_LABEL_SKIP.includes(f.input_type))
}

/** The FlowSystemSources key whose columns a ユーザー/プロジェクト field can auto-fill from. */
const autoFillSourceFor = (t: string): 'user' | 'project' | null =>
    t === 'project' ? 'project' : (t === 'user' || t === 'member') ? 'user' : null
const autoFillSource = computed(() => autoFillSourceFor(props.field.input_type))
/** Loads into the same refTargetFields the 参照 mapping editor reads, so that editor works unchanged. */
const loadAutoFillFields = async (t: string) => {
    const src = autoFillSourceFor(t)
    refTargetFields.value = []
    if (!src) return
    const data = await api.get(`/flow_system_fields/${src}`)
    refTargetFields.value = (data?.fields ?? []).filter((f: any) => !AUTOFILL_SOURCE_SKIP.includes(f.input_type))
}
/**
 * ユーザー fields are 複数選択 by default, so gating the editor on single-select would hide the feature
 * from most fields. It is shown either way; when several people are selected there is no single 役職 to
 * copy, so the runtime leaves the destinations alone and this hint says so.
 */
const autoFillMultiHint = computed(() =>
    autoFillSource.value === 'user' && v.value.multiple !== false,
)

/**
 * この項目を「コピー先」にしているフィールド（ユーザー / プロジェクト / 参照）。
 *
 * 設定はコピー元のフィールド側に置いてあるので、コピー先の項目を開いても自分が自動入力される側だと
 * 分からなかった。「なぜ勝手に値が入るのか」「なぜ手で直しても戻るのか」がこの画面から読み取れない
 * のは設定漏れと区別できないため、逆引きして出す。
 */
const autoFillIntoThis = computed(() => {
    const me = props.field.key
    if (!me) return [] as { label: string; type: string; from: string }[]

    return (props.fields ?? [])
        .filter((f) => f.key !== me)
        .flatMap((f) => (f.validation?.field_mappings ?? [])
            .filter((m) => m?.to === me)
            .map((m) => ({ label: f.label || f.key, type: f.input_type, from: m.from })))
})

const fileAccepts = FLOW_FILE_ACCEPT
const typeLabel = (t: string) => FLOW_TYPE_LABEL[t] ?? t
const hasOptions = computed(() => ['select', 'radio', 'checkbox'].includes(props.field.input_type))
const isLayout = computed(() => isLayoutType(props.field.input_type))
const labelFieldName = computed(() =>
    props.field.input_type === 'heading' ? '見出し文' : props.field.input_type === 'label' ? 'テキスト' : 'ラベル'
)
// Other formula fields ARE referenceable (chains compute multi-pass server-side) — only self and layout parts are excluded.
/**
 * 計算式で参照できるものの一覧（オートコンプリートに出る候補）。
 *
 * テーブルは「表全体」と「列ごと」の両方を出す。評価側は前から `テーブル.列` を解釈できて、
 * kintone から取り込んだアプリは実際に SUM([販管費テーブル.販管費]) の形を使っているのに、
 * この一覧が表全体しか出していなかったので、手で組むと SUM([テーブル]) になり
 * 「1列だけ合計したいのに全部の数値列が合算される」という食い違いになっていた。
 * 表全体の候補にも（表全体）と付けて、どちらを選んでいるのか読めるようにする。
 */
const referenceableFields = computed(() => {
    const out: { key: string; label: string; input_type: string }[] = []

    for (const f of props.fields ?? []) {
        if (f.key === props.field.key || isLayoutType(f.input_type) || isSecretType(f.input_type)) continue

        if (f.input_type === 'table') {
            out.push({ key: f.key, label: `${f.label}（表全体）`, input_type: f.input_type })
            for (const c of f.validation?.columns ?? []) {
                if (!c?.key || isLayoutType(c.input_type)) continue
                out.push({
                    // 挿入されるのはラベル形式。式を読んだときに何を合計しているのか分かるほうが
                    // c1/c3 より安全（列キーでも解決できるので、既存の式はそのまま動く）。
                    key: `${f.label}.${c.label || c.key}`,
                    label: `${f.label} › ${c.label || c.key}`,
                    input_type: c.input_type,
                })
            }
            continue
        }
        out.push({ key: f.key, label: f.label, input_type: f.input_type })
    }

    return out
})

watch(() => props.field, (f) => {
    if (!f) return
    // guard against validation arriving as an empty array (PHP serializes empty validation as `[]`);
    // setting props on an array is lost by JSON.stringify on save — see normalizeValidation in FlowBuilder
    if (!f.validation || Array.isArray(f.validation)) f.validation = {}
    if (f.input_type === 'formula' && !f.result_type) f.result_type = 'number'
    if (f.input_type === 'reference') {
        loadRefApps()
        loadRefFields()
        if (!Array.isArray(f.validation.field_mappings)) f.validation.field_mappings = []
    }
    // ユーザー/プロジェクト auto-fill reuses the 参照 field's mapping editor wholesale — same
    // field_mappings shape, same destination/type rules; only the source columns differ, and those come
    // from the matching FlowSystemSources entry rather than a target app.
    if (autoFillSourceFor(f.input_type)) {
        loadAutoFillFields(f.input_type)
        if (!Array.isArray(f.validation.field_mappings)) f.validation.field_mappings = []
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

// 必須 and 無効化 are mutually exclusive: a required field a user can't fill would block submission.
const setRequired = (val: boolean) => {
    props.field.is_required = val
    if (val) v.value.disabled = false
}
const setDisabled = (val: boolean) => {
    v.value.disabled = val
    if (val) props.field.is_required = false
}

const onRefTargetChange = () => {
    v.value.label_field = null
    v.value.field_mappings = [] // previous mappings referenced the old target's fields
    loadRefFields()
}
// selector carries apps as their numeric id and system sources as 'sys:<key>'
const onRefAppChange = (val: string | number | null) => {
    const s = val === null || val === '' ? '' : String(val)
    if (s.startsWith('sys:')) {
        v.value.target_source = s.slice(4)
        v.value.target_definition_id = null
    } else {
        v.value.target_definition_id = s === '' ? null : Number(s)
        v.value.target_source = null
    }
    onRefTargetChange()
}
// option lists for the searchable selectors (app / system source / label field / mapping fields)
const refSelectValue = computed(() => (v.value.target_source ? `sys:${v.value.target_source}` : (v.value.target_definition_id ?? null)))
// normal apps first, then a divider, then system sources (grouped so FlowSearchSelect draws the line)
const refAppOptions = computed(() => [
    ...refApps.value.map((a) => ({ value: a.id as number | string, label: a.name, group: 'app' })),
    ...refSystemSources.value.map((s) => ({ value: `sys:${s.key}`, label: `${s.label}（システム）`, group: 'system' })),
])
const refFieldOptions = computed(() => refTargetFields.value.map((f) => ({ value: f.key, label: f.label })))
const labelFieldOptions = computed(() => [{ value: '', label: 'レコード番号' }, ...refFieldOptions.value])
const destOptionsFor = (fromKey: string) => destFieldsFor(fromKey).map((f) => ({ value: f.key, label: f.label }))
const refLabelName = computed(() => {
    const k = v.value.label_field
    if (!k) return 'レコード番号'
    return refTargetFields.value.find((f) => f.key === k)?.label ?? k
})

// Destination fields for lookup field-copy: writable fields in THIS app (exclude self, layout,
// formula (computed), and container/reference/file types that can't take a copied scalar/value).
// 'password' is deliberately NOT skipped: an encrypted field is the only allowed destination for a
// source column that declares itself 'password' (口座番号), and the strict same-type rule below means
// a text column still cannot be mapped into one. That pairing is what keeps a secret out of a plain
// column by construction rather than by convention.
const MAP_DEST_SKIP = ['heading', 'label', 'spacer', 'divider', 'table', 'file', 'formula', 'reference']
const mappingDestFields = computed(() =>
    (props.fields ?? []).filter((f) => f.key !== props.field.key && !MAP_DEST_SKIP.includes(f.input_type))
)
const addMapping = () => {
    if (!Array.isArray(v.value.field_mappings)) v.value.field_mappings = []
    v.value.field_mappings.push({ from: '', to: '' })
}
const removeMapping = (i: number) => { v.value.field_mappings?.splice(i, 1) }

// Field-copy type compatibility (strict same-type, plus any scalar → text). A field's comparable
// "value type": short/long collapse to 'text', a formula resolves to its result_type, everything else
// is its own input_type. So date→date, number→number, formula(number)→number, and any scalar into a
// text field are allowed; arrays/booleans (checkbox/user/member/toggle) must match exactly.
const SCALAR_TO_TEXT = ['text', 'number', 'date', 'datetime', 'time', 'select', 'radio']
const valueTypeOf = (f: { input_type: string; result_type?: string | null }): string => {
    if (f.input_type === 'formula') return f.result_type || 'number'
    if (f.input_type === 'short' || f.input_type === 'long') return 'text'
    return f.input_type
}
const destAllowedForSource = (src: { input_type: string; result_type?: string | null }, dest: { input_type: string }): boolean => {
    const s = valueTypeOf(src)
    const d = valueTypeOf(dest)
    return d === s || (d === 'text' && SCALAR_TO_TEXT.includes(s))
}
// Destinations valid for a chosen source field (empty source → show all until one is picked).
const destFieldsFor = (fromKey: string) => {
    const src = fromKey ? refTargetFields.value.find((x) => x.key === fromKey) : null
    if (!src) return mappingDestFields.value
    return mappingDestFields.value.filter((d) => destAllowedForSource(src, d))
}
// Changing the source may invalidate the current destination — drop it if no longer compatible.
const onMappingFromChange = (m: { from: string; to: string }) => {
    if (m.to && !destFieldsFor(m.from).some((d) => d.key === m.to)) m.to = ''
}


const setOption = (oi: number, val: string) => { if (props.field.options) props.field.options[oi] = val }
const addOption = () => {
    if (!props.field.options) props.field.options = []
    props.field.options.push(`選択肢${props.field.options.length + 1}`)
}
const removeOption = (oi: number) => props.field.options?.splice(oi, 1)

/* ---- table columns ---- */
/**
 * A column's rule object, created on demand. TableColumn.validation has always been the same
 * FlowFieldValidation a field uses — the renderer and the server validator both read it — it simply
 * had no UI, so it stayed null on every column ever created.
 */
const colV = (col: TableColumn): FlowFieldValidation => {
    if (!col.validation) col.validation = {}

    return col.validation
}
// same mutual exclusion as a field: a required column nobody can fill would block every save
const setColDisabled = (col: TableColumn, val: boolean) => {
    colV(col).disabled = val
    if (val) col.required = false
}

// 'table' stays excluded (no nested tables). formula + reference are allowed as columns.
// note: this list never honoured projectOnly, which is why メンバー showed up as a column type even on
// apps with no project — the `deprecated` flag hides it from here as well as from the palette.
const COLUMN_TYPES = FLOW_FIELD_TYPES.filter((t) => !isLayoutType(t.type) && t.type !== 'table' && !t.deprecated)
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
const onColRefAppChange = (col: TableColumn, val: string | number | null) => {
    col.target_definition_id = val === null || val === '' ? null : Number(val)
    onColRefTarget(col)
}
const colLabelFieldOptions = (id: number | null | undefined) =>
    [{ value: '', label: 'レコード番号' }, ...colRefFields(id).map((f) => ({ value: f.key, label: f.label }))]
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
.col-del { border: 1px solid var(--formBorder); margin-top: 20px; background: var(--background-color); color: #dc2626; border-radius: 6px; padding: 7px 12px; font-size: 12px; cursor: pointer; }
.col-del:disabled { opacity: 0.4; cursor: not-allowed; }
.flow-ghost-btn { width: fit-content; }
.formula-area { width: 100%; min-height: 64px; font-family: ui-monospace, monospace; font-size: 13px; resize: vertical; }
.def-checks { display: flex; flex-direction: column; gap: 7px; }
.def-checks .fi-opt { font-size: 13px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
.def-hint { font-size: 11.5px; color: gray; margin-top: 6px; line-height: 1.8; line-break: strict; }
/* 1組を縦に積み、罫線で囲って組の境目が分かるようにする。横並びだと固定幅のサイドバーでは
   ラベルが数文字で切れてしまい、どれを選んでいるのか読めなかった。 */
.af-src { display: flex; align-items: center; gap: 6px; margin-top: 6px; padding: 6px 8px; border: 1px solid var(--calendarBorder); border-radius: 6px; font-size: 12px; }
.af-src-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.af-src-type { flex: none; font-size: 11px; color: var(--sub-color); }
.map-row { display: flex; flex-direction: column; gap: 4px; margin-top: 10px; padding: 8px; border: 1px solid var(--calendarBorder); border-radius: 6px; }
.map-line { display: flex; align-items: center; gap: 6px; }
.map-sel { flex: 1; min-width: 0; }
.map-arrow { color: gray; flex: none; font-size: 12px; width: 13px; text-align: center; }
.map-del { border: none; background: none; color: gray; cursor: pointer; padding: 4px; display: flex; flex: none; }
.map-del:hover { color: tomato; }
.achip { user-select: none; }
.tcols { display: flex; flex-direction: column; gap: 8px; }
.tcol { border: 1px solid var(--calendarBorder); border-radius: 8px; padding: 10px; background: var(--background-color); }
.tcol-h { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.tcol-n { font-size: 12px; color: gray; font-weight: 500; }
.tcol-opts { margin-top: 6px; padding-left: 10px; border-left: 2px solid var(--calendarBorder); }
.tcol-req { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 12px; color: gray; }
.sremove:disabled { opacity: .35; cursor: default; }
</style>
