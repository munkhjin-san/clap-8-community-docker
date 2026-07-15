<template>
    <section class="prev-portfolio">
        <header class="prev-portfolio__header">
            <div>
                <div class="mb-4">前回のポートフォリオ</div>
                <p v-if="updatedLabel" class="prev-portfolio__date">{{ updatedLabel }}</p>
            </div>
        </header>

        <div v-if="feedbackCards.length" class="prev-portfolio__feedback-grid">
            <article
                v-for="card in feedbackCards"
                :key="card.key"
                class="prev-portfolio__card prev-portfolio__feedback-card"
            >
                <p class="prev-portfolio__card-title">
                    <span class="prev-portfolio__mark" :class="`prev-portfolio__mark--${card.tone}`"></span>
                    {{ card.title }}
                </p>
                <p
                    class="prev-portfolio__text"
                    :class="{ 'prev-portfolio__empty': !hasText(card.content) }"
                >
                    {{ hasText(card.content) ? card.content : '記録なし' }}
                </p>
            </article>
        </div>

        <article v-if="hasFinalPortfolio" class="prev-portfolio__card prev-portfolio__portfolio-card">
            <p class="prev-portfolio__section-title">完成したポートフォリオ</p>
            <h3 v-if="portfolio.public_title" class="prev-portfolio__portfolio-title">
                {{ portfolio.public_title }}
            </h3>
            <p class="prev-portfolio__text prev-portfolio__text--large">{{ portfolio.public_content }}</p>
        </article>

        <div v-if="detailBlocks.length" class="prev-portfolio__details">
            <article
                v-for="block in detailBlocks"
                :key="block.key"
                class="prev-portfolio__detail"
            >
                <p class="prev-portfolio__detail-title">{{ block.title }}</p>
                <p class="prev-portfolio__text">{{ block.content }}</p>
            </article>
        </div>

        <div v-if="canGeneratePersonalMaterial" class="prev-portfolio__ai-area">
            <LoaderButton
                :loading="personalMaterialLoading"
                :content="personalMaterialButtonLabel"
                @triggered="generatePersonalMaterial"
            >
                <template #icon>
                    <AiIcon class="prev-portfolio__ai-icon" :size="16" fill="#fff" />
                </template>
            </LoaderButton>

            <article v-if="personalMaterialRaw" class="prev-portfolio__card prev-portfolio__ai-card">
                <p class="prev-portfolio__section-title">個人専用研修資料</p>
                <div class="prev-portfolio__generated" v-html="personalMaterialHtml"></div>

                <div v-if="canShowPersonalMaterialFeedback" class="prev-portfolio__understanding">
                    <div v-if="isPersonalMaterialCompleted">
                        <div class="si-box prev-portfolio__important-point">
                            <p class="prev-portfolio__important-title">
                                <strong>特に重要だと理解した点</strong>
                            </p>
                            <div>
                                <p>{{ importantPoint }}</p>
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
    </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDialog } from '@/composables/dialog'
import { useSSE } from '@/composables/sse'
import { useLearningApi } from '@/composables/learningApi'
import { renderMarkdown } from '@/utils/markdown'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import LongInput from '@/components/Form/LongInput.vue'
import AiIcon from '@/components/Icons/AiIcon.vue'
import type { LearningPersonalMaterial, LearningPortfolio, LearningTheme } from '@/types/learning'

type ValidatableRef = {
    validate?: () => Promise<{ valid: boolean }>
}

const props = defineProps<{
    themeTitle?: string | null
    portfolio: LearningPortfolio
    theme?: LearningTheme | null
    personalMaterial?: LearningPersonalMaterial | null
    canGeneratePersonalMaterial?: boolean
    refreshLessonView?: () => Promise<void>
}>()

const displayTitle = computed(() => props.themeTitle || props.portfolio.public_title || '前回のポートフォリオ')
const { ping } = useDialog()
const router = useRouter()
const learningApi = useLearningApi()
const { on, start, stop } = useSSE({ autoReconnect: false })
const personalMaterialRaw = ref(props.personalMaterial?.content ?? '')
const personalMaterialLoading = ref(false)
const personalMaterialSaved = ref(Boolean(props.personalMaterial?.id))
const personalMaterialCompleted = ref(Boolean(props.personalMaterial?.understand))
const selectedUnderstand = ref<boolean | null>(props.personalMaterial?.understand ?? null)
const importantPoint = ref(props.personalMaterial?.important_point ?? '')
const importantPointRef = ref<ValidatableRef | null>(null)
const feedbackSaving = ref(false)
const feedbackError = ref('')
const understandOptions = [
    { value: true, content: '理解した' },
    { value: false, content: '理解できなかった' },
]

const hasText = (value?: string | null) => Boolean(value && value.trim())
const canGeneratePersonalMaterial = computed(() => Boolean(props.canGeneratePersonalMaterial && props.theme?.id))
const personalMaterialHtml = computed(() => {
    return renderMarkdown(personalMaterialRaw.value)
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
    personalMaterialRaw.value = material?.content ?? ''
    personalMaterialSaved.value = Boolean(material?.id)
    personalMaterialCompleted.value = Boolean(material?.understand)
    selectedUnderstand.value = material?.understand ?? null
    importantPoint.value = material?.important_point ?? ''
}, { immediate: true })

on('update', (payload) => {
    try {
        const parsed = JSON.parse(payload)
        if (parsed?.event === 'response.output_text.delta') {
            personalMaterialRaw.value += parsed.response?.delta ?? parsed.delta ?? ''
        }
    } catch {}
})
on('error', () => {
    personalMaterialLoading.value = false
    ping('個人専用研修資料の作成に失敗しました。しばらくしてから再度お試しください。')
})
on('complete', () => {
    personalMaterialLoading.value = false
    personalMaterialSaved.value = true
})

const generatePersonalMaterial = () => {
    if (!props.theme?.id || !props.canGeneratePersonalMaterial) return

    stop()
    personalMaterialRaw.value = ''
    personalMaterialSaved.value = false
    personalMaterialCompleted.value = false
    selectedUnderstand.value = null
    importantPoint.value = ''
    feedbackError.value = ''
    personalMaterialLoading.value = true
    start(`/lesson_theme/${props.theme.id}/personal_materials/portfolio_recurring_trainee/stream`)
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
            title: '改善・懸念のフィードバック',
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
    background: var(--bg3);
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
    padding: 24px 26px;
}

.prev-portfolio__ai-area {
    margin-top: 22px;
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
