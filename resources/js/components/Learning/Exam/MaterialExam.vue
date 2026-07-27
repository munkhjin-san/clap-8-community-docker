<template>
    <div class="material-exam">
        <div class="post-separetor mt-6"></div>
        <div class="material-exam__head">
            <p class="material-exam__title">試験</p>
            <div class="material-exam__meta">
                <span>合格基準：{{ exam?.passing_score ?? '-' }}%</span>
                <span>最大受験回数：{{ exam?.max_attempts ?? '-' }}回</span>
                <span>残り受験回数：{{ remainingAttempts }}</span>
            </div>
        </div>

        <div v-if="loading" class="material-exam__card">
            <p>読み込み中です...</p>
        </div>
        <div v-else-if="!exam" class="material-exam__card">
            <p>このセクションには試験が設定されていません。</p>
            <div class="si-box">
                <LoaderButton :content="'完了'" @triggered="emit('finished')"/>
            </div>
        </div>
        <div v-else class="material-exam__card">
            <div class="attempt-list" v-if="attempts.length">
                <div class="attempt-item" v-for="attempt in attempts" :key="attempt.id">
                    <span>第{{ attempt.attempt_number }}回</span>
                    <span>{{ attempt.score }}%</span>
                    <span :class="attempt.status === 'passed' ? 'passed' : 'failed'">{{ attempt.status === 'passed' ? '合格' : '不合格' }}</span>
                </div>
            </div>

            <div v-if="examPassed" class="exam-status">
                <p>合格しました。お疲れさまでした。</p>
                <div class="si-box">
                    <LoaderButton :content="'完了'" @triggered="emit('finished')"/>
                </div>
            </div>
            <div v-else class="exam-body">
                <div v-if="submissionResult" class="exam-result">
                    <p>結果：{{ submissionResult.status === 'passed' ? '合格' : '不合格' }}（{{ submissionResult.score }}%）</p>
                    <p>受験回数：{{ submissionResult.attempt_number }} / {{ exam.max_attempts }}</p>
                </div>
                <div class="question-block" v-for="(question, index) in exam.questions ?? []" :key="question.id">
                    <div class="question-title">Q{{ index + 1 }}. {{ question.prompt }}</div>
                    <div class="question-explanation" v-if="question.explanation">{{ question.explanation }}</div>
                    <div class="option-list">
                        <label
                            v-for="option in question.options"
                            :key="option.id"
                            class="option-item"
                            :class="{
                                'option-item--reveal': shouldRevealAnswers,
                                'option-item--correct': shouldRevealAnswers && option.is_correct,
                                'option-item--selected-wrong': shouldRevealAnswers && finalAnswersMap[question.id]?.option_id === option.id && !finalAnswersMap[question.id]?.is_correct
                            }"
                        >
                            <input
                                type="radio"
                                :name="`m-question-${question.id}`"
                                :value="option.id"
                                v-model="selectedAnswers[question.id]"
                                :disabled="!canSubmitExam"
                            />
                            <span>{{ option.label }}</span>
                            <span class="option-badge option-badge--correct" v-if="shouldRevealAnswers && option.is_correct">正解</span>
                            <span
                                class="option-badge option-badge--yours"
                                v-else-if="shouldRevealAnswers && finalAnswersMap[question.id]?.option_id === option.id"
                            >
                                あなたの回答
                            </span>
                        </label>
                    </div>
                    <div
                        class="question-feedback"
                        v-if="shouldRevealAnswers && finalAnswersMap[question.id] && !finalAnswersMap[question.id].is_correct"
                    >
                        <div class="feedback-title">正解：{{ getCorrectOptionLabel(question) }}</div>
                        <div class="feedback-text" v-if="question.correct_explanation">{{ question.correct_explanation }}</div>
                    </div>
                </div>
                <span v-if="formError && canSubmitExam" class="form-error">{{ formError }}</span>
                <div class="exam-actions" v-if="canSubmitExam">
                    <LoaderButton :loading="submitting" :content="'提出する'" @triggered="submitExam"/>
                </div>
                <div v-else class="exam-status text-center">
                    <label class="flex items-center justify-center gap-[10px] cursor-pointer">
                        <input :class="['custom-f-checkbox', { 'invalid-box' : !complete && validate }]" type="checkbox" v-model="complete">
                        <div>回答と解説を確認しました<span class="text-[gray] text-[12px] ml-[5px]">(必須)</span></div>
                    </label>
                    <p v-if="shouldRevealAnswers" class="exam-status-note">上記の正解と解説を確認してください。</p>
                    <div class="si-box">
                        <LoaderButton :content="'完了'" @triggered="finish"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useLearningApi } from '@/composables/learningApi';
import type { LearningExam, LearningExamAttempt, LearningExamQuestion, LearningFinalExamAnswer } from '@/types/learning';

const props = defineProps<{
    themeId: number | string
    materialId: number
}>()
const emit = defineEmits<{
    finished: []
}>()

const learningApi = useLearningApi()

const exam = ref<LearningExam | null>(null)
const attempts = ref<LearningExamAttempt[]>([])
const remainingAttempts = ref(0)
const loading = ref(false)
const submitting = ref(false)
const selectedAnswers = reactive<Record<number, number | null>>({})
const formError = ref('')
const submissionResult = ref<LearningExamAttempt | null>(null)
const finalAnswers = ref<LearningFinalExamAnswer[]>([])
const revealAnswers = ref(false)
const complete = ref(false)
const validate = ref(false)
const examPassed = computed(() => attempts.value.some(attempt => attempt.status === 'passed'))

const finalAnswersMap = computed(() => {
    return finalAnswers.value.reduce<Record<number, LearningFinalExamAnswer>>((acc, answer) => {
        acc[answer.question_id] = answer
        return acc
    }, {})
})

const shouldRevealAnswers = computed(() => revealAnswers.value)
const canSubmitExam = computed(() => remainingAttempts.value > 0)

const initializeAnswers = () => {
    Object.keys(selectedAnswers).forEach(key => delete selectedAnswers[Number(key)])
    if(!exam.value?.questions) return
    exam.value.questions.forEach(question => {
        const finalAnswer = shouldRevealAnswers.value ? finalAnswersMap.value[question.id] : null
        selectedAnswers[question.id] = finalAnswer ? finalAnswer.option_id : null
    })
}

const fetchExam = async() => {
    loading.value = true
    try{
        const data = await learningApi.getLearningExam(props.themeId, props.materialId)
        revealAnswers.value = !!data?.reveal_answers
        finalAnswers.value = data?.final_attempt_answers ?? []
        exam.value = data?.exam ?? null
        attempts.value = data?.attempts ?? []
        remainingAttempts.value = data?.remaining_attempts ?? 0
        initializeAnswers()
    }catch(error){
        console.error(error)
        exam.value = null
        attempts.value = []
        remainingAttempts.value = 0
        revealAnswers.value = false
        finalAnswers.value = []
    }finally{
        loading.value = false
    }
}

const finish = () => {
    validate.value = true
    if(!complete.value && !examPassed.value) return
    emit('finished')
}

const submitExam = async() => {
    if(submitting.value || !exam.value) return
    if(remainingAttempts.value <= 0){
        formError.value = '受験可能回数を超えています。'
        return
    }
    const questions = exam.value.questions ?? []
    const unanswered = questions.some(question => !selectedAnswers[question.id])
    if(unanswered){
        formError.value = '全ての設問に回答してください。'
        return
    }
    formError.value = ''
    submissionResult.value = null
    submitting.value = true
    const payload = {
        lesson_theme_id: props.themeId,
        lesson_material_id: props.materialId,
        answers: questions.map(question => ({
            question_id: question.id,
            option_id: Number(selectedAnswers[question.id])
        }))
    }
    try{
        const response = await learningApi.submitLearningExam(payload)
        submissionResult.value = response
        await fetchExam()
    }catch(error){
        console.error(error)
    }finally{
        submitting.value = false
    }
}

onMounted(() => {
    fetchExam()
})

watch(() => exam.value?.questions?.length, () => initializeAnswers())

const getCorrectOptionLabel = (question: LearningExamQuestion) => {
    if(!shouldRevealAnswers.value) return ''
    const option = question.options?.find(opt => opt.is_correct)
    return option?.label ?? ''
}
</script>

<style scoped>
.material-exam__head{
    margin: 20px 0 15px;
}
.material-exam__title{
    font-size: 14px;
    margin-bottom: 8px;
}
.material-exam__meta{
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: gray;
    flex-wrap: wrap;
}
.material-exam__card{
    background: var(--bg3);
    padding: 20px;
    line-height: 1.6;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.question-block{
    border: 1px solid var(--bg3);
    background: var(--background-color);
    padding: 15px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.option-list{
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.option-item{
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}
.option-item--reveal{
    border: 1px solid var(--bg3);
    padding: 8px 10px;
}
.option-item--correct{
    border-color: rgb(34, 197, 94);
    background: rgba(34, 197, 94, 0.08);
}
.option-item--selected-wrong{
    border-color: tomato;
    background: rgba(255, 99, 71, 0.08);
}
.option-badge{
    font-size: 11px;
    padding: 2px 8px;
    border: 1px solid transparent;
    margin-left: auto;
}
.option-badge--correct{
    background: rgba(34, 197, 94, 0.15);
    border-color: rgba(34, 197, 94, 0.4);
    color: rgb(22, 163, 74);
}
.option-badge--yours{
    background: var(--bg3);
    border-color: var(--bg3);
    color: gray;
}
.question-feedback{
    border-left: 3px solid var(--primary-color);
    background: var(--background-color);
    padding: 10px 12px;
    margin-top: 10px;
    font-size: 13px;
    line-height: 1.5;
}
.feedback-title{
    margin-bottom: 6px;
}
.feedback-text{
    font-size: 13px;
    color: inherit;
    line-height: 1.6;
}
.exam-status-note{
    font-size: 12px;
    color: gray;
    margin-top: 6px;
}
.exam-actions{
    display: flex;
    justify-content: flex-end;
    margin-top: 30px;
}
.attempt-list{
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 12px;
}
.attempt-item{
    display: flex;
    gap: 15px;
}
.attempt-item .passed{
    color: rgb(34, 197, 94);
}
.attempt-item .failed{
    color: tomato;
}
.exam-status{
    text-align: center;
    line-height: 1.8;
}
.exam-result{
    background: var(--background-color);
    padding: 10px;
    font-size: 14px;
}
</style>
