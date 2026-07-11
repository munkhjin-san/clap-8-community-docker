<template>
    <Teleport to="body">
        <div class="pd-overlay">
            <!-- top bar -->
            <div class="pd-top">
                <input v-model="tool.name" class="pd-name" placeholder="帳票名">
                <div class="pd-top-right">
                    <div class="pd-orient" title="用紙の向き">
                        <button :class="{ on: tool.config.paper.orientation === 'portrait' }" @click="tool.config.paper.orientation = 'portrait'" title="縦">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3.5" y="1.5" width="7" height="11" rx="1"/></svg>
                        </button>
                        <button :class="{ on: tool.config.paper.orientation === 'landscape' }" @click="tool.config.paper.orientation = 'landscape'" title="横">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="1.5" y="3.5" width="11" height="7" rx="1"/></svg>
                        </button>
                    </div>
                    <button class="pd-preview" :disabled="previewing" @click="doPreview">{{ previewing ? '生成中…' : 'プレビュー' }}</button>
                    <button class="pd-done" @click="emit('close')">完了</button>
                    <button class="pd-back" @click="emit('close')" title="閉じる"><CloseIcon size="12" /></button>
                </div>
            </div>

            <div class="pd-body">
                <!-- palette -->
                <div class="pd-palette">
                    <div class="pd-pal-sec">要素を追加</div>
                    <button v-for="p in palette" :key="p.type" class="pd-chip" @click="addElement(p.type)">
                        <span class="pd-chip-ico" v-html="p.icon"></span>{{ p.label }}
                    </button>
                    <p class="pd-hint">ドラッグで移動、右下で大きさ変更。</p>
                </div>

                <!-- canvas -->
                <div class="pd-canvas-wrap" @pointerdown.self="selectedId = null">
                    <div
                        class="pd-page"
                        :style="{ width: pageW * scale + 'px', height: pageH * scale + 'px' }"
                        @pointerdown.self="selectedId = null"
                    >
                        <div
                            v-for="el in elements"
                            :key="el.id"
                            class="pd-el"
                            :class="{ sel: selectedId === el.id, ['t-' + el.type]: true }"
                            :style="elStyle(el)"
                            @pointerdown.stop="startDrag($event, el)"
                        >
                            <div class="pd-el-inner" :style="innerStyle(el)">
                                <template v-if="el.type === 'text'">{{ el.text || 'テキスト' }}</template>
                                <template v-else-if="el.type === 'field'">〈{{ fieldLabel(el.fieldKey) }}〉</template>
                                <template v-else-if="el.type === 'today'">{{ todayPreview(el) }}</template>
                                <template v-else-if="el.type === 'image'">
                                    <img v-if="el.src" :src="el.src" style="width:100%;height:100%;object-fit:contain;">
                                    <span v-else class="pd-ph">画像</span>
                                </template>
                                <template v-else-if="el.type === 'table'">
                                    <table class="pd-mini">
                                        <thead><tr><th v-for="(c, ci) in (el.columns || [])" :key="ci">{{ c.label || '列' }}</th><th v-if="!(el.columns || []).length">明細列</th></tr></thead>
                                        <tbody><tr v-for="r in 2" :key="r"><td v-for="(c, ci) in (el.columns || [])" :key="ci">—</td><td v-if="!(el.columns || []).length">—</td></tr></tbody>
                                    </table>
                                </template>
                            </div>
                            <span v-if="selectedId === el.id" class="pd-resize" @pointerdown.stop="startResize($event, el)"></span>
                        </div>
                    </div>
                </div>

                <!-- inspector -->
                <div class="pd-insp">
                    <template v-if="sel">
                        <div class="pd-insp-h">{{ palLabel(sel.type) }}<button class="pd-el-del" @click="removeEl(sel.id)" title="削除"><CloseIcon size="10" /></button></div>

                        <!-- geometry -->
                        <div class="pd-grid4">
                            <label>X<input type="number" v-model.number="sel.x"></label>
                            <label>Y<input type="number" v-model.number="sel.y"></label>
                            <label>幅<input type="number" v-model.number="sel.w"></label>
                            <label>高<input type="number" v-model.number="sel.h"></label>
                        </div>

                        <template v-if="sel.type === 'text'">
                            <label class="pd-f">テキスト<textarea v-model="sel.text" rows="3"></textarea></label>
                        </template>

                        <template v-else-if="sel.type === 'field'">
                            <label class="pd-f">項目
                                <select v-model="sel.fieldKey">
                                    <option v-for="f in valueFields" :key="f.key" :value="f.key">{{ f.label }}</option>
                                </select>
                            </label>
                            <label class="pd-f">表示形式
                                <select v-model="fmtKind">
                                    <option value="text">そのまま</option>
                                    <option value="number">数値（カンマ区切り）</option>
                                    <option value="date">日付</option>
                                </select>
                            </label>
                            <label v-if="fmtKind === 'date'" class="pd-f">日付書式
                                <select v-model="datePattern">
                                    <option value="Y年n月j日">2026年8月1日</option>
                                    <option value="Y/m/d">2026/08/01</option>
                                    <option value="Y-m-d">2026-08-01</option>
                                </select>
                            </label>
                            <div class="pd-grid2">
                                <label>前置き<input v-model="sel.prefix" placeholder="¥ など"></label>
                                <label>後置き<input v-model="sel.suffix" placeholder="円 など"></label>
                            </div>
                        </template>

                        <template v-else-if="sel.type === 'today'">
                            <label class="pd-f">日付書式
                                <select v-model="datePattern">
                                    <option value="Y年n月j日">2026年8月1日</option>
                                    <option value="Y/m/d">2026/08/01</option>
                                    <option value="Y-m-d">2026-08-01</option>
                                </select>
                            </label>
                        </template>

                        <template v-else-if="sel.type === 'image'">
                            <label class="pd-f">画像
                                <input type="file" accept="image/*" @change="onImageUpload">
                            </label>
                        </template>

                        <template v-else-if="sel.type === 'box' || sel.type === 'line'">
                            <div class="pd-grid2">
                                <label>線の太さ<input type="number" v-model.number="borderWidth"></label>
                                <label>線の色<input type="color" v-model="borderColor"></label>
                            </div>
                            <div v-if="sel.type === 'box'" class="pd-grid2">
                                <label>塗り<input type="color" v-model="fill"></label>
                                <label>角丸<input type="number" v-model.number="radius"></label>
                            </div>
                        </template>

                        <template v-else-if="sel.type === 'table'">
                            <label class="pd-f">明細の元データ（テーブル項目）
                                <select v-model="sel.sourceFieldKey" @change="onTableSourceChange">
                                    <option v-for="f in tableFields" :key="f.key" :value="f.key">{{ f.label }}</option>
                                </select>
                            </label>
                            <p v-if="!tableFields.length" class="pd-warn">フォームに「テーブル」項目がありません。先に追加してください。</p>
                            <div class="pd-cols">
                                <div class="pd-cols-h">表示する列</div>
                                <div v-for="(c, ci) in (sel.columns || [])" :key="ci" class="pd-col-row">
                                    <select v-model="c.colKey" class="pd-col-src">
                                        <option v-for="sc in sourceColumns" :key="sc.key" :value="sc.key">{{ sc.label || sc.key }}</option>
                                    </select>
                                    <input v-model="c.label" class="pd-col-lbl" placeholder="見出し">
                                    <select v-model="c.align" class="pd-col-al"><option value="left">左</option><option value="center">中</option><option value="right">右</option></select>
                                    <input type="number" v-model.number="c.width" class="pd-col-w" placeholder="%">
                                    <button class="pd-col-del" @click="sel.columns!.splice(ci, 1)"><CloseIcon size="8" /></button>
                                </div>
                                <button class="pd-col-add" :disabled="!sourceColumns.length" @click="addColumn">＋ 列を追加</button>
                            </div>
                            <label class="pd-f">合計に使う金額列
                                <select v-model="sel.amountColKey">
                                    <option :value="undefined">（なし）</option>
                                    <option v-for="sc in sourceColumns" :key="sc.key" :value="sc.key">{{ sc.label || sc.key }}</option>
                                </select>
                            </label>
                            <div class="pd-toggles">
                                <label class="pd-ck"><input type="checkbox" v-model="sel.showSubtotal"> 小計</label>
                                <label class="pd-ck"><input type="checkbox" v-model="sel.showTax"> 消費税</label>
                                <label class="pd-ck"><input type="checkbox" v-model="sel.showTotal"> 合計</label>
                            </div>
                            <label class="pd-f" v-if="sel.showTax">税率（%）<input type="number" v-model.number="taxPct"></label>
                        </template>

                        <!-- text styling (text / field / date) -->
                        <template v-if="sel.type === 'text' || sel.type === 'field' || sel.type === 'today'">
                            <div class="pd-style">
                                <label>文字<input type="number" v-model.number="fontSize"></label>
                                <button class="pd-tb" :class="{ on: sel.style?.bold }" @click="toggleBold">B</button>
                                <select v-model="align"><option value="left">左</option><option value="center">中</option><option value="right">右</option></select>
                                <input type="color" v-model="color">
                            </div>
                        </template>
                    </template>

                    <template v-else>
                        <div class="pd-insp-h">ページ設定</div>
                        <label class="pd-f">ファイル名パターン
                            <input v-model="tool.config.filename" placeholder="請求書_{seq}">
                        </label>
                        <p class="pd-hint">{seq}=レコード番号 / {id}=ID / {app}=アプリ名</p>
                        <p class="pd-hint">要素を選択すると、その設定が表示されます。</p>
                    </template>
                </div>
            </div>

            <!-- preview modal -->
            <div v-if="previewUrl" class="pd-preview-modal" @click.self="closePreview">
                <div class="pd-preview-box">
                    <div class="pd-preview-bar"><b>プレビュー</b><button class="pd-back" @click="closePreview"><CloseIcon size="12" /></button></div>
                    <iframe :src="previewUrl" class="pd-preview-frame"></iframe>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { BuilderDefinition, FlowAppTool, PdfElement, PdfElementType } from '@/types/flow'
import { isLayoutType } from '@/types/flow'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'

const props = defineProps<{ tool: FlowAppTool; def: BuilderDefinition }>()
const emit = defineEmits<{ close: [] }>()

const api = useApi()
const dialog = useDialog()

const pageW = computed(() => (props.tool.config.paper.orientation === 'landscape' ? 1123 : 794))
const pageH = computed(() => (props.tool.config.paper.orientation === 'landscape' ? 794 : 1123))
const scale = ref(0.62)
const selectedId = ref<string | null>(null)

const elements = computed(() => props.tool.config.elements)
const sel = computed<PdfElement | null>(() => elements.value.find((e) => e.id === selectedId.value) ?? null)

const valueFields = computed(() => props.def.fields.filter((f) => !isLayoutType(f.input_type)))
const tableFields = computed(() => props.def.fields.filter((f) => f.input_type === 'table'))
const sourceColumns = computed(() => {
    const f = props.def.fields.find((x) => x.key === sel.value?.sourceFieldKey)
    return (f?.validation?.columns as any[]) ?? []
})

const palette: { type: PdfElementType; label: string; icon: string }[] = [
    { type: 'text', label: '静的テキスト', icon: 'T' },
    { type: 'field', label: 'フィールド差込', icon: '⧉' },
    { type: 'today', label: '現在日付', icon: '日' },
    { type: 'table', label: '明細テーブル', icon: '☰' },
    { type: 'image', label: '画像', icon: '▨' },
    { type: 'box', label: '枠', icon: '▢' },
    { type: 'line', label: '罫線', icon: '―' },
]
const palLabel = (t: string) => palette.find((p) => p.type === t)?.label ?? t

let seq = 0
const uid = () => `e_${Date.now()}_${seq++}`

const addElement = (type: PdfElementType) => {
    const base: any = { id: uid(), type, x: 48, y: 48, w: 300, h: 40 }
    if (type === 'text') Object.assign(base, { text: 'テキスト', style: { fontSize: 16, align: 'left', color: '#111827' } })
    if (type === 'field') Object.assign(base, { w: 260, h: 28, fieldKey: valueFields.value[0]?.key, style: { fontSize: 13, align: 'left', color: '#111827' } })
    if (type === 'today') Object.assign(base, { w: 200, h: 26, format: { kind: 'date', pattern: 'Y年n月j日' }, style: { fontSize: 13, align: 'left', color: '#111827' } })
    if (type === 'image') Object.assign(base, { w: 150, h: 60, src: '' })
    if (type === 'box') Object.assign(base, { w: 220, h: 120, style: { borderWidth: 1, borderColor: '#334155', fill: 'transparent', radius: 0 } })
    if (type === 'line') Object.assign(base, { w: 300, h: 1, style: { borderWidth: 1, borderColor: '#334155' } })
    if (type === 'table') {
        const srcKey = tableFields.value[0]?.key
        Object.assign(base, {
            y: 320, w: 700, h: 260,
            sourceFieldKey: srcKey,
            columns: columnsFromSource(srcKey), amountColKey: guessAmountCol(srcKey),
            showSubtotal: true, showTax: true, showTotal: true, tax: { rate: 0.1 }, currency: '¥',
            borderColor: '#c9cfd8', fontSize: 11,
        })
    }
    elements.value.push(base)
    selectedId.value = base.id
}

// derive designer columns from a table field's own column definitions
const sourceColsOf = (srcKey?: string): any[] => {
    const f = props.def.fields.find((x) => x.key === srcKey)
    return (f?.validation?.columns as any[]) ?? []
}
const columnsFromSource = (srcKey?: string) => sourceColsOf(srcKey).map((c: any) => ({
    colKey: c.key,
    label: c.label || c.key,
    align: ['number', 'formula', 'calc'].includes(c.input_type) ? 'right' as const : 'left' as const,
}))
const guessAmountCol = (srcKey?: string): string | undefined =>
    sourceColsOf(srcKey).find((c: any) => ['number', 'formula', 'calc'].includes(c.input_type))?.key
const removeEl = (id: string) => {
    const i = elements.value.findIndex((e) => e.id === id)
    if (i >= 0) elements.value.splice(i, 1)
    selectedId.value = null
}

const fieldLabel = (key?: string) => props.def.fields.find((f) => f.key === key)?.label ?? '未選択'

// preview of the 現在日付 element on the canvas (same patterns the server renders)
const todayPreview = (el: PdfElement): string => {
    const now = new Date()
    const y = now.getFullYear(), m = now.getMonth() + 1, d = now.getDate()
    const p2 = (n: number) => String(n).padStart(2, '0')
    switch (el.format?.pattern) {
        case 'Y/m/d': return `${y}/${p2(m)}/${p2(d)}`
        case 'Y-m-d': return `${y}-${p2(m)}-${p2(d)}`
        default: return `${y}年${m}月${d}日`
    }
}

/* ---- drag / resize (canvas coords = screen / scale) ---- */
const startDrag = (e: PointerEvent, el: PdfElement) => {
    selectedId.value = el.id
    const sx = e.clientX, sy = e.clientY, ox = el.x, oy = el.y
    const move = (ev: PointerEvent) => {
        el.x = Math.max(0, Math.round((ox + (ev.clientX - sx) / scale.value) / 4) * 4)
        el.y = Math.max(0, Math.round((oy + (ev.clientY - sy) / scale.value) / 4) * 4)
    }
    const up = () => { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', up) }
    window.addEventListener('pointermove', move)
    window.addEventListener('pointerup', up)
}
const startResize = (e: PointerEvent, el: PdfElement) => {
    const sx = e.clientX, sy = e.clientY, ow = el.w, oh = el.h
    const move = (ev: PointerEvent) => {
        el.w = Math.max(16, Math.round((ow + (ev.clientX - sx) / scale.value) / 4) * 4)
        el.h = Math.max(el.type === 'line' ? 1 : 16, Math.round((oh + (ev.clientY - sy) / scale.value) / 4) * 4)
    }
    const up = () => { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', up) }
    window.addEventListener('pointermove', move)
    window.addEventListener('pointerup', up)
}

/* ---- rendering ---- */
const elStyle = (el: PdfElement) => ({
    left: el.x * scale.value + 'px',
    top: el.y * scale.value + 'px',
    width: el.w * scale.value + 'px',
    height: el.h * scale.value + 'px',
})
const innerStyle = (el: PdfElement) => {
    const s: any = {}
    if (el.type === 'text' || el.type === 'field' || el.type === 'today') {
        s.fontSize = ((el.style?.fontSize ?? 13) * scale.value) + 'px'
        s.textAlign = el.style?.align ?? 'left'
        s.color = el.style?.color ?? '#111'
        s.fontWeight = el.style?.bold ? '700' : '400'
    }
    if (el.type === 'box') {
        s.border = `${el.style?.borderWidth ?? 1}px solid ${el.style?.borderColor ?? '#334155'}`
        s.background = el.style?.fill ?? 'transparent'
        s.borderRadius = (el.style?.radius ?? 0) + 'px'
    }
    if (el.type === 'line') s.borderTop = `${el.style?.borderWidth ?? 1}px solid ${el.style?.borderColor ?? '#334155'}`
    return s
}

/* ---- style bridges for the selected element ---- */
const styleProp = (key: string, def: any) => computed({
    get: () => (sel.value?.style as any)?.[key] ?? def,
    set: (v) => { if (sel.value) { sel.value.style = { ...(sel.value.style || {}), [key]: v } } },
})
const fontSize = styleProp('fontSize', 13)
const align = styleProp('align', 'left')
const color = styleProp('color', '#111827')
const borderWidth = styleProp('borderWidth', 1)
const borderColor = styleProp('borderColor', '#334155')
const fill = styleProp('fill', 'transparent')
const radius = styleProp('radius', 0)
const toggleBold = () => { if (sel.value) sel.value.style = { ...(sel.value.style || {}), bold: !sel.value.style?.bold } }

const fmtKind = computed({
    get: () => sel.value?.format?.kind ?? 'text',
    set: (v: any) => { if (sel.value) sel.value.format = { ...(sel.value.format || {}), kind: v } },
})
const datePattern = computed({
    get: () => sel.value?.format?.pattern ?? 'Y年n月j日',
    set: (v: string) => { if (sel.value) sel.value.format = { ...(sel.value.format || {}), pattern: v } },
})
const taxPct = computed({
    get: () => Math.round((sel.value?.tax?.rate ?? 0.1) * 100),
    set: (v: number) => { if (sel.value) sel.value.tax = { rate: (Number(v) || 0) / 100 } },
})

const onTableSourceChange = () => {
    if (!sel.value) return
    sel.value.columns = columnsFromSource(sel.value.sourceFieldKey)
    sel.value.amountColKey = guessAmountCol(sel.value.sourceFieldKey)
}
const addColumn = () => {
    if (!sel.value) return
    const used = new Set((sel.value.columns || []).map((c) => c.colKey))
    const next = sourceColumns.value.find((c: any) => !used.has(c.key)) ?? sourceColumns.value[0]
    if (!next) return
    sel.value.columns = [...(sel.value.columns || []), { colKey: next.key, label: next.label || next.key, align: 'left', width: undefined as any }]
}

const onImageUpload = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file || !sel.value) return
    const reader = new FileReader()
    reader.onload = () => { if (sel.value) sel.value.src = String(reader.result) }
    reader.readAsDataURL(file)
}

/* ---- preview ---- */
const previewing = ref(false)
const previewUrl = ref<string | null>(null)
const doPreview = async () => {
    if (!props.def.id) { dialog.toast('先にアプリを保存してください。'); return }
    previewing.value = true
    try {
        const res: any = await api.post('/flow_tool_pdf_preview',
            { flow_definition_id: props.def.id, config: props.tool.config },
            { rawResponse: true, silent: true },
            { responseType: 'blob' })
        previewUrl.value = URL.createObjectURL(res.data)
    } catch (err: any) {
        dialog.toast(err?.response?.data?.message || 'プレビューを生成できませんでした。')
    } finally {
        previewing.value = false
    }
}
const closePreview = () => { if (previewUrl.value) URL.revokeObjectURL(previewUrl.value); previewUrl.value = null }
</script>

<style scoped>
.pd-overlay { position: fixed; inset: 0; z-index: 100000; background: var(--bg3); display: flex; flex-direction: column; }
.pd-top { display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: var(--background-color); border-bottom: 1px solid var(--calendarBorder); }
.pd-back { border: none; background: none; color: gray; cursor: pointer; padding: 6px; display: flex; border-radius: 6px; }
.pd-back:hover { background: var(--bg3); }
.pd-name { font-size: 15px; font-weight: 600; border: 1px solid var(--formBorder); border-radius: 7px; padding: 6px 10px; background: var(--background-color); width: 280px; max-width: 40vw; box-sizing: border-box !important; }
.pd-name:focus { border-color: var(--primary-color); outline: none; }
.pd-top-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.pd-orient { display: inline-flex; border: 1px solid var(--formBorder); border-radius: 7px; overflow: hidden; }
.pd-orient button { border: none; background: var(--background-color); padding: 7px 10px; cursor: pointer; color: gray; display: flex; align-items: center; }
.pd-orient button.on { background: var(--bg3); color: var(--primary-color); }
.pd-preview { border: 1px solid var(--primary-color); color: var(--primary-color); background: none; border-radius: 7px; padding: 7px 16px; font-size: 13px; cursor: pointer; }
.pd-done { border: none; background: var(--primary-button, var(--primary-color)); color: #fff; border-radius: 7px; padding: 8px 20px; font-size: 13px; cursor: pointer; }

.pd-body { flex: 1; display: flex; min-height: 0; }
.pd-palette { width: 180px; flex-shrink: 0; border-right: 1px solid var(--calendarBorder); background: var(--background-color); padding: 12px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
.pd-pal-sec { font-size: 11px; color: gray; margin-bottom: 2px; }
.pd-chip { display: flex; align-items: center; gap: 8px; border: 1px solid var(--calendarBorder); background: var(--background-color); border-radius: 7px; padding: 8px 10px; font-size: 13px; cursor: pointer; text-align: left; }
.pd-chip:hover { border-color: var(--primary-color); background: var(--bg3); }
.pd-chip-ico { width: 18px; text-align: center; color: var(--primary-color); font-weight: 700; }
.pd-hint { font-size: 10.5px; color: gray; line-height: 1.5; margin-top: 6px; }

.pd-canvas-wrap { flex: 1; overflow: auto; display: flex; justify-content: center; padding: 24px; }
.pd-page { position: relative; background: #fff; box-shadow: 0 2px 16px rgba(0,0,0,.15); flex-shrink: 0; align-self: flex-start; }
.pd-el { position: absolute; box-sizing: border-box; cursor: move; outline: 1px dashed transparent; }
.pd-el:hover { outline-color: var(--formBorder); }
/* elements sit on the always-white paper, so use the (dark in both themes) button ink,
   not --primary-color which goes near-white in dark mode and vanishes on the page. */
.pd-el.sel { outline: 1.5px solid var(--primary-button, var(--primary-color)); }
.pd-el-inner { width: 100%; height: 100%; overflow: hidden; line-height: 1.3; word-break: break-word; }
.pd-el.t-field .pd-el-inner { color: var(--primary-button, var(--primary-color)); }
.pd-ph { color: #b0b6c0; font-size: 11px; display: flex; align-items: center; justify-content: center; height: 100%; border: 1px dashed var(--formBorder); }
.pd-mini { width: 100%; border-collapse: collapse; font-size: 8px; }
.pd-mini th, .pd-mini td { border: 1px solid #d5d9e0; padding: 1px 3px; color: #6b7280; }
.pd-mini th { background: #f0f2f5; }
.pd-resize { position: absolute; right: -5px; bottom: -5px; width: 11px; height: 11px; background: var(--primary-button, var(--primary-color)); border: 2px solid #fff; border-radius: 50%; cursor: nwse-resize; }

.pd-insp { width: 280px; flex-shrink: 0; border-left: 1px solid var(--calendarBorder); background: var(--background-color); padding: 14px; overflow-y: auto; overflow-x: hidden; box-sizing: border-box !important; }
.pd-insp-h { display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 12px; }
.pd-el-del { border: none; background: none; color: gray; cursor: pointer; padding: 3px; display: flex; }
.pd-el-del:hover { color: tomato; }
.pd-grid4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; margin-bottom: 12px; }
.pd-grid4 label, .pd-grid2 label, .pd-f, .pd-style label { display: flex; flex-direction: column; gap: 3px; font-size: 10.5px; color: gray; min-width: 0; }
.pd-grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 10px; }
.pd-f { margin-bottom: 10px; }
.pd-insp input, .pd-insp select, .pd-insp textarea { border: 1px solid var(--formBorder); border-radius: 6px; padding: 6px 8px; font-size: 12.5px; background: var(--background-color); box-sizing: border-box !important; width: 100%; max-width: 100%; }
.pd-insp input[type=color] { padding: 2px; height: 30px; }
.pd-style { display: flex; align-items: flex-end; gap: 6px; margin-top: 6px; }
.pd-style label { flex: 0 0 56px; }
.pd-style select { width: auto; flex: 1; min-width: 0; }
.pd-style input[type=color] { flex: 0 0 40px; width: 40px; }
.pd-tb { border: 1px solid var(--formBorder); background: var(--background-color); border-radius: 6px; width: 30px; height: 30px; font-weight: 700; cursor: pointer; }
.pd-tb.on { background: var(--primary-button, var(--primary-color)); color: #fff; border-color: var(--primary-button, var(--primary-color)); }
.pd-warn { font-size: 11px; color: #e2574c; margin: 4px 0; }
.pd-cols { margin: 6px 0 10px; }
.pd-cols-h { font-size: 11px; color: gray; margin-bottom: 6px; }
.pd-col-row { display: flex; gap: 4px; margin-bottom: 5px; align-items: center; }
.pd-col-src { flex: 1.2; } .pd-col-lbl { flex: 1.2; } .pd-col-al { flex: 0 0 48px; } .pd-col-w { flex: 0 0 46px; }
.pd-col-del { border: none; background: none; color: gray; cursor: pointer; padding: 3px; display: flex; flex-shrink: 0; }
.pd-col-add { width: 100%; border: 1px dashed var(--formBorder); background: none; border-radius: 6px; padding: 6px; font-size: 12px; color: var(--primary-color); cursor: pointer; margin-top: 2px; }
.pd-toggles { display: flex; gap: 12px; margin-bottom: 10px; }
.pd-ck { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--primary-color); }
.pd-ck input { width: auto; }

.pd-preview-modal { position: fixed; inset: 0; z-index: 100001; background: rgba(15,20,30,.5); display: flex; align-items: center; justify-content: center; padding: 24px; }
.pd-preview-box { width: min(860px, 100%); height: 90vh; background: var(--background-color); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; }
.pd-preview-bar { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid var(--calendarBorder); font-size: 14px; }
.pd-preview-frame { flex: 1; border: none; width: 100%; }
</style>
