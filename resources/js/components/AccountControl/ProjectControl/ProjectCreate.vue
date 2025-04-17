<template>
    <div class="overlay" @click="emit('close')">
        <div class="projectModalInner" @click.stop>
            <div class="projectModalMainHeader">
                <p class="ml-[30px]">{{ editData ? 'プロジェクトを編集する' : '新しいプロジェクトを作成する' }}</p>
                <div class="flex items-center justify-center w-[60px] h-[60px] min-w-[60px] ml-auto cursor-pointer" @click="emit('close')">
                    <CloseIcon size="13"/>
                </div>
            </div>
            <div class="projectModalContainer">                
                <div class="projectModalSideMenu">
                    <div class="projectModalSideMenuInner">
                        <div 
                            v-for="(title, index) in stepTitles" 
                            :key="index" 
                            class="projectModalSideMenuItem" 
                            :class="{'active-step': title.hash == activeHash }" 
                            @click="jumpTo(title.hash)">
                            {{ title.name }}
                        </div>
                    </div>

                </div>
                <div class="projectModalContent" @scroll="onScroll">
                    <div class="projectModalContentInner">
                        <AiLoader v-if="taskCreating || misoCreating" :message="taskCreating ? 
                                    'ガントチャート用のタスクをAIで自動生成中です。<br>この処理には数分かかる場合があります。' : 
                                    '自動生成中です。<br>この処理には数分かかる場合があります。'"/>
                        <div id="basic" class="mb-[60px] section-hd">
                            <p class="mb-[20px]"><strong>基本情報</strong></p>
                            <div>
                                <ShortInput 
                                    name="name"
                                    v-model="projectParams.name"
                                    :rules="'required'"
                                    placeHolder="タイトル"
                                    type="text"
                                    ref="projectTitle"
                                />
                            </div>
                            <div class="si-box">
                                <MemberSelector 
                                    name="manager"
                                    rules="required"
                                    v-model="projectParams.manager"
                                    :options="managerOptions"
                                    :multiple="true"
                                    placeHolder="管理者"
                                    ref="projectManager"
                                />
                            </div>
                            <div class="si-box">
                                <MemberSelector 
                                    name="member"
                                    v-model="projectParams.members"
                                    placeHolder="メンバー"
                                    :options="userList"
                                    :closeOnSelect="false"
                                    :multiple="true"
                                />
                            </div>
                            <div class="si-box">
                                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">期間</p>
                                <div style="display:flex;position: relative;width:100%">
                                    <ShortInput 
                                        name="startDate" 
                                        :rules="'required'"
                                        :initialValue="projectParams.date_start"
                                        customClass="date"
                                        ref="startDateRef"
                                        type="date"
                                        v-model="projectParams.date_start"
                                    />
                                    <div style="align-self: center;margin: 0 20px;font-size: 14px;color: gray;">ー</div>
                                    <ShortInput 
                                        name="endDate" 
                                        :rules="'required'"
                                        :initialValue="projectParams.date_end"
                                        customClass="date"
                                        ref="endDateRef"
                                        type="date"
                                        v-model="projectParams.date_end"
                                    />
                                </div>
                            </div>
                        </div>
                        <div id="overview" class="mb-[60px] section-hd">
                            <p class="mb-[20px]"><strong>概要</strong></p>
                            <div>
                                <div style="background:inherit;">        
                                    <div style="position:relative;background:inherit;">
                                        <div style="position: relative;background:inherit;border: 1px solid var(--primary-color);" ref="serviceCategoryRef">
                                            <v-autocomplete
                                                chips
                                                :items="serviceCategories"
                                                :multiple="true"
                                                closable-chips
                                                flat
                                                tile
                                                bg-color="var(--background-color)"
                                                clear-on-select
                                                hide-details
                                                hide-selected
                                                hide-no-data
                                                focused
                                                eager
                                                label="サービスカテゴリ"
                                                :menu-props="{ scrollStrategy: 'close'}"
                                                v-model="projectParams.category"
                                                
                                            >
                                                <template v-slot:chip="{ props, item }">
                                                    <v-chip
                                                        closable
                                                        v-bind="props"
                                                        :text="item.title"
                                                        :close-icon="CloseIcon"
                                                        rounded="0"
                                                        density="compact"
                                                    >
                                                    </v-chip>
                                                </template>
                                                <template v-slot:item="{ props, item }">
                                                    <!-- <v-list-item :width="serviceCategoryRef && serviceCategoryRef?.clientWidth ? serviceCategoryRef?.clientWidth - 32 : undefined" v-bind="props" :subtitle="item.raw.subtitle" :text="item.raw" rounded="0" density="compact" :ripple="false" variant="flat"></v-list-item>                     -->
                                                    <div v-bind="props" class="text-[14px] py-[15px] hover:bg-[var(--bg2)] cursor-pointer" :style="{width: serviceCategoryRef && serviceCategoryRef?.clientWidth ? `${serviceCategoryRef?.clientWidth}px` : undefined}">
                                                        <div class="px-[15px] text-[var(--primary-color)]">
                                                            {{ item.title }}
                                                        </div>
                                                        <div class="text-gray-500 text-[10px] px-[30px] mt-[10px]">
                                                            {{ item.raw.subtitle }}
                                                        </div>
                                                    </div>
                                                </template>
                                            </v-autocomplete>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="si-box flex flex-col gap-[15px]">
                                <PartnerSelector 
                                    name="customer"
                                    v-model="projectParams.customers!"
                                    placeHolder="顧客企業（正式名称）"
                                />
                            </div>

                            <div class="si-box flex flex-col gap-[15px]">
                                <PartnerSelector 
                                    name="customer"
                                    v-model="projectParams.partners!"
                                    placeHolder="パートナー企業（正式名称）"
                                />
                            </div>

                            <div class="si-box relative">
                                <LongInput 
                                    name="private_memo"
                                    v-model="projectParams.private_memo"
                                    placeHolder="管理者用非公開メモ"
                                    ref="projectMemo"
                                    rules="required"                        
                                />

                            </div>
                            <div class="si-box relative">
                                <p class="mb-[15px]">概要</p>
                                <RichEditor 
                                    name="description"
                                    :initila-value="projectParams.description"
                                    placeHolder="概要"
                                    :key="inputKeys.description"
                                    @content-updated="(val) => projectParams.description = val"
                                />
                                <div @click="generateAutoText('概要', 'description')" title="概要を自動生成する" class="absolute bottom-[1px] right-[7px] bg-[var(--background-color)] flex items-center cursor-pointer">
                                    <OpenAIIcon :loading="inputLoading.description"/>
                                    <p class="text-[var(--primary-color)] text-[12px]">{{inputLoading.description ? '生成中...' : '自動生成'}}</p>
                                </div>
                            </div>
 
                        </div>
                        <div class="mb-[60px] section-hd" id="miso">
                            <p class="mb-[20px]"><strong>MISO</strong></p>
                            <div class="relative">
                                <p class="mb-[15px]">ミッション</p>
                                <RichEditor 
                                    name="mission"
                                    :initila-value="projectParams.mission"
                                    placeHolder="ミッション"
                                    :key="inputKeys.mission"
                                    @content-updated="(val) => projectParams.mission = val"
                                />
                                <div @click="generateAutoText('ミッション', 'mission')" title="概要を自動生成する" class="absolute bottom-[1px] right-[7px] bg-[var(--background-color)] flex items-center cursor-pointer">
                                    <OpenAIIcon :loading="inputLoading.mission"/>
                                    <p class="text-[var(--primary-color)] text-[12px]">{{inputLoading.mission ? '生成中...' : '自動生成'}}</p>
                                </div>
                            </div>
                            <div class="si-box">
                                <p class="mb-[15px]">イノベーション</p>
                                <RichEditor 
                                    name="innovation"
                                    :initila-value="projectParams.innovation"
                                    placeHolder="イノベーション"
                                    :key="inputKeys.innovation"
                                    @content-updated="(val) => projectParams.innovation = val"
                                />
                                <div @click="generateAutoText('イノベーション', 'innovation')" title="概要を自動生成する" class="absolute bottom-[1px] right-[7px] bg-[var(--background-color)] flex items-center cursor-pointer">
                                    <OpenAIIcon :loading="inputLoading.innovation"/>
                                    <p class="text-[var(--primary-color)] text-[12px]">{{inputLoading.innovation ? '生成中...' : '自動生成'}}</p>
                                </div>
                            </div>
                            <div class="si-box">
                                <p class="mb-[15px]">ストラテジー</p>
                                <RichEditor 
                                    name="strategy_miso"
                                    :initila-value="projectParams.strategy_miso"
                                    placeHolder="イノベーション"
                                    :key="inputKeys.strategy_miso"
                                    @content-updated="(val) => projectParams.strategy_miso = val"
                                />
                                <div @click="generateAutoText('ストラテジー', 'strategy_miso')" title="概要を自動生成する" class="absolute bottom-[1px] right-[7px] bg-[var(--background-color)] flex items-center cursor-pointer">
                                    <OpenAIIcon :loading="inputLoading.strategy_miso"/>
                                    <p class="text-[var(--primary-color)] text-[12px]">{{inputLoading.strategy_miso ? '生成中...' : '自動生成'}}</p>
                                </div>
                            </div>
                            <div class="si-box">
                                <p class="mb-[15px]">オペレーション</p>
                                <RichEditor 
                                    name="operation"
                                    :initila-value="projectParams.operation"
                                    placeHolder="オペレーション"
                                    :key="inputKeys.operation"
                                    @content-updated="(val) => projectParams.operation = val"
                                />
                                <div @click="generateAutoText('オペレーション', 'operation')" title="概要を自動生成する" class="absolute bottom-[1px] right-[7px] bg-[var(--background-color)] flex items-center cursor-pointer">
                                    <OpenAIIcon :loading="inputLoading.operation"/>
                                    <p class="text-[var(--primary-color)] text-[12px]">{{inputLoading.operation ? '生成中...' : '自動生成'}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="section-hd" id="tasks">
                            <p class="mb-[20px]"><strong>タスクの自動生成</strong></p>
                            <div class="relative" ref="flowContainer">
                                <div>
                                    <p class="text-[13px] text-[gray] mt-[30px] leading-normal">
                                        プロジェクトのMISO「ミッション、イノベーション、ストラテジー、オペレーション」を元にタスクを自動生成します。<br>
                                    </p>
                                </div>
                                <div class="mt-5 flex gap-[10px]">
                                    <CommandButton 
                                        :buttons="[
                                            { title: '生成する', action: generateTasks},
                                            ...(generatedTasks.length > 0 ? [{ title: 'キャンセル', action: () => generatedTasks = [] }] : [])
                                        ]"
                                    />
                                </div>
                                <div class="mt-5 flex flex-col gap-[20px]" v-if="generatedTasks.length">
                                    <VueFlow 
                                        :nodes="flowTasks.nodes" 
                                        :edges="flowTasks.edges" 
                                        fit-view-on-init
                                        :default-zoom="1" 
                                        :min-zoom="1" 
                                        :max-zoom="1" 
                                        :nodes-draggable="false" 
                                        :zoom-on-scroll="false"
                                        :zoom-on-double-click="false" 
                                        :zoom-on-pinching="false" 
                                        :pan-on-drag="false"
                                        :pan-on-scroll="false" 
                                        :edges-deleteable="false" 
                                        :default-viewport="{ x: 40, y: 80, zoom: 1 }"
                                        @pane-ready="(vueFlowInstance) => flowInitilized(vueFlowInstance)"
                                        :style="{ 
                                            height: `${flowTasks.totalHeight}px`, 
                                            minHeight: `${flowTasks.totalHeight}px`, 
                                            minWidth: '100%' 
                                        }"
                                    >

                                        <template #node-custom="nodeProps">
                                            <Handle type="target" :position="Position.Left" :connectable="false" />
                                            <Handle type="source" :position="Position.Left" :connectable="false" />                   
                                                <SampleTask 
                                                    :task="nodeProps.data.task"
                                                    ref="mainTaskRef"
                                                    @delete="deleteTask"
                                                    @update="updateTask"
                                                />
                                        </template>

                                        <template #edge-custom="edgeProps">
                                            <CustomEdge v-bind="edgeProps" />
                                        </template>
                                    </VueFlow>
                                </div> 
                                
                            </div>                            
                            <div class="si-box">
                                <LoaderButton @triggered="createProject" :loading="loading" content="保存する"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import ShortInput from '@/components/Form/ShortInput.vue';
import LongInput from '@/components/Form/LongInput.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import PartnerSelector from '@/components/Form/PartnerSelector.vue';
import { computed, inject, onBeforeUnmount, onMounted, reactive, ref, toRaw, useTemplateRef } from 'vue';
import axios from 'axios';
import { DialogMethods, Task, TaskUser, User } from '@/interface/globalInterface';
import { ComponentExposed } from 'vue-component-type-helpers';
import { Project } from '@/interface/projectInterface';
import OpenAIIcon from '../../Icons/OpenAIIcon.vue';    
import SampleTask from '@/components/Task/Gantt/SampleTask.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { DateTime } from 'luxon';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import 'styles/selector.css'
import AddIcon from '@/components/Form/AddIcon.vue';
import Modal from '@/components/Global/Modal.vue';
import { useAuthUserStore } from '@/store/auth';
import OpenAI from 'openai';
import RichEditor from '@/components/Global/RichEditor.vue';
import {marked} from 'marked'
import DOMPurify from 'dompurify';
import taskGenerateFormat from '../../../../assets/taskGenerateFormat.json'
import { type Node, type Edge, MarkerType, VueFlow, VueFlowStore, Position, Handle } from '@vue-flow/core';
import CustomEdge from '@/components/Task/Gantt/CustomEdge.vue';
import AiLoader from '@/components/Global/AiLoader.vue';
import { useRoute, useRouter } from 'vue-router';

const emit = defineEmits(['close', 'getProjects'])
const props = defineProps(['userList', 'editData'])
const loading = ref(false)
const taskCreating = ref(false)
const misoCreating = ref(false)
const auth = useAuthUserStore()
const step = ref(0)
const router = useRouter()
const route = useRoute()
const stepTitles = [
    {name: '基本情報', hash: '#basic'},
    {name: '概要', hash: '#overview'},
    {name: 'MISO', hash: '#miso'},
    {name: 'タスク自動生成', hash: '#tasks'}
]
const inputKeys = reactive({
    mission: 0,
    innovation: 0,
    strategy_miso: 0,
    operation: 0,
    description: 0
})

const inputLoading = reactive({
    mission: false,
    innovation: false,
    strategy_miso: false,
    operation: false,
    description: false
})

const projectParams = reactive<Partial<Project>>(props.editData ? { ...toRaw(props.editData) } : {
    name: '',
    description: '',
    strategy_miso: '',
    mission: '',
    innovation: '',
    operation: '',
    category: [],
    manager: [],
    members: [],
    date_start: '',
    date_end: '',
    board_id: null
})
const generatedTasks = ref<Task[]>([])
const flowInstance = ref<VueFlowStore | null>(null)
onMounted(() => {
    if(projectParams.customers == null){
        projectParams.customers = []
    }
    if(projectParams.partners == null){
        projectParams.partners = []
    }
    if(!props.editData){
        projectParams.date_start = DateTime.now().toISODate()
        projectParams.date_end = DateTime.now().plus({ days: 30 }).toISODate()
        if(auth.activeUser){
            projectParams.manager = [auth.activeUser as User]
        }
        
    }

})

const startDateRef = useTemplateRef<ComponentExposed<typeof ShortInput>>('startDateRef')
const endDateRef = useTemplateRef<ComponentExposed<typeof ShortInput>>('endDateRef')
const projectTitle = useTemplateRef<ComponentExposed<typeof ShortInput>>('projectTitle')
const projectManager = useTemplateRef<ComponentExposed<typeof MemberSelector>>('projectManager')
const projectOverview = useTemplateRef<ComponentExposed<typeof LongInput>>('projectOverview')
const mainTaskRef = useTemplateRef<ComponentExposed<typeof SampleTask>[]>('mainTaskRef')
const projectMemo = useTemplateRef<ComponentExposed<typeof LongInput>>('projectMemo')
const flowContainer = useTemplateRef('flowContainer')

const serviceCategoryRef = useTemplateRef('serviceCategoryRef')
const serviceCategories = [
    {title: "営業・マーケティング支援", subtitle: 'テレマーケティング、訪問営業、オンライン営業、イベント・プロモーション支援、代理店連携など', value: "営業・マーケティング支援"},
    {title: "IT・システムサービス", subtitle: 'システム導入、クラウドサービス、ネットワーク保守・運用、ICT支援、初期設定サポートなど', value: "IT・システムサービス"},
    {title: "業務改善・プロセスコンサルティング", subtitle: '業務プロセスの標準化、プロジェクトマネジメント、戦略立案、デジタル化推進、RPA導入など', value: "業務改善・プロセスコンサルティング"},
    {title: "アウトソーシング・人材派遣", subtitle: '定型業務のアウトソーシング、派遣業務、業務委託、採用支援・人材育成、リソース調整など', value: "アウトソーシング・人材派遣"},
    {title: "輸入食品・流通支援", subtitle: '外国産食品などの輸入調達、物流、国内販売促進など、食材に関する全般的なサポート', value: "輸入食品・流通支援"},
]

const { notify, info, confirm} = inject('dialog') as DialogMethods

const managerOptions = computed(() => {
    return props.userList.filter((user: { position_id: number; }) => user.position_id <= 6)
})
const activeHash = ref('#basic');

const flowTasks = computed(() => {
    const nodes = <Node[]>[]
    const edges = <Edge[]>[]
    let topOffset = 20

    const checkSelfIncluded = (taskRecord: Task) => {
        const executors = taskRecord.executors.map(e => e.id)
        return executors.includes(auth.activeUser.id!)
    }
    generatedTasks.value.forEach((task) => {
        const offsetX = 0
        nodes.push({
            id: task.id.toString(),
            type: 'custom',
            label: task.title as string,
            position: { x: offsetX, y: topOffset },
            data: { task: task, mainTask: null },
            style:{
                width: `50%`,
                minWidth: '60px',
            }
        })
        topOffset += 116
        task.sub_tasks.forEach((subTask) => {
            topOffset += 15
            nodes.push({
                id: subTask.id.toString(),
                type: 'custom',
                label: subTask.title as string,
                position: { x: 60, y: topOffset },
                data: { task: subTask, mainTask: task },
                connectable: false,
                style:{
                    width: `50%`,
                    minWidth: '60px',
                }
            })

            edges.push({
                id: subTask.id.toString(),
                source: task.id.toString(),
                target: subTask.id.toString(),
                type: 'smoothstep',
                style:{
                    strokeWidth: 2
                },
                markerEnd: MarkerType.ArrowClosed,
            })
            topOffset += 116

        })
        topOffset += 30
    })
    return {
        nodes: nodes,
        totalHeight: topOffset,
        edges: edges,
        totalWidth: flowContainer.value?.clientWidth
    }
})
const validation = async() => {
    const validationTargets = [startDateRef.value, endDateRef.value, projectTitle.value]
    let result = true
    for(const target of validationTargets){                
        const val = await target?.validate() || {valid:false}
        result = result && val.valid
    }
    return result
}
const managerValidation = async() => {
    if (!projectManager.value) return

    const val = await projectManager.value?.validate() || { valid: false}

    return val.valid
}

const createProject = async() => {
    
    const validate = await validation()
    const managerValidate = await managerValidation()

    if(!validate || !managerValidate) {
        notify('必須項目を入力してください。')
        return
    }

    const params = {
        id: props.editData?.id,
        params: projectParams,
        tasks: generatedTasks.value
    }
    loading.value = true
    try {
        const response = await axios.post('/create_project', params)

        info('保存しました。')
        emit('close')
        loading.value = false
        
        emit('getProjects')
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        loading.value = false

    }
}
const generateTasks = async() => {
    // const validate = await validation()
    const managerValidate = await managerValidation()
    if(!managerValidate) return
    if (!projectParams.mission && !projectParams.innovation && !projectParams.strategy_miso && !projectParams.operation) {
        notify('タスクを生成するには、ミッション、イノベーション、ストラテジー、オペレーションのいずれかが必要です。')
        return
    }
    try {
        generatedTasks.value = []
        taskCreating.value = true
        const openai = new OpenAI({
            apiKey: import.meta.env.VITE_OPENAI_API_KEY,
            dangerouslyAllowBrowser: true 
        });       
        const instructionText = `あなたはプロジェクトのタスクを自動生成するアシスタントです。\n
        プロジェクトの概要と期間と4つの様子があげられます。\n
        「ミッション、イノベーション、ストラテジー、オペレーション」\n
        各様子に1つのメインタスクを生成し、その中にサブタスクも生成します。\n
        メインタスクの内容の前必ず様子を記載する必要がある。\n
        例：【ミッション】タスク内容(remarks)\n
        期間(duration)はプロジェクトの期間に合わせます。日数です。\n
        (id)はタスクのIDです。メインタスクの場合は、main_{unique_id} とします。\nサブタスクの場合は、sub_{unique_id} とします。\n
        (start_at)はタスクの開始日です。プロジェクトの期間にあわせます。\n
        (end_at)はタスクの終了日です。プロジェクトの期間にあわせます。\n
        必要に応じてサブタスク（sub_tasks）を追加。ただし、不要な場合は sub_tasks: [] を返すこと。実行可能なステップに分解します。\n
        タスク間の親子関係を適切に設定:\n   
        - sub_tasks → サブタスクの配列（必要な場合）。\n  
        - parent_task_id → 親タスクのID（サブタスクの場合）。`
        const userMessage = 
        `
        プロジェクト名 : ${projectParams.name}
        プロジェクトの実施期間 : ${projectParams.date_start} ~ ${projectParams.date_end}
        プロジェクトの概要 : ${projectParams.description}
        ミッション : ${projectParams.mission}
        イノベーション : ${projectParams.innovation}
        ストラテジー : ${projectParams.strategy_miso}
        オペレーション : ${projectParams.operation}
        `
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
                            "text": userMessage
                        }
                    ]
                }
            ],
            text: {
                "format": {
                    "type": "json_schema",
                    "name": "tasks_schema",
                    "strict": true,
                    "schema": taskGenerateFormat
                }
            },
        });

        if(response.output[0].type == 'message' && response.output[0].content[0].type == "output_text"){
     
            const parsedData = JSON.parse(response.output[0].content[0].text);
            generatedTasks.value = parsedData.tasks.map((task: Task) => {
                return {
                    ...task,
                    executors: projectParams.manager,
                    sub_tasks: task.sub_tasks.map((subTask: Task) => {
                        return {
                            ...subTask,
                            executors: projectParams.manager,
                        }
                    })
                }
            })
            console.log(generatedTasks)
        }
        taskCreating.value = false
        
    } catch (err) {
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
        taskCreating.value = false
    }
}

const flowInitilized = (vueFlowInstance: VueFlowStore) => {
    flowInstance.value = vueFlowInstance
    if (flowInstance.value)
        flowInstance.value.setViewport({ x: 40, y: 0, zoom: 1 })
}

const deleteTask = (id: number) => {
    const index = generatedTasks.value.findIndex(task => task.id === id);
    if (index !== -1) {
        generatedTasks.value.splice(index, 1);
    } else {
        generatedTasks.value.forEach(task => {
            const subtaskIndex = task.sub_tasks.findIndex(subtask => subtask.id === id);
            if (subtaskIndex !== -1) {
                task.sub_tasks.splice(subtaskIndex, 1);
            }
        });
    }
}

const instruction = (val:string) => {
    return `あなたはMISOフレームワークの自動生成アシスタントです。
    MISOフレームワークとは
    ■ Mission（ミッション：目的と背景の明確化）
    ・プロジェクトの具体的な目的や存在意義を明確に記述
    ・達成すべき定量的および定性的目標を設定
    ・主要ステークホルダーとその具体的役割を示す

    ■ Innovation（イノベーション：価値創造と差別化）
    ・本質的な課題の分析と根本原因の明確化
    ・競合との差別化を図るための独自かつ具体的なアイデアの提示

    ■ Strategy（ストラテジー：戦略と施策の具体化）
    ・プロジェクト成功に向けた具体的かつ実践可能な戦略
    ・施策実施に必要なリソース、役割分担を具体的に提示
    ・想定されるリスクへの対処戦略を具体的に明示

    ■ Operation（オペレーション：実行と継続的改善）
    ・実施プロセス（作業フロー）の具体的な提示
    ・進捗管理と評価方法を具体的に設定
    ・フィードバック収集手法と改善サイクルの具体的設計
    ユーザーからは次のようなデータがあげられます。
    プロジェクト名
    プロジェクトの実施期間
    プロジェクトの概要
    あなたの役割はユーザーから挙げられたデータを分析し、MISOフレームワークの通り、プロジェクトの${val}を考え、具体的かつ実践的な${val}を生成することです。
    ${val}のみを生成します。他の様子を別で生成するため今回はいりません。
    不足情報がある場合は、AIが具体的に追加質問を行います。
    
    注意事項
    テキストフォーマットはMarkdown形式で記述してください。ただし、
    ・太文字やボルドやheadingを使わない。
    ulの場合は、・を使って箇条書きを行ってください。
    olの場合は、数字を使って箇条書きを行ってください。
    よけな付け足すをしないでください。例: プロジェクト名や期間そしてプロジェクトの${val}などを記載しない
    文書はあまり長くせず少し要点をまとめた文章にしてください。
    `
}

const descriptionInstruction = `
挙げられたプロジェクトの情報から、プロジェクトの概要を生成してください。
プロジェクトの概要は、プロジェクトの目的、背景、目標、スコープ、成果物、リスク、課題、ステークホルダー、リソース、スケジュール、予算、コミュニケーション
など、プロジェクトの全体像を示す内容を含めてください。
    注意事項
    テキストフォーマットはMarkdown形式で記述してください。ただし、
    ・太文字やボルドやheadingを使わない。
    ulの場合は、・を使って箇条書きを行ってください。
    olの場合は、数字を使って箇条書きを行ってください。
    よけな付け足すをしないでください。例: プロジェクト名や期間そして「プロジェクトの概要」などを記載しない
`

const generateAutoText = async(index:string, indexVal:string) => {
    

    if(inputLoading[indexVal]){
        return
    }
    const validationTargets = [startDateRef.value, endDateRef.value, projectTitle.value]
    let result = true
    if(indexVal == 'description'){
        validationTargets.push(projectMemo.value)
    }else{
        validationTargets.push(projectManager.value)
    }

    for(const target of validationTargets){                
        const val = await target?.validate() || {valid:false}
        result = result && val.valid
    }
    if(!result){
        notify('必須項目を入力してください。')
        return
    }

    let confirmed = {value: true}
    if(projectParams[indexVal]){
        confirmed = await confirm('既存の内容は上書きされます。よろしいですか？')
    }
    if(!confirmed.value){
        return
    }
    inputLoading[indexVal] = true

    const openai = new OpenAI({
        apiKey: import.meta.env.VITE_OPENAI_API_KEY,
        dangerouslyAllowBrowser: true 
    });  

    let inputText = `
        プロジェクト名 : ${projectParams.name}
        プロジェクトの実施期間 : ${projectParams.date_start} ~ ${projectParams.date_end}
        プロジェクトの概要 : ${projectParams.description}
    `
    if(projectParams.customers && projectParams.customers.length){
        inputText += `顧客企業 : ${projectParams.customers.join(', ')}`
    }
    if(projectParams.partners && projectParams.partners.length){
        inputText += `パートナー企業 : ${projectParams.partners.join(', ')}`
    }
    if(projectParams.category && projectParams.category.length){
        inputText += `サービスカテゴリ : ${projectParams.category.join(', ')}`
    }
    const instructionText = indexVal == 'description' ? descriptionInstruction : instruction(index)
    try{
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
                            "text": inputText
                        }
                    ]
                }
            ],
            text: {
                "format": {
                    "type": "text"
                }
            },
            stream: true
        });
        let rawText = '';
        for await (const event of response) {
            console.log(event);
            if (event.type === 'response.output_text.delta') {
                rawText += event.delta; 

                const markedText = marked.parse(rawText) as string;
                const sanitizedText = DOMPurify.sanitize(markedText);

                projectParams[indexVal] = sanitizedText;
                inputKeys[indexVal]++;
            }
        }
    } catch (e) {
        console.error(e);
        notify('自動生成に失敗しました。')

    } finally{
        inputLoading[indexVal] = false
    }

}
const shiftStep = async(from: number, to: number) => {
    let valid = true
    const validationTargets: (ComponentExposed<typeof ShortInput> | ComponentExposed<typeof LongInput> | null)[] = []
    if(from == 0){
        validationTargets.push(startDateRef.value, endDateRef.value, projectTitle.value, projectManager.value)
    }
    else if(from == 1){
        validationTargets.push( projectMemo.value)
    }
    const filteredTargets = validationTargets.filter(target => target)
    console.log(filteredTargets)
    for(const target of filteredTargets){                
        const val = await target?.validate() || {valid:false}
        valid = valid && val.valid
    }
    if(!valid){
        notify('必須項目を入力してください。')
        return
    }
    step.value = to
}
const onScroll = (event) => {
    const target = event.target as HTMLElement
    const sections = document.querySelectorAll(".section-hd");
    let currentSection = "";
    sections.forEach((section) => {
        const rect = section.getBoundingClientRect();
        if (rect.top <= 200 && rect.bottom >= 200) {
        currentSection = section.id;
        }
    });
    if (target.scrollTop + target.clientHeight >= target.scrollHeight) {
        currentSection = "tasks";
    }

    if (currentSection) {
        activeHash.value = `#${currentSection}`;
    }
}
const jumpTo = (hash:string) => {
    const elId = hash.replace('#', '')
    const target = document.getElementById(elId)
    if(target){
        target.scrollIntoView({behavior: 'smooth', block: 'start'})
    }
}

const updateTask = (data) => {
    console.log(data)
    const task = generatedTasks.value.find(task => task.id === data.id)
    if (task) {
        task[data.column] = data.value
    } else {
        generatedTasks.value.forEach(task => {
            const subTask = task.sub_tasks.find(subTask => subTask.id === data.id)
            if (subTask) {
                subTask[data.column] = data.value
            }
        });
    }
}
</script>