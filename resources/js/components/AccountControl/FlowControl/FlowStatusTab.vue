<template>
    <div class="flow-status-tab">
        <div class="sf-toggle">
            <span class="sw" :class="{ on: def.use_status_flow }" @click="def.use_status_flow = !def.use_status_flow"></span>
            <div>
                <b>ステータス機能を使う</b>
                <p>レコードにステータスと、状態を変えるアクションボタンを追加します。オフの場合は通常のデータ一覧として動作します。</p>
            </div>
        </div>

        <div v-if="def.use_status_flow" class="sf-body">
            <div class="col">
                <div class="col-h">ステータス</div>
                <div
                    v-for="(st, i) in def.statuses"
                    :key="st.key"
                    class="st"
                    :class="{ sel: selected === i }"
                    @click="selected = i"
                >
                    <div class="st-main">
                        <div class="st-name">{{ st.name || '(名称未設定)' }}</div>
                        <div class="st-meta">{{ st.actions.length }} アクション</div>
                    </div>
                    <span v-if="st.is_initial" class="st-tag">初期</span>
                    <div class="st-tools" @click.stop>
                        <button @click="move(i, -1)" :disabled="i === 0" title="上へ">▲</button>
                        <button @click="move(i, 1)" :disabled="i === def.statuses.length - 1" title="下へ">▼</button>
                        <button @click="removeStatus(i)" :disabled="def.statuses.length <= 1" title="削除"><CloseIcon size="10" /></button>
                    </div>
                </div>
                <button class="add-st" @click="addStatus">＋ ステータスを追加</button>
            </div>

            <div class="insp" v-if="current">
                <div class="irow">
                    <label>ステータス名</label>
                    <input type="text" v-model="current.name" class="custom-a-input !box-border flex-1">
                </div>
                <div class="irow">
                    <label>初期ステータス</label>
                    <span class="sw sm" :class="{ on: current.is_initial }" @click="setInitial(selected)"></span>
                    <span class="hint">新規レコードはここから開始</span>
                </div>

                <div class="divider"></div>
                <div class="sec">アクションボタン</div>
                <p class="hint mb-[8px]">押すとレコードを別のステータスへ移動します（差し戻しは逆向きのボタンを作るだけ）。</p>
                <FlowStatusActionRow
                    v-for="(a, ai) in current.actions"
                    :key="ai"
                    :action="a"
                    :status-options="otherStatuses(current)"
                    :users="users"
                    :positions="positions"
                    @remove="current.actions.splice(ai, 1)"
                />
                <button class="add-act" @click="addAction">＋ ボタンを追加</button>

                <div class="divider"></div>
                <div class="sec">フィールド権限（このステータスでの編集可否）</div>
                <p v-if="def.fields.length === 0" class="hint">先に「フォーム」タブで項目を追加してください。</p>
                <div v-for="f in valueFields" :key="f.key" class="perm">
                    <span class="text-[13px]">{{ f.label }}</span>
                    <div class="seg">
                        <button v-for="r in rules" :key="r.value" :class="{ on: ruleOf(f.key) === r.value }" @click="setRule(f.key, r.value)">{{ r.label }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { isLayoutType } from '@/types/flow'
import type { BuilderDefinition, BuilderStatus, FlowRule, FlowOptionUser, FlowOptionPosition } from '@/types/flow'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import FlowStatusActionRow from './FlowStatusActionRow.vue'

const props = defineProps<{
    def: BuilderDefinition
    users: FlowOptionUser[]
    positions: FlowOptionPosition[]
}>()

const rules: { value: FlowRule; label: string }[] = [
    { value: 'edit', label: '編集可' },
    { value: 'read', label: '閲覧のみ' },
    { value: 'hide', label: '非表示' },
]

const selected = ref(0)
const current = computed<BuilderStatus | null>(() => props.def.statuses[selected.value] ?? null)
const valueFields = computed(() => props.def.fields.filter((f) => !isLayoutType(f.input_type)))

let keySeq = 0
const newKey = () => `s_ui_${Date.now()}_${keySeq++}`

const otherStatuses = (st: BuilderStatus) =>
    props.def.statuses.filter((s) => s.key !== st.key).map((s) => ({ key: s.key, name: s.name || '(名称未設定)' }))

const addStatus = () => {
    props.def.statuses.push({ key: newKey(), name: '新しいステータス', is_initial: false, rules: {}, actions: [] })
    selected.value = props.def.statuses.length - 1
}
const removeStatus = (i: number) => {
    if (props.def.statuses.length <= 1) return
    const removedKey = props.def.statuses[i].key
    props.def.statuses.splice(i, 1)
    // Clear any buttons that targeted the removed status.
    props.def.statuses.forEach((s) => s.actions.forEach((a) => { if (a.to_status_key === removedKey) a.to_status_key = null }))
    if (selected.value >= props.def.statuses.length) selected.value = props.def.statuses.length - 1
    if (!props.def.statuses.some((s) => s.is_initial) && props.def.statuses[0]) props.def.statuses[0].is_initial = true
}
const move = (i: number, dir: number) => {
    const j = i + dir
    if (j < 0 || j >= props.def.statuses.length) return
    const arr = props.def.statuses
    ;[arr[i], arr[j]] = [arr[j], arr[i]]
    selected.value = j
}
const setInitial = (i: number) => {
    props.def.statuses.forEach((s, idx) => (s.is_initial = idx === i))
}
const addAction = () => {
    if (!current.value) return
    current.value.actions.push({ name: '', label: '', color: '#3b6df5', to_status_key: null, eligible: [] })
}

const ruleOf = (key: string): FlowRule => current.value?.rules[key] ?? 'edit'
const setRule = (key: string, rule: FlowRule) => { if (current.value) current.value.rules[key] = rule }
</script>

<style scoped>
.flow-status-tab { display: flex; flex-direction: column; gap: 16px; }
.sf-toggle { display: flex; align-items: flex-start; gap: 12px; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 14px 16px; }
.sf-toggle b { font-size: 14px; }
.sf-toggle p { font-size: 12px; color: gray; margin: 4px 0 0; line-height: 1.6; }
.sf-body { display: grid; grid-template-columns: 250px minmax(0, 1fr); gap: 16px; align-items: start; }
.col-h { font-size: 13px; color: gray; margin: 0 2px 9px; }
.st { display: flex; align-items: center; gap: 7px; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 6px; padding: 9px; cursor: pointer; margin-bottom: 8px; }
.st:hover { border-color: var(--formBorder); }
.st.sel { border-color: var(--primary-color); }
.st-main { flex: 1; min-width: 0; }
.st-name { font-size: 13px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.st-meta { font-size: 11px; color: gray; margin-top: 2px; }
.st-tag { font-size: 11px; color: var(--primary-color); background: var(--bg3); border: 1px solid var(--primary-color); padding: 1px 6px; border-radius: 8px; }
.st-tools { display: flex; gap: 1px; }
.st-tools button { border: none; background: none; color: gray; cursor: pointer; font-size: 12px; padding: 2px; }
.st-tools button:disabled { opacity: 0.25; cursor: default; }
.add-st { width: 100%; border: 1px dashed var(--formBorder); background: none; border-radius: 6px; padding: 8px; font-size: 12px; color: gray; cursor: pointer; }
.add-st:hover { background: var(--bg3); }
.insp { background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 12px; padding: 14px 16px; }
.sec { font-size: 13px; font-weight: 600; margin: 0 0 8px; }
.irow { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.irow label { font-size: 12px; color: gray; width: 96px; flex-shrink: 0; }
.hint { font-size: 11px; color: gray; }
.divider { height: 1px; background: var(--calendarBorder); margin: 16px 0; }
.add-act { width: 100%; border: 1px dashed var(--formBorder); background: none; border-radius: 7px; padding: 8px; font-size: 12px; color: var(--primary-color); cursor: pointer; }
.perm { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 0; border-bottom: 1px solid var(--calendarBorder); }
.perm:last-child { border-bottom: none; }
.seg { display: inline-flex; border: 1px solid var(--calendarBorder); border-radius: 6px; overflow: hidden; }
.seg button { border: none; background: var(--background-color); padding: 5px 10px; font-size: 12px; color: gray; cursor: pointer; border-right: 1px solid var(--calendarBorder); }
.seg button:last-child { border-right: none; }
.seg button.on { background: var(--bg3); color: var(--primary-color); font-weight: 500; }
.sw { width: 36px; height: 20px; border-radius: 10px; background: var(--formBorder); position: relative; cursor: pointer; display: inline-block; flex-shrink: 0; transition: background .12s; }
.sw.on { background: var(--primary-color); }
.sw::after { content: ""; position: absolute; width: 16px; height: 16px; border-radius: 50%; background: #fff; top: 2px; left: 2px; transition: left .12s; }
.sw.on::after { left: 18px; }
@media (max-width: 1000px) { .sf-body { grid-template-columns: 1fr; } }
</style>
