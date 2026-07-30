<template>
    <div class="section-wrapper">
        <div class="section-inner" v-if="selectedTopic && isEnabled(selectedTopic.active)">
        
            <div v-if="isSalaryChallenge">
                <!-- Same 個別研修資料 as the basic stage: slide presentation + text
                     version. Reference only here (read-only, no theme picker). -->
                <div class="gd-material">
                    <div v-if="presentationSpec" class="gd-presentation-card">
                        <div>
                            <h3>個別研修資料</h3>
                            <p>「{{ presentationSpec.goal_title }}」を達成するために</p>
                        </div>
                        <button type="button" class="gd-presentation-button" @click="presentationOpen = true">
                            研修資料を見る
                        </button>
                    </div>
                    <LearningCollapseCard v-if="presentationSpec" label="テキスト版を見る">
                        <div class="markdown-content" v-html="materialTextHtml"></div>
                    </LearningCollapseCard>
                    <LearningCollapseCard v-else label="個人専用研修資料">
                        <div class="markdown-content" v-html="aiMaterialHtml"></div>
                    </LearningCollapseCard>
                </div>

                <div class="si-box gd-theme">
                    <p><strong>グループディスカッション用テーマ</strong></p>
                    <p class="gd-theme__value">{{ portfolio?.discussion_theme || '（未選択）' }}</p>
                </div>
            </div>
            <div v-else>
                <!-- First-timer's portfolio in its own collapse card, open by default. -->
                <LearningCollapseCard label="ポートフォリオ" :default-open="true">
                    <div class="markdown-content" v-html="portfolioContentHtml"></div>
                </LearningCollapseCard>
            </div>
            <div class="m-5 p-5 bg-[var(--background-color)] ">
                <div>
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
        <LearningPresentationPreview
            v-if="presentationOpen && presentationSpec"
            :presentation="presentationSpec"
            :selectable="false"
            @close="presentationOpen = false"
        />
    </div>

</template>
<script setup lang="ts">
import LongInput from '../../Form/LongInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import LearningCollapseCard from '@/components/Learning/shared/LearningCollapseCard.vue';
import LearningPresentationPreview from '@/components/Learning/shared/LearningPresentationPreview.vue';
import { computed, ref, inject, watch, type Ref } from 'vue'
import { useRoute, useRouter } from 'vue-router';
import { useDialog } from '@/composables/dialog';
import { useLearningApi } from '@/composables/learningApi';
import { LESSON_PORTFOLIO_STATUS } from '@/config/learning';
import { isEnabled } from '@/utils/learningProgress';
import { renderMarkdown } from '@/utils/markdown';
import type { LearningPersonalMaterial, LearningPortfolio, LearningSlideDeckSpec, LearningTheme } from '@/types/learning';

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
    // Path 3 (salary challenge): studied AI material is collapsible; the chosen theme is highlighted.
    const isSalaryChallenge = computed(() => Boolean(portfolio?.value?.salary_issue_id))
    const aiMaterialHtml = computed(() => renderMarkdown(portfolio?.value?.ai_material))

    // Same 個別研修資料 as the basic stage (slide presentation + text), read-only here.
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
        const id = props.selectedTopic?.id ?? themeId.value
        if (!id) return
        try {
            const prev = await learningApi.getPreviousExperience(id as number | string)
            personalMaterial.value = prev?.personal_material ?? null
        } catch {
            personalMaterial.value = null
        }
    }
    watch(() => [props.selectedTopic?.id, isSalaryChallenge.value], () => {
        if (isSalaryChallenge.value) loadPersonalMaterial()
    }, { immediate: true })

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

    /* No margin here: LearningCollapseCard carries its own m-[20px], so a
       wrapper margin would double-inset it. The card below matches that 20px. */
    .gd-presentation-card {
        margin: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        padding: 24px;
        background: var(--background-color);
        border: 1px solid var(--formBorder);
    }
    .gd-presentation-card h3 {
        margin: 0 0 8px;
        font-size: 18px;
        line-height: 1.5;
    }
    .gd-presentation-card p {
        margin: 0;
        font-size: 13px;
        line-height: 1.8;
    }
    .gd-presentation-button {
        padding: 11px 16px;
        border: 1px solid rgb(255 255 255 / 70%);
        background: gray;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
    }

    .gd-theme {
        margin: 20px;
        padding: 20px;
        background: var(--background-color);
    }
    .gd-theme__value {
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.8;
    }
</style>
