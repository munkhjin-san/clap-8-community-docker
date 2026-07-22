<template>
    <div class="section-wrapper">
        <div v-if="selectedTopic && isEnabled(selectedTopic.active)"  class="section-inner">

            <!-- <div v-if="selectedTopic && selectedTopic.guidance" v-html="selectedTopic?.guidance"></div> -->

            <!-- Path 3: AI study material is reference-only here — collapsed behind a link. -->
            <div v-if="hasAiMaterial" class="si-box" style="margin-top:0;">
                <p class="jump-link" @click="showAiMaterial = !showAiMaterial">
                    {{ showAiMaterial ? '閉じる' : 'AI生成学習教材を表示する' }}
                </p>
                <div v-if="showAiMaterial" class="markdown-content" v-html="aiMaterialHtml"></div>
            </div>

            <div v-if="portfolio && portfolio.positive_feedback">
                <p><strong>ポジティブフィードバック</strong></p>
                <p>{{ portfolio.positive_feedback }}</p>
            </div>
            <div class="si-box" v-if="portfolio && portfolio.negative_feedback">
                <p><strong>ネガティブフィードバック</strong></p>
                <p>{{ portfolio.negative_feedback }}</p>
            </div>
            <div class="si-box" v-if="portfolio && portfolio.noticed">
                <p><strong>フィードバックから得た発見と成長</strong></p>
                <p>{{ portfolio.noticed }}</p>
            </div>
            <!-- For path 3, `content` is the AI material (shown via the collapsible button above),
                 so this raw block is only for the first-timer's own discussion draft. -->
            <div class="si-box" v-if="portfolio && portfolio.content && !hasAiMaterial">
                <p><strong>ディスカッション用ポートフォリオタイトル</strong></p>
                <p>{{ portfolio.portfolio_title }}</p>
                <p><strong>ディスカッション用ポートフォリオ内容</strong></p>
                <p>{{ portfolio.content }}</p>
            </div>
            <div class="si-box">
                <p :style="{marginBottom: portfolio && portfolio.status == 2 ? '20px' : '0'}"><strong>{{portfolio && portfolio.status == 2 ? 'フィードバックによる発見と成長を反映し、ポートフォリオを完成させてください。' : 'ポートフォリオ'}}</strong></p>
                <ShortInput
                    v-if="portfolio && portfolio.status == 2"
                    :initialValue="portfolio.public_title ? portfolio.public_title : ''"
                    ref="portfolioTitle"
                    placeHolder="ポートフォリオタイトル"
                    name="portfolioTitle"
                    rules="required"
                    label="タイトル"
                    v-model="portfolio_title"
                />
                <p v-else>{{ portfolio?.public_title }}</p>
            </div>
            <div class="si-box">
                <LongInput
                    v-if="portfolio && portfolio.status == 2"
                    :initialValue="portfolio.public_content ? portfolio.public_content : ''"
                    :placeHolder="`ポートフォリオの内容`"
                    ref="portfolioBody"
                    :key="portfolio.public_content ? portfolio.public_content : 0"
                    rules="required"
                    name="recordBody"
                    label="タイトル"
                    v-model="portfolioContent"
                />
                <p v-else>{{ portfolio?.public_content }}</p>
            </div>
            <OpenAiReview 
                v-if="selectedTopic && portfolio && hasAfterDiscussionReview"
                :config-key="afterDiscussionConfig?.config_key"
                :lesson-theme-id="selectedTopic.id"
                :source-text="portfolio?.ai_review_final ?? undefined"
                :message="portfolioContent || portfolio?.public_content || ''"
                confirm-text="ポジティブ・ネガティブフィードバックから得た発見と成長がポートフォリオに反映されている。"
                ref="reviewElFinal"
            />
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
    // Path 3: the AI-generated study material, shown on demand (markdown-rendered).
    const showAiMaterial = ref(false)
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
