<template>
    <div class="section-wrapper">
        <div class="" v-if="selectedTopic && isEnabled(selectedTopic.active)">

            <!-- <div v-if="selectedTopic && selectedTopic.guidance" v-html="selectedTopic?.guidance"></div> -->

            <!-- Path 3: studied AI material as the shared collapse card (reference, collapsed). -->
            <LearningCollapseCard v-if="hasAiMaterial" label="個人専用研修資料">
                <div class="markdown-content" v-html="aiMaterialHtml"></div>
            </LearningCollapseCard>

            <!-- Discussion output: first-timer's draft + feedback + growth, collapsed. -->
            <LearningCollapseCard label="グループディスカッション内容">
                <div v-if="portfolio && portfolio.content && !hasAiMaterial" class="cp-block">
                    <p class="cp-label">ディスカッション用ポートフォリオタイトル</p>
                    <p class="cp-text">{{ portfolio.portfolio_title }}</p>
                    <p class="cp-label cp-label--mt">ディスカッション用ポートフォリオ内容</p>
                    <p class="cp-text">{{ portfolio.content }}</p>
                </div>
                <div v-if="portfolio && portfolio.positive_feedback" class="cp-block">
                    <p class="cp-label">ポジティブフィードバック</p>
                    <p class="cp-text">{{ portfolio.positive_feedback }}</p>
                </div>
                <div v-if="portfolio && portfolio.negative_feedback" class="cp-block">
                    <p class="cp-label">ネガティブフィードバック</p>
                    <p class="cp-text">{{ portfolio.negative_feedback }}</p>
                </div>
                <div v-if="portfolio && portfolio.noticed" class="cp-block">
                    <p class="cp-label">フィードバックから得た発見と成長</p>
                    <p class="cp-text">{{ portfolio.noticed }}</p>
                </div>
            </LearningCollapseCard>

            <!-- Final portfolio: editable while completing (status 2), read-only collapse card once finished. -->
            <template v-if="portfolio && portfolio.status == 2">
                <div class="m-5 p-5 bg-[var(--background-color)]">
                    <div class="si-box">
                        <p style="margin-bottom:20px"><strong>フィードバックによる発見と成長を反映し、ポートフォリオを完成させてください。</strong></p>
                        <ShortInput
                            :initialValue="portfolio.public_title ? portfolio.public_title : ''"
                            ref="portfolioTitle"
                            placeHolder="ポートフォリオタイトル"
                            name="portfolioTitle"
                            rules="required"
                            label="タイトル"
                            v-model="portfolio_title"
                        />
                    </div>
                    <div class="si-box">
                        <LongInput
                            :initialValue="portfolio.public_content ? portfolio.public_content : ''"
                            :placeHolder="`ポートフォリオの内容`"
                            ref="portfolioBody"
                            :key="portfolio.public_content ? portfolio.public_content : 0"
                            rules="required"
                            name="recordBody"
                            label="タイトル"
                            v-model="portfolioContent"
                        />
                    </div>
                </div>
            </template>
            <LearningCollapseCard v-else label="完成したポートフォリオ" :default-open="true">
                <h3 v-if="portfolio?.public_title" class="cp-title">{{ portfolio?.public_title }}</h3>
                <p class="cp-text">{{ portfolio?.public_content }}</p>
            </LearningCollapseCard>
            <div v-if="selectedTopic && portfolio && hasAfterDiscussionReview && portfolio.status == 2" class="m-5 p-5 bg-[var(--background-color)]">           
                <OpenAiReview                     
                    :config-key="afterDiscussionConfig?.config_key"
                    :lesson-theme-id="selectedTopic.id"
                    :source-text="portfolio?.ai_review_final ?? undefined"
                    :message="portfolioContent || portfolio?.public_content || ''"
                    confirm-text="ポジティブ・ネガティブフィードバックから得た発見と成長がポートフォリオに反映されている。"
                    ref="reviewElFinal"
                />
            </div>
            <div v-if="portfolio && portfolio.status == 2" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                <div>
                    <LoaderButton @triggered="savePortfolio('save')" :loading="processing_save" :content="'一時保存'"/>
                </div>
                <div>
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'次へ'"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import LongInput from '../../Form/LongInput.vue';
import ShortInput from '../../Form/ShortInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import LearningCollapseCard from '@/components/Learning/shared/LearningCollapseCard.vue';
import { computed, ref, onBeforeMount, inject, watch, type Ref } from 'vue'
import OpenAiReview from '../../Global/OpenAiReview.vue'
import { useLearningApi } from '@/composables/learningApi';
import { useDialog } from '@/composables/dialog';
import { LESSON_PORTFOLIO_STATUS } from '@/config/learning';
import { isEnabled } from '@/utils/learningProgress';
import { renderMarkdown } from '@/utils/markdown';
import type { LearningPortfolio, LearningTheme } from '@/types/learning';

    const PORTFOLIO_AFTER_DISCUSSION_CONFIG_KEY = 'portfolio_after_discussion'
    const props = defineProps<{
        selectedTopic?: LearningTheme | null
        available?: boolean
        editTarget?: boolean
    }>()
    const portfolio = inject<Ref<LearningPortfolio | null>>('portfolio')
    const portfolioContent = ref('')
    const portfolioBody = ref<any>(null)
    const portfolioTitle = ref<any>(null)
    const processing = ref(false)
    const router = useRouter()
    const processing_save = ref(false)
    const portfolio_title = ref('')
    const route = useRoute()
    const reviewElFinal = ref<any>(null)
    const learningApi = useLearningApi()
    const { ask, ping, toast } = useDialog()
    const afterDiscussionConfig = computed(() => {
        return props.selectedTopic?.ai_configs?.find(config => config.config_key === PORTFOLIO_AFTER_DISCUSSION_CONFIG_KEY) ?? null
    })
    const hasAfterDiscussionReview = computed(() => Boolean(afterDiscussionConfig.value))
    // Path 3: the AI-generated study material (markdown-rendered) in a collapse card.
    const hasAiMaterial = computed(() => Boolean(portfolio?.value?.ai_material))
    const aiMaterialHtml = computed(() => renderMarkdown(portfolio?.value?.ai_material))
    watch(portfolio ?? ref(null), (record) => {
        portfolioContent.value = record?.public_content ?? ''
        portfolio_title.value = record?.public_title ?? ''
    }, { immediate: true })
    onBeforeMount(() => {
        setTimeout(() => {
            if(!props.selectedTopic?.lesson_portfolio || Number(props.selectedTopic.lesson_portfolio.status ?? 0) < LESSON_PORTFOLIO_STATUS.DISCUSSION_COMPLETED){
                backToast()
            }
        }, 500)
        
    })
    const savePortfolio = async(status: 'save' | 'next') => {
        if (!portfolio?.value) return

        let portfolioStatus: number = LESSON_PORTFOLIO_STATUS.DISCUSSION_COMPLETED
        if(status == 'next'){
            
            processing.value = true
            portfolioStatus = LESSON_PORTFOLIO_STATUS.FINAL_COMPLETED
        }else{
            processing_save.value = true
        }
        const params = {
            theme_id: route.params.lessonThemeId,
            params: {
                portfolio_title: portfolio.value.portfolio_title,
                content: portfolio.value.content,
                public_title: portfolio_title.value,
                public_content: portfolioContent.value,
                status: portfolioStatus,
                ai_review_final: reviewElFinal.value?.reviewResultRaw ?? portfolio.value.ai_review_final,
            }  

        }
        await learningApi.savePortfolio(params)
        if(status == 'next'){

        }else{
            toast(props.editTarget ? '編集しました。' :'保存しました。')
            processing_save.value = false
        }

    }
    const nextStage = async() => {
        const result = await portfolioBody.value.validate()
        const title_result = await portfolioTitle.value.validate()
        if(hasAfterDiscussionReview.value && !reviewElFinal.value?.reviewResultRaw){
            ping('ポートフォリオを完了する前、AI分析してください。')
            return
        }
        const valid = hasAfterDiscussionReview.value ? await reviewElFinal.value?.validate() : true
        if(result.valid && title_result.valid && valid){
            const answer = await ask('ポートフォリオを完了にしますか。\n完了後は編集ができません。')
                                      
            if(!answer.value) return
            await savePortfolio('next')
            setTimeout(() => {                    
                processing.value = false
                router.push({name: 'form'})
            }, 1000);
               
        }
        
    }
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
<style scoped>
.cp-block { margin-bottom: 22px; }
.cp-block:last-child { margin-bottom: 0; }
.cp-label { margin: 0 0 6px; font-size: 13px; color: var(--third-color); }
.cp-label--mt { margin-top: 14px; }
.cp-text {
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.9;
}
.cp-title { margin: 0 0 12px; font-size: 18px; }
</style>
