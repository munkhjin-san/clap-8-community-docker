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
                            <div class="relative">
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
                            <div class=si-box>
                                <div style="background:inherit;">        
                                    <div style="position:relative;background:inherit;">
                                        <div style="position: relative;background:inherit;border: 1px solid var(--primary-color);" ref="industryTypeRef">
                                            <v-autocomplete
                                                chips
                                                :items="ProjectIndustryTypes"
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
                                                label="業種区分"
                                                :menu-props="{ scrollStrategy: 'close'}"
                                                v-model="projectParams.industry_type"
                                                
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
                                                    <div v-bind="props" class="text-[14px] py-[15px] hover:bg-[var(--bg2)] cursor-pointer" :style="{width: industryTypeRef && industryTypeRef?.clientWidth ? `${industryTypeRef?.clientWidth}px` : undefined}">
                                                        <div class="px-[15px] text-[var(--primary-color)]">
                                                            {{ item.title }}
                                                        </div>
                                                    </div>
                                                </template>
                                            </v-autocomplete>
                                        </div>
                                    </div>
                                </div>

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
                                <AiGenerationProject 
                                    v-model:text="projectParams.description"
                                    url-prefix="/project_generate_description"
                                    place-holder="概要"
                                    which="description"
                                    ref="descriptionGenerator"
                                    config-key="project_description_generation"
                                    :data="projectParams"
                                />                                                                
                            </div>
                            <p class="text-[12px] text-[gray] mt-[10px] leading-normal">概要は管理者用の非公開メモから自動生成されます。プロジェクト情報を詳しく入力すると、より正確な概要が作成されます。</p>
 
                        </div>
                        <div class="mb-[60px] section-hd" id="miso">
                            <p class="mb-[20px]"><strong>MISO</strong></p>
                            <div class="relative">
                                <AiGenerationProject 
                                    v-model:text="projectParams.mission"
                                    url-prefix="/project_generate_miso"
                                    place-holder="ミッション"
                                    which="mission"
                                    ref="missionGenerator"
                                    config-key="project_miso_generation"
                                    :data="projectParams"
                                /> 
                            </div>
                            <div class="si-box">                                
                                <AiGenerationProject 
                                    v-model:text="projectParams.innovation"
                                    url-prefix="/project_generate_miso"
                                    place-holder="イノベーション"
                                    which="innovation"
                                    ref="innovationGenerator"
                                    config-key="project_miso_generation"
                                    :data="projectParams"
                                /> 
                            </div>
                            <div class="si-box">
                                <AiGenerationProject 
                                    v-model:text="projectParams.strategy_miso"
                                    url-prefix="/project_generate_miso"
                                    place-holder="ストラテジー"
                                    which="strategy"
                                    ref="strategyGenerator"
                                    config-key="project_miso_generation"
                                    :data="projectParams"
                                /> 
                            </div>
                            <div class="si-box">
                                <AiGenerationProject 
                                    v-model:text="projectParams.operation"
                                    url-prefix="/project_generate_miso"
                                    place-holder="オペレーション"
                                    which="operation"
                                    ref="operationGenerator"
                                    config-key="project_miso_generation"
                                    :data="projectParams"
                                /> 
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
import { computed, onMounted, reactive, ref, toRaw, useTemplateRef } from 'vue';
import { Task } from '@/interface/globalInterface';
import { ComponentExposed } from 'vue-component-type-helpers';
import { Project } from '@/interface/projectInterface';  
import SampleTask from '@/components/Task/Gantt/SampleTask.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { DateTime } from 'luxon';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import 'styles/selector.css'
import { useAuthUserStore } from '@/store/auth';
import { type Node, type Edge, MarkerType, VueFlow, VueFlowStore, Position, Handle } from '@vue-flow/core';
import CustomEdge from '@/components/Task/Gantt/CustomEdge.vue';
import AiLoader from '@/components/Global/AiLoader.vue';
import ProjectServiceCategories from 'assets/ProjectServiceCategories.json'
import ProjectIndustryTypes from 'assets/ProjectIndustryTypes.json'
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import AiGenerationProject from '@/components/Global/AiGenerationProject.vue';

const emit = defineEmits(['close', 'getProjects'])
const props = defineProps(['userList', 'editData'])
const api = useApi()
const {ask, ping, toast } = useDialog()
const loading = ref(false)
const taskCreating = ref(false)
const misoCreating = ref(false)
const auth = useAuthUserStore()

const stepTitles = [
    {name: '基本情報', hash: '#basic'},
    {name: '概要', hash: '#overview'},
    {name: 'MISO', hash: '#miso'},
    {name: 'タスク自動生成', hash: '#tasks'}
]
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
    industry_type: [],
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
        if(auth.activeUser && projectManager.value){
            projectManager.value.selectBy([auth.activeUser])
        }
        
    }

})

const startDateRef = useTemplateRef<ComponentExposed<typeof ShortInput>>('startDateRef')
const endDateRef = useTemplateRef<ComponentExposed<typeof ShortInput>>('endDateRef')
const projectTitle = useTemplateRef<ComponentExposed<typeof ShortInput>>('projectTitle')
const projectManager = useTemplateRef<ComponentExposed<typeof MemberSelector>>('projectManager')
const mainTaskRef = useTemplateRef<ComponentExposed<typeof SampleTask>[]>('mainTaskRef')
const projectMemo = useTemplateRef<ComponentExposed<typeof LongInput>>('projectMemo')
const flowContainer = useTemplateRef('flowContainer')

const serviceCategoryRef = useTemplateRef('serviceCategoryRef')
const industryTypeRef = useTemplateRef('industryTypeRef')
const serviceCategories = ProjectServiceCategories

const managerOptions = computed(() => {
    return props.userList.filter((user: { position_id: number; }) => user.position_id <= 6)
})
const activeHash = ref('#basic');

const flowTasks = computed(() => {
    const nodes = <Node[]>[]
    const edges = <Edge[]>[]
    let topOffset = 20

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
    if (!projectManager.value) return false
    const val = await projectManager.value?.validate() || { valid: false}
    return val.valid
}

const createProject = async() => {
    
    const validate = await validation()
    const managerValidate = await managerValidation()

    if(!validate || !managerValidate) {
        ping('必須項目を入力してください。')
        return
    }
    const membersIds = projectParams.members?.map((member: { id: number; }) => member.id) ?? []
    const managerIds = projectParams.manager?.map((manager: { id: number; }) => manager.id) ?? []
    const checkDuplicated = membersIds.filter((id: number) => managerIds.includes(id))
    if(checkDuplicated.length > 0){
        ping('メンバーと管理者に同じユーザーが含まれています。')
        return
    }

    const params = {
        id: props.editData?.id,
        params: projectParams,
        tasks: generatedTasks.value
    }
    loading.value = true
    const data = await api.post('/create_project', params, {
        toast: '保存しました。',
    })
    if(data){
        emit('close')
        emit('getProjects')
    }
    loading.value = false
}
const generateTasks = async() => {

    const managerValidate = await managerValidation()
    if(!managerValidate) return
    if (!projectParams.mission && !projectParams.innovation && !projectParams.strategy_miso && !projectParams.operation) {
        ping('タスクを生成するには、ミッション、イノベーション、ストラテジー、オペレーションのいずれかが必要です。')
        return
    }
    let result = {value: true}
    if (generatedTasks.value.length) {
        result = await ask('既存のタスクは上書きされます。よろしいですか？')
    }
    if (!result.value) return
    try {
        generatedTasks.value = []
        taskCreating.value = true   

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

        const data = await api.post('/non_stream_prompt', { message: userMessage, config_key: 'project_task_generation' })
        const parsedData = JSON.parse(data);
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
        taskCreating.value = false
        
    } catch (err) {        
        ping('タスクの自動生成に失敗しました。<br>' + err)        
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