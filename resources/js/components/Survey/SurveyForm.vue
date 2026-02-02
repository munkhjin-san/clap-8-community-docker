<template>
    <div class="w-full h-full" :class="{'overflow-y-auto' : mode == 'all'}">
        <div :class="{'p-[20px] m-[20px]' : mode == 'all'}" class="bg-[var(--background-color)] text-[var(--primary-color)] leading-normal flex flex-col gap-[20px] text-[14px]">
            <div v-if="survey.repeat_setting == 1" class="text-[12px]">
                <div class="p-[15px] bg-[var(--bg3)]">
                    <div class="mb-[10px]">対象月</div>
                    <select v-model="answer.target_date" class="border border-solid border-[var(--primary-color)] h-[40px] m-h-[40px] px-[10px] text-[var(--primary-color)] appearance-none rounded-none">
                        <option v-for="month in monthsArray" :value="month">{{ DateTime.fromISO(month).toFormat('yyyy年M月') }}</option>
                    </select>
                    <p class="text-[12px] mt-[5px]">このフォームは毎月提出するものです。該当の月を選んでください。</p>
                </div>                
            </div>
            <div class="text-[16px]">{{ survey.title }}</div>
            <div class="rich-wrapper" v-html="urlCheck(survey.description)"></div>
            <div class="flex flex-col gap-[30px]" :key="forceRefresh">
                <div v-for="block in visibleBlocks" :key="block.id">
                    <SurveyBlock
                        ref="blocks"
                        :block="block"
                        :answer="answer.block_answers.find(a => a.custom_form_block_id == block.id)"
                        @selection-change="onSelectionChange"
                    />
                </div>
                
            </div>
            <div class="si-box flex justify-center gap-[20px]">
                <LoaderButton content="一時保存する" style="margin:0" :loading="loading[1]" @triggered="sendSurvey(1)"/>
                <LoaderButton content="送信する" style="margin:0" :loading="loading[2]" @triggered="sendSurvey(2)"/>
            </div>
        </div>
        <Teleport to="body">
            <RollDice :form-id="survey.id" v-if="prizeEligible" @close="closePrize"/>
        </Teleport>
    </div>
</template>
<script setup lang="ts">
import { CustomForm, SurveyAnswer } from '@/interface/customFormInterface';
import SurveyBlock from './SurveyBlock.vue';
import { urlCheck } from '@/utils/tools';
import { computed, onMounted, reactive, ref, useTemplateRef } from 'vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { ComponentExposed } from 'vue-component-type-helpers';
import { useBadgeStore } from '@/store/badge';
import { DateTime, Interval } from 'luxon';
import { useRoute } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import RollDice from '../Global/RollDice.vue';
const badge = useBadgeStore()
const props = defineProps<{
    survey: CustomForm
    mode?: 'board' | 'all'
    answerId?: number | null
}>()

const emit = defineEmits<{
    saved: [status: number, answerId: number | null]
}>()
const route = useRoute()
const auth = useAuthUserStore()
const api = useApi()
const { ping } = useDialog()
const prizeEligible = ref(false)
const answerId = ref<number | null>(null)
onMounted(() => {
    const answerEditId = props.answerId || route.query.answerId || null
    if(props.survey.survey_answers && answerEditId){
        const targetAnswer = props.survey.survey_answers.find(a => a.id == answerEditId)
        console.log('targetAnswer', targetAnswer)
        if(targetAnswer && targetAnswer.user_id == auth.id){
            answer.value = targetAnswer
            forceRefresh.value += 1
        }
    } 
})

const monthsArray = computed(() => {
    const interval = Interval.fromDateTimes(
        DateTime.now().startOf('month').minus({ months: 6 }),
        DateTime.now().endOf('month').plus({ months: 6 })
    );
    if (!interval.isValid) {
        return [];
    }
    return interval.splitBy({ months: 1 }).map(i => i.start?.toISODate() || '').filter(date => date);
})
const blocks = useTemplateRef<ComponentExposed<typeof SurveyBlock>[]>('blocks')
const answer = ref<SurveyAnswer>({
    block_answers: [],
    target_date: props.survey.repeat_setting == 1 ? DateTime.now().startOf('month').toISODate() : null,
})

const loading = reactive({
    1: ref(false), // 一時保存
    2: ref(false), // 送信
})
const forceRefresh = ref(0)
const selections = reactive<Record<number, { type: 'radio' | 'checkbox'; elementIds: number[] }>>({})
const onSelectionChange = (payload: { blockId: number; type: 'radio' | 'checkbox'; elementIds: number[] }) => {
    selections[payload.blockId] = {
        type: payload.type,
        elementIds: payload.elementIds,
    }
}
const visibleBlocks = computed(() => {
    const visible: CustomForm['blocks'] = []
    const visibleIds = new Set<number>()
    for (const block of props.survey.blocks) {
        const rawDependsOn = Array.isArray(block.depends_on) ? block.depends_on : (block.depends_on ? [block.depends_on] : [])
        if (!rawDependsOn.length) {
            visible.push(block)
            visibleIds.add(block.id)
            continue
        }
        const matches = rawDependsOn.some((condition) => {
            if (!condition?.block_id || !visibleIds.has(condition.block_id)) return false
            const selection = selections[condition.block_id]
            if (!selection) return false
            const expectedIds = Array.isArray(condition.element_ids)
                ? condition.element_ids.map((id) => Number(id))
                : []
            if (!expectedIds.length) return false
            const conditionType = condition.type === 'checkbox' ? 'checkbox' : 'radio'
            if (conditionType !== selection.type) return false
            if (conditionType === 'checkbox') {
                const matchMode = condition.match === 'all' ? 'all' : 'any'
                return matchMode === 'all'
                    ? expectedIds.every((id) => selection.elementIds.includes(id))
                    : expectedIds.some((id) => selection.elementIds.includes(id))
            }
            return selection.elementIds[0] === expectedIds[0]
        })
        if (matches) {
            visible.push(block)
            visibleIds.add(block.id)
        }
    }
    return visible
})

const sendSurvey = async(status:number) => {
    const targets = blocks.value && blocks.value.length ? blocks.value.filter(b => b) : []
    let blockValid = true
    
    for(const block of targets){
        const isValid = block.isValid()
        console.log('isValid', isValid, block)
        blockValid =  isValid && blockValid
    }
    if(!blockValid) {
        ping('必須項目が未入力です。')
        return
    }
    
    const constructedBlocks = targets.map(t => ({
        ...t.extractedData,
        files: t.extractedData.files.map(f => f.id)
    }));
    const params = {
        custom_form_id: props.survey.id,
        params: constructedBlocks,
        status: status,
        survey_answer_id: answer && answer.value.id ? answer.value.id : null,
        target_date: answer.value.target_date,
    }
    
    loading[status] = true
    const messages = ['', '保存しました。', '送信しました。']
    const data = await api.post('/save_survey_answer', params, {
        toast: messages[status]
    })
    loading[status] = false
    if(data?.prize_eligible && status == 2){
        prizeEligible.value = data.prize_eligible
        answerId.value = data.id
    }else{
        setTimeout(() => {        
            badge.getRemindBadge()  
            emit('saved', status, data?.id)
        }, 300);
    }



}

const closePrize = () => {
    prizeEligible.value = false
    badge.getRemindBadge()  
    emit('saved', 2, answerId.value)
}
</script>
<style>
.rich-wrapper p:empty::after { content: "\00A0"; }                  /* <p></p> */

.rich-wrapper p:has(> br:only-child)::after { content: "\00A0"; }   /* <p><br></p> */
</style>
