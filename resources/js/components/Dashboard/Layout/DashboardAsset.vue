<template>
    <BaseLayout
        :title="data.title" 
        :count="data.data.length" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <div v-if="!fullscreen" class="mx-3 mb-3">
            <div v-if="data.data.length" class="mb-3">
                <p class="text-sm mb-2">使用中の物品</p>
                <v-expansion-panels>
                    <v-expansion-panel selected-class="selected-panel-item" hide-actions static :tile="true" class="rm-p" v-for="(asset, index) in data.data.slice(0, 5)" :key="index">
                        <v-expansion-panel-title>
                            <template v-slot:default="{ expanded }">
                                <PanelTitle :expanded="expanded">{{ asset.item_name }}</PanelTitle>
                            </template>
                        </v-expansion-panel-title>
                        <v-expansion-panel-text>
                            <PanelData>
                                
                            </PanelData>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>
                <div class="text-xs ml-4 mt-2 text-[gray]" v-if="data.data.length > 5">
                    ...その他{{ data.data.length - 5 }}件
                </div>
            </div>
            <div v-else>
                <div class="text-sm text-[gray] mb-3 text-center">
                    使用中の物品はありません。
                </div>
            </div>
            <div class="text-center">
                <router-link @click="viewHistory = true" :to="{name: 'dashboard', params: { type: 'assets'}}" class="jump-link text-sm text-center">
                    詳細を見る
                </router-link>
            </div>
        </div>
        <div v-show="fullscreen">
            <!-- <div class="p-4" v-if="data.data.length">
                
                
            </div>
            <div v-else>
                <div class="text-center text-sm text-[gray] py-3">
                    使用中の物品はありません。
                </div>
            </div>
            <div>
                <button @click="viewHistory = !viewHistory" class="text-sm jump-link ml-4 mb-2">
                    {{ viewHistory ? '使用中の物品の履歴を非表示にする' : '使用中の物品の履歴を表示する' }}
                </button>
            </div> -->


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
// import MySurveyAnswers from '@/components/Survey/MySurveyAnswers.vue';

const props = defineProps<{
    data: {
        title: string,
        data: Asset[],
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
}>()
const router = useRouter()
const route = useRoute()
const parent = useTemplateRef('parent')
const auth = useAuthUserStore()
const loadCount = ref(0)
const viewHistory = ref(false)

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