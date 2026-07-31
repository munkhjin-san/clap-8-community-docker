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

                <div v-for="slot in topSlots" :key="`t${slot.id}`" class="rv-slot">
                    <span v-for="(it, i) in slot.items" :key="i" class="rv-slot-item">
                        <span class="rv-slot-label">{{ it.label }}</span>
                        <span class="rv-slot-value">{{ formatSlotItem(it) }}</span>
                    </span>
                </div>

                <table
                    class="rv-table"
                    :class="{ 'rv-fixed': fixedLayout, 'rv-dragging': resizing !== null, 'rv-gridlines': editingId !== null }"
                    :style="{ '--rv-grip-h': gridHeight ? gridHeight + 'px' : undefined }"
                    @mouseover="onCellOver"
                    @mouseout="onCellOut"
                >
                    <colgroup>
                        <!-- the check and action columns are measured like any other: hardcoding them meant
                             switching to fixed layout resized them, which shifted every column along -->
                        <col v-if="canBulk" :style="{ width: (widthOf('$check') ?? 34) + 'px' }">
                        <col v-for="c in columns" :key="c.key" :style="colWidth(c) ? { width: colWidth(c) + 'px' } : undefined">
                        <col :style="{ width: (widthOf('$action') ?? 72) + 'px' }">
                    </colgroup>
                    <thead>
                        <tr>
                            <th v-if="canBulk" class="rv-th rv-th-check" @click.stop>
                                <input type="checkbox" class="rv-check" :checked="allSelected" @change="toggleAll">
                            </th>
                            <th v-for="c in columns" :key="c.key" class="rv-th" :class="{ num: isNumericCol(c) }" @click="toggleSort(c.ref)">
                                <span class="rv-thlabel">{{ c.label }}<span v-if="String(sortRef) === String(c.ref)" class="rv-arrow">{{ sortDir === 'asc' ? '↑' : '↓' }}</span></span>
                                <span class="rv-colgrip" :class="{ dragging: resizing === c.key }" title="ドラッグで列幅を変更" @mousedown="startResize(c, $event)" @click.stop></span>
                            </th>
                            <th class="rv-th rv-th-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="rec in displayRecords" :key="rec.id">
                        <tr class="rv-row" :class="{ editing: editingId === rec.id }" @click="onRowClick(rec)" @dblclick="startInlineEdit(rec)">
                            <td v-if="canBulk" class="rv-td rv-td-check" @click.stop>
                                <input type="checkbox" class="rv-check" :checked="selected.has(rec.id)" :disabled="!rec.can_delete" @change="toggleSelect(rec.id)">
                            </td>
                            <td v-for="c in columns" :key="c.key" class="rv-td" :class="{ num: isNumericCol(c), edit: isCellEditable(rec, c) }" @click="isCellEditable(rec, c) && $event.stopPropagation()">
                                <template v-if="c.system">
                                    <span v-if="c.ref === '$record_number'" class="rv-idcell">{{ rec.record_number }}</span>
                                    <span v-else-if="c.ref === '$status'"><span v-if="rec.current_status" class="rv-statuscell" :style="statusStyle(rec)"><span v-if="rec.pending_action" class="rv-pdot" title="あなたの対応待ちです"></span>{{ rec.current_status }}</span></span>
                                    <span v-else class="rv-datecell">{{ sysDate(rec, c.ref) }} <span class="rv-time">{{ sysTime(rec, c.ref) }}</span></span>
                                </template>
                                <template v-else-if="isCellEditable(rec, c)">
                                    <FlowFieldInput
                                        :field="c.field!"
                                        :users="users"
                                        :projects="projects"
                                        :record-id="rec.id"
                                        :model-value="editValues[c.field!.id!]"
                                        @update:model-value="onCellInput(c.field!, $event)"
                                        @lookup="onCellLookup(rec, $event)"
                                    />
                                    <div v-if="editErrors[c.field!.id!]" class="rv-cellerr">{{ editErrors[c.field!.id!] }}</div>
                                </template>
                                <FlowFieldInput
                                    v-else
                                    :field="c.field!"
                                    :model-value="editingId === rec.id ? editValues[c.field!.id!] : rec.values[c.field!.id!]"
                                    :users="users" :projects="projects" readonly
                                    :title="editingId === rec.id ? uneditableReason(rec, c) : undefined"
                                />
                            </td>
                            <td class="rv-td rv-td-action" @click.stop>
                                <div v-if="editingId === rec.id" class="rv-actions"></div>
                                <div v-else class="rv-actions">
                                    <button v-if="rec.can_edit" class="rv-actbtn" title="この行で編集" @click="startInlineEdit(rec)">
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
                        <!-- 保存/キャンセル live under the row, not in its last cell: pinned to the right edge
                             they covered whichever column happened to be there. Sticky left like the
                             集計スロット, so they stay put however far the table is scrolled. -->
                        <tr v-if="editingId === rec.id" class="rv-editbar">
                            <td :colspan="columns.length + 1 + (canBulk ? 1 : 0)" class="rv-editbartd">
                                <div class="rv-editbarrow">
                                    <div class="rv-editbarwrap">
                                        <button class="rv-editbtn" :disabled="savingInline" @click="cancelInlineEdit">キャンセル</button>
                                        <LoaderButton class="rv-editbtn primary" :loading="savingInline" content="保存" @triggered="saveInline(rec)" />
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </template>
                        <tr v-if="!displayRecords.length && !loading">
                            <td :colspan="columns.length + 1 + (canBulk ? 1 : 0)" class="rv-empty">レコードがありません。</td>
                        </tr>
                    </tbody>
                </table>

                <div v-for="slot in bottomSlots" :key="`b${slot.id}`" class="rv-slot">
                    <span v-for="(it, i) in slot.items" :key="i" class="rv-slot-item">
                        <span class="rv-slot-label">{{ it.label }}</span>
                        <span class="rv-slot-value">{{ formatSlotItem(it) }}</span>
                    </span>
                </div>
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

        <!-- Truncated-cell peek. Lives outside .rv-scroll and is position:fixed, or the scroll
             container would clip it. -->
        <div
            v-if="peek.open"
            class="rv-peek"
            :class="{ 'rv-peek-lefthand': peek.side === 'left' }"
            :style="peek.style"
            @mouseenter="cancelPeekHide"
            @mouseleave="hidePeek"
        >
            <button class="rv-peek-copy" :title="peek.copied ? 'コピーしました' : 'コピー'" @click="copyPeek">
                <Copy size="12" />
            </button>
            <div class="rv-peek-body">{{ peek.text }}</div>
        </div>

        <FlowCsvExportModal
            v-if="exportModalOpen && definition"
            :fields="definition.fields"
            :build-url="buildExportUrl"
            @close="exportModalOpen = false"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import { useFlowOptionsStore } from '@/store/flowOptions'
import FlowFieldInput from './FlowFieldInput.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import { fillSlotValues, formatSlotItem, type SlotDto } from '@/utils/flowSlots'
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
import { applyLookupCopy, lockedByServer, validateRecordValues, validationSummary } from '@/utils/flowValidation'
import { emptyFieldValue } from '@/utils/flowDefaults'
import { useUnsavedGuard } from '@/composables/unsavedGuard'
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
const rawSlots = ref<SlotDto[]>([])
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
            rawSlots.value = (data as any).slots ?? []
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
/**
 * Slot values: the server fills them in server mode (it knows the whole filtered set); in client mode
 * it defers, because only here do we know what the local search/ad-hoc filter left on screen.
 */
const slots = computed<SlotDto[]>(() => {
    if (!rawSlots.value.length) return []
    const needsLocal = rawSlots.value.some((s) => s.items.some((it) => !it.computed))
    if (!needsLocal) return rawSlots.value
    const fields = definition.value?.fields ?? []
    return fillSlotValues(rawSlots.value, filteredClient.value, fields)
})
const visibleSlots = computed(() => slots.value.filter((s) => s.items.length))
const topSlots = computed(() => visibleSlots.value.filter((s) => s.position === 'top'))
const bottomSlots = computed(() => visibleSlots.value.filter((s) => s.position === 'bottom'))

const buildExportUrl = (opts: { encoding: 'utf8' | 'sjis'; scope: 'all' | 'no_table' | 'table'; tableFieldId: number | null }) => {
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

// 複製: open a new record pre-filled with this record's values
const duplicateRecord = (rec: FlowRecordDto) => router.push({ name: 'flow-record-new', params: { flowId: flowId.value }, query: { from: rec.id } })

/* ---- column widths ------------------------------------------------------------------------------
 * Entering edit mode used to move the whole table: the edit cells asked for a min-width, and because
 * a table shares column widths across every row, that re-laid out all of them. Measured on a
 * 40-column app: 5270px → 7349px, 25 columns resized, and the cell that had just been double-clicked
 * jumped 298px sideways — so the field the user aimed at was no longer under the cursor.
 *
 * The fix is to make widths explicit rather than to chase the cell afterwards. Two sources:
 *  - `userWidths`, from dragging a column edge, kept per user (see the storage note below)
 *  - `lockWidths`, a snapshot taken when a row opens for editing, so the layout is pinned to exactly
 *    what was on screen at that moment. Dropped again on exit, so the table goes back to sizing
 *    itself to its content when nobody has resized anything.
 * Either one switches the table to `table-layout: fixed`, which is what makes the numbers binding.
 */
const MIN_COL_W = 56
/** The key of the column being dragged, or null. Holds the key rather than a flag so only that one
 *  column's handle lights up — as a boolean it marked the whole table and every grip showed at once. */
const resizing = ref<string | null>(null)
/**
 * While a row is being edited the column edges become the drag handles, so the grip has to run the
 * full height of the table rather than living only in the header — a 9px strip up in the header is a
 * target nobody finds. The height is measured because the grip is absolutely positioned inside a
 * sticky <th>, which gives it no way to express "as tall as the table" in CSS alone.
 */
const gridHeight = ref(0)
const measureGrid = () => { gridHeight.value = Math.round(document.querySelector('.rv-table')?.getBoundingClientRect().height ?? 0) }
const userWidths = ref<Record<string, number>>({})
const lockWidths = ref<Record<string, number>>({})
const fixedLayout = computed(() => !!Object.keys(lockWidths.value).length || !!Object.keys(userWidths.value).length)
const widthOf = (key: string): number | undefined => lockWidths.value[key] ?? userWidths.value[key]
const colWidth = (c: ResolvedColumn) => widthOf(c.key)

/**
 * Stored per user, not on the view. Column width is a display preference and screens differ, the app
 * already keeps this kind of state in localStorage (theme, dismissed warnings, tour), and views can
 * only be written through the builder's save — which needs manage permission and would apply one
 * person's drag to everyone using the app.
 */
const widthKey = () => `flowColW:${flowId.value}:${activeViewId.value ?? 'default'}`
const loadWidths = () => {
    lockWidths.value = {}
    try { userWidths.value = JSON.parse(localStorage.getItem(widthKey()) || '{}') } catch { userWidths.value = {} }
}
const saveWidths = () => {
    try { localStorage.setItem(widthKey(), JSON.stringify(userWidths.value)) } catch { /* private mode / quota — widths just won't persist */ }
}

/** The widths currently on screen, keyed by column — including the check and action columns. */
const measureColumns = (): Record<string, number> => {
    const ths = [...document.querySelectorAll<HTMLElement>('.rv-table thead th')]
    const offset = canBulk.value ? 1 : 0
    const out: Record<string, number> = {}
    const px = (el?: HTMLElement) => (el ? Math.round(el.getBoundingClientRect().width) : 0)
    if (canBulk.value && px(ths[0])) out.$check = px(ths[0])
    columns.value.forEach((c, i) => {
        const w = px(ths[i + offset])
        if (w) out[c.key] = w
    })
    const act = px(ths[columns.value.length + offset])
    if (act) out.$action = act
    return out
}

const startResize = (c: ResolvedColumn, e: MouseEvent) => {
    e.preventDefault()
    e.stopPropagation() // the header itself toggles the sort
    // pin every column on the first drag, so dragging one edge moves only that edge
    if (!Object.keys(userWidths.value).length) userWidths.value = { ...lockWidths.value, ...measureColumns() }
    const startX = e.clientX
    const startW = userWidths.value[c.key] ?? Math.round(measureColumns()[c.key] ?? 120)
    hidePeek()
    resizing.value = c.key

    const onMove = (ev: MouseEvent) => {
        userWidths.value = { ...userWidths.value, [c.key]: Math.max(MIN_COL_W, Math.round(startW + ev.clientX - startX)) }
    }
    const onUp = () => {
        document.removeEventListener('mousemove', onMove)
        document.removeEventListener('mouseup', onUp)
        document.body.classList.remove('rv-resizing')
        resizing.value = null
        saveWidths()
    }
    document.addEventListener('mousemove', onMove)
    document.addEventListener('mouseup', onUp)
    document.body.classList.add('rv-resizing')
}

/* ---- truncated-cell peek -----------------------------------------------------------------------
 * A list cell is one clipped line (nowrap + overflow:hidden + max-width), so long text and long
 * multi-line text both end in an ellipsis with no way to read them short of opening the record.
 * Hovering a *clipped* cell opens the full value next to the cursor, after a delay so it never
 * flickers past while the pointer crosses the table.
 */
const PEEK_DELAY = 450      // long enough that sweeping across cells shows nothing
const PEEK_W = 420
const PEEK_GAP = 14
const PEEK_MIN_H = 120

const peek = reactive<{ open: boolean; text: string; copied: boolean; side: 'right' | 'left'; style: Record<string, string> }>({
    open: false, text: '', copied: false, side: 'right', style: {},
})
let peekTimer: ReturnType<typeof setTimeout> | null = null
let peekHideTimer: ReturnType<typeof setTimeout> | null = null
let peekCell: HTMLElement | null = null

/** Only a cell whose content is actually cut off has anything to reveal. */
const cellClipped = (td: HTMLElement) => td.scrollWidth > td.clientWidth + 1

/**
 * The cell's text. Chips (users, checkbox choices) are separate elements, so textContent would run
 * them together — "田中佐藤" — hence the explicit join.
 */
const cellText = (td: HTMLElement): string => {
    const chips = td.querySelectorAll('.fi-chip, .fi-pill')
    if (chips.length > 1) return [...chips].map((c) => (c.textContent ?? '').trim()).filter(Boolean).join('、')
    return (td.textContent ?? '').trim()
}

/**
 * Bottom-right of the cursor by default, flipped when that would run off screen. Height is bounded
 * by the space actually available (and never taller than the table area), with the overflow scrolling
 * inside — a 200-line note must not become a popup taller than the window.
 */
const peekStyleFor = (x: number, y: number) => {
    const vw = window.innerWidth
    const vh = window.innerHeight
    const scroll = document.getElementById('rvScroll')?.getBoundingClientRect()
    const topLimit = Math.max(8, scroll?.top ?? 8)
    const bottomLimit = Math.min(vh - 8, scroll?.bottom ?? vh - 8)

    const spaceBelow = bottomLimit - y - PEEK_GAP
    const spaceAbove = y - PEEK_GAP - topLimit
    const below = spaceBelow >= PEEK_MIN_H || spaceBelow >= spaceAbove
    const maxH = Math.max(PEEK_MIN_H, Math.floor(below ? spaceBelow : spaceAbove))

    // right of the cursor unless the popup wouldn't fit there
    const fitsRight = x + PEEK_GAP + PEEK_W <= vw - 8
    const left = fitsRight ? x + PEEK_GAP : Math.max(8, x - PEEK_GAP - PEEK_W)

    const style: Record<string, string> = {
        left: `${Math.round(left)}px`,
        width: `${PEEK_W}px`,
        maxHeight: `${maxH}px`,
    }
    if (below) style.top = `${Math.round(y + PEEK_GAP)}px`
    else style.bottom = `${Math.round(vh - (y - PEEK_GAP))}px`

    // which side of the cursor the popup ended up on — the copy button goes on the edge nearest the
    // cursor, so reaching it is the shortest possible move
    return { style, side: (fitsRight ? 'right' : 'left') as 'right' | 'left' }
}

const clearPeekTimers = () => {
    if (peekTimer) { clearTimeout(peekTimer); peekTimer = null }
    if (peekHideTimer) { clearTimeout(peekHideTimer); peekHideTimer = null }
}
const hidePeek = () => {
    clearPeekTimers()
    peek.open = false
    peek.copied = false
    peekCell = null
}
/** The pointer has to cross a gap to reach the copy button, which means leaving the cell — so the
 *  hide is deferred and cancelled when the pointer lands on the popup itself. */
const cancelPeekHide = () => { if (peekHideTimer) { clearTimeout(peekHideTimer); peekHideTimer = null } }

const onCellOver = (e: MouseEvent) => {
    const td = (e.target as HTMLElement | null)?.closest?.('.rv-td') as HTMLElement | null
    if (!td) return
    // Same cell: the pointer is moving among the spans its content is built from, which fires
    // mouseout/mouseover pairs the whole time. Cancel any pending hide rather than returning early —
    // returning left the hide scheduled, so the peek closed while the pointer was still on the cell
    // and nothing re-armed it (no fresh mouseover comes once the pointer is already inside).
    if (td === peekCell) { cancelPeekHide(); return }
    clearPeekTimers()
    peek.open = false
    peekCell = null
    // nothing to preview in an input, a checkbox or the action buttons
    if (td.classList.contains('edit') || td.classList.contains('rv-td-check') || td.classList.contains('rv-td-action')) return
    if (!cellClipped(td)) return

    peekCell = td
    const { clientX, clientY } = e
    peekTimer = setTimeout(() => {
        const text = cellText(td)
        if (!text) return
        const placed = peekStyleFor(clientX, clientY)
        peek.text = text
        peek.copied = false
        peek.style = placed.style
        peek.side = placed.side
        peek.open = true
    }, PEEK_DELAY)
}

const onCellOut = (e: MouseEvent) => {
    const to = e.relatedTarget as HTMLElement | null
    if (to?.closest?.('.rv-peek')) return // moving onto the popup, not away
    if (peekCell && to && peekCell.contains(to)) return // still within the same cell
    clearPeekTimers()
    peekHideTimer = setTimeout(hidePeek, 140)
}

const copyPeek = async () => {
    try {
        await navigator.clipboard.writeText(peek.text)
        peek.copied = true
        setTimeout(() => { peek.copied = false }, 1500)
    } catch {
        dialog.toast('コピーできませんでした。')
    }
}

/* ---- クイック編集: the row's own cells become inputs, kintone-style ---- */

const editingId = ref<number | null>(null)
const editValues = reactive<Record<string, any>>({})
const editErrors = reactive<Record<string, string | null>>({})
const savingInline = ref(false)
/** JSON of the values as seeded, so leaving the row can tell edits from an untouched open. */
const editBaseline = ref('')

/**
 * A テーブル can't be edited in one grid cell (it is a table of its own), and a 計算 field has no
 * stored value to edit. Everything else the server says is writable becomes a real input.
 */
const INLINE_BLOCKED = ['table', 'formula']
const isInlineEditable = (rec: FlowRecordDto, c: ResolvedColumn) =>
    !c.system && !!c.field && !INLINE_BLOCKED.includes(c.field.input_type)
    && !c.field.validation?.disabled
    && !lockedByServer(c.field, rec.editable_field_ids ?? null)

const isCellEditable = (rec: FlowRecordDto, c: ResolvedColumn) => editingId.value === rec.id && isInlineEditable(rec, c)

/** Why a cell stayed read-only while the rest of the row is editable — shown as its tooltip. */
const uneditableReason = (rec: FlowRecordDto, c: ResolvedColumn) => {
    const f = c.field
    if (!f || c.system) return undefined
    if (f.input_type === 'table') return 'テーブルはこの行では編集できません。詳細画面で編集してください。'
    if (f.input_type === 'formula') return '計算項目は自動で求められます。'
    if (f.validation?.disabled) return '入力できません（自動入力のみ）'
    if (lockedByServer(f, rec.editable_field_ids ?? null)) return '現在のステータスまたは権限では編集できません。'
    return undefined
}

/** The fields this row can actually submit — only what the view shows, so a save is a partial update. */
const inlineFields = (rec: FlowRecordDto) =>
    columns.value.filter((c) => isInlineEditable(rec, c)).map((c) => c.field!)

const onCellInput = (f: { id?: number }, v: any) => {
    editValues[f.id!] = v
    editErrors[f.id!] = null
}
const onCellLookup = (rec: FlowRecordDto, payload: { mappings: { from: string; to: string }[]; source: Record<string, any> }) =>
    applyLookupCopy(definition.value?.fields ?? [], editValues, editErrors, payload,
        { editableFieldIds: rec.editable_field_ids ?? null })

/**
 * Single click opens the record, double click edits it in place — so the open has to wait long enough
 * to tell which gesture it was. 250ms is the usual dblclick threshold; it only ever delays navigation.
 */
let clickTimer: ReturnType<typeof setTimeout> | null = null
const clearClickTimer = () => { if (clickTimer) { clearTimeout(clickTimer); clickTimer = null } }
const onRowClick = (rec: FlowRecordDto) => {
    // while a row is open for editing, a stray click must not navigate away from unsaved input
    if (editingId.value !== null) return
    clearClickTimer()
    clickTimer = setTimeout(() => { clickTimer = null; openRecord(rec) }, 250)
}

const isInlineDirty = () => JSON.stringify(editValues) !== editBaseline.value
// leaving the list mid-edit loses the row just as surely as closing the tab does, so the same
// question gets asked for a soft back, a browser back and a tab close
useUnsavedGuard(() => editingId.value !== null && isInlineDirty())

/**
 * Escape cancels. Deliberately NOT Enter-to-save: Enter already means something inside the row —
 * a newline in a 複数行 field, committing an IME composition, choosing an option in a picker — so
 * binding it to "save the record" turns an ordinary keystroke into a write. Saving stays an explicit
 * press of 保存.
 */
const onInlineKey = (e: KeyboardEvent) => {
    if (editingId.value === null) return
    if (e.key !== 'Escape') return
    e.preventDefault()
    cancelInlineEdit()
}

/** Seeds every field, not just the visible columns: a lookup can copy into a field off-screen. */
const seedEditValues = (rec: FlowRecordDto) => {
    Object.keys(editValues).forEach((k) => delete editValues[k])
    Object.keys(editErrors).forEach((k) => delete editErrors[k])
    for (const f of definition.value?.fields ?? []) {
        if (f.id) editValues[f.id] = rec.values?.[f.id] ?? emptyFieldValue(f)
    }
    editBaseline.value = JSON.stringify(editValues)
}

const startInlineEdit = async (rec: FlowRecordDto) => {
    clearClickTimer() // a double-click fires click first; don't let the deferred open land
    if (!rec.can_edit) { dialog.ping('このレコードを編集する権限がありません。'); return }
    if (editingId.value !== null && editingId.value !== rec.id && isInlineDirty()) {
        const answer = await dialog.ask('編集中の内容が保存されていません。破棄して別の行を編集しますか？')
        if (!answer?.value) return
    }
    // pin the layout to what is on screen before the inputs appear (no-op once the user has resized:
    // those widths are already explicit, so nothing can reflow)
    if (!Object.keys(userWidths.value).length) lockWidths.value = measureColumns()
    editingId.value = rec.id
    seedEditValues(rec)
    nextTick(measureGrid) // the row grows as it turns into inputs
    if (!inlineFields(rec).length) dialog.ping('この表示中の列にはこの行で編集できる項目がありません。')
}

const releaseWidthLock = () => { lockWidths.value = {}; gridHeight.value = 0 }

const cancelInlineEdit = async () => {
    if (isInlineDirty()) {
        const answer = await dialog.ask('編集中の内容を破棄しますか？')
        if (!answer?.value) return
    }
    editingId.value = null
    releaseWidthLock()
}

const saveInline = async (rec: FlowRecordDto) => {
    // Only the columns on screen are editable here, so only those are validated and sent — the
    // endpoint merges onto the stored record, so untouched fields (and their 必須) are unaffected.
    const fields = inlineFields(rec)
    const found = validateRecordValues(fields, editValues, { stored: rec.values ?? null })
    Object.keys(editErrors).forEach((k) => delete editErrors[k])
    Object.assign(editErrors, found)
    // a bad column can be scrolled off to the side here, so the under-input message alone is easy to
    // miss entirely — the row just refuses to close with no reason given
    const problem = validationSummary(fields, found)
    if (problem) {
        dialog.ping(problem)
        return
    }

    const payload: Record<string, any> = {}
    for (const f of fields) payload[f.id!] = editValues[f.id!]

    savingInline.value = true
    try {
        const fresh = await api.post('/flow_app_record_update', { id: rec.id, values: payload }, { toast: '保存しました。' }) as FlowRecordDto | null
        if (!fresh) return
        // patch the row in place — the response carries recomputed formulas, the current status and a
        // re-resolved editable_field_ids, so the row stays truthful without a full reload
        const i = records.value.findIndex((r) => r.id === rec.id)
        if (i !== -1) records.value[i] = fresh
        editingId.value = null
        releaseWidthLock()
        // server mode computes the 集計スロット over the whole filtered set, so an edited number leaves
        // them stale; client mode recomputes from `records` by itself.
        if (mode.value === 'server' && rawSlots.value.length) await load()
    } finally {
        savingInline.value = false
    }
}

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
onMounted(() => {
    document.addEventListener('keydown', onInlineKey)
    // the popup is anchored to a viewport position, so anything that moves the cell invalidates it
    document.getElementById('rvScroll')?.addEventListener('scroll', hidePeek, { passive: true })
    window.addEventListener('resize', hidePeek)
    window.addEventListener('resize', measureGrid)
})
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onInlineKey)
    document.getElementById('rvScroll')?.removeEventListener('scroll', hidePeek)
    window.removeEventListener('resize', hidePeek)
    window.removeEventListener('resize', measureGrid)
    clearClickTimer()
    clearPeekTimers()
})

// The open editor belongs to one row of one page. Changing page, view, filter or search rebuilds the
// list under it, so close it rather than leave it attached to a row that may no longer be there.
watch([page, activeViewId, search, () => JSON.stringify(adhocFilter)], () => { editingId.value = null; releaseWidthLock() })
watch([flowId, activeViewId], loadWidths, { immediate: true })
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
/* Only once widths are explicit — otherwise columns would share the space equally instead of sizing
   to their content.
   Named rv-fixed, not `fixed`: Tailwind ships a global `.fixed { position: fixed }`, and a scoped
   block gives no protection against that — the utility matches the same element. Adding `fixed` to
   the table took it out of the flow, so a bottom 集計スロット floated up under the toolbar and the
   out-of-flow table covered the column headers. */
.rv-table.rv-fixed { table-layout: fixed; }
.rv-colgrip { position: absolute; top: 0; right: 0; width: 9px; height: 100%; cursor: col-resize; user-select: none; }
/* Editing a row turns every column edge into a handle. The gridlines are the affordance — the line
   you see is the line you grab — and the grip runs the table's full height (measured into
   --rv-grip-h) instead of hiding in the header. Only while editing: a full-height 9px band over
   every column boundary would otherwise sit in front of ordinary row clicks.

   The line is an inset shadow, not a border: a border is part of the cell box, so switching it on
   widened every cell by 1px and shifted the whole row sideways the moment edit mode opened. An inset
   shadow paints in the same place and costs no layout, so the columns hold still. */
.rv-table.rv-gridlines .rv-th, .rv-table.rv-gridlines .rv-td { box-shadow: inset -1px 0 0 var(--calendarBorder); }
.rv-table.rv-gridlines .rv-td:last-child, .rv-table.rv-gridlines .rv-th:last-child { box-shadow: none; }
.rv-table.rv-gridlines .rv-colgrip { height: var(--rv-grip-h, 100%); }
.rv-table.rv-gridlines .rv-colgrip:hover::after, .rv-table.rv-gridlines .rv-colgrip.dragging::after { top: 0; bottom: 0; }
/* Keep this selector free of :global(). Written as `:global(body.rv-resizing) .rv-colgrip::after`,
   Vue's scoped compiler emitted just `body.rv-resizing` — dropping the descendant and the
   pseudo-element — so these styles, background: var(--primary-color) included, were applied to
   <body> and turned the whole screen black for the duration of a drag. The drag state rides on
   ordinary classes on the table and the grip instead, which keeps these rules local.

   right: 0 puts the highlight on the column boundary itself — the same 1px the gridline paints, and
   the grip's own right edge. At right: 4px it floated 4px inside the cell, so the line you grabbed
   and the line that moved were visibly different lines. */
.rv-colgrip:hover::after, .rv-colgrip.dragging::after { content: ""; position: absolute; top: 4px; bottom: 4px; right: 0; width: 2px; background: var(--primary-color); border-radius: 1px; }
/* A drag sweeps the pointer sideways across the other boundaries, and each one it crossed lit up on
   hover. Only the column actually being resized shows a handle for the duration. */
.rv-table.rv-dragging .rv-colgrip:not(.dragging):hover::after { content: none; }
/* body still carries the drag cursor: it must win over whatever the pointer is currently above.
   This one has no descendant, so it compiles as written. */
:global(body.rv-resizing) { cursor: col-resize; user-select: none; }
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
/* Slot strip: a free row above/below the table, deliberately not aligned to the columns.
   sticky keeps it in place while the table scrolls horizontally underneath — the strip is only as
   wide as the scrollport (the table is much wider), so pinning its left edge is enough.
   `left` must match margin-left: when stuck, the offset is measured to the border box, so left:0
   would drop the inset and slide the strip 12px toward the edge on scroll. */
.rv-slot { position: sticky; left: 12px; z-index: 1; display: flex; flex-wrap: wrap; gap: 8px 20px; padding: 10px 12px; background: var(--bg3); border: 1px solid var(--calendarBorder); border-radius: 8px; margin: 8px 12px; }
.rv-slot-item { display: inline-flex; align-items: baseline; gap: 8px; }
.rv-slot-label { font-size: 11.5px; color: gray; }
.rv-slot-value { font-size: 14px; color: var(--primary-color); font-variant-numeric: tabular-nums; }
.rv-row { cursor: pointer; }
.rv-row:hover { background: var(--selected-background); }
/* --- クイック編集 row ---------------------------------------------------------------------------
   A normal cell is a single clipped line (nowrap + overflow:hidden + max-width). An input needs the
   opposite of all three, and `overflow: visible` is what lets a picker's dropdown — positioned
   absolute inside the field, never teleported — escape the cell instead of being cut off at it.
   The .rv-scroll container still bounds it, which is fine: useFloatingMenu already flips a menu
   above its input when there isn't room below. */
.rv-row.editing { background: var(--selected-background); }
.rv-row.editing .rv-td { vertical-align: top; }
/* except a cell holding just a switch: 20px against a ~36px picker, top-aligned, reads as stuck to
   the top of the row. Centring the cell needs no number to keep in step with the neighbour's height.
   Here rather than in flow-shared.css because the rule above ties on specificity and wins by order. */
.rv-row.editing .rv-td:has(.flow-sw) { vertical-align: middle; }
/* No min-width here: that is what re-laid out the table when a row opened for editing. The cell keeps
   its column's width and the inputs fit into it (min-width:0 below), so nothing moves. */
.rv-td.edit { position: relative; white-space: normal; overflow: visible; padding: 8px 10px; }
.rv-td.edit :deep(.fi-input), .rv-td.edit :deep(input), .rv-td.edit :deep(select), .rv-td.edit :deep(textarea) { box-sizing: border-box; max-width: 100%; min-width: 0; }
.rv-cellerr { font-size: 11px; color: #e2574c; margin-top: 3px; white-space: normal; }
/* a read-only cell in an editing row: dimmed so it reads as "not yours to change", with a reason on hover */
.rv-row.editing .rv-td:not(.edit):not(.rv-td-check):not(.rv-td-action) { opacity: .55; }

/* Save bar under the editing row. The <td> spans the whole table (which can be far wider than the
   screen), so the strip inside is pinned with position:sticky — the same approach as .rv-slot, and
   `left` matches the margin because a sticky offset is measured to the border box. */
/* Truncated-cell peek. position:fixed against the viewport, since the coordinates come from the
   cursor; the height is capped inline (see peekStyleFor) and the body scrolls inside it. */
/* align-items stays at its default `stretch` — with flex-start the body is sized by its content, so
   nothing bounds it, overflow:auto has nothing to act on, and a 400-line value spills straight past
   the popup's border while the box itself looks correctly capped. The button gets align-self instead.
   overflow:hidden on the container is the backstop that keeps anything inside the rounded corners. */
.rv-peek { position: fixed; z-index: 60; box-sizing: border-box; display: flex; gap: 8px; padding: 10px 12px; overflow: hidden; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 8px; box-shadow: 0 8px 24px rgba(0, 0, 0, .18); }
/* min-height:0 lets a flex item shrink below its content height — without it the scroll never starts */
.rv-peek-body { flex: 1 1 auto; min-width: 0; min-height: 0; overflow: auto; font-size: 13px; line-height: 1.7; color: var(--primary-color); white-space: pre-wrap; overflow-wrap: break-word; }
/* In the flow beside the first line, not floating over a corner: its 22px box matches the 13px/1.7
   line box, so it sits level with that line. Which side it takes follows where the popup opened —
   row-reverse when the popup is left of the cursor — so the button is always on the edge the cursor
   is already next to. */
.rv-peek-copy { flex: none; align-self: flex-start; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; padding: 0; border: 1px solid var(--formBorder); border-radius: 5px; background: var(--background-color); color: var(--primary-color); cursor: pointer; }
.rv-peek-copy:hover { background: var(--bg3); border-color: var(--primary-color); }
.rv-peek.rv-peek-lefthand { flex-direction: row-reverse; }

.rv-editbar { background: var(--selected-background); }
.rv-editbartd { padding: 0 0 8px; border-bottom: 1px solid var(--calendarBorder); }
/* The row spans the whole table, so flex-end puts the buttons at the table's right edge — which is
   off-screen on a wide table. `position: sticky; right` then pulls them back to the screen's right
   edge and holds them there while scrolling (sticky can only pull a box toward the scrollport, which
   is why they have to start out at the far right rather than at the left). */
.rv-editbarrow { display: flex; justify-content: flex-end; padding-right: 12px;margin-top: 10px; }
/* the 12px gutter has to live on the sticky offset, not on the row's padding: once pinned, the box
   is held that far from the scrollport edge and the parent's padding no longer has any say */
.rv-editbarwrap { position: sticky; right: 12px; z-index: 3; display: inline-flex; align-items: center; gap: 8px; }
/* .l-button (global, from LoaderButton) brings min-height:35px / font-size:14px / min-width:100px,
   so 保存 stood taller than a plain <button> next to it. Two classes deep beats it without
   !important; the fill stays LoaderButton's, so the primary action still reads as primary. */
.rv-editbarwrap .rv-editbtn { box-sizing: border-box !important; height: 30px; min-height: 0; min-width: 0; margin: 0; padding: 0 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 12.5px; border-radius: 6px; letter-spacing: normal; white-space: nowrap; cursor: pointer; }
.rv-editbarwrap button.rv-editbtn { border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); }
.rv-editbarwrap button.rv-editbtn:hover:not(:disabled) { background: var(--bg3); }
.rv-editbarwrap button.rv-editbtn:disabled { opacity: .6; cursor: default; }
.rv-td { font-size: 13.5px; padding: 13px 14px; border-bottom: 1px solid var(--calendarBorder); vertical-align: middle; white-space: nowrap; max-width: 280px; overflow: hidden; text-overflow: ellipsis; }
.rv-td.num { text-align: right; font-variant-numeric: tabular-nums; }
.rv-td-action { text-align: right; width: 72px; }
.rv-row.editing .rv-td { border-bottom-color: transparent; }
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
