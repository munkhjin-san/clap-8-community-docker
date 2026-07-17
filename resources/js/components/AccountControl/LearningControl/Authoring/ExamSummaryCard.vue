<template>
    <div class="lesson-preview exam-card learning-exam-summary-card">
        <div class="learning-exam-summary-card__body">
            <div class="learning-exam-summary-card__title">試験</div>
            <div
                v-if="exam"
                class="learning-exam-summary-card__meta"
            >
                <span>タイトル：{{ exam.title || '未設定' }}</span>
                <span>合格基準：{{ exam.passing_score }}%</span>
                <span>最大受験回数：{{ exam.max_attempts }}回</span>
                <span>設問数：{{ exam.questions?.length || 0 }}問</span>
            </div>
            <div
                v-else
                class="learning-exam-summary-card__meta"
            >
                試験はまだ設定されていません。
            </div>
        </div>
        <div class="learning-exam-summary-card__menu">
            <ItemMenu :items="menuItems"/>
        </div>
    </div>
</template>

<script setup lang="ts">
import ItemMenu from '@/components/Global/ItemMenu.vue'
import type { MenuList } from '@/interface/globalInterface'
import type { LearningExam } from '@/types/learning'

defineProps<{
    exam: LearningExam | null
    menuItems: MenuList[]
}>()
</script>

<style scoped>
.learning-exam-summary-card{
    padding: 20px;
}

.learning-exam-summary-card__body{
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.learning-exam-summary-card__title{
    font-size: 18px;
}

.learning-exam-summary-card__meta{
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 12px;
    color: gray;
}

.learning-exam-summary-card__menu{
    position: absolute;
    right: 10px;
    top: 10px;
}
</style>
