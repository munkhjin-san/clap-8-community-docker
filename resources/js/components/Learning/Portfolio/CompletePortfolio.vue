<template>
    <div class="section-wrapper">
        <div class="" v-if="selectedTopic && isEnabled(selectedTopic.active)">

            <!-- <div v-if="selectedTopic && selectedTopic.guidance" v-html="selectedTopic?.guidance"></div> -->

            <!-- Path 3: same 個別研修資料 as the basic / discussion stages —
                 slide presentation + text version. Reference only (read-only). -->
            <template v-if="presentationSpec">
                <div class="cp-presentation-card">
                    <div>
                        <h3>個別研修資料</h3>
                        <p>「{{ presentationSpec.goal_title }}」を達成するために</p>
                    </div>
                    <button type="button" class="cp-presentation-button" @click="presentationOpen = true">
                        研修資料を見る
                    </button>
                </div>
                <LearningCollapseCard label="テキスト版を見る">
                    <div class="markdown-content" v-html="materialTextHtml"></div>
                </LearningCollapseCard>
            </template>
            <LearningCollapseCard v-else-if="hasAiMaterial" label="個人専用研修資料">
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
        <LearningPresentationPreview
            v-if="presentationOpen && presentationSpec"
            :presentation="presentationSpec"
            :selectable="false"
            @close="presentationOpen = false"
        />
    </div>
</template>
<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import LongInput from '../../Form/LongInput.vue';
import ShortInput from '../../Form/ShortInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import LearningCollapseCard from '@/components/Learning/shared/LearningCollapseCard.vue';
import LearningPresentationPreview from '@/components/Learning/shared/LearningPresentationPreview.vue';
import { computed, ref, onBeforeMount, inject, watch, type Ref } from 'vue'
import OpenAiReview from '../../Global/OpenAiReview.vue'
import { useLearningApi } from '@/composables/learningApi';
import { useDialog } from '@/composables/dialog';
import { LESSON_PORTFOLIO_STATUS } from '@/config/learning';
import { isEnabled } from '@/utils/learningProgress';
import { renderMarkdown } from '@/utils/markdown';
import type { LearningPersonalMaterial, LearningPortfolio, LearningSlideDeckSpec, LearningTheme } from '@/types/learning';

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

    // Same 個別研修資料 as the basic / discussion stages (slide + text), read-only.
    const personalMaterial = ref<LearningPersonalMaterial | null>(null)
    const presentationOpen = ref(false)
    const presentationSpec = computed<LearningSlideDeckSpec | null>(() => {
        const spec = personalMaterial.value?.presentation_spec
        return spec && spec.format === 'slide_deck_v1' ? spec : null
    })
    const materialTextHtml = computed(() =>
        renderMarkdown(personalMaterial.value?.content ?? portfolio?.value?.ai_material),
    )
    const loadPersonalMaterial = async() => {
        const id = props.selectedTopic?.id ?? route.params.lessonThemeId
        if (!id) return
        try {
            const prev = await learningApi.getPreviousExperience(id as number | string)
            personalMaterial.value = prev?.personal_material ?? null
        } catch {
            personalMaterial.value = null
        }
    }
    watch(() => [props.selectedTopic?.id, hasAiMaterial.value], () => {
        if (hasAiMaterial.value) loadPersonalMaterial()
    }, { immediate: true })
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
/* 20px matches LearningCollapseCard's own m-[20px] so both cards align. */
.cp-presentation-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    margin: 20px;
    padding: 24px;
    background: var(--background-color);
    border: 1px solid var(--formBorder);
}
.cp-presentation-card h3 {
    margin: 0 0 8px;
    font-size: 18px;
    line-height: 1.5;
}
.cp-presentation-card p {
    margin: 0;
    font-size: 13px;
    line-height: 1.8;
}
.cp-presentation-button {
    padding: 11px 16px;
    border: 1px solid rgb(255 255 255 / 70%);
    background: gray;
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

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
