<template>
    <div class="admin-window rv-screen">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div class="rv-card">
            <div class="rv-head">
                <div class="rv-r1">
                    <Back class="rv-back" :size="14" @click="back" />
                    <FlowAppIcon
                        v-if="definition"
                        class="rv-appicon"
                        :icon-svg="definition.icon_svg"
                        :icon-image="definition.icon_image"
                        :color-id="definition.color_id"
                        :name="definition.name"
                        :seed="definition.id"
                        :size="30"
                        round
                    />
                    <span class="rv-title" :title="definition?.name">{{ definition?.name }}</span>
                    <div class="rv-actions">
                        <button v-if="canManage" class="rv-actbtn" title="アプリを編集" @click="editApp">
                            <Edit :size="14" /><span class="rv-actlabel">編集</span>
                        </button>
                        <ItemMenu v-if="canExport || canImport" :items="csvItems" title="CSV入出力">
                            <template #default="{ show, active }">
                                <div class="rv-actbtn" :class="{ active }" @click.stop="show">
                                    <span class="rv-csv-inner">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none">
                                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" stroke="var(--primary-color)" stroke-width="1.5" stroke-linejoin="round" />
                                            <path d="M13 2v7h7" stroke="var(--primary-color)" stroke-width="1.5" stroke-linejoin="round" />
                                        </svg>
                                        <span class="rv-actlabel">CSV</span>
                                    </span>
                                </div>
                            </template>
                        </ItemMenu>
                    </div>
                    <input ref="importInput" type="file" accept=".csv" class="hidden" @change="importCsv">
                </div>
                <div class="rv-r2">
                    <div class="rv-searchwrap">
                        <PostSearchBar className="newChatMemberSearch" :customPlaceHolder="searchPlaceholder" @searchStart="onSearch" />
                    </div>
                    <div class="rv-viewinfo">
                        <select v-if="views.length > 1" v-model="activeViewId" class="rv-ctrl" @change="onViewChange">
                            <option v-for="v in views" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                        <span v-else-if="activeView" class="rv-viewname">{{ activeView.name }}</span>
                        <button v-if="canBulk && selected.size" class="rv-bulkdel" @click="bulkDelete">選択削除 ({{ selected.size }})</button>
                        <span class="rv-count">{{ totalCount }}件</span>
                    </div>
                </div>
            </div>

            <div v-if="definition" id="rvScroll" class="rv-scroll">
                <table class="rv-table">
                    <thead>
                        <tr>
                            <th v-if="canBulk" class="rv-th rv-th-check" @click.stop>
                                <input type="checkbox" :checked="allSelected" @change="toggleAll">
                            </th>
                            <th v-for="c in columns" :key="c.key" class="rv-th" :class="{ num: isNumericCol(c) }" @click="toggleSort(c.ref)">
                                <span class="rv-thlabel">{{ c.label }}<span v-if="String(sortRef) === String(c.ref)" class="rv-arrow">{{ sortDir === 'asc' ? '↑' : '↓' }}</span></span>
                            </th>
                            <th class="rv-th rv-th-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="rec in displayRecords" :key="rec.id" class="rv-row" @click="openRecord(rec)">
                            <td v-if="canBulk" class="rv-td rv-td-check" @click.stop>
                                <input type="checkbox" :checked="selected.has(rec.id)" :disabled="!rec.can_delete" @change="toggleSelect(rec.id)">
                            </td>
                            <td v-for="c in columns" :key="c.key" class="rv-td" :class="{ num: isNumericCol(c) }">
                                <template v-if="c.system">
                                    <span v-if="c.ref === '$record_number'" class="rv-idcell">{{ rec.record_number }}</span>
                                    <span v-else-if="c.ref === '$status'"><span v-if="rec.current_status" class="rv-statuscell">{{ rec.current_status }}</span></span>
                                    <span v-else class="rv-datecell">{{ sysDate(rec, c.ref) }} <span class="rv-time">{{ sysTime(rec, c.ref) }}</span></span>
                                </template>
                                <FlowFieldInput v-else :field="c.field!" :model-value="rec.values[c.field!.id!]" :users="users" :projects="projects" readonly />
                            </td>
                            <td class="rv-td rv-td-action" @click.stop>
                                <div class="rv-actions">
                                    <button v-if="rec.can_edit" class="rv-actbtn" title="編集" @click="editRecord(rec)">
                                        <Edit size="13" />
                                    </button>
                                    <button v-if="rec.can_delete" class="rv-actbtn rv-actbtn-del" title="削除" @click="deleteRecord(rec)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </button>
                                    <span v-if="!rec.can_edit && !rec.can_delete" class="rv-detail">詳細</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!displayRecords.length && !loading">
                            <td :colspan="columns.length + 1 + (canBulk ? 1 : 0)" class="rv-empty">レコードがありません。</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pageCount > 1" class="rv-pager">
                <PostSearchPager :possiblePage="pageCount" :activePath="page" @setNavi="onNavi" @setActivePage="setPage" />
            </div>
        </div>

        <FloatButton v-if="permissions?.add" title="新規作成" hideOn="rvScroll" @action="openNew">
            <template #icon><AddIcon size="15" /></template>
        </FloatButton>

        <FlowCsvImportModal
            v-if="importFile && definition"
            :file="importFile"
            :flow-id="definition.id!"
            @close="closeImport"
            @imported="onImported"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import FlowFieldInput from './FlowFieldInput.vue'
import FlowCsvImportModal from './FlowCsvImportModal.vue'
import Back from '@/components/Icons/Back.vue'
import Edit from '@/components/Icons/Edit.vue'
import PostSearchBar from '@/components/Post/PostSearchBar.vue'
import PostSearchPager from '@/components/Post/PostSearchPager.vue'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import FloatButton from '@/components/Global/FloatButton.vue'
import AddIcon from '@/components/Form/AddIcon.vue'
import FlowAppIcon from './FlowAppIcon.vue'
import { pageTitleOverride } from '@/composables/pageTitle'
import { resolveColumns, applyFilters, applySort, systemColumnValue, type ResolvedColumn } from '@/utils/flowView'
import type { FlowDefinitionApi, FlowRecordDto, FlowAppPermissionsDto, FlowOptionUser, FlowOptionProject, FlowViewApi, FlowRecordsResponse } from '@/types/flow'
import type { MenuList } from '@/interface/globalInterface'

const api = useApi()
const dialog = useDialog()
const route = useRoute()
const router = useRouter()

const loading = ref(true)
const definition = ref<FlowDefinitionApi | null>(null)
// reflect the opened app's name in the browser tab title
watch(() => definition.value?.name, (name) => { if (name) pageTitleOverride.value = name })
const permissions = ref<FlowAppPermissionsDto | null>(null)
const records = ref<FlowRecordDto[]>([])
const users = ref<FlowOptionUser[]>([])
const projects = ref<FlowOptionProject[]>([])
const views = ref<FlowViewApi[]>([])
const activeViewId = ref<number | null>(null)
const search = ref('')
const sortRef = ref<number | string | null>(null)
const sortDir = ref<'asc' | 'desc'>('asc')
const importInput = ref<HTMLInputElement | null>(null)

const flowId = computed(() => route.params.flowId)
const activeView = computed<FlowViewApi | null>(() => views.value.find((v) => v.id === activeViewId.value) ?? views.value[0] ?? null)
const columns = computed(() => resolveColumns(activeView.value, definition.value?.fields ?? [], !!definition.value?.use_status_flow))

const isNumericCol = (c: ResolvedColumn) => !c.system && (c.field?.input_type === 'number' || c.field?.input_type === 'formula')

const searchPlaceholder = computed(() => `${definition.value?.name ?? 'レコード'}を検索`)

const canExport = computed(() => !!permissions.value?.export)
const canImport = computed(() => !!permissions.value?.import)
const canManage = computed(() => !!permissions.value?.manage)
const editApp = () => router.push({ name: 'flow-builder', params: { flowId: flowId.value } })
const csvItems = computed<MenuList[]>(() => {
    const items: MenuList[] = []
    if (canExport.value) items.push({ title: 'CSV出力', action: exportCsv })
    if (canImport.value) items.push({ title: 'CSV取込', action: () => importInput.value?.click() })
    return items
})

const fmtDateTime = (v: any) => {
    if (!v) return { d: '', t: '' }
    const dt = new Date(v)
    if (isNaN(dt.getTime())) return { d: String(v), t: '' }
    const p = (n: number) => String(n).padStart(2, '0')
    return { d: `${dt.getFullYear()}/${p(dt.getMonth() + 1)}/${p(dt.getDate())}`, t: `${p(dt.getHours())}:${p(dt.getMinutes())}` }
}
const sysDate = (rec: FlowRecordDto, ref: number | string) => fmtDateTime(systemColumnValue(rec, String(ref))).d
const sysTime = (rec: FlowRecordDto, ref: number | string) => fmtDateTime(systemColumnValue(rec, String(ref))).t

const PER_PAGE = 50
const mode = ref<'server' | 'client'>('server')
const total = ref(0)
const page = ref(1)

// Client mode only (apps with record-level perms return the full visible set): filter+search+sort locally.
const filteredClient = computed(() => {
    let list = applyFilters(records.value, activeView.value?.filters)
    const kw = search.value.trim().toLowerCase()
    if (kw) {
        list = list.filter((r) =>
            String(r.record_number ?? '').includes(kw) ||
            Object.values(r.values).some((v) => String(v ?? '').toLowerCase().includes(kw)))
    }
    const sort = sortRef.value !== null
        ? [{ field: sortRef.value, direction: sortDir.value }]
        : (activeView.value?.sort ?? [])
    return applySort(list, sort)
})
const totalCount = computed(() => (mode.value === 'server' ? total.value : filteredClient.value.length))
const pageCount = computed(() => Math.max(1, Math.ceil(totalCount.value / PER_PAGE)))
const displayRecords = computed(() => {
    if (mode.value === 'server') return records.value
    const start = (page.value - 1) * PER_PAGE
    return filteredClient.value.slice(start, start + PER_PAGE)
})

const load = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams({ page: String(page.value), per_page: String(PER_PAGE) })
        if (activeViewId.value) params.set('view_id', String(activeViewId.value))
        if (search.value.trim()) params.set('search', search.value.trim())
        if (sortRef.value !== null) { params.set('sort_field', String(sortRef.value)); params.set('sort_dir', sortDir.value) }
        const data = await api.get(`/flow_app_records/${flowId.value}?${params.toString()}`) as (FlowRecordsResponse & { mode?: string; total?: number }) | null
        if (data) {
            definition.value = data.definition
            permissions.value = data.permissions
            views.value = data.views ?? []
            mode.value = data.mode === 'client' ? 'client' : 'server'
            records.value = data.records ?? []
            total.value = data.total ?? records.value.length
            if (!activeViewId.value) {
                const def = views.value.find((v) => v.is_default) ?? views.value[0]
                activeViewId.value = def?.id ?? null
            }
        }
    } finally {
        loading.value = false
    }
}

const scrollTop = () => { const el = document.getElementById('rvScroll'); if (el) el.scrollTop = 0 }
// Server mode re-queries on any change; client mode recomputes locally (all rows already loaded).
const refetch = () => { if (mode.value === 'server') load() }

const onSearch = (kw: string) => { search.value = kw; page.value = 1; refetch() }
const onViewChange = () => { sortRef.value = null; page.value = 1; refetch() }
const setPage = (n: number) => {
    const p = Math.min(pageCount.value, Math.max(1, n))
    if (p === page.value) return
    page.value = p
    refetch()
    scrollTop()
}
const onNavi = (dir: number) => setPage(page.value + dir)

const toggleSort = (ref: number | string) => {
    if (sortRef.value !== null && String(sortRef.value) === String(ref)) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortRef.value = ref
        sortDir.value = 'asc'
    }
    page.value = 1
    refetch()
}

const exportCsv = () => { if (definition.value) window.location.href = `/flow_app_export/${definition.value.id}` }
const importFile = ref<File | null>(null)
const importCsv = (e: Event) => {
    const input = e.target as HTMLInputElement
    const file = input.files?.[0]
    if (file) importFile.value = file
    input.value = ''
}
const closeImport = () => { importFile.value = null }
const onImported = (n: number) => {
    importFile.value = null
    dialog.toast(`${n}件を取り込みました。`)
    load()
}

const openNew = () => router.push({ name: 'flow-record-new', params: { flowId: flowId.value } })
const openRecord = (rec: FlowRecordDto) => router.push({ name: 'flow-record-detail', params: { flowId: flowId.value, recordId: rec.record_number } })
// quick-edit shortcut: open the record already in edit mode
const editRecord = (rec: FlowRecordDto) => router.push({ name: 'flow-record-detail', params: { flowId: flowId.value, recordId: rec.record_number }, query: { edit: '1' } })

/* ---- row shortcuts (edit / delete) + bulk delete (一括処理) ---- */
const deleteRecord = async (rec: FlowRecordDto) => {
    if (!window.confirm(`レコード #${rec.record_number} を削除します。よろしいですか？`)) return
    const res = await api.post('/flow_app_record_delete', { id: rec.id }, { toast: '削除しました。' })
    if (res) {
        records.value = records.value.filter((r) => r.id !== rec.id)
        total.value = Math.max(0, total.value - 1)
        const s = new Set(selected.value); s.delete(rec.id); selected.value = s
    }
}

const selected = ref<Set<number>>(new Set())
const canBulk = computed(() => !!permissions.value?.bulk)
const toggleSelect = (id: number) => { const s = new Set(selected.value); s.has(id) ? s.delete(id) : s.add(id); selected.value = s }
const deletableRows = computed(() => displayRecords.value.filter((r) => r.can_delete))
const allSelected = computed(() => deletableRows.value.length > 0 && deletableRows.value.every((r) => selected.value.has(r.id)))
const toggleAll = () => {
    const s = new Set(selected.value)
    if (allSelected.value) deletableRows.value.forEach((r) => s.delete(r.id))
    else deletableRows.value.forEach((r) => s.add(r.id))
    selected.value = s
}
const bulkDelete = async () => {
    const ids = [...selected.value]
    if (!ids.length) return
    if (!window.confirm(`選択した ${ids.length} 件を削除します。この操作は元に戻せません。`)) return
    for (const id of ids) { await api.post('/flow_app_record_delete', { id }) }
    records.value = records.value.filter((r) => !selected.value.has(r.id))
    total.value = Math.max(0, total.value - ids.length)
    selected.value = new Set()
    dialog.toast(`${ids.length} 件を削除しました。`)
}
const back = () => {
    const pid = definition.value?.project_record_id
    if (pid) router.push({ name: 'custom-apps', params: { projectId: pid } })
    else router.push({ name: 'flow-control' })
}

onMounted(async () => {
    const opts = await api.get('/flow_options')
    if (opts) { users.value = opts.users ?? []; projects.value = opts.projects ?? [] }
    await load()
})
</script>

<style scoped>
.rv-screen {background: var(--background-color); color: var(--primary-color); width: 100%; height: 100%; position: relative; }
.rv-card { background: var(--background-color); }

.rv-head { background: var(--background-color); }
.rv-r1 { display: flex; align-items: center; background: var(--bg3); gap: 10px; min-height: 56px; padding: 0 16px; border-bottom: 1px solid var(--calendarBorder); }
.rv-back { flex: none; cursor: pointer; fill: var(--primary-color); padding: 4px; }
.rv-appicon { flex: none; }
.rv-searchwrap { flex: 0 1 480px; min-width: 0; display: flex; align-items: center; }
.rv-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; flex: none; }
.rv-actbtn { display: flex; align-items: center; gap: 6px; height: 20px; padding: 0 12px; border: 1px solid var(--formBorder); border-radius: 8px; background: var(--background-color); cursor: pointer; transition: background .12s, border-color .12s; }
.rv-actbtn:hover { background: var(--bg3); border-color: var(--primary-color); }
.rv-actlabel { font-size: 13px; color: var(--primary-color); white-space: nowrap; }
.rv-csv :deep(.boardMenuContainer) { display: flex; align-items: center; height: 30px; padding: 0 12px; border: 1px solid var(--formBorder); border-radius: 8px; background: var(--background-color); cursor: pointer; transition: background .12s, border-color .12s; }
.rv-csv :deep(.boardMenuContainer:hover) { background: var(--bg3); border-color: var(--primary-color); }
.rv-csv :deep(.boardMenuContainer.active) { background: var(--bg3); border-color: var(--primary-color); }
.rv-csv-inner { display: flex; align-items: center; gap: 6px; }
.rv-r2 { display: flex; align-items: center; gap: 12px; min-height: 48px; padding: 10px 16px; }
.rv-title { flex: 1 1 auto; font-size: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0;line-height: 1.5; }
.rv-viewinfo { display: flex; align-items: center; gap: 8px; flex: none; margin-left: auto; }
.rv-viewname { font-size: 13px; color: var(--primary-color); }
.rv-ctrl { height: 30px; padding: 0 10px; border: 1px solid var(--formBorder); border-radius: 6px; background: var(--background-color); color: var(--primary-color); font-size: 13px; cursor: pointer; }
.rv-count { font-size: 12px; color: gray; white-space: nowrap; }

.rv-scroll { max-height: calc(100vh - 182px); overflow: auto; border-top: 1px solid var(--calendarBorder); }
.rv-pager { position: sticky; padding: 10px 0; bottom: 0; border-top: 1px solid var(--calendarBorder); background: var(--background-color) }
.rv-table { width: 100%; border-collapse: collapse; }
.rv-th { text-align: left; font-size: 12px; font-weight: normal; color: gray; letter-spacing: .02em; padding: 12px 14px; white-space: nowrap; cursor: pointer; user-select: none; position: sticky; top: 0; background: var(--bg3); border-bottom: 1px solid var(--calendarBorder); z-index: 1; }
.rv-th:hover { color: var(--primary-color); }
.rv-th.num { text-align: right; }
.rv-thlabel { display: inline-flex; align-items: center; gap: 3px; }
.rv-th.num .rv-thlabel { flex-direction: row-reverse; }
.rv-arrow { color: var(--primary-color); }
.rv-th-action { width: 72px; cursor: default; }
.rv-th-check, .rv-td-check { width: 34px; text-align: center; cursor: default; }
.rv-td-check input, .rv-th-check input { cursor: pointer; }
.rv-row { cursor: pointer; }
.rv-row:hover { background: var(--selected-background); }
.rv-td { font-size: 13.5px; padding: 13px 14px; border-bottom: 1px solid var(--calendarBorder); vertical-align: middle; white-space: nowrap; max-width: 280px; overflow: hidden; text-overflow: ellipsis; }
.rv-td.num { text-align: right; font-variant-numeric: tabular-nums; }
.rv-td-action { text-align: right; width: 72px; }
.rv-detail { font-size: 12px; color: gray; }
.rv-row:hover .rv-detail { color: var(--primary-color); }
.rv-actions { display: inline-flex; align-items: center; gap: 4px; justify-content: flex-end; }
.rv-actbtn { border: 1px solid var(--calendarBorder); background: var(--background-color); color: gray; border-radius: 6px; padding: 4px 8px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; line-height: 0; }
.rv-actbtn:hover { color: var(--primary-color); border-color: var(--primary-color); background: var(--bg3); }
.rv-actbtn-del:hover { color: tomato; border-color: tomato; }
.rv-bulkdel { border: 1px solid tomato; color: tomato; background: var(--background-color); border-radius: 6px; padding: 4px 10px; font-size: 12px; cursor: pointer; white-space: nowrap; }
.rv-bulkdel:hover { background: tomato; color: #fff; }
.rv-idcell { font-size: 13px; color: gray; }
.rv-statuscell { display: inline-block; font-size: 12px; color: var(--primary-color); background: var(--bg3); padding: 3px 10px; border-radius: 12px; }
.rv-datecell { font-size: 13px; color: gray; }
.rv-time { opacity: .6; }
.rv-empty { text-align: center; color: gray; font-size: 13px; padding: 40px; }
</style>
