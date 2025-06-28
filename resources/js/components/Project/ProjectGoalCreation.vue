<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>{{editGoalData && editGoalData.id ? '成果目標編集' : '成果目標作成'}}</p>
        </template>
        <template #content>
            <AiLoader v-if="aiLoading" message="成果目標をAIで自動生成中です。<br>この処理には数分かかる場合があります。"/>
            <div>
                期間: {{ selectedDate.name }}
            </div>
            <div class="si-box">
                <p :class="['form-title-small', 'form-title-active', 'mb-[20px]' ]">該当部門選択（必須）</p>
                <select class="dropDownSelector taskDateTimePicker" style="max-width: 100%;" v-model="selectedProject">
                    <option v-for="project in usersProjects" :value="project">{{ project.name }}</option>
                </select>
            </div>
            <div class="si-box">
                <p v-html="`職能レベル : <strong>${evaluationData?.current_level || '未設定'}</strong>`"></p>
            </div>
            <div class="si-box" v-if="evaluationData && evaluationData.checklist">
                <div class="mb-[10px]">保有能力</div>
                <div class="flex flex-col gap-[10px] text-[13px] leading-normal">
                    <div v-for="skill in baseSkills">
                        <div class="flex gap-3">
                            <div>
                                <svg v-if="Array.isArray(evaluationData?.checklist) && evaluationData.checklist.map(d => d.content.replace(/ /g, '')).includes(skill.replace(/ /g, ''))"
                                    fill="var(--primary-color)" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                    height="10" viewBox="0 0 38 32">
                                    <path
                                        d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z">
                                    </path>
                                </svg>
                                <svg v-else version="1.1" fill="gray" xmlns="http://www.w3.org/2000/svg"
                                    height="10" viewBox="0 0 32 32">
                                    <path
                                        d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z">
                                    </path>
                                </svg>
                            </div>

                            <div
                                :class="Array.isArray(evaluationData?.checklist) && evaluationData.checklist.map(d => d.content.replace(/ /g, '')).includes(skill.replace(/ /g, '')) ? 'text-[var(--primary-color)]' : 'text-[gray]'">
                                {{ skill }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-[30px] p-[20px] bg-[var(--bg3)]">
                
                <p class="text-[11px] mb-[15px] leading-normal">プロジェクトのMISOおよび個人のスキルに基づいた成果目標を、AIを活用して立案することができます。
                    <br>さらに、「カスタマイズ」を通じてAIに必要な情報を提供することで、より具体的で適切な目標設定が可能になります。<br>
                </p>
                
                <div>
                    <div class="si-box">
                        <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">期日</p>
                        <div style="display:flex;position: relative;width:100%">
                            <ShortInput 
                                name="startDate" 
                                :rules="'required'"
                                customClass="date"
                                ref="startDateRef"
                                type="date"
                                v-model="goalParams.start_date"
                            />
                            <div style="align-self: center;margin: 0 20px;font-size: 14px;color: gray;">ー</div>
                            <ShortInput 
                                name="endDate" 
                                :rules="'required'"
                                customClass="date"
                                ref="endDateRef"
                                type="date"
                                v-model="goalParams.end_date"
                            />
                        </div>
                    </div>
                    <div class="si-box">
                        <LongInput 
                            placeHolder="目標内容（非公開）"
                            type="text"
                            v-model="goalParams.private_memo"
                            custom-class="height-adjust"
                        />
                    </div>
                    <div class="si-box">
                        <LongInput 
                            placeHolder="生成指示のカスタマイズ"
                            type="text"
                            v-model="goalParams.custom_instruction"
                            custom-class="height-adjust"
                        />
                    </div>

                </div>
                <div class="si-box">
                    <!-- <div class="mb-20px flex gap-[15px]">
                        <label class="flex items-center gap-[10px] text-[12px]">
                            <input type="radio" name="type_model" class="custom-f-radio" value="openai" v-model="aiType">
                            <span>OpenAI</span>
                        </label>
                        <label class="flex items-center gap-[10px] text-[12px]">
                            <input type="radio" name="type_model" class="custom-f-radio" value="gemini" v-model="aiType">
                            <span>Gemini</span>
                        </label>

                    </div> -->
                    <LoaderButton :loading="aiLoading" content="成果目標提案作成" style="margin: 0; margin-top: 15px;" @triggered="getAdvice">
                        <template #icon>
                            <AiIcon :size="20" fill="#fff" class="mr-[5px]"/>
                        </template>
                    </LoaderButton>
                </div>

      
            </div>
            <div v-if="release">            
                <div class="si-box" style="margin-bottom: 10px;">
                    目標設定フォーム
                </div>
                <ShortInput 
                    place-holder="タイトル"
                    name="goalTitle" 
                    :rules="'required'"
                    ref="goalTitleRef"
                    type="text"
                    v-model="goalParams.title"
                />

                <div class="si-box">
                    <LongInput 
                        placeHolder="MISO"
                        type="text"
                        v-model="goalParams.miso"
                        rules="required"
                        ref="misoRef"
                        custom-class="height-adjust"
                        :key="keys.miso"
                    />
                </div>
                <div class="si-box">
                    <LongInput 
                        placeHolder="KGI"
                        type="text"
                        v-model="goalParams.kgi"
                        rules="required"
                        ref="kgiRef"
                        custom-class="height-adjust"
                        :key="keys.kgi"
                    />
                </div>
                <div class="si-box">
                    <p class="mb-[15px]">KPI</p>
                    <div class="flex flex-col gap-[25px]">
                        <div v-for="(step, index) in goalParams.steps" :key="index" class="flex w-full">     
                            <div class="flex items-center w-full gap-[10px]">
                                <ShortInput type="text" v-model="step.content" custom-class="full minimal" ref="kpiRef" rules="required" class="w-full"/>                       
                            </div>             
                        </div>
                    </div>
                </div>

                
                <div class="si-box">
                    <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">期日</p>
                    <div style="display:flex;position: relative;width:100%">
                        <ShortInput 
                            name="startDate" 
                            :rules="'required'"
                            :initialValue="goalParams.start_date"
                            customClass="date"
                            ref="startDateRef"
                            type="date"
                        />
                        <div style="align-self: center;margin: 0 20px;font-size: 14px;color: gray;">ー</div>
                        <ShortInput 
                            name="endDate" 
                            :rules="'required'"
                            :initialValue="goalParams.end_date"
                            customClass="date"
                            ref="endDateRef"
                            type="date"
                        />
                    </div>
                </div>
            </div>
            <div v-if="release" class="si-box justify-center flex gap-[15px] flex-wrap">
                <LoaderButton style="margin: 0;" @triggered="saveOutcomeGoal(0)" content="保存" :loading="loading"/>
                <LoaderButton style="margin: 0;" @triggered="saveOutcomeGoal(2)" content="申請" :loading="loading"/>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { ProjectGoal } from '@/interface/projectInterface';
import { inject, onMounted, reactive, ref, useTemplateRef } from 'vue';
import ShortInput from '../Form/ShortInput.vue';
import LongInput from '../Form/LongInput.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useBadgeStore } from '@/store/badge'
import { useRoute } from 'vue-router';
import { EvaluationRecord, EvaluationSkill } from '@/interface/evaluationInterface';
import Modal from '../Global/Modal.vue';
import OpenAI from 'openai';
import GoalGenerateFormat from '../../../assets/GoalGenerateFormat.json'
import { DateTime } from 'luxon';
import { useProject } from '@/composables/project';
import AiLoader from '../Global/AiLoader.vue';
import AiIcon from '../Icons/AiIcon.vue';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';

const emit = defineEmits([
    'close',
    'fetchMemberData'
])

const props = defineProps<{
    selectedDate: any
    editGoalData: ProjectGoal | null
}>()

const goalParams = reactive<Partial<ProjectGoal>>( props.editGoalData ? {...props.editGoalData} : {
    start_date: DateTime.now().toISODate(),
    end_date: DateTime.now().plus({month: 1}).toISODate(),
    expected_effect: '',
    status: 0,
    custom_instruction: '',
    private_memo: '',
    kgi: '',
    miso: '',
    title: '',
    steps: [{
        content: '',
        status: 0,
        progress: 0,
    }]
})

const { usersProjects } = useProject()

const loading = ref(false)
const startDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const endDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const { getProjects } = useProject()
const refresh = inject('refresh') as Function
const badge = useBadgeStore()
const route = useRoute()
const evaluationData = ref<EvaluationRecord | null>(null)
const aiLoading = ref(false)
const baseSkills = ref<string[]>([])
const { ask, ping, toast } = useDialog()
const api = useApi()
const aiType = ref('openai')
const keys = reactive({
    goalContent: 0,
    expectedEffect: 0,
    kgi: 0,
    miso: 0
})
const release = ref(props.editGoalData && props.editGoalData.id ? true : false)
const { selectedProject } = useProject()

const goalTitleRef = useTemplateRef<InstanceType<typeof ShortInput>>('goalTitleRef')
const misoRef = useTemplateRef<InstanceType<typeof LongInput>>('misoRef')
const kgiRef = useTemplateRef<InstanceType<typeof LongInput>>('kgiRef')
const kpiRef = useTemplateRef<InstanceType<typeof ShortInput>[]>('kpiRef')

onMounted(() => {
    getEvaluationData()

})

const getPreviousGoals = (): Promise<ProjectGoal[]> => {
    return new Promise(async (resolve, reject) => {
        try {
            const span = route.params.span as string
            const [year, which_half] = span.split('-')
            const response: ProjectGoal[] = await api.post('/get_previous_goals', {
                user_id: route.params.memberId,
                year: year,
                which_half: which_half,
            })
            resolve(response)
        } catch (e) {
            resolve([]) 
        }
    })
}

const getEvaluationData = async() => {


        const span = route.params.span as string
        const [year, which_half] = span.split('-')
        const response = await api.post('/get_evaluation_data', {
            user_id: route.params.memberId,
            year: year,
            which_half: which_half  
        }, {
            silent: true
        })
        evaluationData.value = response && response.evaluation ? response.evaluation : null
        baseSkills.value = response && response.base_skills ? response.base_skills  : []
        

}
const instruction = `
プロジェクトの様子
「プロジェクト名、ミッション、イノベーション、ストラテジー、オペレーション、概要」
があげられます。
そしてユーザーの職能レベルとその職能レベルのスキルシートがあげられます。
あなたの役割はそのデータを基に、成果目標を提案することです。
成果目標の仕組みは次の通りです。
プロジェクトごとにメンバーの成果目標を構造化し、以下4項目を自動生成する。  
成果目標は「KGI（50点）+KPI（10点*5=50点）」で構成する。

 1. タイトル（10〜30文字）【json-index: title】
- プロジェクト名や個人の取り組みのテーマを短く明示
- 他者が見てすぐ内容をイメージできること



 2. MISO（100〜200文字）【json-index: miso】
- 以下4要素を織り交ぜて、個人目標の背景・意図・方法論を簡潔に表現する：
  - Mission（目的・達成したい価値）
  - Innovation（解決すべき問題・変革の視点）
  - Strategy（どのように取り組むかの構造）
  - Operation（どんな行動や運用で継続するか）
- 表記は自由。文章中に4要素が自然に含まれていればよい
- 決まり文句にならないよう、本人らしい言葉・仮説・視点を重視
冒頭にこの成果目標を設定した背景を述べる。目標内容及び生成指示のカスタマイズがあればそれを参照。

---

 3. KGI（1項目／50点配分）【json-index: kgi】
- 成果目標としてのゴール指標（定量または定性）
- 達成基準が明確なもの（例：○○件完了、顧客満足度4.5以上など）
- あいまいな表現はNG（例：「うまくやる」「満足してもらう」など）

---

 4. KPI（5項目／各10点=合計50点）【json-index: kpi】
- KGIを支えるプロセス・行動・仕組みの観点から具体的に記述
- それぞれ異なる視点（例：顧客対応、資料整備、リスク対応、振り返りなど）で構成
- 「意識する」「気をつける」などの抽象表現は禁止
- 各KPIは評価・実行・振り返りが可能な行動単位で記述


成果目標の条件は以下の通りです。
期間：達成に最短1か月から最長6か月の期間を要すること。期間はレベルに応じて適切なものとする。
レベル：個々が保有している職能レベルスキルを発揮すること。
内容：プロジェクトに貢献し、定性、定量の目標であること。個人の成長というよりは、プロジェクトへの貢献内容がメインとなります。


もし、「目標内容および生成指示のカスタマイズ」があれば、それを考慮してください。
目標内容および生成指示のカスタマイズがある場合、そのアイディアを拡大して、具体的な目標を生成してください。
アイディアがない場合は、プロジェクトのミッションに会う、アイディアを提案してください。
`
const getAdvice = async() => {
    if(goalParams.kgi || goalParams.miso || (goalParams.steps?.length && goalParams.steps[0].content)){ 
        const answer = await ask('既存の目標がある場合、AIで生成した課題は上書きされます。よろしいでしょうか？')
        if(!answer.value) return
    }

    const projectDetails = `
        プロジェクト名: ${selectedProject.value?.name}
        概要: ${selectedProject.value?.overview}
        ミッション: ${selectedProject.value?.mission}
        イノベーション、: ${selectedProject.value?.innovation}
        ストラテジー: ${selectedProject.value?.strategy_miso}
        オペレーション: ${selectedProject.value?.operation}
    `

   
    const checkList = evaluationData.value?.checklist.flatMap((item: EvaluationSkill) => item.content).join('\n ')
    const userDetail = `
        職能レベル: ${evaluationData.value?.current_level}
        職能レベル保留スキル: \n ${checkList}
    `
    const previousGoals = await getPreviousGoals()


    let combined = `
        プロジェクトの様子: \n
        ${projectDetails}\n\n
        ユーザーの職能レベル: \n
        ${userDetail}\n
    ` 
    if(goalParams.private_memo){
        combined = `
            ${combined}\n\n
            本人による目標内容: 
            ${goalParams.private_memo}
        `
    }
    if(goalParams.custom_instruction){
        combined = `
            ${combined}\n\n
            生成指示のカスタマイズ: 
            ${goalParams.custom_instruction}
        `
    }
    const psuedoElement = document.createElement('div')
    psuedoElement.innerHTML = combined
    combined = psuedoElement.innerText



    if(goalParams.start_date && goalParams.end_date){
        combined = `
            ${combined}\n\n
            期間: 
            ${goalParams.start_date} - ${goalParams.end_date}
        `
    }
    if(previousGoals.length){
        combined = `
            ${combined}\n\n
            過去の成果目標: \n
            ${previousGoals.map((goal: ProjectGoal) => {
                return `
                    タイトル: ${goal.title}\n
                    目標内容: ${goal.outcome_goal}\n
                    MISO: ${goal.miso}\n
                    KGI: ${goal.kgi}\n
                    KPI: ${goal.steps.map((step: any) => step.content).join(', ')}\n
                    期間: ${goal.start_date} - ${goal.end_date}\n
                    期待される効果: ${goal.expected_effect}\n
                `
            }).join('\n\n')}
            過去の成果目標の内容に重複しないように生成してください。
        `
    }
    let instructionText = instruction

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
                                "text": instructionText
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
                        "name": "monthly_goal_creation",
                        "strict": true,
                        "schema": GoalGenerateFormat
                    }
                },
        
            });
            if(response.output[0].type == 'message' && response.output[0].content[0].type == "output_text"){
        
                const parsedData = JSON.parse(response.output[0].content[0].text);
                console.log(parsedData)
                goalParams.title = parsedData.title || ''
                goalParams.miso = parsedData.miso || ''
                goalParams.kgi = parsedData.kgi || ''

                if(parsedData.kpi){                 
                    goalParams.steps = parsedData.kpi.map((step: any) => {
                        return {
                            content: step
                        }
                    })                    
                }
                keys.miso = keys.miso + 1
                keys.kgi = keys.kgi + 1
                finalize()
            }
        } catch (err) {
            if (err instanceof OpenAI.APIError) {
                if(err.status == 500){
                    ping('AI修正に失敗しました。<br>AIサーバーから反応がありませんでした。しばらく立ってから再度お試しください。')
                }else{
                    ping('AI修正に失敗しました。<br>' + err.message)
                }
                
            } else {
                ping('AI修正に失敗しました。<br>' + err)
            }

        } finally{
            aiLoading.value = false
            release.value = true
        }
    }

}

const finalize = () => {
    toast('AI生成が完了しました。内容を確認してください。')
    setTimeout(() => {
        goalTitleRef.value?.$el.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest'
        })
    }, 100);
}
const removeAdditionalProperties = (obj: any): any => {
  if (Array.isArray(obj)) {
    return obj.map(removeAdditionalProperties);
  } else if (obj && typeof obj === 'object') {
    const newObj: any = {};
    for (const [key, value] of Object.entries(obj)) {
      if (key !== 'additionalProperties') {
        newObj[key] = removeAdditionalProperties(value);
      }
    }
    return newObj;
  }
  return obj;
}
const checkFields = async() => {
    const targets = [
        startDateRef.value, 
        endDateRef.value, 
        goalTitleRef.value,
        misoRef.value,
        kgiRef.value,
        ...(kpiRef.value || []),
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
    if(!result) {
        ping('必須項目が未入力です。')
        return
    }
    if(status == 2) {
        const answer = await ask('申請後には編集ができなくなります。よろしいでしょうか？')
        info_message = '申請しました。'
        if(!answer.value) return
    }
    const span = route.params.span as string
    const [year, which_half] = span.split('-')
    const params = {
        
        id: props.editGoalData?.id ?? null,
        date: props.selectedDate.evaluationDate,
        steps: goalParams.steps,
        params: {
            project_id: selectedProject.value?.id,
            user_id: route.params.memberId,
            start_date: goalParams.start_date,
            end_date: goalParams.end_date,
            year: year,
            which_half: which_half,
            criteria: evaluationData.value?.current_level || '',
            status: status,      
            title: goalParams.title,
            kgi: goalParams.kgi,
            miso: goalParams.miso,    
            custom_instruction: goalParams.custom_instruction,
            private_memo: goalParams.private_memo,
        }
        
    }

    await api.post('/save_project_goal', params, {
        toast: info_message
    })
    emit('close')
    refresh()
    getProjects()
    badge.getMembersGoalsBadge()

}

</script>
