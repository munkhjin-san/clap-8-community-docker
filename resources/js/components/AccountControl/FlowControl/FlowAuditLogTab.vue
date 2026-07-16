<template>
    <div class="flow-audit-tab">
        

        <div class="al-toolbar">
            <select v-model="actionFilter" class="custom-a-input !box-border al-filter" @change="reload">
                <option value="">すべての操作</option>
                <option v-for="a in ACTIONS" :key="a.key" :value="a.key">{{ a.label }}</option>
            </select>
            <span class="al-total">{{ total }}件</span>
        </div>

        <div class="al-list">
            <div v-if="loading" class="al-empty">読み込み中…</div>
            <div v-else-if="!logs.length" class="al-empty">ログがありません。</div>
            <template v-else>
                <div v-for="l in logs" :key="l.id" class="al-row">
                    <div class="al-main" @click="toggle(l)">
                        <span class="al-time">{{ fmt(l.created_at) }}</span>
                        <span class="al-user">{{ l.user?.name ?? '—' }}</span>
                        <span class="al-badge" :class="l.action">{{ actionLabel(l.action) }}</span>
                        <span class="al-detail">{{ summary(l) }}</span>
                        <button v-if="l.action === 'csv_export'" class="al-dl" @click.stop="download(l)">ダウンロード</button>
                        <span v-if="l.action === 'settings_change'" class="al-caret" :class="{ open: expanded === l.id }">▾</span>
                    </div>
                    <div v-if="l.action === 'settings_change' && expanded === l.id" class="al-diff">
                        <div v-for="(d, concern) in (l.meta?.diff ?? {})" :key="concern" class="al-concern">
                            <div class="al-concern-title">{{ concernLabel(String(concern)) }}</div>

                            <!-- general: a flat {field: {old, new}} map, not the row-based shape below -->
                            <div v-if="concern === 'general'" class="al-sub">
                                <div v-for="(fv, field) in d" :key="String(field)" class="al-field-diff">
                                    <span class="al-field-name">{{ field }}</span>
                                    <span class="al-old">{{ short(fv.old) }}</span>
                                    <span class="al-arrow">→</span>
                                    <span class="al-new">{{ short(fv.new) }}</span>
                                </div>
                            </div>
                            <template v-else>
                                <div v-if="d.changed === true" class="al-changed-flag">変更あり</div>
                                <ul v-if="d.added?.length" class="al-sub">
                                    <li v-for="(row, i) in d.added" :key="'a'+i">＋ {{ rowLabel(row) }}</li>
                                </ul>
                                <ul v-if="d.removed?.length" class="al-sub">
                                    <li v-for="(row, i) in d.removed" :key="'r'+i">－ {{ rowLabel(row) }}</li>
                                </ul>
                                <div v-if="d.changed && typeof d.changed === 'object'" class="al-sub">
                                    <div v-for="(fields, key) in d.changed" :key="'c'+key" class="al-changed-row">
                                        <div class="al-changed-key">#{{ key }}</div>
                                        <div v-for="(fv, field) in fields" :key="String(field)" class="al-field-diff">
                                            <span class="al-field-name">{{ field }}</span>
                                            <span class="al-old">{{ short(fv.old) }}</span>
                                            <span class="al-arrow">→</span>
                                            <span class="al-new">{{ short(fv.new) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div v-if="pageCount > 1" class="al-pager">
            <PostSearchPager :possiblePage="pageCount" :activePath="page" @setNavi="onNavi" @setActivePage="setPage" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useApi } from '@/composables/api'
import PostSearchPager from '@/components/Post/PostSearchPager.vue'
import type { BuilderDefinition, FlowAuditAction, FlowAuditLogEntry } from '@/types/flow'

const props = defineProps<{ def: BuilderDefinition }>()
const api = useApi()

const ACTIONS: { key: FlowAuditAction; label: string }[] = [
    { key: 'record_view', label: 'レコード閲覧' },
    { key: 'csv_export', label: 'CSV出力' },
    { key: 'settings_change', label: '設定変更' },
    { key: 'file_download', label: 'ファイルダウンロード' },
]
const actionLabel = (a: FlowAuditAction) => ACTIONS.find((x) => x.key === a)?.label ?? a

const CONCERN_LABELS: Record<string, string> = {
    general: '基本情報',
    fields: 'フォーム',
    statuses: 'フロー設定（ステータス）',
    status_actions: 'フロー設定（アクション）',
    app_permissions: 'アクセス権（アプリ）',
    field_permissions: 'アクセス権（フィールド）',
    record_permissions: 'アクセス権（レコード）',
    views: 'ビュー',
    tools: 'ツール',
}
const concernLabel = (k: string) => CONCERN_LABELS[k] ?? k

const PER_PAGE = 30
const loading = ref(true)
const logs = ref<FlowAuditLogEntry[]>([])
const total = ref(0)
const page = ref(1)
const actionFilter = ref<FlowAuditAction | ''>('')
const expanded = ref<number | null>(null)
const pageCount = computed(() => Math.max(1, Math.ceil(total.value / PER_PAGE)))

const toggle = (l: FlowAuditLogEntry) => {
    if (l.action !== 'settings_change') return
    expanded.value = expanded.value === l.id ? null : l.id
}

const load = async () => {
    if (!props.def.id) return
    loading.value = true
    try {
        const params = new URLSearchParams({ page: String(page.value), per_page: String(PER_PAGE) })
        if (actionFilter.value) params.set('action', actionFilter.value)
        const data = await api.get(`/flow_audit_logs/${props.def.id}?${params.toString()}`) as { logs: FlowAuditLogEntry[]; total: number } | null
        if (data) {
            logs.value = data.logs ?? []
            total.value = data.total ?? 0
        }
    } finally {
        loading.value = false
    }
}
const reload = () => { page.value = 1; load() }
const setPage = (n: number) => { page.value = Math.min(pageCount.value, Math.max(1, n)); load() }
const onNavi = (dir: number) => setPage(page.value + dir)

const download = (l: FlowAuditLogEntry) => { window.location.href = `/flow_audit_log/${l.id}/download` }

const fmt = (v: string) => {
    const d = new Date(v)
    if (isNaN(d.getTime())) return v
    const p = (n: number) => String(n).padStart(2, '0')
    return `${d.getFullYear()}/${p(d.getMonth() + 1)}/${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`
}
const short = (v: any) => {
    if (v === null || v === undefined || v === '') return '（空）'
    const s = typeof v === 'object' ? JSON.stringify(v) : String(v)
    return s.length > 40 ? s.slice(0, 40) + '…' : s
}
const rowLabel = (row: any) => row?.label ?? row?.name ?? row?.key ?? `#${row?.id ?? '?'}`

const summary = (l: FlowAuditLogEntry) => {
    const m = l.meta ?? {}
    switch (l.action) {
        case 'record_view':
            return l.record ? `レコード #${l.record.record_number}` : ''
        case 'csv_export': {
            const scope = m.scope === 'table' ? 'テーブルのみ' : 'すべての項目'
            const enc = m.encoding === 'sjis' ? 'Shift-JIS' : 'UTF-8'
            return `${m.filename ?? ''}（${scope} / ${enc} / ${m.row_count ?? 0}行）`
        }
        case 'settings_change': {
            const keys = Object.keys(m.diff ?? {}).map(concernLabel)
            return keys.length ? keys.join('、') : '変更なし'
        }
        case 'file_download':
            return `${l.record ? `レコード #${l.record.record_number} · ` : ''}${m.file_name ?? ''}`
        default:
            return ''
    }
}

onMounted(load)
</script>

<style scoped>
.flow-audit-tab { display: flex; flex-direction: column; gap: 14px; max-width: 920px; }
.al-intro { background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 14px 16px; }
.al-intro b { font-size: 14px; }
.al-intro p { font-size: 12px; color: gray; margin: 4px 0 0; line-height: 1.6; }
.al-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.al-filter { min-width: 160px; }
.al-total { font-size: 12px; color: gray; }
.al-list { display: flex; flex-direction: column; gap: 6px; }
.al-empty { font-size: 12px; color: gray; padding: 24px; text-align: center; border: 1.5px dashed var(--formBorder); border-radius: 10px; }
.al-row { border: 1px solid var(--calendarBorder); border-radius: 10px; background: var(--background-color); overflow: hidden; }
.al-main { display: flex; align-items: center; gap: 12px; padding: 10px 14px; cursor: default; }
.al-time { font-size: 11.5px; color: gray; white-space: nowrap; font-variant-numeric: tabular-nums; }
.al-user { font-size: 12.5px; font-weight: 600; white-space: nowrap; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
.al-badge { font-size: 11px; padding: 3px 9px; border-radius: 20px; background: var(--bg3); color: var(--primary-color); white-space: nowrap; }
.al-detail { flex: 1; min-width: 0; font-size: 12px; color: gray; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.al-dl { border: 1px solid var(--formBorder); background: none; border-radius: 6px; padding: 4px 10px; font-size: 11px; color: var(--primary-color); cursor: pointer; white-space: nowrap; }
.al-dl:hover { background: var(--bg3); }
.al-caret { font-size: 11px; color: gray; cursor: pointer; transition: transform .12s; }
.al-caret.open { transform: rotate(180deg); }
.al-diff { border-top: 1px solid var(--calendarBorder); padding: 10px 14px; display: flex; flex-direction: column; gap: 10px; }
.al-concern-title { font-size: 12px; font-weight: 600; margin-bottom: 4px; }
.al-changed-flag { font-size: 11.5px; color: gray; }
.al-sub { font-size: 11.5px; color: gray; display: flex; flex-direction: column; gap: 3px; padding-left: 4px; }
.al-changed-row { border-left: 2px solid var(--formBorder); padding-left: 8px; margin-bottom: 4px; }
.al-changed-key { font-size: 11px; color: gray; margin-bottom: 2px; }
.al-field-diff { display: flex; align-items: center; gap: 6px; font-size: 11.5px; }
.al-field-name { color: var(--primary-color); min-width: 90px; }
.al-old { color: tomato; text-decoration: line-through; }
.al-new { color: seagreen; }
.al-arrow { color: gray; }
.al-pager { margin-top: 4px; }
</style>
