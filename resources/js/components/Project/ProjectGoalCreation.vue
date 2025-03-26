<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>成果目標作成</p>
        </template>
        <template #content>
            <div class="si-box">
                {{ selectedDate.name }}
            </div>
            <div class="si-box">
                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">該当部門選択（必須）</p>
                <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="chosenProject">
                    <option v-for="project in authProjects" :value="project">{{ project.name }}</option>
                </select>
            </div>
            <div class="si-box">
                <p v-html="`職能レベル : <strong>${evaluationData?.current_level || '未設定'}</strong>`"></p>
            </div>
            <div class="si-box">
                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">AI アドバイス</p>
                <div class="leading-normal whitespace-break-spaces text-[13px]" v-html="aiAdvice"></div> 
            </div>
            <div class="si-box" style="margin-bottom: 10px;">
                目標設定フォーム
            </div>
            <div>
                <LongInput
                    placeHolder="現状分析"
                    v-model="situation"
                    rules="required"
                    :initialValue="situation"
                    ref="situationRef"
                    type="text"
                    custom-class="height-adjust"
                />
            </div>
            <div class="si-box">
                <LongInput 
                    placeHolder="行動計画"
                    v-model="actionPlan"
                    rules="required"
                    :initialValue="actionPlan"
                    ref="actionPlanRef"
                    type="text"
                    custom-class="height-adjust"
                />
            </div>
            <div class="si-box">
                <LongInput 
                    placeHolder="成果目標"
                    v-model="goalContent"
                    rules="required"
                    :initialValue="goalContent"
                    ref="goalContentRef"
                    type="text"
                    custom-class="height-adjust"
                />
            </div>
            <div class="si-box">
                <LongInput 
                    placeHolder="期待される効果"
                    type="text"
                    v-model="expectedEffect"
                    :initialValue="expectedEffect"
                    rules="required"
                    ref="expectedRef"
                    custom-class="height-adjust"
                />
            </div>
            
            <div class="si-box">
                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">期日</p>
                <div style="display:flex;position: relative;width:100%">
                    <ShortInput 
                        name="startDate" 
                        :rules="'required'"
                        :initialValue="startDate"
                        customClass="date"
                        ref="startDateRef"
                        type="date"
                        v-model="startDate"
                    />
                    <div style="align-self: center;margin: 0 20px;font-size: 14px;color: gray;">ー</div>
                    <ShortInput 
                        name="endDate" 
                        :rules="'required'"
                        :initialValue="endDate"
                        customClass="date"
                        ref="endDateRef"
                        type="date"
                        v-model="endDate"
                    />
                </div>
            </div>
            
            <div style="background: var(--bg3);padding: 20px;margin-top: 30px;">
                <div class="mb-[15px]">AI判定とフィードバック</div>

                <div class="mb-[20px] text-[13px] whitespace-break-spaces leading-normal" v-html="content_review"></div> 
                <LoaderButton style="margin: 0" @triggered="getReview" :loading="reviewLoading" :content="'AI判定とフィードバック'"/>                               
            </div>
            <div class="si-box" v-if="content_review" style="justify-content: center;display: flex;gap:15px;flex-wrap: wrap;">
                <LoaderButton style="margin: 0;" @triggered="saveOutcomeGoal(0)" content="保存" :loading="loading"/>
                <LoaderButton style="margin: 0;" @triggered="saveOutcomeGoal(2)" content="申請" :loading="loading"/>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { Project } from '@/interface/projectInterface';
import { useResponsive } from '@/store/responsive';
import { inject, onMounted, ref } from 'vue';
import ShortInput from '../Form/ShortInput.vue';
import LongInput from '../Form/LongInput.vue';
import moment from 'moment';
import axios from 'axios';
import LoaderButton from '../Global/LoaderButton.vue';
import { Dialog } from '@/interface/globalInterface';
import OpenAI from 'openai';
import { Stream } from 'openai/streaming.mjs';
import { useBadgeStore } from '@/store/badge'
import { useRoute } from 'vue-router';
import { EvaluationRecord } from '@/interface/evaluationInterface';
import Modal from '../Global/Modal.vue';
interface authProject {
    value: Project
}
const emit = defineEmits([
    'close',
    'fetchMemberData'
])
const props = defineProps([
    'selectedDate',
    'selectedProject',
    'editGoalData',
])


const checkedItems = ref([])
const responsive = useResponsive()
const authProjects = inject<authProject>('authProjects')
const startDate = ref(props.editGoalData?.start_date ?? moment().format('YYYY-MM-DD'))
const endDate = ref(props.editGoalData?.end_date ?? '')
const goalContent = ref(props.editGoalData?.outcome_goal ?? '')
const chosenProject = ref(props.selectedProject ?? null)
const content_review = ref(props.editGoalData?.ai_review ?? '')
const aiAdvice = ref(props.editGoalData?.ai_advice ?? '')
const reviewLoading = ref(false)
const loading = ref(false)
const situation = ref(props.editGoalData?.situation_analysis ?? '')
const actionPlan = ref(props.editGoalData?.action_plan ?? '')
const expectedEffect = ref(props.editGoalData?.expected_effect ?? '')
const target = ref(props.editGoalData?.target_value ?? '')
const startDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const endDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const goalContentRef = ref<InstanceType<typeof LongInput> | null>(null)
const actionPlanRef = ref<InstanceType<typeof LongInput> | null>(null)
const situationRef = ref<InstanceType<typeof LongInput> | null>(null)
const expectedRef = ref<InstanceType<typeof LongInput> | null>(null)
const { notify, confirm, info } = inject<Dialog>('dialog')!
const refresh = inject('refresh') as Function
const getProjects = inject('getProjects') as Function
const badge = useBadgeStore()
const route = useRoute()
const evaluationData = ref<EvaluationRecord | null>(null)
onMounted(() => {
    getEvaluationData()
})

const getEvaluationData = async() => {
    console.log(route)
    try {
        const span = route.params.span as string
        const [year, which_half] = span.split('-')
        const response = await axios.post('/get_evaluations', {
            user_id: route.params.memberId,
            year: year,
            which_half: which_half  
        }).then(res => res.data)
        evaluationData.value = response && response.length > 0 ? response[0] : null
        if(evaluationData.value && !aiAdvice.value){
            getAdvice()
        }
        
    } catch (e) {
        // notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const getAdvice = async() => {
    const full = `
                職務評価基準: ${evaluationData.value?.current_level}
                概要: ${chosenProject.value?.overview}
                戦略: ${chosenProject.value?.strategy}
                KGI: ${chosenProject.value?.kgi}
                KPI: ${chosenProject.value?.kpi}
                この情報に基づいて、従業員はどのような成果目標を作成できますか? どのようなアドバイスを与えることができますか?
                
                ** などの記号、リスト、箇条書きを使用せずに、手順を明確に説明して応答します。`
    aiAdvice.value = ''
    const openai = new OpenAI({
        apiKey: import.meta.env.VITE_OPENAI_API_KEY,
        dangerouslyAllowBrowser: true 
    });
    try {
        const response = await openai.chat.completions.create({
            // model: 'gpt-4',
            model: 'gpt-4o-mini',
            messages: [{ role: 'assistant', content: full }],
            stream: true,
            temperature: 0.8
        })
        if (!response || !response[Symbol.asyncIterator]) {
            throw new Error('OpenAI API からの応答が無効です。');
        }
        const stream = response as Stream<OpenAI.Chat.Completions.ChatCompletionChunk>;
            for await (const part of stream) {
                try {
                    const content = part.choices[0]?.delta?.content || ''
                    let before = aiAdvice.value ? aiAdvice.value : ''
                    aiAdvice.value = before + content
                } catch (error) {
                    console.log(error)
                }
                
                

            }
    } catch (err) {
        if (err instanceof OpenAI.APIError) {
            if(err.status == 500){
                notify('AI修正に失敗しました。<br>ChatGPTサーバーから反応がありませんでした。しばらく立ってから再度お試しください。')
            }else{
                notify('AI修正に失敗しました。<br>' + err.message)
            }
            
        } else {
            notify('AI修正に失敗しました。<br>' + err)
        }
    }
}
const checkFields = async() => {
    const targets = [
        startDateRef.value, 
        endDateRef.value, 
        goalContentRef.value, 
        actionPlanRef.value,
        situationRef.value,
        expectedRef.value
    ]
    const validateTargets = targets.filter( target => target !== null)
    let result = true
    for(const target of validateTargets){                
        const val = await target?.validate() || {valid: false}
        result = result && val.valid
    }
    return result
}
const saveOutcomeGoal = async(status: number) => {
    const result = await checkFields()
    let info_message = '保存しました。'
    if(!result) return
    if(status == 2) {
        const answer = await confirm('申請後には編集ができなくなります。よろしいでしょうか？')
        info_message = '申請しました。'
        if(!answer.value) return
    }
    const span = route.params.span as string
    const [year, which_half] = span.split('-')
    const params = {
        
        goal_id: props.editGoalData?.id ?? null,
        checked_items: checkedItems.value,
        date: props.selectedDate.evaluationDate,
        params: {
            project_id: chosenProject.value.id,
            user_id: route.params.memberId,
            start_date: startDate.value,
            end_date: endDate.value,
            year: year,
            which_half: which_half,
            outcome_goal: goalContent.value,
            situation_analysis: situation.value,
            target_value: target.value,
            criteria: evaluationData.value?.current_level || '',
            ai_review: content_review.value,
            expected_effect: expectedEffect.value,
            action_plan: actionPlan.value,
            status: status,
            ai_advice: aiAdvice.value
        }
        
    }
    try {
        await axios.post('/save_project_goal', params).then(res => res.data)
        emit('close')
        refresh()
        getProjects()
        info(info_message)
        badge.getMembersGoalsBadge()
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const getReview = async() => {
    const result = await checkFields()
    if(!result) return
    const full = `
                従業員職務評価基準: 
                ${evaluationData.value?.current_level || '不明'}
                プロジェクト概要: 
                ${chosenProject.value?.overview}
                プロジェクト戦略: 
                ${chosenProject.value?.strategy}
                プロジェクトKGI: 
                ${chosenProject.value?.kgi}
                プロジェクトKPI: 
                ${chosenProject.value?.kpi}
                プロジェクト名前:
                ${chosenProject.value.name}
                これらは会社から提供された情報です。
                当社では、半期の間で、成果目標を設定します。
                成果目標は、職能を参考にしており、その目標が設定した期間内に80％以上達成することが望ましいです。
                以下の内容でふさわしいか判断してください。
                【職能】
                ${evaluationData.value?.current_level || '不明'}

                【設定期間】: ${startDate.value} ~ ${endDate.value}

                【成果目標】： ${goalContent.value}
                
                【状況分析】： ${situation.value}
                
                【行動計画】： ${actionPlan.value}
                
                【期待せれる効果】： ${expectedEffect.value}

                これらは従業員の成果目標情報です。

                反対が難しいばいふさわしいくないといっでください。そして結論からフィードバックください
                
                ** などの記号、リスト、箇条書きを使用せずに、手順を明確に説明して応答します。`
    content_review.value = ''
    reviewLoading.value = true
    const openai = new OpenAI({
        apiKey: import.meta.env.VITE_OPENAI_API_KEY,
        dangerouslyAllowBrowser: true 
    });
    try {
        const response = await openai.chat.completions.create({
            // model: 'gpt-4',
            model: 'gpt-4o-mini',
            messages: [{ role: 'assistant', content: full }],
            stream: true,
            temperature: 0.8
        })
        if (!response || !response[Symbol.asyncIterator]) {
            throw new Error('OpenAI API からの応答が無効です。');
        }
        const stream = response as Stream<OpenAI.Chat.Completions.ChatCompletionChunk>;
            for await (const part of stream) {
                try {
                    const content = part.choices[0]?.delta?.content || ''
                    let before = content_review.value ? content_review.value : ''
                    content_review.value = before + content
                } catch (error) {
                    reviewLoading.value = false
                }
                
                

            }
            reviewLoading.value = false
    } catch (err) {
        if (err instanceof OpenAI.APIError) {
            if(err.status == 500){
                notify('AI修正に失敗しました。<br>ChatGPTサーバーから反応がありませんでした。しばらく立ってから再度お試しください。')
            }else{
                notify('AI修正に失敗しました。<br>' + err.message)
            }
            
        } else {
            notify('AI修正に失敗しました。<br>' + err)
        }
        reviewLoading.value = false
    }
    
    

}
</script>
