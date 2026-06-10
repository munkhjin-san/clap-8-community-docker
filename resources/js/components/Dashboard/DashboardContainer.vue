<template>
    <div id="dashBoardContainer" class="w-full h-full overflow-hidden relative">
        <div class="w-full h-full overflow-y-scroll bg-[var(--bg3)] relative" :class="{'hidescroll' : route.params.type}" ref="sortParent">
            <div class="mem-header-section mobile" :style="{'transform': `translateY(${offset}px)`}">        
                <div class="post-header sticky top-0 z-[11] bg-[var(--background-color)]" >
                    <HamBurger />       
                    <div class="text-[14px] text-[var(--primary-color)]">ダッシュボード</div>   
                    <div class="flex items-center ml-auto mr-1">
                        <button class="relative w-8 h-8 flex items-center justify-center rounded-full hover:bg-[var(--bg3)]" @click="router.push({name: 'dashboard-support'})">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px;height: 18px;" fill="var(--primary-color)" viewBox="0 0 240.28505 248.17445">
                                <path d="M218.18097,97.18395c-1.82721-17.12347-5.58807-33.48346-14.245-48.15063C185.20264,15.22589,149.64588-3.19739,111.34595.4577,60.98829,5.26348,23.0387,46.61529,22.31,97.19506c-24.08392,13.19543-28.46429,46.21063-14.13135,68.35309,9.30396,14.37335,25.2088,20.65472,41.95911,19.43524,4.65363-.33881,8.97003-2.26715,8.95428-7.8125l-.224-78.94958c-.00952-3.37378-2.97369-7.11548-6.25531-7.0152l-17.1665.52472C39.3078,45.46984,77.17139,11.18566,123.5055,12.52221c43.64526,1.25903,75.67639,35.87048,81.10394,79.16583l-16.70581-.72546c-3.73932-.16241-6.65942,3.71735-6.64777,7.51208l.24323,78.85565c.01166,3.76337,2.85779,7.4599,7.25238,7.36237l15.36676-.34113c-6.46051,23.96423-26.26788,36.82068-50.48389,37.24371-2.69196-8.0752-8.99866-14.11334-17.37292-14.1463l-19.9046-.07837c-10.82007-.04254-18.62231,9.58582-19.06335,19.58441-.44067,9.99048,6.37451,19.89001,17.17834,21.15643l21.737.06293c8.88385.02576,15.03302-6.60071,17.91583-14.51257,33.15387.93512,60.40399-22.65192,64.18945-55.01428,6.92743-4.17523,12.21924-9.31702,15.97937-16.29437,11.70764-21.72473,6.21655-52.06543-16.11249-65.16919ZM46.43689,103.651l.1792,68.23822c-9.38287,1.09918-19.68134-2.86859-25.88196-10.87268-8.85162-11.42603-10.40411-27.35675-4.11859-40.1441,5.7207-11.63806,16.89026-17.73834,29.82135-17.22144ZM133.57441,235.9497l-14.98926.02094c-4.72955.00659-8.09363-2.8537-8.65808-7.12598-.45056-3.41064,2.12286-8.86975,6.69977-8.99701l16.72394-.46484c4.93878-.13733,8.18817,3.49164,8.58527,7.78973.41284,4.46826-2.79443,8.76941-8.36163,8.77716ZM220.5246,160.06024c-6.43311,8.73248-16.59039,12.1283-26.38428,11.92389l-.11743-68.10461c9.93091-1.06567,19.29919,3.10608,25.72235,11.34149,9.96277,12.7738,10.7937,31.2453.77936,44.83923Z"/>
                            </svg>
                            <Badge style="left: auto;right:-3;top:-2" :count="collection.systemUpdates.length"/>
                        </button>
                        <button @click="customize" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-[var(--bg3)]">
                            <svg fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" style="height:18px;width:18px" viewBox="0 0 13.15764 13.10742">
                                <path d="M11.09078,13.10742c-.47168,0-.89258-.16992-1.21875-.49219l-4.75-4.70312-.0791.02148c-.37402.10059-.75586.15234-1.13477.15234-.79102,0-1.90137-.17285-2.70215-.99316C-.04399,5.81152-.13286,4.29297.10445,3.63672c.13281-.36523.33887-.44434.48926-.44629.01562-.00098.24609-.02734.43945-.02734.12305,0,.16699.01172.17773.01465.36621.11816,1.25488.87207,1.56641,1.15332l.06348.05762.08105-.03027c.71094-.2666,1.24414-.80078,1.50098-1.50586l.02344-.06348-.80371-1.34277c-.1582-.16309-.32812-.52148-.28516-.77344.05469-.31348.42188-.54785.65234-.6084.28125-.04395.53027-.06445.77441-.06445,1.17773,0,1.85742.56934,2.31738,1.06543.93555,1.00879,1.27148,2.47168.89746,3.91406l-.02051.07812,4.625,4.63672c.62402.62305.81934,1.79785.06641,2.68555-.37988.44922-.98535.72754-1.5791.72754ZM10.52632,11.78223c.15918.15723.375.24316.61035.24316.25488,0,.50195-.09961.67773-.27344.17285-.17188.28418-.45215.28516-.71387,0-.21289-.07129-.39551-.20508-.53027l-5.14062-5.15234.0791-.20312c.45996-1.1875.6123-1.85938.02637-2.88965-.31641-.55762-.84766-1.49219-2.54395-1.49219h-.34375l1.0625,1.05469c.22168.21875.58887.58105.48828,1.01172-.33203,1.40625-1.39746,2.40527-2.85156,2.67285l-.17188.03223-1.69531-1.65332-.03125.28906c-.09277.86133.5293,1.95996,1.15918,2.48535.5957.49609,1.2793.53809,1.98633.53906.33594,0,.65723-.14746.96875-.29004.11133-.05078.22266-.10254.33496-.14648l.20508-.08008,5.09961,5.09668Z"/>
                            </svg>
                        </button>
                        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-[var(--bg3)]" @click="refreshAll" v-if="!route.params.type">
                            <svg fill="var(--primary-color)" v-if="!initialLoader && !refreshingDashboard" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 406.7002 448.97456">
                                <path d="M269.42244,400.48149c89.40405-38.52608,127.74738-143.45953,84.52156-230.37382-4.00132-8.04547-.26147-17.82743,7.09537-22.04708,7.4958-4.29935,18.71269-3.19281,23.2254,5.40907,20.95447,39.94219,27.1756,85.82814,18.89384,129.76056-19.02756,100.93584-110.71041,171.77738-212.55189,165.33852C89.88917,442.20092,8.2668,362.26379.5443,261.0774c-2.28189-29.8992,2.63636-63.24923,14.27731-91.50091,25.44743-61.75894,78.66763-107.53931,144.41752-122.44033l-19.58257-16.43668c-7.42992-6.23632-8.21032-17.1677-2.31285-24.29177,6.18069-7.46619,16.86033-8.68422,24.91843-2.18939l51.8508,41.79173c6.84966,5.52083,8.93392,15.44934,4.04718,22.84488l-36.39742,55.08348c-5.60688,8.48539-17.40599,9.55259-24.3728,4.29712-8.40154-6.33776-9.11161-16.578-3.67234-25.07838l13.93379-21.77543c-31.98287,6.59331-59.7407,22.17515-82.69216,44.87814-41.19269,40.74673-58.67726,98.6188-45.74298,156.9487,11.22378,50.61602,47.48919,95.46628,97.6474,117.14014,41.87034,18.09258,90.2506,18.36429,132.55882.13279Z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="initialLoader" class="dashboard-parent dashboard-grid-stack dashboard-skeleton-stack" :style="getSkeletonContainerStyle()">
                <div
                    v-for="s in skeletonCards"
                    :key="s.id"
                    class="dashboard-skeleton-item"
                    :style="getSkeletonItemStyle(s)"
                >
                    <div class="dashboard-card-item dashboard-skeleton-card min-w-0 w-full rounded overflow-hidden bg-[var(--bg2)] animate-pulse">
                        <div class="p-4 h-full flex flex-col gap-3">
                            <div class="h-4 w-2/3 rounded bg-[var(--bg1)] opacity-50" />
                            <div class="h-3 w-full rounded bg-[var(--bg1)] opacity-35" />
                            <div class="h-3 w-5/6 rounded bg-[var(--bg1)] opacity-35" />
                            <div class="mt-auto h-8 w-24 rounded bg-[var(--bg1)] opacity-25" />
                        </div>
                    </div>
                </div>
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
            <div
                v-else
                ref="mParent"
                class="dashboard-parent dashboard-grid-stack grid-stack"
                :class="{ 'is-customizing': customizing }"
                @click.capture="handleGridClickCapture"
            >
                <div
                    v-for="card in visibleDashboardCards"
                    :key="card.type"
                    class="grid-stack-item"
                    :data-dashboard-card="card.type"
                    :gs-id="card.type"
                    v-bind="getGridStackAttributes(card)"
                >
                    <div class="grid-stack-item-content">
                        <component
                            :key="`${card.type}-${updateKey}`"
                            :is="DASHBOARD_COMPONENTS[card.layout]"
                            class="dashboard-card-item min-w-0 w-full"
                            :fullscreen="route.params.type === card.type"
                            :data="card"
                            :id="`card-${card.type}`"
                            ref="cardLayouts"
                            @toggle="toggle"
                            @resize="(type:string) => resize(type)"
                            @refreshData="refreshData"
                        />
                        <div class="dashboard-customize-layer">
                            <div
                                class="dashboard-customize-control dashboard-customize-drag handler"
                                title="並び替え"
                            >
                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M10 3v14" />
                                    <path d="M7.5 5.5 10 3l2.5 2.5" />
                                    <path d="M7.5 14.5 10 17l2.5-2.5" />
                                    <path d="M3 10h14" />
                                    <path d="M5.5 7.5 3 10l2.5 2.5" />
                                    <path d="M14.5 7.5 17 10l-2.5 2.5" />
                                </svg>
                            </div>
                            <div
                                v-if="card.canResize !== false"
                                class="dashboard-customize-size-options pc"
                                title="サイズ変更"
                            >
                                <!-- <button
                                    v-for="size in GRID_COLUMNS"
                                    :key="`${card.type}-size-${size}`"
                                    type="button"
                                    class="dashboard-customize-size-button"
                                    :class="{ 'is-active': getCardSpan(card) === size }"
                                    :title="`${size}列`"
                                    @click.stop.prevent="setCardWidth(card.type, size)"
                                >
                                    <span class="dashboard-customize-size-preview" :style="{ '--dashboard-size': size }"></span>
                                </button> -->
                                <div class="dashboard-customize-blocks text-[12px]">
                                    <div 
                                        v-for="block in 4"
                                        :key="`${card.type}-block-${block}`"
                                        @click.stop.prevent="setCardWidth(card.type, block)" 
                                        :class="{'bg-[var(--primary-color)] text-[var(--background-color)]': getCardSpan(card) >= block}" 
                                        class="dashboard-customize-block">
                                        <span>{{ block }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>      
            <FloatButton :hide-on="sortParent" title="サポートデスク" :order="3" class="fixed pc" @action="router.push({name: 'dashboard-support'})">
                <template #icon>
                    <div class="relative">
                        <Badge style="right: -15px;top: -8px;left: auto;" v-if="collection.systemUpdates.length" :count="collection.systemUpdates.length"/>
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px;height: 22px;" fill="black" viewBox="0 0 240.28505 248.17445">
                            <path d="M218.18097,97.18395c-1.82721-17.12347-5.58807-33.48346-14.245-48.15063C185.20264,15.22589,149.64588-3.19739,111.34595.4577,60.98829,5.26348,23.0387,46.61529,22.31,97.19506c-24.08392,13.19543-28.46429,46.21063-14.13135,68.35309,9.30396,14.37335,25.2088,20.65472,41.95911,19.43524,4.65363-.33881,8.97003-2.26715,8.95428-7.8125l-.224-78.94958c-.00952-3.37378-2.97369-7.11548-6.25531-7.0152l-17.1665.52472C39.3078,45.46984,77.17139,11.18566,123.5055,12.52221c43.64526,1.25903,75.67639,35.87048,81.10394,79.16583l-16.70581-.72546c-3.73932-.16241-6.65942,3.71735-6.64777,7.51208l.24323,78.85565c.01166,3.76337,2.85779,7.4599,7.25238,7.36237l15.36676-.34113c-6.46051,23.96423-26.26788,36.82068-50.48389,37.24371-2.69196-8.0752-8.99866-14.11334-17.37292-14.1463l-19.9046-.07837c-10.82007-.04254-18.62231,9.58582-19.06335,19.58441-.44067,9.99048,6.37451,19.89001,17.17834,21.15643l21.737.06293c8.88385.02576,15.03302-6.60071,17.91583-14.51257,33.15387.93512,60.40399-22.65192,64.18945-55.01428,6.92743-4.17523,12.21924-9.31702,15.97937-16.29437,11.70764-21.72473,6.21655-52.06543-16.11249-65.16919ZM46.43689,103.651l.1792,68.23822c-9.38287,1.09918-19.68134-2.86859-25.88196-10.87268-8.85162-11.42603-10.40411-27.35675-4.11859-40.1441,5.7207-11.63806,16.89026-17.73834,29.82135-17.22144ZM133.57441,235.9497l-14.98926.02094c-4.72955.00659-8.09363-2.8537-8.65808-7.12598-.45056-3.41064,2.12286-8.86975,6.69977-8.99701l16.72394-.46484c4.93878-.13733,8.18817,3.49164,8.58527,7.78973.41284,4.46826-2.79443,8.76941-8.36163,8.77716ZM220.5246,160.06024c-6.43311,8.73248-16.59039,12.1283-26.38428,11.92389l-.11743-68.10461c9.93091-1.06567,19.29919,3.10608,25.72235,11.34149,9.96277,12.7738,10.7937,31.2453.77936,44.83923Z"/>
                        </svg>
                    </div>
                </template>
            </FloatButton>      
            <FloatButton :hide-on="sortParent" :order="2" @action="customize" class="fixed pc z-[13]" v-if="!route.params.type">
                <template #icon>
                    <svg xmlns="http://www.w3.org/2000/svg" style="height:18px;width:18px" viewBox="0 0 13.15764 13.10742">
                        <path d="M11.09078,13.10742c-.47168,0-.89258-.16992-1.21875-.49219l-4.75-4.70312-.0791.02148c-.37402.10059-.75586.15234-1.13477.15234-.79102,0-1.90137-.17285-2.70215-.99316C-.04399,5.81152-.13286,4.29297.10445,3.63672c.13281-.36523.33887-.44434.48926-.44629.01562-.00098.24609-.02734.43945-.02734.12305,0,.16699.01172.17773.01465.36621.11816,1.25488.87207,1.56641,1.15332l.06348.05762.08105-.03027c.71094-.2666,1.24414-.80078,1.50098-1.50586l.02344-.06348-.80371-1.34277c-.1582-.16309-.32812-.52148-.28516-.77344.05469-.31348.42188-.54785.65234-.6084.28125-.04395.53027-.06445.77441-.06445,1.17773,0,1.85742.56934,2.31738,1.06543.93555,1.00879,1.27148,2.47168.89746,3.91406l-.02051.07812,4.625,4.63672c.62402.62305.81934,1.79785.06641,2.68555-.37988.44922-.98535.72754-1.5791.72754ZM10.52632,11.78223c.15918.15723.375.24316.61035.24316.25488,0,.50195-.09961.67773-.27344.17285-.17188.28418-.45215.28516-.71387,0-.21289-.07129-.39551-.20508-.53027l-5.14062-5.15234.0791-.20312c.45996-1.1875.6123-1.85938.02637-2.88965-.31641-.55762-.84766-1.49219-2.54395-1.49219h-.34375l1.0625,1.05469c.22168.21875.58887.58105.48828,1.01172-.33203,1.40625-1.39746,2.40527-2.85156,2.67285l-.17188.03223-1.69531-1.65332-.03125.28906c-.09277.86133.5293,1.95996,1.15918,2.48535.5957.49609,1.2793.53809,1.98633.53906.33594,0,.65723-.14746.96875-.29004.11133-.05078.22266-.10254.33496-.14648l.20508-.08008,5.09961,5.09668Z"/>
                    </svg>
                </template>
            </FloatButton>
            <FloatButton :hide-on="sortParent" title="データを更新" @action="refreshAll" class="fixed pc" v-if="!route.params.type">
                <template #icon>
                    <svg v-if="!initialLoader && !refreshingDashboard" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 406.7002 448.97456">
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
        <Transition name="downShiftPop">
            <div class="absolute bottom-4 w-fit left-0 right-0 mx-auto z-[14] flex items-center gap-2" v-if="canCustomizeGrid()">
                <button type="button" class="pc rounded-full bg-[var(--background-color)] !text-[var(--primary-color)] px-4 py-2 text-[12px] shadow-lg" @click="autoArrangeDashboard">
                    <div>自動整列</div>
                </button>
                <button type="button" class="rounded-full bg-[var(--primary-color)] !text-[var(--background-color)] px-4 py-2 text-[12px] shadow-lg" @click="customize">
                    <div>カスタマイズ完了</div>
                </button>
            </div>
        </Transition>
    </div>
</template>
<script lang="ts" setup>
import { useAuthUserStore } from '@/store/auth';
import { computed, nextTick, onBeforeUnmount, onMounted, provide, ref, useTemplateRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useResizeObserver } from '@vueuse/core';
import { useDialog } from '@/composables/dialog';
import { GridStack, type GridItemHTMLElement, type GridStackWidget } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';
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
import Badge from '../Global/Badge.vue';

const auth = useAuthUserStore()
const initialLoader = ref(true)
const refreshingDashboard = ref(false)
const cardLayouts = useTemplateRef('cardLayouts')
const mParent = useTemplateRef('mParent')
const route = useRoute()
const dashboardStore = useDashboardStore()
const { collection } = dashboardStore
const { getBatchDashboardData } = dashboardStore
const dashboardGoalsStore = useDashboardGoalsStore()
type SkeletonCard = {
    id: string
    x: number
    y: number
    w: number
    h: number
}
const updateKey = ref(0)

const skeletonCards = ref<SkeletonCard[]>([])
const openSupport = ref(false)
const prefsStore = useDashboardPrefsStore()
prefsStore.setActiveUser(auth.activeUser?.id)

// Initialize cards with defaults from config
const defaultDashboardCards = structuredClone(getDefaultDashboardCards())
if (auth.isAdmin) {
    defaultDashboardCards.push(structuredClone(ADMIN_PERSONNEL_EVALUATION_CARD))
}
prefsStore.applyLayoutToCards(defaultDashboardCards)

const dashboardCards = ref<DashboardCard[]>(prefsStore.applyOrderToCards(defaultDashboardCards))
const canSeeIncidentCard = computed(() => {
    return auth.isPM
        || auth.isBoss
        || auth.isAdmin
        || collection.incidents.attention.length > 0
        || (collection.incidents.emergency_contacts && collection.incidents.emergency_contacts.length > 0)
})
const permissionAllowedDashboardCards = computed(() => dashboardCards.value.filter((card) => {
    if (card.type === 'incidents' && !canSeeIncidentCard.value) {
        return route.params.type === card.type
    }

    return true
}))
const visibleDashboardCards = computed(() => permissionAllowedDashboardCards.value.filter((card) => shouldShowCard(card)))
const grid = ref<GridStack | null>(null)
const customizing = ref(false)
const GRID_COLUMNS = 4
// GridStack snaps height to whole rows. A small row keeps auto-sized cards close to their real content height.
const GRID_CELL_HEIGHT = 4
const GRID_MARGIN = 10
const DEFAULT_CARD_HEIGHT_BY_TYPE: Record<string, number> = {
    remindedMessages: 260,
    mustCheckMessages: 180,
    mustSignMessages: 180,
    unfinishedTasks: 180,
    untouchedTasks: 180,
    pendingApprovalTasks: 180,
    forms: 180,
    overdueGoals: 420,
    projects: 220,
    challenges: 220,
    assets: 180,
    incidents: 220,
    // 'partner-crm': 220,
    schedules: 180,
    timesheet: 220,
    notice: 160,
    personnelEvaluation: 220,
}
let viewportResizeTimer: ReturnType<typeof setTimeout> | undefined
let suppressGridClickUntil = 0
let isRestoringGrid = false
let isGridUserInteracting = false
let gridInteractionReleaseFrame: number | undefined
let gridStateSaveFrame: number | undefined
let gridHeightSyncFrame: number | undefined
const pendingHeightSyncTypes = new Set<string>()

const getCardSpan = (card: DashboardCard) => {
    const span = Number.parseInt(card.col.split('-')[2] || '1', 10)
    return clampGridWidth(span)
}

const clampGridWidth = (value: unknown) => {
    const width = Number(value)
    return Number.isFinite(width) ? Math.max(1, Math.min(GRID_COLUMNS, Math.round(width))) : 1
}

const getSavedCardWidth = (card: DashboardCard) => {
    const storedCol = prefsStore.layout?.[card.type]
    if (typeof storedCol === 'string' && /^col-span-[1-4]$/.test(storedCol)) {
        return clampGridWidth(Number.parseInt(storedCol.split('-')[2] || '1', 10))
    }

    return clampGridWidth(prefsStore.getGridLayout(card.type)?.w ?? getCardSpan(card))
}

const getDefaultCardHeight = (type: string) => DEFAULT_CARD_HEIGHT_BY_TYPE[type] ?? 180

const getGridRowsFromContentHeight = (height: unknown, fallbackHeight = 180) => {
    const contentHeight = Number(height)
    if (!Number.isFinite(contentHeight) || contentHeight <= 0) {
        return Math.max(1, Math.ceil((fallbackHeight + GRID_MARGIN * 2) / GRID_CELL_HEIGHT))
    }
    return Math.max(1, Math.ceil((contentHeight + GRID_MARGIN * 2) / GRID_CELL_HEIGHT))
}

const getSavedOrDefaultGridRows = (card: DashboardCard) => {
    return getGridRowsFromContentHeight(prefsStore.heights[card.type], getDefaultCardHeight(card.type))
}

const buildPackedGridLayouts = (cards: DashboardCard[]) => {
    const layouts: Record<string, GridStackWidget> = {}
    let x = 0
    let y = 0
    let rowHeight = 1

    for (const card of cards) {
        const stored = prefsStore.getGridLayout(card.type)
        const w = getSavedCardWidth(card)
        const h = Math.max(1, stored?.h ?? getSavedOrDefaultGridRows(card))

        if (x + w > GRID_COLUMNS) {
            x = 0
            y += rowHeight
            rowHeight = 1
        }

        layouts[card.type] = { x, y, w, h }
        x += w
        rowHeight = Math.max(rowHeight, h)

        if (x >= GRID_COLUMNS) {
            x = 0
            y += rowHeight
            rowHeight = 1
        }
    }

    return layouts
}

const getInitialGridLayout = (card: DashboardCard, cards = dashboardCards.value): GridStackWidget => {
    const stored = prefsStore.getGridLayout(card.type)
    const w = getSavedCardWidth(card)
    const h = Math.max(1, stored?.h ?? getSavedOrDefaultGridRows(card))

    if (stored?.x !== undefined && stored?.y !== undefined) {
        return { x: stored.x, y: stored.y, w, h }
    }

    return buildPackedGridLayouts(cards)[card.type] ?? { w, h }
}

const getCardWidget = (card: DashboardCard): GridStackWidget => {
    const layout = getInitialGridLayout(card, visibleDashboardCards.value)

    return {
        id: card.type,
        x: layout.x,
        y: layout.y,
        w: clampGridWidth(layout.w ?? getCardSpan(card)),
        h: Math.max(1, layout.h ?? 2),
        minW: 1,
        maxW: GRID_COLUMNS,
        minH: 1,
        autoPosition: layout.x === undefined || layout.y === undefined,
    }
}

const getGridStackAttributes = (card: DashboardCard) => {
    const layout = getInitialGridLayout(card, visibleDashboardCards.value)
    return {
        'gs-x': layout.x,
        'gs-y': layout.y,
        'gs-w': clampGridWidth(layout.w ?? getCardSpan(card)),
        'gs-h': Math.max(1, layout.h ?? 2),
    }
}

const getGridItem = (type: string) => {
    return mParent.value?.querySelector(`[data-dashboard-card="${type}"]`) as GridItemHTMLElement | null
}

const writeGridStackAttributes = (item: GridItemHTMLElement | null) => {
    const node = item?.gridstackNode
    if (!item || !node) return

    item.setAttribute('gs-x', String(node.x ?? 0))
    item.setAttribute('gs-y', String(node.y ?? 0))
    item.setAttribute('gs-w', String(clampGridWidth(node.w)))
    item.setAttribute('gs-h', String(Math.max(1, node.h ?? 1)))
}

const getExpectedGridColumns = () => {
    const width = typeof window !== 'undefined' ? window.innerWidth : 960
    return width <= 959 ? 1 : GRID_COLUMNS
}

const getDashboardPanelHeight = (item: GridItemHTMLElement) => {
    const panel = item.querySelector('.panel:not(.fullscreen)') as HTMLElement | null
    const content = item.querySelector('.grid-stack-item-content') as HTMLElement | null
    const target = panel || content
    if (!target) return 0

    const rectHeight = Math.ceil(target.getBoundingClientRect().height)
    return Math.max(rectHeight, target.scrollHeight)
}

const syncGridItemHeight = (type: string) => {
    const item = getGridItem(type)
    if (!grid.value || !item?.gridstackNode) return
    if (isGridUserInteracting || hasActiveGridInteraction()) {
        pendingHeightSyncTypes.add(type)
        return
    }

    const contentHeight = getDashboardPanelHeight(item)
    if (contentHeight <= 0) return

    const rows = Math.max(1, Math.ceil((contentHeight + GRID_MARGIN * 2) / GRID_CELL_HEIGHT))
    prefsStore.setHeight(type, contentHeight)

    if (item.gridstackNode.h === rows) {
        prefsStore.setGridLayoutNow(type, {
            x: item.gridstackNode.x,
            y: item.gridstackNode.y,
            w: clampGridWidth(item.gridstackNode.w),
            h: rows,
        })
        return
    }
    grid.value.setAnimation(false)
    grid.value.update(item, { h: rows })
    writeGridStackAttributes(item)
    prefsStore.setGridLayoutNow(type, {
        x: item.gridstackNode.x,
        y: item.gridstackNode.y,
        w: clampGridWidth(item.gridstackNode.w),
        h: rows,
    })
    requestAnimationFrame(() => {
        grid.value?.setAnimation(true)
    })
}

const scheduleGridItemHeightSync = (type: string) => {
    pendingHeightSyncTypes.add(type)
    if (gridHeightSyncFrame !== undefined) return

    gridHeightSyncFrame = requestAnimationFrame(() => {
        gridHeightSyncFrame = undefined
        if (isGridUserInteracting || hasActiveGridInteraction()) return

        for (const pendingType of pendingHeightSyncTypes) {
            syncGridItemHeight(pendingType)
        }
        pendingHeightSyncTypes.clear()
    })
}

const syncVisibleGridHeights = async () => {
    await nextTick()
    await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()))
    for (const card of visibleDashboardCards.value) {
        syncGridItemHeight(card.type)
    }
    saveGridState(true)
}

const syncGridAfterViewportChange = () => {
    if (viewportResizeTimer) clearTimeout(viewportResizeTimer)
    viewportResizeTimer = setTimeout(async () => {
        if (initialLoader.value) buildSkeletonCards()
        if (grid.value) {
            const expectedColumns = getExpectedGridColumns()
            if (grid.value.getColumn() !== expectedColumns) {
                grid.value.column(expectedColumns, expectedColumns === 1 ? 'list' : 'moveScale')
            }
        }
        await syncVisibleGridHeights()
    }, 120)
}

const suppressNextGridClick = (duration = 350) => {
    suppressGridClickUntil = Math.max(suppressGridClickUntil, Date.now() + duration)
}

const handleGridClickCapture = (event: MouseEvent) => {
    if (Date.now() > suppressGridClickUntil) return
    event.preventDefault()
    event.stopPropagation()
    event.stopImmediatePropagation()
}

const hasActiveGridInteraction = () => {
    const activeGrid = grid.value as any
    if (!activeGrid) return false
    const hasActiveNode = activeGrid.engine?.nodes?.some((node: any) => node?._moving || node?._resizing || node?._event)
    const hasPlaceholder = Boolean(activeGrid._placeholder?.parentElement)
    return Boolean(hasActiveNode || hasPlaceholder)
}

const handleGlobalGridKeydown = (event: KeyboardEvent) => {
    if (event.key !== 'Escape') return
    const activeGrid = grid.value as any
    if (activeGrid?._placeholder?.parentElement) {
        activeGrid._placeholder.parentElement.removeChild(activeGrid._placeholder)
    }
}

const buildSkeletonCards = () => {
    const isSingleColumn = typeof window !== 'undefined' && window.innerWidth <= 959
    let mobileY = 0
    const skeletonSourceCards = permissionAllowedDashboardCards.value

    skeletonCards.value = [...skeletonSourceCards]
        .sort((a, b) => {
            const aLayout = getInitialGridLayout(a, skeletonSourceCards)
            const bLayout = getInitialGridLayout(b, skeletonSourceCards)
            return (aLayout.y ?? 0) - (bLayout.y ?? 0) || (aLayout.x ?? 0) - (bLayout.x ?? 0)
        })
        .map((card) => {
            const layout = getInitialGridLayout(card, skeletonSourceCards)
            const h = Math.max(1, layout.h ?? getSavedOrDefaultGridRows(card))
            const y = isSingleColumn ? mobileY : Number(layout.y ?? 0)
            if (isSingleColumn) mobileY += h

            return {
                id: `sk-${card.type}`,
                x: isSingleColumn ? 0 : Number(layout.x ?? 0),
                y,
                w: isSingleColumn ? 1 : clampGridWidth(layout.w ?? getCardSpan(card)),
                h,
            }
        })
}

const getSkeletonContainerStyle = () => {
    const rowCount = skeletonCards.value.reduce((max, card) => Math.max(max, card.y + card.h), 0)
    return {
        height: `${rowCount * GRID_CELL_HEIGHT + GRID_MARGIN * 2}px`,
    }
}

const getSkeletonItemStyle = (card: SkeletonCard) => {
    const columns = typeof window !== 'undefined' && window.innerWidth <= 959 ? 1 : GRID_COLUMNS
    return {
        left: `calc(${card.x} * (100% / ${columns}))`,
        top: `${card.y * GRID_CELL_HEIGHT}px`,
        width: `calc(${card.w} * (100% / ${columns}) - 20px)`,
        height: `${card.h * GRID_CELL_HEIGHT}px`,
    }
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

const stopDashboardCardHeightObservers = () => {
    for (const stop of heightObservers.values()) stop()
    heightObservers.clear()
}



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
            scheduleGridItemHeightSync(type)
        })
        heightObservers.set(type, stop)
    }
}

const router = useRouter()
const { ping, toast } = useDialog()
const sortParent = useTemplateRef('sortParent')

const offset = ref(0)

const saveGridState = (force = false) => {
    if (!grid.value) return
    if (isRestoringGrid && !force) return

    const layouts: Record<string, { x?: number; y?: number; w?: number; h?: number }> = {}
    const orderedTypes: string[] = []
    const nodes = [...grid.value.engine.nodes].sort((a, b) => (a.y ?? 0) - (b.y ?? 0) || (a.x ?? 0) - (b.x ?? 0))

    for (const node of nodes) {
        const type = String(node.id || node.el?.dataset.dashboardCard || '')
        if (!type) continue
        const width = clampGridWidth(node.w)
        orderedTypes.push(type)
        layouts[type] = {
            x: node.x,
            y: node.y,
            w: width,
            h: node.h,
        }

        const card = dashboardCards.value.find((item) => item.type === type)
        if (card && width) {
            card.col = `col-span-${width}`
            prefsStore.setColSpan(type, card.col)
        }
    }

    const hiddenTypes = dashboardCards.value
        .map((card) => card.type)
        .filter((type) => !orderedTypes.includes(type))

    prefsStore.setOrder([...orderedTypes, ...hiddenTypes])
    prefsStore.setGridLayoutsNow(layouts)
    buildSkeletonCards()
}

const scheduleGridStateSave = () => {
    if (isRestoringGrid) return
    if (gridStateSaveFrame !== undefined) cancelAnimationFrame(gridStateSaveFrame)
    gridStateSaveFrame = requestAnimationFrame(() => {
        gridStateSaveFrame = undefined
        saveGridState()
    })
}

const syncGridAfterUserInteraction = async () => {
    saveGridState()
    await nextTick()
    await syncVisibleGridHeights()
    scheduleGridStateSave()
}

const startGridUserInteraction = () => {
    if (gridInteractionReleaseFrame !== undefined) {
        cancelAnimationFrame(gridInteractionReleaseFrame)
        gridInteractionReleaseFrame = undefined
    }
    isGridUserInteracting = true
    suppressNextGridClick(600)
}

const finishGridUserInteraction = (_event?: Event, item?: GridItemHTMLElement) => {
    writeGridStackAttributes(item ?? null)
    suppressNextGridClick()
    if (gridInteractionReleaseFrame !== undefined) {
        cancelAnimationFrame(gridInteractionReleaseFrame)
    }
    gridInteractionReleaseFrame = requestAnimationFrame(() => {
        gridInteractionReleaseFrame = requestAnimationFrame(() => {
            gridInteractionReleaseFrame = undefined
            isGridUserInteracting = false
            syncGridAfterUserInteraction()
        })
    })
}

const initGridStack = async () => {
    if (initialLoader.value || !mParent.value) return
    isRestoringGrid = true

    try {
        if (!grid.value) {
            grid.value = GridStack.init(
                {
                    column: GRID_COLUMNS,
                    columnOpts: {
                        columnMax: GRID_COLUMNS,
                        breakpointForWindow: true,
                        breakpoints: [{ w: 959, c: 1, layout: 'list' }],
                    },
                    cellHeight: GRID_CELL_HEIGHT,
                    margin: GRID_MARGIN,
                    animate: true,
                    float: false,
                    disableDrag: true,
                    disableResize: false,
                    handle: '.handler',
                    resizable: { handles: '' },
                },
                mParent.value,
            )

            grid.value.on('dragstart resizestart', startGridUserInteraction)
            grid.value.on('dragstop resizestop', finishGridUserInteraction)
        }

        const expectedColumns = getExpectedGridColumns()
        if (grid.value.getColumn() !== expectedColumns) {
            grid.value.column(expectedColumns, expectedColumns === 1 ? 'list' : 'moveScale')
        }

        await nextTick()

        const visibleTypes = new Set(visibleDashboardCards.value.map((card) => card.type))
        for (const node of [...grid.value.engine.nodes]) {
            const type = String(node.id || node.el?.dataset.dashboardCard || '')
            if (type && !visibleTypes.has(type) && node.el) {
                grid.value.removeWidget(node.el, false, false)
            }
        }

        for (const card of visibleDashboardCards.value) {
            const item = getGridItem(card.type)
            if (!item || item.gridstackNode) continue
            grid.value.makeWidget(item, getCardWidget(card))
        }

        await syncVisibleGridHeights()
        applyGridInteractionMode()

    } finally {
        isRestoringGrid = false
    }
}

const canCustomizeGrid = () => customizing.value && !route.params.type

const applyGridInteractionMode = () => {
    grid.value?.enableMove(canCustomizeGrid(), false)
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

const setCardWidth = async(type: string, width: number) => {
    const layout = dashboardCards.value.find(item => item.type === type);
    if (!layout) return;
    const item = getGridItem(type)
    const nextSpan = clampGridWidth(width)

    layout.col = `col-span-${nextSpan}`
    prefsStore.setColSpan(type, layout.col)
    if (!grid.value || !item) return

    grid.value.update(item, { w: nextSpan })
    writeGridStackAttributes(item)
    await nextTick()
    scheduleGridStateSave()
};

const resize = async(type: string) => {
    const layout = dashboardCards.value.find(item => item.type === type);
    if (!layout) return;
    const item = getGridItem(type)
    const currentNumber = item?.gridstackNode?.w ?? getCardSpan(layout)
    const nextSpan = currentNumber === 4 ? 1 : currentNumber + 1;
    await setCardWidth(type, nextSpan)
};

const autoArrangeDashboard = async () => {
    if (!grid.value) return

    const sortedNodes = [...grid.value.engine.nodes].sort((a, b) => (a.y ?? 0) - (b.y ?? 0) || (a.x ?? 0) - (b.x ?? 0))
    grid.value.setAnimation(false)
    grid.value.batchUpdate()

    for (const node of sortedNodes) {
        const type = String(node.id || node.el?.dataset.dashboardCard || '')
        if (!type || !node.el) continue

        const card = dashboardCards.value.find((item) => item.type === type)
        if (card) {
            card.col = 'col-span-1'
            prefsStore.setColSpan(type, card.col)
        }

        grid.value.update(node.el, { w: 1 })
        writeGridStackAttributes(node.el)
    }

    grid.value.batchUpdate(false)
    grid.value.compact('compact', true)
    await nextTick()
    await syncVisibleGridHeights()
    grid.value.compact('compact', true)

    for (const node of grid.value.engine.nodes) {
        writeGridStackAttributes(node.el ?? null)
    }

    saveGridState()
    requestAnimationFrame(() => {
        grid.value?.setAnimation(true)
    })
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
const customize = () => {
    customizing.value = !customizing.value
}
onMounted(async () => {
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', syncGridAfterViewportChange)
    }
    if (typeof document !== 'undefined') {
        document.addEventListener('keydown', handleGlobalGridKeydown, true)
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
    await initGridStack()
    setupDashboardCardHeightObservers()
}
const refreshAll = async () => {
    refreshingDashboard.value = true
    try {
        stopDashboardCardHeightObservers()
        updateKey.value += 1
        await dashboardGoalsStore.initDashboardData(true) // Force refresh
        syncDashboardCardsFromStore()
        dashboardStore.getAnnualLeaveData()
        toast('ダッシュボードを更新しました。')
    } catch (e) {
        ping((e as any)?.response?.data?.message || (e as any)?.message || '更新に失敗しました。')
    } finally {
        refreshingDashboard.value = false
    }
    await nextTick()
    await initGridStack()
    setupDashboardCardHeightObservers()
}
watch(
    () => initialLoader.value,
    async (loading) => {
        if (loading) return
        await nextTick()
        await initGridStack()
        setupDashboardCardHeightObservers()
    },
    { immediate: true },
)

watch(
    () => visibleDashboardCards.value.map((card) => card.type).join('|'),
    async () => {
        if (initialLoader.value) return
        await nextTick()
        await initGridStack()
        setupDashboardCardHeightObservers()
    },
    { flush: 'post' },
)

watch(
    () => route.params.type,
    async () => {
        if (route.params.type) customizing.value = false
        await nextTick()
        applyGridInteractionMode()
    },
)

watch(
    () => customizing.value,
    (enabled) => {
        applyGridInteractionMode()
        if (!enabled) scheduleGridStateSave()
    },
)

watch(
    () => dashboardStore.collection,
    () => syncDashboardCardsFromStore(),
    { deep: true },
)

watch(
    () => auth.activeUser?.id,
    async (userId, previousUserId) => {
        if (userId === previousUserId) return
        prefsStore.setActiveUser(userId)
        const nextDefaultCards = structuredClone(getDefaultDashboardCards())
        if (auth.isAdmin) {
            nextDefaultCards.push(structuredClone(ADMIN_PERSONNEL_EVALUATION_CARD))
        }
        prefsStore.applyLayoutToCards(nextDefaultCards)
        dashboardCards.value = prefsStore.applyOrderToCards(nextDefaultCards)
        syncDashboardCardsFromStore()
        buildSkeletonCards()
        await nextTick()
        await initGridStack()
        setupDashboardCardHeightObservers()
    },
)
defineExpose({
    refreshData
})

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', syncGridAfterViewportChange)
    }
    if (typeof document !== 'undefined') {
        document.removeEventListener('keydown', handleGlobalGridKeydown, true)
    }
    if (viewportResizeTimer) clearTimeout(viewportResizeTimer)
    if (gridInteractionReleaseFrame !== undefined) {
        cancelAnimationFrame(gridInteractionReleaseFrame)
        gridInteractionReleaseFrame = undefined
    }
    if (gridHeightSyncFrame !== undefined) {
        cancelAnimationFrame(gridHeightSyncFrame)
        gridHeightSyncFrame = undefined
    }
    pendingHeightSyncTypes.clear()
    if (gridStateSaveFrame !== undefined) {
        cancelAnimationFrame(gridStateSaveFrame)
        gridStateSaveFrame = undefined
        saveGridState()
    }
    stopDashboardCardHeightObservers()
    grid.value?.destroy(false)
    grid.value = null
})

provide('getAssets', () => refreshData('pending_approval_tasks'))
provide('refresh', () => refreshData('overdue_goals'))
</script>
