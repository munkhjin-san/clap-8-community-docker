<template>
    <div class="flex flex-col gap-5">
        <p><strong>チェック項目</strong></p>
        <div v-for="summary in material?.summaries" :key="summary.id">
            <p>{{ summary.title }}</p>
            <div class="ml-5 mt-2" v-for="question in summary.questions" :key="question.id">
                <QuestionRadio 
                    :question-id="question.id"
                    :question="question.question"
                    :answers="decidedAnswers"
                    :answer="question.answer?.answer_val"
                    @setValue="val => setAnswers(val, summary.id, question)"
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
const summaryAnswers = defineModel<SummaryAnswers[]>('modelValue')
const auth = useAuthUserStore()
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
}
</script>