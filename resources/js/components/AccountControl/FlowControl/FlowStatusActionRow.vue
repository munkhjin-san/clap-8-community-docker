<template>
    <div class="act" :class="{ embedded }">
        <div class="act-top">
            <input type="color" v-model="action.color" class="act-color" title="ボタンの色">
            <input type="text" v-model="action.label" placeholder="ボタン名（例：承認）" class="custom-a-input !box-border act-name">
            <select v-model="action.to_status_key" class="custom-a-input !box-border act-to" title="移動先ステータス">
                <option :value="null" disabled>移動先…</option>
                <option v-for="s in statusOptions" :key="s.key" :value="s.key">{{ s.name }}</option>
            </select>
            <button v-if="!embedded" class="act-del" @click="emit('remove')" title="削除"><CloseIcon size="9" /></button>
        </div>

        <div class="act-elig">
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

            <p v-if="!eligibleConfigured" class="elig-hint">未設定 = 編集権限を持つ全員が押せます</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { BuilderStatusAction, FlowOptionUser, FlowOptionPosition, FlowField } from '@/types/flow'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import ItemSelector from '@/components/Form/ItemSelector.vue'
import MemberSelector from '@/components/Form/MemberSelector.vue'

const props = defineProps<{
    action: BuilderStatusAction
    statusOptions: { key: string; name: string }[]
    users: FlowOptionUser[]
    positions: FlowOptionPosition[]
    projectFields?: FlowField[]
    embedded?: boolean
}>()
const emit = defineEmits<{ remove: [] }>()

const projectFields = computed(() => props.projectFields ?? [])

/* ---- project-scoped process (PM options) ---- */
const hasType = (t: string) => props.action.eligible.some((e) => e.subject_type === t)
const removeType = (t: string) => {
    const l = props.action.eligible
    const i = l.findIndex((e) => e.subject_type === t)
    if (i >= 0) l.splice(i, 1)
}
const pmOpen = ref(hasType('creator_project_manager') || hasType('field_project_manager'))
const togglePm = (e: Event) => {
    pmOpen.value = (e.target as HTMLInputElement).checked
    if (!pmOpen.value) { removeType('creator_project_manager'); removeType('field_project_manager') }
}
const creatorPm = computed(() => hasType('creator_project_manager'))
const toggleCreatorPm = () => {
    if (creatorPm.value) removeType('creator_project_manager')
    else props.action.eligible.push({ subject_type: 'creator_project_manager', subject_id: null })
}
const fieldPm = computed(() => hasType('field_project_manager'))
const fieldPmFieldId = ref<number | null>(
    props.action.eligible.find((e) => e.subject_type === 'field_project_manager')?.subject_id
    ?? projectFields.value[0]?.id ?? null,
)
const toggleFieldPm = () => {
    if (fieldPm.value) { removeType('field_project_manager'); return }
    if (!fieldPmFieldId.value) fieldPmFieldId.value = projectFields.value[0]?.id ?? null
    if (fieldPmFieldId.value) props.action.eligible.push({ subject_type: 'field_project_manager', subject_id: fieldPmFieldId.value })
}
const setFieldPmField = () => {
    const s = props.action.eligible.find((e) => e.subject_type === 'field_project_manager')
    if (s) s.subject_id = fieldPmFieldId.value
}
const eligibleConfigured = computed(() =>
    creatorChecked.value || members.value.length > 0 || hasType('creator_project_manager') || hasType('field_project_manager'),
)

/* creator = a toggle; stored as a {creator} eligible subject */
const creatorChecked = computed(() => props.action.eligible.some((e) => e.subject_type === 'creator'))
const toggleCreator = () => {
    const list = props.action.eligible
    const i = list.findIndex((e) => e.subject_type === 'creator')
    if (i >= 0) list.splice(i, 1)
    else list.push({ subject_type: 'creator', subject_id: null })
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
    props.action.eligible
        .filter((e) => e.subject_type === 'user' && e.subject_id != null)
        .map((e) => props.users.find((u) => u.id === e.subject_id) ?? { id: e.subject_id, name: `#${e.subject_id}` })
)
watch(members, (list) => {
    const others = props.action.eligible.filter((e) => e.subject_type !== 'user')
    const picked = (list ?? []).map((u: any) => ({ subject_type: 'user' as const, subject_id: u.id }))
    props.action.eligible.splice(0, props.action.eligible.length, ...others, ...picked)
}, { deep: true })
</script>

<style scoped>
.act { position: relative; border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 12px; margin-bottom: 10px; background: var(--background-color); }
/* inside the settings modal: no card chrome, the modal owns the frame */
.act.embedded { border: none; padding: 0; margin: 0; background: none; }
.act.embedded .act-top { padding-right: 0; }
.act.embedded .act-elig { margin-top: 14px; }
/* top row: color + name + 移動先, inline & wrappable */
.act-top { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; padding-right: 22px; }
.act-color { width: 24px; height: 24px; padding: 0; border: 1px solid var(--formBorder); border-radius: 6px; background: none; cursor: pointer; flex-shrink: 0; overflow: hidden; }
.act-color::-webkit-color-swatch-wrapper { padding: 0; }
.act-color::-webkit-color-swatch { border: none; border-radius: 5px; }
.act-color::-moz-color-swatch { border: none; border-radius: 5px; }
.act-name { flex: 0 1 200px; min-width: 120px; }
.act-to { flex: 0 1 150px; min-width: 110px; }
.act-del { position: absolute; top: 8px; right: 8px; border: none; background: none; color: gray; cursor: pointer; padding: 4px; display: flex; flex-shrink: 0; }

.act-elig { margin-top: 12px; border-top: 1px dashed var(--calendarBorder); padding-top: 10px; display: flex; flex-direction: column; gap: 9px; }
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

.elig-hint { font-size: 11px; color: gray; }
</style>
