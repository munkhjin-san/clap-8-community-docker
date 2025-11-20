<template>
    <div class="overlay" @mousedown="close(false)">                         
        <div class="chatCreate scrollable" @mousedown.stop>
            <div class="recordFormTitle" style="display:flex">
                <p>{{ examData ? '試験を編集する' : '新しい試験を作成する' }}</p>
                <div class="m-close-button" @click="close(false)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div class="si-box">
                <ShortInput 
                    name="examTitle" 
                    placeHolder="試験タイトルを入力（任意）" 
                    customClass="full"
                    v-model="title"
                />
            </div>
            <div class="si-box">
                <LongInput 
                    placeHolder="説明（任意）"
                    v-model="description"
                />
                <!-- <textarea class="custom-o-input" style="width: 100%;min-height: 100px; box-sizing:border-box !important;" placeholder="説明（任意）" v-model="description"></textarea> -->
            </div>
            <div class="si-box" style="display:grid;grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:15px;">
                <div>
                    <!-- <label class="form-lbl text-sm">合格基準（%）</label> -->
                    <ShortInput type="number" placeHolder="合格基準（%）" v-model.number="passingScore"/>
                    <!-- <input type="number" min="1" max="100" class="custom-o-input w-full" v-model.number="passingScore"> -->
                </div>
                <div>
                    <ShortInput type="number" placeHolder="最大受験回数" v-model.number="maxAttempts" />
                    <!-- <label class="form-lbl text-sm">最大受験回数</label>
                    <input type="number" min="1" class="custom-o-input w-full" v-model.number="maxAttempts"> -->
                </div>
            </div>
            <div class="si-box">
                <div style="display:flex;align-items:center;justify-content:space-between;padding: 10px 0; position: sticky; top: 80px; background: var(--background-color);">
                    <div style="font-size:14px;">設問一覧</div>
                    <button class="custom-add-btn" @click="addQuestion">＋ 設問を追加</button>
                </div>
                <div class="flex flex-col gap-[15px]">
                    <div class="question-card" v-for="(question, qIndex) in questions" :key="question.uuid">
                        <div class="flex justify-between items-center">
                            <div class="form-lbl text-sm">設問 {{ qIndex + 1 }}</div>
                            <button class="custom-remove-btn" v-if="questions.length > 1" @click="removeQuestion(qIndex)">削除</button>
                        </div>
                        <textarea class="custom-o-input w-full mt-[10px] mb-2 !box-border" placeholder="問題文" v-model="question.prompt"></textarea>
                        <textarea class="custom-o-input w-full !box-border" placeholder="解説（任意）" v-model="question.explanation"></textarea>
                        <div class="flex items-center justify-between mt-[10px]">
                            <div class="form-lbl text-xs text-gray">選択肢</div>
                            <button class="custom-add-btn" @click="addOption(qIndex)">＋ 選択肢を追加</button>
                        </div>
                        <div class="flex flex-col gap-[8px] mt-[10px] whitespace-nowrap">
                            <div class="option-row" v-for="(option, oIndex) in question.options" :key="option.uuid">
                                <input class="custom-o-input w-full" placeholder="選択肢" v-model="option.label"/>
                                <label class="option-check">
                                    <input type="radio" :name="`question-${question.uuid}`" :checked="option.is_correct" @change="setCorrectOption(qIndex, oIndex)">
                                    <span>正解</span>
                                </label>
                                <button class="custom-remove-btn" v-if="question.options.length > 2" @click="removeOption(qIndex, oIndex)">削除</button>
                            </div>
                        </div>
                        <textarea
                            class="custom-o-input w-full !box-border mt-[10px]"
                            placeholder="正答の解説（任意）"
                            v-model="question.correct_explanation"
                        ></textarea>
                    </div>
                </div>
            </div>
            <div class="si-box">
                <span v-if="formError" class="form-error" style="font-size: 11px;">{{ formError }}</span>
                <LoaderButton @triggered="save" :loading="processing" content="保存する"/>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import ShortInput from '../../Form/ShortInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import LongInput from '@/components/Form/LongInput.vue';

const props = defineProps({
    themeId: {
        type: Number,
        required: true
    },
    examData: {
        type: Object,
        default: null
    }
})
const emit = defineEmits(['close'])
const api = useApi()
const { toast, ping } = useDialog()

const title = ref(props.examData?.title ?? '')
const description = ref(props.examData?.description ?? '')
const passingScore = ref(props.examData?.passing_score ?? 80)
const maxAttempts = ref(props.examData?.max_attempts ?? 1)
const processing = ref(false)
const formError = ref('')

const uuid = () => {
    if(typeof crypto !== 'undefined' && crypto.randomUUID){
        return crypto.randomUUID()
    }
    return Math.random().toString(36).substring(2, 11)
}

const normalizeOptions = (options) => {
    if(options?.length){
        return options.map(option => ({
            id: option.id ?? null,
            uuid: uuid(),
            label: option.label ?? '',
            is_correct: !!option.is_correct
        }))
    }
    return [
        { id: null, uuid: uuid(), label: '', is_correct: true },
        { id: null, uuid: uuid(), label: '', is_correct: false },
    ]
}
const normalizeQuestions = () => {
    if(props.examData?.questions?.length){
        return props.examData.questions.map((question, index) => ({
            id: question.id ?? null,
            uuid: uuid(),
            prompt: question.prompt ?? '',
            explanation: question.explanation ?? '',
            correct_explanation: question.correct_explanation ?? '',
            position: question.position ?? index,
            options: normalizeOptions(question.options)
        }))
    }
    return [{
        id: null,
        uuid: uuid(),
        prompt: '',
        explanation: '',
        correct_explanation: '',
        position: 0,
        options: normalizeOptions()
    }]
}
const questions = ref(normalizeQuestions())

const close = (refresh) => {
    emit('close', refresh)
}
const addQuestion = () => {
    questions.value.push({
        id: null,
        uuid: uuid(),
        prompt: '',
        explanation: '',
        correct_explanation: '',
        position: questions.value.length,
        options: normalizeOptions()
    })
}
const removeQuestion = (index) => {
    questions.value.splice(index, 1)
}
const addOption = (questionIndex) => {
    questions.value[questionIndex].options.push({
        id: null,
        uuid: uuid(),
        label: '',
        is_correct: questions.value[questionIndex].options.length === 0
    })
}
const removeOption = (questionIndex, optionIndex) => {
    questions.value[questionIndex].options.splice(optionIndex, 1)
    if(!questions.value[questionIndex].options.some(opt => opt.is_correct)){
        questions.value[questionIndex].options[0].is_correct = true
    }
}
const setCorrectOption = (questionIndex, optionIndex) => {
    questions.value[questionIndex].options.forEach((option, idx) => {
        option.is_correct = idx === optionIndex
    })
}
const validateForm = () => {
    formError.value = ''
    if(passingScore.value < 1 || passingScore.value > 100){
        formError.value = '合格基準は1〜100で入力してください。'
        return false
    }
    if(maxAttempts.value < 1){
        formError.value = '最大受験回数は1以上を入力してください。'
        return false
    }
    if(!questions.value.length){
        formError.value = '少なくとも1つの設問が必要です。'
        return false
    }
    for(const question of questions.value){
        if(!question.prompt.trim()){
            formError.value = '各設問に問題文を入力してください。'
            return false
        }
        if(question.options.length < 2){
            formError.value = '各設問には最低2つの選択肢が必要です。'
            return false
        }
        if(!question.options.some(opt => opt.is_correct)){
            formError.value = '各設問で正解の選択肢を指定してください。'
            return false
        }
        if(question.options.some(opt => !opt.label.trim())){
            formError.value = '選択肢の内容を入力してください。'
            return false
        }
    }
    return true
}
const save = async() => {
    if(processing.value){
        return
    }
    if(!validateForm()){
        ping(formError.value)
        return
    }
    processing.value = true
    const payload = {
        lesson_theme_id: props.themeId,
        exam_id: props.examData?.id ?? null,
        title: title.value,
        description: description.value,
        passing_score: passingScore.value,
        max_attempts: maxAttempts.value,
        questions: questions.value.map((question, index) => ({
            id: question.id ?? null,
            prompt: question.prompt,
            explanation: question.explanation,
            correct_explanation: question.correct_explanation,
            position: index,
            options: question.options.map(option => ({
                id: option.id ?? null,
                label: option.label,
                is_correct: option.is_correct
            }))
        }))
    }
    try{
        await api.post('/lesson_exam', payload, {
            toast: props.examData ? '試験を更新しました。' : '試験を追加しました。'
        })
        close(true)
    }catch(error){
        console.error(error)
        toast('保存に失敗しました。')
    }finally{
        processing.value = false
    }
}
</script>

<style scoped>
.question-card{
    border: solid 1px var(--bg3);
    padding: 15px;
    background: var(--bg3);
}
.option-row{
    display: flex;
    gap: 10px;
    align-items: center;
}
.option-check{
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
}
.custom-add-btn{
    font-size: 12px;
    color: var(--primary-color);
    cursor: pointer;
    border: none;
    background: transparent;
}
.custom-remove-btn{
    font-size: 12px;
    color: tomato;
    cursor: pointer;
    border: none;
    background: transparent;
}
</style>
