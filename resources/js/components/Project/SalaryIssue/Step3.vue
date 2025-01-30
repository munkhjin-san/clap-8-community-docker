<template>
    <div style="margin-top: 20px;">
        <div v-if="selectedTheme">
            <div @click="emit('next', 1), emit('goback')" class="undo-kadai">
                <svg fill="var(--primary-color)" version="1.1" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                </svg>
                <div>戻る</div>
            </div>
            <div class="selected-theme">
                <div style="margin-top: 15px;">
                    <div><strong>成果目標</strong></div>
                    <div style="font-size: 12px;margin-top: 10px;white-space: break-spaces;">{{ chosenGoal.outcome_goal }}</div>
                </div>
            </div>
            
            <p :class="['form-title-small', 'form-title-active']" style="margin: 10px 0;">選択したテーマ</p>
            <div class="selected-theme">                            
                <div style="margin-top: 15px;">
                    <div><strong>{{ selectedTheme.title }}</strong></div>
                    <div style="font-size: 12px;margin-top: 10px;white-space: break-spaces;">{{ selectedTheme.content }}</div>
                </div>
            </div>
            <div class="si-box">
                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">AI アドバイス</p>
                <div style="white-space: break-spaces" v-html="aiAdvice"></div> 
            </div>
            <div class="si-box" style="background: var(--background-color);">
                <ShortInput
                    :initialValue="title"
                    ref="kadaiTitle"
                    placeHolder="昇給課題タイトル"
                    name="kadaiTitle"
                    rules="required|max:250"
                    label="タイトル"
                    v-model="title"
                />
            </div>
            <div class="si-box" style="background: var(--background-color);">
                <LongInput
                    :initialValue="content"   
                    ref="kadaiContent"
                    :placeHolder="`昇給課題内容・詳細`"
                    name="kadaiContent"
                    rules="required"
                    label="タイトル"
                    v-model="content"
                />
            </div>
            <div class="si-box" style="background: var(--background-color);">
                <LongInput
                    :initialValue="content_goal"   
                    ref="kadaiGoal"
                    :placeHolder="`課題達成による取得能力`"
                    name="kadaiGoal"
                    rules="required"
                    label="タイトル"
                    v-model="content_goal"
                />
            </div>
            <div style="background: var(--bg3);padding: 20px;margin-top: 20px;">
                <div style="font-weight: 600;margin-bottom: 20px">AI判定とフィードバック</div>

                <div style="margin-bottom: 20px" v-html="content_review"></div> 
                <LoaderButton style="margin: 0" @triggered="getReview" :loading="reviewLoading" :content="'AI判定とフィードバック'"/>                               
            </div>
            
            <div v-if="content_review" class="si-box" style="justify-content: center;display: flex;gap:15px;flex-wrap: wrap;">
                <LoaderButton v-if="!reviewLoading" style="margin: 0" :loading="saving" @triggered="saveTemplateConfirm" content="保存"/>
                <LoaderButton v-if="!reviewLoading" style="margin: 0" :loading="attaching" @triggered="applyToManagementConfirm" content="申請"/>
                
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { ref, inject, onMounted, watch } from 'vue';
import { Dialog } from '@/interface/globalInterface';
import OpenAI from "openai";
import { Stream } from 'openai/streaming.mjs';
import axios from 'axios';
import moment from 'moment';
import ShortInput from '@/components/Form/ShortInput.vue';
import LongInput from '@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useBadgeStore } from '@/store/badge';
const emit = defineEmits([
    'close', 
    'next',
    'goback'
])
const props = defineProps([
    'getIssues', 
    'editData', 
    'selectedTheme',
    'chosenGoal',
    'selectedDate',
    'memberData',
    'evaluation'
])
interface Date {
    value: string;
}
const reviewLoading = ref(false)
const title = ref(props.editData?.title ?? '')
const content = ref(props.editData?.content ?? '')
const content_goal = ref(props.editData?.ability ?? '')
const content_review = ref(props.editData?.review ?? '')
const aiAdvice = ref('')
const evaluationDate = inject('evaluationDate') as Date
const saving = ref(false)
const attaching = ref(false)
const badge = useBadgeStore()
const { notify, confirm, info } = inject<Dialog>('dialog')!
const refresh = inject('refresh') as Function
const kadaiContent = ref<InstanceType<typeof LongInput> | null>(null)
const kadaiTitle = ref<InstanceType<typeof ShortInput> | null>(null)
const kadaiGoal = ref<InstanceType<typeof LongInput> | null>(null)
onMounted(() => {
    if(props.selectedTheme){
        getAdvice()
        console.log(evaluationDate.value)
    }
})
const getAdvice = async() => {
    const full = `
                    昇給問題は従業員の成果目標と結びつく必要がある
                    成果目標: ${props.chosenGoal.outcome_goal}、
                    従業員が選択したテーマは成果目標と一致している必要がある
                    選択したテーマ: ${props.selectedTheme?.title}${props.selectedTheme.content}
                    給与問題を作成するには、給与増加課題のタイトル、給与増加課題の内容と詳細、タスク完了を通じてスキルを習得する能力が必要です。
                    昇給課題を作成するためのアドバイスは何ですか?
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
const getReview = async(data) => {
    const result = await checkFields()
    if (!result) return
    const headTemplate = props.selectedTheme.title_full
    const full = `
                予め会社が定義してる要件は${headTemplate}${props.selectedTheme.content}
                当社の設定する要件に則り、昇給課題の設定内容がふさわしいものであるかを判定してください。昇給課題は、個人の成果目標を100％達成するために必要な基本能力を向上させることを目的として設定されており、達成基準を満たせば昇給します。

                具体的には、目標のレベルは以下の要件を満たす必要があります：
                1. 期間: ${props.chosenGoal.start_date}~${props.chosenGoal.end_date}。
                2. 成果目標: ${props.chosenGoal.outcome_goal}。
                3. 昇給課題タイトル: ${title.value} 
                4. 昇給課題内容・詳細: ${content.value} 
                5. 課題達成による取得能力: ${content_goal.value}

                以上の要件に基づき、昇給課題が適切かどうかを判定してください。
    
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
const checkFields = async() => {
    const targets = [
        kadaiContent.value, 
        kadaiTitle.value, 
        kadaiGoal.value, 
    ]
    const validateTargets = targets.filter( target => target !== null)
    let result = true
    for(const target of validateTargets){                
        const val = await target?.validate() || {valid: false}
        result = result && val.valid
    }
    return result
}
const saveTemplateConfirm = async() => {
    if(props.editData.id){
        const editRecord = props.editData
        if(editRecord && editRecord.content !== content.value && editRecord.review && editRecord.review == content_review.value){
            const answer = await confirm('昇給課題の内容に変更がある場合、再度AI分析を行ってください。<br>このまま保存すると現在の添削結果は削除されます。')
            if(!answer) return
            saveTemplate('empty_review', 0)
        }else{
            saveTemplate(null, 0)
        }
    }
    else{
        saveTemplate(null, 0)
        
    }
}
const saveTemplate = async(action, status) => {
    const result = await checkFields()
    if (!result) return
    try{
        saving.value = true
        const params = {
            editId: props.editData?.id ?? null,
            title: title.value,
            issue_content: content.value,
            goal_id: props.chosenGoal?.id,
            review: action && action == 'empty_review' ? null : content_review.value,
            ability: content_goal.value,
            theme: props.selectedTheme.title_full,
            date: props.selectedDate.evaluationDate,
            status: status,
            user_id: props.memberData?.id,
            mentor_id: props.evaluation?.mentor_id
        }
        await axios.post('/save_kadai_template', params)
            info(status == 2 ? '申請しました。' : '保存しました。')
            title.value = content.value = content_goal.value = ''
            refresh()
            emit('close')
            badge.getProjectBadge()
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally {
        saving.value = false
    }

}
const applyToManagementConfirm = async() => {
    
    if(!content_review.value){
        notify('申請する前にAI分析を完了してください。')
        return
    }
    
    const answer = await confirm('申請後には編集ができなくなります。よろしいでしょうか？')
    if(!answer) return
    await saveTemplate(null, 2)
}
const applyToManagement = async() => {
    try{
        attaching.value = true
        await axios.put('/apply_kadai', {record_id: props.editData?.id})
        refresh()
        emit('close')
        info('申請しました。')
        
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally { 
        attaching.value = false
    }
}
</script>