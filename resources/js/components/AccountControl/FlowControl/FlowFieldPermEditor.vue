<template>
    <div class="flex flex-col gap-[12px] max-w-[900px]">
        <p class="text-[12px] text-gray-500">
            フィールドごとに閲覧・編集できる対象を指定します。未設定のフィールドは全員が閲覧・編集できます（上位権限の範囲内）。
        </p>
        <div v-for="f in fields" :key="f.id" class="flow-card">
            <div class="flex items-center gap-[6px] mb-[8px]">
                <FlowFieldIcon :type="f.input_type" :size="14" />
                <span class="font-medium text-[13px]">{{ f.label }}</span>
            </div>
            <div v-for="(row, i) in rowsFor(f.id!)" :key="i" class="prow">
                <span class="subj">{{ subjectLabel(row) }}</span>
                <span v-for="p in PERMS" :key="p.k" class="perm-flag" @click="(row as any)[p.k] = !(row as any)[p.k]">
                    <span class="cbox" :class="{ on: (row as any)[p.k] }">
                        <svg v-if="(row as any)[p.k]" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5,12 10,17 19,7"></polyline></svg>
                    </span>{{ p.l }}
                </span>
                <button class="rm" @click="remove(row)"><CloseIcon size="9" /></button>
            </div>
            <div class="add-panel">
                <div class="addbar">
                    <div class="seg">
                        <button :class="{ on: typeOf(f.id!) === 'user' }" @click="addType[f.id!] = 'user'">ユーザー</button>
                        <button :class="{ on: typeOf(f.id!) === 'position' }" @click="addType[f.id!] = 'position'">役職</button>
                        <button :class="{ on: typeOf(f.id!) === 'everyone' }" @click="addType[f.id!] = 'everyone'">全員</button>
                    </div>
                    <div class="seg" v-if="def.project_record_id">
                        <button :class="{ on: typeOf(f.id!) === 'project_member' }" @click="addType[f.id!] = 'project_member'">メンバー</button>
                        <button :class="{ on: typeOf(f.id!) === 'project_manager' }" @click="addType[f.id!] = 'project_manager'">PM</button>
                        <button :class="{ on: typeOf(f.id!) === 'project_director' }" @click="addType[f.id!] = 'project_director'">ディレクター</button>
                    </div>
                </div>

                <div v-if="typeOf(f.id!) === 'user'" class="rec-sel mt-[8px]">
                    <MemberSelector :multiple="true" :options="(users as any)" v-model="userPicks[f.id!]" compact place-holder="ユーザーを検索（複数可）" />
                </div>
                <div v-else-if="typeOf(f.id!) === 'position'" class="rec-sel mt-[8px]">
                    <ItemSelector :multiple="true" :options="positions" :reduce="(o: any) => o.id" label="name" v-model="posPicks[f.id!]" :clearable="true" :close-on-select="false" place-holder="役職を検索（複数可）" />
                </div>

                <div class="flags-row mt-[8px]">
                    <div class="perm-flags">
                        <span v-for="p in PERMS" :key="p.k" class="perm-flag" @click="(flagsFor(f.id!) as any)[p.k] = !(flagsFor(f.id!) as any)[p.k]">
                            <span class="cbox" :class="{ on: (flagsFor(f.id!) as any)[p.k] }">
                                <svg v-if="(flagsFor(f.id!) as any)[p.k]" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5,12 10,17 19,7"></polyline></svg>
                            </span>{{ p.l }}
                        </span>
                    </div>
                    <button class="flow-ghost-btn ml-auto" :disabled="addDisabled(f.id!)" @click="add(f.id!)">＋ 追加</button>
                </div>
            </div>
        </div>
        <p v-if="!fields.length" class="text-[12px] text-gray-400">先に「フォーム」タブで項目を追加してください。</p>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import MemberSelector from '@/components/Form/MemberSelector.vue'
import ItemSelector from '@/components/Form/ItemSelector.vue'
import FlowFieldIcon from './FlowFieldIcon.vue'
import { subjectLabelFor } from '@/utils/flowSubject'
import type { BuilderDefinition, FieldPermRow, FlowOptionUser, FlowOptionPosition } from '@/types/flow'

const props = defineProps<{ def: BuilderDefinition; users: FlowOptionUser[]; positions: FlowOptionPosition[] }>()

const PERMS = [
    { k: 'can_view', l: '閲覧' },
    { k: 'can_edit', l: '編集' },
] as const

const fields = computed(() => props.def.fields.filter((f) => f.input_type !== 'heading'))
const addType = reactive<Record<number, 'user' | 'position' | 'everyone' | 'project_member' | 'project_manager' | 'project_director'>>({})
const typeOf = (id: number) => addType[id] ?? 'user'
const NO_TARGET = ['everyone', 'project_member', 'project_manager', 'project_director']

// Per-field multi-select picks + a shared view/edit template (bulk-add, like app/record levels).
const userPicks = reactive<Record<number, any[]>>({})
const posPicks = reactive<Record<number, number[]>>({})
const flagsMap = reactive<Record<number, { can_view: boolean; can_edit: boolean }>>({})
const flagsFor = (id: number) => {
    if (!flagsMap[id]) flagsMap[id] = { can_view: true, can_edit: true }
    return flagsMap[id]
}
const addDisabled = (id: number) => {
    const t = typeOf(id)
    if (t === 'user') return (userPicks[id]?.length ?? 0) === 0
    if (t === 'position') return (posPicks[id]?.length ?? 0) === 0
    return false
}

const rowsFor = (fieldId: number) => props.def.fieldPermissions.filter((r) => r.field_id === fieldId)
const subjectLabel = (row: FieldPermRow) => subjectLabelFor(row.subject_type, row.subject_id, props.users, props.positions)

const add = (fieldId: number) => {
    const type = typeOf(fieldId)
    const flags = flagsFor(fieldId)
    const exists = (id: number | null) => props.def.fieldPermissions.some((r) => r.field_id === fieldId && r.subject_type === type && r.subject_id === id)
    const mk = (id: number | null) => ({ field_id: fieldId, subject_type: type, subject_id: id, can_view: flags.can_view, can_edit: flags.can_edit })

    if (NO_TARGET.includes(type)) {
        if (props.def.fieldPermissions.some((r) => r.field_id === fieldId && r.subject_type === type)) return
        props.def.fieldPermissions.push(mk(null))
    } else if (type === 'user') {
        ;(userPicks[fieldId] ?? []).forEach((u: any) => { if (u && !exists(u.id)) props.def.fieldPermissions.push(mk(u.id)) })
        userPicks[fieldId] = []
    } else if (type === 'position') {
        ;(posPicks[fieldId] ?? []).forEach((id) => { if (!exists(id)) props.def.fieldPermissions.push(mk(id)) })
        posPicks[fieldId] = []
    }
}
const remove = (row: FieldPermRow) => {
    const i = props.def.fieldPermissions.indexOf(row)
    if (i >= 0) props.def.fieldPermissions.splice(i, 1)
}
</script>

<style scoped>
.flow-card { background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 12px 14px; }
.prow { display: flex; align-items: center; gap: 14px; padding: 6px 0; border-bottom: 1px solid var(--calendarBorder); }
.subj { font-size: 13px; flex: 1; }
.perm-flags { display: flex; flex-wrap: wrap; gap: 12px; }
.perm-flag { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--primary-color); cursor: pointer; user-select: none; }
.cbox { width: 20px; height: 20px; border: 1px solid var(--formBorder); border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; box-sizing: border-box !important; flex: none; }
.cbox.on { background: var(--primary-button, var(--primary-color)); border-color: var(--primary-button, var(--primary-color)); fill: #fff; }
.rm { border: none; background: none; color: gray; cursor: pointer; padding: 3px; }
.add-panel { margin-top: 10px; background: var(--bg3); border-radius: 8px; padding: 10px; box-sizing: border-box !important; }
.addbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.flags-row { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.rec-sel { max-width: 420px; box-sizing: border-box !important; }
.rec-sel :deep(.item-selector-shell) { border: 1px solid var(--formBorder) !important; border-radius: 6px !important; box-sizing: border-box !important; }
.rec-sel :deep(.one-selector .v-field__input) { min-height: 38px; padding-top: 2px; padding-bottom: 2px; }
.seg { display: inline-flex; border: 1px solid var(--calendarBorder); border-radius: 6px; overflow: hidden; }
.seg button { border: none; background: var(--background-color); padding: 5px 10px; font-size: 12px; color: gray; cursor: pointer; border-right: 1px solid var(--calendarBorder); }
.seg button:last-child { border-right: none; }
.seg button.on { background: var(--bg3); color: var(--primary-color); font-weight: 500; }
.flow-ghost-btn { background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 6px; padding: 6px 12px; font-size: 12px; cursor: pointer; }
.flow-ghost-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.ml-auto { margin-left: auto; }
</style>
