<template>
    <BaseLayout
        :title="data.title" 
        :count="actionCount"
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <template #icon>
        <svg class="mr-1" fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" height="15" width="23" viewBox="0 0 38.66398 27.74328">
            <path d="M38.27889,24.15441l-4.34658-6.55518c-.11054-.16687-.24313-.31433-.39069-.44421-.00984-2.04926-.01969-4.03534-.02099-5.6676l-.02627-7.34283-.0002-.05731c0-.01855.00005-.03699-.00116-.06616-.00583-.36346-.04756-.74017-.12887-1.09052-.36387-1.63098-1.5916-2.83307-2.90339-2.82867-.43313-.00714-1.69802.01874-2.13627.01099C23.35556.17028,16.3417.01012,11.31132.01598c-.45131-.00885-1.69631-.00397-2.12663-.01422-.22505-.00073-.54804-.00714-.77018.01013-1.72574.14752-3.00831,2.07385-2.9278,4.29608-.01431,3.90802-.01888,8.51929-.02285,12.98279-.07051.07263-.14771.13843-.20818.22058L.44704,24.06652c-.45965.62646-.57295,1.43896-.30215,2.17285.3367.91357,1.20136,1.50391,2.20182,1.50391h33.97224c.97796,0,1.83538-.57178,2.18333-1.45703.27804-.70605.19366-1.50293-.2234-2.13184ZM28.32446,2.21838c.44639-.00269,1.69189.01709,2.11699.01434.73945.00159,1.38261.85236,1.37382,1.80591-.01366,3.72528-.02848,8.21649-.04294,12.5318H7.33344c-.00763-4.2395-.01858-8.57074-.02406-12.26251l-.00055-.22943c-.00035-.02203.0003-.03442.0011-.04425l.00171-.03394c.00236-.91107.64131-1.69006,1.34138-1.66882.29547-.00354.94983-.00049,1.24053-.00641l18.43091-.10669ZM36.31895,25.44347h-13.43951l-1.11653-3.28662h-4.67464l-.98012,3.28662H2.34671c-.14143,0-.24991-.02832-.32867-.0625l3.44265-4.69275,1.20688-1.64514c.03938-.0542.21777-.17285.48777-.17285h24.81623c.28125,0,.45483.12549.48697.17188l1.0983,1.65643,3.10122,4.67706c-.08277.03711-.19688.06787-.33911.06787Z"/>
        </svg>
        </template>
        <div v-if="!fullscreen" class="mx-5 mt-5 mb-3">
            <div v-if="data.data.in_use.length" class="mb-3">
                <p class="text-sm mb-2">使用中の物品</p>
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        selected-class="selected-panel-item"
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                        v-for="(asset, index) in data.data.in_use"
                        :key="asset.id ?? index"
                        :value="asset.id ?? index"
                        :col="Number(data.col?.split('-')[2] ?? 1)"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                <div>
                                    <div class="mr-1 ml-[-5px]" v-if="asset.confirm_logs.length">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="13" viewBox="0 0 38 32" style="fill: rgb(100, 188, 68);; margin-left: 4px;">
                                            <path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                        </svg>
                                    </div>
                                    <!-- <div v-else-if="currentMonth >= ASSET_CONFIRM_DEADLINE_MONTH">
                                        <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>  
                                    </div> -->
                                </div>
                                
                                {{ asset.item_name }}
                            </PanelTitle>
                        </template>
                        <template #body>
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
                                    <div v-if="currentMonth >= ASSET_CONFIRM_DEADLINE_MONTH" class="text-[tomato] mb-3 text-center text-[12px]">
                                        物品の確認を行ってください。
                                    </div>
                                    <div v-else class="text-[gray] mb-3 text-center text-[12px]">
                                        物品の確認がまだ行われていません。
                                    </div>
                                </div>
                                <div class="mt-3 ml-auto w-fit">
                                    <router-link :to="{name: 'dashboard', params: { type: 'assets'}, query: {asset_id: asset.id}}" class="jump-link text-sm text-center">
                                        詳細
                                    </router-link>
                                </div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div v-else>
                <div class="text-sm text-[gray] mb-3 text-center">
                    使用中の物品はありません。
                </div>
            </div>
            <div class="mt-5" v-if="data.data.waiting_approval && data.data.waiting_approval.length">
                <p class="text-sm mb-2">【管理者】承認待ちの物品</p>
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        selected-class="selected-panel-item"
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                        v-for="(asset, index) in data.data.waiting_approval"
                        :key="asset.id ?? index"
                        :value="asset.id ?? index"
                        :col="Number(data.col?.split('-')[2] ?? 1)"
                    >
                        <template #title="{ expanded }">
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
                        <template #body>
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
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
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
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import BaseLayout from './BaseLayout.vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import { Asset } from '@/interface/assetInterface';
import AssetContainer from '@/components/Asset/AssetContainer.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import AssetMovement from '@/components/Asset/AssetMovement.vue';
import { DateTime } from 'luxon';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';

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
        col?: string
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
const ASSET_CONFIRM_DEADLINE_MONTH = 3
const currentMonth = DateTime.now().month
const unconfirmedAssetCount = computed(() => {
    if (currentMonth < ASSET_CONFIRM_DEADLINE_MONTH) return 0

    return props.data.data.in_use.filter((asset) => !asset.confirm_logs.length).length
})
const actionCount = computed(() => {
    return unconfirmedAssetCount.value + (props.data.data.waiting_approval?.length ?? 0)
})
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
