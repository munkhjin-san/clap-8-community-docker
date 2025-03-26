<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>{{ editData ? '作業を編集する' : '新しい作業を作成する' }}</p>
            
        </template>
        <template #menu>
            <CommandButton :buttons="[{title: 'リスク評価・対策の自動生成', action: () => generateRiskAssessment()}]"/> 
        </template>
        <template #content>
            <AiLoader v-if="reviewCreating" message="リスク評価・対策の自動生成中..."/>
            <Teleport to="body">
                <Modal v-if="aiResponse.active && aiResponse.rule" @close="resetAiResponse">
                <template #title>
                    <div class="text-[20px]">リスク評価・対策の自動生成</div>
                    </template>
                    <template #content>
                        <div class="leading-normal mt-[20px]">
                            <p v-html="aiResponse.response.business_description"></p>
                            <div class="si-box text-[14px]">
                                <div class="si-box-title">リスク</div>
                                <div class="mt-[10px] flex flex-col gap-[30px]">
                                    <div v-for="(risk, index) in aiResponse.response.risks" class="flex flex-col gap-[10px]">
                                        <div>
                                            <strong>リスク{{ index + 1 }}</strong>
                                            <p>{{ risk.risk_description }}</p>
                                        </div>
                                        <!-- <div>
                                            被害レベル:{{ risk.damage_level }}
                                        </div>
                                        <div>
                                            リスクレベル:{{ risk.risk_level }}
                                        </div> -->
                                        <div>
                                            <p class="mb-[15px]"><strong>対策</strong></p>
                                            <ul >
                                                <li v-for="measure in risk.mitigation_measures">{{ measure.measure_description }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="si-box flex flex-wrap gap-[10px]">
                            <LoaderButton content="再生成" @triggered="generateRiskAssessment()"/>
                            <LoaderButton content="適用" @triggered="insertRisk" :loading="false"/>
                        </div>
                    </template>
                </Modal>
            </Teleport>
            <div v-if="params.job">
                <div class="si-box" v-for="(job, key) in params.job">
                    <div v-if="requiredTargets.includes(key.toString())">
                        <LongInput 
                            v-if="requireLong.includes(key.toString())"
                            v-model="params.job[key]"
                            :place-holder="key.toString()"
                            :key="updaterKey"
                        />
                        <ShortInput 
                            v-else-if="!numberValueTargets.includes(key.toString())"
                            v-model="params.job[key]"
                            :place-holder="key.toString()"
                            type="text"
                        />
                    </div>


                </div>
            </div>


            <div class="si-box">
                <LoaderButton content="保存する" :loading="loading" @triggered="sendRule"/>
            </div>            
        </template>

    </Modal>
</template>
<script setup lang="ts">
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '../../../Global/Modal.vue';
import { Manual, Rule } from '@/interface/operation';
import ShortInput from '@/components/Form/ShortInput.vue';
import { inject, reactive, ref, toRaw } from 'vue';
import LongInput from '@/components/Form/LongInput.vue';
import axios from 'axios';
import { DialogMethods } from '@/interface/globalInterface';
import 'styles/customForm.css'
import CommandButton from '@/components/Global/CommandButton.vue';
import AiLoader from '@/components/Global/AiLoader.vue';
import OpenAI from 'openai';
const props = defineProps<{
    editData: {
        manual: Manual | null;
        job: Rule | null;
    };
}>();

const emit = defineEmits<{
    close: [flag: boolean]
}>()
const { notify, info, confirm } = inject('dialog') as DialogMethods
const requireLong = ['作業詳細', 'リスク', 'リスク対策']
const requiredTargets = [
    '作業',
    '作業詳細',
    '持ち出し備品利用ツール',
    '対応者・対応部署',
    'リスク',
    'リスク対策',
    '期日',
    'リスクレベル',
    '損害レベル',
]
const numberValueTargets = [
    'リスクレベル',
    '損害レベル',
]
const params = reactive<Partial<Rule>>(props.editData.job ? structuredClone(toRaw(props.editData.job)) : {
    id: '',
    job: { 
        '作業': '',
        '作業詳細': '',
        '持ち出し備品利用ツール': '',
        '対応者・対応部署': '',
        'リスク': '',
        'リスク対策': '',
        '期日': '',          
    }
})
const reviewCreating = ref<boolean>(false);
const loading = ref(false)
const aiResponse = reactive<{
    rule: Partial<Rule> | null;
    response: {
        business_description: string;
        risks: {
            risk_description: string;
            damage_level: number
            risk_level: number
            mitigation_measures: {
                measure_description: string;
            }[]

        }[]
    };
    active: boolean;
}>({
    rule: null,
    response: {
        business_description: '',
        risks: []
    },
    active: false,
})
const updaterKey = ref(0)
const sendRule = async() => {
    loading.value = true
    try {
        const data = {
            manual_id: props.editData.manual?.id,
            job: params
        }
        await axios.post('/create_manual_rule', data)
        info('保存しました。')
        loading.value = false
        emit('close', true)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        loading.value = false
    }
}

const insertRisk = async() => {
    if(params.job){
        let actionType = 'overwrite'
        if(params.job['リスク'] || params.job['リスク対策']){
            const answer = await confirm('リスク、リスク対策は既に入力されています。',
            {answers: [
                {label: '上書き', value: 'overwrite'},
                {label: '追加', value: 'append'},
                {label: 'キャンセル', value: 'cancel'},

            ]})
            console.log(answer)
            if(!answer.value || answer.value === 'cancel') return
            
            actionType = answer.value
        }
        const reviewRisk = aiResponse.response.risks.map(risk => risk.risk_description).join('\n')
        const reviewMeasures = aiResponse.response.risks.map(risk => risk.mitigation_measures.map(measure => measure.measure_description).join('\n')).join('\n')
        const riskOp = {
            overwrite: reviewRisk, 
            append: params.job['リスク'] ? params.job['リスク'] + '\n' + reviewRisk : reviewRisk
        }
        const measuresOp = {
            overwrite: reviewMeasures, 
            append: params.job['リスク対策'] ? params.job['リスク対策'] + '\n' + reviewMeasures : reviewMeasures
        }
        params.job['リスク'] = riskOp[actionType]
        params.job['リスク対策'] = measuresOp[actionType]
        updaterKey.value++

        resetAiResponse()
    }
}
const resetAiResponse = () => {
    aiResponse.rule = null;
    aiResponse.response = {
        business_description: '',
        risks: []
    }
    aiResponse.active = false
}
const generateRiskAssessment = async() => {
    if(!params.job) return
    const jobTitle = params.job['作業'] || ''
    const jobDetail = params.job['作業詳細'] || ''
    const tool = params.job['持ち出し備品利用ツール'] || ''
    if(!jobTitle || !jobDetail){
        notify('作業、作業詳細を入力してください。')
        return
    }
    try{
        resetAiResponse()
        reviewCreating.value = true
        const openai = new OpenAI({
            apiKey: import.meta.env.VITE_OPENAI_API_KEY,
            dangerouslyAllowBrowser: true 
        });       
        const assistant = await openai.beta.assistants.retrieve("asst_hsQ7RFefRtwW39fqB7FMp34P");
        const thread = await openai.beta.threads.create();
        const neededKeys = ['作業', '作業詳細', '持ち出し備品利用ツール',]
        let text = ''
        for (const [key, value] of Object.entries(params.job)) {        
            if (neededKeys.includes(key)){
                text += `${key} : ${value ? value : 'なし'}\n`
            }
        }
        await openai.beta.threads.messages.create(thread.id, { role: "user", content: text });
        let run = await openai.beta.threads.runs.createAndPoll(
            thread.id,
            { 
                assistant_id: assistant.id,
            }
        );
        if (run.status === 'completed') {
            const messages = await openai.beta.threads.messages.list(
                run.thread_id
            );
            for (const message of messages.data.reverse()) {            
                if(message.role == 'assistant'){                
                    try{
                        if (message.content[0].type == 'text') {
                            console.log(`${message.role} > ${message.content[0].text.value}`);
                            const rawText = message.content[0].text.value
                            const jsonData = JSON.parse(rawText)
                            aiResponse.rule = params
                            aiResponse.response = jsonData
                            aiResponse.active = true
                            reviewCreating.value = false
                            console.log(jsonData)
                        }
                    }catch(err){
                        notify('OpenAIレスポンスの解析に失敗しました。')
                        reviewCreating.value = false
                        return
                    }
                }
            }
        } 
    }catch(err){
        if (err instanceof OpenAI.APIError) {
            console.log(err.status); 
            console.log(err); 
            if(err.status == 500){
                notify('タスクの自動生成に失敗しました。<br>OpenAIサーバーから反応がありませんでした。しばらく立ってから再度お試しください。')
            }else{
                notify('タスクの自動生成に失敗しました。>' + err?.message)
            }
            
        } else {
            notify('タスクの自動生成に失敗しました。<br>' + err)
        }
        reviewCreating.value = false
    }


}
</script>