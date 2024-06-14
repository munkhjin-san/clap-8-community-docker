<template>
    <div class="admin-window" style="overflow: auto;">
        <Transition name="modalFade">
            <AssistantCreate v-if="createWindow" :editTarget="assistant" @createFinish="createFinish"/>
        </Transition>

        <div v-if="assistant" style="padding: 15px;font-size: 16px;background: var(--background-color);margin: 20px;line-height: 1.8">
            <div><strong>ID：</strong> {{ assistant.id }}</div>
            <div><strong>タイトル：</strong> {{ assistant.name }}</div>
            <div style="white-space: break-spaces;"><strong>指示：</strong> {{ assistant.instructions }}</div>
            <div><strong>モデル</strong> {{ assistant.model }}</div>
            <div><strong>Temperature：</strong> {{ assistant.temperature }}</div>
            <div><strong>top_p</strong> {{ assistant.top_p }}</div>
            <div style="display: flex;gap:15px;margin-top: 20px">
                <LoaderButton style="margin:0" :loading="false" @triggered="editTarget = assistant;createWindow = true" content="編集"/>
                <LoaderButton style="margin:0" :loading="deleting" @triggered="deleteAssistant" content="削除"/>
            </div>
        </div>
        <div v-else-if="fetch > 0" class="no-comment-text">
            現在アシスタントは登録されていません。
        </div>
        <div v-if="fetch > 0 && !assistant" @click="createWindow = true" class="createBoardButton fileNewButton" title="新規作成" id="boardCreate">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;"><path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path></svg>
        </div>
        <Transition name="modalFade">
            <div v-if="fetch == 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div> 
        </Transition>
    </div>
</template>
<script setup>
import { onMounted, ref, inject } from 'vue';
import OpenAI from "openai";
import AssistantCreate from './AssistantCreate.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
const props = defineProps(['theme'])
const assistant = ref(null)
const fetch = ref(0)
const createWindow = ref(false)
const deleting = ref(false)
const { confirm, info, notify } = inject('dialog')
const getThemes = inject('getThemes')
onMounted(async() => {
    console.log(props.theme)
    if(props.theme && props.theme.assistant_id){
        await getAssistant()
        fetch.value++
    }else{
        fetch.value++
    }
})
const createFinish = async(flag) => {
    if(flag){
        await getThemes()
        setTimeout(() => {
            getAssistant()
        }, 0);
        
    }
    createWindow.value = false
}
const deleteAssistant = async() => {

    const answer = await confirm('アシスタンを削除しますか。')
    if(answer) {
        deleting.value = true
        try {
            const openai = new OpenAI({
                apiKey: import.meta.env.VITE_OPENAI_API_KEY,
                dangerouslyAllowBrowser: true 
            });
            const response = await openai.beta.assistants.del(props.theme.assistant_id);
            console.log(response)
            axios.post('/create_learning_theme', {     
                id: props.theme.id,
                params: {
                    assistant_id: null
                }
            })
            info('削除しました。')
            assistant.value = null
            await getThemes()
        }catch(e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }finally{
            deleting.value = false
        }


    }
}
const getAssistant = async() => {
    try {
        const openai = new OpenAI({
            apiKey: import.meta.env.VITE_OPENAI_API_KEY,
            dangerouslyAllowBrowser: true 
        });
        assistant.value = await openai.beta.assistants.retrieve(props.theme.assistant_id);
    }
    catch(e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }finally{
        fetch.value++
    }

}
</script>