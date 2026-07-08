<template>
    <div class="admin-window rd-screen" :class="{ overlay: isNarrow }" :style="{ '--app-accent': appAccent }">
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
        </div>

        <div class="rd-flow">
            <div class="rd-flow-status">
                <template v-if="showStatus">
                    <span class="rd-flow-cur">{{ record?.current_status }}</span>
                    <template v-if="statusActions.length">
                        <span class="rd-flow-sep">→</span>
                        <button
                            v-for="a in statusActions"
                            :key="a.id"
                            class="rd-act"
                            :class="{ off: !a.can }"
                            :style="a.can ? { background: a.color || 'var(--primary-button)', borderColor: a.color || 'var(--primary-button)', color: readableTextColor(a.color) } : {}"
                            :disabled="!a.can || transitioning"
                            :title="a.can ? `${a.to_status ?? ''}へ移動` : 'あなたはこのアクションを実行できません'"
                            @click="transition(a)"
                        >{{ a.label }}</button>
                    </template>
                </template>
            </div>
            <div class="rd-flow-tools">
                <template v-if="mode === 'view'">
                    <button v-if="!isNew && can.delete" class="rd-tool danger" @click="remove"><Trash size="13" />削除</button>
                    <button v-if="can.edit" class="rd-tool primary" @click="mode = 'edit'"><Edit size="13" />編集</button>
                </template>
                <template v-else>
                    <button class="rd-tool" @click="cancelEdit">キャンセル</button>
                    <button class="rd-tool primary" :disabled="saving" @click="save">保存</button>
                </template>
            </div>
        </div>

        <div class="rd-body">
            <div class="rd-main">
            <div class="rd-canvas">
                <div v-for="(row, ri) in fieldRows" :key="ri" class="rd-row">
                    <div
                        v-for="field in row"
                        :key="field.id"
                        class="rd-block"
                        :class="{ 'rd-heading-block': isLayoutType(field.input_type) }"
                        :style="{ width: field.input_type === 'heading' ? '100%' : field.width + 'px' }"
                    >
                        <template v-if="isLayoutType(field.input_type)">
                            <FlowFieldInput :field="field" :model-value="null" />
                        </template>
                        <template v-else>
                            <label class="rd-label">
                                {{ field.label }}
                                <span v-if="field.is_required" class="rd-req">*</span>
                            </label>
                            <FlowFieldInput
                                :field="field"
                                :users="users"
                                :projects="projects"
                                :readonly="isReadonly(field)"
                                :preview="true"
                                v-model="values[field.id!]"
                                @update:model-value="errors[field.id!] = null"
                            />
                            <div v-if="errors[field.id!]" class="rd-err">{{ errors[field.id!] }}</div>
                        </template>
                    </div>
                </div>
            </div>
            </div>

            <div v-if="!isNew" class="rd-side" :class="{ mobile: isNarrow, open: sheetOpen, collapsed: !isNarrow && sideCollapsed }">
                <div class="rd-side-inner">
                <div class="rd-tabs">
                    <button v-if="!isNarrow" class="rd-collapse" @click="toggleSide" title="パネルを隠す"><ChevronDouble :size="16" /></button>
                    <div class="rd-tabseg">
                        <button
                            v-for="t in sideTabs"
                            :key="t.k"
                            class="rd-tabbtn"
                            :class="{ on: activeTab === t.k }"
                            :title="t.label"
                            :aria-label="t.label"
                            @click="selectTab(t.k)"
                        ><component :is="t.icon" :size="16" /></button>
                    </div>
                    <button v-if="isNarrow" class="rd-sheet-toggle" @click="sheetOpen = !sheetOpen">{{ sheetOpen ? '▼ 閉じる' : '▲ 開く' }}</button>
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
                                    <span class="rd-log-old">{{ fmtChange(key, chg.old) }}</span>
                                    <span class="rd-log-arrow">→</span>
                                    <span class="rd-log-new">{{ fmtChange(key, chg.new) }}</span>
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
                ><ChevronDouble :size="15" /></button>
            </Transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import { useResponsive } from '@/store/responsive'
import { validateFlowField } from '@/utils/flowValidation'
import { resolveFieldDefault } from '@/utils/flowDefaults'
import { readableTextColor } from '@/utils/flowColor'
import { useAuthUserStore } from '@/store/auth'
import FlowFieldInput from './FlowFieldInput.vue'
import Back from '@/components/Icons/Back.vue'
import FlowAppIcon from './FlowAppIcon.vue'
import { useTheme } from '@/store/theme'
import { flowColorValue } from '@/utils/flowColors'
import Trash from '@/components/Icons/Trash.vue'
import Edit from '@/components/Icons/Edit.vue'
import Comment from '@/components/Icons/Comment.vue'
import ChangeLog from '@/components/Icons/ChangeLog.vue'
import ChevronDouble from '@/components/Icons/ChevronDouble.vue'
import AppCommentSection from '@/components/Global/AppCommentSection.vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import { isLayoutType } from '@/types/flow'
import type { FlowField, FlowDefinitionApi, FlowRecordDto, FlowAppPermissionsDto, FlowOptionUser, FlowOptionProject } from '@/types/flow'

const api = useApi()
const route = useRoute()
const router = useRouter()
const responsive = useResponsive()
const auth = useAuthUserStore()

const loading = ref(true)
const saving = ref(false)
const mode = ref<'view' | 'edit'>('view')
const definition = ref<FlowDefinitionApi | null>(null)
const theme = useTheme()
const appAccent = computed(() => flowColorValue(definition.value?.color_id, theme.dark, definition.value?.id ?? 0))
const permissions = ref<FlowAppPermissionsDto | null>(null)
const record = ref<FlowRecordDto | null>(null)
const can = reactive({ view: true, edit: true, delete: false })
const users = ref<FlowOptionUser[]>([])
const projects = ref<FlowOptionProject[]>([])
const values = reactive<Record<string, any>>({})
const errors = reactive<Record<string, string | null>>({})

interface StatusActionDto { id: number; label: string; color?: string | null; to_status_id: number | null; to_status?: string | null; can: boolean }
const statusActions = ref<StatusActionDto[]>([])
const transitioning = ref(false)

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
const visibleFields = computed(() => (definition.value?.fields ?? []).filter((f) => !f.hidden))

const fieldRows = computed<FlowField[][]>(() => {
    const map = new Map<number, FlowField[]>()
    for (const f of visibleFields.value) {
        const r = f.layout_row ?? 0
        if (!map.has(r)) map.set(r, [])
        map.get(r)!.push(f)
    }
    return [...map.keys()].sort((a, b) => a - b)
        .map((k) => map.get(k)!.slice().sort((a, b) => (a.order_number ?? 0) - (b.order_number ?? 0)))
})

const isReadonly = (f: FlowField) => mode.value === 'view' || f.input_type === 'formula'

const recordTitle = computed(() => (isNew.value ? '新規レコード' : `#${record.value?.record_number ?? recordId.value ?? ''}`))
const showFlow = computed(() => !!definition.value?.use_status_flow && !isNew.value)
// Show the status area only when the app uses the flow AND this record actually has a status.
const showStatus = computed(() => showFlow.value && !!record.value?.current_status)

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
const fmtChange = (key: string, val: any): string => {
    if (key === 'status') return (val ?? '') === '' ? '未設定' : String(val)
    if (val === null || val === undefined || val === '' || (Array.isArray(val) && !val.length)) return '未設定'
    const f = fieldByKey.value[key]
    const t = f?.input_type
    if (t === 'user' || t === 'member') return (Array.isArray(val) ? val : [val]).map(userName).join('、')
    if (t === 'file') return (Array.isArray(val) ? val : [val]).map((x: any) => x?.name ?? x).join('、')
    if (t === 'checkbox') return (Array.isArray(val) ? val : [val]).join(' / ')
    if (t === 'toggle') return val ? 'オン' : 'オフ'
    if (t === 'number') return Number(val).toLocaleString()
    if (t === 'table') return Array.isArray(val) ? `${val.length}行` : '未設定'
    if (t === 'reference') return val?.label || (val?.number != null ? `#${val.number}` : '未設定')
    return String(val)
}

const emptyValue = (f: FlowField) => {
    if (['checkbox', 'user', 'member', 'file', 'table'].includes(f.input_type)) return []
    if (f.input_type === 'toggle') return false
    if (f.input_type === 'number' || f.input_type === 'reference') return null
    return ''
}
const initValues = () => {
    (definition.value?.fields ?? []).forEach((f) => {
        values[f.id!] = isNew.value
            ? resolveFieldDefault(f, auth.id)
            : (record.value?.values?.[f.id!] ?? emptyValue(f))
    })
}

const load = async () => {
    loading.value = true
    try {
        const opts = await api.get('/flow_options')
        if (opts) { users.value = opts.users ?? []; projects.value = opts.projects ?? [] }

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
            }
        } else {
            const data = await api.get(`/flow_app_records/${flowId.value}`)
            if (data) {
                definition.value = data.definition
                permissions.value = data.permissions
            }
        }
        initValues()
        mode.value = isNew.value ? 'edit' : 'view'
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
    let ok = true
    for (const f of visibleFields.value) {
        if (f.input_type === 'formula' || isLayoutType(f.input_type)) continue
        const err = validateFlowField(f, values[f.id!])
        errors[f.id!] = err
        if (err) ok = false
    }
    if (!ok) return

    saving.value = true
    try {
        const payload: Record<string, any> = {}
        visibleFields.value.forEach((f) => {
            if (f.input_type !== 'formula' && !isLayoutType(f.input_type)) payload[f.id!] = values[f.id!]
        })
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

const back = () => router.push({ name: 'flow-records', params: { flowId: flowId.value } })

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
.rd-ghost { display: inline-flex; align-items: center; gap: 4px; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 6px; padding: 7px 12px; font-size: 13px; cursor: pointer; fill: var(--primary-color); color: var(--primary-color); }
.rd-ghost.danger { color: #e2574c; border-color: rgba(226, 87, 76, 0.4); }
.rd-ghost:disabled { opacity: 0.5; cursor: default; }
.rd-primary { padding: 7px 18px; font-size: 13px; color: #fff; background: var(--primary-button, var(--primary-color)); border: none; border-radius: 6px; cursor: pointer; }
.rd-primary:disabled { opacity: 0.5; cursor: default; }
.rd-appname { font-size: 14px; font-weight: 600; color: var(--primary-color); line-height: 1.2; }
.rd-title { font-size: 12px; font-weight: 500; color: gray; }
.rd-status { font-size: 11px; color: var(--primary-color); background: var(--bg3); padding: 2px 8px; border-radius: 10px; display: inline-block; margin-top: 4px; }
.rd-flow { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 16px; border-bottom: 1px solid var(--calendarBorder); background: var(--background-color); }
.rd-flow-status { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; min-width: 0; }
.rd-flow-tools { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.rd-tool { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; padding: 4px 10px; border-radius: 6px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; }
.rd-tool.danger { color: #e2574c; border-color: rgba(226, 87, 76, 0.4); }
.rd-tool.primary { color: #fff; background: var(--primary-button, var(--primary-color)); border-color: transparent; }
.rd-tool:disabled { opacity: 0.5; cursor: default; }
.rd-tool :deep(svg) { fill: currentColor; }
.rd-flow-cur { font-size: 12px; font-weight: 600; color: var(--primary-color); background: var(--bg3); padding: 4px 12px; border-radius: 14px; }
.rd-flow-sep { color: gray; font-size: 13px; }
.rd-act { padding: 6px 16px; font-size: 13px; font-weight: 600; color: #fff; border: 1px solid transparent; border-radius: 7px; cursor: pointer; transition: opacity .12s; }
.rd-act:hover { opacity: 0.88; }
.rd-act.off { background: var(--bg3); color: gray; border-color: var(--formBorder); cursor: not-allowed; }
.rd-act:disabled { cursor: not-allowed; }
.rd-body { flex: 1; display: flex; min-height: 0; overflow: hidden; position: relative; }
.rd-main { flex: 1; min-width: 0; overflow: auto; padding: 20px; }
.rd-canvas { width: max-content; min-width: 100%; }
.rd-row { display: flex; gap: 12px; margin-bottom: 12px; align-items: stretch; }
.rd-block { flex: 0 0 auto; box-sizing: border-box; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 8px; padding: 15px; }
.rd-heading-block { border: none; background: none; padding: 4px 0; }
.rd-side { width: 340px; flex-shrink: 0; border-left: 1px solid var(--calendarBorder); background: var(--background-color); display: flex; min-height: 0; overflow: hidden; transition: width .25s ease; }
.rd-side-inner { width: 340px; flex-shrink: 0; display: flex; flex-direction: column; min-height: 0; }
.rd-side.collapsed { width: 0; border-left: none; }
.rd-collapse { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border: none; background: none; border-radius: 6px; color: gray; cursor: pointer; flex-shrink: 0; transition: background .12s, color .12s; }
.rd-collapse:hover { background: var(--bg3); color: var(--primary-color); }
.rd-reveal { position: absolute; right: 0; top: 8px; z-index: 41; display: inline-flex; align-items: center; justify-content: center; width: 17px; height: 30px; padding: 0; border: 1px solid var(--calendarBorder); border-right: none; border-radius: 7px 0 0 7px; background: var(--background-color); color: gray; cursor: pointer; box-shadow: -2px 0 8px rgba(0, 0, 0, 0.08); transition: color .12s, background .12s; }
.rd-reveal:hover { background: var(--bg3); color: var(--primary-color); }
.rd-reveal :deep(svg) { transform: rotate(180deg); }
.chipFade-enter-active, .chipFade-leave-active { transition: opacity .2s ease, transform .2s ease; }
.chipFade-enter-from, .chipFade-leave-to { opacity: 0; transform: translateX(10px); }
.rd-tabs { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-bottom: 1px solid var(--calendarBorder); position: relative; }
.rd-tabseg { display: inline-flex; gap: 4px; }
.rd-tabbtn { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 28px; border: none; background: none; border-radius: 7px; color: gray; fill: currentColor; cursor: pointer; transition: background .12s, color .12s; }
.rd-tabbtn:hover { color: var(--primary-color); }
.rd-tabbtn.on { background: var(--bg3); color: var(--primary-color); }
.rd-side-content { flex: 1; overflow: auto; padding: 14px; }
.rd-placeholder { font-size: 13px; color: gray; text-align: center; padding: 30px 10px; }
.rd-sheet-toggle { position: absolute; right: 8px; top: 8px; border: none; background: none; cursor: pointer; color: gray; font-size: 11px; }
.rd-side.mobile { position: fixed; left: 0; right: 0; bottom: 0; width: auto; border-left: none; border-top: 1px solid var(--calendarBorder); box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.12); z-index: 40; max-height: 72vh; }
.rd-side.mobile .rd-side-inner { width: 100%; }
.rd-side.mobile .rd-tabs { padding-right: 60px; background: var(--background-color); }
.rd-side.mobile .rd-side-content { max-height: 58vh; }
.rd-label { display: block; font-size: 13px; color: gray; margin-bottom: 15px; }
.rd-req { color: #e2574c; }
.rd-err { font-size: 11px; color: #e2574c; margin-top: 3px; }
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
