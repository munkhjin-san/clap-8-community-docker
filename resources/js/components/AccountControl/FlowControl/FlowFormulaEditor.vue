<template>
    <div class="fml">
        <div class="fml-tools">
            <button
                v-for="op in OPERATORS"
                :key="op.label"
                type="button"
                class="fml-chip fml-op"
                :title="op.title"
                @mousedown.prevent
                @click="insertSnippet(op.ins, op.back)"
            >{{ op.label }}</button>
            <span class="fml-tools-sep"></span>
            <button
                v-for="fn in FLOW_FORMULA_FUNCTIONS"
                :key="fn.name"
                type="button"
                class="fml-chip fml-fn"
                :title="fn.signature + ' — ' + fn.description"
                @mousedown.prevent
                @click="insertSnippet(fn.name + '()', 1)"
            >{{ fn.name }}</button>
            <button type="button" class="fml-help" @mousedown.prevent @click="helpOpen = true">
                <span class="fml-help-q">?</span>書き方
            </button>
        </div>
        <div ref="editorEl" class="fml-editor"></div>
        <div class="fml-preview" :class="{ err: preview.error, ok: preview.ok && !preview.error }">
            <template v-if="preview.loading">計算中…</template>
            <template v-else-if="preview.error"><span class="fml-x">!</span>{{ preview.error }}</template>
            <template v-else-if="preview.ok"><span class="fml-eq">=</span>{{ preview.display }}</template>
            <template v-else><span class="fml-hint">「[」で項目、英字入力で関数の候補が表示されます</span></template>
        </div>
        <div v-if="preview.warn" class="fml-warn"><span class="fml-warn-i">!</span>{{ preview.warn }}</div>
        <FlowFormulaHelp v-if="helpOpen" @close="helpOpen = false" />
    </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { EditorView, keymap, placeholder as cmPlaceholder } from '@codemirror/view'
import { EditorState } from '@codemirror/state'
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands'
import { StreamLanguage, syntaxHighlighting, HighlightStyle } from '@codemirror/language'
import { autocompletion, completionKeymap, closeBrackets, type CompletionContext } from '@codemirror/autocomplete'
import { tags as t } from '@lezer/highlight'
import { useApi } from '@/composables/api'
import { FLOW_TYPE_LABEL } from '@/types/flow'
import { FLOW_FORMULA_FUNCTIONS, FLOW_FORMULA_FUNCTION_NAMES } from '@/utils/flowFormulaFunctions'
import FlowFormulaHelp from './FlowFormulaHelp.vue'

const props = defineProps<{
    modelValue: string | null | undefined
    fields?: { key: string; label: string; input_type: string }[]
    resultType?: string | null
}>()
const emit = defineEmits<{ 'update:modelValue': [string] }>()
const api = useApi()

const editorEl = ref<HTMLElement | null>(null)
const helpOpen = ref(false)
let view: EditorView | null = null

// Clickable templates — insert at the cursor (back = chars to move the caret left after insert).
const OPERATORS = [
    { label: '＋', ins: ' + ', back: 0, title: '足す' },
    { label: '−', ins: ' - ', back: 0, title: '引く' },
    { label: '×', ins: ' * ', back: 0, title: '掛ける' },
    { label: '÷', ins: ' / ', back: 0, title: '割る' },
    { label: '( )', ins: '()', back: 1, title: 'かっこ' },
    { label: '＞', ins: ' > ', back: 0, title: 'より大きい' },
    { label: '≧', ins: ' >= ', back: 0, title: '以上' },
    { label: '＜', ins: ' < ', back: 0, title: 'より小さい' },
    { label: '≦', ins: ' <= ', back: 0, title: '以下' },
    { label: '＝', ins: ' = ', back: 0, title: '等しい' },
]
const insertSnippet = (text: string, back = 0) => {
    if (!view) return
    const { from, to } = view.state.selection.main
    view.dispatch({ changes: { from, to, insert: text }, selection: { anchor: from + text.length - back } })
    view.focus() // docChanged fires the updateListener → emits + reschedules preview automatically
}

/* ---- syntax highlighting ---- */
const formulaLanguage = StreamLanguage.define<{}>({
    token(stream) {
        if (stream.eatSpace()) return null
        if (stream.match(/^\[[^\]]*\]/)) return 'field'
        if (stream.match(/^"(?:\\.|[^"])*"/) || stream.match(/^'(?:\\.|[^'])*'/)) return 'string'
        if (stream.match(/^\d+(?:\.\d+)?/)) return 'number'
        if (stream.match(/^[A-Za-z_][A-Za-z0-9_]*/)) {
            return FLOW_FORMULA_FUNCTION_NAMES.includes(stream.current().toUpperCase()) ? 'func' : 'field'
        }
        if (stream.match(/^(>=|<=|!=|<>|==|=|>|<|\+|-|\*|\/|%)/)) return 'operator'
        stream.next()
        return null
    },
    tokenTable: { field: t.variableName, func: t.keyword, string: t.string, number: t.number, operator: t.operator },
})
const highlight = syntaxHighlighting(HighlightStyle.define([
    { tag: t.keyword, color: 'var(--primary-color)', fontWeight: '600' },
    { tag: t.variableName, color: '#2d7d46' },
    { tag: t.string, color: '#b26b00' },
    { tag: t.number, color: '#2f6df6' },
    { tag: t.operator, color: 'gray' },
]))

/* ---- autocomplete: [fields] and functions ---- */
const completeFormula = (ctx: CompletionContext) => {
    const inBracket = ctx.matchBefore(/\[[^\]]*/)
    if (inBracket) {
        return {
            from: inBracket.from,
            options: (props.fields ?? []).map((f) => ({
                // label stays bracketed so it filters against the typed "[…"; detail shows the key that inserts.
                label: `[${f.label}]`,
                detail: `[${f.key}]`,
                info: FLOW_TYPE_LABEL[f.input_type] ?? f.input_type,
                type: 'variable',
                apply: (v: EditorView, _c: unknown, from: number, to: number) => {
                    // reference by key (survives label renames); swallow the ] that closeBrackets auto-added.
                    const end = v.state.doc.sliceString(to, to + 1) === ']' ? to + 1 : to
                    const insert = `[${f.key}]`
                    v.dispatch({ changes: { from, to: end, insert }, selection: { anchor: from + insert.length } })
                },
            })),
            validFor: /^\[[^\]]*$/,
        }
    }
    const word = ctx.matchBefore(/[A-Za-z_][A-Za-z0-9_]*/)
    if (word && (word.from < word.to || ctx.explicit)) {
        return {
            from: word.from,
            options: FLOW_FORMULA_FUNCTIONS.map((fn) => ({
                label: fn.name,
                detail: fn.signature,
                info: fn.description,
                type: 'function',
                apply: (v: EditorView, _c: unknown, from: number, to: number) => {
                    v.dispatch({ changes: { from, to, insert: `${fn.name}()` }, selection: { anchor: from + fn.name.length + 1 } })
                },
            })),
            validFor: /^[A-Za-z_][A-Za-z0-9_]*$/,
        }
    }
    return null
}

const theme = EditorView.theme({
    '&': { fontSize: '13px', border: '1px solid var(--formBorder)', borderRadius: '7px', background: 'var(--background-color)' },
    '&.cm-focused': { outline: 'none', borderColor: 'var(--primary-color)' },
    '.cm-content': { fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace', padding: '9px 10px', caretColor: 'var(--primary-color)', color: 'var(--primary-color)' },
    '.cm-line': { padding: '0' },
    '.cm-placeholder': { color: 'gray' },
    '.cm-tooltip': { border: '1px solid var(--formBorder)', borderRadius: '8px', background: 'var(--background-color)', boxShadow: '0 6px 20px rgba(0,0,0,.14)' },
    '.cm-tooltip-autocomplete ul li[aria-selected]': { background: 'var(--bg3)', color: 'var(--primary-color)' },
})

onMounted(() => {
    view = new EditorView({
        parent: editorEl.value!,
        state: EditorState.create({
            doc: props.modelValue ?? '',
            extensions: [
                history(),
                closeBrackets(),
                keymap.of([...defaultKeymap, ...historyKeymap, ...completionKeymap]),
                formulaLanguage,
                highlight,
                autocompletion({ override: [completeFormula], icons: false }),
                cmPlaceholder('例: [単価] * [数量]'),
                EditorView.lineWrapping,
                theme,
                EditorView.updateListener.of((u) => {
                    if (u.docChanged) {
                        const val = u.state.doc.toString()
                        emit('update:modelValue', val)
                        schedulePreview(val)
                    }
                }),
            ],
        }),
    })
    schedulePreview(props.modelValue ?? '')
})

onBeforeUnmount(() => { view?.destroy(); view = null })

// External changes (e.g. switching between formula fields in the inspector) → reset the doc.
watch(() => props.modelValue, (val) => {
    if (view && (val ?? '') !== view.state.doc.toString()) {
        view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: val ?? '' } })
        schedulePreview(val ?? '')
    }
})

/* ---- live preview (pure eval on the backend, with sample values) ---- */
const preview = reactive<{ loading: boolean; ok: boolean; error: string | null; display: string; warn: string | null }>(
    { loading: false, ok: false, error: null, display: '', warn: null }
)
const TYPE_LABEL: Record<string, string> = { text: '文字', toggle: 'オン/オフ', number: '数値' }
let previewTimer: ReturnType<typeof setTimeout> | null = null
const sampleFor = (t: string): unknown => {
    if (t === 'number') return 1
    if (t === 'toggle') return true
    if (t === 'checkbox') return []
    return 'サンプル'
}
const schedulePreview = (formula: string) => {
    if (previewTimer) clearTimeout(previewTimer)
    if (!formula.trim()) { preview.ok = false; preview.error = null; preview.warn = null; preview.loading = false; return }
    preview.loading = true
    previewTimer = setTimeout(() => runPreview(formula), 300)
}
const runPreview = async (formula: string) => {
    const values: Record<string, unknown> = {}
    for (const f of props.fields ?? []) { values[f.label] = sampleFor(f.input_type); values[f.key] = sampleFor(f.input_type) }
    try {
        const res = await api.post('/flow_formula_preview', { formula, result_type: props.resultType ?? 'number', values }, { silent: true })
        preview.loading = false
        if (res?.ok && res.missing_refs?.length) {
            // References that don't resolve (deleted field or typo) — would silently compute as 0.
            preview.ok = false
            preview.error = `存在しない項目を参照しています: ${res.missing_refs.map((r: string) => `[${r}]`).join(' ')}`
            preview.warn = null
        } else if (res?.ok) {
            preview.ok = true; preview.error = null
            preview.display = res.value === null || res.value === '' ? '（空）' : String(res.value)
            preview.warn = res.suggested_type
                ? `結果は${res.suggested_type === 'text' ? '文字列' : '真偽値'}です。結果の種類を「${TYPE_LABEL[res.suggested_type]}」にしてください。`
                : null
        } else {
            preview.ok = false; preview.error = res?.error ?? '計算できませんでした'; preview.warn = null
        }
    } catch {
        preview.loading = false; preview.ok = false; preview.error = null; preview.warn = null
    }
}
watch(() => props.resultType, () => { if (view) schedulePreview(view.state.doc.toString()) })
</script>

<style scoped>
.fml { display: flex; flex-direction: column; gap: 7px; }
.fml-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 5px; }
.fml-chip { font-size: 12px; line-height: 1; padding: 5px 9px; border: 1px solid var(--formBorder); border-radius: 6px; background: var(--background-color); color: var(--primary-color); cursor: pointer; box-sizing: border-box !important; }
.fml-chip:hover { background: var(--bg3); border-color: var(--primary-color); }
.fml-op { min-width: 30px; font-weight: 600; }
.fml-fn { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11.5px; color: var(--primary-color); }
.fml-tools-sep { width: 1px; align-self: stretch; background: var(--calendarBorder); margin: 2px 3px; }
.fml-help { margin-left: auto; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; padding: 5px 10px; border: none; background: none; color: var(--primary-color); cursor: pointer; }
.fml-help:hover { text-decoration: underline; }
.fml-help-q { display: inline-flex; align-items: center; justify-content: center; width: 15px; height: 15px; border-radius: 50%; border: 1px solid currentColor; font-size: 10px; font-weight: 700; }
.fml-editor { width: 100%; }
.fml-editor :deep(.cm-editor) { min-height: 64px; }
.fml-preview { font-size: 12px; color: gray; min-height: 18px; display: flex; align-items: center; gap: 6px; }
.fml-preview.ok { color: var(--primary-color); }
.fml-preview.err { color: #e2574c; }
.fml-eq { font-weight: 700; }
.fml-x { display: inline-flex; align-items: center; justify-content: center; width: 15px; height: 15px; border-radius: 50%; background: #e2574c; color: #fff; font-size: 10px; font-weight: 700; flex-shrink: 0; }
.fml-hint { color: #a0a0a0; }
.fml-warn { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #b26b00; background: rgba(217, 119, 6, 0.08); border: 1px solid rgba(217, 119, 6, 0.25); border-radius: 6px; padding: 6px 9px; margin-top: -2px; }
.fml-warn-i { display: inline-flex; align-items: center; justify-content: center; width: 15px; height: 15px; border-radius: 50%; background: #d97706; color: #fff; font-size: 10px; font-weight: 700; flex-shrink: 0; }
</style>
