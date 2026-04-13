<template>
    <div id="dashBoardContainer" class="w-full h-full overflow-hidden relative">
        <div class="w-full h-full overflow-y-scroll bg-[var(--bg3)] relative" :class="{'hidescroll' : route.params.type}" ref="sortParent" @scroll="handleScroll">
            <div class="mem-header-section mobile" :style="{'transform': `translateY(${offset}px)`}">        
                <div class="post-header sticky top-0 z-[11] bg-[var(--background-color)]" >
                    <HamBurger />       
                    <div class="text-[14px] text-[var(--primary-color)]">ダッシュボード</div>   
                </div>
            </div>
            <div ref="mParent" class="dashboard-parent p-5 grid grid-cols-4 gap-5 under960:grid-cols-1 items-stretch">
                <template v-if="initialLoader">
                    <div
                        v-for="s in skeletonCards"
                        :key="s.id"
                        class="dashboard-card-item"
                        :class="[s.col, 'min-w-0 w-full rounded overflow-hidden bg-[var(--bg2)] animate-pulse under960:col-span-1']"
                        :style="{ height: s.height + 'px' }"
                    >
                        <div class="p-4 h-full flex flex-col gap-3">
                            <div class="h-4 w-2/3 rounded bg-[var(--bg1)] opacity-50" />
                            <div class="h-3 w-full rounded bg-[var(--bg1)] opacity-35" />
                            <div class="h-3 w-5/6 rounded bg-[var(--bg1)] opacity-35" />
                            <div class="mt-auto h-8 w-24 rounded bg-[var(--bg1)] opacity-25" />
                        </div>
                    </div>
                </template>
                <component
                    v-for="card in dashboardCards"
                    :key="`${card.type}-${updateKey}`"
                    v-show="!initialLoader && shouldShowCard(card)"
                    :is="DASHBOARD_COMPONENTS[card.layout]"
                    class="dashboard-card-item"
                    :class="[card.col, 'min-w-0 w-full']"
                    :fullscreen="route.params.type === card.type"
                    :data="card"
                    :id="`card-${card.type}`"
                    ref="cardLayouts"
                    @toggle="toggle"
                    @resize="(type:string) => resize(type)"
                    @refreshData="refreshData"
                />

            </div>
            <FloatButton :hide-on="sortParent" title="サポートデスク" :order="2" class="fixed" @action="router.push({name: 'regulations'})">
                <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px;height: 22px;" fill="var(--primary-color)" viewBox="0 0 240.28505 248.17445">
                        <path d="M218.18097,97.18395c-1.82721-17.12347-5.58807-33.48346-14.245-48.15063C185.20264,15.22589,149.64588-3.19739,111.34595.4577,60.98829,5.26348,23.0387,46.61529,22.31,97.19506c-24.08392,13.19543-28.46429,46.21063-14.13135,68.35309,9.30396,14.37335,25.2088,20.65472,41.95911,19.43524,4.65363-.33881,8.97003-2.26715,8.95428-7.8125l-.224-78.94958c-.00952-3.37378-2.97369-7.11548-6.25531-7.0152l-17.1665.52472C39.3078,45.46984,77.17139,11.18566,123.5055,12.52221c43.64526,1.25903,75.67639,35.87048,81.10394,79.16583l-16.70581-.72546c-3.73932-.16241-6.65942,3.71735-6.64777,7.51208l.24323,78.85565c.01166,3.76337,2.85779,7.4599,7.25238,7.36237l15.36676-.34113c-6.46051,23.96423-26.26788,36.82068-50.48389,37.24371-2.69196-8.0752-8.99866-14.11334-17.37292-14.1463l-19.9046-.07837c-10.82007-.04254-18.62231,9.58582-19.06335,19.58441-.44067,9.99048,6.37451,19.89001,17.17834,21.15643l21.737.06293c8.88385.02576,15.03302-6.60071,17.91583-14.51257,33.15387.93512,60.40399-22.65192,64.18945-55.01428,6.92743-4.17523,12.21924-9.31702,15.97937-16.29437,11.70764-21.72473,6.21655-52.06543-16.11249-65.16919ZM46.43689,103.651l.1792,68.23822c-9.38287,1.09918-19.68134-2.86859-25.88196-10.87268-8.85162-11.42603-10.40411-27.35675-4.11859-40.1441,5.7207-11.63806,16.89026-17.73834,29.82135-17.22144ZM133.57441,235.9497l-14.98926.02094c-4.72955.00659-8.09363-2.8537-8.65808-7.12598-.45056-3.41064,2.12286-8.86975,6.69977-8.99701l16.72394-.46484c4.93878-.13733,8.18817,3.49164,8.58527,7.78973.41284,4.46826-2.79443,8.76941-8.36163,8.77716ZM220.5246,160.06024c-6.43311,8.73248-16.59039,12.1283-26.38428,11.92389l-.11743-68.10461c9.93091-1.06567,19.29919,3.10608,25.72235,11.34149,9.96277,12.7738,10.7937,31.2453.77936,44.83923Z"/>
                    </svg>
                </template>
            </FloatButton>
            <FloatButton :hide-on="sortParent" title="データを更新" @action="refreshAll" class="fixed" v-if="!route.params.type">
                <template #icon>
                    <svg v-if="!initialLoader" xmlns="http://www.w3.org/2000/svg" width="406.7002" height="448.97456" viewBox="0 0 406.7002 448.97456">
                        <path d="M269.42244,400.48149c89.40405-38.52608,127.74738-143.45953,84.52156-230.37382-4.00132-8.04547-.26147-17.82743,7.09537-22.04708,7.4958-4.29935,18.71269-3.19281,23.2254,5.40907,20.95447,39.94219,27.1756,85.82814,18.89384,129.76056-19.02756,100.93584-110.71041,171.77738-212.55189,165.33852C89.88917,442.20092,8.2668,362.26379.5443,261.0774c-2.28189-29.8992,2.63636-63.24923,14.27731-91.50091,25.44743-61.75894,78.66763-107.53931,144.41752-122.44033l-19.58257-16.43668c-7.42992-6.23632-8.21032-17.1677-2.31285-24.29177,6.18069-7.46619,16.86033-8.68422,24.91843-2.18939l51.8508,41.79173c6.84966,5.52083,8.93392,15.44934,4.04718,22.84488l-36.39742,55.08348c-5.60688,8.48539-17.40599,9.55259-24.3728,4.29712-8.40154-6.33776-9.11161-16.578-3.67234-25.07838l13.93379-21.77543c-31.98287,6.59331-59.7407,22.17515-82.69216,44.87814-41.19269,40.74673-58.67726,98.6188-45.74298,156.9487,11.22378,50.61602,47.48919,95.46628,97.6474,117.14014,41.87034,18.09258,90.2506,18.36429,132.55882.13279Z"/>
                    </svg>
                    <div v-else class="spinner-nano"></div>
                </template>
            </FloatButton>
        </div>
        
            <router-view v-slot="{Component}">
                <Transition name="modalFade">
                    <component :is="Component"/>
                </Transition>
            </router-view>
        
    </div>
</template>
<script lang="ts" setup>
import { useAuthUserStore } from '@/store/auth';
import { nextTick, onMounted, provide, ref, useTemplateRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useResizeObserver } from '@vueuse/core';
import { useDialog } from '@/composables/dialog';
import { useSortable } from '@vueuse/integrations/useSortable.mjs';
import HamBurger from '../Global/HamBurger.vue';
import FloatButton from '../Global/FloatButton.vue';
import { useDashboardStore } from '@/store/dashboard';
import { useDashboardPrefsStore } from '@/store/dashboardPrefs';
import { useDashboardGoalsStore } from '@/store/dashboardGoals';
import { DashboardCard } from '@/interface/dashboard';
import { 
    DASHBOARD_COMPONENTS, 
    getDefaultDashboardCards,
    ADMIN_PERSONNEL_EVALUATION_CARD,
    CARD_DATA_KEY_BY_TYPE,
    CARD_ADMIN_DATA_KEY_BY_TYPE,
    CARD_REFRESH_KEYS_BY_TYPE,
    CARD_ADMIN_REFRESH_KEYS_BY_TYPE,
    shouldShowCard
} from '@/config/dashboardCards';
import './dashboard.css'

const auth = useAuthUserStore()
const initialLoader = ref(true)
const cardLayouts = useTemplateRef('cardLayouts')
const mParent = useTemplateRef('mParent')
const route = useRoute()
const dashboardStore = useDashboardStore()
const { collection } = dashboardStore
const { getBatchDashboardData } = dashboardStore
const dashboardGoalsStore = useDashboardGoalsStore()
type SkeletonCard = {
    id: string
    col: string
    height: number
}
const updateKey = ref(0)
const randomInt = (min: number, max: number) => Math.floor(Math.random() * (max - min + 1)) + min

const skeletonCards = ref<SkeletonCard[]>([])
const openSupport = ref(false)
const prefsStore = useDashboardPrefsStore()

// Initialize cards with defaults from config
const defaultDashboardCards = structuredClone(getDefaultDashboardCards())
prefsStore.applyLayoutToCards(defaultDashboardCards)

const dashboardCards = ref<DashboardCard[]>(prefsStore.applyOrderToCards(defaultDashboardCards))

const buildSkeletonCards = () => {
    skeletonCards.value = dashboardCards.value.map((card) => {
        const height = prefsStore.heights[card.type] ?? randomInt(140, 320)
        return {
            id: `sk-${card.type}`,
            col: card.col,
            height,
        }
    })
}

buildSkeletonCards()

const syncDashboardCardsFromStore = () => {
    for (const card of dashboardCards.value) {
        const dataKey = CARD_DATA_KEY_BY_TYPE[card.type] || CARD_ADMIN_DATA_KEY_BY_TYPE[card.type]
        if (!dataKey) continue
        const payload = (collection as any)[dataKey]
        card.data = payload as any
    }
}

watch(
    () => dashboardCards.value.map((c) => c.type).join('|'),
    () => prefsStore.setOrder(dashboardCards.value.map((c) => c.type)),
    { flush: 'post' },
)

const heightObservers = new Map<string, () => void>()

const setupDashboardCardHeightObservers = () => {
    if (!cardLayouts.value || !Array.isArray(cardLayouts.value)) return

    for (const comp of cardLayouts.value as any[]) {
        const type = comp?.cardType
        const el = comp?.$el as HTMLElement | undefined
        if (typeof type !== 'string' || !el) continue
        if (heightObservers.has(type)) continue

        const { stop } = useResizeObserver(el, (entries) => {
            const entry = entries[0]
            const h = Math.round(entry?.contentRect?.height ?? 0)
            if (h <= 0) return
            prefsStore.setHeight(type, h)
        })
        heightObservers.set(type, stop)
    }
}

const sortable = useSortable(mParent, dashboardCards, {
    animation: 150,
    handle: '.handler',
    watchElement: true,
    disabled: true,
})

watch(
    () => initialLoader.value,
    (loading) => {
        sortable.option('disabled', loading)
    },
    { immediate: true },
)

const router = useRouter()
const { ping } = useDialog()
const sortParent = useTemplateRef('sortParent')

const offset = ref(0)
const prevScrollPosition = ref(0)
const prevScrollTime = ref<number>(typeof performance !== 'undefined' ? performance.now() : Date.now())
const accumulatedScrollUp = ref(0)

const handleScroll = () => {
    if (!sortParent.value) return

    // iOS (and some touch browsers) can emit "rubber-band" scroll where scrollTop
    // temporarily goes <= 0. In that case we always show the header.
    const rawScrollTop = sortParent.value.scrollTop
    const now = typeof performance !== 'undefined' ? performance.now() : Date.now()
    if (rawScrollTop <= 0) {
        offset.value = 0
        prevScrollPosition.value = 0
        prevScrollTime.value = now
        accumulatedScrollUp.value = 0
        return
    }

    // Normalize and add a tiny dead-zone to avoid jitter.
    const currentScrollTop = Math.max(0, rawScrollTop)
    const delta = currentScrollTop - prevScrollPosition.value
    const dt = Math.max(1, now - prevScrollTime.value)

    const JITTER_PX = 2
    const HIDE_AFTER_PX = 30
    const SHOW_UP_VELOCITY_PX_PER_MS = 0.55 // ~550px/s
    const SHOW_AFTER_UP_PX = 80

    if (Math.abs(delta) < JITTER_PX) return

    // Only hide once the user has scrolled down a bit.
    if (delta > 0) {
        accumulatedScrollUp.value = 0
        if (currentScrollTop > HIDE_AFTER_PX) offset.value = -95
    }

    // More native-like: on upward scroll, only show if user scrolls up fast
    // (flick) or has scrolled up a meaningful distance.
    if (delta < 0) {
        const upDistance = -delta
        const upVelocity = upDistance / dt
        accumulatedScrollUp.value += upDistance

        if (
            currentScrollTop <= HIDE_AFTER_PX ||
            upVelocity >= SHOW_UP_VELOCITY_PX_PER_MS ||
            accumulatedScrollUp.value >= SHOW_AFTER_UP_PX
        ) {
            offset.value = 0
            accumulatedScrollUp.value = 0
        }
    }

    prevScrollPosition.value = currentScrollTop
    prevScrollTime.value = now
}

const refreshData = async (dataType: string) => {
    console.log('refreshData', dataType)
    try {
        const keys = CARD_REFRESH_KEYS_BY_TYPE[dataType] || CARD_ADMIN_REFRESH_KEYS_BY_TYPE[dataType]
        console.log('keys to refresh', keys)
        if (!keys || keys.length === 0) return
        await getBatchDashboardData(keys as unknown as string[])
        syncDashboardCardsFromStore()
    } catch (e) {
        ping((e as any)?.response?.data?.message || (e as any)?.message || 'エラーが発生しました。');
    }
};

const resize = async(type: string) => {
    const layout = dashboardCards.value.find(item => item.type === type);
    // const previousSpan = layout?.col.split('-')[2];
    if (!layout) return;
    const currentSpan = layout.col.split('-')[2];
    const currentNumber = parseInt(currentSpan);
    const nextSpan = currentNumber === 4 ? 1 : currentNumber + 1;

    if(!cardLayouts.value) return;
    const comp = cardLayouts.value.find((comp: any) => comp && comp.cardType === type) as any;
    const el = comp?.$el as HTMLElement;
    if (!el) return

    // FIRST
    const first = el.getBoundingClientRect()

    // mutate class
    layout.col = `col-span-${nextSpan}`
    await nextTick()
    prefsStore.setColSpan(type, layout.col)

    // LAST
    const last = el.getBoundingClientRect()

    // INVERT
    const dx = first.left - last.left
    const dy = first.top - last.top
    const sx = first.width / last.width
    const sy = first.height / last.height

    // PLAY
    el.animate(
        [
        { transformOrigin: 'top left', transform: `translate(${dx}px, ${dy}px) scale(${sx}, ${sy})` },
        { transformOrigin: 'top left', transform: 'none' },
        ],
        { duration: 180, easing: 'cubic-bezier(.2,.8,.2,1)' }
    )
};
const toggle = async (el: HTMLElement | null, type: string) => {
    const node = el
    if (!node) return

    const first = node.getBoundingClientRect()
    const appType = route.params.type ? String(route.params.type) : ''
    // fullscreen.value = fullscreen.value === '' ? type : ''
    if(appType === type){
        await router.replace({ name: 'dashboard' })
    } else {
        await router.replace({ name: 'dashboard', params: { type: type } })
    }
    await nextTick()
    await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()))

    if (!node.isConnected) return

    const last = node.getBoundingClientRect()

    if (last.width === 0 || last.height === 0) return

    const dx = first.left - last.left
    const dy = first.top - last.top
    const sx = first.width / last.width
    const sy = first.height / last.height

    const clamp = (v: number, min: number, max: number) => Math.max(min, Math.min(max, v))
    const syClamped = clamp(sy, 0.9, 1.1)

    node.animate(
        [
            { transformOrigin: 'top left', transform: `translate(${dx}px, ${dy}px) scale(${sx}, ${syClamped})` },
            { transformOrigin: 'top left', transform: 'none' }
        ],
        { duration: 150, easing: 'cubic-bezier(.2,.8,.2,1)' }
    )
}

onMounted(async () => {
    if(auth.isAdmin) {
        dashboardCards.value.push(structuredClone(ADMIN_PERSONNEL_EVALUATION_CARD))
    }
    init()
})

const init = async() => {
    try {
        await dashboardGoalsStore.initDashboardData() // Uses cache if fresh
        syncDashboardCardsFromStore()
    } finally {
        initialLoader.value = false
    }

    await nextTick()
    setupDashboardCardHeightObservers()
}
const refreshAll = async () => {
    initialLoader.value = true
    try {
        updateKey.value += 1
        await dashboardGoalsStore.initDashboardData(true) // Force refresh
        syncDashboardCardsFromStore()
        dashboardStore.getAnnualLeaveData()
    } finally {
        initialLoader.value = false
    }
}
watch(
    () => initialLoader.value,
    async (loading) => {
        if (loading) return
        await nextTick()
        setupDashboardCardHeightObservers()
    },
    { immediate: true },
)

watch(
    () => dashboardStore.collection,
    () => syncDashboardCardsFromStore(),
    { deep: true },
)
defineExpose({
    refreshData
})

provide('getAssets', () => refreshData('pending_approval_tasks'))
provide('refresh', () => refreshData('overdue_goals'))
</script>