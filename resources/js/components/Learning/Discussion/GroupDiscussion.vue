<template>
    <div class="section-wrapper">
        <div class="section-inner" v-if="selectedTopic && isEnabled(selectedTopic.active)">
        
            <div>
                <p><strong>ポートフォリオ</strong></p>
                <div class="markdown-content" v-html="portfolioContentHtml"></div>
            </div>
            <div class="si-box">
                <p :style="{marginBottom: portfolio && portfolio.status == 1 ? '20px' : '0'}"><strong>どのようなフィードバックをもらいましたか。</strong></p>
                <LongInput
                    v-if="portfolio && portfolio.status == 1"
                    :initialValue="portfolio?.positive_feedback ?? p_feedBack"
                    :placeHolder="`ポジティブフィードバックの内容`"
                    :key="portfolio?.positive_feedback ?? 0"
                    ref="p_feedbackBody"
                    name="recordBody"
                    label="タイトル"
                    v-model="p_feedBack"
                />
                <div v-else>
                    <p>ポジティブフィードバック</p>
                    <p>{{ portfolio?.positive_feedback }}</p>
                </div>
            </div>
            <div class="si-box">
                <LongInput
                    v-if="portfolio && portfolio.status == 1 "
                    :initialValue="portfolio?.negative_feedback ?? n_feedBack"
                    :placeHolder="`ネガティブフィードバックの内容`"
                    :key="portfolio.negative_feedback ?? 0"
                    ref="n_feedbackBody"
                    name="recordBody"
                    label="タイトル"
                    v-model="n_feedBack"
                />
                <div v-else>
                    <p>ネガティブフィードバック</p>
                    <p>{{ portfolio?.negative_feedback }}</p>
                </div>
            </div>
            <div class="si-box">
                <p :style="{marginBottom: portfolio && portfolio.status == 1 ? '20px' : '0'}"><strong>フィードバックから得た発見と成長</strong></p>
                <LongInput
                    v-if="portfolio && portfolio.status == 1 "
                    :initialValue="portfolio?.noticed ?? noticed"
                    :placeHolder="`発見と成長の内容`"
                    :key="portfolio.noticed ?? 0"
                    ref="noticedBody"
                    name="recordBody"
                    label="タイトル"
                    v-model="noticed"
                />
                <div v-else>
                    <p>{{ portfolio?.noticed }}</p>
                </div>
            </div>
            <div v-if="portfolio && portfolio.status == 1 " style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                <div>
                    <LoaderButton @triggered="saveContent('save')" :loading="processing_save" :content="'一時保存'"/>
                </div>
                <div>
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'ディスカッション完了'"/>
                </div>
            </div>
            
            
        </div>
    </div>
    
</template>
<script setup lang="ts">
import LongInput from '../../Form/LongInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import { computed, ref, inject, watch, type Ref } from 'vue'
import { useRoute, useRouter } from 'vue-router';
import { useDialog } from '@/composables/dialog';
import { useLearningApi } from '@/composables/learningApi';
import { LESSON_PORTFOLIO_STATUS } from '@/config/learning';
import { isEnabled } from '@/utils/learningProgress';
import { renderMarkdown } from '@/utils/markdown';
import type { LearningPortfolio, LearningTheme } from '@/types/learning';

    const props = defineProps<{
        selectedTopic?: LearningTheme | null
        editTarget?: boolean
    }>();
    const portfolio = inject<Ref<LearningPortfolio | null>>('portfolio')
    const route = useRoute()
    const p_feedBack = ref('')
    const n_feedBack = ref('')
    const p_feedbackBody = ref<unknown>(null)
    const n_feedbackBody = ref<unknown>(null)
    const noticedBody = ref<unknown>(null)
    const noticed = ref('')
    const processing = ref(false)
    const router = useRouter()
    const lesson = inject<() => void | Promise<void>>('getLessonPortfolios')
    const processing_save = ref(false)
    const learningApi = useLearningApi()
    const { ask, toast } = useDialog()
    const themeId = computed(() => route.params.lessonThemeId)
    const portfolioContentHtml = computed(() => renderMarkdown(portfolio?.value?.content))

    watch(portfolio ?? ref(null), (record) => {
        p_feedBack.value = record?.positive_feedback ?? ''
        n_feedBack.value = record?.negative_feedback ?? ''
        noticed.value = record?.noticed ?? ''
    }, { immediate: true })
   
    const saveContent = async(status: 'save' | 'next') => {
        if (!portfolio?.value || !themeId.value) return

        let portfolioStatus: number = LESSON_PORTFOLIO_STATUS.DISCUSSION_DRAFT_READY
        if(status == 'next'){
            processing.value = true
            portfolioStatus = LESSON_PORTFOLIO_STATUS.DISCUSSION_COMPLETED
        }else{
            processing_save.value = true
        }
        
        const params = {
            theme_id: themeId.value,
            params:{
                positive_feedback: p_feedBack.value || portfolio.value.positive_feedback,
                negative_feedback: n_feedBack.value || portfolio.value.negative_feedback,
                noticed: noticed.value || portfolio.value.noticed,
                status: portfolioStatus,
            }

        }
        await learningApi.savePortfolio(params)
        if(status == 'save'){
            toast(props.editTarget ? '編集しました。' :'保存しました。')
            setTimeout(() => {
                processing_save.value = false
            }, 500);
            
        }
            

        
    }
    
    const nextStage = async() => {
        const answer = await ask('グループディスカッションを完了にしますか。\n完了後は編集ができません。')
        if(!answer.value) return
        await saveContent('next')
        setTimeout(() => {                    
            finishDiscussion()
            processing_save.value = false
        }, 1000);      
    }
    const finishDiscussion = async() => {
        const options = {
            answers: [{label: 'OK', value: true}]
        }
        const answer = await ask('グループディスカッションを完了にしまた。\nお疲れ様でした。', options)
        if(answer.value){
            await lesson?.()
            router.push({name: 'top'})
        }
            
         
    }
    
</script>
<style>
    .inactive-overlay{
        color: white;
        font-size: 20px;
        text-align: center;
        padding: 20px;
    }
    .markdown-content {
        margin-top: 10px;
        line-height: 1.9;
        word-break: break-word;
    }

    .markdown-content p,
    .markdown-content ul,
    .markdown-content ol {
        margin: 0 0 12px;
    }

    .markdown-content h1,
    .markdown-content h2,
    .markdown-content h3,
    .markdown-content h4 {
        margin: 18px 0 10px;
        font-weight: 700;
        line-height: 1.55;
    }

    .markdown-content ul,
    .markdown-content ol {
        padding-left: 1.4em;
    }
</style>
