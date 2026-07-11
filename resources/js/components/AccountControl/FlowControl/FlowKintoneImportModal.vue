<template>
    <Modal size="large" :loader="busy" disable-scroll @close="emit('close')">
        <template #title><h2 class="ki-title">kintoneから取込</h2></template>
        <template #content>
            <div class="ki-wrap">
                <!-- STEP: enter app id -->
                <template v-if="step === 'input'">
                    <p class="ki-lead">kintoneのアプリIDを入力して、フォーム構成を取得します。</p>
                    <div class="ki-inputrow">
                        <input v-model.number="appId" type="number" min="1" class="custom-a-input !box-border ki-input" placeholder="アプリID（例: 26）" @keyup.enter="fetchPreview">
                        <button class="ki-btn ki-primary" :disabled="!appId" @click="fetchPreview">取得</button>
                    </div>
                    <p v-if="error" class="ki-error">{{ error }}</p>
                </template>

                <!-- STEP: preview -->
                <template v-else-if="step === 'preview' && preview">
                    <div class="ki-apphead">
                        <div>
                            <div class="ki-appname">{{ preview.app.name }}</div>
                            <div v-if="preview.app.description" class="ki-appdesc">{{ preview.app.description }}</div>
                        </div>
                        <div class="ki-summary">
                            <span class="ki-sum-n ok">{{ preview.summary.supported }}</span> 項目を取込
                            <span v-if="preview.summary.skipped" class="ki-sum-skip">（{{ preview.summary.skipped }} 項目は未対応）</span>
                        </div>
                    </div>

                    <div v-if="preview.status_flow?.statuses?.length" class="ki-flow">
                        <div class="ki-flow-h">
                            ステータスフロー
                            <span v-if="!preview.status_flow.enable" class="ki-flow-off">（kintoneでは無効）</span>
                        </div>
                        <div class="ki-flow-states">
                            <template v-for="(s, i) in preview.status_flow.statuses" :key="i">
                                <span class="ki-state" :class="{ init: s.is_initial }">{{ s.name }}</span>
                                <span v-if="i < preview.status_flow.statuses.length - 1" class="ki-flow-sep">›</span>
                            </template>
                        </div>
                        <div v-if="preview.status_flow.actions.length" class="ki-flow-acts">
                            アクション: {{ preview.status_flow.actions.map((a) => a.name).join(' / ') }}
                        </div>
                    </div>

                    <div class="ki-table-scroll">
                        <table class="ki-table">
                            <thead>
                                <tr>
                                    <th class="ki-col-h">kintoneの項目</th>
                                    <th class="ki-col-h">タイプ</th>
                                    <th class="ki-col-h"></th>
                                    <th class="ki-col-h">取込先タイプ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(f, i) in preview.fields" :key="i" :class="{ 'ki-skip': !f.supported }">
                                    <td class="ki-fname">
                                        {{ f.label }}<span v-if="f.required" class="ki-req">*</span>
                                        <span v-if="f.options?.length" class="ki-optcount">選択肢 {{ f.options.length }}</span>
                                    </td>
                                    <td class="ki-kt">{{ f.kintone_type }}</td>
                                    <td class="ki-arrow">{{ f.supported ? '→' : '' }}</td>
                                    <td>
                                        <template v-if="f.supported">
                                            <span class="ki-typechip">{{ typeLabel(f.mapped_type) }}</span>
                                            <span v-if="f.formula_status === 'ok'" class="ki-fbadge ok" title="kintoneの計算式を取り込みます">計算式</span>
                                            <div v-if="f.columns?.length" class="ki-cols">
                                                <span v-for="c in f.columns" :key="c.key" class="ki-col">{{ c.label }}<i>{{ typeLabel(c.input_type) }}</i></span>
                                            </div>
                                            <code v-if="f.formula_status === 'ok' && f.formula" class="ki-formula">{{ f.formula }}</code>
                                            <div v-else-if="f.kintone_type === 'CALC'" class="ki-formula-note">{{ f.note }}</div>
                                        </template>
                                        <span v-else class="ki-unsupported">{{ f.note || '未対応' }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="ki-note">※ ステータス・作成者・レコード番号などのシステム項目は自動で用意されるため一覧には表示されません。</p>

                    <div class="ki-actions">
                        <button class="ki-btn" @click="step = 'input'">戻る</button>
                        <button class="ki-btn ki-primary" :disabled="!preview.summary.supported" @click="emit('import', preview)">
                            フォームに取り込む
                        </button>
                    </div>
                </template>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useApi } from '@/composables/api'
import Modal from '@/components/Global/Modal.vue'

interface PreviewColumn { key: string; label: string; input_type: string; options: string[]; required: boolean }
interface PreviewField { code: string; label: string; kintone_type: string; mapped_type: string | null; supported: boolean; required: boolean; options: string[]; columns?: PreviewColumn[]; note: string | null; formula?: string; formula_status?: 'ok' | 'fallback'; result_type?: string }
interface StatusFlow { enable: boolean; statuses: { name: string; index: number; is_initial: boolean }[]; actions: { name: string; from: string; to: string }[] }
interface Preview { app: { id: string; name: string; description: string | null }; fields: PreviewField[]; summary: { total: number; supported: number; skipped: number }; status_flow?: StatusFlow }

const emit = defineEmits<{ close: []; import: [preview: Preview] }>()

const api = useApi()
const busy = ref(false)
const step = ref<'input' | 'preview'>('input')
const appId = ref<number | null>(null)
const preview = ref<Preview | null>(null)
const error = ref('')

const TYPE_LABELS: Record<string, string> = {
    short: '短文', long: '長文', number: '数値', date: '日付', datetime: '日時', time: '時刻',
    select: '選択', radio: 'ラジオ', checkbox: 'チェック', toggle: 'オン/オフ', user: 'ユーザー', member: 'メンバー',
    file: 'ファイル', label: 'ラベル', table: 'テーブル',
}
const typeLabel = (t: string | null) => (t ? (TYPE_LABELS[t] ?? t) : '')

const fetchPreview = async () => {
    if (!appId.value) return
    busy.value = true
    error.value = ''
    try {
        const data = await api.post('/flow_kintone_preview', { app_id: appId.value }, { silent: true })
        if (data?.app) { preview.value = data; step.value = 'preview' }
        else error.value = 'アプリの取得に失敗しました。'
    } catch (e: any) {
        error.value = e?.response?.data?.message || 'アプリの取得に失敗しました。アプリIDと接続設定をご確認ください。'
    } finally {
        busy.value = false
    }
}
</script>

<style scoped>
.ki-title { font-size: 16px; font-weight: 600; }
.ki-wrap { display: flex; flex-direction: column; min-height: 0; height: 100%; color: var(--primary-color); }
.ki-lead { font-size: 13px; margin-bottom: 14px; }
.ki-inputrow { display: flex; gap: 10px; }
.ki-input { width: 240px; }
.ki-error { color: #e2574c; font-size: 13px; margin-top: 12px; }
.ki-apphead { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--calendarBorder); margin-bottom: 4px; }
.ki-appname { font-size: 16px; font-weight: 700; }
.ki-appdesc { font-size: 12px; color: gray; margin-top: 3px; max-width: 520px; }
.ki-summary { font-size: 12px; color: gray; white-space: nowrap; }
.ki-sum-n { font-size: 18px; font-weight: 700; }
.ki-sum-n.ok { color: #2e7d32; }
.ki-sum-skip { color: #d97706; }
.ki-flow { padding: 12px 0; border-bottom: 1px solid var(--calendarBorder); }
.ki-flow-h { font-size: 12px; color: gray; margin-bottom: 8px; }
.ki-flow-off { color: #d97706; }
.ki-flow-states { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
.ki-state { font-size: 12px; font-weight: 600; background: var(--bg3); color: var(--primary-color); border-radius: 12px; padding: 3px 12px; }
.ki-state.init { background: var(--primary-color); color: #fff; }
.ki-flow-sep { color: gray; }
.ki-flow-acts { font-size: 12px; color: gray; margin-top: 8px; }
.ki-table-scroll { flex: 1; overflow: auto; }
.ki-table { width: 100%; border-collapse: collapse; }
.ki-col-h { position: sticky; top: 0; background: var(--bg3); text-align: left; font-size: 11px; color: gray; padding: 8px 12px; font-weight: 600; }
.ki-table td { padding: 8px 12px; border-top: 1px solid var(--calendarBorder); font-size: 13px; vertical-align: middle; }
.ki-fname { font-weight: 500; }
.ki-req { color: #e2574c; margin-left: 3px; }
.ki-optcount { font-size: 11px; color: gray; background: var(--bg3); border-radius: 8px; padding: 1px 7px; margin-left: 8px; }
.ki-kt { color: gray; font-size: 12px; }
.ki-arrow { color: gray; text-align: center; width: 24px; }
.ki-typechip { display: inline-block; font-size: 12px; font-weight: 500; background: var(--bg3); color: var(--primary-color); border-radius: 5px; padding: 2px 10px; }
.ki-unsupported { font-size: 12px; color: #d97706; }
.ki-fbadge { display: inline-block; font-size: 10.5px; padding: 1px 7px; border-radius: 9px; margin-left: 6px; }
.ki-fbadge.ok { color: #0f7b4f; background: rgba(16, 185, 129, 0.14); }
.ki-formula { display: block; margin-top: 4px; font-size: 11px; color: gray; font-family: ui-monospace, monospace; word-break: break-all; line-height: 1.5; }
.ki-formula-note { margin-top: 3px; font-size: 11px; color: #d97706; }
.ki-cols { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
.ki-col { font-size: 11px; color: gray; background: var(--bg3); border-radius: 5px; padding: 1px 7px; }
.ki-col i { font-style: normal; color: var(--primary-color); opacity: .7; margin-left: 5px; }
.ki-skip { opacity: 0.6; }
.ki-note { font-size: 11px; color: gray; margin-top: 10px; }
.ki-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; flex-shrink: 0; }
.ki-btn { font-size: 13px; padding: 8px 18px; border-radius: 7px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; }
.ki-btn:hover { background: var(--bg3); }
.ki-primary { background: var(--primary-button, var(--primary-color)); color: #fff; border-color: transparent; }
.ki-primary:hover { background: var(--primary-button, var(--primary-color)); opacity: 0.88; }
.ki-primary:disabled { opacity: 0.5; cursor: default; }
</style>
