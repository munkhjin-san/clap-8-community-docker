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
                押したときに実行するサーバー側の処理を選びます。処理の中身はシステム側のコードに書かれていて、
                アプリの設定では作れません。
            </p>

            <p v-if="loading" class="ae-empty">読み込み中…</p>
            <p v-else-if="!catalog.length" class="ae-empty">
                実行できる処理がまだ登録されていません。システム側で処理を追加すると、ここに出ます。
            </p>

            <div v-else class="ae-handlers">
                <label v-for="h in catalog" :key="h.key" class="ae-handler" :class="{ on: cfg.handler === h.key }">
                    <input type="radio" :value="h.key" :checked="cfg.handler === h.key" @change="pick(h)">
                    <span class="ae-h-name">{{ h.label }}</span>
                </label>
            </div>

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

/** Choosing a process names the button too, unless the name has already been edited. */
const pick = (h: FlowActionCatalogEntry) => {
    const untouched = !props.tool.name || props.tool.name === '新しいボタン'
        || catalog.value.some((x) => x.label === props.tool.name)
    cfg.value.handler = h.key
    if (untouched) props.tool.name = h.label
}

const projectFields = computed(() => props.def.fields.filter((f) => f.input_type === 'project'))
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
.ae-empty { font-size: 12px; color: gray; line-height: 1.9; margin: 0 0 10px; }

.ae-handlers { display: flex; flex-direction: column; gap: 8px; }
.ae-handler { display: flex; align-items: center; gap: 10px; padding: 11px 14px; border: 1px solid var(--calendarBorder); border-radius: 9px; background: var(--background-color); cursor: pointer; }
.ae-handler.on { border-color: var(--primary-color); }
.ae-handler input { margin: 0; cursor: pointer; flex-shrink: 0; }
.ae-h-name { font-size: 13px; color: var(--primary-color); }

.ae-elig { margin-top: 20px; padding-top: 16px; border-top: 1px dashed var(--calendarBorder); margin-bottom: 12px; }

.ae-actions { display: flex; justify-content: flex-end; margin-top: 22px; padding-top: 16px; border-top: 1px solid var(--calendarBorder); }
.ae-btn { font-size: 13px; padding: 8px 18px; border-radius: 7px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); cursor: pointer; letter-spacing: normal; }
.ae-btn:hover { background: var(--bg3); }
</style>
