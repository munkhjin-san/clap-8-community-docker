<template>
    <div class="admin-window">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div class="flow-builder-bar">
            <div class="flex items-center gap-[10px] min-w-0 flex-wrap">
                <div class="flow-back" @click="back"><Back size="14" /></div>
                <button v-if="!def.id && auth.hasPrivilage" class="flow-ghost-btn flow-ghost-btn-lg" @click="kintoneOpen = true">kintoneから取込</button>
            </div>
            <div class="flex items-center gap-[8px]">
                <button class="flow-ghost-btn flow-ghost-btn-lg" @click="back">キャンセル</button>
                <button class="flow-primary-btn" :disabled="saving" @click="save">保存</button>
            </div>
        </div>

        <div class="flow-tabs">
            <div v-for="t in tabs" :key="t.key" class="flow-tab" :class="{ on: tab === t.key }" @click="setTab(t.key)">{{ t.label }}</div>
        </div>

        <div class="flow-builder-body">
            <div v-show="tab === 'general'" class="flow-general">
                <div class="fg-panel">
                    <div class="fg-row">
                        <label class="fg-label">アプリ名 <span class="fg-required">*</span></label>
                        <div class="name-wrap">
                            <input
                                ref="nameInput"
                                type="text"
                                v-model="def.name"
                                placeholder="アプリ名を入力"
                                class="flow-name-input"
                                :class="{ error: nameError }"
                                @input="nameError = false"
                            >
                            <span v-if="nameError" class="name-error">アプリ名を入力してください</span>
                        </div>
                    </div>
                    <div class="fg-row">
                        <label class="fg-label">説明</label>
                        <div class="flow-desc-editor">
                            <!-- keyed on the app id so it re-inits with the loaded description after fetch -->
                            <RichEditor :key="def.id ?? 'new'" :initilaValue="def.description || ''" @content-updated="def.description = $event" />
                        </div>
                    </div>
                    <div class="fg-row">
                        <label class="fg-label">状態</label>
                        <label class="fg-toggle">
                            <span class="flow-sw" :class="{ on: def.is_active }" @click="def.is_active = !def.is_active"></span>
                            <span>{{ def.is_active ? '有効' : '停止中' }}</span>
                        </label>
                    </div>
                    <div class="fg-row">
                        <label class="fg-label">アイコン</label>
                        <div class="fg-icon">
                            <FlowAppIcon :icon-svg="def.icon_svg" :icon-image="def.icon_image" :color-id="def.color_id" :name="def.name" :size="60" />
                            <div class="fg-icon-btns">
                                <button type="button" class="flow-ghost-btn flow-ghost-btn-lg" @click="iconCropOpen = true">画像をアップロード</button>
                                <button type="button" class="flow-ghost-btn flow-ghost-btn-lg" :disabled="iconGenLoading || !def.name.trim()" @click="generateIcon">
                                    {{ iconGenLoading ? '生成中…' : '✨ AIで生成' }}
                                </button>
                                <button v-if="def.icon_svg || def.icon_image" type="button" class="flow-ghost-btn flow-ghost-btn-lg" @click="clearIcon">削除</button>
                            </div>
                        </div>
                    </div>
                    <div class="fg-row">
                        <label class="fg-label">テーマカラー</label>
                        <div class="fg-swatches">
                            <button
                                v-for="c in FLOW_COLORS"
                                :key="c.id"
                                type="button"
                                class="fg-swatch"
                                :class="{ on: def.color_id === c.id }"
                                :style="{ background: theme.dark ? c.dark : c.light }"
                                :title="c.name"
                                @click="def.color_id = c.id"
                            >
                                <svg v-if="def.color_id === c.id" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="5,13 10,18 19,6" /></svg>
                            </button>
                        </div>
                    </div>
                    <div v-if="def.id && myPerms.bulk" class="fg-row fg-danger">
                        <label class="fg-label">危険な操作</label>
                        <div class="fg-danger-body">
                            <button type="button" class="fg-truncate" :disabled="truncating" @click="truncateRecords">
                                {{ truncating ? '削除中…' : '全レコードを削除' }}
                            </button>
                            <p class="fg-danger-hint">このアプリの全レコードを削除し、レコード番号を1にリセットします。元に戻せません。</p>
                        </div>
                    </div>
                </div>
            </div>
            <FlowFormTab v-show="tab === 'form'" :def="def" />
            <FlowStatusTab v-show="tab === 'status'" :def="def" :users="users" :positions="positions" />
            <FlowViewTab v-show="tab === 'view'" :def="def" :users="users" />
            <FlowToolsTab v-show="tab === 'tools'" :def="def" :users="users" />
            <FlowPermissionTab v-show="tab === 'permission'" :def="def" :users="users" :positions="positions" />
            <FlowAuditLogTab v-if="def.id && auditOpened" v-show="tab === 'audit'" :def="def" />
        </div>

        <FlowKintoneImportModal v-if="kintoneOpen" @close="kintoneOpen = false" @import="onKintoneImport" />
        <FlowAppIconCropModal v-if="iconCropOpen" @close="iconCropOpen = false" @cropped="onIconCropped" />
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { computed, onMounted, ref, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useApi } from '@/composables/api'
import { useFlowOptionsStore } from '@/store/flowOptions'
import FlowFormTab from './FlowFormTab.vue'
import FlowStatusTab from './FlowStatusTab.vue'
import FlowPermissionTab from './FlowPermissionTab.vue'
import FlowViewTab from './FlowViewTab.vue'
import FlowToolsTab from './FlowToolsTab.vue'
import FlowAuditLogTab from './FlowAuditLogTab.vue'
import Back from '@/components/Icons/Back.vue'
import FlowKintoneImportModal from './FlowKintoneImportModal.vue'
import FlowAppIcon from './FlowAppIcon.vue'
import FlowAppIconCropModal from './FlowAppIconCropModal.vue'
import RichEditor from '@/components/Global/RichEditor.vue'
import { useDialog } from '@/composables/dialog'
import { pageTitleOverride } from '@/composables/pageTitle'
import { useAuthUserStore } from '@/store/auth'
import { isLayoutType, defaultWidthFor, emptyPdfTemplate } from '@/types/flow'
import { FLOW_COLORS } from '@/utils/flowColors'
import { useTheme } from '@/store/theme'
import type {
    BuilderDefinition, BuilderStatus, BuilderView, FlowDefinitionApi, AppPermissionRow,
    FlowField, FlowInputType,
} from '@/types/flow'

const api = useApi()
const dialog = useDialog()
const theme = useTheme()
const auth = useAuthUserStore()
const route = useRoute()
const router = useRouter()
const kintoneOpen = ref(false)
const iconCropOpen = ref(false)
const iconGenLoading = ref(false)

const onIconCropped = (dataUrl: string) => {
    def.value.icon_image = dataUrl
    def.value.icon_svg = null
    iconCropOpen.value = false
}
const clearIcon = () => { def.value.icon_svg = null; def.value.icon_image = null }
const generateIcon = async () => {
    if (!def.value.name.trim()) return
    iconGenLoading.value = true
    try {
        const res = await api.post('/flow_generate_icon', { name: def.value.name, description: def.value.description ?? '' })
        if (res?.svg) { def.value.icon_svg = res.svg; def.value.icon_image = null }
    } finally {
        iconGenLoading.value = false
    }
}

const loading = ref(true)
const saving = ref(false)
const truncating = ref(false)
const myPerms = ref<Record<string, boolean>>({})
const nameError = ref(false)
const nameInput = ref<HTMLInputElement | null>(null)
type BuilderTab = 'general' | 'form' | 'status' | 'view' | 'tools' | 'permission' | 'audit'
const TAB_KEYS: BuilderTab[] = ['general', 'form', 'status', 'view', 'tools', 'permission', 'audit']
const tabFromRoute = (): BuilderTab => {
    const t = route.params.tab as string | undefined
    return TAB_KEYS.includes(t as BuilderTab) ? (t as BuilderTab) : 'general'
}
// each tab is its own route: /apps/builder/:flowId/:tab (general = no suffix)
const tab = ref<BuilderTab>(tabFromRoute())
const setTab = (key: BuilderTab) => {
    tab.value = key
    router.push({ name: 'flow-builder', params: { ...route.params, tab: key === 'general' ? undefined : key }, query: route.query })
}
watch(() => route.params.tab, () => { if (route.name === 'flow-builder') tab.value = tabFromRoute() })
// lazy-mount the audit tab: it fetches from the server (unlike the other tabs, which just edit the
// already-loaded `def`), so don't fire that request until the user actually opens it. Sticky once
// true so switching away and back doesn't re-fetch/re-mount.
const auditOpened = ref(tab.value === 'audit')
watch(tab, (t) => { if (t === 'audit') auditOpened.value = true })
// 監査ログ only makes sense once the app exists (it has nothing to show, and no id to query by, beforehand).
const tabs = computed(() => [
    { key: 'general' as const, label: '基本情報' },
    { key: 'form' as const, label: 'フォーム' },
    { key: 'status' as const, label: 'フロー設定' },
    { key: 'view' as const, label: 'ビュー' },
    { key: 'tools' as const, label: 'ツール' },
    { key: 'permission' as const, label: 'アクセス権' },
    ...(def.value.id ? [{ key: 'audit' as const, label: '監査ログ' }] : []),
])
const flowOptionsStore = useFlowOptionsStore()
const { users, positions } = storeToRefs(flowOptionsStore)

const creatorRow = (): AppPermissionRow => ({
    subject_type: 'creator', subject_id: null,
    can_view: true, can_add: true, can_edit: true, can_delete: true,
    can_manage: true, can_import: true, can_export: true, can_bulk: true,
})

let statusKeySeq = 0
const newStatusKey = () => `s_new_${Date.now()}_${statusKeySeq++}`

const newDefinition = (): BuilderDefinition => ({
    name: '',
    description: '',
    // default a new app to a color from the palette (so the icon is on-brand + a swatch is pre-selected)
    color_id: FLOW_COLORS[Math.floor(Math.random() * FLOW_COLORS.length)].id,
    icon_svg: null,
    icon_image: null,
    is_active: true,
    use_status_flow: false,
    fields: [],
    statuses: [
        { key: newStatusKey(), name: '申請中', is_initial: true, ui_x: 60, ui_y: 80, rules: {}, actions: [] },
        { key: newStatusKey(), name: '承認済', is_initial: false, ui_x: 360, ui_y: 80, rules: {}, actions: [] },
    ],
    appPermissions: [creatorRow()],
    recordPermissions: [],
    fieldPermissions: [],
    views: [defaultView()],
    tools: [],
})

const defaultView = (): BuilderView => ({
    name: 'すべて', is_default: true, columns: [], filters: [], sort: [],
})

const def = ref<BuilderDefinition>(newDefinition())
// show the edited app's name in the browser tab title
watch(() => def.value?.name, (name) => { if (name) pageTitleOverride.value = name })

// kintone import (admin/PM only, create screen): append the mapped fields into the current form to review + save.
const onKintoneImport = (preview: any) => {
    kintoneOpen.value = false

    const existing = def.value.fields
    const used = new Set(existing.map((f) => f.key))
    const uniqueKey = (code: string) => {
        const base = String(code || 'field')
        let k = base, n = 2
        while (used.has(k)) k = `${base}_${n++}`
        used.add(k)
        return k
    }
    // kintone only requires unique field CODES, not labels — but our save validation forbids
    // duplicate labels among data fields. Auto-suffix collisions so an imported app saves cleanly.
    const usedLabels = new Set(
        existing.filter((f) => !isLayoutType(f.input_type)).map((f) => (f.label ?? '').trim()).filter(Boolean),
    )
    const uniqueLabel = (label: string, inputType: string) => {
        const base = (label ?? '').trim()
        if (!base || isLayoutType(inputType as FlowInputType)) return label // layout parts hold display text, not identifiers
        let l = base, n = 2
        while (usedLabels.has(l)) l = `${base} (${n++})`
        usedLabels.add(l)
        return l
    }
    let row = existing.reduce((m, f) => Math.max(m, f.layout_row ?? 0), -1) + 1
    let order = existing.length
    const optType = (t: string) => ['select', 'radio', 'checkbox'].includes(t)
    const codeToKey: Record<string, string> = {}
    const added: FlowField[] = (preview.fields ?? []).filter((f: any) => f.supported).map((f: any) => {
        const isTable = f.mapped_type === 'table'
        const isFormula = f.mapped_type === 'formula'
        const validation: any = {}
        if (isTable) {
            validation.columns = (f.columns ?? []).map((c: any, i: number) => ({
                key: c.key || `c${i + 1}`,
                label: c.label,
                input_type: c.input_type,
                options: optType(c.input_type) ? (c.options?.length ? [...c.options] : ['選択肢1']) : null,
                required: !!c.required,
                formula: c.input_type === 'formula' ? (c.formula ?? '') : undefined,
                result_type: c.input_type === 'formula' ? (c.result_type ?? 'number') : undefined,
            }))
        }
        const key = uniqueKey(f.code)
        codeToKey[f.code] = key
        return {
            key,
            label: uniqueLabel(f.label, f.mapped_type),
            input_type: f.mapped_type,
            width: defaultWidthFor(f.mapped_type),
            is_required: !!f.required,
            options: optType(f.mapped_type) ? (f.options?.length ? [...f.options] : ['選択肢1']) : null,
            validation,
            formula: isFormula ? (f.formula ?? '') : undefined,
            result_type: isFormula ? (f.result_type ?? 'number') : undefined,
            layout_row: 0, // assigned by the packing pass below
            order_number: order++,
        } as FlowField
    })
    // if any field code got renamed for uniqueness, rewrite formula refs to the new keys.
    // handles both [code] and subtable-aggregate [table.column] references.
    if (Object.entries(codeToKey).some(([c, k]) => c !== k)) {
        const remap = (formula: string) => formula.replace(/\[([^\]]+)\]/g, (_m, ref) => {
            const dot = ref.indexOf('.')
            if (dot > 0) {
                const table = ref.slice(0, dot)
                return `[${codeToKey[table] ?? table}${ref.slice(dot)}]`
            }
            return `[${codeToKey[ref] ?? ref}]`
        })
        for (const fld of added) {
            if (fld.input_type === 'formula' && fld.formula) {
                fld.formula = remap(fld.formula)
            }
            // subtable per-row calc columns (sibling keys are stable; top-level refs may have renamed)
            for (const col of (fld.validation?.columns ?? []) as any[]) {
                if (col.input_type === 'formula' && col.formula) col.formula = remap(col.formula)
            }
        }
    }

    // pack imported fields into rows by width instead of one-per-row (rough multi-column layout)
    {
        const TARGET = 1080, GAP = 14
        let x = 0
        for (const f of added) {
            const w = isLayoutType(f.input_type) ? TARGET : f.width // layout blocks take their own row
            if (x > 0 && x + w > TARGET) { row++; x = 0 }
            f.layout_row = row
            x += w + GAP
            if (isLayoutType(f.input_type)) { row++; x = 0 } // and force a break after them
        }
    }

    // Status flow (structure only — kintone states → statuses, actions → transition buttons; assignees ignored).
    let statuses = def.value.statuses
    let useStatusFlow = def.value.use_status_flow
    const sf = preview.status_flow
    if (sf?.statuses?.length) {
        const nameToKey: Record<string, string> = {}
        statuses = sf.statuses.map((s: any) => {
            const key = newStatusKey()
            nameToKey[s.name] = key
            return { key, name: s.name, is_initial: !!s.is_initial, rules: {}, actions: [] as any[] }
        })
        ;(sf.actions ?? []).forEach((a: any) => {
            const from = statuses.find((s) => s.key === nameToKey[a.from])
            const toKey = nameToKey[a.to]
            if (from && toKey) {
                from.actions.push({ name: a.name, label: a.name, color: '#3b6df5', to_status_key: toKey, eligible: [] })
            }
        })
        // Importing a flow implies wanting it — enable it (the admin can toggle off). kintone's own enable is shown in the preview.
        useStatusFlow = true
    }

    // Reassign def (new identity) so FlowFormTab's `watch(() => props.def)` rebuilds its rows.
    def.value = {
        ...def.value,
        name: def.value.name || (preview.app?.name ?? ''),
        description: def.value.description || (preview.app?.description ?? ''),
        use_status_flow: useStatusFlow,
        statuses,
        fields: [...existing, ...added],
    }
    setTab('form')
    const flowNote = sf?.statuses?.length ? `・ステータスフロー（${sf.statuses.length}）` : ''
    dialog.toast(`${added.length}件の項目${flowNote}を取り込みました。保存前に内容をご確認ください。`)
}

// A field with no validation is stored server-side as an empty JSON array (PHP serializes an empty
// array as `[]`, not `{}`), so the API hands it back as a JS array. Binding `v-model="v.default"` in
// the inspector then sets `.default` as an expando property on that array — which JSON.stringify
// silently drops on save. Coerce to a real object on load so builder edits always persist. Nested
// table-column validation has the same hazard, so normalize those too.
const normalizeValidation = (val: any): Record<string, any> => {
    const obj = val && typeof val === 'object' && !Array.isArray(val) ? { ...val } : {}
    if (Array.isArray(obj.columns)) {
        obj.columns = obj.columns.map((c: any) => ({
            ...c,
            validation: c?.validation && typeof c.validation === 'object' && !Array.isArray(c.validation) ? c.validation : {},
        }))
    }
    return obj
}

const toBuilder = (api: FlowDefinitionApi): BuilderDefinition => {
    const idToKey: Record<number, string> = {}
    api.fields.forEach((f) => { if (f.id) idToKey[f.id] = f.key })
    // Stable builder key per persisted status id, so actions can reference from/to.
    const statusKey = (id?: number | null) => (id ? `s${id}` : newStatusKey())
    return {
        id: api.id,
        name: api.name,
        description: api.description,
        color_id: api.color_id ?? null,
        icon_svg: api.icon_svg ?? null,
        icon_image: api.icon_image ?? null,
        is_active: api.is_active ?? true,
        use_status_flow: !!api.use_status_flow,
        fields: api.fields.map((f) => ({ ...f, width: f.width ?? 260, options: f.options ?? null, validation: normalizeValidation(f.validation) })),
        statuses: api.statuses.map((s): BuilderStatus => {
            const rules: Record<string, any> = {}
            ;(s.field_rules || []).forEach((r) => {
                const key = idToKey[r.flow_field_id]
                if (key) rules[key] = r.rule
            })
            const actions = (api.status_actions ?? [])
                .filter((a) => a.flow_status_id === s.id)
                .map((a) => ({
                    id: a.id,
                    name: a.name ?? '',
                    label: a.label,
                    color: a.color ?? '#3b6df5',
                    to_status_key: a.to_status_id ? `s${a.to_status_id}` : null,
                    eligible: (a.eligible ?? []).map((e) => ({ subject_type: e.subject_type, subject_id: e.subject_id ?? null })),
                }))
            return {
                id: s.id,
                key: statusKey(s.id),
                name: s.name,
                is_initial: !!s.is_initial,
                color: s.color ?? null,
                ui_x: s.ui_x ?? null,
                ui_y: s.ui_y ?? null,
                rules,
                actions,
            }
        }),
        appPermissions: api.app_permissions && api.app_permissions.length
            ? api.app_permissions.map((r) => ({ ...r }))
            : [creatorRow()],
        recordPermissions: (api.record_permission_sets ?? []).map((s: any) => ({
            match_mode: s.match_mode ?? 'all',
            conditions: (s.conditions ?? []).map((c: any) => ({ source: c.source, field_id: c.field_id ?? null, operator: c.operator, values: c.values ?? [] })),
            grants: (s.grants ?? []).map((g: any) => ({ subject_type: g.subject_type, subject_id: g.subject_id ?? null, can_view: !!g.can_view, can_edit: !!g.can_edit, can_delete: !!g.can_delete })),
        })),
        fieldPermissions: (api.field_permissions ?? []).map((f: any) => ({ field_id: f.field_id, subject_type: f.subject_type, subject_id: f.subject_id ?? null, can_view: !!f.can_view, can_edit: !!f.can_edit })),
        views: (api.views ?? []).length
            ? (api.views ?? []).map((v) => ({
                id: v.id, name: v.name, is_default: !!v.is_default,
                columns: v.columns ?? [], filters: v.filters ?? [], sort: v.sort ?? [],
            }))
            : [defaultView()],
        tools: (api.tools ?? []).map((t: any) => ({
            id: t.id, tool_type: t.tool_type, name: t.name, is_active: !!t.is_active,
            config: t.config ?? emptyPdfTemplate(),
        })),
    }
}

// A view ref survives pruning if it's a system column ($…) or points to a field still present.
const liveRef = (ref: number | string | null | undefined): boolean => {
    if (typeof ref === 'string' && ref.startsWith('$')) return true
    return def.value.fields.some((f) => f.id != null && f.id === Number(ref))
}

const buildPayload = () => ({
    id: def.value.id,
    name: def.value.name,
    description: def.value.description,
    color_id: def.value.color_id ?? null,
    icon_svg: def.value.icon_svg ?? null,
    icon_image: def.value.icon_image ?? null,
    is_active: def.value.is_active,
    use_status_flow: def.value.use_status_flow,
    fields: def.value.fields.map((f, i) => ({ ...f, order_number: i })),
    statuses: def.value.statuses.map((s) => ({
        id: s.id,
        key: s.key,
        name: s.name,
        is_initial: s.is_initial,
        color: s.color ?? null,
        ui_x: s.ui_x ?? null,
        ui_y: s.ui_y ?? null,
        field_rules: Object.entries(s.rules).map(([field_key, rule]) => ({ field_key, rule })),
    })),
    status_actions: def.value.statuses.flatMap((s) =>
        s.actions.map((a) => ({
            id: a.id,
            from_status_key: s.key,
            to_status_key: a.to_status_key,
            name: a.name || null,
            label: a.label,
            color: a.color,
            eligible: a.eligible,
        })).filter((a) => a.to_status_key && a.label)
    ),
    app_permissions: def.value.appPermissions,
    record_permissions: def.value.recordPermissions,
    field_permissions: def.value.fieldPermissions,
    views: def.value.views.map((v) => ({
        id: v.id, name: v.name, is_default: v.is_default,
        // Drop column/filter/sort refs to fields that no longer exist (e.g. deleted since the
        // view was configured). System columns ($record_number 等) and live field ids are kept.
        columns: (v.columns ?? []).filter(liveRef),
        filters: (v.filters ?? []).filter((f) => liveRef(f.field)),
        sort: (v.sort ?? []).filter((s) => liveRef(s.field)),
    })),
    tools: def.value.tools.map((t) => ({
        id: t.id, tool_type: t.tool_type, name: t.name, is_active: t.is_active, config: t.config,
    })),
    project_record_id: def.value.project_record_id ?? null,
})

const fieldError = (): string | null => {
    const fields = def.value.fields
    const keys = new Map<string, number>()
    const labels = new Map<string, number>()
    for (const f of fields) {
        const key = (f.key ?? '').trim()
        if (!key) return `「${f.label || f.input_type}」のフィールドキーが空です。`
        keys.set(key, (keys.get(key) ?? 0) + 1)
        // Labels must be unique among data fields (layout parts hold display text, not identifiers).
        if (!isLayoutType(f.input_type)) {
            const label = (f.label ?? '').trim()
            if (label) labels.set(label, (labels.get(label) ?? 0) + 1)
        }
    }
    const dupKey = [...keys].find(([, n]) => n > 1)
    if (dupKey) return `フィールドキー「${dupKey[0]}」が重複しています。キーはアプリ内で一意にしてください。`
    const dupLabel = [...labels].find(([, n]) => n > 1)
    if (dupLabel) return `ラベル「${dupLabel[0]}」が重複しています。項目名はアプリ内で一意にしてください。`
    return null
}

const save = async () => {
    if (!def.value.name.trim()) {
        nameError.value = true
        setTab('general')
        nextTick(() => nameInput.value?.focus())
        return
    }
    const fErr = fieldError()
    if (fErr) {
        setTab('form')
        dialog.toast(fErr)
        return
    }
    saving.value = true
    try {
        const data = await api.post('/flow_definition_save', buildPayload(), { toast: '保存しました。' })
        if (data) {
            def.value = toBuilder(data as FlowDefinitionApi)
            // land inside the app itself (both edit and newly created)
            router.push({ name: 'flow-records', params: { flowId: (data as FlowDefinitionApi).id } })
        }
    } finally {
        saving.value = false
    }
}

const truncateRecords = async () => {
    if (!def.value.id) return
    if (!window.confirm(`「${def.value.name}」の全レコードを削除し、レコード番号を1にリセットします。\nこの操作は元に戻せません。実行しますか？`)) return
    truncating.value = true
    try {
        const res = await api.post(`/flow_app_truncate/${def.value.id}`, {}, { toast: '全レコードを削除しました。' })
        if (res) dialog.toast(`${res.deleted ?? 0}件のレコードを削除しました。`)
    } finally {
        truncating.value = false
    }
}

const back = () => {
    // return to wherever settings was opened from (app list / records / a record)
    if (window.history.state?.back) {
        router.back()
        return
    }
    // no in-app history (e.g. opened via a direct link) → sensible default
    if (def.value.project_record_id) {
        router.push({ name: 'custom-apps', params: { projectId: def.value.project_record_id } })
    } else {
        router.push({ name: 'flow-control' })
    }
}

onMounted(async () => {
    try {
        await flowOptionsStore.load()
        const id = route.params.flowId
        if (id) {
            const data = await api.get(`/flow_definitions/${id}`)
            if (data) { def.value = toBuilder(data as FlowDefinitionApi); myPerms.value = (data as any).my_permissions ?? {} }
        } else if (route.query.project) {
            def.value.project_record_id = Number(route.query.project)
        }
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.flow-builder-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 16px; border-bottom: 1px solid var(--calendarBorder); background: var(--background-color); flex-wrap: wrap; }
.admin-window { color: var(--primary-color); }
.flow-back { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border: 1px solid var(--formBorder); border-radius: 6px; cursor: pointer; fill: var(--primary-color); flex-shrink: 0; }
.flow-back:hover { background: var(--bg3); }
.flow-general { display: flex; justify-content: center; }
.fg-panel { width: 100%; max-width: 720px; box-sizing: border-box !important; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 22px 24px; display: flex; flex-direction: column; gap: 20px; }
.fg-row { display: flex; flex-direction: column; gap: 7px; }
.fg-danger { margin-top: 8px; padding-top: 16px; border-top: 1px dashed var(--calendarBorder); }
.fg-danger-body { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
.fg-truncate { background: tomato; color: #fff; border: none; border-radius: 6px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; }
.fg-truncate:hover { background: #e8482e; }
.fg-truncate:disabled { opacity: 0.55; cursor: not-allowed; }
.fg-danger-hint { font-size: 11.5px; color: gray; margin: 0; }
.fg-label { font-size: 12px; color: gray; font-weight: 500; }
.fg-required { color: #e24b4a; }
.fg-toggle { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--primary-color); cursor: pointer; width: fit-content; }
.fg-icon { display: flex; align-items: center; gap: 14px; }
.fg-icon-btns { display: flex; flex-wrap: wrap; gap: 8px; }
.fg-swatches { display: grid; grid-template-columns: repeat(4, 28px); gap: 8px; width: fit-content; }
.fg-swatch { width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--calendarBorder); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; color: var(--primary-color); box-sizing: border-box !important; transition: transform .1s, box-shadow .1s; padding: 0; }
.fg-swatch:hover { transform: scale(1.08); }
.fg-swatch.on { box-shadow: 0 0 0 2px var(--primary-color); }
.name-wrap { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.flow-desc-editor { width: 100%; }
.flow-desc-editor :deep(.editor-root) { border-radius: 7px; }
.flow-desc-editor :deep(.tiptap) { min-height: 120px; }
.flow-name-input { width: 100%; font-size: 14px; color: var(--primary-color); background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 7px; padding: 8px 10px; transition: border-color .15s; box-sizing: border-box !important; }
.flow-name-input::placeholder { color: gray; font-weight: 400; }
.flow-name-input:focus { outline: none; border-color: var(--primary-color); }
.flow-name-input.error { border-color: #e24b4a; background: rgba(226, 75, 74, 0.06); }
.name-error { display: flex; align-items: center; gap: 4px; font-size: 11px; color: #e24b4a; padding-left: 3px; }
.flow-tabs { display: flex; gap: 2px; border-bottom: 1px solid var(--calendarBorder); background: var(--background-color); padding: 0 16px; flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; }
.flow-tabs::-webkit-scrollbar { display: none; }
.flow-tab { padding: 11px 16px; font-size: 14px; color: gray; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; flex-shrink: 0; }
.flow-tab:hover { color: var(--primary-color); }
.flow-tab.on { color: var(--primary-color); border-bottom-color: var(--primary-color); font-weight: 500; }
.flow-builder-body { flex: 1; overflow: auto; padding: 18px; background: var(--bg3); }

@media (max-width: 640px) {
    .flow-tabs { padding: 0 10px; }
    .flow-tab { padding: 10px 10px; font-size: 12px; }
    /* clear the app's fixed bottom nav so the last content isn't hidden under it */
    .flow-builder-body { padding: 14px 12px 84px; }
}
.flow-ghost-btn-lg { white-space: nowrap; }
.flow-primary-btn { padding: 7px 18px; font-size: 13px; color: #fff; background: var(--primary-button, var(--primary-color)); border: none; border-radius: 6px; cursor: pointer; }
.flow-primary-btn:disabled { opacity: 0.5; cursor: default; }
</style>
