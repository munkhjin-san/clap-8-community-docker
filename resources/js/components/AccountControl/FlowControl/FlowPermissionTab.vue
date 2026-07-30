<template>
    <div>
        <div class="ptabs">
            <div v-for="t in subTabs" :key="t.k" class="ptab" :class="{ on: sub === t.k }" @click="sub = t.k">{{ t.label }}</div>
        </div>
        <div v-show="sub === 'app'" class="flex flex-col gap-[14px] max-w-[860px]">
        <div class="flow-card">
            <div class="flow-card-h">アプリのアクセス権</div>
            <div class="perm-intro">
                <p class="perm-intro-lead">指定が細かい行が優先されます。</p>
                <div class="perm-tiers">
                    <span class="perm-tier">全員</span>
                    <span class="perm-tier-lt">＜</span>
                    <span class="perm-tier">役職</span>
                    <span class="perm-tier-lt">＜</span>
                    <span class="perm-tier">個人指定</span>
                </div>
                <ul class="perm-intro-list">
                    <li>個人指定の行がある人には、その行だけが適用されます（役職・全員の行は無視されます）。</li>
                    <li>行の並び順は結果に影響しません。</li>
                    <li>作成者は設定に関わらず常にアクセスできます。</li>
                </ul>
            </div>

            <div class="perm-scroll">
                <table class="perm-table">
                    <thead>
                        <tr>
                            <th class="th-subject">対象</th>
                            <th v-for="p in PERMS" :key="p.k">{{ p.l }}</th>
                            <th class="th-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in def.appPermissions" :key="i">
                            <td class="td-subject">
                                <span class="subj-tag" :class="{ special: row.subject_type === 'creator' || row.subject_type === 'everyone' }">{{ subjectLabel(row) }}</span>
                                <span v-if="overriddenPosition(row)" class="perm-warn" :title="overrideNote(row)">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.6 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                </span>
                            </td>
                            <td v-for="p in PERMS" :key="p.k" class="td-check">
                                <span class="flow-cbox" :class="{ on: (row as any)[p.k] }" @click="(row as any)[p.k] = !(row as any)[p.k]">
                                    <svg v-if="(row as any)[p.k]" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5,12 10,17 19,7"></polyline></svg>
                                </span>
                            </td>
                            <td class="td-actions">
                                <button v-if="row.subject_type !== 'creator'" @click="def.appPermissions.splice(i, 1)" title="削除"><CloseIcon size="9" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="overrides.length" class="perm-notice">
                <span class="perm-notice-ico">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.6 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <div class="perm-notice-body">
                    <div class="perm-notice-h">個人指定が役職より優先されます</div>
                    <div v-for="o in overrides" :key="o.user" class="perm-notice-item">
                        {{ o.user }} … 役職「{{ o.position }}」の行は適用されず、この人には個人指定の行だけが有効になります。
                    </div>
                </div>
            </div>

            <button class="flow-ghost-btn flow-ghost-btn-lg mt-[14px]" @click="openAdd">＋ 権限を追加</button>
        </div>
        </div>
        <Modal v-if="addOpen" persist @close="closeAdd">
            <template #title>権限を追加</template>
            <template #content>
                <div class="flex items-center gap-[8px] flex-wrap">
                    <div class="flow-seg">
                        <button :class="{ on: addType === 'user' }" @click="addType = 'user'">ユーザー</button>
                        <button :class="{ on: addType === 'position' }" @click="addType = 'position'">役職</button>
                        <button :class="{ on: addType === 'everyone' }" @click="addType = 'everyone'">全員</button>
                    </div>
                    <div class="flow-seg" v-if="def.project_record_id">
                        <button :class="{ on: addType === 'project_member' }" @click="addType = 'project_member'">メンバー</button>
                        <button :class="{ on: addType === 'project_manager' }" @click="addType = 'project_manager'">PM</button>
                        <button :class="{ on: addType === 'project_director' }" @click="addType = 'project_director'">ディレクター</button>
                    </div>
                </div>
                <div v-if="addType === 'user' || addType === 'position'" class="perm-picker mt-[10px]">
                    <MemberSelector v-if="addType === 'user'" :multiple="true" :options="(users as any)" v-model="userPicks" compact place-holder="ユーザーを検索（複数選択可）" />
                    <ItemSelector v-else :multiple="true" :options="positions" v-model="positionPicks" label="name" :clearable="true" :close-on-select="false" place-holder="役職を検索（複数選択可）" />
                </div>
                <div class="flow-perm-flags mt-[12px]">
                    <span v-for="p in PERMS" :key="p.k" class="flow-perm-flag" @click="addFlags[p.k] = !addFlags[p.k]">
                        <span class="flow-cbox" :class="{ on: addFlags[p.k] }">
                            <svg v-if="addFlags[p.k]" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5,12 10,17 19,7"></polyline></svg>
                        </span>{{ p.l }}
                    </span>
                </div>
                <div class="perm-modal-actions">
                    <button class="flow-ghost-btn flow-ghost-btn-lg" @click="closeAdd">キャンセル</button>
                    <!-- 適用, not 追加: the dialog is already titled 権限を追加, and this button's job is to
                         apply what has been composed to the list behind it -->
                    <button class="flow-ghost-btn flow-ghost-btn-lg perm-add-primary" :disabled="!hasPicks" @click="addRow">適用</button>
                </div>
            </template>
        </Modal>

        <FlowRecordPermEditor v-show="sub === 'record'" :def="def" :users="users" :positions="positions" :reveal="() => (sub = 'record')" />
        <FlowFieldPermEditor v-show="sub === 'field'" :def="def" :users="users" :positions="positions" :reveal="() => (sub = 'field')" />
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { ref, reactive, computed, watch } from 'vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import MemberSelector from '@/components/Form/MemberSelector.vue'
import ItemSelector from '@/components/Form/ItemSelector.vue'
import Modal from '@/components/Global/Modal.vue'
import FlowRecordPermEditor from './FlowRecordPermEditor.vue'
import FlowFieldPermEditor from './FlowFieldPermEditor.vue'
import type { BuilderDefinition, AppPermissionRow, FlowSubjectType, FlowOptionUser, FlowOptionPosition } from '@/types/flow'

const props = defineProps<{
    def: BuilderDefinition
    users: FlowOptionUser[]
    positions: FlowOptionPosition[]
}>()

const sub = ref<'app' | 'record' | 'field'>('app')
const subTabs = [
    { k: 'app' as const, label: 'アプリ' },
    { k: 'record' as const, label: 'レコード' },
    { k: 'field' as const, label: 'フィールド' },
]

const PERMS = [
    { k: 'can_view', l: '閲覧' },
    { k: 'can_add', l: '追加' },
    { k: 'can_edit', l: '編集' },
    { k: 'can_delete', l: '削除' },
    { k: 'can_manage', l: '管理' },
    { k: 'can_import', l: '取込' },
    { k: 'can_export', l: '書出' },
    { k: 'can_bulk', l: '一括処理' },
] as const

const addType = ref<FlowSubjectType>('user')
const NO_TARGET_SUBJECTS = ['everyone', 'project_member', 'project_manager', 'project_director']

// Multi-select pickers: users → User objects, positions → ids. One 追加 creates a row per pick,
// all sharing the flag template below (bulk-add without changing the 1-subject-per-row skeleton).
const userPicks = ref<any[]>([])
const positionPicks = ref<number[]>([])
const addFlags = reactive({
    can_view: true, can_add: false, can_edit: false, can_delete: false,
    can_manage: false, can_import: false, can_export: false, can_bulk: false,
})
/**
 * The add form lives in a modal, which is the whole point: 保存 sits behind the overlay, so a row
 * that has been composed but not added can no longer be left on screen and silently dropped by a
 * save. Closing the modal is the only way out, and that is an explicit discard.
 */
const addOpen = ref(false)
const openAdd = () => {
    addType.value = 'user'
    userPicks.value = []
    positionPicks.value = []
    PERMS.forEach((p) => { addFlags[p.k] = false })
    addOpen.value = true
}
const closeAdd = () => { addOpen.value = false }

const hasPicks = computed(() =>
    addType.value === 'user' ? userPicks.value.length > 0
    : addType.value === 'position' ? positionPicks.value.length > 0
    : true)
// switching type clears pending picks so nothing leaks across pickers
watch(addType, () => { userPicks.value = []; positionPicks.value = [] })

const SUBJECT_LABELS: Record<string, string> = {
    creator: '作成者',
    everyone: '全員',
    project_member: 'プロジェクトメンバー',
    project_manager: 'プロジェクトマネージャー',
    project_director: 'ディレクター',
}
const subjectLabel = (row: AppPermissionRow): string => {
    if (SUBJECT_LABELS[row.subject_type]) return SUBJECT_LABELS[row.subject_type]
    if (row.subject_type === 'user') return props.users.find((u) => u.id === row.subject_id)?.name ?? `ユーザー#${row.subject_id}`
    if (row.subject_type === 'position') return props.positions.find((p) => p.id === row.subject_id)?.name ?? `役職#${row.subject_id}`
    return row.subject_type
}

/**
 * An individual row REPLACES the person's 役職 row (個人指定 is the more specific tier), so if the
 * two disagree the 役職 boxes silently stop applying to them. Surface that rather than let someone
 * discover it from a support ticket.
 */
const positionRowIds = computed(() => new Set(
    props.def.appPermissions
        .filter((r) => r.subject_type === 'position' && r.subject_id != null)
        .map((r) => Number(r.subject_id)),
))
const overriddenPosition = (row: any): string | null => {
    if (row.subject_type !== 'user' || row.subject_id == null) return null
    const u = props.users.find((x) => Number(x.id) === Number(row.subject_id))
    const pid = u?.position_id
    if (!pid || !positionRowIds.value.has(Number(pid))) return null

    return props.positions.find((p) => Number(p.id) === Number(pid))?.name ?? '役職'
}
const overrideNote = (row: any) =>
    `このユーザーは役職「${overriddenPosition(row)}」の行にも該当しますが、個人指定のほうが優先されます。`
    + '役職の行の権限は適用されず、この行のチェックだけが有効になります。'

/** Same information as the row icon, spelled out below the table — the icon alone is hover-only. */
const overrides = computed(() =>
    props.def.appPermissions
        .map((row) => ({ row, position: overriddenPosition(row) }))
        .filter((x) => x.position)
        .map((x) => ({ user: subjectLabel(x.row), position: x.position as string })),
)

// 全員 matches everyone, so it must sit at the bottom (catch-all). Keep it there after adds.
const pinEveryoneLast = () => {
    const arr = props.def.appPermissions
    const others = arr.filter((r) => r.subject_type !== 'everyone')
    const everyone = arr.filter((r) => r.subject_type === 'everyone')
    arr.splice(0, arr.length, ...others, ...everyone)
}
// No「無効」marker any more: permissions are the union of every matching row, so a 全員 row above
// no longer swallows the ones below it — each row still contributes what it grants.

const emptyPerms = () => ({
    can_view: false, can_add: false, can_edit: false, can_delete: false,
    can_manage: false, can_import: false, can_export: false, can_bulk: false,
})

const addRow = () => {
    const type = addType.value
    const exists = (t: string, id: number | null) => props.def.appPermissions.some((r) => r.subject_type === t && r.subject_id === id)
    const newRow = (id: number | null) => ({ subject_type: type, subject_id: id, ...emptyPerms(), ...addFlags })

    if (NO_TARGET_SUBJECTS.includes(type)) {
        if (exists(type, null)) return
        props.def.appPermissions.push(newRow(null))
    } else if (type === 'user') {
        userPicks.value.forEach((u) => { if (u && !exists('user', u.id)) props.def.appPermissions.push(newRow(u.id)) })
        userPicks.value = []
    } else if (type === 'position') {
        positionPicks.value.forEach((id) => { if (!exists('position', id)) props.def.appPermissions.push(newRow(id)) })
        positionPicks.value = []
    }
    pinEveryoneLast()
    closeAdd()
}
</script>

<style scoped>
.ptabs { display: flex; gap: 2px; margin-bottom: 14px; border-bottom: 1px solid var(--calendarBorder); }
.ptab { padding: 8px 14px; font-size: 13px; color: gray; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
.ptab.on { color: var(--primary-color); border-bottom-color: var(--primary-color); font-weight: 500; }
.flow-card { border-radius: 12px; padding: 16px 18px; }
.flow-card-h { font-size: 14px; font-weight: 500; margin-bottom: 4px; }
.perm-scroll { overflow-x: auto; }
.perm-table { border-collapse: collapse; width: 100%; min-width: 620px; }
.perm-table th { font-size: 12px; color: gray; font-weight: 500; padding: 6px 8px; border-bottom: 1px solid var(--calendarBorder); text-align: center; }
.th-subject { text-align: left; min-width: 160px; }
.th-actions { width: 80px; }
.td-subject { padding: 8px; border-bottom: 1px solid var(--calendarBorder); }
.subj-tag { font-size: 13px; }
.subj-tag.special { color: var(--primary-color); font-weight: 500; }
.td-check { text-align: center; padding: 8px; border-bottom: 1px solid var(--calendarBorder); }
.td-actions { text-align: right; padding: 8px; border-bottom: 1px solid var(--calendarBorder); white-space: nowrap; }
.td-actions button { border: none; background: none; color: gray; cursor: pointer; font-size: 12px; padding: 2px; }
.td-actions button:disabled { opacity: 0.25; cursor: default; }
.perm-add { padding: 12px; border-radius: 8px; background: var(--bg3); }
/* staged-but-not-added: make it visible here so the block at 保存 is the safety net, not the notice */
.perm-modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--calendarBorder); }
.perm-add-primary:not(:disabled) { border-color: var(--primary-color); }
.perm-picker { width: 100%; max-width: 520px; }
/* ItemSelector has no compact variant — match the thin, rounded inline-input look (like MemberSelector compact) */
.perm-picker :deep(.item-selector-shell) { border: 1px solid var(--formBorder); border-radius: 6px; }
.perm-picker :deep(.one-selector .v-field__input) { min-height: 38px; padding-top: 2px; padding-bottom: 2px; }
/* the rule used to be one dense run of text; split into a lead, the hierarchy, and its consequences */
.perm-intro { margin-bottom: 14px; }
.perm-intro-lead { font-size: 12px; color: var(--primary-color); line-height: 1.7; margin: 0; }
.perm-tiers { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; margin: 9px 0; }
.perm-tier { font-size: 11.5px; color: var(--primary-color); background: var(--bg3); border: 1px solid var(--calendarBorder); border-radius: 6px; padding: 3px 10px; }
.perm-tier-lt { font-size: 11px; color: gray; }
.perm-intro-list { margin: 0; padding-left: 1.1em; }
.perm-intro-list li { font-size: 12px; color: gray; line-height: 1.8; }
.perm-warn { display: inline-flex; margin-left: 6px; color: #d97706; vertical-align: middle; cursor: help; }
.perm-notice { display: flex; gap: 9px; margin-top: 12px; padding: 10px 12px; background: rgba(217, 119, 6, 0.08); border: 1px solid rgba(217, 119, 6, 0.28); border-radius: 8px; }
.perm-notice-ico { color: #d97706; flex-shrink: 0; display: flex; margin-top: 1px; }
.perm-notice-body { min-width: 0; }
.perm-notice-h { font-size: 12px; color: var(--primary-color); margin-bottom: 3px; }
.perm-notice-item { font-size: 12px; color: gray; line-height: 1.6; }
</style>
