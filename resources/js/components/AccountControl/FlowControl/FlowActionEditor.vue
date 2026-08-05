<template>
    <Modal persist @close="emit('close')">
        <template #title>カスタムボタンの設定</template>
        <template #content>
            <div class="ae-row">
                <label class="ae-label">ボタン名</label>
                <span class="ae-color-wrap" :class="{ theme: !cfg.color }" :title="cfg.color ? 'ボタンの色' : 'アプリのテーマ色（クリックで個別指定）'">
                    <input type="color" class="ae-color" :value="cfg.color || '#3b6df5'"
                        @input="cfg.color = ($event.target as HTMLInputElement).value">
                    <button v-if="cfg.color" type="button" class="ae-color-clear" title="テーマ色に戻す"
                        @click.stop="cfg.color = ''">×</button>
                </span>
                <input v-model="tool.name" type="text" class="ae-input flex-1" placeholder="ボタン名">
            </div>

            <div class="ae-sec">処理</div>
            <p class="ae-note">
                ボタンが何をするかはシステム側で用意した処理から選びます。アプリの設定では作れません
                （外部連携はフィールドの意味を分かっていないと組めないため、コード側で用意しています）。
            </p>

            <p v-if="loading" class="ae-empty">読み込み中…</p>
            <p v-else-if="!catalog.length" class="ae-empty">利用できる処理がまだありません。</p>

            <div v-else class="ae-handlers">
                <label v-for="h in catalog" :key="h.key" class="ae-handler" :class="{ on: cfg.handler === h.key }">
                    <input type="radio" :value="h.key" :checked="cfg.handler === h.key" @change="pick(h)">
                    <span class="ae-h-main">
                        <span class="ae-h-name">{{ h.label }}</span>
                        <span class="ae-h-desc">{{ h.description }}</span>
                        <span v-if="h.once_only" class="ae-h-once">1レコードにつき1回だけ実行できます</span>
                    </span>
                </label>
            </div>

            <!-- The whole point of having no mapping UI: the handler names the field keys it needs,
                 and this table says whether THIS app has them. 未作成 here is why a button says 設定不足. -->
            <template v-if="picked">
                <div class="ae-sec">必要なフィールド</div>
                <p class="ae-note">
                    処理はフィールドの「フィールドコード」で値を読み書きします。下のコードと同じコードのフィールドを
                    フォームに用意してください。結果の書き戻し先は、誰も編集できないフィールド（編集権限なし）に
                    しておくと安全です——処理が入れた値を後から書き換えられなくなります。
                </p>
                <div class="ae-keys">
                    <div v-for="k in keyRows" :key="k.key" class="ae-key" :class="{ missing: !k.present }">
                        <span class="ae-k-dot" :class="{ ok: k.present }"></span>
                        <code class="ae-k-code">{{ k.key }}</code>
                        <span class="ae-k-label">{{ k.label }}</span>
                        <span class="ae-k-kind">{{ k.kind }}</span>
                        <span class="ae-k-state">{{ k.present ? (k.fieldLabel ? `→ ${k.fieldLabel}` : 'あり') : '未作成' }}</span>
                    </div>
                </div>
            </template>

            <!-- no section heading: FlowEligiblePicker labels itself 「押せる人（責任者）」 -->
            <div class="ae-elig">
            <FlowEligiblePicker
                :eligible="cfg.eligible"
                :users="users"
                :positions="positions"
                :project-fields="projectFields"
            />
            </div>
            <p class="ae-note">
                このボタンは対応待ちの件数や通知の対象にはなりません（ステータスのボタンとは別扱いです）。
            </p>

            <div class="ae-actions">
                <button class="ae-btn" @click="emit('close')">閉じる</button>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Modal from '@/components/Global/Modal.vue'
import FlowEligiblePicker from './FlowEligiblePicker.vue'
import { useApi } from '@/composables/api'
import { actionConfig } from '@/types/flow'
import type { BuilderDefinition, FlowActionCatalogEntry, FlowAppTool, FlowOptionPosition, FlowOptionUser } from '@/types/flow'

const props = defineProps<{
    tool: FlowAppTool
    def: BuilderDefinition
    users: FlowOptionUser[]
    positions: FlowOptionPosition[]
}>()
const emit = defineEmits<{ close: [] }>()
const api = useApi()

const cfg = computed(() => actionConfig(props.tool))
const catalog = ref<FlowActionCatalogEntry[]>([])
const loading = ref(true)

onMounted(async () => {
    try {
        const data = await api.get('/flow_action_catalog') as { actions: FlowActionCatalogEntry[] } | null
        catalog.value = data?.actions ?? []
    } finally {
        loading.value = false
    }
})

const picked = computed(() => catalog.value.find((h) => h.key === cfg.value.handler) ?? null)

/** Choosing a handler names the button too, unless the name has already been edited. */
const pick = (h: FlowActionCatalogEntry) => {
    const untouched = !props.tool.name || props.tool.name === '新しいボタン'
        || catalog.value.some((x) => x.label === props.tool.name)
    cfg.value.handler = h.key
    if (untouched) props.tool.name = h.label
}

const projectFields = computed(() => props.def.fields.filter((f) => f.input_type === 'project'))

/** Handler-declared keys × the app's actual field keys. */
const keyRows = computed(() => {
    const h = picked.value
    if (!h) return []
    const byKey = new Map(props.def.fields.map((f) => [f.key, f]))
    const rows = [
        ...h.inputs.map((i) => ({ key: i.key, label: i.label, kind: i.required ? '読み取り（必須）' : '読み取り' })),
        ...h.outputs.map((o) => ({ key: o.key, label: o.label, kind: '書き戻し' })),
    ]
    return rows.map((r) => ({ ...r, present: byKey.has(r.key), fieldLabel: byKey.get(r.key)?.label ?? '' }))
})
</script>

<style scoped>
.ae-row { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.ae-label { font-size: 12px; color: gray; flex-shrink: 0; min-width: 70px; }
/* own input rule (custom-a-input is too tight/small inside these panels) */
.ae-input { box-sizing: border-box; height: 34px; padding: 0 10px; font-size: 13px; border: 1px solid var(--formBorder); border-radius: 6px; background: var(--background-color); color: var(--primary-color); min-width: 0; }
.ae-input:focus { outline: none; border-color: var(--primary-color); }

.ae-color-wrap { position: relative; display: inline-flex; flex-shrink: 0; }
.ae-color-wrap.theme .ae-color { opacity: .35; }
.ae-color { width: 26px; height: 26px; padding: 0; border: 1px solid var(--formBorder); border-radius: 6px; background: none; cursor: pointer; flex-shrink: 0; overflow: hidden; }
.ae-color::-webkit-color-swatch-wrapper { padding: 0; }
.ae-color::-webkit-color-swatch { border: none; border-radius: 5px; }
.ae-color::-moz-color-swatch { border: none; border-radius: 5px; }
.ae-color-clear { position: absolute; top: -6px; right: -6px; width: 15px; height: 15px; border-radius: 50%; border: 1px solid var(--formBorder); background: var(--background-color); color: gray; font-size: 10px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; }
.ae-color-clear:hover { color: var(--primary-color); border-color: var(--primary-color); }

.ae-sec { font-size: 13px; color: var(--primary-color); margin: 20px 0 8px; }
.ae-note { font-size: 12px; color: gray; line-height: 1.9; margin: 0 0 12px; }
.ae-empty { font-size: 12px; color: gray; margin: 0 0 10px; }

.ae-handlers { display: flex; flex-direction: column; gap: 8px; }
.ae-handler { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border: 1px solid var(--calendarBorder); border-radius: 9px; background: var(--background-color); cursor: pointer; }
.ae-handler.on { border-color: var(--primary-color); }
.ae-handler input { margin: 3px 0 0; cursor: pointer; flex-shrink: 0; }
.ae-h-main { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.ae-h-name { font-size: 13px; color: var(--primary-color); }
.ae-h-desc { font-size: 12px; color: gray; line-height: 1.7; }
.ae-h-once { font-size: 11.5px; color: gray; }

.ae-elig { margin-top: 20px; padding-top: 16px; border-top: 1px dashed var(--calendarBorder); margin-bottom: 12px; }

.ae-keys { display: flex; flex-direction: column; gap: 6px; }
.ae-key { display: flex; align-items: center; gap: 9px; padding: 8px 11px; border: 1px solid var(--calendarBorder); border-radius: 7px; font-size: 12px; flex-wrap: wrap; }
.ae-key.missing { border-color: rgba(226, 87, 76, .45); }
.ae-k-dot { width: 7px; height: 7px; border-radius: 50%; background: #e2574c; flex-shrink: 0; }
.ae-k-dot.ok { background: #4caf7d; }
.ae-k-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11.5px; color: var(--primary-color); background: var(--bg3); padding: 2px 6px; border-radius: 4px; }
.ae-k-label { color: var(--primary-color); }
.ae-k-kind { color: gray; font-size: 11px; }
.ae-k-state { margin-left: auto; color: gray; font-size: 11.5px; }

.ae-actions { display: flex; justify-content: flex-end; margin-top: 22px; padding-top: 16px; border-top: 1px solid var(--calendarBorder); }
.ae-btn { font-size: 13px; padding: 8px 18px; border-radius: 7px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; letter-spacing: normal; }
.ae-btn:hover { background: var(--bg3); }
</style>
