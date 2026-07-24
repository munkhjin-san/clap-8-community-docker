<template>
    <div ref="rootEl" class="fbell">
        <button class="fbell-btn" :class="{ on: open }" :title="count ? `通知 ${count}件` : '通知'" @click.stop="toggle">
            <Bell :size="14" />
            <Badge v-if="count" class="fbell-badge" :count="count" />
        </button>

        <div v-if="open" class="fbell-menu" @click.stop>
            <div class="fbell-head">
                <span class="fbell-title">通知</span>
                <button class="fbell-gear" :class="{ on: showPrefs }" title="通知設定" @click="showPrefs = !showPrefs">
                    <Gear :size="13" />
                </button>
            </div>

            <!-- settings: per-user per-app toggles -->
            <div v-if="showPrefs" class="fbell-prefs">
                <div v-for="p in PREF_ITEMS" :key="p.key" class="fbell-pref">
                    <span class="fbell-pref-label">{{ p.label }}</span>
                    <span
                        class="flow-sw fbell-sw"
                        :class="{ on: prefs[p.key] !== false }"
                        @click="togglePref(p.key)"
                    ></span>
                </div>
                <p class="fbell-pref-hint">このアプリの通知の受け取りを設定します（自分にのみ適用）。</p>
            </div>

            <!-- events (view-only: opening this popup never marks anything read) -->
            <div v-else class="fbell-list">
                <div v-if="loading" class="fbell-empty">読み込み中…</div>
                <template v-else-if="events.length">
                    <button
                        v-for="ev in events"
                        :key="ev.id"
                        class="fbell-item"
                        :class="{ unread: !ev.read }"
                        @click="openEvent(ev)"
                    >
                        <span class="fbell-dot" :class="{ show: !ev.read }"></span>
                        <span class="fbell-item-body">
                            <span class="fbell-item-text">{{ eventText(ev) }}</span>
                            <span class="fbell-item-sub">
                                <template v-if="ev.record_number != null">#{{ ev.record_number }}・</template>{{ fmtWhen(ev.created_at) }}
                            </span>
                        </span>
                    </button>
                </template>
                <div v-else class="fbell-empty">通知はありません</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { onBeforeUnmount, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import Bell from '@/components/Icons/Bell.vue'
import Gear from '@/components/Icons/Gear.vue'
import Badge from '@/components/Global/Badge.vue'

interface BellEvent {
    id: number
    type: 'comment' | 'new_record' | 'status_change'
    actor?: { id: number; name: string } | null
    record_number?: number | null
    meta?: Record<string, any> | null
    read: boolean
    created_at?: string
}

const props = defineProps<{
    defId: number
    count: number
}>()

const api = useApi()
const router = useRouter()

const open = ref(false)
const showPrefs = ref(false)
const loading = ref(false)
const events = ref<BellEvent[]>([])
const prefs = reactive<Record<string, boolean>>({})
const rootEl = ref<HTMLElement | null>(null)

const PREF_ITEMS = [
    { key: 'comment_own', label: '自分が作成したレコードへのコメント' },
    { key: 'comment_participated', label: 'コメントしたレコードへのコメント' },
    { key: 'new_record', label: '新しいレコードの追加' },
    { key: 'status_change', label: '自分のレコードのステータス変更' },
] as const

const onOutside = (e: MouseEvent) => {
    if (rootEl.value && !rootEl.value.contains(e.target as Node)) close()
}
const close = () => {
    open.value = false
    showPrefs.value = false
    document.removeEventListener('mousedown', onOutside)
}
const toggle = async () => {
    if (open.value) { close(); return }
    open.value = true
    document.addEventListener('mousedown', onOutside)
    loading.value = true
    try {
        const data = await api.get(`/flow_notifications/${props.defId}`)
        events.value = data?.events ?? []
        Object.assign(prefs, data?.prefs ?? {})
    } finally {
        loading.value = false
    }
}
onBeforeUnmount(() => document.removeEventListener('mousedown', onOutside))

const togglePref = async (key: string) => {
    prefs[key] = prefs[key] === false
    await api.post('/flow_notification_pref', { flow_definition_id: props.defId, pref: key, enabled: prefs[key] }, { silent: true })
}

const eventText = (ev: BellEvent): string => {
    const who = ev.actor?.name ?? ''
    if (ev.type === 'comment') return `${who}さんがコメントしました`
    if (ev.type === 'status_change') {
        const to = ev.meta?.to ? `「${ev.meta.to}」` : ''
        return `ステータスが${to}になりました`
    }
    // new_record: grouped import rows carry meta.count and no record link
    if (ev.meta?.count) return `CSVで${ev.meta.count}件のレコードが追加されました`
    return `${who}さんがレコードを追加しました`
}

const fmtWhen = (v?: string): string => {
    if (!v) return ''
    const d = new Date(v)
    if (isNaN(d.getTime())) return ''
    const diff = Date.now() - d.getTime()
    const min = Math.floor(diff / 60000)
    if (min < 1) return 'たった今'
    if (min < 60) return `${min}分前`
    const h = Math.floor(min / 60)
    if (h < 24) return `${h}時間前`
    const p = (n: number) => String(n).padStart(2, '0')
    return `${d.getMonth() + 1}/${d.getDate()} ${p(d.getHours())}:${p(d.getMinutes())}`
}

const openEvent = (ev: BellEvent) => {
    close()
    if (ev.record_number != null) {
        router.push({ name: 'flow-record-detail', params: { flowId: props.defId, recordId: ev.record_number } })
    } else {
        // grouped import events have no single record → the app's record list
        router.push({ name: 'flow-records', params: { flowId: props.defId } })
    }
}
</script>

<style scoped>
.fbell { position: relative; display: inline-flex; }
.fbell-btn { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border: none; background: none; border-radius: 6px; cursor: pointer; }
.fbell-btn:hover, .fbell-btn.on { background: var(--bg3); }
.fbell-badge { position: absolute; top: -4px; right: -5px; }
.fbell-menu { position: absolute; right: 0; top: calc(100% + 6px); z-index: 60; width: 300px; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 8px; box-shadow: 0 6px 20px rgba(0, 0, 0, .14); padding: 6px; cursor: default; }
.fbell-head { display: flex; align-items: center; justify-content: space-between; padding: 4px 8px 6px; border-bottom: 1px solid var(--formBorder); }
.fbell-title { font-size: 12px; color: var(--sub-color); letter-spacing: .04em; }
.fbell-gear { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border: none; background: none; border-radius: 5px; cursor: pointer; fill: var(--primary-color); opacity: .65; }
.fbell-gear:hover, .fbell-gear.on { background: var(--bg3); opacity: 1; }
.fbell-list { max-height: 320px; overflow-y: auto; padding-top: 4px; }
/* position/box pinned explicitly — a global button rule otherwise leaks position:absolute + top:50px in here */
.fbell-item { position: relative; inset: auto; box-sizing: border-box !important; display: flex; align-items: flex-start; gap: 7px; width: 100%; border: none; background: none; text-align: left; padding: 8px; border-radius: 6px; cursor: pointer; }
.fbell-item:hover { background: var(--bg3); }
.fbell-item.unread .fbell-item-text { font-weight: 600; }
/* unread marker: the app's green-dot convention */
.fbell-dot { width: 7px; height: 7px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; background: transparent; }
.fbell-dot.show { background: #35c759; }
.fbell-item-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.fbell-item-text { font-size: 12.5px; color: var(--primary-color); line-height: 1.5; }
.fbell-item-sub { font-size: 11px; color: var(--sub-color); }
.fbell-empty { padding: 22px 10px; text-align: center; font-size: 12px; color: var(--sub-color); }
.fbell-prefs { padding: 8px 8px 4px; }
.fbell-pref { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 0; }
.fbell-pref-label { font-size: 12px; color: var(--primary-color); line-height: 1.4; }
.fbell-sw { transform: scale(.85); }
.fbell-pref-hint { margin: 8px 0 4px; font-size: 11px; color: var(--sub-color); line-height: 1.5; }
</style>
