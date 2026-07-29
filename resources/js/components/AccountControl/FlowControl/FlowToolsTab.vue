<template>
    <div class="flow-tools-tab">
        <!-- root: one block per tool kind. Kinds come from TOOL_KINDS, so a future tool needs an
             entry there plus its own screen — nothing here changes. -->
        <template v-if="!kind">
            <div class="tt-intro">
                <b>ツール</b>
                <p>アプリに追加できる拡張機能です。使いたいツールを選んでください。</p>
            </div>
            <div class="tt-grid">
                <button v-for="k in TOOL_KINDS" :key="k.route" type="button" class="tt-card" @click="openKind(k.route)">
                    <span class="tt-card-ico"><FlowFieldIcon :type="k.icon" :size="22" /></span>
                    <span class="tt-card-name">{{ k.label }}</span>
                    <span class="tt-card-desc">{{ k.desc }}</span>
                    <span class="tt-card-count">{{ countOf(k.type) ? `${countOf(k.type)} 件設定済み` : '未設定' }}</span>
                </button>
            </div>
        </template>

        <!-- one kind's own screen -->
        <template v-else>
            <div class="tt-head">
                <button type="button" class="tt-back" @click="openKind(null)">&lsaquo; ツール</button>
                <b class="tt-title">{{ kind.label }}</b>
            </div>
            <div class="tt-intro">
                <p>{{ kind.desc }}</p>
            </div>

            <div v-if="rows.length" class="tt-list">
                <div v-for="row in rows" :key="row.i" class="tt-row" :class="{ off: !row.tool.is_active }">
                    <span class="tt-ico"><FlowFieldIcon :type="kind.icon" :size="18" /></span>
                    <div class="tt-main">
                        <div class="tt-name">{{ row.tool.name }}</div>
                        <div class="tt-meta">{{ metaOf(row.tool) }}</div>
                    </div>
                    <span class="flow-sw" :class="{ on: row.tool.is_active }" @click="row.tool.is_active = !row.tool.is_active" title="有効/無効"></span>
                    <button class="tt-btn" @click="openDesigner(row.i)">{{ kind.type === 'slot' ? '設定' : 'デザイン' }}</button>
                    <button class="tt-del" @click="def.tools.splice(row.i, 1)" title="削除"><CloseIcon size="10" /></button>
                </div>
            </div>
            <p v-else class="tt-empty">まだありません。下のボタンから追加してください。</p>

            <button class="tt-add" @click="addOfKind">＋ {{ kind.label }}を追加</button>
        </template>

        <template v-if="editingIndex !== null && def.tools[editingIndex]">
            <FlowSlotEditor
                v-if="def.tools[editingIndex].tool_type === 'slot'"
                :tool="def.tools[editingIndex]"
                :def="def"
                @close="editingIndex = null"
            />
            <FlowPdfDesigner v-else :tool="def.tools[editingIndex]" :def="def" @close="editingIndex = null" />
        </template>
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { BuilderDefinition, FlowAppTool, FlowOptionUser } from '@/types/flow'
import { TOOL_KINDS, emptyPdfTemplate, emptySlot, pdfConfig, slotConfig, toolKindByRoute } from '@/types/flow'
import FlowFieldIcon from './FlowFieldIcon.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import FlowPdfDesigner from './FlowPdfDesigner.vue'
import FlowSlotEditor from './FlowSlotEditor.vue'

const props = defineProps<{ def: BuilderDefinition; users: FlowOptionUser[] }>()

const route = useRoute()
const router = useRouter()
const editingIndex = ref<number | null>(null)

/** Which kind's screen is open — driven by the URL, so a screen is linkable and back/forward work. */
const kind = computed(() => toolKindByRoute(route.params.sub as string | undefined))
const openKind = (sub: string | null) => {
    editingIndex.value = null
    router.replace({
        name: 'flow-builder',
        params: { ...route.params, tab: 'tools', sub: sub ?? undefined },
        query: route.query,
    })
}
// an unknown …/tools/xxx renders the root, so normalise the URL to match. immediate: true because
// a watcher alone misses the case where the bad segment was in the address on first load.
watch(kind, (k) => { if (!k && route.params.sub) openKind(null) }, { immediate: true })

/** Tools of the open kind, carrying their index in def.tools so edit/delete still address the real row. */
const rows = computed(() =>
    props.def.tools
        .map((tool, i) => ({ tool, i }))
        .filter((r) => r.tool.tool_type === kind.value?.type),
)
const countOf = (type: string) => props.def.tools.filter((t) => t.tool_type === type).length

const metaOf = (tool: FlowAppTool) =>
    tool.tool_type === 'slot'
        ? `${slotConfig(tool).position === 'top' ? '表の上' : '表の下'} · ${(slotConfig(tool).items || []).length} 項目`
        : `${(pdfConfig(tool).elements || []).length} 要素`

const addOfKind = () => {
    if (kind.value?.type === 'slot') {
        props.def.tools.push({ tool_type: 'slot', name: '集計', is_active: true, config: emptySlot() })
    } else {
        props.def.tools.push({ tool_type: 'pdf', name: '新しいPDF帳票', is_active: true, config: emptyPdfTemplate() })
    }
    editingIndex.value = props.def.tools.length - 1
}
const openDesigner = (i: number) => { editingIndex.value = i }
</script>

<style scoped>
.flow-tools-tab { display: flex; flex-direction: column; gap: 14px; max-width: 720px; }
.tt-intro { background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 14px 16px; }
.tt-intro b { font-size: 14px; }
.tt-intro p { font-size: 12px; color: gray; margin: 4px 0 0; line-height: 1.6; }
/* root: one card per tool kind */
.tt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; }
.tt-card { box-sizing: border-box; display: flex; flex-direction: column; align-items: flex-start; gap: 6px; text-align: left; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 16px; cursor: pointer; letter-spacing: normal; }
.tt-card:hover { border-color: var(--primary-color); }
.tt-card-ico { display: flex; margin-bottom: 2px; }
.tt-card-name { font-size: 14px; color: var(--primary-color); }
.tt-card-desc { font-size: 12px; color: gray; line-height: 1.6; }
.tt-card-count { font-size: 11.5px; color: gray; margin-top: 4px; }
/* a kind's own screen */
.tt-head { display: flex; align-items: center; gap: 12px; }
.tt-back { border: none; background: none; color: gray; font-size: 12px; cursor: pointer; padding: 0; letter-spacing: normal; }
.tt-back:hover { color: var(--primary-color); }
.tt-title { font-size: 14px; }
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
