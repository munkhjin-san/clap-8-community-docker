<template>
    <div class="overlay" @mousedown="closeModal(false, null)">                         
        <div class="chatCreate" @mousedown.stop style="overflow: hidden auto;">     
            <div class="recordFormTitle" style="display:flex">
                <p>{{ editTarget ? `要約を編集する` : `新しい要約を作成する`}}</p>
                <div class="m-close-button" @click="closeModal(false, null)" style="position:unset; margin:auto 0 auto auto">
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
                <div style="font-size: 14px;margin-bottom: 15px;">要約質問</div>
                <div class="relative">
                    <div>
                        <div>
                            <div>
                                <div class="flex flex-col gap-[5px]">
                                    <TransitionGroup name="customInputGroup">
                                        <div :key="index" v-for="(question, index) in questions">     
                                            <div class="flex items-center gap-[10px] flex-col">
                                                <div class="flex items-center w-full">
                                                    <input placeholder="質問" class="custom-o-input w-full" type="text" ref="radios" v-model="question.question"/>
                                                </div>
                                                <div style="width: 100%;">
                                                    <div style="font-size: 14px;margin-bottom: 15px;">要約内容</div>
                                                    <RichEditor 
                                                        ref="richEdit" 
                                                        :initilaValue="question.content"
                                                        @content-updated="updateRichContent(index, $event)"
                                                    />
                                                </div>
                                                <div class="flex">
                                                    <div title="質問追加" @click="addQuestion()" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center">
                                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 32 32" style="fill: var(--primary-color);">
                                                            <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                                                        </svg>
                                                    </div>
                                                    <div title="質問削除" v-if="questions.length > 1" @click="removeQuestion(index, question.id)" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" style="fill: var(--primary-color);" viewBox="0 0 16.79 2.88">
                                                            <path class="cls-1" d="m1.15,2.67c2.42.14,4.85.2,7.28.2,2.43,0,4.85-.05,7.28-.25.56-.05,1.03-.5,1.08-1.08.06-.65-.43-1.23-1.08-1.29C10.86-.11,6-.05,1.15.21.54.25.04.74,0,1.36c-.05.68.47,1.27,1.15,1.31Z"/>
                                                        </svg>
                                                    </div>                                
                                                </div> 
                                            </div>             
                                        </div>
                                    </TransitionGroup>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            
            
                    
                    
            <div class="si-box">
                <LoaderButton @triggered="createSend" :loading="processing" :content="'保存する'"/>
            </div>               
        
        </div>
    </div>      
</template>

<script setup>      
import ShortInput from '../../Form/ShortInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue'
import RichEditor from '../../Global/RichEditor.vue';
import { inject, ref } from 'vue';
import { useApi } from '@/composables/api';
    const props = defineProps(['materialId', 'summaryData'])
    const emit = defineEmits(['createFinish'])
    const processing = ref(false)
    const title = ref(props.summaryData?.title ?? '')
    const richEdit = ref(null)
    const deleted = ref([])
    const questions = ref(props.summaryData?.questions.length ? props.summaryData.questions.map(({ id, question, content }) => ({ id, question, content })) : [
        {
            id: null,
            question: '',
            content: '',
        }
    ])
    const api = useApi()
    const createSend = async() => {
        processing.value = true
        if(!title.value){
            processing.value = false
            return
        }
        const params = {
            id: props.summaryData?.id ?? null,
            params: {
                lesson_material_id: props.materialId ?? props.summaryData?.lesson_material_id,
                title: title.value,
            },
            questions: questions.value,
            deleted: deleted.value
        }
        const data = await api.post('/add_material_summary',params, {
            toast: props.summaryData ? '編集しました。' : '保存しました。'
        })
        closeModal(true, data.id)
        processing.value = false                
    }
    const closeModal = (flag, id) => {
        processing.value = false
        emit('createFinish',flag, id);              
    }     
    const addQuestion = () => {
        questions.value.push({
            id: null,
            question: '',
            content: ''
        })
    }
    const removeQuestion = async(index, questionId) => {
        if (questionId) {
            deleted.value.push(questionId)
        }
        questions.value.splice(index, 1)
    }
    const updateRichContent = (index, content) => {
        questions.value[index].content = content;
    };
    
</script>
<style scoped>
.custom-f-radio  {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    width: 15px;
    height: 15px;
    border: 1px solid var(--primary-color);
    background-color: var(--background-color);
    position: relative;
    cursor: pointer;
    transition: background-color 0.3s, border-color 0.3s;
}
.custom-f-radio{
    border-radius: 50px;
    background-color: var(--background-color);
    box-sizing: border-box !important;
    min-height: 20px;
    min-width: 20px;
    width: 20px;
    height: 20px;
}
.custom-o-input{
    border: solid thin;
    padding: 5px 10px;
    font-size: 13px;
    background-color: var(--background-color);
}
.custom-q-input{
    border-bottom: solid thin transparent;
}
/* .custom-o-input:focus,
.custom-q-input:focus {
  border-bottom: solid thin var(--primary-color);
} */
.custom-q-input{
    font-size: 13px;
    padding: 5px 10px;
    width: -webkit-fill-available;
    background-color: var(--background-color);
}
.plus-button{
    height: 30px;
    width: 30px;
    border-radius: 50px;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}
.plus-button:hover{
    background-color: var(--bg3);
}
</style>
    
    
    
    
    
    