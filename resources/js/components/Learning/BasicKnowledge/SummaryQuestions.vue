<template>
    <div class="flex flex-col gap-5">
        <p><strong>理解度チェック</strong></p>
        <div v-for="summary in material?.summaries" :key="summary.id">
            <p>{{ summary.title }}</p>
            <div class="ml-5 mt-5" v-for="question in summary.questions" :key="question.id">
                <QuestionRadio 
                    :question-id="question.id"
                    :question="question.question"
                    :answers="decidedAnswers"
                    :answer="question.answer?.answer_val"
                    :show-error="validationErrors?.[question.id]"
                    @setValue="(val: number) => setAnswers(val, summary.id, question)"
                    @validationError="(val: boolean) => handleValidationError(question.id, val)"
                />
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { decidedAnswers } from '@/utils/tools';
import QuestionRadio from '../Portfolio/QuestionRadio.vue';
import { useAuthUserStore } from '@/store/auth';
defineProps(['material'])
interface SummaryAnswers {
    lesson_summary_id: number,
    lesson_summary_question_id: number,
    answer_val: number
}
const summaryAnswers = defineModel<SummaryAnswers[]>('answers')
const auth = useAuthUserStore()
const validationErrors = defineModel<Record<number, boolean>>('errors');
const setAnswers = (val: number, summaryId: number, question: any) => {
    const data = {
        id: question.answer?.id,
        lesson_summary_id: summaryId,
        lesson_summary_question_id: question.id,
        answer_val: val,
        user_id: auth.id
    }
    if (!summaryAnswers.value) {
        summaryAnswers.value = []
    }
    const existingAnswer = summaryAnswers.value.find(answer => 
        answer.lesson_summary_id === summaryId && answer.lesson_summary_question_id === question.id
    )
    if (existingAnswer) {
        existingAnswer.answer_val = val
    } else {
        summaryAnswers.value.push(data)
    }
    if (!validationErrors.value) {
        validationErrors.value = {};
    }

    validationErrors.value[question.id] = false;
}
const handleValidationError = (questionId: number, hasError: boolean) => {
    if (!validationErrors.value) {
        validationErrors.value = {};
    }
    validationErrors.value[questionId] = hasError;
};
</script>