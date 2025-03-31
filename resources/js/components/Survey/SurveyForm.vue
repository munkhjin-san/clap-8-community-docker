<template>
    <div class="w-full h-full overflow-y-auto">
        <div class="m-[20px] p-[20px] bg-[var(--background-color)] text-[var(--primary-color)] leading-normal flex flex-col gap-[20px] text-[14px]">
            <div class="text-[12px] ml-auto">ステータス : 
                <span v-if="hasAnswer && hasAnswer.status == 2" class="text-[green]">回答済み</span>
                <span v-else-if="hasAnswer && hasAnswer.status == 1" class="text-[orange]">一時保存</span>
                <span v-else class="text-[gray]">未回答</span>
            </div>
            <div class="text-[16px]">{{ survey.title }}</div>
            <div v-html="urlCheck(survey.description)"></div>
            <div class="flex flex-col gap-[30px]">
                <div v-for="block in survey.blocks">
                    <SurveyBlock ref="blocks" :block="block"/>
                </div>
                
            </div>
            <div class="si-box flex justify-center gap-[20px]">
                <LoaderButton content="一時保存する" style="margin:0" :loading="loading[1]" @triggered="sendSurvey(1)"/>
                <LoaderButton content="送信する" style="margin:0" :loading="loading[2]" @triggered="sendSurvey(2)"/>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { CustomForm, SurveyAnswer } from '@/interface/customFormInterface';
import { useRoute } from 'vue-router';
import SurveyBlock from './SurveyBlock.vue';
import { urlCheck } from '@/utils/tools';
import { computed, inject, onMounted, reactive, ref, useTemplateRef } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { ComponentExposed } from 'vue-component-type-helpers';
import axios from 'axios';
import { DialogMethods } from '@/interface/globalInterface';
import { DialogKey } from '@/interface/keys';
import { useBadgeStore } from '@/store/badge';
const { confirm, info, notify } = inject('dialog') as DialogMethods;
const badge = useBadgeStore()
const props = defineProps<{
    survey: CustomForm
}>()

const emit = defineEmits<{
    saved: []
}>()
const route = useRoute()
const blocks = useTemplateRef<ComponentExposed<typeof SurveyBlock>[]>('blocks')
const answer = reactive<SurveyAnswer>({
    block_answers: []
})

const loading = ref([false, false, false])
onMounted(() => {
    const myAnswer = props.survey?.survey_answers ? props.survey.survey_answers[0] : null
    if(myAnswer){
        Object.assign(answer, myAnswer)
    }
})
const hasAnswer = computed(() => {
    return props.survey.survey_answers && props.survey.survey_answers.length ? props.survey.survey_answers[0] : null
})
const sendSurvey = async(status:number) => {
    const targets = blocks.value && blocks.value.length ? blocks.value : []
    let blockValid = true
    
    for(const block of targets){
        const isValid = block.isValid()
        blockValid =  isValid && blockValid
    }
    if(!blockValid) return
    
    const constructedBlocks = targets.map(t => ({
        ...t.extractedData,
        files: t.extractedData.files.map(f => f.id)
    }));
    const params = {
        custom_form_id: props.survey.id,
        params: constructedBlocks,
        status: status
    }
    
    try{
        const messages = ['', '保存しました。', '送信しました。']
        loading[status] = true
        await axios.post('/save_survey_answer', params)
        setTimeout(() => {
            emit('saved')
            info(messages[status])
            loading[status] = false
            badge.getRemindBadge()  
        }, 300);

    } catch (e) { 
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } 
}
</script>