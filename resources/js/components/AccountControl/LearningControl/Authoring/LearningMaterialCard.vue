<template>
    <div class="lesson-preview learning-material-card">
        <div class="learning-material-card__content">
            <div class="learning-material-card__heading">
                <div v-html="lesson.title"></div>
                <div class="learning-material-card__chips">
                    <span class="lesson-chip learning-material-card__chip learning-material-card__chip--primary">
                        {{ priorityLabel }}
                    </span>
                    <span
                        v-if="lesson.material_type"
                        class="lesson-chip learning-material-card__chip"
                    >
                        {{ lesson.material_type }}
                    </span>
                    <span
                        v-if="requestLabel"
                        class="lesson-chip learning-material-card__chip learning-material-card__chip--request"
                    >
                        {{ requestLabel }}
                    </span>
                    <span
                        v-if="lesson.has_question && lesson.material_type === 'ケーススタディ'"
                        class="lesson-chip learning-material-card__chip learning-material-card__chip--case-question"
                    >
                        QA ケース
                    </span>
                </div>
            </div>
            <div class="learning-material-card__meta">
                <span v-if="lesson.material_type === 'ケーススタディ'">
                    AI設定：{{ hasAiConfig ? '設定済み' : '未設定' }}
                </span>
                <div
                    v-if="lesson.priority === sectionPriority && lesson.summaries?.length"
                    class="learning-material-card__summary-title"
                >
                    理解チェック
                </div>
                <template v-if="lesson.priority === sectionPriority">
                    <div
                        v-for="summary in lesson.summaries ?? []"
                        :key="summary.id"
                        class="learning-material-card__summary-row"
                    >
                        <span>{{ summary.title }}</span>
                        <ItemMenu :items="summaryMenuItems(summary)"/>
                    </div>
                </template>
            </div>
            <Transition name="modalFade">
                <div
                    v-if="loading"
                    class="cal-month-loader learning-material-card__loader"
                >
                    <div id="loaderMini">
                        <div class="spinner-mini learning-material-card__spinner"></div>
                    </div>
                </div>
            </Transition>
        </div>
        <div class="learning-material-card__menu">
            <ItemMenu :items="menuItems"/>
        </div>
    </div>
</template>

<script setup lang="ts">
import ItemMenu from '@/components/Global/ItemMenu.vue'
import { LESSON_MATERIAL_PRIORITY } from '@/config/learning'
import type { MenuList } from '@/interface/globalInterface'
import type { LearningMaterial, LearningSummary } from '@/types/learning'

defineProps<{
    lesson: LearningMaterial
    menuItems: MenuList[]
    priorityLabel: string
    requestLabel: string
    loading: boolean
    summaryMenuItems: (summary: LearningSummary) => MenuList[]
    hasAiConfig: boolean
}>()

const sectionPriority = LESSON_MATERIAL_PRIORITY.SECTION
</script>

<style scoped>
.learning-material-card__content{
    height: calc(100% - 40px);
    margin: 20px 40px 20px 20px;
    position: relative;
}

.learning-material-card__heading{
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 8px;
}

.learning-material-card__chips{
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 11px;
}

.learning-material-card__chip{
    background: var(--bg2);
    color: var(--text-color);
    padding: 2px 8px;
}

.learning-material-card__chip--primary{
    background: var(--primary-color);
    color: #fff;
}

.learning-material-card__chip--request{
    background: #ffe8cc;
    color: #b15c00;
}

.learning-material-card__chip--case-question{
    background: #dbeafe;
    color: #1d4ed8;
}

.learning-material-card__meta{
    display: flex;
    flex-direction: column;
    font-size: 12px;
    color: gray;
}

.learning-material-card__summary-title{
    margin-top: 10px;
    color: var(--primary-color);
    font-size: 14px;
}

.learning-material-card__summary-row{
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.learning-material-card__loader{
    top: 50%;
}

.learning-material-card__spinner{
    border-color: transparent rgb(134 134 134) rgb(134 134 134);
}

.learning-material-card__menu{
    position: absolute;
    right: 10px;
    top: 10px;
}
</style>
