<template>
    <Modal persist @close="emit('close')">
        <template #title>レコードから検索</template>
        <template #content>
            <div class="rs-bar">
                <input
                    ref="inputEl"
                    v-model="kw"
                    type="search"
                    class="custom-a-input !box-border w-full"
                    placeholder="すべてのアプリのレコードを検索"
                    autocomplete="off"
                    spellcheck="false"
                >
                <div v-if="loading" class="spinner-nano rs-spin"></div>
            </div>

            <p class="rs-meta">
                <template v-if="!query">キーワードを入力してください。</template>
                <template v-else-if="loading">検索中…</template>
                <!-- "no matches" would contradict the truncation notice below, so stay quiet when the
                     candidate cap was hit: nothing was found *within the part we looked at*, which is
                     not the same claim -->
                <template v-else-if="!total && !truncated">「{{ query }}」に一致するレコードはありません。</template>
                <template v-else-if="total">{{ total }}件中 {{ from }}–{{ to }}件を表示</template>
            </p>
            <!-- the server stops collecting candidates at a cap; say so rather than presenting a
                 partial result as if it were everything -->
            <p v-if="truncated" class="rs-warn">
                候補が多すぎるため、一部のみを検索しました。キーワードを追加して絞り込んでください。
            </p>

            <div class="rs-list">
                <button v-for="h in hits" :key="`${h.record_id}-${h.field_label}`" type="button" class="rs-hit" @click="openRecord(h)">
                    <div class="rs-hit-top">
                        <FlowAppIcon
                            class="rs-appico"
                            :icon-svg="apps[h.definition_id]?.icon_svg"
                            :icon-image="apps[h.definition_id]?.icon_image"
                            :color-id="apps[h.definition_id]?.color_id"
                            :name="h.definition_name"
                            :seed="h.definition_id"
                            :size="22"
                        />
                        <span class="rs-app">{{ h.definition_name }}</span>
                        <span class="rs-num">#{{ h.record_number }}</span>
                        <span class="rs-date">{{ shortDate(h.updated_at) }}</span>
                    </div>
                    <div class="rs-hit-body">
                        <span class="rs-field">{{ h.field_label }}</span>
                        <span class="rs-value"><template v-for="(p, i) in highlight(h.value)" :key="i"><mark v-if="p.hit" class="rs-mark">{{ p.text }}</mark><template v-else>{{ p.text }}</template></template></span>
                    </div>
                </button>
            </div>

            <div v-if="pages > 1" class="rs-pager">
                <PostSearchPager
                    :possiblePage="pages"
                    :activePath="page"
                    @setNavi="(d: number) => go(page + d)"
                    @setActivePage="(p: number) => go(p)"
                />
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import Modal from '@/components/Global/Modal.vue'
import FlowAppIcon from './FlowAppIcon.vue'
import PostSearchPager from '@/components/Post/PostSearchPager.vue'
import { useApi } from '@/composables/api'

interface Hit {
    definition_id: number
    definition_name: string
    record_id: number
    record_number: number
    field_label: string
    value: string
    updated_at: string | null
}

const props = defineProps<{ initialKeyword?: string }>()
const emit = defineEmits<{ close: [] }>()

const api = useApi()
const router = useRouter()
const inputEl = ref<HTMLInputElement | null>(null)
const kw = ref(props.initialKeyword ?? '')
const query = ref('')          // the keyword the current results belong to
const hits = ref<Hit[]>([])
const apps = ref<Record<number, { id: number; name: string; icon_svg: string | null; icon_image: string | null; color_id: number | null }>>({})
const total = ref(0)
const page = ref(1)
const perPage = 20
const truncated = ref(false)
const loading = ref(false)

const pages = computed(() => Math.max(1, Math.ceil(total.value / perPage)))
const from = computed(() => (total.value ? (page.value - 1) * perPage + 1 : 0))
const to = computed(() => Math.min(total.value, page.value * perPage))

/** Only the newest request may write results — typing fast otherwise lets a slow earlier
 *  response land last and overwrite the list with stale hits. */
let seq = 0
const run = async (p: number) => {
    const term = kw.value.trim()
    if (!term) {
        query.value = ''
        hits.value = []
        apps.value = {}
        total.value = 0
        truncated.value = false

        return
    }
    const mine = ++seq
    loading.value = true
    try {
        const data: any = await api.get('/flow_record_search', { q: term, page: p, per_page: perPage })
        if (mine !== seq) return
        hits.value = data?.hits ?? []
        apps.value = data?.apps ?? {}
        total.value = data?.total ?? 0
        truncated.value = !!data?.truncated
        query.value = term
        page.value = p
    } finally {
        if (mine === seq) loading.value = false
    }
}

let timer: ReturnType<typeof setTimeout> | undefined
watch(kw, () => {
    clearTimeout(timer)
    timer = setTimeout(() => run(1), 300)
})

const go = (p: number) => run(Math.min(Math.max(1, p), pages.value))

/** Split a value on the search term so the matching part can be marked. */
const highlight = (value: string): { text: string; hit: boolean }[] => {
    const term = query.value.trim()
    if (!term) return [{ text: value, hit: false }]
    const out: { text: string; hit: boolean }[] = []
    const hay = value.toLowerCase()
    const needle = term.toLowerCase()
    let i = 0
    for (;;) {
        const at = hay.indexOf(needle, i)
        if (at < 0) break
        if (at > i) out.push({ text: value.slice(i, at), hit: false })
        out.push({ text: value.slice(at, at + needle.length), hit: true })
        i = at + needle.length
    }
    if (i < value.length) out.push({ text: value.slice(i), hit: false })

    return out.length ? out : [{ text: value, hit: false }]
}

const shortDate = (iso: string | null) => (iso ? iso.slice(0, 10) : '')

const openRecord = (h: Hit) => {
    // land on the app's record list; opening the record itself is the list's own concern
    router.push({ name: 'flow-records', params: { flowId: h.definition_id } })
    emit('close')
}

onMounted(async () => {
    await nextTick()
    inputEl.value?.focus()
    if (kw.value.trim()) run(1)
})
</script>

<style scoped>
.rs-bar { position: relative; display: flex; align-items: center; }
.rs-spin { position: absolute; right: 10px; }
.rs-meta { font-size: 12px; color: gray; margin: 10px 0 0; }
.rs-warn { font-size: 12px; color: var(--primary-color); background: var(--bg3); border-radius: 6px; padding: 6px 9px; margin: 8px 0 0; }
/* no max-height/overflow here on purpose: the modal shell (.scrollable) is the scroller, and
   giving the list its own made the panel scroll inside a scroll */
.rs-list { display: flex; flex-direction: column; margin-top: 8px; }
.rs-hit { box-sizing: border-box; width: 100%; text-align: left; background: none; border: none; border-bottom: 1px solid var(--calendarBorder); padding: 9px 4px; cursor: pointer; letter-spacing: normal; }
.rs-hit:hover { background: var(--bg3); }
.rs-hit-top { display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: gray; flex-wrap: wrap; }
.rs-app { color: var(--primary-color); font-size: 13px; }
.rs-num { font-variant-numeric: tabular-nums; }
.rs-date { margin-left: auto; font-variant-numeric: tabular-nums; }
.rs-hit-body { display: flex; gap: 8px; margin-top: 3px; font-size: 13px; min-width: 0; }
.rs-field { color: gray; font-size: 12px; flex-shrink: 0; max-width: 40%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rs-value { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rs-mark { background: var(--bg3); color: inherit; padding: 0 1px; }
.rs-pager { margin-top: 14px; }
.rs-appico { flex-shrink: 0; }
</style>
