<template>
    <Modal persist @close="emit('close')">
        <template #title>集計スロットの設定</template>
        <template #content>
            <div class="se-row">
                <label class="se-label">スロット名</label>
                <input v-model="tool.name" type="text" class="custom-a-input !box-border flex-1" placeholder="集計">
            </div>

            <div class="se-row">
                <label class="se-label">表示位置</label>
                <div class="se-seg">
                    <button type="button" :class="{ on: cfg.position === 'top' }" @click="cfg.position = 'top'">表の上</button>
                    <button type="button" :class="{ on: cfg.position === 'bottom' }" @click="cfg.position = 'bottom'">表の下</button>
                </div>
            </div>

            <div class="se-sec">集計する項目</div>
            <p v-if="!sources.length" class="se-empty">集計できる項目がありません。数値または計算の項目（テーブル内の列も可）を追加してください。</p>

            <div v-for="(item, i) in cfg.items" :key="i" class="se-item">
                <select v-model="item.source" class="custom-a-input !box-border se-src">
                    <option value="">項目を選択</option>
                    <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <select v-model="item.fn" class="custom-a-input !box-border se-fn">
                    <option v-for="(label, fn) in SLOT_AGG_FN_LABEL" :key="fn" :value="fn">{{ label }}</option>
                </select>
                <input v-model="item.prefix" type="text" class="custom-a-input !box-border se-affix" placeholder="￥" title="数値の前に付ける文字" maxlength="6">
                <input v-model="item.suffix" type="text" class="custom-a-input !box-border se-affix" placeholder="件" title="数値の後に付ける文字" maxlength="6">
                <input v-model="item.label" type="text" class="custom-a-input !box-border se-cap" :placeholder="autoLabel(item)">
                <span class="se-preview" title="表示例">{{ preview(item) }}</span>
                <button class="se-del" title="削除" @click="cfg.items.splice(i, 1)"><CloseIcon size="9" /></button>
            </div>

            <button class="se-add" :disabled="!sources.length" @click="addItem">＋ 項目を追加</button>

            <p class="se-note">集計はいま表示しているビューの絞り込みに従います（ページ内だけでなく、条件に一致するすべてのレコードが対象です）。スロットはアプリの設定なので、どのビューでも同じ項目が表示されます。</p>
            <p class="se-note">空欄のレコードは計算から除きます。閲覧できない項目は集計そのものが表示されません。</p>
            <p class="se-note">前後の文字（￥・件 など）は表示だけに使われ、計算には影響しません。</p>

            <div class="se-actions">
                <button class="se-btn" @click="emit('close')">閉じる</button>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@/components/Global/Modal.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import { SLOT_AGG_FN_LABEL, slotConfig } from '@/types/flow'
import type { BuilderDefinition, FlowAppTool, SlotAggItem } from '@/types/flow'

const props = defineProps<{ tool: FlowAppTool; def: BuilderDefinition }>()
const emit = defineEmits<{ close: [] }>()

const cfg = computed(() => slotConfig(props.tool))
const NUMERIC = ['number', 'formula']

/**
 * Aggregatable sources: 数値/計算 fields, plus 数値/計算 columns inside a テーブル.
 * Only saved fields appear — an unsaved field has no id yet, and the slot stores ids.
 */
const sources = computed(() => {
    const out: { value: string; label: string }[] = []
    for (const f of props.def.fields) {
        if (!f.id) continue
        if (NUMERIC.includes(f.input_type)) {
            out.push({ value: String(f.id), label: f.label })
            continue
        }
        if (f.input_type === 'table') {
            for (const c of (f.validation?.columns ?? [])) {
                if (NUMERIC.includes(c.input_type)) {
                    out.push({ value: `${f.id}:${c.key}`, label: `${f.label} › ${c.label || c.key}` })
                }
            }
        }
    }
    return out
})

const sourceLabel = (source: string) => sources.value.find((s) => s.value === source)?.label ?? ''
const autoLabel = (item: SlotAggItem) => {
    const base = sourceLabel(item.source)
    return base ? `${base} の ${SLOT_AGG_FN_LABEL[item.fn]}` : '表示名（任意）'
}

/** What the record list will show, using a sample number — the affixes are display-only. */
const preview = (item: SlotAggItem) => `${item.prefix ?? ''}${(1234).toLocaleString('ja-JP')}${item.suffix ?? ''}`

const addItem = () => cfg.value.items.push({ source: sources.value[0]?.value ?? '', fn: 'sum', label: '', prefix: '', suffix: '' })
</script>

<style scoped>
.se-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.se-label { font-size: 12px; color: gray; flex-shrink: 0; min-width: 70px; }
.se-seg { display: inline-flex; border: 1px solid var(--formBorder); border-radius: 7px; overflow: hidden; }
.se-seg button { border: none; background: var(--background-color); color: var(--primary-color); font-size: 12px; padding: 7px 14px; cursor: pointer; letter-spacing: normal; }
.se-seg button + button { border-left: 1px solid var(--formBorder); }
.se-seg button.on { background: var(--primary-button, var(--primary-color)); color: #fff; }
.se-sec { font-size: 13px; font-weight: 500; color: var(--primary-color); margin: 18px 0 10px; }
.se-empty { font-size: 12px; color: gray; margin: 0 0 10px; }
.se-item { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
.se-src { flex: 1 1 190px; min-width: 0; }
.se-fn { flex: 0 0 90px; }
.se-affix { flex: 0 0 54px; text-align: center; }
.se-cap { flex: 1 1 130px; min-width: 0; }
.se-preview { flex: 0 0 auto; font-size: 11.5px; color: gray; font-variant-numeric: tabular-nums; }
.se-del { border: none; background: none; color: gray; cursor: pointer; padding: 2px; display: flex; flex-shrink: 0; }
.se-del:hover { color: var(--primary-color); }
.se-add { box-sizing: border-box; font-size: 12px; padding: 6px 14px; border: 1px dashed var(--formBorder); border-radius: 6px; background: none; color: var(--primary-color); cursor: pointer; letter-spacing: normal; }
.se-add:disabled { color: gray; cursor: default; }
.se-note { font-size: 12px; color: gray; line-height: 1.6; margin: 14px 0 0; }
.se-actions { display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--calendarBorder); }
.se-btn { font-size: 13px; padding: 8px 18px; border-radius: 7px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; letter-spacing: normal; }
.se-btn:hover { background: var(--bg3); }
</style>
