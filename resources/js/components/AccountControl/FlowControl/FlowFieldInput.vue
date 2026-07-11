<template>
    <!-- layout / decoration: renders the same in every mode -->
    <div v-if="field.input_type === 'heading'" class="fi-heading">{{ field.label }}</div>
    <div v-else-if="field.input_type === 'label'" class="fi-labeltext">{{ field.label }}</div>
    <div v-else-if="field.input_type === 'spacer'" class="fi-spacer" :style="{ height: (field.validation?.height || 24) + 'px' }"></div>
    <hr v-else-if="field.input_type === 'divider'" class="fi-divider" :style="{ borderTopStyle: field.validation?.line_style || 'solid' }">

    <!-- read-only table: full grid in the detail, compact "N行" in list cells -->
    <div v-else-if="readonly && field.input_type === 'table'" class="fi-rotable">
        <template v-if="preview">
            <div v-if="tableRows.length" class="fi-tbl-scroll">
                <table class="fi-tbl fi-tbl-ro">
                    <thead>
                        <tr><th v-for="col in tableColumns" :key="col.key">{{ col.label || '列' }}</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, ri) in tableRows" :key="ri">
                            <td v-for="col in tableColumns" :key="col.key">
                                <FlowFieldInput :field="cellField(col)" :model-value="row[col.key]" :users="users" :projects="projects" readonly />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <span v-else class="fi-ro">—</span>
        </template>
        <span v-else class="fi-ro fi-tbl-count">{{ tableRows.length ? `${tableRows.length}行` : '—' }}</span>
    </div>

    <!-- read-only reference: label as a link to the target record -->
    <a
        v-else-if="readonly && field.input_type === 'reference'"
        class="fi-ref-ro"
        :class="{ 'fi-ref-ro-empty': !refSelected, 'fi-ref-ro-link': refSelected }"
        @click.stop="openRefRecord"
    >{{ refSelected ? (refSelected.label || ('#' + refSelected.number)) : '—' }}</a>

    <!-- read-only display (table cells, locked fields) -->
    <span v-else-if="readonly" class="fi-ro">
        <template v-if="isEmpty">—</template>
        <template v-else-if="field.input_type === 'toggle'">{{ val ? '✓' : '—' }}</template>
        <template v-else-if="field.input_type === 'checkbox'">
            <span v-for="o in val" :key="o" class="fi-chip">{{ o }}</span>
        </template>
        <template v-else-if="field.input_type === 'user' || field.input_type === 'member'">
            <span v-for="id in val" :key="id" class="fi-chip">{{ userName(id) }}</span>
        </template>
        <template v-else-if="field.input_type === 'file'">
            <!-- detail: every file, clickable to preview -->
            <template v-if="preview">
                <button v-for="(f, i) in arrayVal" :key="i" type="button" class="fi-fileitem fi-file-btn" @click="openPreview(i)">
                    <img v-if="f?.mime_type === 'image' && f?.url" :src="f.url" class="fi-thumb" alt="">
                    <FileIcon v-else class="fi-fileicon" :ext="f?.extension" />
                    <span class="fi-fname"><span class="fi-fname-base">{{ fileBase(f) }}</span><span class="fi-fname-ext">{{ fileExt(f) }}</span></span>
                </button>
            </template>
            <!-- table: first file + a "+N" badge for the rest -->
            <template v-else>
                <span class="fi-fileitem">
                    <img v-if="arrayVal[0]?.mime_type === 'image' && arrayVal[0]?.url" :src="arrayVal[0].url" class="fi-thumb" alt="">
                    <FileIcon v-else class="fi-fileicon" :ext="arrayVal[0]?.extension" />
                    <span class="fi-fname"><span class="fi-fname-base">{{ fileBase(arrayVal[0]) }}</span><span class="fi-fname-ext">{{ fileExt(arrayVal[0]) }}</span></span>
                </span>
                <span v-if="arrayVal.length > 1" class="fi-filemore">+{{ arrayVal.length - 1 }}</span>
            </template>
        </template>
        <template v-else-if="field.input_type === 'select' || field.input_type === 'radio'"><span class="fi-pill">{{ val }}</span></template>
        <template v-else-if="field.input_type === 'date' || field.input_type === 'datetime' || field.input_type === 'time'"><span class="fi-mono">{{ val }}</span></template>
        <template v-else-if="field.input_type === 'number'">{{ formatNumber(val) }}</template>
        <template v-else-if="field.input_type === 'formula'">{{ formatFormula(val) }}</template>
        <template v-else-if="field.input_type === 'project'"><span class="fi-pill">{{ projectName(val) }}</span></template>
        <template v-else><span class="fi-text" :class="{ 'fi-text-full': preview }"><template v-for="(p, i) in linkify(val)" :key="i"><a v-if="p.href" :href="p.href" class="fi-link" target="_blank" rel="noopener noreferrer" @click.stop>{{ p.text }}</a><template v-else>{{ p.text }}</template></template></span></template>
    </span>

    <!-- editable -->
    <template v-else>
        <input v-if="field.input_type === 'short'" type="text" v-model="val" class="fi-input" :placeholder="field.label">
        <textarea v-else-if="field.input_type === 'long'" v-model="val" class="fi-input fi-area"></textarea>
        <input v-else-if="field.input_type === 'number'" type="number" v-model.number="val" class="fi-input">
        <input v-else-if="field.input_type === 'date'" type="date" v-model="val" class="fi-input">
        <input v-else-if="field.input_type === 'datetime'" type="datetime-local" v-model="val" class="fi-input">
        <input v-else-if="field.input_type === 'time'" type="time" v-model="val" class="fi-input">
        <select v-else-if="field.input_type === 'select'" v-model="val" class="fi-input">
            <option :value="null">—</option>
            <option v-for="o in field.options || []" :key="o" :value="o">{{ o }}</option>
        </select>
        <div v-else-if="field.input_type === 'radio'" class="fi-opts">
            <label v-for="o in field.options || []" :key="o" class="fi-opt">
                <input type="radio" :value="o" v-model="val"> {{ o }}
            </label>
        </div>
        <div v-else-if="field.input_type === 'checkbox'" class="fi-opts">
            <label v-for="o in field.options || []" :key="o" class="fi-opt">
                <input type="checkbox" :value="o" :checked="arrayVal.includes(o)" @change="toggleArray(o)"> {{ o }}
            </label>
        </div>
        <span v-else-if="field.input_type === 'toggle'" class="sw" :class="{ on: !!val }" @click="val = !val"></span>
        <MemberSelector
            v-else-if="field.input_type === 'user' || field.input_type === 'member'"
            v-model="selectedUsers"
            :options="(users as any)"
            :multiple="userMultiple"
            compact
            :place-holder="field.label || 'ユーザーを選択'"
        />
        <div v-else-if="field.input_type === 'file'" class="fi-files">
            <div v-for="(f, i) in arrayVal" :key="f?.id ?? i" class="fi-fileitem fi-file-edit">
                <button type="button" class="fi-file-open" @click="openPreview(i)">
                    <img v-if="f?.mime_type === 'image' && f?.url" :src="f.url" class="fi-thumb" alt="">
                    <FileIcon v-else class="fi-fileicon" :ext="f?.extension" />
                    <span class="fi-fname"><span class="fi-fname-base">{{ fileBase(f) }}</span><span class="fi-fname-ext">{{ fileExt(f) }}</span></span>
                </button>
                <button type="button" class="fi-fileremove" title="削除" @click="removeFile(i)">×</button>
            </div>
            <label class="fi-fileadd">
                <input type="file" multiple hidden :accept="acceptAttr" @change="addFiles">
                <span>{{ uploading ? 'アップロード中…' : '＋ ファイルを選択' }}</span>
            </label>
        </div>
        <span v-else-if="field.input_type === 'formula'" class="fi-ro fi-formula">{{ isEmpty ? '—' : formatFormula(val) }}</span>
        <div v-else-if="field.input_type === 'table'" class="fi-table">
            <div class="fi-tbl-scroll">
                <table class="fi-tbl">
                    <thead>
                        <tr>
                            <th v-for="col in tableColumns" :key="col.key" :style="col.width ? { width: col.width + 'px' } : undefined">
                                {{ col.label || '列' }}<span v-if="col.required" class="fi-tbl-req">*</span>
                            </th>
                            <th class="fi-tbl-act"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, ri) in tableRows" :key="ri">
                            <td v-for="col in tableColumns" :key="col.key">
                                <FlowFieldInput
                                    :field="cellField(col)"
                                    :model-value="row[col.key]"
                                    :users="users"
                                    :projects="projects"
                                    :readonly="col.input_type === 'formula'"
                                    @update:model-value="setCell(ri, col.key, $event)"
                                />
                            </td>
                            <td class="fi-tbl-act">
                                <button type="button" class="fi-tbl-del" title="行を削除" @click="removeRow(ri)">×</button>
                            </td>
                        </tr>
                        <tr v-if="!tableRows.length">
                            <td :colspan="tableColumns.length + 1" class="fi-tbl-empty">行がありません</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" class="fi-tbl-add" @click="addRow">＋ 行を追加</button>
        </div>
        <div v-else-if="field.input_type === 'reference'" class="fi-ref">
            <div v-if="refSelected" class="fi-ref-chip">
                <span class="fi-ref-label">{{ refSelected.label || ('#' + refSelected.number) }}</span>
                <button type="button" class="fi-ref-clear" title="解除" @click="clearRef">×</button>
            </div>
            <div v-else class="fi-ref-search">
                <input
                    type="text"
                    v-model="refQuery"
                    class="fi-input"
                    :placeholder="refPlaceholder"
                    :disabled="!refTargetId"
                    @focus="openRef"
                    @input="onRefInput"
                    @blur="closeRef"
                >
                <div v-if="refOpen" class="fi-ref-menu">
                    <div v-if="refLoading" class="fi-ref-empty">検索中…</div>
                    <template v-else>
                        <button
                            v-for="c in refResults"
                            :key="c.id"
                            type="button"
                            class="fi-ref-opt"
                            @mousedown.prevent
                            @click="pickRef(c)"
                        >
                            <span class="fi-ref-opt-label">{{ c.label }}</span>
                            <span class="fi-ref-opt-no">#{{ c.number }}</span>
                        </button>
                        <div v-if="!refResults.length" class="fi-ref-empty">該当するレコードがありません</div>
                    </template>
                </div>
            </div>
        </div>
        <div v-else-if="field.input_type === 'project'" class="fi-project">
            <ItemSelector
                :multiple="false"
                :options="(projects as any)"
                :reduce="(o: any) => o.id"
                label="name"
                v-model="val"
                :clearable="true"
                :close-on-select="true"
                place-holder="プロジェクトを選択"
            />
        </div>
        <input v-else type="text" v-model="val" class="fi-input">
    </template>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import { useFilePreview } from '@/store/filePreview'
import FileIcon from '@/components/Board/Mixed/FileIcon.vue'
import MemberSelector from '@/components/Form/MemberSelector.vue'
import ItemSelector from '@/components/Form/ItemSelector.vue'
import type { FlowField, FlowOptionUser, FlowOptionProject } from '@/types/flow'

const props = defineProps<{
    field: FlowField
    modelValue: any
    users?: FlowOptionUser[]
    projects?: FlowOptionProject[]
    readonly?: boolean
    preview?: boolean
}>()
const projectName = (id: any) => {
    if (id === null || id === undefined || id === '') return '—'
    return props.projects?.find((p) => p.id === Number(id))?.name ?? `#${id}`
}
const emit = defineEmits<{ 'update:modelValue': [any] }>()
defineOptions({ name: 'FlowFieldInput' }) // explicit name so table cells can recurse into this component

const api = useApi()
const filePreview = useFilePreview()
const uploading = ref(false)

// Split filename so the extension always stays visible while the base truncates ("app-2026….csv").
const fileName = (f: any): string => String(f?.name ?? (typeof f === 'string' ? f : ''))
const fileExt = (f: any): string => { const n = fileName(f); const i = n.lastIndexOf('.'); return i > 0 ? n.slice(i) : '' }
const fileBase = (f: any): string => { const n = fileName(f); const i = n.lastIndexOf('.'); return i > 0 ? n.slice(0, i) : n }

// Open the shared FilePreview modal (image/pdf/text/video/audio preview + download menu for the rest).
const openPreview = (i: number) => {
    const src = arrayVal.value
    const files = src.filter((f: any) => f?.url).map((f: any) => ({ ...f, file_path: f.url, doc_path: f.url }))
    if (!files.length) return
    const clicked = src[i]
    const idx = clicked?.id != null ? files.findIndex((f: any) => f.id === clicked.id) : 0
    filePreview.setFilePreview({ active: true, files, target: files[idx] ?? files[0], source: 'flow', index: idx < 0 ? 0 : idx, message: null })
}
const acceptAttr = computed(() => (props.field.validation?.accept?.length ? props.field.validation.accept.join(',') : undefined))

// Upload to the shared temp store (/attach_upload_api); backend moves them to the record folder on save.
const addFiles = async (e: Event) => {
    const target = e.target as HTMLInputElement
    if (!target.files?.length) return
    uploading.value = true
    try {
        const fd = new FormData()
        Array.from(target.files).forEach((file, idx) => fd.append(String(idx), file))
        const uploaded = (await api.post('/attach_upload_api', fd)) ?? []
        const added = uploaded.map((u: any) => ({
            id: u.id, name: u.name, extension: u.extension, mime_type: u.mime_type,
            size: u.size, user_id: u.user_id, url: `/cdn/temp_upload/${u.id}.${u.extension}`,
        }))
        emit('update:modelValue', [...arrayVal.value, ...added])
    } finally {
        uploading.value = false
        target.value = ''
    }
}

const removeFile = (i: number) => {
    const next = [...arrayVal.value]
    const [removed] = next.splice(i, 1)
    if (removed && !removed.stored) api.post('/remove_temp_file', { id: removed.id }, { silent: true })
    emit('update:modelValue', next)
}

const val = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
})
const arrayVal = computed<any[]>(() => (Array.isArray(props.modelValue) ? props.modelValue : []))
const isEmpty = computed(() => {
    const v = props.modelValue
    return v === null || v === undefined || v === '' || (Array.isArray(v) && v.length === 0)
})

const toggleArray = (o: string) => {
    const next = [...arrayVal.value]
    const i = next.indexOf(o)
    if (i >= 0) next.splice(i, 1)
    else next.push(o)
    emit('update:modelValue', next)
}

const userName = (id: number) => props.users?.find((u) => u.id === id)?.name ?? `#${id}`

// split read-only text into plain runs + clickable URL/email links (preserving whitespace/newlines)
// URL charset is RFC-3986-safe ASCII only, so a link stops at CJK / full-width punctuation (e.g. 「…docs。次へ」)
const LINK_RE = /(https?:\/\/[A-Za-z0-9\-._~:/?#@!$&*+=%,;]+|www\.[A-Za-z0-9\-._~:/?#@!$&*+=%,;]+|[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})/g
const LINK_TRAIL = /[)\]}>」』】》〉、。，．！？!?.,;:'"）]+$/
const linkify = (raw: any): { text: string; href?: string }[] => {
    const text = raw == null ? '' : String(raw)
    if (!text) return [{ text: '' }]
    const parts: { text: string; href?: string }[] = []
    let last = 0
    for (const m of text.matchAll(LINK_RE)) {
        const start = m.index ?? 0
        let tok = m[0]
        const tm = tok.match(LINK_TRAIL) // don't let trailing punctuation into the link
        const trail = tm ? tm[0] : ''
        if (trail) tok = tok.slice(0, tok.length - trail.length)
        if (!tok) continue
        if (start > last) parts.push({ text: text.slice(last, start) })
        const isEmail = tok.includes('@') && !/^https?:\/\//i.test(tok)
        const href = isEmail ? `mailto:${tok}` : (/^www\./i.test(tok) ? `https://${tok}` : tok)
        parts.push({ text: tok, href })
        last = start + tok.length + trail.length
        if (trail) parts.push({ text: trail })
    }
    if (last < text.length) parts.push({ text: text.slice(last) })
    return parts.length ? parts : [{ text }]
}

// user/member field: flow stores ID arrays; MemberSelector wants full User objects (return-object). Bridge both ways.
const userMultiple = computed(() => props.field.validation?.multiple !== false) // default = multiple (existing behavior)
const usersById = computed<Record<number, FlowOptionUser>>(() => {
    const m: Record<number, FlowOptionUser> = {}
    ;(props.users ?? []).forEach((u) => { m[u.id] = u })
    return m
})
const selectedUsers = computed<any>({
    get() {
        const ids = Array.isArray(props.modelValue) ? props.modelValue : (props.modelValue != null && props.modelValue !== '' ? [props.modelValue] : [])
        const objs = ids.map((id: number) => usersById.value[id] ?? ({ id, name: `#${id}` } as any))
        return userMultiple.value ? objs : (objs[0] ?? null)
    },
    set(v: any) {
        const arr = Array.isArray(v) ? v : (v ? [v] : [])
        emit('update:modelValue', arr.map((u: any) => u.id))
    },
})
/* ---- table field: rows of cells, each cell a nested FlowFieldInput ---- */
const tableColumns = computed<any[]>(() => props.field.validation?.columns || [])
const tableRows = computed<any[]>(() => (Array.isArray(props.modelValue) ? props.modelValue : []))
const cellFields = computed<Record<string, FlowField>>(() => {
    const m: Record<string, FlowField> = {}
    for (const c of tableColumns.value) {
        m[c.key] = {
            key: c.key, label: c.label, input_type: c.input_type, options: c.options ?? null,
            // reference columns keep target/label at the column root — surface them where the reference input reads them
            validation: {
                ...(c.validation ?? {}),
                target_definition_id: c.target_definition_id ?? c.validation?.target_definition_id ?? null,
                label_field: c.label_field ?? c.validation?.label_field ?? null,
            },
            formula: c.formula ?? null,
            result_type: c.result_type ?? null,
        } as FlowField
    }
    return m
})
const cellField = (col: any): FlowField => cellFields.value[col.key]
const defaultCell = (c: any) => {
    if (['checkbox', 'file', 'user', 'member'].includes(c.input_type)) return []
    if (c.input_type === 'toggle') return false
    return null
}
const addRow = () => {
    const row: Record<string, any> = {}
    for (const c of tableColumns.value) row[c.key] = defaultCell(c)
    emit('update:modelValue', [...tableRows.value, row])
}
const removeRow = (i: number) => {
    const next = [...tableRows.value]
    next.splice(i, 1)
    emit('update:modelValue', next)
}
const setCell = (ri: number, key: string, value: any) => {
    emit('update:modelValue', tableRows.value.map((r, i) => (i === ri ? { ...r, [key]: value } : r)))
}

/* ---- reference field: single-record picker (snapshot {id, number, label}) ---- */
const refTargetId = computed(() => props.field.validation?.target_definition_id ?? null)
const refLabelField = computed(() => props.field.validation?.label_field ?? '')
const refSelected = computed<any>(() => (props.modelValue && props.modelValue.id ? props.modelValue : null))
const refQuery = ref('')
const refOpen = ref(false)
const refLoading = ref(false)
const refResults = ref<any[]>([])
let refTimer: ReturnType<typeof setTimeout> | null = null
const refPlaceholder = computed(() => (refTargetId.value ? 'レコードを検索…' : '参照先アプリが未設定です'))
const searchRef = async () => {
    if (!refTargetId.value) return
    refLoading.value = true
    try {
        const q = encodeURIComponent(refQuery.value.trim())
        const lf = encodeURIComponent(refLabelField.value || '')
        const data = await api.get(`/flow_reference_search/${refTargetId.value}?q=${q}&label_field=${lf}`)
        refResults.value = data?.records ?? []
    } finally {
        refLoading.value = false
    }
}
const openRef = () => { if (!refTargetId.value) return; refOpen.value = true; searchRef() }
const onRefInput = () => { refOpen.value = true; if (refTimer) clearTimeout(refTimer); refTimer = setTimeout(searchRef, 250) }
const closeRef = () => { setTimeout(() => { refOpen.value = false }, 120) }
const pickRef = (c: any) => { emit('update:modelValue', { id: c.id, number: c.number, label: c.label }); refQuery.value = ''; refOpen.value = false }
const clearRef = () => emit('update:modelValue', null)
const router = useRouter()
const openRefRecord = () => {
    const sel = refSelected.value
    if (!sel || !refTargetId.value || sel.number == null) return
    router.push({ name: 'flow-record-detail', params: { flowId: refTargetId.value, recordId: sel.number } })
}

const formatNumber = (n: any) => (n === null || n === '' ? '' : Number(n).toLocaleString())
// Formula results: number-typed → comma format (also clears float noise like 385000.00000000006); text → raw.
const formatFormula = (v: any) => {
    if (v === null || v === '' || v === undefined) return ''
    if (props.field.result_type === 'text') return String(v)
    return isNaN(Number(v)) ? String(v) : Number(v).toLocaleString()
}
</script>

<style scoped>
/* global main.css forces `box-sizing: unset !important` on *, so re-assert border-box here (class beats * even with !important) or width:100% + padding overflows the block */
.fi-input { width: 100%; box-sizing: border-box !important; font-size: 13px; padding: 6px 9px; border: 1px solid var(--formBorder); border-radius: 6px; background: var(--background-color); color: var(--primary-color); }
.fi-area { min-height: 64px; resize: vertical; }
/* project picker: match the thin, rounded look of .fi-input (ItemSelector ships a bolder/square shell) */
.fi-project { max-width: 100%; }
.fi-project :deep(.item-selector-shell) { border: 1px solid var(--formBorder) !important; border-radius: 6px !important; box-sizing: border-box !important; overflow: hidden; }
.fi-project :deep(.one-selector .v-field__input) { min-height: 34px; padding-top: 2px; padding-bottom: 2px; font-size: 13px; }
.fi-multi { min-height: 80px; }
.fi-opts { display: flex; flex-wrap: wrap; gap: 11px 18px; }
.fi-opt { font-size: 13px; display: inline-flex; align-items: flex-start; gap: 9px; cursor: pointer; line-height: 1.5; }
/* custom checkbox / radio (native styling is cheap) */
.fi-opt input[type="checkbox"], .fi-opt input[type="radio"] {
    appearance: none; -webkit-appearance: none;
    box-sizing: border-box !important;
    width: 18px; height: 18px; margin: 1px 0 0; flex-shrink: 0;
    border: 1.5px solid var(--formBorder); background: var(--background-color);
    position: relative; cursor: pointer; transition: background .12s, border-color .12s, box-shadow .12s;
}
.fi-opt input[type="checkbox"] { border-radius: 5px; }
.fi-opt input[type="radio"] { border-radius: 50%; }
.fi-opt:hover input:not(:checked) { border-color: var(--primary-color); }
.fi-opt input:checked { background: var(--primary-color); border-color: var(--primary-color); }
.fi-opt input[type="checkbox"]:checked::after {
    content: ""; position: absolute; left: 5px; top: 2px;
    width: 4px; height: 8px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg);
}
.fi-opt input[type="radio"]:checked::after {
    content: ""; position: absolute; inset: 0; margin: auto;
    width: 7px; height: 7px; border-radius: 50%; background: #fff;
}
.fi-opt input:focus-visible { outline: none; box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-color) 25%, transparent); }
.fi-ro { font-size: 13px; }
.fi-formula { color: var(--primary-color); font-weight: 500; }
.fi-chip { display: inline-block; font-size: 11.5px; padding: 2px 8px; margin: 1px 3px 1px 0; border-radius: 4px; background: color-mix(in srgb, var(--app-accent, var(--bg3)) 50%, var(--background-color)); color: color-mix(in srgb, var(--app-accent, var(--primary-color)) 45%, var(--primary-color)); }
.fi-pill { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 4px; font-size: 12px; font-weight: 500; background: color-mix(in srgb, var(--app-accent, var(--bg3)) 50%, var(--background-color)); color: color-mix(in srgb, var(--app-accent, var(--primary-color)) 45%, var(--primary-color)); }
.fi-mono { font-size: 13px; }
.fi-link { color: var(--primary-color); text-decoration: underline; overflow-wrap: anywhere; }
/* full record-detail display preserves line breaks + comfortable spacing; table/list cells stay single-line */
.fi-text-full { white-space: pre-wrap; overflow-wrap: break-word; line-height: 1.7; }
.fi-files { display: flex; flex-direction: column; gap: 6px; }
.fi-fileitem { display: inline-flex; align-items: center; gap: 6px; max-width: 100%; vertical-align: middle; margin: 1px 6px 1px 0; }
.fi-file-btn { border: none; background: none; padding: 0; cursor: pointer; color: var(--primary-color); }
.fi-file-open { display: inline-flex; align-items: center; gap: 6px; border: none; background: none; padding: 0; cursor: pointer; color: var(--primary-color); min-width: 0; text-align: left; }
.fi-file-open:hover .fi-fname, .fi-file-btn:hover .fi-fname { text-decoration: underline; }
.fi-fileicon { flex-shrink: 0; display: inline-flex; }
.fi-fileicon :deep(svg) { width: 16px !important; min-width: 16px !important; height: 20px !important; display: block; }
.fi-thumb { width: 20px; height: 20px; object-fit: cover; border-radius: 3px; flex-shrink: 0; }
.fi-fname { display: inline-flex; min-width: 0; max-width: 20ch; font-size: 12.5px; }
.fi-fname-base { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fi-fname-ext { flex-shrink: 0; white-space: nowrap; }
.fi-filemore { font-size: 11px; color: gray; background: var(--bg3); border-radius: 8px; padding: 1px 7px; margin-left: 2px; align-self: center; flex-shrink: 0; }
.fi-fileremove { border: none; background: none; color: gray; cursor: pointer; font-size: 15px; line-height: 1; padding: 0 2px; flex-shrink: 0; }
.fi-fileadd { display: inline-flex; align-items: center; align-self: flex-start; font-size: 12px; padding: 5px 12px; border: 1px dashed var(--formBorder); border-radius: 6px; color: var(--primary-color); cursor: pointer; }
.fi-fileadd:hover { background: var(--bg3); }
.fi-heading { font-size: 15px; font-weight: 500; color: var(--primary-color); border-bottom: 1px solid var(--calendarBorder); padding-bottom: 4px; }
.fi-labeltext { font-size: 13px; color: var(--primary-color); white-space: pre-wrap; line-height: 1.6; }
.fi-spacer { width: 100%; }
.fi-divider { width: 100%; border: none; border-top-width: 1.5px; border-top-color: var(--formBorder); margin: 6px 0; }
.sw { width: 38px; height: 22px; border-radius: 11px; background: var(--formBorder); position: relative; cursor: pointer; display: inline-block; transition: background .12s; }
.sw.on { background: var(--primary-color); }
.sw::after { content: ""; position: absolute; width: 18px; height: 18px; border-radius: 50%; background: #fff; top: 2px; left: 2px; transition: left .12s; }
.sw.on::after { left: 18px; }
/* table field */
.fi-tbl-scroll { overflow-x: auto; border: 1px solid var(--calendarBorder); border-radius: 6px; }
.fi-tbl { border-collapse: collapse; width: 100%; font-size: 13px; }
.fi-tbl th { text-align: left; font-size: 11.5px; color: gray; font-weight: 500; padding: 7px 9px; background: var(--bg3); border-bottom: 1px solid var(--calendarBorder); border-right: 1px solid var(--calendarBorder); white-space: nowrap; min-width: 110px; }
.fi-tbl td { padding: 3px 5px; border-bottom: 1px solid var(--calendarBorder); border-right: 1px solid var(--calendarBorder); vertical-align: top; min-width: 110px; }
.fi-tbl tr:last-child td { border-bottom: none; }
.fi-tbl th:last-child, .fi-tbl td:last-child { border-right: none; }
.fi-tbl-req { color: #e2574c; margin-left: 2px; }
.fi-tbl-act { width: 36px; min-width: 36px !important; text-align: center; }
.fi-tbl-del { border: none; background: none; color: gray; cursor: pointer; font-size: 16px; line-height: 1; padding: 4px 5px; }
.fi-tbl-del:hover { color: #e2574c; }
.fi-tbl-empty { text-align: center; color: gray; font-size: 12px; padding: 12px; }
/* cell inputs sit flush inside the grid — the td border provides the structure */
.fi-tbl td :deep(.fi-input) { border: 1px solid transparent; background: transparent; }
.fi-tbl td :deep(.fi-input:focus) { border-color: var(--formBorder); background: var(--background-color); }
.fi-tbl td :deep(.fi-opts) { padding: 4px 2px; gap: 6px 12px; }
.fi-tbl-add { margin-top: 8px; font-size: 12px; padding: 6px 12px; border: 1px dashed var(--formBorder); border-radius: 6px; color: var(--primary-color); background: var(--background-color); cursor: pointer; }
.fi-tbl-add:hover { background: var(--bg3); }
/* reference field picker */
.fi-ref { position: relative; }
.fi-ref-search { position: relative; }
.fi-ref-menu { position: absolute; z-index: 30; top: calc(100% + 4px); left: 0; right: 0; max-height: 260px; overflow-y: auto; overflow-x: hidden; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,.12); padding: 4px; box-sizing: border-box !important; }
.fi-ref-opt { display: flex; align-items: center; justify-content: space-between; gap: 10px; width: 100%; box-sizing: border-box !important; text-align: left; border: none; background: none; padding: 7px 9px; border-radius: 6px; cursor: pointer; font-size: 13px; color: var(--primary-color); }
.fi-ref-opt:hover { background: var(--bg3); }
.fi-ref-opt-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
.fi-ref-opt-no { flex-shrink: 0; font-size: 11px; color: gray; }
.fi-ref-empty { padding: 9px; font-size: 12px; color: gray; text-align: center; }
.fi-ref-chip { display: inline-flex; align-items: center; gap: 8px; max-width: 100%; padding: 5px 6px 5px 11px; border: 1px solid var(--formBorder); border-radius: 7px; background: var(--bg3); }
.fi-ref-label { font-size: 13px; color: var(--primary-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
.fi-ref-clear { border: none; background: none; color: gray; cursor: pointer; font-size: 15px; line-height: 1; padding: 0 4px; flex-shrink: 0; }
.fi-ref-clear:hover { color: #e2574c; }
.fi-ref-ro { font-size: 13px; }
.fi-ref-ro-link { color: var(--primary-color); cursor: pointer; text-decoration: underline; text-underline-offset: 2px; }
.fi-ref-ro-link:hover { opacity: .8; }
.fi-ref-ro-empty { color: gray; }
/* read-only table */
.fi-tbl-ro td { padding: 6px 9px; font-size: 13px; vertical-align: top; }
.fi-tbl-count { color: gray; }
</style>
