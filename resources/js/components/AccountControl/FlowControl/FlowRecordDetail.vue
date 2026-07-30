<template>
    <div class="admin-window rd-screen" :class="{ overlay: isNarrow }">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div class="rd-bar">
            <div class="flex items-center gap-[12px] min-w-0">
                <div class="rd-ghost" @click="back"><Back size="13" /></div>
                <FlowAppIcon
                    v-if="definition"
                    class="flex-none"
                    :icon-svg="definition.icon_svg"
                    :icon-image="definition.icon_image"
                    :color-id="definition.color_id"
                    :name="definition.name"
                    :seed="definition.id"
                    :size="28"
                />
                <div class="min-w-0">
                    <div v-if="definition" class="rd-appname truncate">{{ definition.name }}</div>
                    <div class="rd-title truncate">{{ recordTitle }}</div>
                </div>
            </div>
            <!-- up/down record navigation, matching the list's newest-first order:
                 ↑ = newer (higher #), ↓ = older (lower #) -->
            <div v-if="mode === 'view' && !isNew" class="rd-nav">
                <button class="rd-navbtn" :disabled="!nav.next" :title="nav.next ? `一つ上のレコード #${nav.next}` : '上のレコードはありません'" @click="goToRecord(nav.next)">
                    <Back fill="currentColor" :size="12" class="rotate-90" />
                </button>
                <button class="rd-navbtn" :disabled="!nav.prev" :title="nav.prev ? `一つ下のレコード #${nav.prev}` : '下のレコードはありません'" @click="goToRecord(nav.prev)">
                    <Back fill="currentColor" :size="12" class="-rotate-90" />
                </button>
            </div>
            <!-- app settings: top-right of the title bar; hidden while editing a record and on mobile
                 (mobile consolidates it, along with the PDF/削除/編集 tools, into the ⋮ menu below) -->
            <button v-if="mode === 'view' && permissions?.manage && !isNarrow" class="rd-settings" title="アプリ設定" @click="editApp">
                <Gear :size="15" /><span class="rd-settings-label">アプリ設定</span>
            </button>
            <ItemMenu v-if="mode === 'view' && isNarrow && mobileMenuItems.length" class="rd-mobile-menu" :items="mobileMenuItems" title="メニュー" />
        </div>

        <div class="rd-flow">
            <div class="rd-flow-status">
                <template v-if="showStatus">
                    <span class="rd-flow-cur" :style="currentStatusStyle">{{ record?.current_status }}</span>
                    <!-- status transitions are hidden while editing — you can't move status mid-edit -->
                    <!-- only the actions this user may actually press are shown; the separator
                         goes with them, so a read-only viewer just sees the current status -->
                    <template v-if="pressableActions.length && mode === 'view'">
                        <span class="rd-flow-sep">→</span>
                        <button
                            v-for="a in pressableActions"
                            :key="a.id"
                            class="rd-act"
                            :style="actionStyle(a)"
                            :disabled="transitioning"
                            :title="`${a.to_status ?? ''}へ移動`"
                            @click="transition(a)"
                        >{{ a.label }}</button>
                    </template>
                </template>
            </div>
            <div class="rd-flow-tools">
                <!-- desktop only: on mobile these consolidate into the ⋮ menu in the title bar -->
                <template v-if="mode === 'view' && !isNarrow">
                    <button v-for="t in pdfTools" :key="t.id" class="rd-tool" @click="downloadPdf(t)" :title="t.name"><FileIcon ext="unknown" class="rd-tool-file" />{{ t.name }}</button>
                    <button v-if="canDuplicate" class="rd-tool" title="このレコードを複製して新規作成" @click="duplicate"><Copy size="13" />複製</button>
                    <button v-if="!isNew && can.delete" class="rd-tool danger" @click="remove"><Trash size="13" />削除</button>
                    <button v-if="can.edit" class="rd-tool primary" title="編集（E）" @click="mode = 'edit'"><Edit size="13" />編集</button>
                </template>
                <template v-else-if="mode === 'edit'">
                    <button class="rd-tool" @click="cancelEdit">キャンセル</button>
                    <button class="rd-tool primary" :disabled="saving" @click="save">保存</button>
                </template>
            </div>
        </div>

        <div class="rd-body">
            <div class="rd-main">
            <FlowRecordForm
                :fields="definition?.fields ?? []"
                :values="values"
                :errors="errors"
                :readonly="mode === 'view'"
                :editable-field-ids="record?.editable_field_ids ?? null"
                :is-new="isNew"
                :users="users"
                :projects="projects"
                :record-id="record?.id ?? null"
                :stacked="isNarrow"
            />
            </div>

            <div v-if="!isNew" class="rd-side" :class="{ mobile: isNarrow, open: sheetOpen, collapsed: !isNarrow && sideCollapsed }">
                <div class="rd-side-inner">
                <div class="rd-tabs">
                    <button v-if="!isNarrow" class="rd-collapse" @click="toggleSide" title="パネルを隠す"><Back fill="currentColor" :size="11" class="rotate-180"/></button>
                    <div class="rd-tabseg">
                        <button
                            v-for="t in sideTabs"
                            :key="t.k"
                            class="rd-tabbtn"
                            :class="{ on: activeTab === t.k }"
                            :title="t.label"
                            :aria-label="t.label"
                            @click="selectTab(t.k)"
                        >
                            <component :is="t.icon" :size="16" />
                            <!-- unread comments: cleared only after the tab has actually been viewed (~5s) -->
                            <span v-if="t.k === 'comment' && unreadComments" class="rd-tab-badge">{{ unreadComments }}</span>
                        </button>
                    </div>
                    <button
                        v-if="isNarrow"
                        class="rd-sheet-toggle"
                        :title="sheetOpen ? 'パネルを閉じる' : 'パネルを開く'"
                        @click="sheetOpen = !sheetOpen"
                    >
                        <Back fill="currentColor" :size="11" :class="sheetOpen ? '-rotate-90' : 'rotate-90'" />
                    </button>
                </div>
                <div class="rd-side-content" v-show="!isNarrow || sheetOpen">
                    <AppCommentSection
                        v-if="activeTab === 'comment' && record"
                        commentable-type="flow_record"
                        :commentable-id="record.id"
                        :users="mentionableUsers"
                        title="コメント"
                        variant="panel"
                    />
                    <template v-else-if="activeTab === 'history'">
                        <p v-if="!logs.length" class="rd-placeholder">変更履歴はありません。</p>
                        <div v-for="lg in logs" :key="lg.id" class="rd-log">
                            <div class="rd-log-head">
                                <div>
                                    <div class="rd-log-action">{{ logHeader(lg) }}</div>
                                    <div class="rd-log-date">{{ fmtLogDate(lg.created_at) }}</div>
                                </div>
                                <UserPanel v-if="lg.user" :user="lg.user" with-name size="22" disable-instant />
                            </div>
                            <div v-if="lg.changes && Object.keys(lg.changes).length" class="rd-log-rows">
                                <div v-for="(chg, key) in lg.changes" :key="key" class="rd-log-row">
                                    <span class="rd-log-label">{{ changeLabel(key) }}</span>
                                    <!-- secrets never show values; a rotation reads true→true, so state the action -->
                                    <span v-if="isSecretChange(key)" class="rd-log-new">{{ secretChangeText(chg) }}</span>
                                    <template v-else>
                                        <span class="rd-log-old">{{ fmtChange(key, chg.old) }}</span>
                                        <span class="rd-log-arrow">→</span>
                                        <span class="rd-log-new">{{ fmtChange(key, chg.new) }}</span>
                                    </template>
                                </div>
                            </div>
                            <div v-if="lg.note" class="rd-log-note">{{ lg.note }}</div>
                        </div>
                    </template>
                </div>
                </div>
            </div>

            <Transition name="chipFade">
                <button
                    v-if="!isNew && !isNarrow && sideCollapsed"
                    class="rd-reveal"
                    @click="toggleSide"
                    title="パネルを表示"
                >
                    <Back :size="11" fill="currentColor" />
                    <span v-if="unreadComments" class="rd-tab-badge rd-reveal-badge">{{ unreadComments }}</span>
                </button>
            </Transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useApi } from '@/composables/api'
import { useFlowOptionsStore } from '@/store/flowOptions'
import { useFilePreview } from '@/store/filePreview'
import { useResponsive } from '@/store/responsive'
import { submittableValues, validateRecordValues } from '@/utils/flowValidation'
import { emptyFieldValue, resolveFieldDefault } from '@/utils/flowDefaults'
import { readableTextColor } from '@/utils/flowColor'
import { flowColorValue } from '@/utils/flowColors'
import { useTheme } from '@/store/theme'
import { pageTitleOverride } from '@/composables/pageTitle'
import { useAuthUserStore } from '@/store/auth'
import FlowRecordForm from './FlowRecordForm.vue'
import FlowAppIcon from './FlowAppIcon.vue'
import Trash from '@/components/Icons/Trash.vue'
import Edit from '@/components/Icons/Edit.vue'
import Copy from '@/components/Icons/Copy.vue'
import Gear from '@/components/Icons/Gear.vue'
import Comment from '@/components/Icons/Comment.vue'
import ChangeLog from '@/components/Icons/ChangeLog.vue'
import FileIcon from '@/components/Board/Mixed/FileIcon.vue'
import AppCommentSection from '@/components/Global/AppCommentSection.vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import { isLayoutType, isSecretType } from '@/types/flow'
import type { FlowField, FlowDefinitionApi, FlowRecordDto, FlowAppPermissionsDto, FlowAppTool } from '@/types/flow'
import type { MenuList } from '@/interface/globalInterface'
import Back from '@/components/Icons/Back.vue'

const api = useApi()
const route = useRoute()
const router = useRouter()
const responsive = useResponsive()
const auth = useAuthUserStore()

const loading = ref(true)
const saving = ref(false)
const mode = ref<'view' | 'edit'>('view')
const definition = ref<FlowDefinitionApi | null>(null)
// keep the app's name in the browser tab title while viewing/editing a record
watch(() => definition.value?.name, (name) => { if (name) pageTitleOverride.value = name })
const permissions = ref<FlowAppPermissionsDto | null>(null)
const record = ref<FlowRecordDto | null>(null)
const theme = useTheme()
// the app's theme accent (default for status buttons + fallback for status pills)
const appAccent = computed(() => flowColorValue(definition.value?.color_id, theme.dark, definition.value?.id ?? 0))
const statusColorById = computed<Record<number, string | null>>(() => {
    const map: Record<number, string | null> = {}
    for (const s of definition.value?.statuses ?? []) if (s.id != null) map[s.id] = s.color ?? null
    return map
})
// current-status pill: its own color, else neutral grey
const currentStatusStyle = computed(() => {
    const c = record.value?.current_status_id != null ? statusColorById.value[record.value.current_status_id] : null
    return c ? { background: c, color: readableTextColor(c), borderColor: c } : {}
})
// action button: its own color, else inherit the app theme accent; auto-contrast text
const actionStyle = (a: { can: boolean; color?: string | null }) => {
    if (!a.can) return {}
    const c = a.color || appAccent.value
    return { background: c, borderColor: c, color: readableTextColor(c) }
}
const can = reactive({ view: true, edit: true, delete: false })

const filePreview = useFilePreview()
const flowOptionsStore = useFlowOptionsStore()
const { users, projects } = storeToRefs(flowOptionsStore)

/**
 * Anything that owns the keyboard while it is on screen: .chatCreate is the app-wide dialog panel
 * (Modal.vue and the hand-rolled overlays alike), .cu-toast-mask the global confirm/prompt from
 * dialog.ts — the one behind api.post({ ask }) — and .md-window the file preview.
 *
 * Deliberately NOT .overlay: this screen puts that class on its own root when narrow, so matching
 * it would silently kill the shortcut on mobile. .mini-info is out too — a toast blocks nothing.
 */
const BLOCKING_LAYERS = '.chatCreate, .cu-toast-mask, .md-window'

const isTypingTarget = (el: EventTarget | null): boolean => {
    const node = el as HTMLElement | null
    if (!node || !node.tagName) return false
    if (node.isContentEditable) return true

    return ['INPUT', 'TEXTAREA', 'SELECT'].includes(node.tagName)
}

/**
 * "E" opens edit mode — only when the 編集 button is genuinely available.
 *
 * The guards are the whole feature: a bare letter shortcut is one careless keystroke away from
 * hijacking normal typing, so it bails out when focus is in a field (typing "e" into 件名 or the
 * comment box must not flip the record), when an IME is mid-composition, when a modifier is held
 * (Cmd+E / Ctrl+E belong to the browser), and when a dialog or the file preview is on top.
 */
const onEditHotkey = (e: KeyboardEvent) => {
    if (e.key !== 'e' && e.key !== 'E') return
    if (e.ctrlKey || e.metaKey || e.altKey) return
    if (e.isComposing || e.keyCode === 229) return
    if (isTypingTarget(e.target) || isTypingTarget(document.activeElement)) return
    if (filePreview.active || document.querySelector(BLOCKING_LAYERS)) return
    if (mode.value !== 'view' || !can.edit) return

    e.preventDefault()
    mode.value = 'edit'
}

onMounted(() => document.addEventListener('keydown', onEditHotkey))
onBeforeUnmount(() => document.removeEventListener('keydown', onEditHotkey))

const values = reactive<Record<string, any>>({})
const errors = reactive<Record<string, string | null>>({})

interface StatusActionDto { id: number; label: string; color?: string | null; to_status_id: number | null; to_status?: string | null; can: boolean }
const statusActions = ref<StatusActionDto[]>([])
// prev/next record numbers (record-number order, view-permission aware) for the header arrows
const nav = reactive<{ prev: number | null; next: number | null }>({ prev: null, next: null })
// list context riding on the record URL (?view/?sf/?sd/?f) — carried across up/down shifts and
// handed back to the records list on 戻る so view, sort and ad-hoc filter all survive
const LIST_CONTEXT_KEYS = ['view', 'sf', 'sd', 'f'] as const
const listContext = () => {
    const q: Record<string, any> = {}
    for (const k of LIST_CONTEXT_KEYS) if (route.query[k] != null) q[k] = route.query[k]
    return q
}
const goToRecord = (n: number | null) => {
    if (n == null) return
    // never carry ?edit= — arriving on the next record mid-edit would be a surprise
    router.push({ name: 'flow-record-detail', params: { flowId: flowId.value, recordId: n }, query: listContext() })
}
const transitioning = ref(false)
// buttons the user can't press are hidden rather than greyed out — a disabled button invites a
// click and then explains why not; absence is quieter and matches 対応待ち, which never listed them
const pressableActions = computed(() => statusActions.value.filter((a) => a.can))

interface LogDto { id: number; user?: any; action?: string; field?: string | null; old_value?: any; new_value?: any; changes?: Record<string, any> | null; note?: string | null; created_at?: string }
const logs = ref<LogDto[]>([])
const mentionableUsers = ref<any[]>([])

const activeTab = ref<'comment' | 'history'>('comment')
const sheetOpen = ref(false)
const isNarrow = computed(() => responsive.mobile)

// Right panel collapse (desktop) — persisted so the choice sticks across records/sessions.
const SIDE_COLLAPSE_KEY = 'flow_rd_side_collapsed'
const sideCollapsed = ref(localStorage.getItem(SIDE_COLLAPSE_KEY) === '1')
const toggleSide = () => {
    sideCollapsed.value = !sideCollapsed.value
    localStorage.setItem(SIDE_COLLAPSE_KEY, sideCollapsed.value ? '1' : '0')
}
const sideTabs = [
    { k: 'comment' as const, label: 'コメント', icon: Comment },
    { k: 'history' as const, label: '変更履歴', icon: ChangeLog },
]
const selectTab = (k: 'comment' | 'history') => {
    activeTab.value = k
    if (isNarrow.value) sheetOpen.value = true
}

const flowId = computed(() => route.params.flowId)
const recordId = computed(() => route.params.recordId as string | undefined)
const isNew = computed(() => !recordId.value)
// duplicate ("複製"): new-record screen opened as ?from={sourceRecordId} — seed the form with that
// record's values instead of plain defaults. Holds the fetched source values while seeding.
const dupFrom = computed(() => (route.query.from ? String(route.query.from) : null))
const dupValues = ref<Record<string, any> | null>(null)



/* ---- unread-comment badge: clears only after the comment tab has really been viewed ---- */
const unreadComments = ref(0)
// "viewed" = comment tab active AND its content actually on screen (panel expanded / sheet open)
const commentVisible = computed(() =>
    !isNew.value && !!record.value && activeTab.value === 'comment'
    && (isNarrow.value ? sheetOpen.value : !sideCollapsed.value))
let commentReadTimer: ReturnType<typeof setTimeout> | null = null
watch([commentVisible, unreadComments], () => {
    if (commentReadTimer) { clearTimeout(commentReadTimer); commentReadTimer = null }
    if (!commentVisible.value || unreadComments.value === 0) return
    // ~5s of the tab being visible counts as "read" — then clear the badge server-side
    commentReadTimer = setTimeout(async () => {
        const id = record.value?.id
        if (!id || !commentVisible.value) return
        await api.post('/flow_notification_comments_read', { record_id: id }, { silent: true })
        unreadComments.value = 0
    }, 5000)
}, { immediate: true })
onBeforeUnmount(() => { if (commentReadTimer) clearTimeout(commentReadTimer) })

const recordTitle = computed(() => (isNew.value ? (dupFrom.value ? '新規レコード（複製）' : '新規レコード') : `#${record.value?.record_number ?? recordId.value ?? ''}`))
// 複製: open the new-record screen pre-filled from this record (needs 追加 permission)
const canDuplicate = computed(() => !isNew.value && !!permissions.value?.add && !!record.value?.id)
const duplicate = () => router.push({ name: 'flow-record-new', params: { flowId: flowId.value }, query: { from: record.value!.id } })
const showFlow = computed(() => !!definition.value?.use_status_flow && !isNew.value)
// Show the status area only when the app uses the flow AND this record actually has a status.
const showStatus = computed(() => showFlow.value && !!record.value?.current_status)

// active PDF tools → download buttons (only for saved records)
const pdfTools = computed<FlowAppTool[]>(() =>
    isNew.value ? [] : (definition.value?.tools ?? []).filter((t) => t.tool_type === 'pdf' && t.is_active),
)
const downloadPdf = (tool: FlowAppTool) => {
    if (!tool.id || !record.value?.id) return
    window.open(`/flow_tool_pdf/${tool.id}/${record.value.id}`, '_blank')
}

// mobile: the PDF/削除/編集 buttons + アプリ設定 consolidate into one ⋮ menu (desktop keeps separate buttons)
const mobileMenuItems = computed<MenuList[]>(() => {
    const items: MenuList[] = []
    pdfTools.value.forEach((t) => items.push({ title: t.name, action: () => downloadPdf(t) }))
    if (canDuplicate.value) items.push({ title: '複製', action: () => duplicate() })
    if (!isNew.value && can.delete) items.push({ title: '削除', action: () => remove() })
    if (can.edit) items.push({ title: '編集', action: () => { mode.value = 'edit' } })
    if (permissions.value?.manage) items.push({ title: 'アプリ設定', action: () => editApp() })
    return items
})

const fieldLabelByKey = computed<Record<string, string>>(() => {
    const map: Record<string, string> = {}
    ;(definition.value?.fields ?? []).forEach((f) => { map[f.key] = f.label })
    return map
})
const fmtLogDate = (v?: string) => {
    if (!v) return ''
    const d = new Date(v)
    if (isNaN(d.getTime())) return String(v)
    const p = (n: number) => String(n).padStart(2, '0')
    return `${d.getFullYear()}/${p(d.getMonth() + 1)}/${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`
}
const fieldByKey = computed<Record<string, FlowField>>(() => {
    const map: Record<string, FlowField> = {}
    ;(definition.value?.fields ?? []).forEach((f) => { map[f.key] = f })
    return map
})
const userName = (id: any) => users.value.find((u) => u.id === Number(id))?.name ?? `#${id}`
const logHeader = (lg: LogDto) => (lg.action === 'created' ? '作成' : '更新')
const changeLabel = (key: string) => (key === 'status' ? 'ステータス' : (fieldLabelByKey.value[key] ?? key))
// history rows for encrypted fields: state what happened, never a value (and never a bare
// "設定あり → 設定あり", which is what a rotation would otherwise look like)
const isSecretChange = (key: string) => {
    const t = fieldByKey.value[key]?.input_type
    return !!t && isSecretType(t)
}
const secretChangeText = (chg: { old?: any; new?: any }) => {
    if (chg.new !== true) return '削除されました'
    return chg.old === true ? '変更されました' : '設定されました'
}
const fmtChange = (key: string, val: any): string => {
    if (key === 'status') return (val ?? '') === '' ? '未設定' : String(val)
    if (val === null || val === undefined || val === '' || (Array.isArray(val) && !val.length)) return '未設定'
    const f = fieldByKey.value[key]
    const t = f?.input_type
    if (t === 'password') return val === true ? '設定あり' : '未設定'
    // (secret rows are rendered via secretChangeText, not old→new — see the history template)
    if (t === 'user' || t === 'member') return (Array.isArray(val) ? val : [val]).map(userName).join('、')
    if (t === 'file') return (Array.isArray(val) ? val : [val]).map((x: any) => x?.name ?? x).join('、')
    if (t === 'checkbox') return (Array.isArray(val) ? val : [val]).join(' / ')
    if (t === 'toggle') return val ? 'オン' : 'オフ'
    if (t === 'number') return Number(val).toLocaleString()
    if (t === 'table') return Array.isArray(val) ? `${val.length}行` : '未設定'
    if (t === 'reference') return val?.label || (val?.number != null ? `#${val.number}` : '未設定')
    return String(val)
}


// duplicate copies every editable field EXCEPT formula (recomputed), layout (no value), and file
// (attachments aren't re-uploaded — copying the refs would share storage between records)
const canDuplicateField = (f: FlowField) => !isLayoutType(f.input_type) && f.input_type !== 'formula' && f.input_type !== 'file' && !isSecretType(f.input_type)
const cloneVal = (v: any) => (v && typeof v === 'object' ? JSON.parse(JSON.stringify(v)) : v)

const initValues = () => {
    const dup = isNew.value ? dupValues.value : null
    ;(definition.value?.fields ?? []).forEach((f) => {
        if (!isNew.value) {
            values[f.id!] = record.value?.values?.[f.id!] ?? emptyFieldValue(f)
            return
        }
        // duplicate: use the source value when present, else fall back to the field's default
        const src = dup && canDuplicateField(f) ? dup[f.id!] : undefined
        values[f.id!] = src !== undefined && src !== null ? cloneVal(src) : resolveFieldDefault(f, auth.id)
    })
}

const load = async () => {
    loading.value = true
    try {
        await flowOptionsStore.load()

        if (recordId.value) {
            const data = await api.get(`/flow_app_record_by_number/${flowId.value}/${recordId.value}`)
            if (data) {
                definition.value = data.definition
                permissions.value = data.permissions
                record.value = data.record
                Object.assign(can, data.can ?? {})
                statusActions.value = data.status_actions ?? []
                logs.value = data.logs ?? []
                mentionableUsers.value = data.mentionable_users ?? []
                unreadComments.value = data.unread_comments ?? 0
                nav.prev = data.nav?.prev ?? null
                nav.next = data.nav?.next ?? null
            }
        } else {
            const data = await api.get(`/flow_app_records/${flowId.value}`)
            if (data) {
                definition.value = data.definition
                permissions.value = data.permissions
                // duplicate: pull the source record's values to pre-fill the form (view perm enforced server-side)
                dupValues.value = null
                if (dupFrom.value) {
                    const src = await api.get(`/flow_app_record/${dupFrom.value}`)
                    dupValues.value = src?.record?.values ?? null
                }
            }
        }
        initValues()
        // ?edit=1 = quick-edit shortcut from the records table
        mode.value = (isNew.value || (!!route.query.edit && can.edit)) ? 'edit' : 'view'
    } catch {
        // record missing / no access (api.ts already showed the error dialog) — don't strand the
        // user on an empty screen; send them back to the app portal. (cancels return null, not throw)
        router.push({ name: 'flow-control' })
    } finally {
        loading.value = false
    }
}

const cancelEdit = () => {
    if (isNew.value) return back()
    initValues()
    Object.keys(errors).forEach((k) => (errors[k] = null))
    mode.value = 'view'
}

const save = async () => {
    const opts = { editableFieldIds: record.value?.editable_field_ids ?? null, isNew: isNew.value }
    const found = validateRecordValues(definition.value?.fields ?? [], values, { ...opts, stored: record.value?.values ?? null })
    Object.keys(errors).forEach((k) => (errors[k] = null))
    Object.assign(errors, found)
    if (Object.values(found).some(Boolean)) return

    saving.value = true
    try {
        const payload = submittableValues(definition.value?.fields ?? [], values, opts)
        if (isNew.value) {
            const data = await api.post('/flow_app_record_create', { flow_definition_id: definition.value?.id, values: payload }, { toast: '作成しました。' })
            if (data) back()
        } else {
            const data = await api.post('/flow_app_record_update', { id: record.value?.id, values: payload }, { toast: '保存しました。' })
            if (data) await load()
        }
    } finally {
        saving.value = false
    }
}

const remove = async () => {
    if (!record.value) return
    const data = await api.post('/flow_app_record_delete', { id: record.value.id }, { ask: 'このレコードを削除しますか？', toast: '削除しました。' })
    if (data) back()
}

const transition = async (a: StatusActionDto) => {
    if (!record.value || !a.can || transitioning.value) return
    transitioning.value = true
    try {
        const data = await api.post('/flow_app_record_transition', { id: record.value.id, action_id: a.id }, { toast: `「${a.label}」を実行しました。` })
        if (data) {
            record.value = data.record
            Object.assign(can, data.can ?? {})
            statusActions.value = data.status_actions ?? []
            logs.value = data.logs ?? []
            mentionableUsers.value = data.mentionable_users ?? []
            permissions.value = data.permissions
        }
    } finally {
        transitioning.value = false
    }
}

const back = () => {
    // always land on the app's record list (history-back would step through every record the
    // up/down arrows visited); the carried list context restores view, sort and ad-hoc filter
    router.push({ name: 'flow-records', params: { flowId: flowId.value }, query: listContext() })
}
const editApp = () => router.push({ name: 'flow-builder', params: { flowId: flowId.value } })

load()
// Reload when navigating between records without unmounting (e.g. following a reference link
// from one record detail to another) — the component is reused, so setup's load() won't re-run.
watch(() => [flowId.value, recordId.value], (next, prev) => {
    if (next[0] !== prev[0] || next[1] !== prev[1]) load()
})
</script>

<style scoped>
.rd-screen { display: flex; flex-direction: column; align-items: stretch; color: var(--primary-color); }
.rd-screen.overlay { position: fixed; inset: 0; z-index: 30; background: var(--bg3); }
.rd-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--calendarBorder); background: var(--background-color); }
/* prev/next record arrows: borderless, hover chip like the other bar icons */
.rd-nav { display: inline-flex; align-items: center; gap: 2px; margin-left: auto; flex-shrink: 0; }
.rd-navbtn { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: none; background: none; border-radius: 7px; color: gray; fill: currentColor; cursor: pointer; transition: background .12s, color .12s; }
.rd-navbtn:hover:not(:disabled) { background: var(--bg3); color: var(--primary-color); }
.rd-navbtn:disabled { opacity: .3; cursor: default; }
.rd-settings { flex: none; display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 12px; border: 1px solid var(--formBorder); border-radius: 6px; background: var(--background-color); color: var(--primary-color); fill: var(--primary-color); cursor: pointer; transition: background .12s, border-color .12s; }
.rd-settings:hover { background: var(--bg3); border-color: var(--primary-color); }
.rd-settings-label { font-size: 13px; color: var(--primary-color); white-space: nowrap; }
.rd-mobile-menu { flex: none; }
.rd-ghost { display: inline-flex; align-items: center; gap: 4px; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 6px; padding: 7px 12px; font-size: 13px; cursor: pointer; fill: var(--primary-color); color: var(--primary-color); }
.rd-ghost.danger { color: #e2574c; border-color: rgba(226, 87, 76, 0.4); }
.rd-ghost:disabled { opacity: 0.5; cursor: default; }
.rd-primary { padding: 7px 18px; font-size: 13px; color: #fff; background: var(--primary-button, var(--primary-color)); border: none; border-radius: 6px; cursor: pointer; }
.rd-primary:disabled { opacity: 0.5; cursor: default; }
.rd-appname { font-size: 14px; color: var(--primary-color); line-height: 1.2; }
.rd-title { font-size: 12px; font-weight: 500; color: gray; }
.rd-status { font-size: 11px; color: var(--primary-color); background: var(--bg3); padding: 2px 8px; border-radius: 10px; display: inline-block; margin-top: 4px; }
.rd-flow { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px; border-bottom: 1px solid var(--calendarBorder); background: var(--background-color); }
.rd-flow-status { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; min-width: 0; }
.rd-flow-tools { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.rd-tool { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; padding: 4px 10px; border-radius: 6px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; }
/* PDF tool icon: neutral (common) file glyph, sized to the button row */
.rd-tool-file :deep(.file-icon-01-mobile) { width: auto; min-width: 0; height: 15px; }
.rd-tool-file { display: inline-flex; }
.rd-tool.danger { color: #e2574c; border-color: rgba(226, 87, 76, 0.4); }
.rd-tool.primary { color: #fff; background: var(--primary-button, var(--primary-color)); border-color: transparent; }
.rd-tool:disabled { opacity: 0.5; cursor: default; }
/* only the direct icon svgs (編集/削除) follow the text color; the nested FileIcon keeps its own PDF red */
.rd-tool > :deep(svg) { fill: currentColor; }
.rd-flow-cur { font-size: 12px; color: var(--primary-color); background: var(--bg3); padding: 4px 12px; border-radius: 14px; white-space: nowrap; flex-shrink: 0; }
.rd-flow-sep { color: gray; font-size: 13px; }
.rd-act { padding: 2px 10px; font-size: 12px; color: #fff; border: 1px solid transparent; border-radius: 5px; cursor: pointer; transition: opacity .12s; }
.rd-act:hover { opacity: 0.88; }
.rd-act.off { background: var(--bg3); color: gray; border-color: var(--formBorder); cursor: not-allowed; }
.rd-act:disabled { cursor: not-allowed; }
.rd-body { flex: 1; display: flex; min-height: 0; overflow: hidden; position: relative; }
.rd-main { flex: 1; min-width: 0; overflow: auto; padding: 20px; }
/* narrow screens: ignore builder-set pixel widths and stack fields full-width */
/* mobile: status row and action/tool buttons stack onto their own lines instead of being crushed */
.rd-screen.overlay .rd-flow { flex-wrap: wrap; }
.rd-screen.overlay .rd-flow-status { width: 100%; }
.rd-screen.overlay .rd-flow-tools { width: 100%; justify-content: flex-end; flex-wrap: wrap; }
.rd-side { width: 340px; flex-shrink: 0; border-left: 1px solid var(--calendarBorder); background: var(--background-color); display: flex; min-height: 0; overflow: hidden; transition: width .25s ease; }
.rd-side-inner { width: 340px; flex-shrink: 0; display: flex; flex-direction: column; min-height: 0; }
.rd-side.collapsed { width: 0; border-left: none; }
.rd-collapse { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border: none; background: none; border-radius: 6px; color: gray; cursor: pointer; flex-shrink: 0; transition: background .12s, color .12s; }
.rd-collapse:hover { background: var(--bg3); color: var(--primary-color); }
.rd-reveal { position: absolute; right: 0; top: 8px; z-index: 41; display: inline-flex; align-items: center; justify-content: center; gap: 6px; width: auto; min-width: 34px; height: 30px; padding: 0 9px 0 8px; border: 1px solid var(--calendarBorder); border-right: none; border-radius: 7px 0 0 7px; background: var(--background-color); color: gray; cursor: pointer; box-shadow: -2px 0 8px rgba(0, 0, 0, 0.08); transition: color .12s, background .12s; }
.rd-reveal:hover { background: var(--bg3); color: var(--primary-color); }
.chipFade-enter-active, .chipFade-leave-active { transition: opacity .2s ease, transform .2s ease; }
.chipFade-enter-from, .chipFade-leave-to { opacity: 0; transform: translateX(10px); }
.rd-tabs { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-bottom: 1px solid var(--calendarBorder); position: relative; }
.rd-tabseg { display: inline-flex; gap: 4px; }
.rd-tabbtn { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 28px; border: none; background: none; border-radius: 7px; color: gray; fill: currentColor; cursor: pointer; transition: background .12s, color .12s; }
/* unread-comment count riding on the comment tab (or the collapsed reveal chevron) */
.rd-tab-badge { position: absolute; top: -4px; right: 2px; min-width: 15px; height: 15px; padding: 0 4px; border-radius: 8px; background: tomato; color: #fff; font-size: 10px; line-height: 15px; text-align: center; box-sizing: border-box !important; }
/* inline next to the Back icon (the shared .rd-tab-badge is absolute for the tab variant) */
.rd-reveal-badge { position: static; top: auto; right: auto; left: auto; flex-shrink: 0; }
.rd-tabbtn:hover { color: var(--primary-color); }
.rd-tabbtn.on { background: var(--bg3); color: var(--primary-color); }
.rd-side-content { flex: 1; overflow: auto; padding: 14px; }
.rd-placeholder { font-size: 13px; color: gray; text-align: center; padding: 30px 10px; }
/* mobile sheet toggle: same chip as the desktop .rd-collapse, arrow points up to open /
   down to close. position+inset pinned — a global button rule leaks absolute/top:50px here */
.rd-sheet-toggle { position: relative; inset: auto; margin-left: auto; box-sizing: border-box !important; display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border: none; background: none; border-radius: 6px; color: gray; fill: currentColor; cursor: pointer; flex-shrink: 0; transition: background .12s, color .12s; }
.rd-sheet-toggle:hover { background: var(--bg3); color: var(--primary-color); }
.rd-side.mobile { position: fixed; left: 0; right: 0; bottom: 0; width: auto; border-left: none; border-top: 1px solid var(--calendarBorder); box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.12); z-index: 40; max-height: 72vh; }
.rd-side.mobile .rd-side-inner { width: 100%; }
/* no reserved right gap: the sheet toggle is a flex child now, not an absolute overlay */
.rd-side.mobile .rd-tabs { background: var(--background-color); }
.rd-side.mobile .rd-side-content { max-height: 58vh; }
/* --sub-color = theme-aware muted text (light #666 / dark #b0b3b8): readable in dark without the
   near-white glare of --primary-color, and not the too-dim fixed gray. */
/* disabled field (edit mode): muted + not-allowed cursor so it reads as locked; inner ignores pointer events */
.rd-sec { font-size: 12px; color: gray; margin-bottom: 10px; }
.rd-log { background: var(--bg3); border-radius: 8px; padding: 12px 14px; margin-bottom: 12px; }
.rd-log-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.rd-log-action { font-size: 13px; font-weight: 600; }
.rd-log-date { font-size: 11px; color: gray; margin-top: 2px; }
.rd-log-rows { margin-top: 10px; display: flex; flex-direction: column; gap: 6px; }
.rd-log-row { display: grid; grid-template-columns: auto 1fr auto 1fr; align-items: center; gap: 8px; font-size: 12.5px; }
.rd-log-label { color: gray; white-space: nowrap; }
.rd-log-old { color: gray; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rd-log-new { color: var(--primary-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rd-log-arrow { color: gray; }
.rd-log-note { font-size: 11px; color: gray; margin-top: 8px; }
@media (max-width: 900px) { .rd-main { padding-bottom: 60px; } }
</style>
