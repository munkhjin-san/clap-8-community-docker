<template>
    <div>
        <div class="ptabs">
            <div v-for="t in subTabs" :key="t.k" class="ptab" :class="{ on: sub === t.k }" @click="sub = t.k">{{ t.label }}</div>
        </div>
        <div v-show="sub === 'app'" class="flex flex-col gap-[14px] max-w-[860px]">
        <div class="flow-card">
            <div class="flow-card-h">アプリのアクセス権</div>
            <p class="text-[12px] text-gray-500 mb-[12px]">
                上の行から順に判定され、最初に一致した行の権限が適用されます。作成者は常にアクセスできます。
            </p>

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
                        <tr v-for="(row, i) in def.appPermissions" :key="i" :class="{ 'perm-row-shadowed': isShadowed(i) }">
                            <td class="td-subject">
                                <span class="subj-tag" :class="{ special: row.subject_type === 'creator' || row.subject_type === 'everyone' }">{{ subjectLabel(row) }}</span>
                                <span v-if="isShadowed(i)" class="perm-shadow-note" title="上の「全員」ルールが先に一致するため、この行は適用されません。">無効</span>
                            </td>
                            <td v-for="p in PERMS" :key="p.k" class="td-check">
                                <span class="cbox" :class="{ on: (row as any)[p.k] }" @click="(row as any)[p.k] = !(row as any)[p.k]">
                                    <svg v-if="(row as any)[p.k]" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5,12 10,17 19,7"></polyline></svg>
                                </span>
                            </td>
                            <td class="td-actions">
                                <button @click="move(i, -1)" :disabled="i === 0" title="上へ">▲</button>
                                <button @click="move(i, 1)" :disabled="i === def.appPermissions.length - 1" title="下へ">▼</button>
                                <button v-if="row.subject_type !== 'creator'" @click="def.appPermissions.splice(i, 1)" title="削除"><CloseIcon size="9" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="perm-add mt-[14px]">
                <div class="flex items-center gap-[8px] flex-wrap">
                    <div class="seg">
                        <button :class="{ on: addType === 'user' }" @click="addType = 'user'">ユーザー</button>
                        <button :class="{ on: addType === 'position' }" @click="addType = 'position'">役職</button>
                        <button :class="{ on: addType === 'everyone' }" @click="addType = 'everyone'">全員</button>
                    </div>
                    <div class="seg" v-if="def.project_record_id">
                        <button :class="{ on: addType === 'project_member' }" @click="addType = 'project_member'">メンバー</button>
                        <button :class="{ on: addType === 'project_manager' }" @click="addType = 'project_manager'">PM</button>
                        <button :class="{ on: addType === 'project_director' }" @click="addType = 'project_director'">ディレクター</button>
                    </div>
                </div>
                <div v-if="addType === 'user' || addType === 'position'" class="perm-picker mt-[10px]">
                    <MemberSelector v-if="addType === 'user'" :multiple="true" :options="(users as any)" v-model="userPicks" compact place-holder="ユーザーを検索（複数選択可）" />
                    <ItemSelector v-else :multiple="true" :options="positions" v-model="positionPicks" label="name" :clearable="true" :close-on-select="false" place-holder="役職を検索（複数選択可）" />
                </div>
                <div class="flex items-center gap-[12px] mt-[10px] flex-wrap">
                    <div class="perm-flags">
                        <span v-for="p in PERMS" :key="p.k" class="perm-flag" @click="addFlags[p.k] = !addFlags[p.k]">
                            <span class="cbox" :class="{ on: addFlags[p.k] }">
                                <svg v-if="addFlags[p.k]" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5,12 10,17 19,7"></polyline></svg>
                            </span>{{ p.l }}
                        </span>
                    </div>
                    <button class="flow-ghost-btn" :disabled="!hasPicks" @click="addRow">＋ 追加</button>
                </div>
            </div>
        </div>
        </div>
        <FlowRecordPermEditor v-show="sub === 'record'" :def="def" :users="users" :positions="positions" />
        <FlowFieldPermEditor v-show="sub === 'field'" :def="def" :users="users" :positions="positions" />
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import MemberSelector from '@/components/Form/MemberSelector.vue'
import ItemSelector from '@/components/Form/ItemSelector.vue'
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

const move = (i: number, dir: number) => {
    const j = i + dir
    if (j < 0 || j >= props.def.appPermissions.length) return
    const arr = props.def.appPermissions
    ;[arr[i], arr[j]] = [arr[j], arr[i]]
}

// 全員 matches everyone, so it must sit at the bottom (catch-all). Keep it there after adds.
const pinEveryoneLast = () => {
    const arr = props.def.appPermissions
    const others = arr.filter((r) => r.subject_type !== 'everyone')
    const everyone = arr.filter((r) => r.subject_type === 'everyone')
    arr.splice(0, arr.length, ...others, ...everyone)
}
// A row is unreachable if an earlier row already matches everyone (a 全員 above it).
const firstEveryoneIndex = computed(() => props.def.appPermissions.findIndex((r) => r.subject_type === 'everyone'))
const isShadowed = (i: number) => firstEveryoneIndex.value >= 0 && i > firstEveryoneIndex.value

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
}
</script>

<style scoped>
.ptabs { display: flex; gap: 2px; margin-bottom: 14px; border-bottom: 1px solid var(--calendarBorder); }
.ptab { padding: 8px 14px; font-size: 13px; color: gray; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
.ptab.on { color: var(--primary-color); border-bottom-color: var(--primary-color); font-weight: 500; }
.flow-card { background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 12px; padding: 16px 18px; }
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
.cbox { width: 20px; height: 20px; border: 1px solid var(--formBorder); border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.cbox.on { background: var(--primary-button, var(--primary-color)); border-color: var(--primary-button, var(--primary-color)); fill: #fff; }
.td-actions { text-align: right; padding: 8px; border-bottom: 1px solid var(--calendarBorder); white-space: nowrap; }
.td-actions button { border: none; background: none; color: gray; cursor: pointer; font-size: 12px; padding: 2px; }
.td-actions button:disabled { opacity: 0.25; cursor: default; }
.perm-add { padding: 12px; border-radius: 8px; background: var(--bg3); }
.perm-picker { width: 100%; max-width: 520px; }
.perm-flags { display: flex; flex-wrap: wrap; gap: 12px; }
.perm-flag { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--primary-color); cursor: pointer; user-select: none; }
.perm-row-shadowed .subj-tag, .perm-row-shadowed .td-check { opacity: 0.4; }
.perm-shadow-note { margin-left: 8px; font-size: 10.5px; font-weight: 600; color: #d97706; background: rgba(217, 119, 6, 0.12); border-radius: 8px; padding: 1px 7px; }
/* ItemSelector has no compact variant — match the thin, rounded inline-input look (like MemberSelector compact) */
.perm-picker :deep(.item-selector-shell) { border: 1px solid var(--formBorder); border-radius: 6px; }
.perm-picker :deep(.one-selector .v-field__input) { min-height: 38px; padding-top: 2px; padding-bottom: 2px; }
.seg { display: inline-flex; border: 1px solid var(--calendarBorder); border-radius: 6px; overflow: hidden; }
.seg button { border: none; background: var(--background-color); padding: 6px 11px; font-size: 12px; color: gray; cursor: pointer; border-right: 1px solid var(--calendarBorder); }
.seg button:last-child { border-right: none; }
.seg button.on { background: var(--bg3); color: var(--primary-color); font-weight: 500; }
.flow-ghost-btn { background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 6px; padding: 7px 14px; font-size: 13px; cursor: pointer; }
.flow-ghost-btn:disabled { opacity: 0.4; cursor: default; }
</style>
