<template>
    <!-- 対応待ち: strict live count — appears only while records await THIS user's action.
         Not a notification: no read state, no prefs; it drops only when the record moves on. -->
    <div v-if="count > 0" ref="rootEl" class="fpend">
        <button class="fpend-btn" :class="{ on: open }" :title="`対応待ち ${count}件`" @click.stop="toggle">
            <TaskIcon size="18" />
            <Badge class="fpend-badge" :count="count" />
        </button>

        <!-- teleported: overflow-clipping ancestors (fc-table-scroll) would cut an in-place dropdown -->
        <Teleport to="body">
        <div v-if="open" ref="menuEl" class="fpend-menu" :style="{ top: menuPos.top + 'px', left: menuPos.left + 'px' }" @click.stop>
            <div class="fpend-head">
                <span class="fpend-title">対応待ち</span>
                <span class="fpend-hint">あなたの対応が必要なレコード</span>
            </div>
            <div class="fpend-list">
                <div v-if="loading" class="fpend-empty">読み込み中…</div>
                <template v-else-if="items.length">
                    <button v-for="it in items" :key="it.record_id" class="fpend-item" @click="openRecord(it)">
                        <span class="fpend-dot"></span>
                        <span class="fpend-item-body">
                            <span class="fpend-item-text">#{{ it.record_number }}<template v-if="it.status">・{{ it.status }}</template></span>
                            <span class="fpend-item-sub">{{ fmtWhen(it.updated_at) }}</span>
                        </span>
                    </button>
                </template>
                <div v-else class="fpend-empty">対応待ちはありません</div>
            </div>
        </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import 'styles/flow-shared.css'
import { nextTick, onBeforeUnmount, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import Badge from '@/components/Global/Badge.vue'
import TaskIcon from '@/components/Icons/TaskIcon.vue'

interface PendingItem {
    record_id: number
    record_number: number
    status?: string | null
    updated_at?: string
}

const props = defineProps<{
    defId: number
    count: number
}>()

const api = useApi()
const router = useRouter()

const open = ref(false)
const loading = ref(false)
const items = ref<PendingItem[]>([])
const rootEl = ref<HTMLElement | null>(null)
const menuEl = ref<HTMLElement | null>(null)
const menuPos = reactive({ top: 0, left: 0 })

const onOutside = (e: MouseEvent) => {
    const t = e.target as Node
    if (rootEl.value?.contains(t) || menuEl.value?.contains(t)) return
    close()
}
// the menu is position:fixed, so any scroll would leave it floating detached from its trigger
const onScroll = () => close()
const close = () => {
    open.value = false
    document.removeEventListener('mousedown', onOutside)
    window.removeEventListener('scroll', onScroll, true)
}
const MENU_WIDTH = 300
const EDGE = 8

const place = () => {
    const r = rootEl.value?.getBoundingClientRect()
    if (!r) return
    // keep clear of the side menu (it wins by paint order over anything beneath it)
    const sideRight = document.querySelector('.side-menu-root')?.getBoundingClientRect().right ?? 0
    let left = r.right - MENU_WIDTH
    if (left < sideRight + EDGE) left = r.left
    menuPos.left = Math.max(EDGE, Math.min(left, window.innerWidth - MENU_WIDTH - EDGE))
    menuPos.top = r.bottom + 6
}
// after content loads, flip above the trigger if the menu runs past the viewport bottom
const clampVertical = async () => {
    await nextTick()
    const r = rootEl.value?.getBoundingClientRect()
    const h = menuEl.value?.offsetHeight ?? 0
    if (!r || !h) return
    if (menuPos.top + h > window.innerHeight - EDGE) menuPos.top = Math.max(EDGE, r.top - 6 - h)
}

const toggle = async () => {
    if (open.value) { close(); return }
    place()
    open.value = true
    document.addEventListener('mousedown', onOutside)
    window.addEventListener('scroll', onScroll, true)
    loading.value = true
    try {
        const data = await api.get(`/flow_pending_actions/${props.defId}`)
        items.value = data?.items ?? []
    } finally {
        loading.value = false
        clampVertical()
    }
}
onBeforeUnmount(close)

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

const openRecord = (it: PendingItem) => {
    close()
    router.push({ name: 'flow-record-detail', params: { flowId: props.defId, recordId: it.record_number } })
}
</script>

<style scoped>
.fpend { position: relative; display: inline-flex; }
.fpend-btn { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border: none; background: none; border-radius: 6px; cursor: pointer; color: var(--primary-color); }
.fpend-btn:hover, .fpend-btn.on { background: var(--bg2); }
.fpend-badge { position: absolute; top: -4px; right: -4px; left: auto; }
/* fixed + teleported to body: immune to overflow-clipping ancestors and stacking-context traps */
.fpend-menu { position: fixed; z-index: 1000; width: 300px; box-sizing: border-box !important; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 8px; box-shadow: 0 6px 20px rgba(0, 0, 0, .14); padding: 6px; cursor: default; }
.fpend-head { display: flex; align-items: baseline; gap: 8px; padding: 4px 8px 6px; border-bottom: 1px solid var(--formBorder); }
.fpend-title { font-size: 12px; color: var(--sub-color); letter-spacing: .04em; flex-shrink: 0; }
.fpend-hint { font-size: 10.5px; color: var(--sub-color); opacity: .75; }
.fpend-list { max-height: 320px; overflow-y: auto; padding-top: 4px; }
/* position/box pinned explicitly — a global button rule otherwise leaks position:absolute + top:50px in here */
.fpend-item { position: relative; inset: auto; box-sizing: border-box !important; display: flex; align-items: flex-start; gap: 7px; width: 100%; border: none; background: none; text-align: left; padding: 8px; border-radius: 6px; cursor: pointer; }
.fpend-item:hover { background: var(--bg3); }
/* action-required marker: filled dot, always on (this list has no read state) */
.fpend-dot { width: 7px; height: 7px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; background: tomato; }
.fpend-item-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.fpend-item-text { font-size: 12.5px; font-weight: 600; color: var(--primary-color); line-height: 1.5; }
.fpend-item-sub { font-size: 11px; color: var(--sub-color); }
.fpend-empty { padding: 22px 10px; text-align: center; font-size: 12px; color: var(--sub-color); }
</style>
