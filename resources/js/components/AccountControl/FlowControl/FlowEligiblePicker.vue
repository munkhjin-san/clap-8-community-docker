<template>
    <div class="elig">
        <div class="elig-h">押せる人（責任者）</div>

        <div class="elig-toggles">
            <label class="chk"><input type="checkbox" :checked="creatorChecked" @change="toggleCreator"> 作成者</label>
            <label class="chk"><input type="checkbox" :checked="pmOpen" @change="togglePm"> PM（プロジェクト責任者）</label>
        </div>

        <div v-if="pmOpen" class="elig-pm-sub">
            <label class="chk"><input type="checkbox" :checked="creatorPm" @change="toggleCreatorPm"> 作成者のPM<small>作成者が管理するPJの責任者</small></label>
            <label class="chk" :class="{ disabled: !projectFields.length }" :title="!projectFields.length ? 'フォームにプロジェクト項目を追加すると使えます' : ''">
                <input type="checkbox" :checked="fieldPm" :disabled="!projectFields.length" @change="toggleFieldPm"> 選択プロジェクトのPM<small v-if="!projectFields.length">プロジェクト項目が必要</small>
            </label>
            <select v-if="fieldPm && projectFields.length" v-model.number="fieldPmFieldId" class="custom-a-input !box-border elig-pm-select" @change="setFieldPmField">
                <option v-for="f in projectFields" :key="f.id" :value="f.id">{{ f.label }}</option>
            </select>
        </div>

        <div class="elig-pickers">
            <div class="pk">
                <span class="pk-lbl">役職で絞り込み</span>
                <ItemSelector
                    v-model="positionFilter"
                    :options="positions"
                    :multiple="true"
                    :clearable="true"
                    :close-on-select="false"
                    label="name"
                    place-holder="役職を選択"
                />
            </div>
            <div class="pk">
                <span class="pk-lbl">ユーザーを指定</span>
                <MemberSelector
                    v-model="members"
                    :options="(users as any)"
                    :multiple="true"
                    compact
                    place-holder="ユーザーを選択"
                />
            </div>
        </div>

        <p v-if="!configured" class="elig-hint">未設定 = 編集権限を持つ全員が押せます</p>
    </div>
</template>

<script setup lang="ts">
/**
 * 「押せる人」の設定UI。ステータスのアクションボタンとカスタムボタンで共有する。
 *
 * eligible 配列を直接書き換える（親のオブジェクトをそのまま持つ）ので、親はモデルの同期を
 * 気にしなくてよい。判定側も FlowService::matchesAnySubject 1本なので、両者は同じ規則で動く。
 */
import { computed, ref, watch } from 'vue'
import type { ActionSubject, FlowOptionUser, FlowOptionPosition, FlowField } from '@/types/flow'
import { eligibleIsConfigured } from '@/types/flow'
import ItemSelector from '@/components/Form/ItemSelector.vue'
import MemberSelector from '@/components/Form/MemberSelector.vue'

const props = defineProps<{
    eligible: ActionSubject[]
    users: FlowOptionUser[]
    positions: FlowOptionPosition[]
    projectFields?: FlowField[]
}>()

const projectFields = computed(() => props.projectFields ?? [])
const configured = computed(() => eligibleIsConfigured(props.eligible))

/* ---- project-scoped process (PM options) ---- */
const hasType = (t: string) => props.eligible.some((e) => e.subject_type === t)
const removeType = (t: string) => {
    const i = props.eligible.findIndex((e) => e.subject_type === t)
    if (i >= 0) props.eligible.splice(i, 1)
}
const pmOpen = ref(hasType('creator_project_manager') || hasType('field_project_manager'))
const togglePm = (e: Event) => {
    pmOpen.value = (e.target as HTMLInputElement).checked
    if (!pmOpen.value) { removeType('creator_project_manager'); removeType('field_project_manager') }
}
const creatorPm = computed(() => hasType('creator_project_manager'))
const toggleCreatorPm = () => {
    if (creatorPm.value) removeType('creator_project_manager')
    else props.eligible.push({ subject_type: 'creator_project_manager', subject_id: null })
}
const fieldPm = computed(() => hasType('field_project_manager'))
const fieldPmFieldId = ref<number | null>(
    props.eligible.find((e) => e.subject_type === 'field_project_manager')?.subject_id
    ?? projectFields.value[0]?.id ?? null,
)
const toggleFieldPm = () => {
    if (fieldPm.value) { removeType('field_project_manager'); return }
    if (!fieldPmFieldId.value) fieldPmFieldId.value = projectFields.value[0]?.id ?? null
    if (fieldPmFieldId.value) props.eligible.push({ subject_type: 'field_project_manager', subject_id: fieldPmFieldId.value })
}
const setFieldPmField = () => {
    const s = props.eligible.find((e) => e.subject_type === 'field_project_manager')
    if (s) s.subject_id = fieldPmFieldId.value
}

/* creator = a toggle; stored as a {creator} eligible subject */
const creatorChecked = computed(() => props.eligible.some((e) => e.subject_type === 'creator'))
const toggleCreator = () => {
    const i = props.eligible.findIndex((e) => e.subject_type === 'creator')
    if (i >= 0) props.eligible.splice(i, 1)
    else props.eligible.push({ subject_type: 'creator', subject_id: null })
}

/* position picker = bulk add/remove into the member selection (NOT stored as eligible) */
const positionFilter = ref<number[]>([])
const usersOfPosition = (pid: number) => props.users.filter((u) => u.position_id === pid)
watch(positionFilter, (newIds, oldIds) => {
    const added = newIds.filter((id) => !oldIds.includes(id))
    const removed = oldIds.filter((id) => !newIds.includes(id))
    // pick a position → push all its users into the member list (dedup)
    for (const pid of added) {
        for (const u of usersOfPosition(pid)) {
            if (!members.value.some((m: any) => m.id === u.id)) members.value.push(u)
        }
    }
    // deselect a position → remove all its users from the member list
    for (const pid of removed) {
        const ids = usersOfPosition(pid).map((u) => u.id)
        if (ids.length) members.value = members.value.filter((m: any) => !ids.includes(m.id))
    }
})

/* members = the picked users; kept in sync with the {user} eligible subjects */
const members = ref<any[]>(
    props.eligible
        .filter((e) => e.subject_type === 'user' && e.subject_id != null)
        .map((e) => props.users.find((u) => u.id === e.subject_id) ?? { id: e.subject_id, name: `#${e.subject_id}` })
)
watch(members, (list) => {
    const others = props.eligible.filter((e) => e.subject_type !== 'user')
    const picked = (list ?? []).map((u: any) => ({ subject_type: 'user' as const, subject_id: u.id }))
    props.eligible.splice(0, props.eligible.length, ...others, ...picked)
}, { deep: true })
</script>

<style scoped>
.elig { display: flex; flex-direction: column; gap: 9px; }
.elig-h { font-size: 11px; color: gray; font-weight: 600; letter-spacing: .02em; }

/* plain checkboxes (no boxed chips) */
.elig-toggles { display: flex; flex-wrap: wrap; gap: 8px 18px; }
.chk { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; color: var(--primary-color); user-select: none; }
.chk input { margin: 0; cursor: pointer; }
.chk small { color: gray; font-size: 10.5px; margin-left: 6px; }
.chk.disabled { color: #aab0ba; cursor: not-allowed; }
.chk.disabled input { cursor: not-allowed; }

.elig-pm-sub { display: flex; flex-direction: column; gap: 8px; padding: 9px 11px; background: var(--bg3); border-radius: 8px; }
.elig-pm-select { max-width: 260px; }

/* pickers — side-by-side, compact, rounded, with a tiny label */
.elig-pickers { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px 14px; }
.pk { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.pk-lbl { font-size: 11px; color: gray; }
.pk :deep(.item-selector-shell),
.pk :deep(.member-selector-shell) { border: 1px solid var(--formBorder) !important; border-radius: 6px !important; box-sizing: border-box !important; }
.pk :deep(.one-selector .v-field__input),
.pk :deep(.member-selector-compact .v-field__input) { min-height: 32px; padding-top: 1px; padding-bottom: 1px; }
.pk :deep(.v-field__input) { --v-input-chips-margin-top: 2px; --v-input-chips-margin-bottom: 2px; }

.elig-hint { font-size: 11.5px; color: gray; line-height: 1.8; line-break: strict; }
</style>
