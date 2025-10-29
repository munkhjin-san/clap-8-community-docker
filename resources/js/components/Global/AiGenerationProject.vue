<template>
    <LongInput 
        name="description"
        :place-holder="placeHolder"
        v-model="text"
        :key="`key_${key}`"
        custom-class="!pb-[35px]"
    />
    <div title="概要を自動生成する" class="absolute bottom-[10px] right-[10px] bg-[var(--background-color)] flex items-center cursor-pointer">
        <CommandButton
            :buttons="[
                { title: loading ? '生成中...' : '自動生成', action: () => execute()}
            ]" 
        />
    </div>
</template>
<script lang="ts" setup>
import { onBeforeUnmount, ref, useTemplateRef } from 'vue'
import LongInput from '../Form/LongInput.vue';
import { useSSE } from '@/composables/sse';
import { useApi } from '@/composables/api';
import { Project } from '@/interface/projectInterface';
import CommandButton from './CommandButton.vue';
import { useDialog } from '@/composables/dialog';

const props = defineProps<{
    urlPrefix: string
    data: Partial<Project>
    placeHolder?: string
    which: 'description' | 'mission' | 'innovation' | 'strategy' | 'operation'
    configKey: 'project_description_generation' | 'project_miso_generation'
}>()
const key = ref(0)
const api = useApi()
const text = defineModel<string>('text', { default: '' })
const loading = ref(false)
const { on, start, isOpen, stop } = useSSE({autoReconnect: false})
const { ask, ping } = useDialog()
on('update', (payload:any) => {
    try{
        const parsed = JSON.parse(payload)
        if(parsed?.event === 'response.output_text.delta'){
            text.value += parsed.response.delta
            key.value += 1
        }
    }
    catch{}
});
on('error', (e) => {
    loading.value = false
    ping('エラーが発生しました。しばらくしてから再度お試しください。')
});
on('complete', () => {
    loading.value = false
});
const execute = async () => {
    if(loading.value || isOpen.value) return

    if(text.value && text.value.trim().length > 0){
        const result = await ask('概要が既に入力されています。上書きしますか？')
        if(!result.value) return
    }
    text.value = ''
    loading.value = true
    let inputText = `プロジェクト名 : ${props.data.name}、プロジェクトの実施期間 : ${props.data.date_start} ~ ${props.data.date_end}、プロジェクトの概要 : ${ props.urlPrefix === '/project_generate_description' ? props.data.private_memo : props.data.description}`
    if(props.data.mission && props.data.mission.length){
        inputText += `ミッション : ${props.data.mission}`
    }
    if(props.data.innovation && props.data.innovation.length){
        inputText += `イノベーション : ${props.data.innovation}`
    }
    if(props.data.strategy_miso && props.data.strategy_miso.length){
        inputText += `ストラテジー : ${props.data.strategy_miso}`
    }
    if(props.data.operation && props.data.operation.length){
        inputText += `オペレーション : ${props.data.operation}`
    }
    if(props.data.customers && props.data.customers.length){
        inputText += `顧客企業 : ${props.data.customers.join(', ')}`
    }
    if(props.data.partners && props.data.partners.length){
        inputText += `パートナー企業 : ${props.data.partners.join(', ')}`
    }
    if(props.data.category && props.data.category.length){
        inputText += `サービスカテゴリ : ${props.data.category.join(', ')}`
    }
    const finalText = inputText.trim()
    if (!finalText) return
    const {id} = await api.post('/ai_correction_prepare', { text: finalText })
    if(!id) return

    if(!props.urlPrefix) return

    start(`/stream_prompt`, { request_id: id, which: props.which, config_key: props.configKey } )
}


onBeforeUnmount(() => {
    stop();
});

defineExpose({
    execute
})
</script>