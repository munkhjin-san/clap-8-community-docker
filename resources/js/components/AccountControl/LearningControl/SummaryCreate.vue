<template>
    <div class="overlay" @mousedown="closeModal(false, null)">                         
        <div class="chatCreate summary-create" @mousedown.stop>     
            <div class="recordFormTitle summary-create__header">
                <p>{{ summaryData ? `理解チェックを編集する` : `新しい理解チェックを作成する`}}</p>
                <div class="m-close-button summary-create__close" @click="closeModal(false, null)">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>      
            
            <div class="si-box">
                <ShortInput 
                    name="lessonTitle" 
                    placeHolder="タイトルを入力（必須）" 
                    :rules="'required'"
                    customClass="full"
                    ref="lessonTitle"
                    type="text"
                    v-model="title"
                />
            </div>
            
            <div class="si-box">
                <div class="summary-create__section-title">理解チェックの質問</div>
                <TransitionGroup name="customInputGroup" class="summary-question-list" tag="div">
                    <SummaryQuestionEditor
                        v-for="(question, index) in questions"
                        :key="question.uuid"
                        :question="question"
                        :index="index"
                        :can-remove="questions.length > 1"
                        @add-question="addQuestion"
                        @remove-question="removeQuestion"
                        @content-updated="updateRichContent"
                    />
                </TransitionGroup>
                
            </div>
            
            
                    
                    
            <div class="si-box">
                <LoaderButton @triggered="createSend" :loading="processing" :content="'保存する'"/>
            </div>               
        
        </div>
    </div>      
</template>

<script setup lang="ts">      
import ShortInput from '../../Form/ShortInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue'
import { ref } from 'vue';
import { useLearningApi } from '@/composables/learningApi';
import type { LearningSummary } from '@/types/learning';
import SummaryQuestionEditor from './Authoring/SummaryQuestionEditor.vue';
import type { EditableSummaryQuestion } from './Authoring/summaryEditorTypes';
    const props = defineProps<{
        materialId?: number | null
        summaryData?: (LearningSummary & { lesson_material_id?: number | null }) | null
    }>()
    const emit = defineEmits<{
        createFinish: [reload: boolean, id: number | null]
    }>()
    const processing = ref(false)
    const title = ref(props.summaryData?.title ?? '')
    const deleted = ref<number[]>([])
    const uuid = () => {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID()
        }
        return Math.random().toString(36).substring(2, 11)
    }
    const questions = ref<EditableSummaryQuestion[]>(props.summaryData?.questions?.length ? props.summaryData.questions.map(({ id, question, content }) => ({ id, uuid: uuid(), question, content })) : [
        {
            id: null,
            uuid: uuid(),
            question: '',
            content: '',
        }
    ])
    const learningApi = useLearningApi()
    const createSend = async() => {
        processing.value = true
        if(!title.value){
            processing.value = false
            return
        }
        const params = {
            id: props.summaryData?.id ?? null,
            params: {
                lesson_material_id: props.materialId ?? props.summaryData?.lesson_material_id ?? null,
                title: title.value,
            },
            questions: questions.value.map(({ id, question, content }) => ({ id, question, content })),
            deleted: deleted.value
        }
        const data = await learningApi.saveMaterialSummary(params, Boolean(props.summaryData))
        closeModal(true, data.id)
        processing.value = false                
    }
    const closeModal = (flag: boolean, id: number | null) => {
        processing.value = false
        emit('createFinish',flag, id);              
    }     
    const addQuestion = () => {
        questions.value.push({
            id: null,
            uuid: uuid(),
            question: '',
            content: ''
        })
    }
    const removeQuestion = async(index: number, questionId: number | null) => {
        if (questionId) {
            deleted.value.push(questionId)
        }
        questions.value.splice(index, 1)
    }
    const updateRichContent = (index: number, content: string) => {
        questions.value[index].content = content;
    };
    
</script>
<style scoped>
.summary-create{
    overflow: hidden auto;
}

.summary-create__header{
    display: flex;
}

.summary-create__close{
    margin: auto 0 auto auto;
    position: unset;
}

.summary-create__section-title{
    font-size: 14px;
    margin-bottom: 15px;
}

.summary-question-list{
    display: flex;
    flex-direction: column;
    gap: 5px;
}
</style>
    
    
    
    
    
    
