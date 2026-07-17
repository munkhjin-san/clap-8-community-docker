<template>
    <div class="flow-tools-tab">
        <div class="tt-intro">
            <b>ツール</b>
            <p>アプリのレコードから帳票を作成するなどの拡張機能です。PDF帳票では、テンプレートをデザインしてレコードのPDFを出力できます。</p>
        </div>

        <div v-if="def.tools.length" class="tt-list">
            <div v-for="(tool, i) in def.tools" :key="i" class="tt-row" :class="{ off: !tool.is_active }">
                <span class="tt-ico"><FlowFieldIcon :type="'file'" :size="18" /></span>
                <div class="tt-main">
                    <div class="tt-name">{{ tool.name }}</div>
                    <div class="tt-meta">PDF帳票 · {{ (tool.config.elements || []).length }} 要素</div>
                </div>
                <span class="flow-sw" :class="{ on: tool.is_active }" @click="tool.is_active = !tool.is_active" title="有効/無効"></span>
                <button class="tt-btn" @click="openDesigner(i)">デザイン</button>
                <button class="tt-del" @click="def.tools.splice(i, 1)" title="削除"><CloseIcon size="10" /></button>
            </div>
        </div>
        <p v-else class="tt-empty">まだツールがありません。「＋ PDF帳票を追加」から作成してください。</p>

        <button class="tt-add" @click="addPdf">＋ PDF帳票を追加</button>

        <FlowPdfDesigner
            v-if="editingIndex !== null && def.tools[editingIndex]"
            :tool="def.tools[editingIndex]"
            :def="def"
            @close="editingIndex = null"
        />
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { ref } from 'vue'
import type { BuilderDefinition, FlowOptionUser } from '@/types/flow'
import { emptyPdfTemplate } from '@/types/flow'
import FlowFieldIcon from './FlowFieldIcon.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import FlowPdfDesigner from './FlowPdfDesigner.vue'

const props = defineProps<{ def: BuilderDefinition; users: FlowOptionUser[] }>()

const editingIndex = ref<number | null>(null)

const addPdf = () => {
    props.def.tools.push({ tool_type: 'pdf', name: '新しいPDF帳票', is_active: true, config: emptyPdfTemplate() })
    editingIndex.value = props.def.tools.length - 1
}
const openDesigner = (i: number) => { editingIndex.value = i }
</script>

<style scoped>
.flow-tools-tab { display: flex; flex-direction: column; gap: 14px; max-width: 720px; }
.tt-intro { background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 14px 16px; }
.tt-intro b { font-size: 14px; }
.tt-intro p { font-size: 12px; color: gray; margin: 4px 0 0; line-height: 1.6; }
.tt-list { display: flex; flex-direction: column; gap: 8px; }
.tt-row { display: flex; align-items: center; gap: 12px; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 12px 14px; }
.tt-row.off { opacity: .6; }
.tt-ico { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: var(--bg3); color: var(--primary-color); flex-shrink: 0; }
.tt-main { flex: 1; min-width: 0; }
.tt-name { font-size: 13.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-meta { font-size: 11px; color: gray; margin-top: 2px; }
.tt-btn { border: 1px solid var(--primary-color); color: var(--primary-color); background: none; border-radius: 7px; padding: 6px 14px; font-size: 12px; cursor: pointer; white-space: nowrap; }
.tt-btn:hover { background: var(--bg3); }
.tt-del { border: none; background: none; color: gray; cursor: pointer; padding: 5px; display: flex; }
.tt-del:hover { color: tomato; }
.tt-empty { font-size: 12px; color: gray; padding: 20px; text-align: center; border: 1.5px dashed var(--formBorder); border-radius: 10px; }
.tt-add { align-self: flex-start; border: 1px dashed var(--formBorder); background: none; border-radius: 8px; padding: 9px 18px; font-size: 13px; color: var(--primary-color); cursor: pointer; }
.tt-add:hover { background: var(--bg3); }
</style>
