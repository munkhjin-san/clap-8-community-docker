<template>
    <div class="flow-status-tab">
        <div class="sf-toggle">
            <span class="flow-sw" :class="{ on: def.use_status_flow }" @click="def.use_status_flow = !def.use_status_flow"></span>
            <div>
                <b>ステータス機能を使う</b>
                <p>レコードにステータスと、状態を変えるアクションボタンを追加します。オフの場合は通常のデータ一覧として動作します。</p>
            </div>
        </div>

        <div v-if="def.use_status_flow" class="sf-canvas-wrap">
            <div class="canvas-bar">
                <button class="cb-add" @click="addStatus">＋ ステータス</button>
                <span class="cb-hint">ブロックをタップして名前・アクションボタンを設定します。</span>
            </div>

            <div ref="canvasEl" class="sf-canvas">
                <div class="sf-stage" :style="{ height: contentHeight + 'px', minWidth: stageMinWidth + 'px' }">
                    <!-- edges: a nested <svg x="50%"> puts x=0 at the horizontal centre, so no pixel
                         measurement is needed — everything stays centred purely via SVG/CSS percentages. -->
                    <svg class="sf-edges" width="100%" :height="contentHeight" xmlns="http://www.w3.org/2000/svg">
                        <svg x="50%" y="0" width="1" :height="contentHeight" style="overflow: visible">
                            <path
                                v-for="e in edgeModels"
                                :key="e.id"
                                :d="e.d"
                                fill="none"
                                :stroke="e.color"
                                stroke-width="2"
                            />
                            <path
                                v-for="e in edgeModels"
                                :key="'a' + e.id"
                                :d="e.arrow"
                                :fill="e.color"
                                stroke="none"
                            />
                        </svg>
                    </svg>

                    <button
                        v-for="e in edgeModels"
                        :key="'l' + e.id"
                        class="edge-lbl"
                        :style="{ left: `calc(50% + ${e.labelDx}px)`, top: e.labelY + 'px', '--edge-c': e.color }"
                        @click.stop="openActionByRef(e)"
                        title="ボタン設定を編集"
                    >{{ e.label || 'ボタン' }}</button>

                    <div
                        v-for="(s, i) in def.statuses"
                        :key="s.key"
                        class="sn"
                        :class="{ initial: s.is_initial }"
                        :style="{ top: blockTop(i) + 'px' }"
                        @click="openStatus(s.key)"
                    >
                        <span v-if="s.is_initial" class="sn-badge">初期</span>
                        <span class="sn-name">{{ s.name || '(名称未設定)' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ステータス設定 modal (name / initial / field rules / action buttons) -->
        <Modal v-if="editingStatus" persist @close="closeStatus">
            <template #title><b class="sf-modal-title">ステータス設定</b></template>
            <template #content>
                <div class="insp-grid">
                    <div class="ifld">
                        <label>ステータス名</label>
                        <div class="st-name-row">
                            <!-- color swatch sits in front of the name input, same as the action-button color -->
                            <span class="st-color-wrap" :class="{ theme: !editingStatus.color }" :title="editingStatus.color ? 'ステータスの色' : 'テーブル表示色（未設定はグレー）'">
                                <input type="color" class="st-color-input" :value="editingStatus.color || '#cccccc'"
                                    @input="editingStatus.color = ($event.target as HTMLInputElement).value">
                                <button v-if="editingStatus.color" type="button" class="st-color-clear" title="色を解除"
                                    @click.stop="editingStatus.color = null">×</button>
                            </span>
                            <input type="text" v-model="editingStatus.name" class="custom-a-input !box-border">
                        </div>
                    </div>
                    <div class="ifld">
                        <label>初期ステータス <small>新規レコードはここから開始</small></label>
                        <span class="flow-sw" :class="{ on: editingStatus.is_initial }" @click="setInitial(editingStatus)"></span>
                    </div>
                </div>

                <div class="divider"></div>
                <div class="sec">アクションボタン</div>
                <p class="hint mb-[8px]">このステータスのレコードに表示され、押すと選んだステータスへ移動します。</p>
                <FlowStatusActionRow
                    v-for="(a, ai) in editingStatus.actions"
                    :key="ai"
                    :action="a"
                    :status-options="otherStatuses(editingStatus)"
                    :users="users"
                    :positions="positions"
                    :project-fields="projectFields"
                    @remove="editingStatus.actions.splice(ai, 1)"
                />
                <button class="add-act" @click="addActionTo(editingStatus)">＋ ボタンを追加</button>

                <div class="divider"></div>
                <div class="sec">フィールド権限（このステータスでの編集可否）</div>
                <p v-if="valueFields.length === 0" class="hint">先に「フォーム」タブで項目を追加してください。</p>
                <div v-for="f in valueFields" :key="f.key" class="perm">
                    <span class="text-[13px]">{{ f.label }}</span>
                    <div class="flow-seg">
                        <button v-for="r in rules" :key="r.value" :class="{ on: ruleOf(editingStatus, f.key) === r.value }" @click="setRule(editingStatus, f.key, r.value)">{{ r.label }}</button>
                    </div>
                </div>

                <div class="modal-foot">
                    <button class="del" :disabled="def.statuses.length <= 1" @click="deleteEditingStatus">このステータスを削除</button>
                    <button class="done" @click="closeStatus">完了</button>
                </div>
            </template>
        </Modal>

        <!-- アクションボタン設定 modal (edge-label shortcut) -->
        <Modal v-if="editingAction && editingActionStatus" persist @close="closeAction">
            <template #title><b class="sf-modal-title">ボタン設定</b></template>
            <template #content>
                <p class="hint mb-[10px]"><b>{{ editingActionStatus.name || '(名称未設定)' }}</b> のレコードで押せるボタンです。押すと選んだステータスへ移動します。</p>
                <FlowStatusActionRow
                    :action="editingAction"
                    :status-options="otherStatuses(editingActionStatus)"
                    :users="users"
                    :positions="positions"
                    :project-fields="projectFields"
                    embedded
                />
                <div class="modal-foot">
                    <button class="del" @click="deleteEditingAction">このボタンを削除</button>
                    <button class="done" @click="closeAction">完了</button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { computed, ref, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { isLayoutType } from '@/types/flow'
import type { BuilderDefinition, BuilderStatus, BuilderStatusAction, FlowRule, FlowOptionUser, FlowOptionPosition } from '@/types/flow'
import Modal from '@/components/Global/Modal.vue'
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

const valueFields = computed(() => props.def.fields.filter((f) => !isLayoutType(f.input_type)))
const projectFields = computed(() => props.def.fields.filter((f) => f.input_type === 'project'))

let keySeq = 0
const newKey = () => `s_ui_${Date.now()}_${keySeq++}`

/* ---------------- vertical, centred layout (all geometry is centre-relative; x=0 is the centre) ---------------- */
const HALF_W = 110 // half of a block's width (220) — right edge sits at +110 from centre
const BLOCK_H = 52 // rendered block height (border-box, min-height 52)
const TOP0 = 24
const ROW_H = 120

const contentHeight = computed(() => TOP0 + props.def.statuses.length * ROW_H)

const blockTop = (i: number) => TOP0 + i * ROW_H
const centerY = (i: number) => blockTop(i) + BLOCK_H / 2
const bottomY = (i: number) => blockTop(i) + BLOCK_H

const edgeModels = computed(() => {
    const idx = new Map(props.def.statuses.map((s, i) => [s.key, i]))
    const out: { id: string; d: string; arrow: string; labelDx: number; labelY: number; color: string; label: string; sKey: string; aRef: BuilderStatusAction }[] = []
    let backCount = 0 // detour edges are alternated right/left and stacked into per-side lanes
    props.def.statuses.forEach((s, i) => {
        s.actions.forEach((a, ai) => {
            if (!a.to_status_key) return
            const j = idx.get(a.to_status_key)
            if (j == null) return
            const color = a.color || 'var(--primary-color)'
            let d: string, arrow: string, labelDx: number, labelY: number
            if (j === i + 1) {
                // straight step down between adjacent blocks (x=0 is the centre)
                const ty = blockTop(j)
                d = `M 0 ${bottomY(i)} L 0 ${ty}`
                arrow = `M 0 ${ty} L -5 ${ty - 9} L 5 ${ty - 9} Z` // tip on the block's top edge, pointing down
                labelDx = 0
                labelY = (bottomY(i) + ty) / 2
            } else {
                // backward / skip → rounded orthogonal detour, alternated right/left so both
                // sides are used; arrowhead points into the target block's near-side edge
                const k = backCount++
                const side = k % 2 === 0 ? 1 : -1        // right, left, right, left …
                const laneIdx = Math.floor(k / 2)
                const y1 = centerY(i)
                const y2 = centerY(j)
                const edge = side * HALF_W               // block edge on this side
                const laneX = side * (HALF_W + 44 + laneIdx * 34)
                const r = 12
                const dir = y2 > y1 ? 1 : -1
                d = `M ${edge} ${y1} L ${laneX - side * r} ${y1} Q ${laneX} ${y1} ${laneX} ${y1 + dir * r} `
                    + `L ${laneX} ${y2 - dir * r} Q ${laneX} ${y2} ${laneX - side * r} ${y2} L ${edge} ${y2}`
                arrow = `M ${edge} ${y2} L ${edge + side * 9} ${y2 - 5} L ${edge + side * 9} ${y2 + 5} Z`
                labelDx = laneX
                labelY = (y1 + y2) / 2
            }
            out.push({ id: `${s.key}::${ai}`, d, arrow, labelDx, labelY, color, label: a.label, sKey: s.key, aRef: a })
        })
    })
    return out
})

// The stage must be wide enough to hold the blocks plus the widest side lane/label,
// centred; the canvas then scrolls horizontally on narrow screens instead of clipping.
const stageMinWidth = computed(() => {
    let ext = HALF_W + 30
    for (const e of edgeModels.value) ext = Math.max(ext, Math.abs(e.labelDx) + 96)
    return Math.ceil(ext * 2)
})

// When the stage is wider than the viewport (narrow screens), open scrolled to the
// centre so the blocks are in view rather than pushed off to the right.
const canvasEl = ref<HTMLElement | null>(null)
const centerScroll = () => {
    const el = canvasEl.value
    if (el && el.scrollWidth > el.clientWidth) el.scrollLeft = (el.scrollWidth - el.clientWidth) / 2
}
let centered = false
let io: IntersectionObserver | null = null
onMounted(() => {
    nextTick(centerScroll)
    if (canvasEl.value && typeof IntersectionObserver !== 'undefined') {
        io = new IntersectionObserver((entries) => {
            if (entries.some((e) => e.isIntersecting) && !centered) { centered = true; nextTick(centerScroll) }
        })
        io.observe(canvasEl.value)
    }
})
onBeforeUnmount(() => io?.disconnect())

/* ---------------- status add / edit / delete ---------------- */
const addStatus = () => {
    props.def.statuses.push({ key: newKey(), name: '新しいステータス', is_initial: false, ui_x: null, ui_y: null, rules: {}, actions: [] })
}

const editingStatusKey = ref<string | null>(null)
const editingStatus = computed<BuilderStatus | null>(() =>
    props.def.statuses.find((s) => s.key === editingStatusKey.value) ?? null,
)
const openStatus = (key: string) => { editingStatusKey.value = key }
const closeStatus = () => { editingStatusKey.value = null }

const setInitial = (st: BuilderStatus) => {
    props.def.statuses.forEach((s) => (s.is_initial = s.key === st.key))
}

const deleteEditingStatus = () => {
    const st = editingStatus.value
    if (!st || props.def.statuses.length <= 1) return
    const i = props.def.statuses.findIndex((s) => s.key === st.key)
    props.def.statuses.splice(i, 1)
    props.def.statuses.forEach((s) => s.actions.forEach((a) => { if (a.to_status_key === st.key) a.to_status_key = null }))
    if (!props.def.statuses.some((s) => s.is_initial) && props.def.statuses[0]) props.def.statuses[0].is_initial = true
    editingStatusKey.value = null
}

const ruleOf = (st: BuilderStatus, key: string): FlowRule => st.rules[key] ?? 'edit'
const setRule = (st: BuilderStatus, key: string, rule: FlowRule) => { st.rules[key] = rule }

/* ---------------- action buttons ---------------- */
const otherStatuses = (st: BuilderStatus) =>
    props.def.statuses.filter((s) => s.key !== st.key).map((s) => ({ key: s.key, name: s.name || '(名称未設定)' }))

const addActionTo = (st: BuilderStatus) => {
    const target = otherStatuses(st)[0]?.key ?? null
    // empty color → the button inherits the app's theme color (see FlowRecordDetail .rd-act)
    st.actions.push({ name: '', label: '新しいボタン', color: '', to_status_key: target, eligible: [] })
}

const editingAction = ref<BuilderStatusAction | null>(null)
const editingActionStatus = ref<BuilderStatus | null>(null)
const openActionByRef = (e: { sKey: string; aRef: BuilderStatusAction }) => {
    const st = props.def.statuses.find((s) => s.key === e.sKey)
    if (!st) return
    editingActionStatus.value = st
    editingAction.value = e.aRef
}
const closeAction = () => { editingAction.value = null; editingActionStatus.value = null }
const deleteEditingAction = () => {
    const st = editingActionStatus.value
    const a = editingAction.value
    if (st && a) {
        const i = st.actions.indexOf(a)
        if (i >= 0) st.actions.splice(i, 1)
    }
    closeAction()
}
</script>

<style scoped>
.flow-status-tab { display: flex; flex-direction: column; gap: 16px; }
.sf-toggle { display: flex; align-items: flex-start; gap: 12px; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 14px 16px; }
.sf-toggle b { font-size: 14px; }
.sf-toggle p { font-size: 12px; color: gray; margin: 4px 0 0; line-height: 1.6; }

/* ---- canvas: vertical, centred, scrolls (no drag / no pan) ---- */
.sf-canvas-wrap { display: flex; flex-direction: column; gap: 10px; }
.canvas-bar { display: flex; align-items: center; gap: 8px 12px; flex-wrap: wrap; }
.cb-add { border: 1px dashed var(--formBorder); background: none; border-radius: 7px; padding: 7px 14px; font-size: 12px; color: var(--primary-color); cursor: pointer; white-space: nowrap; }
.cb-add:hover { background: var(--bg3); }
.cb-hint { font-size: 11px; color: gray; }
.sf-canvas { max-height: 62vh; overflow: auto; border: 1px solid var(--calendarBorder); border-radius: 12px; background: var(--bg3); }
.sf-stage { position: relative; width: 100%; }

.sf-edges { position: absolute; inset: 0; z-index: 0; pointer-events: none; overflow: visible; }

/* status block — centred via CSS, the whole block is the tap target */
.sn { position: absolute; left: 50%; transform: translateX(-50%); width: 220px; box-sizing: border-box !important; min-height: 52px; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 16px; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); cursor: pointer; transition: border-color .12s, box-shadow .12s; }
.sn:hover { border-color: var(--primary-color); box-shadow: 0 2px 8px rgba(0,0,0,.14); }
.sn.initial { border-color: var(--primary-color); box-shadow: 0 0 0 1px var(--primary-color); }
.sn-name { font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sn-badge { font-size: 10px; color: var(--primary-color); background: var(--bg3); border: 1px solid var(--primary-color); padding: 0 6px; border-radius: 8px; flex-shrink: 0; }

/* edge label = a clickable pill sitting on the connector */
.edge-lbl { position: absolute; z-index: 2; transform: translate(-50%, -50%); pointer-events: all; cursor: pointer; border: 1px solid var(--edge-c); color: var(--edge-c); background: var(--background-color); border-radius: 999px; padding: 3px 12px; font-size: 12px; font-weight: 600; line-height: 1.2; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis; box-shadow: 0 1px 3px rgba(0,0,0,.12); transition: background .12s, color .12s; }
.edge-lbl:hover { background: var(--edge-c); color: #fff; }

/* ---- modal content (shared Global/Modal.vue shell) ---- */
.sf-modal-title { font-size: 15px; font-weight: 600; }
.modal-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 22px; padding-top: 16px; border-top: 1px solid var(--calendarBorder); }
.modal-foot .done { margin-left: auto; border: none; background: var(--primary-button, var(--primary-color)); color: #fff; border-radius: 7px; padding: 8px 22px; font-size: 13px; cursor: pointer; }
.modal-foot .done:hover { opacity: 0.88; }
.modal-foot .del { border: 1px solid tomato; color: tomato; background: none; border-radius: 7px; padding: 7px 14px; font-size: 12px; cursor: pointer; }
.modal-foot .del:disabled { opacity: .4; cursor: default; }

/* status name / initial — two columns, label above input */
.insp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; margin-bottom: 4px; align-items: start; }
.ifld { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.st-name-row { display: flex; align-items: center; gap: 8px; }
.st-name-row .custom-a-input { flex: 1; min-width: 0; }
.st-color-wrap { position: relative; display: inline-flex; width: fit-content; flex: none; }
/* "unset" state: dim the swatch so it doesn't read as a chosen color */
.st-color-wrap.theme .st-color-input { opacity: .35; }
/* width forced: a container rule (.ifld input) otherwise stretches it full-width */
.st-color-input { width: 24px !important; height: 24px; padding: 0; border: 1px solid var(--formBorder); border-radius: 6px; background: none; cursor: pointer; overflow: hidden; }
/* let the color fill the whole swatch (native inputs inset it by default) — matches .act-color */
.st-color-input::-webkit-color-swatch-wrapper { padding: 0; }
.st-color-input::-webkit-color-swatch { border: none; border-radius: 5px; }
.st-color-input::-moz-color-swatch { border: none; border-radius: 5px; }
.st-color-clear { position: absolute; top: -6px; right: -6px; width: 15px; height: 15px; border-radius: 50%; border: 1px solid var(--formBorder); background: var(--background-color); color: gray; font-size: 10px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; }
.st-color-clear:hover { color: var(--primary-color); border-color: var(--primary-color); }
.ifld > label { font-size: 11px; color: gray; }
.ifld > label small { color: #b0b6c0; margin-left: 4px; font-size: 10.5px; }
.ifld input { width: 100%; }

.sec { font-size: 13px; font-weight: 600; margin: 0 0 8px; }
.hint { font-size: 11px; color: gray; }
.divider { height: 1px; background: var(--calendarBorder); margin: 16px 0; }
.add-act { width: 100%; border: 1px dashed var(--formBorder); background: none; border-radius: 7px; padding: 8px; font-size: 12px; color: var(--primary-color); cursor: pointer; }
.add-act:hover { background: var(--bg3); }
.perm { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 0; border-bottom: 1px solid var(--calendarBorder); }
.perm:last-child { border-bottom: none; }

</style>
