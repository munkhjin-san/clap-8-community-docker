<template>
    <BaseLayout
        :title="data.title"
        :count="unreadCount"
        :fullscreen="fullscreen"
        :type="data.type"
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el) => emit('toggle', el, data.type)"
        @resize="emit('resize', data.type)"
    >
        <template #icon>
            <svg style="margin-right: 5px;fill: currentColor;" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 30 30">
                <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
            </svg>
        </template>

        <div v-if="!fullscreen" class="m-5">
            <div v-if="data.data.length" class="mb-3">
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        selected-class="selected-panel-item"
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                        v-for="(notice, index) in data.data"
                        :key="notice.id ?? index"
                        :value="notice.id ?? index"
                        :col="Number(data.col?.split('-')[2] ?? 1)"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded" @click="loadBody(notice.id)">
                                <div v-if="!expanded" class="text-[14px] flex-1 whitespace-nowrap overflow-hidden text-ellipsis leading-normal flex items-center">
                                    <div v-if="!notice.read" class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5"></div>
                                    <div class="text-[14px] flex-1 whitespace-nowrap overflow-hidden text-ellipsis leading-normal" v-html="notice.title"></div>
                                </div>                                
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData class="px-4 py-4 pt-0">
                                <p class="mb-5">{{ notice.title }}</p>
                                <div v-if="bodyLoading.includes(notice.id)" class="py-3 flex items-center justify-center">
                                    <div class="spinner-micro"></div>
                                </div>
                                <div class="whitespace-pre-wrap text-[13px] leading-normal" v-html="urlCheck(notice.body)"></div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div class="text-sm text-[gray] mb-3 text-center" v-else>
                未読のお知らせはありません。
            </div>
            <div class="text-center">
                <router-link :to="{name: 'dashboard', params: { type: 'notice'}}" class="jump-link text-sm text-center">
                    一覧を見る
                </router-link>
            </div>
        </div>
        <div v-if="fullscreen" class="min-h-full">
            <Notice />
        </div>
    </BaseLayout>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { DateTime } from 'luxon';
import BaseLayout from './BaseLayout.vue';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import { customParser, urlCheck } from '@/utils/tools';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import Notice from '@/components/Notice/Notice.vue';
import { NoticeRecord } from '@/interface/notice';


const props = defineProps<{
    data: {
        title: string
        data: NoticeRecord[]
        order?: number
        type: string
        canResize?: boolean
        canFullscreen?: boolean
        col?: string
    }
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
    refreshData: [key: string]
}>()
const bodyLoading = ref<number[]>([])

const loadBody = async (id: number) => {
    const notice = props.data.data.find(n => n.id === id)
    if(bodyLoading.value.includes(id)) return;
    if(notice && notice.body !== undefined) return;
    if (!notice || notice.body) return;

    try {
        bodyLoading.value.push(id)

        const data = await api.get(`/load_notice_body`, { id })
        notice.body = data.body
        setTimeout(() => {
            notice.read = true
        }, 500);
    } catch (error) {
        console.error('Failed to load notice body:', error)
    } finally {
        bodyLoading.value = bodyLoading.value.filter(loadingId => loadingId !== id)
        
    }
}

defineExpose({ cardType: props.data.type })

const auth = useAuthUserStore()
const api = useApi()

const unreadCount = computed(() => props.data.data.filter(d => !d.read).length)

// fullscreen pagination state




</script>