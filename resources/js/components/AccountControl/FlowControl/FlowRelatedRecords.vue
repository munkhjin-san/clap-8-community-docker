<template>
    <div class="rr">
        <div class="rr-head">
            <span class="rr-ico"><FlowFieldIcon type="related" :size="15" /></span>
            <span class="rr-title">{{ field.label || '関連レコード' }}</span>
            <span v-if="data?.child" class="rr-app">{{ data.child.name }}</span>
            <span v-if="data?.ok" class="rr-count">{{ countText }}</span>
            <button v-if="canAdd" type="button" class="rr-add" :title="`${data?.child?.name}を追加（この${parentLabel}が入った状態で開きます）`" @click="addChild">＋ 追加</button>
        </div>

        <p v-if="loading" class="rr-note">読み込み中…</p>
        <p v-else-if="data && !data.ok" class="rr-note">{{ data.message }}</p>
        <p v-else-if="!rows.length" class="rr-note">まだありません。</p>

        <div v-else class="rr-scroll">
            <table class="rr-table">
                <thead>
                    <tr>
                        <th class="rr-num">#</th>
                        <th v-for="c in data!.columns" :key="c.id">{{ c.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in rows" :key="r.id" class="rr-row" @click="openChild(r)">
                        <td class="rr-num">{{ r.record_number }}</td>
                        <td v-for="c in data!.columns" :key="c.id">
                            <FlowFieldInput :field="cellField(c)" :model-value="r.cells[String(c.id)]" :users="users" readonly cell-preview />
                        </td>
                    </tr>
                </tbody>
                <!-- 合計は表示している上限ではなく、見える全件が対象 -->
                <tfoot v-if="data!.aggregates?.length">
                    <tr>
                        <td class="rr-num"></td>
                        <td v-for="c in data!.columns" :key="c.id" class="rr-sum">
                            <template v-if="sumOf(c.id) !== null">
                                <span class="rr-sum-label">合計</span>{{ fmt(sumOf(c.id)!, c) }}
                            </template>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p v-if="data?.ok && data.total > data.shown" class="rr-note rr-more">
            上位 {{ data.shown }} 件を表示（全 {{ data.total }} 件）
        </p>
    </div>
</template>

<script setup lang="ts">
/**
 * 関連レコード — このレコードを指している他アプリのレコードを一覧する。
 *
 * kintoneの関連レコード一覧と違い、結び付けは「値の一致」ではなく既にあるルックアップ関係の裏返し。
 * だから項目名を変えても壊れず、行の権限は子アプリの規則がそのまま効く。
 *
 * 値を持たないブロックなので（isLayoutType に入っている）保存にも計算にもCSV出力にも乗らない。
 */
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import FlowFieldIcon from './FlowFieldIcon.vue'
import FlowFieldInput from './FlowFieldInput.vue'
import type { FlowField, FlowFieldValidation, FlowOptionUser } from '@/types/flow'
import { formatFlowNumber } from '@/utils/flowNumber'

const props = defineProps<{
    field: FlowField
    recordId: number | null
    /** the parent app's name, for the ＋追加 tooltip */
    parentLabel?: string
    users?: FlowOptionUser[]
}>()

interface Col { id: number; label: string; input_type: string; validation?: FlowFieldValidation }
interface Row { id: number; record_number: number; cells: Record<string, any> }
interface Payload {
    ok: boolean
    message: string | null
    child: { id: number; name: string } | null
    link_field_id?: number
    columns: Col[]
    rows: Row[]
    total: number
    shown: number
    /** may the viewer create records in the child app? the server answers, not a prop default —
     *  a declared optional Boolean prop casts an absent value to false, which silently hid the button */
    can_add?: boolean
    aggregates: { id: number; label: string; sum: number }[]
}

const api = useApi()
const router = useRouter()
const data = ref<Payload | null>(null)
const loading = ref(false)

const rows = computed(() => data.value?.rows ?? [])
const parentLabel = computed(() => props.parentLabel || 'レコード')
const canAdd = computed(() => !!data.value?.ok && !!data.value.child && data.value.can_add === true)

const countText = computed(() => {
    const t = data.value?.total ?? 0
    return `${t}件`
})

const load = async () => {
    if (!props.recordId || !props.field.id) { data.value = null; return }
    loading.value = true
    try {
        data.value = await api.get(`/flow_related/${props.field.id}/${props.recordId}`) as Payload
    } finally {
        loading.value = false
    }
}
watch(() => [props.recordId, props.field.id], load, { immediate: true })

/** Reuse the normal read-only renderer for each cell, so types display consistently. */
const cellField = (c: Col): FlowField => ({
    key: `c${c.id}`, label: c.label, input_type: c.input_type as any, width: 200, validation: c.validation ?? {},
} as FlowField)

const sumOf = (columnId: number): number | null => {
    const a = data.value?.aggregates?.find((x) => x.id === columnId)
    return a ? a.sum : null
}
/** 合計も、その列の項目に設定された見え方に合わせる。 */
const fmt = (n: number, c: Col) => formatFlowNumber(n, c.validation)

const openChild = (r: Row) => {
    if (!data.value?.child) return
    router.push({ name: 'flow-record-detail', params: { flowId: data.value.child.id, recordId: r.record_number } })
}

/**
 * 子アプリの新規作成を開き、こちらを指すルックアップを埋めた状態にする。
 * kintoneでは相手のアプリへ行って取引先を手で選び直す必要があった部分。
 */
const addChild = () => {
    if (!data.value?.child || !data.value.link_field_id || !props.recordId) return
    router.push({
        name: 'flow-record-new',
        params: { flowId: data.value.child.id },
        query: { link_field: data.value.link_field_id, link_record: props.recordId },
    })
}
</script>

<style scoped>
.rr { box-sizing: border-box; width: 100%; border: 1px solid var(--calendarBorder); border-radius: 10px; background: var(--background-color); padding: 10px 12px 12px; }
.rr-head { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.rr-ico { display: inline-flex; color: gray; }
.rr-title { font-size: 13px; color: var(--primary-color); }
.rr-app { font-size: 11.5px; color: gray; border: 1px solid var(--formBorder); border-radius: 4px; padding: 1px 6px; }
.rr-count { font-size: 11.5px; color: gray; font-variant-numeric: tabular-nums; }
.rr-add { margin-left: auto; font-size: 12px; padding: 4px 10px; border: 1px solid var(--formBorder); border-radius: 6px; background: var(--background-color); color: var(--primary-color); cursor: pointer; white-space: nowrap; letter-spacing: normal; }
.rr-add:hover { background: var(--bg3); }
.rr-note { font-size: 12px; color: gray; margin: 6px 0 0; line-height: 1.7; }
.rr-more { margin-top: 8px; }

.rr-scroll { overflow-x: auto; }
.rr-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.rr-table th, .rr-table td { text-align: left; padding: 6px 10px; border-bottom: 1px solid var(--calendarBorder); white-space: nowrap; }
.rr-table th { font-size: 11px; color: gray; font-weight: 500; background: var(--bg3); }
.rr-num { width: 54px; color: gray; font-variant-numeric: tabular-nums; }
.rr-row { cursor: pointer; }
.rr-row:hover td { background: var(--bg3); }
.rr-table tfoot td { border-bottom: none; color: var(--primary-color); font-variant-numeric: tabular-nums; }
.rr-sum-label { font-size: 10.5px; color: gray; margin-right: 6px; }
</style>
