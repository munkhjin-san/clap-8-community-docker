<template>
    <button
        type="button"
        :class="[
            'learning-theme-card',
            {
                'learning-theme-card--inactive': !active,
                'learning-theme-card--complete': themeCompleted,
            },
        ]"
        @click="emit('select', theme)"
    >
        <span class="learning-theme-card__body">
            <span class="learning-theme-card__title-row">
                <span v-if="themeCompleted" class="learning-theme-card__complete-chip">完了</span>
                <span class="learning-theme-card__title">{{ theme.title }}</span>
            </span>

            <span class="learning-theme-card__steps">
                <span
                    v-for="step in stageItems"
                    :key="step.label"
                    :class="[
                        'learning-theme-card__step',
                        {
                            'learning-theme-card__step--complete': step.tone === 'complete',
                            'learning-theme-card__step--warning': step.tone === 'warning',
                        },
                    ]"
                >
                    <span class="learning-theme-card__step-mark" aria-hidden="true"></span>
                    <span>{{ step.label }}</span>
                </span>
            </span>

            <span v-if="themeCategories.length" class="learning-theme-card__categories">
                <span
                    v-for="category in themeCategories"
                    :key="category.id"
                    class="learning-theme-card__category"
                >
                    {{ category.name }}
                </span>
            </span>
        </span>
    </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { LearningTheme } from '@/types/learning'
import {
    areCaseStudiesCompletedByAnswer,
    areMaterialsCompletedByAnswer,
    getBasicLearningStatus,
    getCaseStudyMaterials,
    isEnabled,
    isPortfolioComplete,
} from '@/utils/learningProgress'

type StatusTone = 'complete' | 'warning'

interface StageItem {
    label: string
    tone?: StatusTone
}

const props = defineProps<{
    theme: LearningTheme
}>()

const emit = defineEmits<{
    select: [theme: LearningTheme]
}>()

const active = computed(() => isEnabled(props.theme.active))
const materials = computed(() => props.theme.materials ?? [])
const fallbackBasicStatus = computed(() => getBasicLearningStatus(materials.value))
const basicTone = computed<StatusTone | undefined>(() => {
    if (props.theme.progress) {
        if (props.theme.progress.basic.completed) return 'complete'
        if (props.theme.progress.basic.not_understood) return 'warning'
        return undefined
    }

    if (!fallbackBasicStatus.value) return undefined
    return fallbackBasicStatus.value === 'completed' ? 'complete' : 'warning'
})
// A theme can flag has_case_study without actually having any ケーススタディ
// materials. Only surface the step when case-study content exists, otherwise
// it would sit permanently grey with nothing to complete.
const caseStudyHasContent = computed(() => {
    return props.theme.progress
        ? props.theme.progress.case_study.total > 0
        : getCaseStudyMaterials(materials.value).length > 0
})
const caseStudyCompleted = computed(() => {
    return props.theme.progress
        ? props.theme.progress.case_study.total > 0 && props.theme.progress.case_study.completed
        : areCaseStudiesCompletedByAnswer(materials.value)
})
const surveyCompleted = computed(() => {
    return props.theme.progress?.survey.completed ?? Boolean(props.theme.survey_completed)
})
const portfolioProgress = computed(() => {
    return props.theme.progress?.portfolio ?? {
        required: isEnabled(props.theme.portfolio),
        status: Number(props.theme.lesson_portfolio?.status ?? 0),
        draft_ready: Number(props.theme.lesson_portfolio?.status ?? 0) >= 1,
        discussion_completed: Number(props.theme.lesson_portfolio?.status ?? 0) >= 2,
        completed: Number(props.theme.lesson_portfolio?.status ?? 0) >= 3,
    }
})
const themeCompleted = computed(() => {
    return props.theme.progress?.theme_completed
        ?? (
            isPortfolioComplete(props.theme.lesson_portfolio)
            || (areMaterialsCompletedByAnswer(materials.value) && !isEnabled(props.theme.portfolio))
        )
})
const themeCategories = computed(() => props.theme.categories ?? [])
const stageItems = computed<StageItem[]>(() => {
    if (isEnabled(props.theme.portfolio)) {
        return [
            {
                // 知識研修 must reflect real knowledge completion (basicTone),
                // not merely that a portfolio draft exists (draft_ready). A salary
                // challenger can reach the portfolio without finishing the basic
                // sections, so draft_ready would falsely light this green while
                // the 完了 badge (which requires basic_completed) stays hidden.
                label: '知識研修',
                tone: basicTone.value,
            },
            {
                label: 'ディスカッション',
                tone: portfolioProgress.value.discussion_completed ? 'complete' : undefined,
            },
            {
                label: 'ポートフォリオ',
                tone: portfolioProgress.value.completed ? 'complete' : undefined,
            },
        ]
    }

    const steps: StageItem[] = [
        {
            label: '知識研修',
            tone: basicTone.value,
        },
    ]

    if (isEnabled(props.theme.has_case_study) && caseStudyHasContent.value) {
        steps.push({
            label: 'ケーススタディ',
            tone: caseStudyCompleted.value ? 'complete' : undefined,
        })
    }

    if (props.theme.custom_form_id) {
        steps.push({
            label: 'チェックリスト',
            tone: surveyCompleted.value ? 'complete' : undefined,
        })
    }

    return steps
})
</script>

<style scoped>
.learning-theme-card {
    align-items: stretch;
    background: color-mix(in srgb, var(--background-color) 92%, var(--bg2));
    border: 0;
    box-sizing: border-box !important;
    color: var(--primary-color);
    cursor: pointer;
    display: flex;
    min-height: 142px;
    min-width: 0;
    overflow: hidden;
    padding: 0;
    position: relative;
    text-align: left;
    transform: translateY(0) scale(1);
    transform-origin: center;
    transition:
        background-color 0.18s ease,
        box-shadow 0.18s ease,
        transform 0.18s ease;
    width: 100%;
    border-radius: 5px;
    will-change: transform;
}

.learning-theme-card * {
    box-sizing: border-box !important;
}

.learning-theme-card:hover,
.learning-theme-card:focus-visible {
    background: var(--background-color);
    box-shadow: 0 6px 16px rgb(0 0 0 / 8%);
    outline: 0;
    transform: translateY(-1px);
    z-index: 2;
}

.learning-theme-card:active {
    box-shadow: 0 3px 10px rgb(0 0 0 / 7%);
    transform: translateY(0) scale(1.001);
}

.learning-theme-card--inactive {
    background: var(--inactive-background);
    cursor: not-allowed;
    opacity: 0.7;
}

.learning-theme-card--inactive:hover,
.learning-theme-card--inactive:focus-visible {
    background: var(--inactive-background);
    box-shadow: none;
    transform: none;
    z-index: auto;
}

.learning-theme-card__body {
    display: grid;
    flex: 1 1 auto;
    gap: 14px;
    min-width: 0;
    padding: 18px 18px 16px;
}

.learning-theme-card__title-row {
    align-items: center;
    display: flex;
    gap: 8px;
    min-width: 0;
}

.learning-theme-card__complete-chip {
    background: #4f8a4b;
    border-radius: 999px;
    color: #fff;
    flex: 0 0 auto;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.4;
    padding: 2px 8px;
}

.learning-theme-card__title {
    display: inline;
    font-size: 16px;
    line-height: 1.5;
    min-width: 0;
    word-break: break-word;
}

.learning-theme-card__steps {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 14px;
    min-width: 0;
    padding-top: 0;
}

.learning-theme-card__step {
    align-items: center;
    color: #777;
    display: inline-flex;
    font-size: 13px;
    gap: 6px;
    line-height: 1.4;
    min-width: 0;
}

.learning-theme-card__step-mark {
    background: #c9c9c9;
    border-radius: 50%;
    display: inline-block;
    flex: 0 0 8px;
    height: 8px;
    width: 8px;
}

.learning-theme-card__step--complete .learning-theme-card__step-mark {
    background: #3f8f3b;
}

.learning-theme-card__step--warning .learning-theme-card__step-mark {
    background: #f4b63f;
}

.learning-theme-card__categories {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-width: 0;
}

.learning-theme-card__category {
    align-items: center;
    background: color-mix(in srgb, var(--bg3) 70%, transparent);
    border-radius: 999px;
    color: #777;
    display: inline-flex;
    font-size: 11px;
    font-weight: 700;
    height: 21px;
    line-height: 1;
    padding: 0 8px;
}

@media screen and (max-width: 959px) {
    .learning-theme-card {
        min-height: 0;
    }

    .learning-theme-card__title {
        min-height: 0;
    }
}

@media screen and (max-width: 520px) {
    .learning-theme-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
