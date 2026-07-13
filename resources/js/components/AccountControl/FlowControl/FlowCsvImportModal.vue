<template>
    <Modal size="large" :loader="busy" disable-scroll @close="emit('close')">
        <template #title><h2 class="ci-title">CSV取込</h2></template>
        <template #content>
            <div class="ci-wrap">
                <!-- STEP: mapping -->
                <template v-if="step === 'map'">
                    <div class="ci-lead-row">
                        <p class="ci-lead">
                            CSVの各列を項目に割り当ててください。<span class="ci-muted">（{{ rowCount }}行）</span>
                        </p>
                        <div class="ci-bulk">
                            <span class="ci-bulk-label">一括:</span>
                            <button class="ci-chip" type="button" @click="setAllTarget('__new__')">すべて新規作成</button>
                            <button class="ci-chip" type="button" @click="setAllTarget('__skip__')">すべて取り込まない</button>
                            <button class="ci-chip" type="button" @click="resetSuggested">自動判定に戻す</button>
                        </div>
                    </div>
                    <div class="ci-table-scroll">
                        <table class="ci-table">
                            <thead>
                                <tr>
                                    <th class="ci-col-h">CSVの列</th>
                                    <th class="ci-col-h">サンプル</th>
                                    <th class="ci-col-h">取り込み先の項目</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="h in headers" :key="h">
                                    <td class="ci-hname">{{ h }}</td>
                                    <td class="ci-sample">{{ sampleFor(h) }}</td>
                                    <td>
                                        <div class="ci-target">
                                            <select v-model="mapping[h]" class="custom-a-input !box-border ci-select">
                                                <option v-for="f in fields" :key="f.id" :value="String(f.id)">{{ f.label }}（{{ typeLabel(f.input_type) }}）</option>
                                                <option v-for="s in systemColumns" :key="s.key" :value="s.key">{{ s.label }}（システム）</option>
                                                <option value="__new__">＋ 新規項目として作成</option>
                                                <option value="__skip__">取り込まない</option>
                                            </select>
                                            <select v-if="mapping[h] === '__new__'" v-model="newTypes[h]" class="custom-a-input !box-border ci-typesel" title="新規項目のタイプ（推奨を自動判定）">
                                                <option v-for="t in NEW_TYPE_OPTIONS" :key="t.value" :value="t.value">{{ t.label }}</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="dupWarning" class="ci-warn">同じ項目に複数の列が割り当てられています。後の列は無視されます。</p>
                    <p class="ci-note">※ 割り当てていない必須項目は検証されません。選択肢・数値・日付などは取込時に検証されます。</p>
                    <div class="ci-actions">
                        <button class="ci-btn" @click="emit('close')">キャンセル</button>
                        <button class="ci-btn ci-primary" :disabled="!hasMapping" @click="runValidate">検証する</button>
                    </div>
                </template>

                <!-- STEP: validation result -->
                <template v-else-if="step === 'result'">
                    <div class="ci-summary">
                        <div class="ci-stat"><span class="ci-stat-n ok">{{ result.valid_count }}</span><span>有効</span></div>
                        <div class="ci-stat"><span class="ci-stat-n" :class="{ err: result.invalid.length }">{{ result.invalid.length }}</span><span>エラー</span></div>
                        <div class="ci-stat"><span class="ci-stat-n">{{ result.total }}</span><span>合計</span></div>
                    </div>
                    <div v-if="result.invalid.length" class="ci-errors-scroll">
                        <div v-for="row in result.invalid" :key="row.row" class="ci-errrow">
                            <span class="ci-errrow-no">{{ row.row }}行目</span>
                            <span v-for="(e, i) in row.errors" :key="i" class="ci-errcell">{{ e.header }}: {{ e.message }}</span>
                        </div>
                    </div>
                    <p v-else class="ci-allok">すべての行が有効です。</p>
                    <div class="ci-actions">
                        <button class="ci-btn" @click="step = 'map'">戻る</button>
                        <button class="ci-btn ci-primary" :disabled="!result.valid_count" @click="runCommit">
                            有効な{{ result.valid_count }}件を取込
                        </button>
                    </div>
                </template>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useApi } from '@/composables/api'
import Modal from '@/components/Global/Modal.vue'

interface ImportField { id: number; label: string; input_type: string; is_required: boolean; options: string[] }
interface InvalidRow { row: number; errors: { header: string; message: string }[] }

const props = defineProps<{ file: File; flowId: number | string }>()
const emit = defineEmits<{ close: []; imported: [n: number] }>()

const api = useApi()
const busy = ref(false)
const step = ref<'map' | 'result'>('map')
const headers = ref<string[]>([])
const fields = ref<ImportField[]>([])
const systemColumns = ref<{ key: string; label: string }[]>([]) // e.g. 作成日時 / 更新日時 (map to preserve source timestamps)
const sampleRows = ref<Record<string, any>[]>([])
const rowCount = ref(0)
const mapping = reactive<Record<string, string>>({})
const suggested = reactive<Record<string, string>>({}) // analyze-time auto suggestion, for "自動判定に戻す"
const newTypes = reactive<Record<string, string>>({}) // per "__new__" column: chosen field type (default = inferred recommendation)

const setAllTarget = (target: string) => { headers.value.forEach((h) => { mapping[h] = target }) }
const resetSuggested = () => { headers.value.forEach((h) => { mapping[h] = suggested[h] ?? '__skip__' }) }
const result = reactive<{ total: number; valid_count: number; invalid: InvalidRow[] }>({ total: 0, valid_count: 0, invalid: [] })

const NEW_TYPE_OPTIONS = [
    { value: 'short', label: '短文' }, { value: 'long', label: '長文' }, { value: 'number', label: '数値' },
    { value: 'date', label: '日付' }, { value: 'datetime', label: '日時' }, { value: 'time', label: '時刻' },
    { value: 'select', label: '選択' }, { value: 'radio', label: 'ラジオ' }, { value: 'checkbox', label: 'チェック' },
    { value: 'toggle', label: 'オン/オフ' },
]

const TYPE_LABELS: Record<string, string> = {
    short: '短文', long: '長文', number: '数値', date: '日付', datetime: '日時', time: '時刻',
    select: '選択', radio: 'ラジオ', checkbox: 'チェック', toggle: 'オン/オフ', user: 'ユーザー', member: 'メンバー', file: 'ファイル',
}
const typeLabel = (t: string) => TYPE_LABELS[t] ?? t

const form = (phase: string) => {
    const fd = new FormData()
    fd.append('csv', props.file)
    fd.append('flow_definition_id', String(props.flowId))
    fd.append('phase', phase)
    if (phase !== 'analyze') {
        fd.append('mapping', JSON.stringify(mapping))
        fd.append('new_field_types', JSON.stringify(newTypes))
    }
    return fd
}

const sampleFor = (h: string) => {
    const vals = sampleRows.value.map((r) => r[h]).filter((v) => v !== null && v !== undefined && String(v).trim() !== '')
    return vals.slice(0, 2).join(' , ') || '—'
}

const hasMapping = computed(() => Object.values(mapping).some((v) => v !== '__skip__'))
const dupWarning = computed(() => {
    const used = Object.values(mapping).filter((v) => v !== '__skip__' && v !== '__new__')
    return new Set(used).size !== used.length
})

const analyze = async () => {
    busy.value = true
    try {
        const data = await api.post('/flow_app_import', form('analyze'))
        if (!data) return
        headers.value = data.headers ?? []
        fields.value = data.fields ?? []
        systemColumns.value = data.system_columns ?? []
        sampleRows.value = data.sample_rows ?? []
        rowCount.value = data.row_count ?? 0
        ;(data.columns ?? []).forEach((c: any) => {
            mapping[c.header] = c.suggested
            suggested[c.header] = c.suggested
            newTypes[c.header] = c.recommended_type || 'short' // pre-fill the "new field" type with the inferred guess
        })
    } finally {
        busy.value = false
    }
}

const runValidate = async () => {
    busy.value = true
    try {
        const data = await api.post('/flow_app_import', form('validate'))
        if (!data) return
        result.total = data.total ?? 0
        result.valid_count = data.valid_count ?? 0
        result.invalid = data.invalid ?? []
        step.value = 'result'
    } finally {
        busy.value = false
    }
}

const runCommit = async () => {
    busy.value = true
    try {
        const data = await api.post('/flow_app_import', form('commit'))
        if (!data) return
        emit('imported', data.imported ?? 0)
    } finally {
        busy.value = false
    }
}

onMounted(analyze)
</script>

<style scoped>
.ci-title { font-size: 16px; font-weight: 600; }
.ci-wrap { display: flex; flex-direction: column; min-height: 0; height: 100%; color: var(--primary-color); }
.ci-lead-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.ci-lead { font-size: 13px; }
.ci-muted { color: gray; }
.ci-bulk { display: inline-flex; align-items: center; gap: 6px; }
.ci-bulk-label { font-size: 12px; color: gray; }
.ci-chip { font-size: 12px; padding: 4px 10px; border-radius: 6px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; }
.ci-chip:hover { background: var(--bg3); border-color: var(--primary-color); }
.ci-table-scroll { flex: 1; overflow: auto; border: 1px solid var(--calendarBorder); border-radius: 8px; }
.ci-table { width: 100%; border-collapse: collapse; }
.ci-col-h { position: sticky; top: 0; background: var(--bg3); text-align: left; font-size: 11px; color: gray; padding: 8px 12px; font-weight: 600; }
.ci-table td { padding: 8px 12px; border-top: 1px solid var(--calendarBorder); font-size: 13px; vertical-align: middle; }
.ci-hname { font-weight: 500; white-space: nowrap; }
.ci-sample { color: gray; font-size: 12px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ci-target { display: flex; gap: 8px; align-items: center; }
.ci-select { flex: 1; min-width: 180px; }
.ci-typesel { flex: 0 0 110px; }
.ci-warn { font-size: 12px; color: #d97706; margin-top: 8px; }
.ci-note { font-size: 11px; color: gray; margin-top: 8px; }
.ci-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; flex-shrink: 0; }
.ci-btn { font-size: 13px; padding: 8px 18px; border-radius: 7px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; }
.ci-btn:hover { background: var(--bg3); }
.ci-primary { background: var(--primary-button, var(--primary-color)); color: #fff; border-color: transparent; }
.ci-primary:hover { background: var(--primary-button, var(--primary-color)); opacity: 0.88; }
.ci-primary:disabled { opacity: 0.5; cursor: default; }
.ci-summary { display: flex; gap: 24px; padding: 10px 0 16px; }
.ci-stat { display: flex; flex-direction: column; align-items: center; font-size: 12px; color: gray; gap: 2px; }
.ci-stat-n { font-size: 26px; font-weight: 700; color: var(--primary-color); }
.ci-stat-n.ok { color: #2e7d32; }
.ci-stat-n.err { color: #e2574c; }
.ci-errors-scroll { flex: 1; overflow: auto; border: 1px solid var(--calendarBorder); border-radius: 8px; padding: 4px 0; }
.ci-errrow { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 7px 14px; border-top: 1px solid var(--calendarBorder); font-size: 12.5px; }
.ci-errrow:first-child { border-top: none; }
.ci-errrow-no { font-weight: 600; color: #e2574c; flex-shrink: 0; }
.ci-errcell { color: var(--primary-color); background: var(--bg3); padding: 1px 8px; border-radius: 4px; }
.ci-allok { flex: 1; display: flex; align-items: center; justify-content: center; color: #2e7d32; font-size: 14px; }
</style>
