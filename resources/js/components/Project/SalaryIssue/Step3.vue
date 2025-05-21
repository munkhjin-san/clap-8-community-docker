<template>
    <div style="margin-top: 20px;">
        <AiLoader v-if="aiLoading" message="昇給課題をAIで自動生成中です。<br>この処理には数分かかる場合があります。"/>
        <div v-if="selectedTheme">
            <div @click="emit('next', 1), emit('goback')" class="undo-kadai">
                <svg fill="var(--primary-color)" version="1.1" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                </svg>
                <div>戻る</div>
            </div>
            <div class="selected-theme">
                <div class="mt-[15px]">
                    <div><strong>成果目標</strong></div>
                    <div class="text-[12px] mt-[10px] whitespace-break-spaces">{{ chosenGoal.outcome_goal }}</div>
                </div>
            </div>
            
            <p :class="['form-title-small', 'form-title-active']" style="margin: 10px 0;">選択したテーマ</p>
            <div class="selected-theme">                            
                <div style="margin-top: 15px;">
                    <div><strong>{{ selectedTheme.title }}</strong></div>
                    <div class="text-[12px] mt-[10px] whitespace-break-spaces">{{ selectedTheme.content }}</div>
                </div>
            </div>
            <div class="mt-[30px] p-[20px] bg-[var(--bg3)]">
                
                <p class="text-[11px] mb-[15px] leading-normal">プロジェクトのMISOおよび個人のスキルに基づいた成果目標を、AIを活用して立案することができます。
                    <br>さらに、「カスタマイズ」を通じてAIに必要な情報を提供することで、より具体的で適切な目標設定が可能になります。<br>
                </p>
                
                <div>
                    <div class="si-box">
                        <LongInput 
                            placeHolder="課題内容および生成指示のカスタマイズ"
                            type="text"
                            v-model="custom_instruction"
                            custom-class="height-adjust"
                        />
                    </div>

                </div>
                <div class="si-box">                    
                    <LoaderButton :loading="aiLoading" content="昇給課題提案作成" style="margin: 0; margin-top: 15px;" @triggered="getAdvice"/>
                </div>      
            </div>


            <div v-if="release">
                <div class="si-box" style="background: var(--background-color);">
                    <ShortInput
                        :initialValue="title"
                        ref="kadaiTitle"
                        placeHolder="タイトル"
                        name="kadaiTitle"
                        rules="required|max:250"
                        label="タイトル"
                        v-model="title"
                    />
                </div>
                <div class="si-box" style="background: var(--background-color);">
                    <ShortInput
                        :initialValue="content_goal"
                        ref="kadaiSkillRef"
                        placeHolder="開発能力"
                        name="kadaiSkill"
                        rules="required"
                        label="開発能力"
                        v-model="content_goal"
                    />
                </div>
                <div class="si-box">
                    <p class="mb-[20px]">能力評価基準</p>
                    <div class="flex flex-col gap-[20px]">
                        <div v-for="action in actions">
                            <ShortInput
                                ref="kadaiActionRef"
                                name="kadaiAction"
                                rules="required"
                                label="タイトル"
                                v-model="action.content"
                            />
                        </div>

                    </div>

                </div>
                <!-- <div class="si-box" style="background: var(--background-color);">
                    <LongInput
                        :initialValue="content"   
                        ref="kadaiContent"
                        :placeHolder="`昇給課題内容・詳細`"
                        name="kadaiContent"
                        rules="required"
                        label="タイトル"
                        v-model="content"
                        :key="keys.content"
                    />
                </div> -->


                
                <div v-if="release && !aiLoading" class="si-box justify-center flex gap-[15px] flex-wrap">
                    <LoaderButton v-if="!reviewLoading" style="margin: 0" :loading="saving" @triggered="saveTemplateConfirm" content="保存"/>
                    <LoaderButton v-if="!reviewLoading" style="margin: 0" :loading="attaching" @triggered="applyToManagementConfirm" content="申請"/>
                    
                </div>
            </div>

        </div>
    </div>
</template>
<script lang="ts" setup>
import { ref, inject, onMounted, useTemplateRef } from 'vue';
import { Dialog } from '@/interface/globalInterface';
import OpenAI from "openai";
import axios from 'axios';
import ShortInput from '@/components/Form/ShortInput.vue';
import LongInput from '@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useBadgeStore } from '@/store/badge';
import { useAuthUserStore } from '@/store/auth';
import { useProject } from '@/composables/project';
import { useRoute } from 'vue-router';
import { EvaluationRecord, EvaluationSkill } from '@/interface/evaluationInterface';
import AiLoader from '@/components/Global/AiLoader.vue';
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
    'evaluation'
])

const reviewLoading = ref(false)
const title = ref(props.editData?.title ?? '')
const content = ref(props.editData?.content ?? '')
const content_goal = ref(props.editData?.ability ?? '')
const content_review = ref(props.editData?.review ?? '')
const custom_instruction = ref('')
const evaluationData = ref<EvaluationRecord | null>(null)
const aiLoading = ref(false)
const baseSkills = ref<string[]>([])
const aiType = ref('openai')
const { memberData } = useProject()
const actions = ref(props.editData?.actions ?? [])
const saving = ref(false)
const attaching = ref(false)
const badge = useBadgeStore()
const { notify, confirm, info } = inject<Dialog>('dialog')!
const refresh = inject('refresh') as Function
const kadaiContent = ref<InstanceType<typeof LongInput> | null>(null)
const kadaiTitle = ref<InstanceType<typeof ShortInput> | null>(null)
const kadaiGoal = ref<InstanceType<typeof LongInput> | null>(null)
const kadaiActionRef = useTemplateRef<InstanceType<typeof ShortInput>[]>('kadaiActionRef')
const kadaiSkillRef = useTemplateRef<InstanceType<typeof ShortInput>>('kadaiSkillRef')
const release = ref(props.editData && props.editData.id ? true : false)
const keys = ref({
    content: 0,
    content_goal: 0
})
const route = useRoute()
onMounted(() => {
    // if(props.selectedTheme){
    //     getAdvice()
    //     console.log(evaluationDate.value)
    // }
    getEvaluationData()

})
const getEvaluationData = async() => {

    try {
        const span = route.params.span as string
        const [year, which_half] = span.split('-')
        const response = await axios.post('/get_evaluation_data', {
            user_id: memberData.value?.id,
            year: year,
            which_half: which_half  
        }).then(res => res.data)
        evaluationData.value = response && response.evaluation ? response.evaluation : null
        baseSkills.value = response && response.base_skills ? response.base_skills  : []
        
    } catch (e) {
        // notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}

const schema = (model) => {
    let schema = {
        "type": "object",
        "properties": {
            "title": {
                "type": "string",
                "description": "昇給課題タイトル"
            },
            "actions": {
                "type": "array",
                "description": "開発能力が実践的に発揮されたことを確認できる具体的な行動",
                "properties": {
                    "content": {
                        "type": "string",
                        "description": "行動内容"
                    }
                },
                required: ["content"],
                additionalProperties: false
            },
            "skill_theme": {
                "type": "string",
                "description": "習得を目指す能力・スキルをキーワードで提示"
            }
        },
        required: ["title", "content", "content_goal"]
    }
    if(model == 'openai'){
        schema['additionalProperties'] = false
    }
    return schema
}
const systemInstruction = () => {
    return `
        社内で成果目標に基づいて昇給課題を作成しようとしています。
        本機能は、昇給判断に必要な「能力開発課題」をAIによって自動生成するものです。  
        構成は以下の3点で構成され、**開発能力を1件に絞り、その構成要素（能力評価基準）を5件提示**することで、スキルの定着度を立体的かつ明確に評価可能とします。

        昇給課題の評価は、該当する成果目標（KGI＋KPI）が100％達成されたことを前提とし、さらに能力評価基準5件中3件以上が「修得済」と判定された場合に「昇給対象」とします。

        挙げられるデータは以下の通りです。
        1. テーマ: 
        2. テーマ説明:
        3. 職能レベル: 
        4. 職能レベル保留スキル:
        5. 目標:
        6. 期待される効果:
        7. 目標期間:
        8. 目標達成するためのKGI:

        ## ■ 出力構成（昇給課題）

        ### 1. 昇給課題タイトル（能力開発の方向性）（json-index: title）
        - 内容：目指す人物像やスキル像を1文で表現（20〜30文字程度）
        - 例：「状況を構造的に捉え、仮説で動ける力を身につける」


        ### 2. 開発能力（1件）（json-index: skill_theme）
        - 内容：昇給判断において評価対象とする主要能力（例：仮説構築力、論点整理力、傾聴力など）
        - 補足（任意）：能力の定義（1行）
        ### 3. 能力評価基準（構成要素5件）（json-index: actions)
        - 各開発能力を構成する5つのサブスキル・構成要素を明示
        - 各項目は以下の条件で出力する：
        - 各構成要素に対して「観察可能な行動・兆候・言動」の形で記述する
        - あいまいな表現（例：意識する、努力する）は禁止
        - 1行（最大2行）で評価者が確認できるように表現
        - 出力形式（例）：
        【能力評価基準】

        1. 情報整理力：得られた情報を因果関係や優先度で構造化している
        2. 問題抽出力：表層的な要望の背後にある課題構造を言語化できている
        3. 仮説立案力：不明確な状況においても筋の通った仮説を提示できている
        4. 検証設計力：仮説を前提に検証プロセス（観察・質問・実験）を設計している
        5. 構造的表現力：提案や報告を背景→論点→結論の構造で説明できている



        課題を生成するには次の要件を満たす必要があります。
        1. テーマとテーマの説明を理解し、テーマに沿った開発能力を提案してください。。
        2. 職能レベルと職能レベル保留スキルを考慮し、{skill_theme）}には今保留スキル以外、目標を達成するために必要とするスキルを考えてください。
        ユーザーの希望やカスタマ指示があれば、できるだけそれに従ってください。
        markdownを利用しないでください。
        markdownを利用しないでください。
        **などmarkdwonを利用しないでください。
        `
}
const getAdvice = async() => {
    if(content_goal.value || actions.value.length) {
        const answer = await confirm('既存の課題がある場合、AIで生成した課題は上書きされます。よろしいでしょうか？')
        if(!answer.value) return
    }
    const checkList = evaluationData.value?.checklist.flatMap((item: EvaluationSkill) => item.content).join('\n ')
    const userDetail = `
        職能レベル: ${evaluationData.value?.current_level}
        職能レベル保留スキル: \n ${checkList}
    `
    const goalDetail = `
        目標タイトル: ${props.chosenGoal.title}
        目標: ${props.chosenGoal.outcome_goal}
        目標説明: ${props.chosenGoal.miso}
        期待される効果: ${props.chosenGoal.expected_effect}
        目標期間: ${props.chosenGoal.start_date}~${props.chosenGoal.end_date}
        目標達成するためのKGI: ${props.chosenGoal.kgi}
        目標達成するためのKPI: ${props.chosenGoal.steps.map((item: any) => item.content).join('\n ')}
    `
    let combined = `
        テーマ: ${props.selectedTheme.title_full}
        テーマ説明: ${props.selectedTheme.content}\n\n
        ${userDetail}\n\n
        ${goalDetail}\n\n 
        ユーザーの希望やカスタマ指示 : ${custom_instruction.value}       
    `
    console.log(combined)

    if(aiType.value == 'openai'){ 

        try{
            aiLoading.value = true
            const openai = new OpenAI({
                apiKey: import.meta.env.VITE_OPENAI_API_KEY,
                dangerouslyAllowBrowser: true 
            });   
            const response = await openai.responses.create({
                model: "gpt-4.1-mini",
                input: [
                    {
                        "role": "system",
                        "content": [
                            {
                                "type": "input_text",
                                "text": systemInstruction()
                            }
                        ]
                    },
                    {
                        "role": "user",
                        "content": [
                            {
                                "type": "input_text",
                                "text": combined
                            }
                        ]
                    }
                ],
                text: {
                    "format": {
                        "type": "json_schema",
                        "name": "salary_issue_creation",
                        "strict": true,
                        "schema": {
                            "type": "object",
                            "properties": {
                                "title": {
                                    "type": "string",
                                    "description": "昇給課題のタイトル"
                                },
                                "skill_theme": {
                                    "type": "string",
                                    "description": "昇給判断において評価対象とする主要能力"
                                },
                                "actions": {
                                    "type": "array",
                                    "description": "開発能力を構成するサブスキル・構成要素を明示",
                                    "items": {
                                        "type": "string"
                                    }
                                }
                            },
                            "required": [
                                "title",
                                "skill_theme",
                                "actions"
                            ],
                            "additionalProperties": false
                        }
                    }
                },

            });
            if(response.output[0].type == 'message' && response.output[0].content[0].type == "output_text"){

                const parsedData = JSON.parse(response.output[0].content[0].text);
                console.log(parsedData)
                title.value = parsedData.title || ''
                content_goal.value = parsedData.skill_theme || ''
                actions.value = parsedData.actions.map((item: any) => {
                    return {
                        content: item
                    }
                })
                // if(parsedData.title){
                //     title.value = parsedData.title
                // }
                // if(parsedData.content){
                //     content.value = parsedData.content
                // }
                // if(parsedData.content_goal){
                //     content_goal.value = parsedData.content_goal
                // }

                keys.value.content++
                keys.value.content_goal++
                finalize()

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

        } finally{
            aiLoading.value = false
            release.value = true
        }
    }
}
const checkFields = async() => {
    const targets = [
        kadaiGoal.value, 
        kadaiTitle.value, 
        kadaiSkillRef.value,
    ]
    const validateTargets = targets.filter( target => target !== null)
    let result = true
    for(const target of validateTargets){                
        const val = await target?.validate() || {valid: false}
        result = result && val.valid
    }
    if(kadaiActionRef.value){
        for(const target of kadaiActionRef.value){
            const val = await target?.validate() || {valid: false}
            result = result && val.valid
        }
    }
    return result
}
const saveTemplateConfirm = async() => {    
    saveTemplate(null, 0)        
}
const saveTemplate = async(_action, status) => {
    const result = await checkFields()
    if (!result) {
        notify('必須項目が未入力です。')
        return
    }
    try{
        saving.value = true
        const params = {
            editId: props.editData?.id ?? null,
            title: title.value,
            issue_content: content.value,
            goal_id: props.chosenGoal?.id,
            review: null,
            ability: content_goal.value,
            theme: props.selectedTheme.title_full,
            date: props.selectedDate.evaluationDate,
            status: status,
            user_id: memberData.value?.id,
            mentor_id: props.evaluation?.mentor_id,
            actions: actions.value,
        }
        await axios.post('/save_kadai_template', params)
            info(status == 2 ? '申請しました。' : '保存しました。')
            title.value = content.value = content_goal.value = ''
            refresh()
            emit('close')

            badge.getSalaryIssueBadge()
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } finally {
        saving.value = false
    }

}
const applyToManagementConfirm = async() => {
    
    
    const answer = await confirm('申請後には編集ができなくなります。よろしいでしょうか？')
    if(!answer.value) return
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
const finalize = () => {
    info('AI生成が完了しました。内容を確認してください。')
    setTimeout(() => {
        kadaiContent.value?.$el.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest'
        })
    }, 100);
}
</script>