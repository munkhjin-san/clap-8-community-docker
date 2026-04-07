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
                    <svg id="a" data-name="logo" xmlns="http://www.w3.org/2000/svg" width="17.62061" height="24.71744" viewBox="0 0 17.62061 24.71744">
                        <path d="M2.14839,10.77084c-.45128-.33506-.90282-.78642-1.24432-1.26985-.34105-.48343-.5939-1.03215-.74115-1.59969C.01275,7.3334-.02548,6.75032.01521,6.19114c.02023-.28004.06465-.55335.1237-.81975.01578-.06656.02991-.1322.04769-.19921l.05792-.20267c.04273-.13293.08615-.26549.1377-.39342.19926-.51553.47134-.9886.79143-1.41457.6395-.85567,1.47276-1.51376,2.3618-1.98183.88976-.4738,1.83476-.76438,2.77326-.94295.47011-.08929.94077-.14648,1.40911-.18848C8.1862.01153,8.65291.00171,9.11548.00061c.11561-.00273.23267.00436.3491.00673.11665.00309.23308.004.35005.00927l.3516.02173.17603.01064.17675.01628c.4712.03473.94477.11511,1.41961.20894.47566.09383.94982.23512,1.4197.40824.46948.17503.93323.39469,1.37533.67028.88508.54572,1.67856,1.33437,2.20773,2.30524.06665.12065.12643.24585.18557.37096.05283.12847.11229.25404.15684.38651.10024.26013.17084.5329.23112.80839.05328.27813.09388.56026.10238.85012.00509.07165.00264.14538.00327.21812-.00014.07301.00023.14557-.00527.22049-.00436.07347-.00836.15075-.01432.22103l-.02168.19376c-.06315.51435-.17948,1.04906-.38751,1.57059-.20307.52135-.49794,1.0206-.84908,1.44503-.17471.21312-.35905.41206-.55331.59209-.19394.17984-.39347.34369-.59509.49671-.4051.30332-1.54672,1.04933-2.17066,1.43909-.61273.38276-1.34866.81205-1.82394,1.19535-.49207.39683-.98124.87391-1.26986,1.36607-.29223.49832-.43344,1.07136-.4933,1.68496-.06133.62861-.04009,1.53023-.04305,1.94118-.00283.39283-.13841.71484-.44347.78263-.31084.06907-.67792-.06891-.8135-.39036s-.25435-.79617-.31363-1.25669c-.05987-.46052-.08615-.92713-.06974-1.39711.01614-.46989.06674-.94441.17393-1.41975.05364-.23767.27069-.92306.51971-1.41575s.72215-1.06424.89877-1.25118c.35842-.36642.83606-.76527,1.22739-1.0354.38956-.27222,1.86154-1.17262,2.18586-1.40238.64855-.45916,1.20863-.96041,1.56591-1.4803.18107-.26122.31286-.5288.41492-.81975.10138-.29013.16821-.61045.20848-.95714l.01341-.12975.00664-.10374c.00373-.03319.00268-.06919.00291-.10474-.00059-.03564.00277-.07056-.00077-.1072-.00341-.1442-.02364-.29286-.04587-.44188-.05792-.29722-.14748-.59754-.28445-.88295-.2694-.57263-.69215-1.09579-1.24563-1.5194-.55322-.42388-1.23027-.74656-1.96628-.96014-.09024-.03091-.18594-.05001-.27927-.07383-.09392-.02282-.18735-.04719-.2839-.06392l-.28763-.05628-.29231-.04446c-.19389-.03491-.39456-.04455-.59213-.0661-.20003-.01209-.39997-.02555-.60177-.02809-.80966-.01564-1.60969.04573-2.3703.21776-.76066.16648-1.47867.44661-2.09698.83548-.308.19585-.59309.41542-.8443.65973-.24945.24549-.47161.51035-.65032.79411-.09179.14038-.17034.28668-.24013.43434-.07078.14738-.1297.29741-.17748.44779-.02555.07501-.04378.15057-.06355.2254l-.02441.1122-.022.11865c-.02641.15875-.04619.31577-.05069.46907-.02255.61536.14961,1.16608.48657,1.60396.33778.43788.88649.67407,1.48721.85254.87294.25935,1.21907,1.09764.88977,1.78339-.30443.63397-.64683.92582-1.38317.93032-.7356.00449-1.4316-.41156-1.88288-.74663Z"/>
                        <path d="M8.03418,24.63315c-.12738.00687-.25876.00127-.39033-.02476-.1322-.02445-.26499-.06977-.38965-.14182-.24285-.1492-.47648-.38021-.63173-.67576-.15507-.29693-.2254-.62755-.20476-.97655.02237-.34585.13834-.68162.31141-.96828.17357-.28733.40138-.53189.66078-.71978.25863-.18947.54626-.32555.83944-.40919.14652-.04181.29491-.07211.4417-.0863.14516-.01526.29691-.01806.42738-.01286.26822.01245.52535.05747.77648.136.25049.07966.49675.18999.73133.35766.23176.16732.46729.38957.62905.71641.0801.16165.13752.34949.15948.54421.01114.09716.01455.19631.008.29295l-.00509.0719-.00746.06234c-.00468.04187-.01209.08229-.01941.12289-.06224.32338-.19171.61206-.36178.86969-.17202.25531-.38442.487-.64214.65996-.51567.34838-1.22513.36892-1.60532-.02722,0,0-.47225.19413-.72738.20852ZM8.85157,23.8876c.06081-.18697.20041-.30773.32155-.39455s.23839-.18061.29913-.25531c.06092-.07262.14714-.15299.17464-.28157.02669-.12477-.01359-.24203-.11602-.33664s-.27805-.15262-.4122-.17686c-.10308-.01862-.21193-.01938-.30482-.01305s-.16243.01556-.23064.03496-.17274.05336-.23917.0935-.12856.09296-.16984.14514c-.04182.05195-.06596.10151-.07778.14614-.01441.04117-.01441.10227-.00323.14606.01064.0461.04101.09867.10829.16219.05914.0708.2282.1873.33262.25726.15637.10477.27507.24118.31748.47272Z"/>
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