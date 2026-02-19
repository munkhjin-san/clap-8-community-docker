<template>
<div class="w-full h-full overflow-auto bg-[var(--bg3)] relative" :class="{'hidescroll' : route.params.type}" ref="sortParent" @scroll="handleScroll">
    <div class="mem-header-section mobile" :style="{'transform': `translateY(${offset}px)`}">        
        <div class="post-header sticky top-0 z-[11] bg-[var(--background-color)]" >
            <HamBurger />       
            <div class="text-[14px]">ダッシュボード</div>   
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
        <template v-for="card in dashboardCards" :key="card.type">
            <DashboardMessageLayout
                v-if="!initialLoader && card.layout === 'message'"
                v-show="card.data.length > 0"
                class="dashboard-card-item"
                :class="[card.col, 'min-w-0 w-full']"
                :fullscreen="route.params.type === card.type"
                :data="card"
                ref="cardLayouts"
                @refreshData="refreshData"
                @toggle="toggle"
                @resize="(type) => resize(type)"
            />
            <DashboardTaskLayout
                v-else-if="!initialLoader && card.layout === 'task'"
                v-show="card.data.length > 0"
                class="dashboard-card-item"
                :class="[card.col, 'min-w-0 w-full']"
                :fullscreen="route.params.type === card.type"
                @toggle="toggle"
                :data="card"
                ref="cardLayouts"
                @resize="(type) => resize(type)"
            />
            <DashboardSurvey
                v-else-if="!initialLoader && card.layout === 'survey'"
                class="dashboard-card-item"
                :class="[card.col, 'min-w-0 w-full']"
                :fullscreen="route.params.type === card.type"
                @toggle="toggle"
                :data="card"
                ref="cardLayouts"
                @resize="(type) => resize(type)"
            />
            <DashboardGoal
                v-else-if="!initialLoader && card.layout === 'monthly_goals'"
                class="dashboard-card-item"
                :class="[card.col, 'min-w-0 w-full']"
                :fullscreen="route.params.type === card.type"
                @toggle="toggle"
                :data="card"
                ref="cardLayouts"
                @resize="(type) => resize(type)"
            />
            <DashboardChallenge
                v-else-if="!initialLoader && card.layout === 'challenge'"
                v-show="card.data.length > 0"
                class="dashboard-card-item"
                :class="[card.col, 'min-w-0 w-full']"
                :fullscreen="route.params.type === card.type"
                @toggle="toggle"
                :data="card"
                ref="cardLayouts"
                @resize="(type) => resize(type)"
            />
            <DashboardAsset
                v-else-if="!initialLoader && card.layout === 'assets'"
                class="dashboard-card-item"
                :class="[card.col, 'min-w-0 w-full']"
                :fullscreen="route.params.type === card.type"
                @toggle="toggle"
                :data="card"
                ref="cardLayouts"
                @resize="(type) => resize(type)"
                @refreshData="refreshData"
            />
        </template>

    </div>
    <FloatButton :hide-on="sortParent" title="データを更新" @action="refreshAll" class="fixed" v-if="!route.params.type">
        <template #icon>
            <svg v-if="!initialLoader" xmlns="http://www.w3.org/2000/svg" width="406.7002" height="448.97456" viewBox="0 0 406.7002 448.97456">
                <path d="M269.42244,400.48149c89.40405-38.52608,127.74738-143.45953,84.52156-230.37382-4.00132-8.04547-.26147-17.82743,7.09537-22.04708,7.4958-4.29935,18.71269-3.19281,23.2254,5.40907,20.95447,39.94219,27.1756,85.82814,18.89384,129.76056-19.02756,100.93584-110.71041,171.77738-212.55189,165.33852C89.88917,442.20092,8.2668,362.26379.5443,261.0774c-2.28189-29.8992,2.63636-63.24923,14.27731-91.50091,25.44743-61.75894,78.66763-107.53931,144.41752-122.44033l-19.58257-16.43668c-7.42992-6.23632-8.21032-17.1677-2.31285-24.29177,6.18069-7.46619,16.86033-8.68422,24.91843-2.18939l51.8508,41.79173c6.84966,5.52083,8.93392,15.44934,4.04718,22.84488l-36.39742,55.08348c-5.60688,8.48539-17.40599,9.55259-24.3728,4.29712-8.40154-6.33776-9.11161-16.578-3.67234-25.07838l13.93379-21.77543c-31.98287,6.59331-59.7407,22.17515-82.69216,44.87814-41.19269,40.74673-58.67726,98.6188-45.74298,156.9487,11.22378,50.61602,47.48919,95.46628,97.6474,117.14014,41.87034,18.09258,90.2506,18.36429,132.55882.13279Z"/>
            </svg>
            <div v-else class="spinner-nano"></div>
        </template>
    </FloatButton>
</div>
</template>
<script lang="ts" setup>
import { useAuthUserStore } from '@/store/auth';
import { nextTick, onMounted, provide, Ref, ref, useTemplateRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useResizeObserver } from '@vueuse/core';
import { useDialog } from '@/composables/dialog';
import { Message, Task, UserWithGoals } from '@/interface/globalInterface';
import { useSortable } from '@vueuse/integrations/useSortable.mjs';
import DashboardMessageLayout from './Layout/DashboardMessageLayout.vue';
import DashboardTaskLayout from './Layout/DashboardTaskLayout.vue';
import './dashboard.css'
import { CustomForm } from '@/interface/customFormInterface';
import DashboardSurvey from './Layout/DashboardSurvey.vue';
import DashboardGoal from './Layout/DashboardGoal.vue';
import HamBurger from '../Global/HamBurger.vue';
import { useDashboardStore } from '@/store/dashboard';
import { useDashboardPrefsStore } from '@/store/dashboardPrefs';
import { Post } from '@/interface/postInterface';
import DashboardChallenge from './Layout/DashboardChallenge.vue';
import FloatButton from '../Global/FloatButton.vue';
import DashboardAsset from './Layout/DashboardAsset.vue';
import { Asset } from '@/interface/assetInterface';

const auth = useAuthUserStore()
const initialLoader = ref(true)
const cardLayouts = useTemplateRef('cardLayouts')
const mParent = useTemplateRef('mParent')
const route = useRoute()
const dashboardStore = useDashboardStore()
const { collection } = dashboardStore
const { getBatchDashboardData } = dashboardStore
type SkeletonCard = {
    id: string
    col: string
    height: number
}

const randomInt = (min: number, max: number) => Math.floor(Math.random() * (max - min + 1)) + min

const skeletonCards = ref<SkeletonCard[]>([])

type CardBase = {
    title: string
    type: string
    col: string
    order?: number
    canResize?: boolean
    canFullscreen?: boolean
}

type DashboardMessageCard = CardBase & {
    layout: 'message'
    data: Message[]
}

type DashboardTaskCard = CardBase & {
    layout: 'task'
    data: Task[]
}

type DashboardSurveyCard = CardBase & {
    layout: 'survey'
    data: any[]
}

type DashboardOverdueGoalCard = CardBase & {
    layout: 'monthly_goals'
    data: UserWithGoals[]
}

type DashboardChallengeCard = CardBase & {
    layout: 'challenge'
    data: Post[]
}

type DashboardAssetCard = CardBase & {
    layout: 'assets'
    data: {
        in_use: Asset[]
        waiting_approval?: Asset[]
    }
}

type DashboardCard = DashboardMessageCard | DashboardTaskCard | DashboardSurveyCard | DashboardOverdueGoalCard | DashboardChallengeCard | DashboardAssetCard

const prefsStore = useDashboardPrefsStore()

const defaultDashboardCards: DashboardCard[] = [
    {
        title: 'リマインドメッセージ',
        type: 'remindedMessages',
        layout: 'message',
        col: 'col-span-2',
        order: undefined,
        data: [] as Message[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: '確認依頼',
        type: 'mustCheckMessages',
        layout: 'message',
        col: 'col-span-1',
        order: undefined,
        data: [] as Message[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: 'サイン依頼',
        type: 'mustSignMessages',
        layout: 'message',
        col: 'col-span-1',
        order: undefined,
        data: [] as Message[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: '未対応タスク',
        type: 'unfinishedTasks',
        layout: 'task',
        col: 'col-span-1',
        order: undefined,
        data: [] as Task[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: '未完了タスク',
        type: 'untouchedTasks',
        layout: 'task',
        col: 'col-span-1',
        order: undefined,
        data: [] as Task[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: '承認待ちタスク',
        type: 'pendingApprovalTasks',
        layout: 'task',
        col: 'col-span-1',
        order: undefined,
        data: [] as Task[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: 'フォーム',
        type: 'forms',
        layout: 'survey',
        col: 'col-span-1',
        order: undefined,
        data: [] as CustomForm[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: '期限切れ目標',
        type: 'overdueGoals',
        layout: 'monthly_goals',
        col: 'col-span-1',
        order: undefined,
        data: [] as UserWithGoals[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: 'チャレンジ',
        type: 'challenges',
        layout: 'challenge',
        col: 'col-span-1',
        order: undefined,
        data: [] as Post[],
        canFullscreen: false,
        canResize: true,
    },
    {
        title: '物品',
        type: 'assets',
        layout: 'assets',
        col: 'col-span-1',
        order: undefined,
        data: {
            in_use: [] as Asset[]
        },
        canFullscreen: true,
        canResize: true,
    }
]

type DashboardStoreCollection = typeof collection
type DashboardStoreKey = keyof DashboardStoreCollection

const CARD_DATA_KEY_BY_TYPE: Record<string, DashboardStoreKey> = {
    remindedMessages: 'remindedMessages',
    mustCheckMessages: 'mustCheckMessages',
    mustSignMessages: 'mustSignMessages',
    unfinishedTasks: 'unfinishedTasks',
    untouchedTasks: 'untouchedTasks',
    pendingApprovalTasks: 'pendingApprovalTasks',
    forms: 'forms',
    overdueGoals: 'overdueGoals',
    challenges: 'challenges',
    assets: 'assets',
}

const CARD_REFRESH_KEYS_BY_TYPE: Record<string, DashboardStoreKey[]> = {
    remindedMessages: ['remindedMessages'],
    mustCheckMessages: ['mustCheckMessages'],
    mustSignMessages: ['mustSignMessages'],
    unfinishedTasks: ['unfinishedTasks', 'untouchedTasks'],
    untouchedTasks: ['untouchedTasks'],
    pendingApprovalTasks: ['pendingApprovalTasks'],
    forms: ['forms'],
    overdueGoals: ['overdueGoals'],
    challenges: ['challenges'],
    assets: ['assets'],
}

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
        const dataKey = CARD_DATA_KEY_BY_TYPE[card.type]
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
        const keys = CARD_REFRESH_KEYS_BY_TYPE[dataType]
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
    const el = cardLayouts.value.find((comp) => comp && comp.cardType === type)?.$el as HTMLElement;
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
    init()
})

const init = async() => {
    try {
        await getBatchDashboardData([
            'remindedMessages',
            'mustCheckMessages',
            'mustSignMessages',
            'unfinishedTasks',
            'untouchedTasks',
            'pendingApprovalTasks',
            'forms',
            'overdueGraveCount',
            'challenges',
            'assets'
        ])
        syncDashboardCardsFromStore()
    } finally {
        initialLoader.value = false
    }

    await nextTick()
    setupDashboardCardHeightObservers()
}
const refreshAll = async () => {
    initialLoader.value = true
    await init()
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