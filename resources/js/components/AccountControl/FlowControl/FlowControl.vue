<template>
    <div class="admin-window fc-screen">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div class="post-header">
            <HamBurger v-if="responsive.mobile" />
            <div v-show="tab === 'all'" class="fc-header-tools">
                <div class="post-search-wrap">
                    <PostSearchBar className="newChatMemberSearch" :customPlaceHolder="'アプリを検索'" @searchStart="onSearch" />
                </div>
                <!-- view toggle: stays next to the search on every breakpoint (req 8) -->
                <div class="fc-viewtoggle">
                    <button class="fc-vt-btn" :class="{ on: viewMode === 'grid' }" title="グリッド表示" @click="setViewMode('grid')">
                        <Grid size="13" />
                    </button>
                    <button class="fc-vt-btn" :class="{ on: viewMode === 'table' }" title="リスト表示" @click="setViewMode('table')">
                        <List size="13" />
                    </button>
                </div>
            </div>
        </div>

        <div class="fc-tabs">
            <button class="fc-tab" :class="{ on: tab === 'all' }" @click="tab = 'all'">全て</button>
            <button class="fc-tab" :class="{ on: tab === 'waiting' }" @click="tab = 'waiting'">
                対応待ち<Badge :count="waiting.length" />
            </button>
            <!-- sort control lives at the right end of the tab bar (all breakpoints) -->
            <div v-if="tab === 'all' && definitions.length" class="fc-sort-wrap fc-tabs-sort">
                <select v-model="sort" class="fc-sort" @change="savePrefs">
                    <option value="created_desc">新しい順</option>
                    <option value="created_asc">古い順</option>
                    <option value="updated_desc">更新順</option>
                    <option value="name">名前順</option>
                    <option value="mine">自分の作成</option>
                </select>
                <span class="fc-sort-arrow" aria-hidden="true">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                </span>
            </div>
        </div>

        <FloatButton v-if="tab === 'all'" hideOn="fcBody" @action="openBuilder()">
            <template #icon>
                <AddIcon size="15"/>
            </template>
        </FloatButton>

        <div id="fcBody" class="fc-body">
            <!-- 全て: apps -->
            <template v-if="tab === 'all'">
                <div v-if="!loading && definitions.length === 0" class="fc-empty">
                    <div class="fc-empty-ico" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31.91745 29.06039" style="width:28px;height:auto;fill:currentColor;">
                            <path d="M28.3784,17.98843c-.20726-.08255-.33136-.29179-.30513-.51334.00015-.0013.00031-.00259.00046-.00389.11717-1.04865.07001-2.14202-.09717-3.18336-.01592-.13641-.06282-.39613-.09503-.52807-.13395-.68726-.35798-1.41362-.64503-2.05267-.7143-1.65654-1.96391-3.01857-3.33762-4.13222-.60618-.51334-1.28103-.94207-1.99338-1.29526-.16739-.08299-.27298-.25718-.26677-.44391.00171-.05131.0026-.1027.00262-.15415.0312-5.06203-6.04909-7.5754-9.6318-4.01094-1.19352,1.2183-1.67982,2.72544-1.60494,4.17672.0098.18993-.09295.36899-.26364.45287-2.41726,1.18789-4.4532,3.24905-5.43454,5.7688-.38072,1.0287-.59306,2.12819-.68007,3.21511-.07692.71421-.0834,1.4407-.01855,2.16016.01961.21752-.11296.41762-.31805.4927-.73765.27006-1.4449.70875-2.0749,1.33555-3.03344,3.0964-1.51308,8.06074,2.23265,9.22363.54761.17002,1.26193.24856,1.77823.24856s1.42757-.13691,2.10011-.39261c.6542-.24025,1.24692-.61581,1.75162-1.08672.1649-.15386.41124-.17943.59944-.05514,1.75836,1.16122,3.87763,1.83354,5.97678,1.85014,1.33841.00112,2.65986-.34564,3.89833-.82311.69471-.25382,1.3644-.57726,1.99221-.96669.18274-.11335.41729-.08634.57655.05816.55962.50778,1.23125.90592,1.99741,1.14379.54761.17002,1.26193.24858,1.77823.24858s1.42757-.13693,2.10011-.39264c2.0663-.75879,3.52001-2.86679,3.52081-5.06865.01581-2.56442-1.53707-4.47409-3.53892-5.27141ZM14.13406,3.79077c1.00061-1.01888,2.77199-1.01956,3.77405-.00194,1.40332,1.37643.91387,3.51976-.57562,4.41865-.37792.22807-1.0684.36899-1.56143.33004-.49302-.03895-1.22249-.34905-1.72432-.88722-1.00922-1.04989-.97828-2.87006.08731-3.85953ZM6.93782,25.80937c-.37792.22807-1.0684.36899-1.56143.33004s-1.22249-.34905-1.72432-.88722c-1.00922-1.04989-.97828-2.87006.0873-3.85953,1.00061-1.01889,2.772-1.01956,3.77406-.00194,1.40332,1.37643.91387,3.51976-.57562,4.41865ZM17.61638,26.22666c-2.3407.19026-4.37003.00483-6.39274-.94253-.17855-.08363-.26789-.28481-.21003-.4733.15129-.49289.23287-1.00746.23306-1.52739.01856-3.01011-2.12406-5.11827-4.61738-5.58384-.18251-.03408-.31818-.1912-.31932-.37686-.00269-.4376.01612-.87546.05107-1.31233.15139-1.29947.64675-2.53644,1.25446-3.65903.3303-.62248.71762-1.25404,1.14565-1.81391.57047-.75263,1.25315-1.39724,2.01074-1.95342.1832-.1345.4392-.08509.56406.10481.66756,1.01526,1.66397,1.81971,2.90625,2.20539.54761.17,1.26193.24856,1.77823.24856s1.42757-.13693,2.10011-.39262c1.07203-.39367,1.97854-1.15087,2.60331-2.09564.12304-.18606.37319-.23905.55532-.11025.14108.09977.27942.20092.41091.29932.67272.51968,1.29582,1.11751,1.77687,1.82274.1448.18543.33305.50714.46283.70532.60668,1.01337,1.11522,2.10648,1.47063,3.2343.23882.86028.3603,1.74758.37825,2.63871.00397.19704-.13819.36504-.33316.39378-1.12138.16531-2.2256.68082-3.15996,1.61042-1.59399,1.62708-1.92912,3.76937-1.346,5.60535.05923.1865-.02734.38673-.20309.47276-.98149.48046-2.02634.82299-3.12007.89966ZM27.60848,25.78576c-.37793.22807-1.0684.36899-1.56143.33006-.49303-.03895-1.2225-.34907-1.72432-.88722-1.00922-1.04989-.97828-2.87008.0873-3.85953,1.00061-1.01888,2.77199-1.01956,3.77406-.00194,1.40332,1.37641.91387,3.51975-.57562,4.41863Z"/>
                        </svg>
                    </div>
                    <p class="fc-empty-t">まだアプリがありません</p>
                    <button class="fc-empty-btn" @click="openBuilder()">＋ アプリを作成</button>
                </div>

                <template v-else>
                    <p v-if="sortedDefinitions.length === 0" class="fc-empty-line">該当するアプリがありません。</p>

                    <!-- Grid view -->
                    <div v-else-if="viewMode === 'grid'" class="fc-grid">
                        <div v-for="def in sortedDefinitions" :key="def.id" class="fc-card" @click="openRecords(def.id)">
                            <div class="fc-card-top">
                                <FlowAppIcon class="fc-card-ico" :icon-svg="def.icon_svg" :icon-image="def.icon_image" :color-id="def.color_id" :name="def.name" :seed="def.id" :size="44" />
                                <div class="fc-card-head">
                                    <div class="fc-card-name" :title="def.name">{{ def.name }}</div>
                                    <div class="fc-card-flags">
                                        <span v-if="def.pinned" class="fc-flag-pin" title="ピン留め中">
                                            <svg width="12" height="12" viewBox="0 0 32 32" fill="currentColor"><path d="M19.713 28.513c0.045-0.043 0.121-0.125 0.187-0.193 0.067-0.070 0.128-0.148 0.192-0.22 0.122-0.151 0.236-0.306 0.34-0.466 0.414-0.641 0.679-1.346 0.817-2.061 0.137-0.716 0.151-1.449 0.033-2.176-0.062-0.386-0.164-0.773-0.311-1.149-0.037-0.095-0.022-0.198 0.040-0.277l3.236-4.041 3.276-4.116c0.070-0.089 0.184-0.134 0.297-0.121 0.133 0.013 0.267 0.022 0.401 0.022 0.466 0.005 0.925-0.055 1.364-0.169 0.44-0.115 0.861-0.282 1.258-0.502 0.397-0.221 0.773-0.489 1.117-0.834l0.008-0.008 0.005-0.006c0.427-0.434 0.42-1.131-0.013-1.559l-10.277-10.307c-0.44-0.44-1.152-0.441-1.593-0.001l-0.005 0.006c-0.347 0.347-0.618 0.728-0.837 1.129-0.217 0.404-0.38 0.829-0.489 1.269-0.143 0.567-0.191 1.16-0.141 1.75 0.010 0.109-0.034 0.218-0.12 0.286l-4.122 3.291-4.038 3.237c-0.078 0.062-0.184 0.076-0.277 0.040-0.376-0.147-0.762-0.247-1.148-0.31-0.727-0.117-1.46-0.103-2.176 0.033-0.716 0.138-1.42 0.405-2.062 0.818-0.16 0.104-0.316 0.218-0.467 0.339-0.072 0.065-0.149 0.125-0.22 0.193-0.068 0.065-0.15 0.142-0.193 0.187l-0.622 0.621c-0.486 0.485-0.487 1.271-0.001 1.756l0.001 0.002 5.901 5.914c0.058 0.058 0.059 0.15 0.004 0.21-0.199 0.217-0.399 0.433-0.6 0.648-0.394 0.424-0.787 0.852-1.185 1.27-0.796 0.843-1.596 1.679-2.387 2.528l-1.179 1.279-1.167 1.288c-0.775 0.862-1.555 1.722-2.321 2.593-0.333 0.378-0.325 0.964 0.053 1.333 0.365 0.355 0.955 0.347 1.338 0.008 0.863-0.758 1.714-1.529 2.567-2.297l1.288-1.169 1.279-1.179c0.847-0.79 1.685-1.592 2.527-2.386 0.419-0.401 0.846-0.792 1.271-1.186 0.216-0.199 0.431-0.399 0.647-0.6 0.061-0.055 0.153-0.053 0.211 0.005l5.916 5.901c0.484 0.485 1.269 0.484 1.753-0.001l0.625-0.623z"></path></svg>
                                        </span>
                                        <span class="fc-vis">{{ def.is_public ? '全社員' : '限定' }}</span>
                                    </div>
                                </div>
                                <div class="fc-card-menu" @click.stop>
                                    <ItemMenu :items="menuItems(def)" />
                                </div>
                            </div>
                            <div class="fc-card-foot">
                                <span v-if="!def.is_active" class="fc-off">停止中</span>
                                <span class="fc-fi"><span class="fc-num">{{ def.records_count ?? 0 }}</span>件</span>
                                <span class="fc-fi">項目 <span class="fc-num">{{ def.fields_count ?? 0 }}</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Table view -->
                    <div v-else class="fc-table-scroll">
                        <div class="fc-table">
                            <div class="fc-tr fc-th">
                                <div>アプリ名</div>
                                <div class="ar">レコード</div>
                                <div class="ar">項目</div>
                                <div class="ac">公開範囲</div>
                                <div></div>
                            </div>
                            <div v-for="def in sortedDefinitions" :key="def.id" class="fc-tr fc-row" @click="openRecords(def.id)">
                                <div class="fc-td-name">
                                    <FlowAppIcon class="fc-td-ico" :icon-svg="def.icon_svg" :icon-image="def.icon_image" :color-id="def.color_id" :name="def.name" :seed="def.id" :size="30" />
                                    <span class="fc-td-nm" :title="def.name">{{ def.name }}</span>
                                    <span v-if="def.pinned" class="fc-flag-pin" title="ピン留め中">
                                        <svg width="11" height="11" viewBox="0 0 32 32" fill="currentColor"><path d="M19.713 28.513c0.045-0.043 0.121-0.125 0.187-0.193 0.067-0.070 0.128-0.148 0.192-0.22 0.122-0.151 0.236-0.306 0.34-0.466 0.414-0.641 0.679-1.346 0.817-2.061 0.137-0.716 0.151-1.449 0.033-2.176-0.062-0.386-0.164-0.773-0.311-1.149-0.037-0.095-0.022-0.198 0.040-0.277l3.236-4.041 3.276-4.116c0.070-0.089 0.184-0.134 0.297-0.121 0.133 0.013 0.267 0.022 0.401 0.022 0.466 0.005 0.925-0.055 1.364-0.169 0.44-0.115 0.861-0.282 1.258-0.502 0.397-0.221 0.773-0.489 1.117-0.834l0.008-0.008 0.005-0.006c0.427-0.434 0.42-1.131-0.013-1.559l-10.277-10.307c-0.44-0.44-1.152-0.441-1.593-0.001l-0.005 0.006c-0.347 0.347-0.618 0.728-0.837 1.129-0.217 0.404-0.38 0.829-0.489 1.269-0.143 0.567-0.191 1.16-0.141 1.75 0.010 0.109-0.034 0.218-0.12 0.286l-4.122 3.291-4.038 3.237c-0.078 0.062-0.184 0.076-0.277 0.040-0.376-0.147-0.762-0.247-1.148-0.31-0.727-0.117-1.46-0.103-2.176 0.033-0.716 0.138-1.42 0.405-2.062 0.818-0.16 0.104-0.316 0.218-0.467 0.339-0.072 0.065-0.149 0.125-0.22 0.193-0.068 0.065-0.15 0.142-0.193 0.187l-0.622 0.621c-0.486 0.485-0.487 1.271-0.001 1.756l0.001 0.002 5.901 5.914c0.058 0.058 0.059 0.15 0.004 0.21-0.199 0.217-0.399 0.433-0.6 0.648-0.394 0.424-0.787 0.852-1.185 1.27-0.796 0.843-1.596 1.679-2.387 2.528l-1.179 1.279-1.167 1.288c-0.775 0.862-1.555 1.722-2.321 2.593-0.333 0.378-0.325 0.964 0.053 1.333 0.365 0.355 0.955 0.347 1.338 0.008 0.863-0.758 1.714-1.529 2.567-2.297l1.288-1.169 1.279-1.179c0.847-0.79 1.685-1.592 2.527-2.386 0.419-0.401 0.846-0.792 1.271-1.186 0.216-0.199 0.431-0.399 0.647-0.6 0.061-0.055 0.153-0.053 0.211 0.005l5.916 5.901c0.484 0.485 1.269 0.484 1.753-0.001l0.625-0.623z"></path></svg>
                                    </span>
                                    <span v-if="!def.is_active" class="fc-off">停止中</span>
                                </div>
                                <div class="ar"><span class="fc-num">{{ def.records_count ?? 0 }}</span><span class="fc-unit">件</span></div>
                                <div class="ar fc-num">{{ def.fields_count ?? 0 }}</div>
                                <div class="ac"><span class="fc-vis">{{ def.is_public ? '全社員' : '限定' }}</span></div>
                                <div class="fc-td-menu" @click.stop>
                                    <ItemMenu :items="menuItems(def)" />
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
                            <div class="fc-wait-name">{{ it.app_name }}</div>
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
import ItemMenu from '@/components/Global/ItemMenu.vue'
import type { FlowDefinitionListItem } from '@/types/flow'
import type { MenuList } from '@/interface/globalInterface'
import FloatButton from '@/components/Global/FloatButton.vue'
import AddIcon from '@/components/Form/AddIcon.vue'
import PostSearchBar from '@/components/Post/PostSearchBar.vue'
import HamBurger from '@/components/Global/HamBurger.vue'
import Badge from '@/components/Global/Badge.vue'
import Grid from '@/components/Icons/Grid.vue'
import List from '@/components/Icons/List.vue'
import { useAuthUserStore } from '@/store/auth'

interface WaitingItem { app_id: number; app_name: string; record_id: number; record_number: number; status: string | null; updated_at?: string }

const api = useApi()
const router = useRouter()
const responsive = useResponsive()
const auth = useAuthUserStore()
const definitions = ref<FlowDefinitionListItem[]>([])
const waiting = ref<WaitingItem[]>([])
const loading = ref(true)
const search = ref('')
const tab = ref<'all' | 'waiting'>('all')
const sort = ref('created_desc')

// View mode (grid / table) is a per-user client preference — persisted to localStorage and inherited.
type ViewMode = 'grid' | 'table'
const VIEW_KEY = 'flow.apps.viewMode'
const viewMode = ref<ViewMode>(localStorage.getItem(VIEW_KEY) === 'table' ? 'table' : 'grid')
const setViewMode = (m: ViewMode) => {
    viewMode.value = m
    try { localStorage.setItem(VIEW_KEY, m) } catch { /* private mode / quota — non-fatal */ }
}

const menuItems = (def: FlowDefinitionListItem): MenuList[] => [
    { title: def.pinned ? 'ピン留めを外す' : 'ピン留め', action: () => togglePin(def) },
    { title: '編集', action: () => openBuilder(def.id) },
    { title: '削除', action: () => removeDefinition(def.id) },
]

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
    // debounce so rapid sort changes coalesce into one write of the final state (no out-of-order races)
    prefsTimer = setTimeout(() => api.post('/flow_portal_prefs', { sort: sort.value }, { silent: true }), 300)
}
const loadPrefs = async () => {
    const p = await api.get('/flow_portal_prefs', null, { silent: true })
    if (p) sort.value = p.sort || 'created_desc'
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
/* Square / monochrome direction: no rounded corners (except the round app icon), no bold weights,
   colour lives only on the app icons. All surfaces use theme vars so light + dark both hold up. */
.admin-window { color: var(--primary-color); }
.fc-screen { position: relative; display: flex; flex-direction: column; height: 100%; }
.fc-body { flex: 1; overflow: auto; padding: 20px; }

/* header: search + view toggle share one row on every breakpoint */
.fc-header-tools { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
/* keep the search from spanning the whole toolbar on desktop */
.post-search-wrap { flex: 1; min-width: 0; max-width: 520px; }
/* toggle: pinned right with breathing room, height matched to the search input.
   box-sizing is globally content-box here, so 29px content + 2px border = 31px, matching the input */
/* margin-right matches the tab bar / body padding (20px) so it lines up with the sort control below */
.fc-viewtoggle { display: inline-flex; flex-shrink: 0; height: 29px; margin-left: auto; margin-right: 20px; border: 1px solid var(--formBorder); }
.fc-vt-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 100%; border: none; background: var(--background-color); cursor: pointer; padding: 0; }
.fc-vt-btn + .fc-vt-btn { border-left: 1px solid var(--formBorder); }
.fc-vt-btn :deep(svg) { fill: gray; }
.fc-vt-btn.on { background: var(--primary-button, var(--primary-color)); }
/* --primary-button is dark in both themes (#000 / #4b4b4b), so the active glyph is white in both */
.fc-vt-btn.on :deep(svg) { fill: #fff; }

.fc-tabs { display: flex; align-items: center; gap: 4px; padding: 0 20px; }
.fc-tab { position: relative; display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; font-size: 13px; color: gray; background: none; border: none; border-bottom: 2px solid transparent; margin-bottom: -1px; cursor: pointer; }
.fc-tab.on { color: var(--primary-color); border-bottom-color: var(--primary-color); }
.fc-tab :deep(.badge-circle) { position: unset; }

/* controls: sort selector with a linear (stroke) down-arrow */
.fc-sort-wrap { position: relative; display: inline-flex; align-items: center; }
/* sort sits at the right end of the tab bar */
.fc-tabs-sort { margin-left: auto; flex-shrink: 0; }
.fc-sort { height: 30px; padding: 0 30px 0 12px; border: 1px solid var(--formBorder); background: var(--background-color); color: var(--primary-color); font-size: 13px; cursor: pointer; appearance: none; -webkit-appearance: none; }
.fc-sort-arrow { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); display: inline-flex; color: gray; pointer-events: none; }

/* grid view */
.fc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.fc-card { display: flex; flex-direction: column; gap: 14px; background: var(--background-color); border: 1px solid var(--calendarBorder); padding: 16px 18px; cursor: pointer; transition: border-color .12s; }
.fc-card:hover { border-color: var(--primary-color); }
.fc-card-top { display: flex; align-items: flex-start; gap: 12px; }
.fc-card-ico :deep(.fai-initial) { font-weight: 400; }
.fc-card-head { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
.fc-card-name { font-size: 15px; line-height: 1.4; color: var(--primary-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fc-card-flags { display: flex; align-items: center; gap: 8px; }
.fc-card-menu { flex-shrink: 0; margin: -2px -4px 0 0; }
.fc-card-foot { display: flex; align-items: center; gap: 14px; font-size: 12px; color: gray; border-top: 1px solid var(--calendarBorder); padding-top: 12px; }
.fc-fi { display: inline-flex; align-items: baseline; gap: 3px; }
.fc-num { font-size: 15px; color: var(--primary-color); }
.fc-vis { font-size: 11px; color: gray; border: 1px solid var(--calendarBorder); padding: 3px 10px; }
.fc-off { font-size: 11px; color: var(--primary-color); border: 1px solid var(--calendarBorder); padding: 3px 10px; }
.fc-flag-pin { display: inline-flex; align-items: center; color: gray; }

/* table view */
.fc-table-scroll { overflow-x: auto; }
.fc-table { min-width: 620px; border: 1px solid var(--calendarBorder); background: var(--background-color); }
.fc-tr { display: grid; grid-template-columns: 1fr 130px 130px 110px 46px; align-items: center; padding: 11px 18px; }
/* header shade is distinct from BOTH the white rows and the --bg3 page behind the table */
.fc-th { font-size: 12px; color: gray; border-bottom: 1px solid var(--calendarBorder); background: color-mix(in srgb, var(--primary-color) 10%, var(--background-color)); }
.fc-row { border-bottom: 1px solid var(--calendarBorder); cursor: pointer; transition: background-color .1s; }
.fc-row:hover { background: var(--bg3); }
.fc-tr .ar { text-align: right; }
.fc-tr .ac { text-align: center; }
.fc-td-name { display: flex; align-items: center; gap: 10px; min-width: 0; }
.fc-td-ico :deep(.fai-initial) { font-weight: 400; }
.fc-td-nm { font-size: 14px; color: var(--primary-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fc-td-menu { display: flex; justify-content: flex-end; }
.fc-unit { font-size: 12px; color: gray; margin-left: 2px; }
.fc-tr.fc-row .fc-num { font-size: 14px; }

/* empty */
.fc-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; margin-top: 72px; color: gray; }
.fc-empty-ico { width: 60px; height: 60px; background: var(--bg3); display: flex; align-items: center; justify-content: center; color: var(--primary-color); margin-bottom: 8px; }
.fc-empty-t { font-size: 15px; color: var(--primary-color); }
.fc-empty-btn { margin-top: 16px; display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; font-size: 13px; color: #fff; background: var(--primary-button, var(--primary-color)); border: none; cursor: pointer; }
.fc-empty-btn:hover { opacity: .9; }
.fc-empty-line { font-size: 13px; color: gray; margin-top: 40px; text-align: center; }

/* 対応待ち */
.fc-wait { display: flex; align-items: center; gap: 12px; background: var(--background-color); border: 1px solid var(--calendarBorder); padding: 14px 16px; cursor: pointer; transition: border-color .12s; }
.fc-wait:hover { border-color: var(--primary-color); }
.fc-wait-name { font-size: 14px; color: var(--primary-color); }
.fc-wait-st { font-size: 11px; color: var(--primary-color); background: var(--bg3); padding: 2px 9px; }
.fc-wait-arrow { color: gray; font-size: 18px; flex-shrink: 0; }
</style>
