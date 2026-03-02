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
        <template #icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="appIcon mr-1 -mb-[2px]" width="17" viewBox="0 0 25.51 22.62">
                <path d="M25.51,19.04c0-2.15-.06-11.41-.07-13.39,0-.42-.01-1.56-.01-1.96v-.33s0-.08,0-.08c0-.76-.27-1.51-.76-2.08-.61-.72-1.55-1.15-2.5-1.12-.4,0-1.56.02-1.96.01C14.8.14,8.69-.01,3.19,0,1.51.03.06,1.49.04,3.17c0,0,0,.68,0,.68C.03,8.16,0,13.93,0,18.22c0,.2,0,.79,0,.98-.08,1.79,1.38,3.37,3.17,3.42,4.88,0,13.66,0,18.49-.02.1,0,.4,0,.49,0,1.4.05,2.74-.89,3.18-2.23.15-.42.18-.89.17-1.32ZM23.3,18.72s0,.63,0,.63c0,.54-.48,1.01-1.02,1.02-4.07-.02-12.77-.03-16.94-.03h-1.31s-.65,0-.65,0h-.08c-.3,0-.58-.13-.78-.36-.19-.22-.26-.5-.24-.77,0-.19,0-.79,0-.98,0-4.29-.01-10.05-.04-14.37v-.63c0-.32.16-.63.42-.83.17-.13.38-.21.6-.22h1.27c5.06-.03,10.64-.08,15.67-.14v.04s1.31,0,1.31,0h.65c.13,0,.26.01.38.05.45.12.8.55.83,1.01-.01,3.3-.06,12.16-.08,15.57Z"></path>
                <path d="M5.26,7.51c2.46.11,5.05.16,7.51.16,2.47-.01,5.05-.04,7.51-.2.47-.03.85-.4.88-.88.04-.52-.36-.98-.88-1.01-1.88-.13-3.76-.16-5.63-.19-3.09-.03-6.31.01-9.39.15-1.24.11-1.24,1.86,0,1.97Z"></path>
                <path d="M20.36,10.34c-1.89-.13-3.77-.16-5.66-.19-3.1-.03-6.35.01-9.44.15-1.24.11-1.24,1.86,0,1.97,2.47.11,5.07.16,7.55.16,2.49-.01,5.07-.04,7.55-.2.47-.03.85-.4.88-.88.04-.52-.36-.98-.88-1.01Z"></path>
                <path d="M20.29,15.1c-1.88-.13-3.76-.16-5.64-.19-3.09-.03-6.31.01-9.39.15-1.24.11-1.24,1.86,0,1.97,2.46.11,5.05.16,7.52.16,2.48-.01,5.05-.04,7.52-.2.47-.03.85-.4.88-.88.04-.52-.36-.98-.88-1.01Z"></path></svg>
        </template>
        <div v-if="!fullscreen" class="m-5">
            <div v-if="data.data.length" class="mb-3">
                <p class="text-sm mb-2">未回答フォーム</p>
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        selected-class="selected-panel-item"
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                        v-for="(form, index) in data.data"
                        :key="form.id ?? index"
                        :value="form.id ?? index"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">{{ form.title }}</PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div>
                                    <div v-html="form.description"></div>
                                </div>
                                <div class="mt-3 ml-auto w-fit">
                                    <router-link :to="`/survey/${form.id}`" class="jump-link text-[12px]">回答する</router-link>
                                </div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div v-else>
                <div class="text-sm text-[gray] mb-3 text-center">
                    未回答のフォームはありません。
                </div>
            </div>
            <div class="text-center">
                <router-link @click="viewHistory = true" :to="{name: 'dashboard', params: { type: 'forms'}}" class="jump-link text-sm text-center">
                    回答履歴を見る
                </router-link>
            </div>
        </div>
        <div v-show="fullscreen">
            <div class="p-4" v-if="data.data.length">
                <p>未回答フォーム{{ data.data.length }}件</p>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-3">
                    <div
                        v-for="form in data.data"
                        :key="form.id"
                        class="flex items-center justify-between p-3 border border-solid border-[var(--calendarBorder)] bg-[var(--bg3)] rounded-md"
                    >
                        <p class="text-sm font-medium truncate min-w-0">{{ form.title }}</p>
                        <CommandButton
                            :buttons="[
                                { title: '回答', action: () => router.push(`/survey/${form.id}`) },
                            ]"
                        />
                    </div>
                </div>
            </div>
            <div v-else>
                <div class="text-center text-sm text-[gray] py-3">
                    未回答のフォームはありません。
                </div>
            </div>
            <div>
                <button @click="viewHistory = !viewHistory" class="text-sm jump-link ml-4 mb-2">
                    {{ viewHistory ? '回答履歴を非表示にする' : '回答履歴を表示する' }}
                </button>
            </div>
            <MySurveyAnswers v-if="loadCount >= 1 && viewHistory"/>
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
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';

const props = defineProps<{
    data: {
        title: string,
        data: CustomForm[],
        order?: number,
        type: string
        canResize?: boolean
        canFullscreen?: boolean
        col?: string
    }
    fullscreen: boolean
}>()

const MySurveyAnswers = defineAsyncComponent(() => import('@/components/Survey/MySurveyAnswers.vue'))
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