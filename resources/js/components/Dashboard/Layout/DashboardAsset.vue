<template>
    <BaseLayout
        :title="data.title" 
        :count="data.data.in_use.length" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <div v-if="!fullscreen" class="mx-3 mb-3">
            <div v-if="data.data.in_use.length" class="mb-3">
                <p class="text-sm mb-2">使用中の物品</p>
                <v-expansion-panels>
                    <v-expansion-panel selected-class="selected-panel-item" hide-actions static :tile="true" class="rm-p" v-for="(asset, index) in data.data.in_use" :key="index">
                        <v-expansion-panel-title>
                            <template v-slot:default="{ expanded }">
                                <PanelTitle :expanded="expanded">
                                    <div>
                                        <div class="mr-1 ml-[-5px]" v-if="asset.confirm_logs.length">
                                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="13" viewBox="0 0 38 32" style="fill: rgb(100, 188, 68);; margin-left: 4px;">
                                                <path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                            </svg>
                                        </div>
                                        <div v-else-if="currentMonth >= ASSET_CONFIRM_DEADLINE_MONTH" class="mx-1">
                                            <span style="position: unset;" :class="['side-notification !w-2 !min-w-2 !h-2', 'custom-heartbeat',  ]"></span>  
                                        </div>
                                    </div>
                                    
                                    {{ asset.item_name }}
                                </PanelTitle>
                            </template>
                        </v-expansion-panel-title>
                        <v-expansion-panel-text>
                            <PanelData>
                                <div v-if="asset.confirm_logs.length">
                                    <p class="text-sm text-[var(--primary-color)] mb-2">今年の確認履歴</p>
                                    <div class="flex flex-col gap-2">
                                        <div v-for="log in asset.confirm_logs" :key="log.id" class="flex flex-col gap-2 text-[12px]">
                                            <UserPanel v-if="log.user" :user="log.user" size="20" with-name disable-instant/>
                                            <div class="text-[11px] text-[gray]">{{ DateTime.fromISO(log.created_at).toLocaleString(DateTime.DATETIME_MED) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <div class="text-sm text-[gray] mb-3 text-center">
                                        物品の確認がまだ行われていません。
                                    </div>
                                </div>
                            </PanelData>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>
            </div>
            <div v-else>
                <div class="text-sm text-[gray] mb-3 text-center">
                    使用中の物品はありません。
                </div>
            </div>
            <div class="mt-5" v-if="data.data.waiting_approval && data.data.waiting_approval.length">
                <p class="text-sm mb-2">【管理者】承認待ちの物品</p>
                <v-expansion-panels>
                    <v-expansion-panel selected-class="selected-panel-item" hide-actions static :tile="true" class="rm-p" v-for="(asset, index) in data.data.waiting_approval" :key="index">
                        <v-expansion-panel-title>
                            <template v-slot:default="{ expanded }">
                                <PanelTitle v-if="asset.requests" :expanded="expanded">
                                    <div class="flex items-center gap-2 text-[12px]" v-for="assetRequest in asset.requests">
                                        <div v-if="assetRequest.send_user">{{ assetRequest.send_user.name }}</div>
                                        <div v-if="assetRequest.from_external_user">{{ assetRequest.from_external_user }}</div>
                                        <div>➞</div>
                                        <div v-if="assetRequest.recieve_user">{{ assetRequest.recieve_user.name }}</div>
                                        <div v-if="assetRequest.to_external_user">{{ assetRequest.to_external_user }}</div>
                                    </div>
                                    <div>【{{ asset.item_name }}】</div>
                                </PanelTitle>
                            </template>
                        </v-expansion-panel-title>
                        <v-expansion-panel-text>
                            <PanelData>
                                <div v-if="asset?.requests && asset.requests.length" class="bg-[var(--background-color)] w-fit rounded mb-4">
                                    <AssetMovement
                                        v-for="assetRequest in asset.requests"
                                        :asset="asset" 
                                        :assetRequest="assetRequest"
                                        :possible-members="[]"
                                        @reload="emit('refreshData', 'assets')"                                                      
                                        
                                    />
                                </div>
                            </PanelData>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>
            </div>
            <div class="text-center">
                <router-link @click="viewHistory = true" :to="{name: 'dashboard', params: { type: 'assets'}}" class="jump-link text-sm text-center">
                    詳細を見る
                </router-link>
            </div>
        </div>
        <div v-if="fullscreen">
            <AssetContainer :user-list="[]"/>
        </div>
    </BaseLayout>
            
</template>
<script setup lang="ts">
import { CustomForm } from '@/interface/customFormInterface';
import { defineAsyncComponent, onMounted, ref, useTemplateRef, watch } from 'vue';
import BaseLayout from './BaseLayout.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import { Asset } from '@/interface/assetInterface';
import AssetContainer from '@/components/Asset/AssetContainer.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import AssetMovement from '@/components/Asset/AssetMovement.vue';
import { DateTime } from 'luxon';

// import MySurveyAnswers from '@/components/Survey/MySurveyAnswers.vue';

const props = defineProps<{
    data: {
        title: string,
        data : {
            in_use: Asset[]
            waiting_approval?: Asset[]
        },
        order?: number,
        type: string
        canResize?: boolean
        canFullscreen?: boolean
    }
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
    refreshData: [key: string]
}>()
const router = useRouter()
const route = useRoute()
const parent = useTemplateRef('parent')
const auth = useAuthUserStore()
const loadCount = ref(0)
const viewHistory = ref(false)
const ASSET_CONFIRM_DEADLINE_MONTH = 4
const currentMonth = DateTime.now().month
onMounted(() => {
    if(route.params.type === props.data.type) {
        loadCount.value += 1

    }
})

defineExpose({
    cardType: props.data.type,
})

watch(() => props.fullscreen, (newVal) => {
    if (newVal) {
        loadCount.value += 1

    }
})
</script>