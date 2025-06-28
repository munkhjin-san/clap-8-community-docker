<template>
    <div>
        <div style="background: var(--bg3);padding: 20px;margin-top: 20px; white-space: normal;">
            <div style="font-weight: 600;margin-bottom: 20px">AI分析</div>

            <div class="response-container" v-html="sanitizedResponse"></div> 
            <LoaderButton style="margin: 20px 0 0 0" @triggered="openAiReview" :loading="loading" :content="'AI分析'"/>                               
        </div>
        <div style="margin: 15px 0;position:relative">
            <label class="check-container" style="align-self: center;margin: 0">
                <input id="allMemberSelector" v-model="checked" value="" name="" type="checkbox">
                <span class="checkmark-mini" style="margin: auto;bottom: 0;"></span>
                <p style="line-height: 1.3;font-size: 16px;color: var(--primary-color);font-size:14px">{{ confirmText }}</p>        
            </label>  
            <div v-if="!checked && validateCounter" class="i-error">必須です。</div>
        </div>
</div>
</template>
<script setup>
import {marked} from 'marked'
import DOMPurify from 'dompurify';
import OpenAI from "openai";
import { ref, computed } from 'vue';
import LoaderButton from './LoaderButton.vue';
import { useDialog } from '@/composables/dialog';
const props = defineProps(['soureText', 'assistandId', 'message', 'confirmText', 'answer'])
const reviewResultRaw = ref(props.soureText ? props.soureText : '')
const loading = ref(false)
const markedResponse = computed(() => {
    return marked(reviewResultRaw.value);
})
const sanitizedResponse = computed(() => {
    return DOMPurify.sanitize(markedResponse.value);
})
const checked = ref(false)
const validateCounter = ref(0)
const { ping } = useDialog()
const validate = async() => {
    validateCounter.value ++
    return checked.value
}
const openAiReview = async() => {       
        
    try{      

        loading.value = true 
        const full = props.answer ? `質問に関する回答内容：${props.message}` : `ポートフォリオ内容："""${props.message}"""`

        reviewResultRaw.value = ''
        const openai = new OpenAI({
            apiKey: import.meta.env.VITE_OPENAI_API_KEY,
            dangerouslyAllowBrowser: true 
        });       

        const assistant = await openai.beta.assistants.retrieve( props.assistandId );
        const thread = await openai.beta.threads.create();
        await openai.beta.threads.messages.create( thread.id, {role: "user", content: full });
        const run = openai.beta.threads.runs.stream(thread.id, { assistant_id: assistant.id })
            .on('textDelta', (textDelta, snapshot) => {
                let before = reviewResultRaw.value ? reviewResultRaw.value : ''
                reviewResultRaw.value = before + textDelta.value
            }).on('end', () => {
                loading.value = false
            })
        for await (const event of run) {                
            if(event.event == 'thread.run.completed'){
                console.log(event.data);
            }
        }
    }catch(e){
        ping(e)
    }finally{
        loading.value = false
    }
        
}
defineExpose({reviewResultRaw, loading, validate})
</script>