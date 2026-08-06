<template>
    <Teleport to="body">
        <div class="pd-overlay">
            <!-- top bar -->
            <div class="pd-top">
                <input v-model="tool.name" class="pd-name" placeholder="帳票名">
                <div class="pd-top-right">
                    <div class="pd-orient" title="用紙の向き">
                        <button :class="{ on: cfg.paper.orientation === 'portrait' }" @click="cfg.paper.orientation = 'portrait'" title="縦">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3.5" y="1.5" width="7" height="11" rx="1"/></svg>
                        </button>
                        <button :class="{ on: cfg.paper.orientation === 'landscape' }" @click="cfg.paper.orientation = 'landscape'" title="横">
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
                    <!-- ページの切り替え。1枚しか無いときも出す——増やせることが分かる場所がここしかない。 -->
                    <div class="pd-pages" @pointerdown.stop>
                        <button
                            v-for="p in pageCount"
                            :key="p"
                            class="pd-page-tab"
                            :class="{ on: p === currentPage, empty: countOnPage(p) === 0 }"
                            :title="`${p} ページ目`"
                            @click="goToPage(p)"
                        >{{ p }}</button>
                        <button class="pd-page-add" title="ページを追加" @click="addPage">＋</button>
                        <button
                            v-if="pageCount > 1"
                            class="pd-page-del"
                            title="このページを削除"
                            @click="removePage(currentPage)"
                        ><CloseIcon size="10" /></button>
                    </div>

                    <div
                        class="pd-page"
                        :style="{ width: pageW * scale + 'px', height: pageH * scale + 'px' }"
                        @pointerdown.self="selectedId = null"
                    >
                        <!-- 下敷き。要素より下に敷くだけで、掴めない（配置の邪魔をしない）。 -->
                        <canvas ref="bgCanvas" class="pd-bg"></canvas>

                        <div
                            v-for="el in pageElements"
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
                                    <img v-if="el.src" :src="el.src" draggable="false" style="width:100%;height:100%;object-fit:contain;pointer-events:none;-webkit-user-drag:none;">
                                    <span v-else class="pd-ph">画像</span>
                                </template>
                                <template v-else-if="el.type === 'table'">
                                    <table class="pd-mini" :class="{ 'pd-mini-noborder': el.showBorder === false }">
                                        <thead v-if="el.showHeader !== false"><tr><th v-for="(c, ci) in (el.columns || [])" :key="ci">{{ c.label || '列' }}</th><th v-if="!(el.columns || []).length">明細列</th></tr></thead>
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

                        <!-- 置くページ。作った後に「1枚目に戻したい」が必ず出るので、
                             ここから動かせるようにしておく。 -->
                        <label v-if="pageCount > 1" class="pd-f">ページ
                            <select :value="pageOf(sel)" @change="moveToPage(sel, Number(($event.target as HTMLSelectElement).value))">
                                <option v-for="p in pageCount" :key="p" :value="p">{{ p }} ページ目</option>
                            </select>
                        </label>

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
                                <FlowSearchSelect :model-value="sel.fieldKey ?? null" :options="valueFieldOptions" :clearable="false" placeholder="項目を選択" @update:model-value="(val) => sel!.fieldKey = String(val)" />
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
                                <FlowSearchSelect :model-value="sel.sourceFieldKey ?? null" :options="tableFieldOptions" :clearable="false" placeholder="テーブル項目を選択" @update:model-value="(val) => { sel!.sourceFieldKey = String(val); onTableSourceChange() }" />
                            </label>
                            <p v-if="!tableFields.length" class="pd-warn">フォームに「テーブル」項目がありません。先に追加してください。</p>
                            <div class="pd-cols">
                                <div class="pd-cols-h">表示する列</div>
                                <div v-for="(c, ci) in (sel.columns || [])" :key="ci" class="pd-col-row">
                                    <FlowSearchSelect class="pd-col-src" :model-value="c.colKey ?? null" :options="sourceColumnOptions" :clearable="false" placeholder="列" @update:model-value="(val) => onColSourceChange(c, String(val))" />
                                    <input v-model="c.label" class="pd-col-lbl" placeholder="見出し">
                                    <select v-model="c.align" class="pd-col-al"><option value="left">左</option><option value="center">中</option><option value="right">右</option></select>
                                    <input type="number" v-model.number="c.width" class="pd-col-w" placeholder="%">
                                    <button class="pd-col-del" @click="sel.columns!.splice(ci, 1)"><CloseIcon size="8" /></button>
                                </div>
                                <button class="pd-col-add" :disabled="!sourceColumns.length" @click="addColumn">＋ 列を追加</button>
                            </div>
                            <div class="pd-toggles">
                                <label class="pd-ck"><input type="checkbox" v-model="showHeaderModel"> 見出し行を表示</label>
                                <label class="pd-ck"><input type="checkbox" v-model="showBorderModel"> 罫線を表示</label>
                            </div>
                            <label class="pd-f">合計に使う金額列
                                <FlowSearchSelect :model-value="sel.amountColKey ?? null" :options="sourceColumnOptions" placeholder="（なし）" @update:model-value="(val) => sel!.amountColKey = val == null ? undefined : String(val)" />
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
                            <input v-model="cfg.filename" placeholder="請求書_{seq}">
                        </label>
                        <p class="pd-hint">{seq}=レコード番号 / {id}=ID / {app}=アプリ名</p>

                        <div class="pd-bg-sec">
                            <div class="pd-insp-h">下敷きPDF</div>
                            <template v-if="cfg.background">
                                <p class="pd-bg-name" :title="cfg.background.name">{{ cfg.background.name }}</p>
                                <p class="pd-hint">{{ cfg.background.pages }} ページ。各ページの下に、同じ番号のページが敷かれます。</p>
                                <button class="pd-bg-clear" @click="clearBackground">下敷きを外す</button>
                            </template>
                            <template v-else>
                                <label class="pd-f">
                                    <input type="file" accept="application/pdf" :disabled="bgUploading" @change="onBackgroundUpload">
                                </label>
                                <p class="pd-hint">既にあるPDF（契約書のひな形など）の上に、差込項目を置けます。</p>
                            </template>
                            <p v-if="bgUploading" class="pd-hint">読み込み中…</p>
                        </div>
                        <p class="pd-hint">全 {{ pageCount }} ページ（明細テーブルが長い場合は、出力時にさらに増えます）。</p>
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
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import type { BuilderDefinition, FlowAppTool, PdfElement, PdfElementType, PdfTemplate } from '@/types/flow'
import { isLayoutType, isSecretType, pdfElementPage, pdfPageCount } from '@/types/flow'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import FlowSearchSelect from './FlowSearchSelect.vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'

const props = defineProps<{ tool: FlowAppTool; def: BuilderDefinition }>()
// only ever opened for a PDF tool; narrow once instead of casting at every use
const cfg = computed<PdfTemplate>(() => props.tool.config as PdfTemplate)
const emit = defineEmits<{ close: [] }>()

const api = useApi()
const dialog = useDialog()

const pageW = computed(() => (cfg.value.paper.orientation === 'landscape' ? 1123 : 794))
const pageH = computed(() => (cfg.value.paper.orientation === 'landscape' ? 794 : 1123))
const scale = ref(0.62)
const selectedId = ref<string | null>(null)

const elements = computed(() => cfg.value.elements)
const sel = computed<PdfElement | null>(() => elements.value.find((e) => e.id === selectedId.value) ?? null)

/* ---- ページ ----
   page を持たない要素は1ページ目。単ページ時代のテンプレートがそのまま出るのはこの既定のおかげ。 */
const currentPage = ref(1)
const pageCount = computed(() => pdfPageCount(cfg.value))
const pageOf = (el: PdfElement) => pdfElementPage(el)
const pageElements = computed(() => elements.value.filter((e) => pageOf(e) === currentPage.value))
const countOnPage = (p: number) => elements.value.filter((e) => pageOf(e) === p).length

const goToPage = (p: number) => {
    currentPage.value = p
    // 選択したままページを移ると、見えない要素を編集し続けることになる
    if (sel.value && pageOf(sel.value) !== p) selectedId.value = null
}

/* ---- 下敷きPDF ----
   サーバは下敷きの1ページを用紙いっぱいに引き伸ばして敷く。ここも同じ合わせ方をしないと、
   画面で合わせた位置が出力でずれる。 */
const bgCanvas = ref<HTMLCanvasElement | null>(null)
const bgUploading = ref(false)
let pdfjs: any = null
let bgDoc: any = null

const bgUrl = computed(() => {
    const path = cfg.value.background?.path
    if (!path || !props.def.id) return null
    const hash = String(path.split('/').pop() ?? '').replace(/\.pdf$/, '')
    return `/flow_tool_background/${props.def.id}/${hash}`
})

const loadBackground = async () => {
    bgDoc = null
    if (!bgUrl.value) return paintBackground()
    try {
        // アプリに同梱せず、既に配信している pdf.js をそのまま使う（/pdf-reader は帳票以外でも使用中）
        // 型は無い（実行時に配信されるモジュール）。パスを変数にして、ビルド時の解決も型解決も外す。
        // **オリジンを明示する**：開発中は読み込み元がViteのサーバなので、相対のままだと
        // そちらの :5173 を探しに行って落ちる。配信しているのはアプリ側（Laravel）。
        const origin = window.location.origin
        const src = `${origin}/pdf-reader/build/pdf.mjs`
        pdfjs ??= await import(/* @vite-ignore */ src)
        pdfjs.GlobalWorkerOptions.workerSrc = `${origin}/pdf-reader/build/pdf.worker.mjs`
        bgDoc = await pdfjs.getDocument({ url: bgUrl.value, withCredentials: true }).promise
    } catch {
        dialog.toast('下敷きを表示できませんでした（出力には影響しません）。')
    }
    await paintBackground()
}

const paintBackground = async () => {
    const cv = bgCanvas.value
    if (!cv) return
    const dpr = window.devicePixelRatio || 1
    const cw = Math.round(pageW.value * scale.value * dpr)
    const ch = Math.round(pageH.value * scale.value * dpr)
    cv.width = cw
    cv.height = ch
    const ctx = cv.getContext('2d')
    if (!ctx) return
    ctx.clearRect(0, 0, cw, ch)
    if (!bgDoc || currentPage.value > bgDoc.numPages) return

    const page = await bgDoc.getPage(currentPage.value)
    // 一度そのままの縦横比で描いてから、用紙に合わせて引き伸ばす（サーバの UseTemplate と同じ）
    const natural = page.getViewport({ scale: 1 })
    const off = document.createElement('canvas')
    const s = cw / natural.width
    const vp = page.getViewport({ scale: s })
    off.width = Math.max(1, Math.round(vp.width))
    off.height = Math.max(1, Math.round(vp.height))
    await page.render({ canvasContext: off.getContext('2d')!, viewport: vp }).promise
    ctx.drawImage(off, 0, 0, cw, ch)
}

const onBackgroundUpload = async (e: Event) => {
    const input = e.target as HTMLInputElement
    const file = input.files?.[0]
    if (!file) return
    if (!props.def.id) { dialog.toast('先にアプリを保存してください。'); input.value = ''; return }

    bgUploading.value = true
    try {
        const fd = new FormData()
        fd.append('flow_definition_id', String(props.def.id))
        fd.append('file', file)
        const data: any = await api.post('/flow_tool_background', fd, { silent: true })
        cfg.value.background = data
        // 下敷きのページ数だけ、置き場を先に用意しておく
        if (data.pages > pageCount.value) cfg.value.paper.pages = data.pages
        await loadBackground()
    } catch (err: any) {
        dialog.toast(err?.response?.data?.message || '下敷きを読み込めませんでした。')
    } finally {
        bgUploading.value = false
        input.value = ''
    }
}

const clearBackground = async () => {
    if (!(await dialog.ask('下敷きを外します。配置した要素はそのまま残ります。よろしいですか？')).value) return
    delete cfg.value.background
    await loadBackground()
}

watch(bgUrl, loadBackground, { immediate: true })
watch([currentPage, pageW, pageH, scale], paintBackground)
onBeforeUnmount(() => { bgDoc?.destroy?.(); bgDoc = null })

const addPage = () => {
    cfg.value.paper.pages = pageCount.value + 1
    goToPage(cfg.value.paper.pages)
}

const moveToPage = (el: PdfElement, p: number) => {
    el.page = p
    goToPage(p)
    selectedId.value = el.id
}

/** ページを1枚消す。後ろのページは繰り上がる（番号に穴を残さない）。 */
const removePage = async (p: number) => {
    if (pageCount.value <= 1) return
    const n = countOnPage(p)
    if (n > 0 && !(await dialog.ask(`${p} ページ目の要素 ${n} 件も一緒に削除します。よろしいですか？`)).value) return

    cfg.value.elements = elements.value.filter((e) => pageOf(e) !== p)
    for (const el of cfg.value.elements) {
        const cur = pageOf(el)
        if (cur > p) el.page = cur - 1
    }
    cfg.value.paper.pages = Math.max(1, pageCount.value - 1)
    selectedId.value = null
    goToPage(Math.min(p, cfg.value.paper.pages))
}

const valueFields = computed(() => props.def.fields.filter((f) => !isLayoutType(f.input_type) && !isSecretType(f.input_type)))
const tableFields = computed(() => props.def.fields.filter((f) => f.input_type === 'table'))
const sourceColumns = computed(() => {
    const f = props.def.fields.find((x) => x.key === sel.value?.sourceFieldKey)
    return (f?.validation?.columns as any[]) ?? []
})

// option lists for the searchable field pickers
const valueFieldOptions = computed(() => valueFields.value.map((f) => ({ value: f.key, label: f.label })))
const tableFieldOptions = computed(() => tableFields.value.map((f) => ({ value: f.key, label: f.label })))
const sourceColumnOptions = computed(() => sourceColumns.value.map((sc: any) => ({ value: sc.key, label: sc.label || sc.key })))

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
    const base: any = { id: uid(), type, page: currentPage.value, x: 48, y: 48, w: 300, h: 40 }
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
            borderColor: '#c9cfd8', fontSize: 11, showHeader: true, showBorder: true,
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
    e.preventDefault() // stop the browser starting a text selection / native drag while dragging
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
    e.preventDefault()
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
// direct (non-style) element property with a default — needed so a table saved before showHeader/
// showBorder existed still reads as "on" (matches the render service's ?? true fallback) instead of
// the checkbox showing unchecked for old data that has never actually hidden them.
const elProp = (key: 'showHeader' | 'showBorder', def: boolean) => computed({
    get: () => (sel.value as any)?.[key] ?? def,
    set: (v) => { if (sel.value) (sel.value as any)[key] = v },
})
const showHeaderModel = elProp('showHeader', true)
const showBorderModel = elProp('showBorder', true)
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

// Display name of a source column key.
const colSourceName = (key?: string): string => {
    const sc = sourceColumns.value.find((c: any) => c.key === key)
    return sc ? (sc.label || sc.key) : ''
}
// Picking a different source column auto-fills the heading with that column's name — unless the
// user has typed a custom heading (then it's left alone).
const onColSourceChange = (col: any, newKey: string) => {
    const prevName = colSourceName(col.colKey)
    col.colKey = newKey
    if (!col.label || col.label === prevName) {
        col.label = colSourceName(newKey)
    }
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
            { flow_definition_id: props.def.id, config: cfg.value },
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
/* Teleported to <body>, i.e. outside the app's theme container, so it doesn't inherit the
   themed text color and would fall back to black — set it explicitly for the whole overlay. */
.pd-overlay { position: fixed; inset: 0; z-index: 100000; background: var(--bg3); color: var(--primary-color); display: flex; flex-direction: column; }
.pd-top { display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: var(--background-color); border-bottom: 1px solid var(--calendarBorder); }
.pd-back { border: none; background: none; color: gray; cursor: pointer; padding: 6px; display: flex; border-radius: 6px; }
.pd-back:hover { background: var(--bg3); }
.pd-name { font-size: 15px; font-weight: 600; border: 1px solid var(--formBorder); border-radius: 7px; padding: 6px 10px; background: var(--background-color); color: var(--primary-color); width: 280px; max-width: 40vw; box-sizing: border-box !important; }
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

.pd-canvas-wrap { flex: 1; overflow: auto; display: flex; flex-direction: column; align-items: center; padding: 24px; }
.pd-pages { display: flex; align-items: center; gap: 4px; margin-bottom: 12px; flex-wrap: wrap; justify-content: center; }
.pd-pages button { border: 1px solid var(--formBorder); background: var(--background-color); color: gray; cursor: pointer; height: 26px; min-width: 26px; padding: 0 8px; display: flex; align-items: center; justify-content: center; gap: 5px; font-size: 12px; }
.pd-page-tab.on { border-color: var(--primary-color); color: var(--primary-color); }
.pd-page-tab.empty { border-style: dashed; }
.pd-bg { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.pd-bg-sec { border-top: 1px solid var(--formBorder); margin-top: 14px; padding-top: 12px; }
.pd-bg-name { font-size: 12px; margin: 0 0 4px; word-break: break-all; }
.pd-bg-clear { border: 1px solid var(--formBorder); background: var(--background-color); color: gray; cursor: pointer; padding: 5px 10px; font-size: 12px; margin-top: 6px; }
.pd-page-add { font-size: 14px; }
/* Always-white paper: lock dark ink so page content stays readable regardless of app theme. */
.pd-page { position: relative; background: #fff; color: #1a1a1a; box-shadow: 0 2px 16px rgba(0,0,0,.15); flex-shrink: 0; align-self: flex-start; }
.pd-el { position: absolute; box-sizing: border-box; cursor: move; outline: 1px dashed transparent; user-select: none; -webkit-user-select: none; }
.pd-el:hover { outline-color: var(--formBorder); }
/* elements sit on the always-white paper, so use the (dark in both themes) button ink,
   not --primary-color which goes near-white in dark mode and vanishes on the page. */
.pd-el.sel { outline: 1.5px solid var(--primary-button, var(--primary-color)); }
.pd-el-inner { width: 100%; height: 100%; overflow: hidden; line-height: 1.3; word-break: break-word; white-space: pre-wrap; }
.pd-el.t-field .pd-el-inner { color: var(--primary-button, var(--primary-color)); }
.pd-ph { color: #b0b6c0; font-size: 11px; display: flex; align-items: center; justify-content: center; height: 100%; border: 1px dashed var(--formBorder); }
.pd-mini { width: 100%; border-collapse: collapse; font-size: 8px; }
.pd-mini th, .pd-mini td { border: 1px solid #d5d9e0; padding: 1px 3px; color: #6b7280; }
.pd-mini th { background: #f0f2f5; }
.pd-mini-noborder th, .pd-mini-noborder td { border: none; }
.pd-resize { position: absolute; right: -5px; bottom: -5px; width: 11px; height: 11px; background: var(--primary-button, var(--primary-color)); border: 2px solid #fff; border-radius: 50%; cursor: nwse-resize; }

.pd-insp { width: 280px; flex-shrink: 0; border-left: 1px solid var(--calendarBorder); background: var(--background-color); padding: 14px; overflow-y: auto; overflow-x: hidden; box-sizing: border-box !important; }
.pd-insp-h { display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 12px; }
.pd-el-del { border: none; background: none; color: gray; cursor: pointer; padding: 3px; display: flex; }
.pd-el-del:hover { color: tomato; }
.pd-grid4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; margin-bottom: 12px; }
.pd-grid4 label, .pd-grid2 label, .pd-f, .pd-style label { display: flex; flex-direction: column; gap: 3px; font-size: 10.5px; color: gray; min-width: 0; }
.pd-grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 10px; }
.pd-f { margin-bottom: 10px; }
.pd-insp input, .pd-insp select, .pd-insp textarea { border: 1px solid var(--formBorder); border-radius: 6px; padding: 6px 8px; font-size: 12.5px; background: var(--background-color); color: var(--primary-color); box-sizing: border-box !important; width: 100%; max-width: 100%; }
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
