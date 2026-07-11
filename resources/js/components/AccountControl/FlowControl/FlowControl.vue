<template>
    <div class="admin-window fc-screen">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div class="post-header">
            <HamBurger v-if="responsive.mobile" />
            <div v-show="tab === 'all'" class="post-search-wrap">
                <PostSearchBar className="newChatMemberSearch" :customPlaceHolder="'アプリを検索'" @searchStart="onSearch" />
            </div>
        </div>

        <div class="fc-tabs">
            <button class="fc-tab" :class="{ on: tab === 'all' }" @click="tab = 'all'">全て</button>
            <button class="fc-tab" :class="{ on: tab === 'waiting' }" @click="tab = 'waiting'">
                対応待ち<Badge :count="waiting.length" />
            </button>
        </div>

        <FloatButton v-if="tab === 'all'" hideOn="fcBody" @action="openBuilder()">
            <template #icon>
                <AddIcon size="15"/>
            </template>
        </FloatButton>

        <div id="fcBody" class="fc-body">
            <!-- 全て: app tiles -->
            <template v-if="tab === 'all'">
                <div v-if="!loading && definitions.length === 0" class="fc-empty">
                    <div class="fc-empty-ico" aria-hidden="true">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="3" y="3" width="7.5" height="7.5" rx="2" />
                            <rect x="13.5" y="3" width="7.5" height="7.5" rx="2" opacity=".55" />
                            <rect x="3" y="13.5" width="7.5" height="7.5" rx="2" opacity=".55" />
                            <rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2" opacity=".3" />
                        </svg>
                    </div>
                    <p class="fc-empty-t">まだアプリがありません</p>
                    <p class="fc-empty-s">フォーム・一覧・ステータス管理・計算・PDF帳票までを備えたアプリを作成できます。</p>
                    <button class="fc-empty-btn" @click="openBuilder()">＋ アプリを作成</button>
                </div>
                <template v-else>
                    <div class="fc-controls">
                        <select v-model="sort" class="fc-sort" @change="savePrefs">
                            <option value="created_desc">新しい順</option>
                            <option value="created_asc">古い順</option>
                            <option value="updated_desc">更新順</option>
                            <option value="name">名前順</option>
                            <option value="mine">自分の作成</option>
                        </select>
                        <div v-if="!responsive.mobile" class="fc-density">
                            <button v-for="d in densities" :key="d.key" class="fc-density-btn" :class="{ on: density === d.key }" :title="d.label" @click="setDensity(d.key)">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><rect v-for="(r, i) in d.rects" :key="i" :x="r[0]" :y="r[1]" :width="r[2]" :height="r[3]" rx="1" /></svg>
                            </button>
                        </div>
                    </div>

                    <p v-if="sortedDefinitions.length === 0" class="fc-empty-line">該当するアプリがありません。</p>

                    <div v-else class="fc-grid" :class="'fc-d-' + effectiveDensity">
                        <div v-for="def in sortedDefinitions" :key="def.id" class="fc-tile" :style="{ '--app-accent': accentHex(def) }" @click="openRecords(def.id)">
                            <div class="fc-tile-band">
                                <FlowAppIcon class="fc-tile-ico" on-band :icon-svg="def.icon_svg" :icon-image="def.icon_image" :color-id="def.color_id" :name="def.name" :seed="def.id" :size="iconSize" />
                                <div class="fc-tile-flags">
                                    <span v-if="def.pinned" class="fc-tile-pin" title="ピン留め中">
                                        <svg width="14" height="14" viewBox="0 0 32 32" fill="currentColor"><path d="M19.713 28.513c0.045-0.043 0.121-0.125 0.187-0.193 0.067-0.070 0.128-0.148 0.192-0.22 0.122-0.151 0.236-0.306 0.34-0.466 0.414-0.641 0.679-1.346 0.817-2.061 0.137-0.716 0.151-1.449 0.033-2.176-0.062-0.386-0.164-0.773-0.311-1.149-0.037-0.095-0.022-0.198 0.040-0.277l3.236-4.041 3.276-4.116c0.070-0.089 0.184-0.134 0.297-0.121 0.133 0.013 0.267 0.022 0.401 0.022 0.466 0.005 0.925-0.055 1.364-0.169 0.44-0.115 0.861-0.282 1.258-0.502 0.397-0.221 0.773-0.489 1.117-0.834l0.008-0.008 0.005-0.006c0.427-0.434 0.42-1.131-0.013-1.559l-10.277-10.307c-0.44-0.44-1.152-0.441-1.593-0.001l-0.005 0.006c-0.347 0.347-0.618 0.728-0.837 1.129-0.217 0.404-0.38 0.829-0.489 1.269-0.143 0.567-0.191 1.16-0.141 1.75 0.010 0.109-0.034 0.218-0.12 0.286l-4.122 3.291-4.038 3.237c-0.078 0.062-0.184 0.076-0.277 0.040-0.376-0.147-0.762-0.247-1.148-0.31-0.727-0.117-1.46-0.103-2.176 0.033-0.716 0.138-1.42 0.405-2.062 0.818-0.16 0.104-0.316 0.218-0.467 0.339-0.072 0.065-0.149 0.125-0.22 0.193-0.068 0.065-0.15 0.142-0.193 0.187l-0.622 0.621c-0.486 0.485-0.487 1.271-0.001 1.756l0.001 0.002 5.901 5.914c0.058 0.058 0.059 0.15 0.004 0.21-0.199 0.217-0.399 0.433-0.6 0.648-0.394 0.424-0.787 0.852-1.185 1.27-0.796 0.843-1.596 1.679-2.387 2.528l-1.179 1.279-1.167 1.288c-0.775 0.862-1.555 1.722-2.321 2.593-0.333 0.378-0.325 0.964 0.053 1.333 0.365 0.355 0.955 0.347 1.338 0.008 0.863-0.758 1.714-1.529 2.567-2.297l1.288-1.169 1.279-1.179c0.847-0.79 1.685-1.592 2.527-2.386 0.419-0.401 0.846-0.792 1.271-1.186 0.216-0.199 0.431-0.399 0.647-0.6 0.061-0.055 0.153-0.053 0.211 0.005l5.916 5.901c0.484 0.485 1.269 0.484 1.753-0.001l0.625-0.623z"></path></svg>
                                    </span>
                                    <span v-if="!def.is_active" class="fc-tile-off">停止中</span>
                                </div>
                                <div class="fc-tile-menu" @click.stop>
                                    <ItemMenu :items="[
                                        {title: def.pinned ? 'ピン留めを外す' : 'ピン留め', action: () => togglePin(def)},
                                        {title: '編集', action: () => openBuilder(def.id)},
                                        {title: '削除', action: () => removeDefinition(def.id)},
                                    ]"/>
                                </div>
                            </div>
                            <div class="fc-tile-body">
                                <div class="fc-tile-name">{{ def.name }}</div>
                                <p class="fc-tile-desc" v-html="def.description || ''"></p>
                                <div class="fc-tile-meta">
                                    <span class="fc-tile-count">{{ def.records_count ?? 0 }}件</span>
                                    <span class="fc-dot">·</span>
                                    <span>項目 {{ def.fields_count ?? 0 }}</span>
                                    <span class="fc-tile-vis">{{ def.is_public ? '全社員' : '限定' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>

            <!-- 対応待ち: records awaiting the current user's action -->
            <template v-else>
                <p v-if="!loading && !waiting.length" class="text-[13px] text-gray-500 mt-[40px] text-center">
                    対応待ちのレコードはありません。
                </p>
                <div class="w-full flex flex-col gap-[10px]">
                    <div v-for="it in waiting" :key="it.record_id" class="fc-wait" @click="openWaiting(it)">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium">{{ it.app_name }}</div>
                            <div class="flex items-center gap-[10px] mt-[5px]">
                                <span class="text-[12px] text-gray-500">#{{ it.record_number }}</span>
                                <span v-if="it.status" class="fc-wait-st">{{ it.status }}</span>
                            </div>
                        </div>
                        <span class="fc-wait-arrow">›</span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import { useResponsive } from '@/store/responsive'
import FlowAppIcon from './FlowAppIcon.vue'
import { useTheme } from '@/store/theme'
import { flowColorValue } from '@/utils/flowColors'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import type { FlowDefinitionListItem } from '@/types/flow'
import FloatButton from '@/components/Global/FloatButton.vue'
import AddIcon from '@/components/Form/AddIcon.vue'
import PostSearchBar from '@/components/Post/PostSearchBar.vue'
import HamBurger from '@/components/Global/HamBurger.vue'
import Badge from '@/components/Global/Badge.vue'
import { useAuthUserStore } from '@/store/auth'

interface WaitingItem { app_id: number; app_name: string; record_id: number; record_number: number; status: string | null; updated_at?: string }

const api = useApi()
const router = useRouter()
const responsive = useResponsive()
const theme = useTheme()
const auth = useAuthUserStore()
const accentHex = (def: FlowDefinitionListItem) => flowColorValue(def.color_id, theme.dark, def.id)
const definitions = ref<FlowDefinitionListItem[]>([])
const waiting = ref<WaitingItem[]>([])
const loading = ref(true)
const search = ref('')
const tab = ref<'all' | 'waiting'>('all')

type Density = 'compact' | 'normal' | 'spacy'
const density = ref<Density>('normal')
const sort = ref('created_desc')
const densities: { key: Density; label: string; rects: number[][] }[] = [
    { key: 'compact', label: 'コンパクト', rects: [[3, 3, 7, 7], [14, 3, 7, 7], [3, 14, 7, 7], [14, 14, 7, 7]] },
    { key: 'normal', label: '標準', rects: [[3, 3, 8, 8], [13, 3, 8, 8], [3, 13, 8, 8], [13, 13, 8, 8]] },
    { key: 'spacy', label: 'ゆったり', rects: [[3, 3, 18, 8], [3, 13, 18, 8]] },
]
// Mobile always uses the most compact layout, regardless of the saved (desktop) preference.
const effectiveDensity = computed<Density>(() => (responsive.mobile ? 'compact' : density.value))
const iconSize = computed(() => (effectiveDensity.value === 'compact' ? 36 : effectiveDensity.value === 'spacy' ? 54 : 44))

const onSearch = (kw: string) => { search.value = kw }
const sortedDefinitions = computed(() => {
    const kw = search.value.trim().toLowerCase()
    let list = definitions.value
    if (kw) list = list.filter((d) => (d.name ?? '').toLowerCase().includes(kw) || (d.description ?? '').toLowerCase().includes(kw))
    const cmp = (a: FlowDefinitionListItem, b: FlowDefinitionListItem) => {
        switch (sort.value) {
            case 'created_asc': return (a.created_at ?? '').localeCompare(b.created_at ?? '')
            case 'updated_desc': return (b.updated_at ?? '').localeCompare(a.updated_at ?? '')
            case 'name': return (a.name ?? '').localeCompare(b.name ?? '', 'ja')
            case 'mine': {
                const am = a.created_by === auth.id ? 0 : 1
                const bm = b.created_by === auth.id ? 0 : 1
                return am - bm || (b.created_at ?? '').localeCompare(a.created_at ?? '')
            }
            default: return (b.created_at ?? '').localeCompare(a.created_at ?? '') // created_desc
        }
    }
    const sorted = [...list].sort(cmp)
    // pinned apps always float to the top, keeping the chosen order within each group
    return [...sorted.filter((d) => d.pinned), ...sorted.filter((d) => !d.pinned)]
})

const getDefinitions = async () => {
    loading.value = true
    try {
        const data = await api.get('/flow_definitions')
        definitions.value = Array.isArray(data) ? data as FlowDefinitionListItem[] : []
    } finally {
        loading.value = false
    }
}

let prefsTimer: ReturnType<typeof setTimeout> | null = null
const savePrefs = () => {
    if (prefsTimer) clearTimeout(prefsTimer)
    // debounce so rapid density/sort changes coalesce into one write of the final state (no out-of-order races)
    prefsTimer = setTimeout(() => api.post('/flow_portal_prefs', { density: density.value, sort: sort.value }, { silent: true }), 300)
}
const setDensity = (d: Density) => { density.value = d; savePrefs() }
const loadPrefs = async () => {
    const p = await api.get('/flow_portal_prefs', null, { silent: true })
    if (p) { density.value = (p.density as Density) || 'normal'; sort.value = p.sort || 'created_desc' }
}
const togglePin = async (def: FlowDefinitionListItem) => {
    def.pinned = !def.pinned
    await api.post('/flow_app_pin', { flow_definition_id: def.id, pinned: def.pinned }, { silent: true })
}

const openBuilder = (id?: number) => {
    router.push({ name: 'flow-builder', params: id ? { flowId: id } : {} })
}

const openRecords = (id: number) => {
    router.push({ name: 'flow-records', params: { flowId: id } })
}

const getWaiting = async () => {
    const data = await api.get('/flow_dashboard', null, { silent: true })
    waiting.value = (data?.items ?? []) as WaitingItem[]
}

const openWaiting = (it: WaitingItem) => {
    router.push({ name: 'flow-record-detail', params: { flowId: it.app_id, recordId: it.record_number } })
}

const removeDefinition = async (id: number) => {
    const data = await api.post('/flow_definition_delete', { id }, {
        ask: 'このアプリを削除しますか？',
        toast: '削除しました。',
    })
    data && getDefinitions()
}

onMounted(() => {
    getDefinitions()
    getWaiting()
    loadPrefs()
})
</script>

<style scoped>
.admin-window { color: var(--primary-color); }
.fc-screen { position: relative; display: flex; flex-direction: column; height: 100%; }
.fc-body { flex: 1; overflow: auto; padding: 20px; }
.fc-tabs { display: flex; align-items: center; gap: 4px; padding: 0 20px; border-bottom: 1px solid var(--calendarBorder); }
.fc-tab { position: relative; display: inline-flex; align-items: center; gap: 6px; padding: 11px 14px; font-size: 13px; color: gray; background: none; border: none; border-bottom: 2px solid transparent; margin-bottom: -1px; cursor: pointer; }
.fc-tab.on { color: var(--primary-color); border-bottom-color: var(--primary-color); font-weight: 600; }
.fc-tab :deep(.badge-circle) { position: unset; }
/* controls */
.fc-controls { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-bottom: 14px; }
.fc-sort { height: 32px; padding: 0 28px 0 10px; border: 1px solid var(--formBorder); border-radius: 8px; background: var(--background-color); color: var(--primary-color); font-size: 13px; cursor: pointer; }
.fc-density { display: inline-flex; border: 1px solid var(--formBorder); border-radius: 8px; overflow: hidden; }
.fc-density-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: none; border-right: 1px solid var(--formBorder); background: var(--background-color); color: gray; cursor: pointer; }
.fc-density-btn:last-child { border-right: none; }
.fc-density-btn:hover { color: var(--primary-color); }
.fc-density-btn.on { background: var(--bg3); color: var(--primary-color); }
/* tile grid */
.fc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
.fc-grid.fc-d-compact { grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 12px; }
.fc-grid.fc-d-spacy { grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 22px; }
.fc-d-compact .fc-tile-band { height: 44px; }
.fc-d-compact .fc-tile-ico { bottom: -14px; }
.fc-d-compact .fc-tile-body { padding: 20px 13px 12px; }
.fc-d-compact .fc-tile-desc { display: none; }
.fc-d-spacy .fc-tile-band { height: 72px; }
.fc-d-spacy .fc-tile-ico { bottom: -20px; }
.fc-d-spacy .fc-tile-body { padding: 30px 18px 18px; }
.fc-tile { position: relative; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 12px; overflow: hidden; cursor: pointer; transition: transform .12s, box-shadow .12s, border-color .12s; }
.fc-tile:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(0,0,0,.10); border-color: transparent; }
.fc-tile-band { position: relative; height: 56px; background: var(--app-accent); }
.fc-tile-ico { position: absolute; left: 16px; bottom: -16px; }
.fc-tile-menu { position: absolute; top: 6px; right: 6px; }
.fc-tile-flags { position: absolute; top: 9px; right: 38px; display: flex; align-items: center; gap: 6px; }
.fc-tile-pin { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: var(--background-color); color: color-mix(in srgb, var(--app-accent) 55%, var(--primary-color)); }
.fc-tile-off { font-size: 10.5px; color: var(--primary-color); background: var(--background-color); border-radius: 10px; padding: 2px 8px; opacity: .9; }
.fc-tile-body { padding: 24px 16px 15px; }
.fc-tile-name { font-size: 14px; font-weight: 600; line-height: 1.5; color: var(--primary-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fc-tile-desc { font-size: 12px; color: gray; margin-top: 6px; line-height: 1.5; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.fc-tile-meta { display: flex; align-items: center; gap: 7px; font-size: 12px; color: gray; margin-top: 10px; }
.fc-tile-vis { margin-left: auto; font-size: 11px; padding: 1px 8px; border: 1px solid var(--calendarBorder); border-radius: 10px; }
.fc-tile-count { color: var(--primary-color); font-weight: 500; }
.fc-dot { color: var(--calendarBorder); }
/* empty */
.fc-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; margin-top: 72px; color: gray; }
.fc-empty-ico { width: 60px; height: 60px; border-radius: 16px; background: var(--bg3); display: flex; align-items: center; justify-content: center; color: var(--primary-color); margin-bottom: 8px; }
.fc-empty-t { font-size: 15px; font-weight: 600; color: var(--primary-color); }
.fc-empty-s { font-size: 12.5px; color: gray; max-width: 320px; text-align: center; line-height: 1.6; }
.fc-empty-btn { margin-top: 16px; display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; font-size: 13px; font-weight: 600; color: #fff; background: var(--primary-button, var(--primary-color)); border: none; border-radius: 8px; cursor: pointer; }
.fc-empty-btn:hover { opacity: .9; }
.fc-empty-line { font-size: 13px; color: gray; margin-top: 40px; text-align: center; }
.fc-wait { display: flex; align-items: center; gap: 12px; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 14px 16px; cursor: pointer; transition: border-color .12s; }
.fc-wait:hover { border-color: var(--primary-color); }
.fc-wait-st { font-size: 11px; color: var(--primary-color); background: var(--bg3); padding: 2px 9px; border-radius: 10px; }
.fc-wait-arrow { color: gray; font-size: 18px; flex-shrink: 0; }
.flow-primary-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    font-size: 13px;
    color: #fff;
    background: var(--primary-button, var(--primary-color));
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
.flow-primary-btn:hover { opacity: 0.9; }
</style>
