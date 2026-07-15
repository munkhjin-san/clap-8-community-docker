<template>
    <div class="records-wrapper scrollable">
        <div class="records-table">
            <div class="records-header">
                <div class="header-row">
                    <div class="header-cell">氏名</div>
                    <div class="header-cell">ステータス</div>
                    <div class="header-cell">ケーススタディ答え</div>
                    <div class="header-cell">基礎知識理解不能</div>
                    <div class="header-cell">理解できない理由</div>
                    <div class="header-cell">試験受験回</div>
                    <div class="header-cell">試験スコア</div>
                    <div class="header-cell">試験結果</div>
                </div>
            </div>

            <div class="records-body">
                <div
                    v-for="participant in participants"
                    :key="participant.user?.id"
                    class="body-row"
                >
                    <div class="body-cell border-none">{{ participant.user?.name }}</div>
                    <div class="body-cell border-none">
                        <div
                            v-for="status in getCaseStudyStatusLabels(participant)"
                            :key="status"
                            class="case-study-status-row"
                        >
                            <div>{{ status }}</div>
                        </div>
                    </div>
                    <div class="body-cell border-none case-study-cell">
                        <TraineePreviewCell
                            :menu-name="`pt_content${participant.user.id}`"
                            :record-id="participant.user.id"
                            align="left"
                        >
                            <template #summary>
                                <div
                                    v-for="(answer, index) in participant.answers ?? []"
                                    :key="index"
                                >
                                    <p>{{ answer.answer }}</p>
                                </div>
                            </template>
                            <div
                                v-for="(answer, index) in participant.answers ?? []"
                                :key="index"
                            >
                                <p>{{ answer.answer }}</p>
                                <div
                                    v-if="index !== (participant.answers ?? []).length - 1"
                                    class="post-separetor mt-[30px]"
                                ></div>
                            </div>
                        </TraineePreviewCell>
                    </div>
                    <div class="body-cell border-none case-study-cell">
                        <TraineePreviewCell
                            :menu-name="`pt_dt_und${participant.user.id}`"
                            :record-id="participant.user.id"
                        >
                            <template #summary>{{ participant.cant_understand }}</template>
                            {{ participant.cant_understand }}
                        </TraineePreviewCell>
                    </div>
                    <div class="body-cell border-none case-study-cell">
                        <TraineePreviewCell
                            :menu-name="`reason_dt_und${participant.user.id}`"
                            :record-id="participant.user.id"
                        >
                            <template #summary>{{ participant.reason_dnt_und }}</template>
                            {{ participant.reason_dnt_und }}
                        </TraineePreviewCell>
                    </div>
                    <div class="body-cell border-none">
                        <span v-if="getCaseStudyAttemptCount(participant)">{{ getCaseStudyAttemptCount(participant) }}</span>
                    </div>
                    <div class="body-cell border-none">
                        <span v-if="getCaseStudyLatestScore(participant) !== null">{{ getCaseStudyLatestScore(participant) }}%</span>
                    </div>
                    <div class="body-cell border-none">
                        <span
                            v-if="getCaseStudyLatestExamStatus(participant)"
                            :class="[getCaseStudyLatestExamStatus(participant) === 'passed' ? 'passed' : 'failed']"
                        >
                            {{ getCaseStudyExamStatusLabel(getCaseStudyLatestExamStatus(participant)) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { LearningParticipantProgress } from '@/types/learning'
import {
    getCaseStudyAttemptCount,
    getCaseStudyExamStatusLabel,
    getCaseStudyLatestExamStatus,
    getCaseStudyLatestScore,
    getCaseStudyStatusLabels,
} from '@/utils/learningCaseStudyParticipants'
import TraineePreviewCell from './TraineePreviewCell.vue'

defineProps<{
    participants: LearningParticipantProgress[]
}>()
</script>

<style scoped>
.body-cell{
    line-height: 2;
}

.case-study-cell{
    position: relative;
    text-align: left;
}

.case-study-status-row{
    display: flex;
    gap: 15px;
    justify-content: space-between;
    padding: 5px 0;
    white-space: nowrap;
}

.passed{
    color: rgb(34, 197, 94);
}

.failed{
    color: tomato;
}
</style>
