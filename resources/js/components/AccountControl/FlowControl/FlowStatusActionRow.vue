<template>
    <div class="act">
        <div class="act-top">
            <input type="color" v-model="action.color" class="act-color" title="色">
            <input type="text" v-model="action.label" placeholder="ボタン名（例：承認）" class="custom-a-input !box-border flex-1">
            <button class="act-del" @click="emit('remove')" title="削除"><CloseIcon size="9" /></button>
        </div>

        <div class="act-row">
            <label>移動先</label>
            <select v-model="action.to_status_key" class="custom-a-input !box-border flex-1">
                <option :value="null" disabled>ステータスを選択</option>
                <option v-for="s in statusOptions" :key="s.key" :value="s.key">{{ s.name }}</option>
            </select>
        </div>
        <div class="act-row">
            <label>内部名</label>
            <input type="text" v-model="action.name" placeholder="任意（連携用）" class="custom-a-input !box-border flex-1">
        </div>

        <div class="act-elig">
            <div class="elig-h">押せる人（責任者）</div>

            <label class="elig-creator">
                <input type="checkbox" :checked="creatorChecked" @change="toggleCreator">
                <span>作成者</span>
            </label>

            <div class="elig-pos">
                <ItemSelector
                    v-model="positionFilter"
                    :options="positions"
                    :multiple="true"
                    :clearable="true"
                    :close-on-select="false"
                    label="name"
                    place-holder="役職で絞り込み（任意）"
                />
            </div>

            <MemberSelector
                v-model="members"
                :options="(users as any)"
                :multiple="true"
                place-holder="ユーザーを選択"
            />

            <p v-if="!creatorChecked && !members.length" class="elig-hint">未設定 = 編集権限を持つ全員が押せます</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { BuilderStatusAction, FlowOptionUser, FlowOptionPosition } from '@/types/flow'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import ItemSelector from '@/components/Form/ItemSelector.vue'
import MemberSelector from '@/components/Form/MemberSelector.vue'

const props = defineProps<{
    action: BuilderStatusAction
    statusOptions: { key: string; name: string }[]
    users: FlowOptionUser[]
    positions: FlowOptionPosition[]
}>()
const emit = defineEmits<{ remove: [] }>()

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
.act { border: 1px solid var(--calendarBorder); border-radius: 8px; padding: 10px; margin-bottom: 10px; background: var(--background-color); }
.act-top { display: flex; align-items: center; gap: 8px; }
.act-color { width: 30px; height: 30px; padding: 0; border: 1px solid var(--formBorder); border-radius: 6px; background: none; cursor: pointer; flex-shrink: 0; }
.act-del { border: none; background: none; color: gray; cursor: pointer; padding: 4px; display: flex; }
.act-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
.act-row label { font-size: 12px; color: gray; width: 52px; flex-shrink: 0; }
.act-elig { margin-top: 10px; border-top: 1px dashed var(--calendarBorder); padding-top: 9px; display: flex; flex-direction: column; gap: 8px; }
.elig-h { font-size: 12px; color: gray; }
.elig-creator { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; width: fit-content; }
.elig-hint { font-size: 11px; color: gray; }
</style>
