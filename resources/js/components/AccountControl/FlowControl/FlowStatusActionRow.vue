<template>
    <div class="act" :class="{ embedded }">
        <div class="act-top">
            <!-- empty color → the button inherits the app theme color; the swatch just hints -->
            <span class="act-color-wrap" :class="{ theme: !action.color }" :title="action.color ? 'ボタンの色' : 'アプリのテーマ色（クリックで個別指定）'">
                <input type="color" class="act-color" :value="action.color || '#3b6df5'"
                    @input="action.color = ($event.target as HTMLInputElement).value">
                <button v-if="action.color" type="button" class="act-color-clear" title="テーマ色に戻す"
                    @click.stop="action.color = ''">×</button>
            </span>
            <input type="text" v-model="action.label" placeholder="ボタン名（例：承認）" class="custom-a-input !box-border act-name">
            <select v-model="action.to_status_key" class="custom-a-input !box-border act-to" title="移動先ステータス">
                <option :value="null" disabled>移動先…</option>
                <option v-for="s in statusOptions" :key="s.key" :value="s.key">{{ s.name }}</option>
            </select>
            <button v-if="!embedded" class="act-del" @click="emit('remove')" title="削除"><CloseIcon size="9" /></button>
        </div>

        <div class="act-elig">
            <FlowEligiblePicker
                :eligible="action.eligible"
                :users="users"
                :positions="positions"
                :project-fields="projectFields"
            />

            <!-- only meaningful once someone is named: with 押せる人 empty the button is nobody's
                 duty, so there is no badge to switch off in the first place -->
            <label v-if="eligibleConfigured" class="chk act-notify">
                <input type="checkbox" :checked="action.notify !== false" @change="toggleNotify">
                通知バッジを表示する
                <small>押せる人に対応待ちの件数と通知を出します</small>
            </label>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { BuilderStatusAction, FlowOptionUser, FlowOptionPosition, FlowField } from '@/types/flow'
import { eligibleIsConfigured } from '@/types/flow'
import CloseIcon from '@/components/Form/CloseIcon.vue'
import FlowEligiblePicker from './FlowEligiblePicker.vue'

const props = defineProps<{
    action: BuilderStatusAction
    statusOptions: { key: string; name: string }[]
    users: FlowOptionUser[]
    positions: FlowOptionPosition[]
    projectFields?: FlowField[]
    embedded?: boolean
}>()
const emit = defineEmits<{ remove: [] }>()

const eligibleConfigured = computed(() => eligibleIsConfigured(props.action.eligible))

/** off = the named people keep the button but stop being chased (no 対応待ち count, no notification) */
const toggleNotify = (e: Event) => {
    props.action.notify = (e.target as HTMLInputElement).checked
}
</script>

<style scoped>
.act { position: relative; border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 12px; margin-bottom: 10px; background: var(--background-color); }
/* inside the settings modal: no card chrome, the modal owns the frame */
.act.embedded { border: none; padding: 0; margin: 0; background: none; }
.act.embedded .act-top { padding-right: 0; }
.act.embedded .act-elig { margin-top: 14px; }
/* top row: color + name + 移動先, inline & wrappable */
.act-top { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; padding-right: 22px; }
.act-color-wrap { position: relative; display: inline-flex; flex-shrink: 0; }
/* "inherit theme" state: dim the swatch so it doesn't read as a chosen color */
.act-color-wrap.theme .act-color { opacity: .35; }
.act-color-clear { position: absolute; top: -6px; right: -6px; width: 15px; height: 15px; border-radius: 50%; border: 1px solid var(--formBorder); background: var(--background-color); color: gray; font-size: 10px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; }
.act-color-clear:hover { color: var(--primary-color); border-color: var(--primary-color); }
.act-color { width: 24px; height: 24px; padding: 0; border: 1px solid var(--formBorder); border-radius: 6px; background: none; cursor: pointer; flex-shrink: 0; overflow: hidden; }
.act-color::-webkit-color-swatch-wrapper { padding: 0; }
.act-color::-webkit-color-swatch { border: none; border-radius: 5px; }
.act-color::-moz-color-swatch { border: none; border-radius: 5px; }
.act-name { flex: 0 1 200px; min-width: 120px; }
.act-to { flex: 0 1 150px; min-width: 110px; }
.act-del { position: absolute; top: 8px; right: 8px; border: none; background: none; color: gray; cursor: pointer; padding: 4px; display: flex; flex-shrink: 0; }

.act-elig { margin-top: 12px; border-top: 1px dashed var(--calendarBorder); padding-top: 10px; display: flex; flex-direction: column; gap: 9px; }
.chk { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; color: var(--primary-color); user-select: none; }
.chk input { margin: 0; cursor: pointer; }
.act-notify { margin-top: 10px; }
.act-notify small { color: gray; font-size: 10.5px; margin-left: 6px; }
</style>
