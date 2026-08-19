<template>
    <section class="prev-portfolio prev-portfolio--flat">
        <AiLoader
            v-if="personalMaterialLoading"
            message="個人専用研修資料をAIで生成中です。<br>この処理には数分かかる場合があります。"
        />
        <!-- The previous run (any repeater / salary challenger) shown as collapsible
             cards matching the basic-knowledge card, collapsed by default. -->
        <LearningCollapseCard label="前回のグループディスカッション内容">
                <div class="prev-box">
                    <div class="prev-box__item">
                        <p class="prev-box__label">ディスカッション前の内容</p>
                        <p class="prev-portfolio__text" :class="{ 'prev-portfolio__empty': !hasText(portfolio.content) }">
                            {{ hasText(portfolio.content) ? portfolio.content : '記録なし' }}
                        </p>
                    </div>
                    <div class="prev-box__item">
                        <p class="prev-box__label">
                            <span class="prev-portfolio__mark prev-portfolio__mark--positive"></span>ポジティブフィードバック
                        </p>
                        <p class="prev-portfolio__text" :class="{ 'prev-portfolio__empty': !hasText(portfolio.positive_feedback) }">
                            {{ hasText(portfolio.positive_feedback) ? portfolio.positive_feedback : '記録なし' }}
                        </p>
                    </div>
                    <div class="prev-box__item">
                        <p class="prev-box__label">
                            <span class="prev-portfolio__mark prev-portfolio__mark--negative"></span>ネガティヴフィードバック
                        </p>
                        <p class="prev-portfolio__text" :class="{ 'prev-portfolio__empty': !hasText(portfolio.negative_feedback) }">
                            {{ hasText(portfolio.negative_feedback) ? portfolio.negative_feedback : '記録なし' }}
                        </p>
                    </div>
                    <div class="prev-box__item">
                        <p class="prev-box__label">フィードバックから得た成長</p>
                        <p class="prev-portfolio__text" :class="{ 'prev-portfolio__empty': !hasText(portfolio.noticed) }">
                            {{ hasText(portfolio.noticed) ? portfolio.noticed : '記録なし' }}
                        </p>
                    </div>
                </div>
            </LearningCollapseCard>

            <LearningCollapseCard label="前回のポートフォリオ">
                <h3 v-if="portfolio.public_title" class="prev-box__title">{{ portfolio.public_title }}</h3>
                <p class="prev-portfolio__text prev-portfolio__text--large" :class="{ 'prev-portfolio__empty': !hasText(portfolio.public_content) }">
                    {{ hasText(portfolio.public_content) ? portfolio.public_content : '記録なし' }}
                </p>
            </LearningCollapseCard>

        <div v-if="canGeneratePersonalMaterial" class="prev-portfolio__ai-area prev-portfolio__ai-area--flat">
            <!-- (Re)generation is only for the active learner still working through 基礎知識;
                 once completed the material is read-only, so hide the generate button. -->
            <LoaderButton
                v-if="!isPersonalMaterialCompleted"
                :loading="personalMaterialLoading"
                :content="personalMaterialButtonLabel"
                @triggered="generatePersonalMaterial"
            >
                <template #icon>
                    <AiIcon class="prev-portfolio__ai-icon" :size="16" fill="#fff" />
                </template>
            </LoaderButton>

            <article v-if="personalMaterialRaw" class="prev-portfolio__card prev-portfolio__ai-card">
                <div v-if="personalMaterialPresentation" class="prev-portfolio__presentation-card">
                    <div>
                        <h3>個別研修資料</h3>
                        <p>「{{ personalMaterialPresentation.goal_title }}」を達成するために</p>
                    </div>
                    <div>
                        <button class="prev-portfolio__presentation-button" @click="presentationOpen = true">
                            研修資料を見る
                        </button>
                    </div>

                </div>
                <LearningCollapseCard
                    v-if="personalMaterialPresentation"
                    label="テキスト版を見る"
                    class="prev-portfolio__text-version"
                >
                    <div class="prev-portfolio__generated" v-html="personalMaterialHtml"></div>
                </LearningCollapseCard>
                <div v-else class="prev-portfolio__generated" v-html="personalMaterialHtml"></div>

                <!-- Path 3 (salary challenge): choose a group-discussion theme instead of the understanding questionnaire. -->
                <div v-if="canShowPersonalMaterialFeedback && isSalaryChallenge" class="prev-portfolio__understanding">
                    <div v-if="isPersonalMaterialCompleted">
                        <div class="si-box prev-portfolio__important-point">
                            <p class="prev-portfolio__important-title">
                                <strong>グループディスカッション用テーマ</strong>
                            </p>
                            <div>
                                <p class="prev-portfolio__text">{{ importantPoint }}</p>
                            </div>
                        </div>
                    </div>
                    <template v-else>
                        <p class="prev-portfolio__important-title mt-4"><strong>グループディスカッション用テーマ</strong></p>
                        <p class="prev-portfolio__theme-hint">生成された3つのテーマから1つを選び、下の欄に貼り付けてください。</p>
                        <LongInput
                            ref="importantPointRef"
                            v-model="importantPoint"
                            place-holder="グループディスカッション用テーマ"
                            rules="required"
                            name="discussionTheme"
                        />
                        <div class="prev-portfolio__button-row">
                            <LoaderButton
                                :loading="feedbackSaving"
                                content="完了"
                                @triggered="finalizeSalaryChallenge"
                            />
                        </div>
                    </template>
                </div>

                <div v-else-if="canShowPersonalMaterialFeedback" class="prev-portfolio__understanding">
                    <div v-if="isPersonalMaterialCompleted">
                        <div class="si-box prev-portfolio__important-point">
                            <p class="prev-portfolio__important-title">
                                <strong>特に重要だと理解した点</strong>
                            </p>
                            <div>
                                <p class="prev-portfolio__text">{{ importantPoint }}</p>
                            </div>
                        </div>
                        <div class="prev-portfolio__button-row">
                            <LoaderButton :content="'ポートフォリオ作成へ進む'" @triggered="goToPortfolio"/>
                        </div>
                    </div>
                    <template v-else>
                        <p class="mb-4"><strong>内容を理解しましたか？</strong></p>
                        <div
                            v-for="answer in understandOptions"
                            :key="String(answer.value)"
                            class="prev-portfolio__radio-row"
                        >
                            <input
                                :id="`personal-material-understand-${answer.value}`"
                                v-model="selectedUnderstand"
                                class="fish-eye"
                                type="radio"
                                name="personal-material-understand"
                                :value="answer.value"
                            >
                            <label
                                class="prev-portfolio__radio-label"
                                :for="`personal-material-understand-${answer.value}`"
                            >
                                {{ answer.content }}
                            </label>
                        </div>
                        <span class="form-error prev-portfolio__radio-error">{{ selectedUnderstand !== null ? '' : feedbackError }}</span>

                        <div v-if="selectedUnderstand === true" class="si-box prev-portfolio__important-point">
                            <p class="prev-portfolio__important-title mt-4">
                                <strong>特に重要だと理解した点を入力してください</strong>
                            </p>
                            <LongInput
                                ref="importantPointRef"
                                v-model="importantPoint"
                                place-holder="理解した点"
                                rules="required"
                                name="personalMaterialImportantPoint"
                            />
                        </div>

                        <div class="prev-portfolio__button-row">
                            <LoaderButton
                                :loading="feedbackSaving"
                                :content="selectedUnderstand === false ? '次へ' : '完了'"
                                @triggered="submitPersonalMaterialFeedback"
                            />
                        </div>
                    </template>
                </div>
            </article>
        </div>
        <LearningPresentationPreview
            v-if="presentationOpen && personalMaterialPresentation"
            :presentation="personalMaterialPresentation"
            :selectable="!isPersonalMaterialCompleted"
            @close="presentationOpen = false"
            @select-discussion-theme="selectDiscussionTheme"
        />
    </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDialog } from '@/composables/dialog'
import { useLearningApi } from '@/composables/learningApi'
import { renderMarkdown } from '@/utils/markdown'
import { LESSON_PORTFOLIO_STATUS } from '@/config/learning'
import AiLoader from '@/components/Global/AiLoader.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import LongInput from '@/components/Form/LongInput.vue'
import AiIcon from '@/components/Icons/AiIcon.vue'
import LearningCollapseCard from '@/components/Learning/shared/LearningCollapseCard.vue'
import LearningPresentationPreview from '@/components/Learning/shared/LearningPresentationPreview.vue'
import type {
    LearningPersonalMaterial,
    LearningPortfolio,
    LearningSlideDeckSpec,
    LearningTheme,
} from '@/types/learning'

type ValidatableRef = {
    validate?: () => Promise<{ valid: boolean }>
}

const props = defineProps<{
    themeTitle?: string | null
    portfolio: LearningPortfolio
    theme?: LearningTheme | null
    personalMaterial?: LearningPersonalMaterial | null
    canGeneratePersonalMaterial?: boolean
    isSalaryChallenge?: boolean
    refreshLessonView?: () => Promise<void>
}>()

const displayTitle = computed(() => props.themeTitle || props.portfolio.public_title || '前回のポートフォリオ')
const { ping, ask, toast } = useDialog()
const router = useRouter()
const learningApi = useLearningApi()
const currentPersonalMaterial = ref<LearningPersonalMaterial | null>(props.personalMaterial ?? null)
const personalMaterialRaw = ref(props.personalMaterial?.content ?? '')
const personalMaterialLoading = ref(false)
const personalMaterialSaved = ref(Boolean(props.personalMaterial?.id))
const personalMaterialCompleted = ref(Boolean(props.personalMaterial?.understand))
const selectedUnderstand = ref<boolean | null>(props.personalMaterial?.understand ?? null)
const importantPoint = ref(props.personalMaterial?.important_point ?? '')
const importantPointRef = ref<ValidatableRef | null>(null)
const feedbackSaving = ref(false)
const feedbackError = ref('')
const presentationOpen = ref(false)
const understandOptions = [
    { value: true, content: '理解した' },
    { value: false, content: '理解できなかった' },
]

const hasText = (value?: string | null) => Boolean(value && value.trim())
const canGeneratePersonalMaterial = computed(() => Boolean(props.canGeneratePersonalMaterial && props.theme?.id))
const personalMaterialHtml = computed(() => {
    return renderMarkdown(personalMaterialRaw.value)
})
const personalMaterialPresentation = computed<LearningSlideDeckSpec | null>(() => {
    const presentation = currentPersonalMaterial.value?.presentation_spec

    return presentation && presentation.format === 'slide_deck_v1'
        ? presentation
        : null
})
const personalMaterialButtonLabel = computed(() => {
    return hasText(personalMaterialRaw.value) ? '個人専用研修資料再生成' : '個人専用研修資料生成'
})
const canShowPersonalMaterialFeedback = computed(() => {
    return personalMaterialSaved.value && hasText(personalMaterialRaw.value) && !personalMaterialLoading.value
})
const isPersonalMaterialCompleted = computed(() => {
    return personalMaterialCompleted.value
})

watch(() => props.personalMaterial, (material) => {
    currentPersonalMaterial.value = material ?? null
    personalMaterialRaw.value = material?.content ?? ''
    personalMaterialSaved.value = Boolean(material?.id)
    personalMaterialCompleted.value = Boolean(material?.understand)
    selectedUnderstand.value = material?.understand ?? null
    importantPoint.value = material?.important_point ?? ''
}, { immediate: true })

const generatePersonalMaterial = async() => {
    if (!props.theme?.id || !props.canGeneratePersonalMaterial) return

    presentationOpen.value = false
    feedbackError.value = ''
    personalMaterialLoading.value = true
    let generatedPresentationAvailable = false

    try {
        const material = await learningApi.generatePersonalMaterial(props.theme.id)
        currentPersonalMaterial.value = material
        personalMaterialRaw.value = material.content ?? ''
        personalMaterialSaved.value = true
        personalMaterialCompleted.value = Boolean(material.understand)
        selectedUnderstand.value = material.understand
        importantPoint.value = material.important_point ?? ''
        generatedPresentationAvailable = Boolean(personalMaterialPresentation.value)
        toast('個人専用研修資料の生成が完了しました。')

        try {
            await props.refreshLessonView?.()
        } catch {
            ping('資料は作成されましたが、画面の更新に失敗しました。ページを再読み込みしてください。')
        }
    } catch {
        ping('個人専用研修資料の作成に失敗しました。しばらくしてから再度お試しください。')
    } finally {
        personalMaterialLoading.value = false
    }

    if (generatedPresentationAvailable) {
        presentationOpen.value = true
    }
}

const selectDiscussionTheme = (content: string) => {
    importantPoint.value = content
    presentationOpen.value = false
}

const submitPersonalMaterialFeedback = async() => {
    if (!props.theme?.id || feedbackSaving.value || personalMaterialLoading.value) return

    if (selectedUnderstand.value === null) {
        feedbackError.value = '必須です。'
        return
    }

    if (selectedUnderstand.value === false) {
        router.push({ name: 'personal_material_more', params: { lessonThemeId: props.theme.id } })
        return
    }

    if (selectedUnderstand.value === true) {
        const result = importantPointRef.value?.validate
            ? await importantPointRef.value.validate()
            : { valid: true }

        if (!result.valid) return
    }

    feedbackError.value = ''
    feedbackSaving.value = true

    try {
        const saved = await learningApi.savePersonalMaterialFeedback(props.theme.id, {
            understand: selectedUnderstand.value,
            important_point: selectedUnderstand.value ? importantPoint.value : null,
        })

        selectedUnderstand.value = saved.understand
        personalMaterialCompleted.value = Boolean(saved.understand)
        importantPoint.value = saved.important_point ?? ''

        if (saved.understand) {
            await props.refreshLessonView?.()
            router.push({ name: 'summary', params: { lessonThemeId: props.theme.id } })
        }
    } finally {
        feedbackSaving.value = false
    }
}

// Path 3: paste a chosen group-discussion theme, finalize 知識研修, then jump straight
// to the completion screen (no /summary route). The theme is stored in important_point.
const finalizeSalaryChallenge = async() => {
    if (!props.theme?.id || feedbackSaving.value || personalMaterialLoading.value) return

    const result = importantPointRef.value?.validate
        ? await importantPointRef.value.validate()
        : { valid: true }
    if (!result.valid) return

    feedbackSaving.value = true
    try {
        // Save silently — a toast here would reset the shared dialog and make the
        // completion notice below flash away instead of waiting for the user.
        await learningApi.savePersonalMaterialFeedback(props.theme.id, {
            understand: true,
            important_point: importantPoint.value,
        }, { silent: true })
        await learningApi.savePortfolio({
            theme_id: props.theme.id,
            params: { status: LESSON_PORTFOLIO_STATUS.DISCUSSION_DRAFT_READY },
        })

        // Stop on the completion notice; jump to the theme top on OK (no /summary route).
        const answer = await ask('知識研修完了しました。\nお疲れ様でした。', { answers: [{ label: 'OK', value: true }] })
        personalMaterialCompleted.value = true
        await props.refreshLessonView?.()
        if (answer?.value) {
            router.push({ name: 'top', params: { lessonThemeId: props.theme.id } })
        }
    } finally {
        feedbackSaving.value = false
    }
}

// Proceed from an already-completed understanding to the portfolio (discussion-prep) step.
const goToPortfolio = () => {
    if (!props.theme?.id) return
    router.push({ name: 'summary', params: { lessonThemeId: props.theme.id } })
}

const hasFinalPortfolio = computed(() => hasText(props.portfolio.public_content))

const updatedLabel = computed(() => {
    const value = props.portfolio.updated_at ?? props.portfolio.created_at
    if (!value) return ''

    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return ''

    return `更新日 ${date.toLocaleDateString('ja-JP')}`
})

const feedbackCards = computed(() => {
    return [
        {
            key: 'positive',
            title: 'ポジティブフィードバック',
            content: props.portfolio.positive_feedback,
            tone: 'positive',
        },
        {
            key: 'negative',
            title: 'ネガティヴフィードバック',
            content: props.portfolio.negative_feedback,
            tone: 'negative',
        },
    ]
})

const detailBlocks = computed(() => {
    return [
        {
            key: 'draft',
            title: 'ディスカッション前の内容',
            content: props.portfolio.content,
        },
        {
            key: 'episode',
            title: '前回のエピソード',
            content: props.portfolio.episode,
        },
        {
            key: 'knowledge',
            title: '前回の基礎知識メモ',
            content: props.portfolio.basic_knowledge,
        },
    ].filter(block => hasText(block.content))
})
</script>

<style scoped>
.prev-portfolio {
    width: calc(100% - 40px);
    padding: 20px;
    background: var(--background-color);
    border: 1px solid var(--secondary-color);
    color: var(--primary-color);
}
/* Salary-challenge layout: the collapsible cards carry their own margin/border,
   so the section itself is a plain full-width container. */
.prev-portfolio--flat {
    width: 100%;
    padding: 0;
    border: 0;
    background: transparent;
}

.prev-box {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.prev-box__item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.prev-box__label {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0;
    font-size: 13px;
    color: var(--secondary-color);
}

.prev-box__title {
    margin: 0 0 14px;
    font-size: 18px;
    line-height: 1.6;
}

.prev-portfolio__ai-area--flat {
    margin: 20px;
}

.prev-portfolio__header {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 28px;
    margin-bottom: 22px;
}

.prev-portfolio__badge {
    display: inline-flex;
    padding: 5px 12px;
    background: var(--primary-color);
    color: var(--background-color);
    font-size: 12px;
    font-weight: 700;
    line-height: 1.2;
}

.prev-portfolio__date,
.prev-portfolio__eyebrow,
.prev-portfolio__lead,
.prev-portfolio__footer {
    color: var(--secondary-color);
}

.prev-portfolio__date {
    margin: 12px 0 0;
    font-size: 12px;
    line-height: 1.5;
}

.prev-portfolio__heading {
    min-width: 0;
}

.prev-portfolio__eyebrow {
    margin: 0 0 4px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
}

.prev-portfolio__title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    line-height: 1.45;
}

.prev-portfolio__lead {
    margin: 10px 0 0;
    font-size: 13px;
    line-height: 1.8;
}

.prev-portfolio__feedback-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.prev-portfolio__card {
    min-width: 0;
    border: 1px solid var(--secondary-color);
}

.prev-portfolio__feedback-card {
    padding: 20px 22px;
}

.prev-portfolio__feedback-card:only-child {
    grid-column: 1 / -1;
}

.prev-portfolio__portfolio-card {
    padding: 24px 26px;
}

.prev-portfolio__ai-card {
    margin-top: 18px;
}

.prev-portfolio__ai-area {
    margin-top: 22px;
    background: var(--background-color);
    padding: 20px;
    border: solid thin var(--formBorder);
}

.prev-portfolio__ai-area :deep(.l-button) {
    margin: 0;
}

.prev-portfolio__ai-icon {
    flex: 0 0 auto;
    margin-right: 8px;
}

.prev-portfolio__section-title,
.prev-portfolio__card-title,
.prev-portfolio__detail-title {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
}

.prev-portfolio__section-title,
.prev-portfolio__detail-title {
    color: var(--secondary-color);
}

.prev-portfolio__section-title {
    letter-spacing: 0.06em;
}

.prev-portfolio__card-title {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 12px;
}

.prev-portfolio__mark {
    width: 8px;
    height: 8px;
    flex: 0 0 8px;
    background: var(--primary-color);
}

.prev-portfolio__mark--positive {
    background: #248a3d;
}

.prev-portfolio__mark--negative {
    background: #b42318;
}

.prev-portfolio__mark--notice {
    background: #1f6feb;
}

.prev-portfolio__portfolio-title {
    margin: 14px 0 18px;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.6;
}

.prev-portfolio__text {
    margin: 0;
    font-size: 14px;
    line-height: 2;
    white-space: pre-wrap;
    word-break: break-word;
}

.prev-portfolio__generated {
    margin-top: 14px;
    font-size: 14px;
    line-height: 2;
    white-space: normal;
    word-break: break-word;
}

.prev-portfolio__generated :deep(p),
.prev-portfolio__generated :deep(ul),
.prev-portfolio__generated :deep(ol) {
    margin: 0 0 12px;
}

.prev-portfolio__generated :deep(h1),
.prev-portfolio__generated :deep(h2),
.prev-portfolio__generated :deep(h3) {
    margin: 18px 0 10px;
    font-weight: 700;
    line-height: 1.6;
}

.prev-portfolio__presentation-card {
    gap: 16px 22px;
    margin-top: 16px;
    padding: 24px;
    overflow: hidden;
    background: var(--background-color);
    border: 1px solid var(--formBorder);
}

.prev-portfolio__presentation-card span {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.14em;
    opacity: 0.78;
}

.prev-portfolio__presentation-card h3 {
    margin: 8px 0;
    font-size: 21px;
    line-height: 1.5;
}

.prev-portfolio__presentation-card p {
    margin: 0;
    font-size: 13px;
    line-height: 1.8;
    opacity: 0.86;
}

.prev-portfolio__presentation-card button,
.prev-portfolio__presentation-card a {
    align-self: center;
    padding: 11px 16px;
    border: 1px solid rgb(255 255 255 / 70%);
    background: gray;
    color: #ffffff;
    font-size: 12px;
    font-weight: 800;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    margin-top: 20px;
}

.prev-portfolio__presentation-card a {
    margin-left: 20px;
}

.prev-portfolio__text-version {
    margin: 16px 0 0;
}

.prev-portfolio__understanding {
    margin-top: 24px;
    padding-top: 22px;
    border-top: 1px solid var(--primary-color);
}

.prev-portfolio__radio-row {
    display: flex;
    align-items: center;
    padding: 5px 0;
}

.prev-portfolio__radio-label {
    margin-left: 10px;
    cursor: pointer;
}

.prev-portfolio__radio-error {
    color: tomato;
    font-size: 11px;
}

.prev-portfolio__important-point {
    margin: 0;
}

.prev-portfolio__important-title {
    margin-bottom: 20px;
}

.prev-portfolio__theme-hint {
    margin: -8px 0 16px;
    font-size: 12px;
    line-height: 1.7;
    color: var(--secondary-color);
}

.prev-portfolio__button-row {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 25px;
}

.prev-portfolio__empty {
    color: var(--secondary-color);
}

.prev-portfolio__text--large {
    margin-top: 12px;
    font-size: 15px;
    line-height: 2.05;
}

.prev-portfolio__details {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-top: 14px;
}

.prev-portfolio__detail {
    min-width: 0;
    padding: 20px 22px;
    border: 1px solid var(--secondary-color);
}

.prev-portfolio__detail-title {
    margin-bottom: 10px;
}

.prev-portfolio__footer {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 18px;
    font-size: 12px;
    line-height: 1.6;
}

.prev-portfolio__footer-line {
    width: 28px;
    height: 1px;
    background: currentColor;
    flex: 0 0 28px;
}

@media screen and (max-width: 720px) {
    .prev-portfolio {
        width: calc(100% - 32px);
        margin: 18px 16px 28px;
        padding: 18px;
    }

    .prev-portfolio__header,
    .prev-portfolio__feedback-grid,
    .prev-portfolio__details {
        grid-template-columns: 1fr;
    }

    .prev-portfolio__presentation-card {
        grid-template-columns: 1fr;
    }

    .prev-portfolio__presentation-card a {
        grid-column: 1;
    }

    .prev-portfolio__header {
        gap: 14px;
        margin-bottom: 18px;
    }

    .prev-portfolio__feedback-card,
    .prev-portfolio__portfolio-card,
    .prev-portfolio__detail {
        padding: 18px;
    }

    .prev-portfolio__footer {
        align-items: flex-start;
    }
}
</style>
