<template>
    <div class="overlay">
        <div class="chatCreate kadaiCreate scrollable">
            <div class="recordFormTitle" style="display:flex;">
                <div @click="emit('close')" style="margin: 2px 10px 0 0;" v-if="responsive.mobile">
                    <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>                    
                </div>  
                <p>成果目標作成</p>
                <div v-if="!responsive.mobile" class="cursor-pointer" @click="emit('close')" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div class="kadai-root">
                <div style="margin-top: 10px;">
                    {{ selectedDate.name }}
                </div>
                <div class="si-box">
                    <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">雇用形態（必須）</p>
                    <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="goal_status">
                        <option v-for="status in employementStatus" :value="status">{{ status }}</option>
                    </select>
                </div>
                <div class="si-box">
                    <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">該当部門選択（必須）</p>
                    <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="chosenProject">
                        <option v-for="project in authProjects" :value="project">{{ project.name }}</option>
                    </select>
                </div>
                <div class="si-box">
                    <ItemSelector 
                        place-holder="職務"
                        :options="criteriaMaster"
                        v-model="checkedCriteria"
                        label="level"
                        :reduce="option => option['level']"
                        :close-on-select="true"
                        @search="search"
                        :multiple="false"
                    />
                </div>
                <div class="si-box">
                    <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">AI アドバイス</p>
                    <div v-html="aiAdvice"></div> 
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
                <!-- <div class="si-box">
                    <ShortInput 
                        placeHolder="目標値"
                        type="text"
                        v-model="target"
                        :initialValue="target"
                    />
                </div> -->
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
                    <div style="font-weight: 600;margin-bottom: 20px">AI判定とフィードバック</div>

                    <div style="margin-bottom: 20px" v-html="content_review"></div> 
                    <LoaderButton style="margin: 0" @triggered="getReview" :loading="reviewLoading" :content="'AI判定とフィードバック'"/>                               
                </div>
                <div class="si-box" v-if="content_review" style="justify-content: center;display: flex;gap:15px;flex-wrap: wrap;">
                    <LoaderButton style="margin: 0;" @triggered="saveOutcomeGoal(0)" content="保存" :loading="loading"/>
                    <LoaderButton style="margin: 0;" @triggered="saveOutcomeGoal(2)" content="申請" :loading="loading"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { Project } from '@/interface/projectInterface';
import { useResponsive } from '@/store/responsive';
import { inject, onMounted, ref, markRaw, watch } from 'vue';
import ShortInput from '../Form/ShortInput.vue';
import LongInput from '../Form/LongInput.vue';
import moment from 'moment';
import axios from 'axios';
import { debounce } from '@/utils/tools';
import LoaderButton from '../Global/LoaderButton.vue';
import { Dialog } from '@/interface/globalInterface';
import OpenAI from 'openai';
import { Stream } from 'openai/streaming.mjs';
import { useAuthUserStore } from '@/store/auth';
import ItemSelector from '../Form/ItemSelector.vue';
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
    'memberData'
])
const itemsToCheck = ([
    '具体的かつ明瞭な判断指標である',
    '数値や客観的判断要素を含んでいる',
    '事業戦略・職務基準との関連性がある',
    '期日や期間の指定がある',
    '担当PMとの意識合わせができている'
])
const employementStatus = ([
    '正社員',
    '契約社員'
])
const Deselect = markRaw({
    template: `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 32 32"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>`
}) 
const goal_status = ref(props.memberData?.evaluation?.employment_type ?? '正社員')
const checkedItems = ref([])
const focus = ref([false, false])
const responsive = useResponsive()
const authProjects = inject<authProject>('authProjects')
const startDate = ref(props.editGoalData?.start_date ?? moment().format('YYYY-MM-DD'))
const endDate = ref(props.editGoalData?.end_date ?? '')
const goalContent = ref(props.editGoalData?.outcome_goal ?? '')
const impact = ref('')
const progress = ref('')
const chosenProject = ref(props.selectedProject ?? null)
const criteriaMaster = ref<any>([])
const checkedCriteria = ref(props.memberData?.evaluation?.current_level ?? '')
const content_review = ref(props.editGoalData?.ai_review ?? '')
const aiAdvice = ref('')
const reviewLoading = ref(false)
const loading = ref(false)
const auth = useAuthUserStore()
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
onMounted(() => {
    if(props.selectedDate && !props.editGoalData) {
        
        startDate.value = props.selectedDate.value;
        
    }
    firstFetch()
    getAdvice()
})
watch([checkedCriteria, chosenProject], () => {
    getAdvice()
})
const search = debounce(async(key: string) => {
    try {
        criteriaMaster.value = await axios.post('/get_project_criteria', {keywords: key, first: false}).then(res => res.data)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
    
}, 350)
const firstFetch = async() => {
    try {
        criteriaMaster.value = await axios.post('/get_project_criteria', { first: true }).then(res => res.data)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const getAdvice = async() => {
    const full = `
                職務評価基準: ${checkedCriteria.value}
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
    if(!result) return
    if(!checkedCriteria.value) return
    if(status == 2) {
        const answer = await confirm('申請後には編集ができなくなります。よろしいでしょうか？')
        if(!answer) return
    }
    const params = {
        goal_id: props.editGoalData?.id ?? null,
        checked_items: checkedItems.value,
        date: props.selectedDate.evaluationDate,
        params: {
            project_id: chosenProject.value.id,
            user_id: auth.activeUser.id,
            employment_type: goal_status.value,
            start_date: startDate.value,
            end_date: endDate.value,
            target_period: props.selectedDate.value,
            outcome_goal: goalContent.value,
            situation_analysis: situation.value,
            target_value: target.value,
            criteria: checkedCriteria.value,
            ai_review: content_review.value,
            expected_effect: expectedEffect.value,
            action_plan: actionPlan.value,
            status: status
        }
        
    }
    try {
        await axios.post('/save_project_goal', params).then(res => res.data)
        info('保存しました。')
        emit('close')
        refresh()
        getProjects()
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const getReview = async() => {
    const result = await checkFields()
    if(!result) return
    const full = `
                従業員職務評価基準: 
                ${checkedCriteria.value}
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
                ${checkedCriteria.value}

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
