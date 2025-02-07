<template>
    <div class="overlay">
        <div class="chatCreate scrollable">
            <div class="recordFormTitle" style="display:flex;">
                <p>プロジェクト作成</p>
                <div class="cursor-pointer" @click="emit('close')" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div v-if="taskCreating || misoCreating" class="fixed w-full h-full bg-[#00000061] flex items-center justify-center z-[15] top-0 left-0">
                <div class="bg-[#00000085] flex gap-[15px] p-[15px]">
                    <div id="loaderMini" style="width: fit-content;">
                        <div class="spinner-micro" style="border-color: transparent #fff #fff"></div>
                    </div>
                    <p class="text-white leading-normal" v-html="taskCreating ? 'ガントチャート用のタスクをAIで自動生成しています。<br>今しばらくお待ちください。' : 'MISOをAIで自動生成しています。<br>今しばらくお待ちください。'">
                        
                    </p>
                </div>
            </div>
            <div>
                <div>
                    <ShortInput 
                        name="name"
                        v-model="name"
                        :rules="'required'"
                        placeHolder="タイトル"
                        type="text"
                        ref="projectTitle"
                    />
                </div>
                <div class="si-box">
                    <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">期間</p>
                    <div style="display:flex;position: relative;width:100%">
                        <ShortInput 
                            name="startDate" 
                            :rules="'required'"
                            :initialValue="dateStart"
                            customClass="date"
                            ref="startDateRef"
                            type="date"
                            v-model="dateStart"
                        />
                        <div style="align-self: center;margin: 0 20px;font-size: 14px;color: gray;">ー</div>
                        <ShortInput 
                            name="endDate" 
                            :rules="'required'"
                            :initialValue="dateEnd"
                            customClass="date"
                            ref="endDateRef"
                            type="date"
                            v-model="dateEnd"
                        />
                    </div>
                </div>
                <div class="si-box">
                    <LongInput 
                        name="overview"
                        v-model="overview"
                        placeHolder="概要"
                        ref="projectOverview"
                        rules="required"
                    />
                    
                    
                </div>
                <div class="si-box">
                    <div>
                        <p :class="['form-title-small', 'form-title-active']">MISO自動生成</p>
                    </div>
                    <div class="mt-5">
                        <CommandButton 
                            :buttons="[
                                { title: '生成する', action: generateMiso},
                            ]"
                        />
                    </div>
                </div>
                <div class="si-box">
                    <LongInput 
                        name="mission"
                        v-model="mission"
                        placeHolder="ミッション"
                    />
                </div>
                <div class="si-box">
                    <LongInput 
                        name="innovation"
                        v-model="innovation"
                        placeHolder="イノベーション"
                    />
                </div>
                
                <div class="si-box">
                    <LongInput 
                        name="solution"
                        v-model="solution"
                        placeHolder="ソリューション"
                    />
                </div>
                <div class="si-box">
                    <LongInput 
                        name="operation"
                        v-model="operation"
                        placeHolder="オペレーション"
                    />

                </div>
                <!-- <div class="si-box">
                    <LongInput 
                        name="kpi"
                        v-model="kpi"
                        placeHolder="KPI"
                    />
                </div>
                <div class="si-box">
                    <LongInput 
                        name="kgi"
                        v-model="kgi"
                        placeHolder="KGI"
                    />
                </div> -->
                <!-- <div class="si-box">
                    <MemberSelector 
                        name="director"
                        v-model="director"
                        :options="directorOptions"
                        :multiple="false"
                        placeHolder="取締役"
                    />
                </div> -->
                <div class="si-box">
                    <MemberSelector 
                        name="manager"
                        rules="required"
                        v-model="manager"
                        :options="managerOptions"
                        :multiple="true"
                        placeHolder="管理者"
                        ref="projectManager"
                    />
                </div>
                <div class="si-box">
                    <MemberSelector 
                        name="member"
                        v-model="member"
                        placeHolder="メンバー"
                        :options="userList"
                        :closeOnSelect="false"
                        :multiple="true"
                    />
                </div>
                <div class="si-box" style="position:relative;">
                    <div>
                        <p :class="['form-title-small', 'form-title-active']">ボード連携</p>
                    </div>
                    <div class="selectSwitchArea" style="width: fit-content;">    
                        <input type="checkbox" id="release_flag" v-model="boardLink">
                        <label for="release_flag" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer']"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                    </div>  
                </div> 
                <div class="si-box" style="position:relative;">
                    <div>
                        <p :class="['form-title-small', 'form-title-active']">タスク自動生成</p>
                    </div>
                    <div class="mt-5 flex gap-[10px]">
                        <CommandButton 
                            :buttons="[
                                { title: '生成する', action: generateTasks},
                                ...(prepareSampleTasks.length > 0 ? [{ title: 'キャンセル', action: () => aiResponse = [] }] : [])
                            ]"
                        />
                    </div>
                    <div class="mt-5 flex flex-col gap-[20px]" v-if="prepareSampleTasks.length">
                        <div class="max-w-sm" v-for="task in prepareSampleTasks">
                            <SampleTask 
                                :task="task"
                                ref="mainTaskRef"
                                @delete="deleteTask"
                            />
                            <div v-if="task.sub_tasks.length" class="sub-task-wrap">
                                <div class="sub-task-container">
                                    <SampleTask 
                                        v-for="subTask in task.sub_tasks"
                                        :task="subTask"
                                        ref="mainTaskRef"
                                        @delete="deleteTask"
                                    />
                                </div>
                            </div>
                        </div>
                    </div> 
                    
                </div>
                
                <div class="si-box">
                    <LoaderButton @triggered="createProject" :loading="loading" content="作成する"/>
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
import { computed, inject, ref, useTemplateRef } from 'vue';
import axios from 'axios';
import { DialogMethods, Task, TaskUser, User } from '@/interface/globalInterface';
import { ComponentExposed } from 'vue-component-type-helpers';
import { Project } from '@/interface/projectInterface';
import OpenAI from 'openai';
import SampleTask from '@/components/Task/Gantt/SampleTask.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { DateTime } from 'luxon';
const emit = defineEmits(['close', 'getProjects'])
const props = defineProps(['userList', 'editData'])
const name = ref(props.editData?.name ?? '')
const overview = ref(props.editData?.overview ?? '')
const strategy = ref(props.editData?.strategy ?? '')
const kpi = ref(props.editData?.kpi ?? '')
const kgi = ref(props.editData?.kgi ?? '')
const miso = ref(props.editData?.miso ?? '')
const mission = ref(props.editData?.mission ?? '')
const innovation = ref(props.editData?.innovation ?? '')
const operation = ref(props.editData?.operation ?? '')
const solution = ref(props.editData?.solution ?? '')
const director = ref<User>(props.editData?.director ?? null)
const manager = ref<User[] | TaskUser[]>(props.editData?.manager ?? [])
const member = ref<User[]>(props.editData?.members ?? [])
const loading = ref(false)
const dateStart = ref(props.editData?.date_start ?? '')
const dateEnd = ref(props.editData?.date_end ?? '')
const boardLink = ref(props.editData?.board_id ? true : false)
const taskCreating = ref(false)
const misoCreating = ref(false)
const project = ref<Project | null>(null)
const aiResponse = ref<Task[]>([])
const startDateRef = useTemplateRef<ComponentExposed<typeof ShortInput>>('startDateRef')
const endDateRef = useTemplateRef<ComponentExposed<typeof ShortInput>>('endDateRef')
const projectTitle = useTemplateRef<ComponentExposed<typeof ShortInput>>('projectTitle')
const projectManager = useTemplateRef<ComponentExposed<typeof MemberSelector>>('projectManager')
const projectOverview = useTemplateRef<ComponentExposed<typeof LongInput>>('projectOverview')
const mainTaskRef = useTemplateRef<ComponentExposed<typeof SampleTask>[]>('mainTaskRef')
const { notify } = inject('dialog') as DialogMethods
const directorOptions = computed(() => {
    return props.userList.filter((user: { position_id: number; }) => user.position_id < 6 && user.position_id !== null)
})
const managerOptions = computed(() => {
    return props.userList.filter((user: { position_id: number; }) => user.position_id <= 6)
})
const validation = async() => {
    const validationTargets = [startDateRef.value, endDateRef.value, projectTitle.value, projectOverview.value]
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
const generateMiso = async() => {
    const validate = await validation()
    if(!validate) return
    try {
        misoCreating.value = true
        const openai = new OpenAI({
            apiKey: import.meta.env.VITE_OPENAI_API_KEY,
            dangerouslyAllowBrowser: true 
        });       
        const assistant = await openai.beta.assistants.retrieve("asst_S9jC52PggATrcVT4QoEX8NYd");
        const thread = await openai.beta.threads.create();
        const text = `project_name: ${name.value}\n project_overview: ${overview.value}\n project-period: ${dateStart.value} ~ ${dateEnd.value}`;
        await openai.beta.threads.messages.create(thread.id, { role: "user", content: text });
        let jsonBuffer = ''; 

        openai.beta.threads.runs.stream(thread.id, { assistant_id: assistant.id })
            .on('textDelta', (textDelta, snapshot) => {
                const content = textDelta.value || '';
                jsonBuffer += content;
            })
            .on('end', () => {
                try {
                    const parsedData = JSON.parse(jsonBuffer);
                    
                    mission.value = parsedData?.mission
                    innovation.value = parsedData?.innovation
                    solution.value = parsedData?.solution
                    operation.value = parsedData?.operation
                    misoCreating.value = false
                } catch (error) {
                    console.error("JSON Parsing Error:", error);
                }
            });
        
    } catch (e) {

    }

}
const createProject = async() => {
    
    const validate = await validation()
    const managerValidate = await managerValidation()

    if(!validate || !managerValidate) return

    const params = {
        id: props.editData?.id,
        manager_ids: manager.value.map(ob => ob.id),
        member_ids: member.value.map(ob => ob.id),
        params: {
            name: name.value,
            director_id: director.value?.id,
            date_start: dateStart.value,
            date_end: dateEnd.value,
            overview: overview.value,
            solution: solution.value,
            mission: mission.value,
            innovation: innovation.value,
            operation: operation.value,
            kgi: kgi.value,
            kpi: kpi.value,  
        },
        board_link: boardLink.value
    }
    loading.value = true
    try {
        const response = await axios.post('/create_project', params)
        project.value = response.data
        if (aiResponse.value.length) {
            await editTasks()
            await createTasks()
        }
        emit('close')
        loading.value = false
        
        emit('getProjects')
    } catch (e) {
        
    }
}
const generateTasks = async() => {
    const validate = await validation()
    const managerValidate = await managerValidation()
    if(!validate || !managerValidate) return
    if (!mission.value && !innovation.value && !solution.value && !operation.value) {
        notify('タスクを生成するには、ミッション、イノベーション、ソリューション、オペレーションのいずれかが必要です。')
        return
    }
    try {
        taskCreating.value = true
        const openai = new OpenAI({
            apiKey: import.meta.env.VITE_OPENAI_API_KEY,
            dangerouslyAllowBrowser: true 
        });       
        const assistant = await openai.beta.assistants.retrieve("asst_YTY2p8rPF9oE6IcOXU40yfuV");
        const thread = await openai.beta.threads.create();
        const text = `mission: ${mission.value}\n innovation: ${innovation.value}\n solution: ${solution.value}\n operation: ${operation.value}`;
        await openai.beta.threads.messages.create(thread.id, { role: "user", content: text });
        let jsonBuffer = ''; 

        openai.beta.threads.runs.stream(thread.id, { assistant_id: assistant.id })
            .on('textDelta', (textDelta, snapshot) => {
                const content = textDelta.value || '';
                jsonBuffer += content;
            })
            .on('end', () => {
                try {
                    const parsedData = JSON.parse(jsonBuffer);
                    aiResponse.value = parsedData.tasks; 
                    taskCreating.value = false
                    console.log(aiResponse.value) 
                } catch (error) {
                    console.error("JSON Parsing Error:", error);
                }
            });
        
    } catch (e) {

    }
}
const prepareSampleTasks = computed(() => {
    if (!aiResponse.value) return [];

    return aiResponse.value.map(task => {
        const startDate = DateTime.now().toISODate()
        const endDate = DateTime.now().plus({ days: task.duration }).toISODate();

        return {
            ...task,
            executors: manager.value,
            start_at: startDate,
            end_at: endDate,
            sub_tasks: task.sub_tasks.map(subTask => {
                const subTaskEndDate = DateTime.now()
                    .plus({ days: subTask.duration })
                    .toISODate();

                return {
                    ...subTask,
                    executors: manager.value,
                    start_at: startDate,
                    end_at: subTaskEndDate,
                };
            }),
        };
    });
});

const createTasks = async() => {
    if (!aiResponse.value.length && !project.value) return
    try {
        const params = {
            project_id: project.value?.id,
            tasks: aiResponse.value
        }
        await axios.post('/create_project_tasks', params)
    } catch (e) {

    }
}
const editTasks = async() => {
    
    const tasks = aiResponse.value.map(task => {
        const newContent = mainTaskRef.value?.find(sampleTask => sampleTask.computedTaskId === task.id)
        if (newContent) {
            const editedContent = newContent.remarksRef?.textContent
            task.remarks = editedContent as string
        }
        task.sub_tasks.map(subTask => {
            const newContent = mainTaskRef.value?.find(sampleTask => sampleTask.computedTaskId === subTask.id)
            if (newContent) {
                const editedContent = newContent.remarksRef?.textContent
                subTask.remarks = editedContent as string
            }
            return subTask
        })
        return task
    })
    console.log(tasks)
    return tasks
   
}
const deleteTask = (id: number) => {
    const index = aiResponse.value.findIndex(task => task.id === id);
    if (index !== -1) {
        aiResponse.value.splice(index, 1);
    } else {
        aiResponse.value.forEach(task => {
            const subtaskIndex = task.sub_tasks.findIndex(subtask => subtask.id === id);
            if (subtaskIndex !== -1) {
                task.sub_tasks.splice(subtaskIndex, 1);
            }
        });
    }
}
</script>