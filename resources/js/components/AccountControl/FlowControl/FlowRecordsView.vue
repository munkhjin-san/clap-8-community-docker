<template>
    <div class="admin-window rv-screen">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div class="rv-card">
            <!-- app title bar stays pinned at the top -->
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
                />
                <span class="rv-title" :title="definition?.name">{{ definition?.name }}</span>
                <div class="rv-actions">
                    <button v-if="canManage" class="rv-actbtn" title="アプリ設定" @click="editApp">
                        <Gear :size="14" /><span class="rv-actlabel">アプリ設定</span>
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

            <!-- one scroll area: search + description scroll away, the table header stays sticky at top -->
            <div v-if="definition" id="rvScroll" class="rv-scroll">
                <div class="rv-r2">
                    <div class="rv-searchwrap">
                        <PostSearchBar className="newChatMemberSearch" :customPlaceHolder="searchPlaceholder" @searchStart="onSearch" />
                    </div>
                    <button
                        class="rv-filterbtn"
                        :title="hasAdhocFilter ? `フィルター（${adhocFilter.conditions.length}件の条件）` : 'フィルター'"
                        @click="filterModalOpen = true"
                    >
                        <Filter size="14" :filtered="hasAdhocFilter" fill="var(--primary-color)"/>
                    </button>
                    <div class="rv-viewinfo">
                        <select v-if="views.length > 1" v-model="activeViewId" class="rv-ctrl" @change="onViewChange">
                            <option v-for="v in views" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                        <!-- <span v-else-if="activeView" class="rv-viewname">{{ activeView.name }}</span> -->
                        <button v-if="canBulk && selected.size" class="rv-bulkdel" @click="bulkDelete">選択削除 ({{ selected.size }})</button>
                        <!-- <span class="rv-count">{{ totalCount }}件</span> -->
                    </div>
                </div>

                <div v-if="definition.description" class="rv-desc" v-html="definition.description"></div>

                <table class="rv-table">
                    <thead>
                        <tr>
                            <th v-if="canBulk" class="rv-th rv-th-check" @click.stop>
                                <input type="checkbox" class="rv-check" :checked="allSelected" @change="toggleAll">
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
                                <input type="checkbox" class="rv-check" :checked="selected.has(rec.id)" :disabled="!rec.can_delete" @change="toggleSelect(rec.id)">
                            </td>
                            <td v-for="c in columns" :key="c.key" class="rv-td" :class="{ num: isNumericCol(c) }">
                                <template v-if="c.system">
                                    <span v-if="c.ref === '$record_number'" class="rv-idcell">{{ rec.record_number }}</span>
                                    <span v-else-if="c.ref === '$status'"><span v-if="rec.current_status" class="rv-statuscell" :style="statusStyle(rec)"><span v-if="rec.pending_action" class="rv-pdot" title="あなたの対応待ちです"></span>{{ rec.current_status }}</span></span>
                                    <span v-else class="rv-datecell">{{ sysDate(rec, c.ref) }} <span class="rv-time">{{ sysTime(rec, c.ref) }}</span></span>
                                </template>
                                <FlowFieldInput v-else :field="c.field!" :model-value="rec.values[c.field!.id!]" :users="users" :projects="projects" readonly />
                            </td>
                            <td class="rv-td rv-td-action" @click.stop>
                                <div class="rv-actions">
                                    <button v-if="rec.can_edit" class="rv-actbtn" title="編集" @click="editRecord(rec)">
                                        <Edit size="13" />
                                    </button>
                                    <button v-if="permissions?.add" class="rv-actbtn" title="複製して新規作成" @click="duplicateRecord(rec)">
                                        <Copy size="13" />
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

        <FlowRecordFilterModal
            v-if="filterModalOpen && definition"
            :fields="definition.fields"
            :users="users"
            :has-status="!!definition.use_status_flow"
            :status-names="statusNames"
            :model-value="adhocFilter"
            @apply="onApplyFilter"
            @close="filterModalOpen = false"
        />

        <FlowCsvExportModal
            v-if="exportModalOpen && definition"
            :fields="definition.fields"
            :build-url="buildExportUrl"
            @close="exportModalOpen = false"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import { useFlowOptionsStore } from '@/store/flowOptions'
import FlowFieldInput from './FlowFieldInput.vue'
import FlowCsvImportModal from './FlowCsvImportModal.vue'
import FlowRecordFilterModal from './FlowRecordFilterModal.vue'
import FlowCsvExportModal from './FlowCsvExportModal.vue'
import Back from '@/components/Icons/Back.vue'
import Edit from '@/components/Icons/Edit.vue'
import Copy from '@/components/Icons/Copy.vue'
import Gear from '@/components/Icons/Gear.vue'
import Filter from '@/components/Icons/Filter.vue'
import PostSearchBar from '@/components/Post/PostSearchBar.vue'
import PostSearchPager from '@/components/Post/PostSearchPager.vue'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import FloatButton from '@/components/Global/FloatButton.vue'
import AddIcon from '@/components/Form/AddIcon.vue'
import FlowAppIcon from './FlowAppIcon.vue'
import { readableTextColor } from '@/utils/flowColor'
import { pageTitleOverride } from '@/composables/pageTitle'
import { resolveColumns, applyFilters, applyAdhocFilter, applySort, systemColumnValue, type ResolvedColumn } from '@/utils/flowView'
import type { FlowDefinitionApi, FlowRecordDto, FlowAppPermissionsDto, FlowViewApi, FlowRecordsResponse, FlowAdhocFilter } from '@/types/flow'
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
const flowOptionsStore = useFlowOptionsStore()
const { users, projects } = storeToRefs(flowOptionsStore)
const views = ref<FlowViewApi[]>([])
// Restore the view from the URL (?view=) BEFORE the first load so the initial fetch already
// carries it — restoring after the response would show the default view's records under a
// correctly-selected view (server mode filters by view_id server-side).
const activeViewId = ref<number | null>(route.query.view ? Number(route.query.view) : null)
const search = ref('')
// header sort + ad-hoc filter live in the URL too (?sf/?sd/?f) so opening a record and
// coming back — or reloading — keeps them applied (same rule as ?view=: seed before first load)
const seedSf = route.query.sf
const sortRef = ref<number | string | null>(
    typeof seedSf === 'string' && seedSf !== '' ? (isNaN(Number(seedSf)) ? seedSf : Number(seedSf)) : null,
)
const sortDir = ref<'asc' | 'desc'>(route.query.sd === 'desc' ? 'desc' : 'asc')
const importInput = ref<HTMLInputElement | null>(null)

// ?f= carries the ad-hoc filter as base64url of a compact array form
// (["and", [field, op, ...values], …]) — raw JSON in the URL reads as a wall of %22
const encodeAdhoc = (f: FlowAdhocFilter): string => {
    const compact = [f.logic, ...f.conditions.map((c) => [c.field, c.operator, ...(c.values ?? [])])]
    const bytes = new TextEncoder().encode(JSON.stringify(compact))
    return btoa(String.fromCharCode(...bytes)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
}
const decodeAdhoc = (s: string): FlowAdhocFilter | null => {
    try {
        // legacy links carried raw JSON — keep reading them
        const json = /^[{[]/.test(s) ? s : new TextDecoder().decode(
            Uint8Array.from(atob(s.replace(/-/g, '+').replace(/_/g, '/')), (ch) => ch.charCodeAt(0)),
        )
        const parsed = JSON.parse(json)
        if (Array.isArray(parsed)) {
            const [logic, ...conds] = parsed
            return {
                logic: logic === 'or' ? 'or' : 'and',
                conditions: conds.filter(Array.isArray).map((c: any[]) => ({ field: c[0], operator: c[1], values: c.slice(2) })),
            }
        }
        if (parsed && Array.isArray(parsed.conditions)) {
            return { logic: parsed.logic === 'or' ? 'or' : 'and', conditions: parsed.conditions }
        }
    } catch { /* malformed ?f= — start unfiltered */ }
    return null
}

// ad-hoc filter (from the search bar's ⚲ icon) — session-only, not saved to the view
const adhocFilter = reactive<FlowAdhocFilter>({ logic: 'and', conditions: [] })
if (typeof route.query.f === 'string') {
    const seeded = decodeAdhoc(route.query.f)
    if (seeded) {
        adhocFilter.logic = seeded.logic
        adhocFilter.conditions = seeded.conditions
    }
}
const hasAdhocFilter = computed(() => adhocFilter.conditions.length > 0)
const filterModalOpen = ref(false)
const statusNames = computed(() => (definition.value?.statuses ?? []).map((s) => s.name).filter(Boolean))

// the list's full URL state; also attached to record links so 戻る can restore all of it
const listQuery = (): Record<string, string> => {
    const q: Record<string, string> = {}
    if (activeViewId.value) q.view = String(activeViewId.value)
    if (sortRef.value !== null) { q.sf = String(sortRef.value); q.sd = sortDir.value }
    if (hasAdhocFilter.value) q.f = encodeAdhoc(adhocFilter)
    return q
}
const syncQuery = () => router.replace({ query: listQuery() })

const onApplyFilter = (f: FlowAdhocFilter) => {
    adhocFilter.logic = f.logic
    adhocFilter.conditions = f.conditions
    filterModalOpen.value = false
    page.value = 1
    syncQuery()
    refetch()
}

const flowId = computed(() => route.params.flowId)
const activeView = computed<FlowViewApi | null>(() => views.value.find((v) => v.id === activeViewId.value) ?? views.value[0] ?? null)
const columns = computed(() => resolveColumns(activeView.value, definition.value?.fields ?? [], !!definition.value?.use_status_flow))

// per-status pill color (free-picked in the builder); null → neutral --bg3 via CSS
const statusColorById = computed<Record<number, string | null>>(() => {
    const map: Record<number, string | null> = {}
    for (const s of definition.value?.statuses ?? []) if (s.id != null) map[s.id] = s.color ?? null
    return map
})
const statusStyle = (rec: FlowRecordDto) => {
    const c = rec.current_status_id != null ? statusColorById.value[rec.current_status_id] : null
    return c ? { background: c, color: readableTextColor(c) } : {}
}

const isNumericCol = (c: ResolvedColumn) => !c.system && (c.field?.input_type === 'number' || c.field?.input_type === 'formula')

const searchPlaceholder = computed(() => `${definition.value?.name ?? 'レコード'}を検索`)

const canExport = computed(() => !!permissions.value?.export)
const canImport = computed(() => !!permissions.value?.import)
const canManage = computed(() => !!permissions.value?.manage)
const editApp = () => router.push({ name: 'flow-builder', params: { flowId: flowId.value } })
const csvItems = computed<MenuList[]>(() => {
    const items: MenuList[] = []
    if (canExport.value) items.push({ title: 'CSV出力', action: () => { exportModalOpen.value = true } })
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
    let list = applyFilters(records.value, activeView.value?.filters, activeView.value?.filter_logic === 'or' ? 'or' : 'and')
    list = applyAdhocFilter(list, adhocFilter)
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
        if (hasAdhocFilter.value) params.set('filters', JSON.stringify(adhocFilter))
        const data = await api.get(`/flow_app_records/${flowId.value}?${params.toString()}`) as (FlowRecordsResponse & { mode?: string; total?: number }) | null
        if (data) {
            definition.value = data.definition
            permissions.value = data.permissions
            views.value = data.views ?? []
            mode.value = data.mode === 'client' ? 'client' : 'server'
            records.value = data.records ?? []
            total.value = data.total ?? records.value.length
            // no view yet, or a stale ?view= id that isn't one of this app's views (the server
            // falls back to the default view in that case) → sync the selector to the default
            if (!activeViewId.value || !views.value.some((v) => v.id === activeViewId.value)) {
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
const onViewChange = () => {
    // switching views drops the header sort (view brings its own) but keeps the ad-hoc filter
    sortRef.value = null
    page.value = 1
    syncQuery()
    refetch()
}
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
    syncQuery()
    refetch()
}

const exportModalOpen = ref(false)
const buildExportUrl = (opts: { encoding: 'utf8' | 'sjis'; scope: 'all' | 'table'; tableFieldId: number | null }) => {
    const params = new URLSearchParams({ encoding: opts.encoding, scope: opts.scope })
    if (activeViewId.value) params.set('view_id', String(activeViewId.value))
    if (sortRef.value !== null) { params.set('sort_field', String(sortRef.value)); params.set('sort_dir', sortDir.value) }
    if (hasAdhocFilter.value) params.set('filters', JSON.stringify(adhocFilter))
    if (opts.scope === 'table' && opts.tableFieldId) params.set('table_field_id', String(opts.tableFieldId))
    return `/flow_app_export/${definition.value!.id}?${params.toString()}`
}
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
// the record route carries the list's URL state (?view/?sf/?sd/?f) so its back button — and any
// amount of up/down record shifting — can return to the list with view, sort and filter intact
const openRecord = (rec: FlowRecordDto) => router.push({ name: 'flow-record-detail', params: { flowId: flowId.value, recordId: rec.record_number }, query: listQuery() })
// quick-edit shortcut: open the record already in edit mode
const editRecord = (rec: FlowRecordDto) => router.push({ name: 'flow-record-detail', params: { flowId: flowId.value, recordId: rec.record_number }, query: { edit: '1', ...listQuery() } })
// 複製: open a new record pre-filled with this record's values
const duplicateRecord = (rec: FlowRecordDto) => router.push({ name: 'flow-record-new', params: { flowId: flowId.value }, query: { from: rec.id } })

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
    await flowOptionsStore.load()
    await load()
})
</script>

<style scoped>
.rv-screen {background: var(--background-color); color: var(--primary-color); width: 100%; height: 100%; position: relative; display: flex; flex-direction: column; }
.rv-card { background: var(--background-color); flex: 1; min-height: 0; display: flex; flex-direction: column; }

.rv-r1 { flex: none; display: flex; align-items: center; background: var(--bg3); gap: 10px; min-height: 56px; padding: 0 16px; border-bottom: 1px solid var(--calendarBorder); }
.rv-back { flex: none; cursor: pointer; fill: var(--primary-color); padding: 4px; }
.rv-appicon { flex: none; }
.rv-searchwrap { flex: 0 1 480px; min-width: 0; display: flex; align-items: center; }

.rv-filterbtn {color: var(--primary-color); box-sizing: border-box !important; flex: none; display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 1px solid var(--formBorder); border-radius: 6px; background: var(--background-color); cursor: pointer; }
.rv-filterbtn:hover { background: var(--bg3); border-color: var(--primary-color); }
.rv-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; flex: none; }
/* top-bar buttons (アプリ設定 / CSV): scoped to .rv-r1 so the row-action .rv-actbtn rule below can't
   override their geometry. border-box + explicit height pins the <button> and the CSV <div> to the
   SAME total height (a bare <button> defaults to border-box while the <div> is content-box). */
.rv-r1 .rv-actbtn { box-sizing: border-box !important; display: flex; align-items: center; gap: 6px; height: 30px; padding: 0 12px; border: 1px solid var(--formBorder); border-radius: 8px; background: var(--background-color); cursor: pointer; transition: background .12s, border-color .12s; fill: var(--primary-color); }
.rv-r1 .rv-actbtn:hover { background: var(--bg3); border-color: var(--primary-color); }
.rv-actlabel { font-size: 13px; color: var(--primary-color); white-space: nowrap; }
.rv-csv :deep(.boardMenuContainer) { display: flex; align-items: center; height: 30px; padding: 0 12px; border: 1px solid var(--formBorder); border-radius: 8px; background: var(--background-color); cursor: pointer; transition: background .12s, border-color .12s; }
.rv-csv :deep(.boardMenuContainer:hover) { background: var(--bg3); border-color: var(--primary-color); }
.rv-csv :deep(.boardMenuContainer.active) { background: var(--bg3); border-color: var(--primary-color); }
.rv-csv-inner { display: flex; align-items: center; gap: 6px; }
.rv-r2 { display: flex; align-items: center; gap: 12px; min-height: 48px; padding: 10px 16px; position: sticky; left: 0; background: var(--background-color); }
.rv-title { flex: 1 1 auto; font-size: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0;line-height: 1.5; }
.rv-viewinfo { display: flex; align-items: center; gap: 8px; flex: none; }
.rv-viewname { font-size: 13px; color: var(--primary-color); }
.rv-count { font-size: 12px; color: gray; white-space: nowrap; }
.rv-ctrl {
    height: 30px;
    padding: 0 5px;
    border: 1px solid var(--formBorder);
    border-radius: 6px;
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 13px;
    cursor: pointer;
    max-width: 160px;
    padding-right: 20px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.rv-desc { padding: 12px 16px; font-size: 13px; line-height: 1.6; color: var(--primary-color); border-top: 1px solid var(--calendarBorder); position: sticky; left: 0; background: var(--background-color); }
.rv-desc :deep(img) { max-width: 100%; height: auto; }
.rv-desc :deep(a) { color: var(--primary-button, var(--primary-color)); text-decoration: underline; }
.rv-desc :deep(p) { margin: 0 0 6px; }
.rv-desc :deep(p:last-child) { margin-bottom: 0; }
.rv-scroll { flex: 1; min-height: 0; overflow: auto; }
.rv-pager { flex: none; padding: 10px 0; border-top: 1px solid var(--calendarBorder); background: var(--background-color) }
.rv-table { width: 100%; border-collapse: collapse; }
.rv-th { text-align: left; font-size: 12px; font-weight: normal; color: gray; letter-spacing: .02em; padding: 12px 14px; white-space: nowrap; cursor: pointer; user-select: none; position: sticky; top: 0; background: var(--bg3); border-bottom: 1px solid var(--calendarBorder); z-index: 1; }
.rv-th:hover { color: var(--primary-color); }
.rv-th.num { text-align: right; }
.rv-thlabel { display: inline-flex; align-items: center; gap: 3px; }
.rv-th.num .rv-thlabel { flex-direction: row-reverse; }
.rv-arrow { color: var(--primary-color); }
.rv-th-action { width: 72px; cursor: default; }
.rv-th-check, .rv-td-check { width: 34px; text-align: center; cursor: default; }
/* custom checkbox (native styling is cheap) — matches the pattern used for checkbox-type fields */
.rv-check {
    appearance: none; -webkit-appearance: none;
    box-sizing: border-box !important;
    width: 16px; height: 16px; margin: 0; flex-shrink: 0;
    border: 1.5px solid var(--formBorder); border-radius: 4px; background: var(--background-color);
    position: relative; cursor: pointer; vertical-align: middle;
    transition: background .12s, border-color .12s, box-shadow .12s;
}
.rv-check:hover:not(:checked):not(:disabled) { border-color: var(--primary-color); }
.rv-check:checked { background: var(--primary-button, var(--primary-color)); border-color: var(--primary-button, var(--primary-color)); }
.rv-check:checked::after {
    content: ""; position: absolute; left: 4px; top: 1px;
    width: 4px; height: 8px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg);
}
.rv-check:disabled { opacity: 0.45; cursor: not-allowed; }
.rv-check:focus-visible { outline: none; box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-color) 25%, transparent); }
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
/* 要対応: small red dot inside the status pill — the viewer is named by an action on this
   status. Matches the app-wide dot convention (6px, plain, no ring). */
.rv-pdot { display: inline-block; width: 6px; min-width: 6px; height: 6px; border-radius: 9999px; background: tomato; margin-right: 5px; vertical-align: 1px; }
.rv-datecell { font-size: 13px; color: gray; }
.rv-time { opacity: .6; }
.rv-empty { text-align: center; color: gray; font-size: 13px; padding: 40px; }

@media (min-width: 959px) {
    .rv-searchwrap { max-width: 280px; }
}
@media screen and (max-width: 959px) {
    
    .rv-ctrl {
        max-width: 90px;
    }
}
</style>
