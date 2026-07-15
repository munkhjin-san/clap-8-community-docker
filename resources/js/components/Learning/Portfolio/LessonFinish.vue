<template>
    <div v-if="selectedTopic && isEnabled(selectedTopic.active)">
        <div class="h-[calc(100%-110px)] flex items-center justify-center absolute w-full leading-7" >
            <div class="bg-[var(--bg3)] p-5 rounded-lg">
                <div class="flex gap-2.5 mb-7 items-center">
                    <LearningStatusMark />
                    <p class="text-xl"><strong>研修完了しました。</strong></p>
                </div>
                <p class="leading-7">
                    <strong>【{{ selectedTopic ? selectedTopic.title : ''}}】</strong>研修の全行程を修了しました。<br>お疲れ様でした。
                </p>
                <div class="mt-5">
                    <LoaderButton @triggered="router.push({name: 'learning'})" content="ホーム画面へ戻る"/>
                </div>
            </div>            
        </div>
    </div>
</template>
<script setup lang="ts">
import { useRouter } from 'vue-router';
import LoaderButton from '../../Global/LoaderButton.vue';
import { inject, onBeforeMount, type Ref } from 'vue';
import { useDialog } from '@/composables/dialog';
import LearningStatusMark from '@/components/Learning/shared/LearningStatusMark.vue';
import { LESSON_PORTFOLIO_STATUS } from '@/config/learning';
import { isEnabled } from '@/utils/learningProgress';
import type { LearningPortfolio, LearningTheme } from '@/types/learning';

    defineProps<{
        selectedTopic?: LearningTheme | null
    }>()
    const router = useRouter()
    const portfolio = inject<Ref<LearningPortfolio | null>>('portfolio')
    const { ask } = useDialog()
    onBeforeMount(() => {
        setTimeout(() => {
            if(portfolio?.value && Number(portfolio.value.status) < LESSON_PORTFOLIO_STATUS.DISCUSSION_COMPLETED){
                backToast()
            }
        }, 500)
    })
    const backToast = async() => {
        const options = {
            answers: [{label: '戻る', value: true}]
        }
        const answer = await ask('グループディスカッションを完了してください。', options)
        if(answer.value){
            router.go(-1)
        }
    }
</script>
