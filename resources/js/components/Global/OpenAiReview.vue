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
<script setup lang="ts">
import {marked} from 'marked'
import DOMPurify from 'dompurify';
import { ref, computed } from 'vue';
import LoaderButton from './LoaderButton.vue';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';
import { useSSE } from '@/composables/sse';
const props = defineProps<{
    sourceText?: string,
    assistandId?: number,
    message: string,
    confirmText: string,
    answer?: boolean,
    configKey?: string,
    promptId?: string
}>()
const reviewResultRaw = ref(props.sourceText ? props.sourceText : '')
const loading = ref(false)
const markedResponse = computed(() => {
    return marked(reviewResultRaw.value);
})
const sanitizedResponse = computed(() => {
    return DOMPurify.sanitize(markedResponse.value as string);
})
const checked = ref(false)
const validateCounter = ref(0)
const api = useApi()
const { ping } = useDialog()
const { on, start, stop } = useSSE({autoReconnect: false})
const validate = async() => {
    validateCounter.value ++
    return checked.value
}
on('update', (payload:any) => {
    try{
        const parsed = JSON.parse(payload)
        if(parsed?.event === 'response.output_text.delta'){
            reviewResultRaw.value += parsed.response.delta
        }
    }
    catch{}
});
on('error', (e) => {
    ping('エラーが発生しました。しばらくしてから再度お試しください。')
    loading.value = false
});
on('complete', () => {
    loading.value = false
});
const openAiReview = async() => {               
    try{      
        const text = props.answer ? `質問に関する回答内容：${props.message}` : `ポートフォリオ内容："""${props.message}"""`        
        if (!text) return
        const {id} = await api.post('/ai_correction_prepare', { text })
        if(!id) return
        stop();
        reviewResultRaw.value = ''
        loading.value = true
        if(props.promptId){
            start('/stream_prompt', { request_id: id, prompt_id: props.promptId })
        }else if(props.configKey){
            start( `/stream_prompt`, { request_id: id, config_key: props.configKey } )
        }
        
    }catch(e){
        ping(e)
    }        
}
defineExpose({reviewResultRaw, loading, validate})
</script>