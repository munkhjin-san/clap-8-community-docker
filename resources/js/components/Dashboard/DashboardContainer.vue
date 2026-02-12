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
                @remove="removeRemindMessage"
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
                v-show="card.data.length > 0"
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
        </template>

    </div>
</div>
</template>
<script lang="ts" setup>
import { useAuthUserStore } from '@/store/auth';
import { nextTick, onMounted, provide, Ref, ref, useTemplateRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useResizeObserver } from '@vueuse/core';
import { useApi } from '@/composables/api';
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

const auth = useAuthUserStore()
const initialLoader = ref(true)
const cardLayouts = useTemplateRef('cardLayouts')
const mParent = useTemplateRef('mParent')
const route = useRoute()

type SkeletonCard = {
    id: string
    col: string
    height: number
}

const randomInt = (min: number, max: number) => Math.floor(Math.random() * (max - min + 1)) + min

const skeletonCards = ref<SkeletonCard[]>([])

type DashboardMessageCard = {
    title: string
    type: string
    layout: 'message'
    col: string
    order?: number
    data: Message[]
}

type DashboardTaskCard = {
    title: string
    type: string
    layout: 'task'
    col: string
    order?: number
    data: Task[]
}

type DashboardSurveyCard = {
    title: string
    type: string
    layout: 'survey'
    col: string
    order?: number
    data: any[]
}

type DashboardOverdueGoalCard = {
    title: string
    type: 'remind_overdue'
    layout: 'monthly_goals'
    col: string
    order?: number
    data: UserWithGoals[]
}

type DashboardCard = DashboardMessageCard | DashboardTaskCard | DashboardSurveyCard | DashboardOverdueGoalCard

const DASHBOARD_CARDS_ORDER_STORAGE_KEY = 'dashboardCardsOrder:v1'
const DASHBOARD_CARDS_LAYOUT_STORAGE_KEY = 'dashboardCardsLayout:v1'
const DASHBOARD_CARDS_HEIGHTS_STORAGE_KEY = 'dashboardCardsHeights:v1'

const isValidColSpan = (value: unknown): value is string =>
    typeof value === 'string' && /^col-span-[1-4]$/.test(value)

const getStoredDashboardCardsOrder = (): string[] | null => {
    if (typeof localStorage === 'undefined') return null
    try {
        const raw = localStorage.getItem(DASHBOARD_CARDS_ORDER_STORAGE_KEY)
        if (!raw) return null
        const parsed = JSON.parse(raw)
        if (!Array.isArray(parsed)) return null
        const order = parsed.filter((v) => typeof v === 'string') as string[]
        return order.length > 0 ? order : null
    } catch {
        return null
    }
}

const getStoredDashboardCardsLayout = (): Record<string, string> | null => {
    if (typeof localStorage === 'undefined') return null
    try {
        const raw = localStorage.getItem(DASHBOARD_CARDS_LAYOUT_STORAGE_KEY)
        if (!raw) return null
        const parsed = JSON.parse(raw)
        if (!parsed || typeof parsed !== 'object') return null

        const layout: Record<string, string> = {}
        for (const [type, col] of Object.entries(parsed as Record<string, unknown>)) {
            if (typeof type === 'string' && isValidColSpan(col)) layout[type] = col
        }
        return Object.keys(layout).length > 0 ? layout : null
    } catch {
        return null
    }
}

const getStoredDashboardCardsHeights = (): Record<string, number> | null => {
    if (typeof localStorage === 'undefined') return null
    try {
        const raw = localStorage.getItem(DASHBOARD_CARDS_HEIGHTS_STORAGE_KEY)
        if (!raw) return null
        const parsed = JSON.parse(raw)
        if (!parsed || typeof parsed !== 'object') return null

        const heights: Record<string, number> = {}
        for (const [type, value] of Object.entries(parsed as Record<string, unknown>)) {
            const num = typeof value === 'number' ? value : Number(value)
            if (typeof type === 'string' && Number.isFinite(num) && num > 0) heights[type] = Math.round(num)
        }
        return Object.keys(heights).length > 0 ? heights : null
    } catch {
        return null
    }
}

const applyDashboardCardsOrder = (cards: DashboardCard[], order: string[] | null): DashboardCard[] => {
    if (!order || order.length === 0) return cards

    const byType = new Map<string, DashboardCard>()
    for (const card of cards) byType.set(card.type, card)

    const ordered: DashboardCard[] = []
    for (const type of order) {
        const card = byType.get(type)
        if (card) ordered.push(card)
    }

    for (const card of cards) {
        if (!order.includes(card.type)) ordered.push(card)
    }

    return ordered
}

const applyDashboardCardsLayout = (cards: DashboardCard[], layout: Record<string, string> | null) => {
    if (!layout) return
    for (const card of cards) {
        const col = layout[card.type]
        if (isValidColSpan(col)) card.col = col
    }
}

const defaultDashboardCards: DashboardCard[] = [
    {
        title: 'リマインドメッセージ',
        type: 'remind_reminded_messages',
        layout: 'message',
        col: 'col-span-2',
        order: undefined,
        data: [] as Message[],
    },
    {
        title: '未確認メッセージ',
        type: 'remind_unchecked_messages',
        layout: 'message',
        col: 'col-span-1',
        order: undefined,
        data: [] as Message[],
    },
    {
        title: '未署名メッセージ',
        type: 'remind_unsigned_messages',
        layout: 'message',
        col: 'col-span-1',
        order: undefined,
        data: [] as Message[],
    },
    {
        title: '未着手タスク',
        type: 'remind_unfinished_tasks',
        layout: 'task',
        col: 'col-span-1',
        order: undefined,
        data: [] as Task[],
    },
    {
        title: '未完了タスク',
        type: 'remind_untouched_tasks',
        layout: 'task',
        col: 'col-span-1',
        order: undefined,
        data: [] as Task[],
    },
    {
        title: '未承認資産',
        type: 'remind_not_approved_tasks',
        layout: 'task',
        col: 'col-span-1',
        order: undefined,
        data: [] as Task[],
    },
    {
        title: '未回答フォーム',
        type: 'remind_form',
        layout: 'survey',
        col: 'col-span-1',
        order: undefined,
        data: [] as CustomForm[],
    },
    {
        title: '期限切れ目標',
        type: 'remind_overdue',
        layout: 'monthly_goals',
        col: 'col-span-1',
        order: undefined,
        data: [] as UserWithGoals[],
    }
]

applyDashboardCardsLayout(defaultDashboardCards, getStoredDashboardCardsLayout())

const dashboardCards = ref<DashboardCard[]>(applyDashboardCardsOrder(defaultDashboardCards, getStoredDashboardCardsOrder()))

const dashboardCardHeights = ref<Record<string, number>>(getStoredDashboardCardsHeights() ?? {})

let saveHeightsTimer: number | null = null
const scheduleSaveDashboardCardsHeights = () => {
    if (typeof localStorage === 'undefined') return
    if (saveHeightsTimer !== null) window.clearTimeout(saveHeightsTimer)
    saveHeightsTimer = window.setTimeout(() => {
        try {
            localStorage.setItem(DASHBOARD_CARDS_HEIGHTS_STORAGE_KEY, JSON.stringify(dashboardCardHeights.value))
        } catch {
            // ignore localStorage quota/security errors
        }
        saveHeightsTimer = null
    }, 150)
}

const buildSkeletonCards = () => {
    skeletonCards.value = dashboardCards.value.map((card) => {
        const height = dashboardCardHeights.value[card.type] ?? randomInt(140, 320)
        return {
            id: `sk-${card.type}`,
            col: card.col,
            height,
        }
    })
}

buildSkeletonCards()

const saveDashboardCardsOrder = () => {
    if (typeof localStorage === 'undefined') return
    try {
        const order = dashboardCards.value.map((c) => c.type)
        localStorage.setItem(DASHBOARD_CARDS_ORDER_STORAGE_KEY, JSON.stringify(order))
    } catch {
        // ignore localStorage quota/security errors
    }
}

watch(
    () => dashboardCards.value.map((c) => c.type).join('|'),
    () => saveDashboardCardsOrder(),
    { flush: 'post' },
)

const saveDashboardCardsLayout = () => {
    if (typeof localStorage === 'undefined') return
    try {
        const layout: Record<string, string> = {}
        for (const card of dashboardCards.value) {
            if (isValidColSpan(card.col)) layout[card.type] = card.col
        }
        localStorage.setItem(DASHBOARD_CARDS_LAYOUT_STORAGE_KEY, JSON.stringify(layout))
    } catch {
        // ignore localStorage quota/security errors
    }
}

watch(
    () => dashboardCards.value.map((c) => `${c.type}:${c.col}`).join('|'),
    () => saveDashboardCardsLayout(),
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
            if (dashboardCardHeights.value[type] === h) return
            dashboardCardHeights.value = { ...dashboardCardHeights.value, [type]: h }
            scheduleSaveDashboardCardsHeights()
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


const api = useApi()
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


const getData = async () => {
    const data = await api.get('/remind_summary');
    for (const card of dashboardCards.value) {
        const payload = data?.[card.type]      
        card.data = (Array.isArray(payload) ? payload : [])         
    }
}



const refreshData = async (dataType: string) => {
    try {
        const response = await api.get(`/${dataType}`);
        const card = dashboardCards.value.find((c) => c.type === dataType)
        if (card) {
            const payload = response?.[dataType]
            if (card.layout === 'message') {
                card.data = (Array.isArray(payload) ? payload : []) as Message[]
            } else if(card.layout === 'task') {
                card.data = (Array.isArray(payload) ? payload : []) as Task[]
            } else if(card.layout === 'monthly_goals') {
                card.data = (Array.isArray(payload) ? payload : []) as UserWithGoals[]
            } else {
                card.data = (Array.isArray(payload) ? payload : []) as any[]
            }
        }
        if (dataType === 'remind_unfinished_tasks') {
            refreshData('remind_untouched_tasks')   
        }
    } catch (e) {
        ping(e.response?.data.message || e?.message || 'エラーが発生しました。');
    }
};


const removeRemindMessage = (id: number) => {
    const card = dashboardCards.value.find(c => c.type === 'remind_reminded_messages')
    if (!card) return
    card.data = (card.data as Message[]).filter((message: { id: number | string | null }) => Number(message.id) !== id)
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

    saveDashboardCardsLayout()

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
    try {
        await getData()
    } finally {
        initialLoader.value = false
    }

    await nextTick()
    setupDashboardCardHeightObservers()
})

watch(
    () => initialLoader.value,
    async (loading) => {
        if (loading) return
        await nextTick()
        setupDashboardCardHeightObservers()
    },
    { immediate: true },
)
defineExpose({
    refreshData
})

provide('getAssets', () => refreshData('remind_not_approved_tasks'))
provide('refresh', () => refreshData('remind_overdue'))
</script>