<template>
    <Modal persist @close="emit('close')">
        <template #title>CSV出力</template>
        <template #content>
            <div class="ce-group">
                <span class="ce-label">文字コード</span>
                <div class="ce-seg">
                    <button type="button" :class="{ on: encoding === 'utf8' }" @click="encoding = 'utf8'">Unicode (UTF-8)</button>
                    <button type="button" :class="{ on: encoding === 'sjis' }" @click="encoding = 'sjis'">Shift-JIS</button>
                </div>
            </div>

            <div class="ce-group">
                <span class="ce-label">出力範囲</span>
                <div class="ce-seg">
                    <button type="button" :class="{ on: scope === 'all' }" @click="scope = 'all'">すべての項目</button>
                    <button type="button" :class="{ on: scope === 'table' }" @click="scope = 'table'" :disabled="!tableFields.length">テーブルのみ</button>
                </div>
            </div>

            <div v-if="scope === 'table'" class="ce-group">
                <span class="ce-label">テーブル項目</span>
                <select v-model.number="tableFieldId" class="custom-a-input !box-border ce-select">
                    <option v-for="f in tableFields" :key="f.id" :value="f.id">{{ f.label }}</option>
                </select>
            </div>

            <p class="ce-note">出力対象のレコードは現在表示中のビュー設定（項目・絞り込み・並び替え）に従います。フィルターや並び替えを操作している場合はその内容が優先されます。</p>

            <div class="ce-actions">
                <button class="ce-btn" @click="emit('close')">キャンセル</button>
                <button class="ce-btn ce-primary" :disabled="scope === 'table' && !tableFieldId" @click="run">出力する</button>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { FlowField } from '@/types/flow'
import Modal from '@/components/Global/Modal.vue'

const props = defineProps<{
    fields: FlowField[]
    buildUrl: (opts: { encoding: 'utf8' | 'sjis'; scope: 'all' | 'table'; tableFieldId: number | null }) => string
}>()
const emit = defineEmits<{ close: [] }>()

const encoding = ref<'utf8' | 'sjis'>('utf8')
const scope = ref<'all' | 'table'>('all')
const tableFields = computed(() => props.fields.filter((f) => f.input_type === 'table' && f.id))
const tableFieldId = ref<number | null>(null)
if (tableFields.value.length) tableFieldId.value = tableFields.value[0].id!

const run = () => {
    if (scope.value === 'table' && !tableFieldId.value) return
    window.location.href = props.buildUrl({ encoding: encoding.value, scope: scope.value, tableFieldId: scope.value === 'table' ? tableFieldId.value : null })
    emit('close')
}
</script>

<style scoped>
.ce-group { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.ce-label { font-size: 12px; color: gray; flex-shrink: 0; min-width: 70px; }
.ce-seg { display: inline-flex; border: 1px solid var(--formBorder); border-radius: 7px; overflow: hidden; }
.ce-seg button { border: none; background: var(--background-color); color: var(--primary-color); font-size: 12px; padding: 7px 14px; cursor: pointer; }
.ce-seg button + button { border-left: 1px solid var(--formBorder); }
.ce-seg button.on { background: var(--primary-button, var(--primary-color)); color: #fff; }
.ce-seg button:disabled { opacity: 0.4; cursor: default; }
.ce-select { min-width: 200px; }
.ce-note { font-size: 12px; color: gray; line-height: 1.6; margin: 4px 0 0; }
.ce-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--calendarBorder); }
.ce-btn { font-size: 13px; padding: 8px 18px; border-radius: 7px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; }
.ce-btn:hover { background: var(--bg3); }
.ce-primary { background: var(--primary-button, var(--primary-color)); color: #fff; border-color: transparent; }
.ce-primary:hover { background: var(--primary-button, var(--primary-color)); opacity: 0.88; }
.ce-primary:disabled { opacity: 0.5; cursor: default; }
</style>
