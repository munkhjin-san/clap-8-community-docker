<template>
    <DraftLayout>
        <template #main>
            
            <div class="si-box" style="margin-top: 0;">
                <p><strong>ディスカッション用ポートフォリオタイトル</strong></p>
                <p>{{ portfolio?.portfolio_title }}</p>
            </div>
            <div class="si-box">
                <p><strong>ディスカッション用ポートフォリオ内容</strong></p>
                <div class="markdown-content" v-html="portfolioContentHtml"></div>
            </div>
            <div class="si-box" v-if="isDraftEditable">
                <p><strong>ディスカッション用ポートフォリオエピソード</strong></p>
                <div class="markdown-content" v-html="portfolioEpisodeHtml"></div>
            </div>
            <div class="si-box" v-if="isDraftEditable">
                <LoaderButton @triggered="finishPortfolio()" :loading="loading[0]" :content="'作成完了'"/>               
            </div>
            <div v-else class="si-box" style="margin:45px 0">
                <LoaderButton :loading="false" content="ポートフォリオ作成例を確認する" @triggered="viewPortfolios"/>
            </div>
        </template>
    </DraftLayout>
</template>
<script setup lang="ts">
import {useRoute, useRouter} from 'vue-router'
import DraftLayout from '../DraftLayout.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { computed } from 'vue';
import { useDialog } from '@/composables/dialog';
import { useLearningDraftContext } from '@/composables/learningDraftContext';
import { LESSON_PORTFOLIO_STATUS } from '@/config/learning';
import { renderMarkdown } from '@/utils/markdown';
const route = useRoute()
const router = useRouter()
const { portfolio, basicItem, getLessonPortfolios } = useLearningDraftContext()
const { loading, saveItems, viewPortfolios } = basicItem
const { ask } = useDialog()
const isDraftEditable = computed(() => Number(portfolio.value?.status ?? 0) < 1)
const portfolioContentHtml = computed(() => renderMarkdown(portfolio.value?.content))
const portfolioEpisodeHtml = computed(() => renderMarkdown(portfolio.value?.episode))
const params = computed(() => {
    const contentParts = [
        portfolio.value?.content,
        portfolio.value?.episode,
    ].filter(Boolean)

    return  {
        params: {
            status : LESSON_PORTFOLIO_STATUS.DISCUSSION_DRAFT_READY,
            content: contentParts.join('\n\n')
        },
        theme_id: route.params.lessonThemeId,
    }
})
const finishPortfolio = async() => {
    const answer = await ask('知識研修を完了にしますか。\n完了後は編集ができません。')
    if(!answer.value) return  
    await saveItems('summary', 0, [], params.value)
    setTimeout(() => {                    
        finishBasic()
    }, 1000); 
}

const finishBasic = async() => {
    const options = {
        answers: [{label: 'OK', value: true}]
    }
    const answer = await ask('知識研修完了しました。\nお疲れ様でした。', options)
    if(answer.value){
        loading.value[0] = false
        await getLessonPortfolios?.()
        router.push({name: 'top'})
    }        
} 
</script>
<style scoped>
.markdown-content {
    margin-top: 10px;
    line-height: 1.9;
    word-break: break-word;
}

.markdown-content :deep(p),
.markdown-content :deep(ul),
.markdown-content :deep(ol) {
    margin: 0 0 12px;
}

.markdown-content :deep(h1),
.markdown-content :deep(h2),
.markdown-content :deep(h3),
.markdown-content :deep(h4) {
    margin: 18px 0 10px;
    font-weight: 700;
    line-height: 1.55;
}

.markdown-content :deep(ul),
.markdown-content :deep(ol) {
    padding-left: 1.4em;
}
</style>
