<template>
    <div class="ai-prompt-root focused" style="color: var(--primary-color);" >
        <span class="form-plc" style="font-weight: 600;">AI修正案</span> 
        <div v-html="editedText" ref="content" class="typeBoxArea" style="width: calc(100% - 20px);outline: none;border: none; padding: 0 10px 10px; margin-top: 30px;" :contenteditable="isEditable"></div>
        <div class="w-full flex items-center">  
            <div class="flex items-center gap-2.5 mb-2.5 ml-2.5">
                <select class="commentEditButton !m-0" id="aitoneselector" v-model="aiTone">
                    <option v-for="tone in aiToneOptions" :value="tone.value">{{ tone.label }}</option>
                </select>
                <div @click="edit" v-if="isEditable" class="commentEditButton !m-0">再生成</div>
            </div>  
            <div class="ml-auto flex items-center gap-2.5 mb-2.5 mr-2.5">
                <div @click="apply" v-if="isEditable" class="commentEditButton !m-0">適用</div>
                <div @click="reset" v-if="isEditable" class="commentEditButton !m-0">閉じる</div>
            </div>                        
        </div>                        
    </div>
</template>
<script lang="ts" setup>
import { useApi } from '@/composables/api';
import { onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue'
import { useDialog } from '@/composables/dialog';
import { useSSE } from '@/composables/sse';

const props = defineProps<{
    target: HTMLElement | null
}>()
const emit = defineEmits<{
    (e: 'close'): void
}>()

const url = ref('')
const api = useApi()
const editedText = ref('')
const isEditable = ref(false)
const content = useTemplateRef('content')
const { ping } = useDialog()
const { on, start, stop } = useSSE({autoReconnect: false})
const working = ref(false)
onMounted(() => {
    edit()
})
on('update', (payload:any) => {
    try{
        const parsed = JSON.parse(payload)
        if(parsed?.event === 'response.output_text.delta'){
            editedText.value += parsed.response.delta
        }
    }
    catch{}
});
on('error', (e) => {
    working.value = false
    isEditable.value = true
    ping('エラーが発生しました。しばらくしてから再度お試しください。')
});
on('complete', () => {
    working.value = false
    isEditable.value = true
});
const aiToneOptions = [
    { label: 'カジュアル', value: 'casual' },
    { label: 'フォーマル', value: 'formal' },
]

const aiTone = ref('casual')
const edit = async () => {
    
    stop();
    const text = props.target?.innerText.trim()
    if (!text) return
    const {id} = await api.post('/ai_correction_prepare', { text })
    if(!id) return
    editedText.value = ''
    isEditable.value = false
    working.value = true
    start( `/stream_prompt`, { request_id: id, config_key: 'message_correction', aitone: aiTone.value } )
}

const apply = () => {
    try{
        const text = content.value?.innerText || ''
        if (props.target && text) {
            props.target.textContent = text
        }
        reset()
    }
    catch{
        ping('適用するに失敗しました。');
    }   
}

const reset = () => {
    url.value = ''
    isEditable.value = false
    editedText.value = ''
    emit('close')
}
onBeforeUnmount(() => {
    stop();
});

defineExpose({
    editedText,
    working,
    edit,
    apply,
    reset
})
</script>