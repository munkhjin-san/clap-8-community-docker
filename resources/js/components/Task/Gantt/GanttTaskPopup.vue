<template>
    <div class="pop-root" :style="{position: from == 'board' ? 'relative' : 'absolute'}">   
        <div class="min-h-[60px] flex items-center w-full justify-between" :class="from == 'board' ? 'bg-[var(--background-color)]' : 'bg-[var(--bg2)]'">
            <div class="flex items-center w-full">
                <div v-if="from !== 'board'"  @click="router.push({name: 'gantt-chart'})" class="w-[60px] m-h-[60px] flex items-center justify-center cursor-pointer">
                    <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg"><path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path></svg>
                </div>
                <div v-if="from !== 'board'" class="max-w-[calc(100% - 160px)] overflow-hidden overflow-ellipsis whitespace-nowrap leading-normal">{{ project?.name }}</div>
                          
                <TaskCategorizer 
                    v-if="project"
                    v-model:user="selectedUser" 
                    v-model:status="selectedStatus"
                    class="mr-[20px] ml-auto" 
                    :user-options="[...project?.members, ...project?.manager]"  
                    :statusOptions="categoryOptions"
                    @update="getTask"
                />
            </div>        
        </div>     
        <div class="pop-body" v-if="project">   
            
            <div v-if="props.from !== 'board'" class="popup-sticky-header">
                <div class="flex items-center gap-[10px] h-full">
                    <div @click="expandSpan" class="pp-triangle pp-triangle-l cursor-pointer"></div>  
                    <div v-if="!virtualSpan.expanded">{{ virtualSpan.interval.start?.toLocaleString() }}</div>      
                                
                </div>
                <div v-if="virtualSpan.expanded" class="flex items-center w-full h-full">
                    <div @click="setVirtualSpan(item)" v-for="item in virtualSpanCreator" class="flex-[1] text-center h-full items-center hover:bg-[var(--bg3)] cursor-pointer flex justify-center">
                        {{ Parser(item) }}
                    </div>
                </div>
                <div class="flex items-center gap-[10px]">      
                    <div v-if="!virtualSpan.expanded">{{ virtualSpan.interval.end?.toLocaleString() }}</div>
                    <div @click="expandSpan" class="pp-triangle pp-triangle-r cursor-pointer"></div>  
                </div>   
                <div class="absolute w-full">
                    <div v-if="todayCheck" class="today-line" :style="{left: `${todayLinePosition}%`}"> 
                        <div style="position: relative;width: 0;height: 0;">
                            <div class="today-chip"></div>
                        </div>
                    </div> 
                </div>
                          
            </div>
            <div v-if="project" style="width: 100%;position: relative;overflow: hidden auto;min-height: calc(100% - 91px);display: flex;flex-direction: column;gap: 5px;margin-top: 5px">
                <div class="no-comment-text" v-if="!project.tasks.length">現在タスクはありません。</div>
                <GanttTaskItem 
                    :key="task.id"
                    :from="from"
                    v-for="task in filteredTasks"
                    :task="task"
                    :virtualSpan="virtualSpan"
                    :project="project"
                    @setFullText="(data) => Object.assign(quickEdit, data)"
                    @setCommentingTaskId=" (id) => {setCommentView(id)}"
                />
            </div>
        </div>
        <div v-else-if="loader > 0 && !loading">
            <div class="no-comment-text">プロジェクトが見つかりませんでした。</div>
        </div>
        <Transition name="smLoad">
            <GanttTaskComment v-if="commentingTask" :task="commentingTask" @close="commentView = null"/>
        </Transition>

        <FloatButton @action="createTask({})" v-if="project" type="plus"/>
        <Transition name="modalFade">
            <GanttFullText :data="quickEdit" v-if="quickEdit.id && project" @close="Object.assign(quickEdit, {id: null, text: '', editable: false})"/> 
        </Transition>
        <Transition name="modalFade">
            <TaskCreate 
                :pre-data="preData"
                :project="project"
                @close="createWindow = false"
                v-if="createWindow && project"
            />
        </Transition>
        <Transition name="modalFade">
            <SubTaskControl                 
                :pre-data="subPreData"                
                :project="project"
                @close="clearSubPreData"
                v-if="subPreData.active && project"
            />
        </Transition>
    </div>
</template>
<script setup lang="ts">
import { DialogKey, GanttProjectMethodsKey } from '@/interface/keys';
import { computed, ref, onMounted, reactive, provide, inject  } from 'vue';

import FloatButton from '@/components/Global/FloatButton.vue';
import { useRoute, useRouter } from 'vue-router';
import { DateTime, DateTimeUnit, Interval } from "luxon";
import { Project, QuickEditText, SubTaskPreData, VirtualSpan } from '@/interface/projectInterface';
import GanttTaskItem from './GanttTaskItem.vue';
import GanttFullText from './GanttFullText.vue';
import GanttTaskComment from './GanttTaskComment.vue';
import axios from 'axios';
import { Dialog, Task } from '@/interface/globalInterface';
import TaskCreate from '../TaskCreate.vue';
import TaskCategorizer from './TaskCategorizer.vue';
import SubTaskControl from '../SubTaskControl.vue';
import { useBadgeStore } from '@/store/badge';

const props = defineProps<{
  boardProject?: Project
  from?: string
}>()
const project = ref<Project | null>(null)

const quickEdit = reactive<QuickEditText>({
    text: '',
    id: <number | null> null,
    editable: false,
})
const commentView = ref< number| null>(null)
const route = useRoute()
const router = useRouter()
const virtualSpan = reactive<VirtualSpan>({
    interval: Interval.fromDateTimes(DateTime.now(), DateTime.now()),
    unit: 'month',
    expanded: false,
    selectedMonth: null,
    selectedYear: null,
    selectedWeek: null,
    selectedIndex: null,
})
const subPreData = reactive<SubTaskPreData>({
    mainTaskId: null,
    subTaskData: {},
    active: false
})
const preData = reactive<Partial<Task>>({})
const { notify, info, confirm } = inject<Dialog>('dialog')!;
const units = ['year', 'month', 'week', 'day'] as const;
interface VirtualHeader {
    value: number
    unit: DateTimeUnit
}
const selectedUser = ref<number | null>(null)
const selectedStatus = ref<number>(-1)
const categoryOptions = [
    {value: -1, label: 'すべて'},
    {value: 0, label: '未対応'},
    {value: 1, label: '対応中'},
    {value: 2, label: '完了'},
]
const loader = ref(0)
const loading = ref(true)
const createWindow = ref(false)
const badge = useBadgeStore()
onMounted(async() => {
    await getTask(0)
    const interval = virtualSpan.interval
    switch (true) {
        case interval.length('months') > 12:
            virtualSpan.unit = units[0]
            break;
        case interval.length('months') <= 12 && interval.length('months') > 1:
            virtualSpan.unit = units[1]
            break;
        case interval.length('months') <= 1 && interval.length('weeks') > 1:
            virtualSpan.unit = units[2]
            break;
        case interval.length('weeks') <= 1:
            virtualSpan.unit = units[3]
            break;    
        default:
            break;
    }

})
const getTask = async(load?: number) => {
    loading.value = true
    const id = props.boardProject ? props.boardProject.id : route.params.projectId
    project.value = await axios('/get_gantt_project_tasks', {params: {id: id, user_id: selectedUser.value, progress_flag: selectedStatus.value}}).then(res => res.data.project)  
    
    loader.value ++
    loading.value = false
    if(project.value && load == 0){
        virtualSpan.interval = Interval.fromDateTimes(DateTime.fromISO(project.value.date_start), DateTime.fromISO(project.value.date_end))
    }
}
const commentingTask = computed(() => {
    if(!project.value) return null
    const main = project.value.tasks.find( t => t.id == commentView.value)
    const sub = project.value.tasks.flatMap( t => t.sub_tasks).find( s => s.id == commentView.value)
    return main || sub
})





// const updateChecked = async(id:number) => {
//     await axios.post('/update_task_comment_check', {task_id: id})
//     getTask()
// }

const todayCheck = computed(() => {
    const expandStart = virtualSpan.interval.start?.minus({day: 1})
    const expandEnd = virtualSpan.interval.end?.plus({day: 1})
    if(!expandStart?.isValid || !expandEnd?.isValid) return []
    const expnadedInterval = Interval.fromDateTimes(expandStart, expandEnd)
    return expnadedInterval.contains(DateTime.now())
})
const todayLinePosition = computed(() => {
    const today = DateTime.now(); 
    const startDay = virtualSpan.interval.start?.startOf('day')
    const endDay = virtualSpan.interval.end?.endOf('day') 
    const totalDays = endDay?.diff(startDay!, 'hours').as('hours');
    const elapsedDays = today.diff(startDay!, "hours").as('hours');
    const percentage = (elapsedDays / totalDays!) * 100;
    const percentageReadable = Math.min(Math.max(percentage, 0), 100).toFixed(2); 
    return percentageReadable
})

const expandSpan = () => {
    virtualSpan.expanded = true
    
}
const setVirtualSpan = (item: VirtualHeader) => {
    const index = units.findIndex( u => u == item.unit)
    if(index > -1 && index < 3 && virtualSpan.interval.isValid && virtualSpan.interval.start?.isValid){
        const start = virtualSpan.interval.start
        const end = virtualSpan.interval.start.endOf(item.unit)
        
        const ob = item.unit == 'week' ? {'weekNumber': item.value, weekYear: start.year} : {[item.unit]: item.value}
        const newStart = start.set(ob)
        const newEnd = end.set(ob)
        const newInterval = Interval.fromDateTimes(newStart.startOf(item.unit), newEnd.endOf(item.unit))
        virtualSpan.interval = newInterval
        
        virtualSpan.unit = units[index + 1]
    }

}

const virtualSpanCreator = computed(() => {
    if(virtualSpan.interval.isValid && virtualSpan.interval.end?.isValid && virtualSpan.interval.start?.isValid){
        let dateArray:VirtualHeader[] = []
        let current = virtualSpan.interval.start
        while(current < virtualSpan.interval.end){
            const index = virtualSpan.unit == 'week' ? 'weekNumber' : virtualSpan.unit 
            dateArray.push({value:current[index], unit: virtualSpan.unit})
            current = current.plus({[virtualSpan.unit]: 1})
        }
        return dateArray
    }
})
const Parser = (item: VirtualHeader) => {
    const keys = { month: '月', day: '日', year: '年'}
    if(item.unit == 'week'){
        const start = virtualSpan.interval.start


        const instance = start?.set({weekNumber: item.value})
        const startSpan = instance?.startOf('week').day
        const endSpan = instance?.endOf('week').day
        return `${startSpan}日 - ${endSpan}日`
    }else{
        return `${item.value}${keys[item.unit]}`
    }
}
const filteredTasks = computed(() => {
    if(!project.value) return []
    const expandStart = virtualSpan.interval.start?.minus({day: 1})
    const expandEnd = virtualSpan.interval.end?.plus({day: 1})
    if(!expandStart?.isValid || !expandEnd?.isValid) return []
    const expnadedInterval = Interval.fromDateTimes(expandStart, expandEnd)
    const filtered = project.value.tasks.filter((task) => {
        const startAt = DateTime.fromISO(task.start_at);
        const endAt = DateTime.fromISO(task.end_at);
        const taskInterval = Interval.fromDateTimes(startAt, endAt);       
        const overlaps = expnadedInterval.overlaps(taskInterval)
        return overlaps;
    });
    return filtered
})
const clearPreData = () => {
    Object.keys(preData).forEach((key) => {
        delete (preData as any)[key];
    });
}
const clearSubPreData = () => {
    Object.keys(subPreData).forEach((key) => {
        delete (subPreData as any)[key];
    });
}
const createTask = (args: Partial<Task>) => {    
    clearPreData()
    Object.assign(preData, args);   
    createWindow.value = true
}
const remove = async(task: Task) => {
    let pattern = 'タスク';
    if(task.parent_task_id){
        pattern = 'サブタスク'
    }
    const answer = await confirm(`${pattern}を削除しますか。`)
    if(!answer) return

    try{
        axios.delete(`/task_item`, {params: {task_id: task.id}}).then(() => {
            getTask()
        })
        info('削除しました。')
        badge.getTaskBadge()
    } catch (e) { 
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    } 

}
const setCommentView = (id:number) => {
    commentView.value = id
    // updateChecked(id)
}
provide(GanttProjectMethodsKey, {
    createTask: (args) => createTask(args),
    refreshProject: () => getTask(),
    remove: (task) => remove(task),
    addSubTask: (data) => Object.assign(subPreData, data)
})
</script>
<style>
.pp-wrap{
    height: 80%;
    width: 80%;
    display: flex;
    background: var(--background-color);

}

.radio-group {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
}

.radio-label {
  padding: 5px 10px;
  border: 1px solid var(--calendarBorder);
  background-color: var(--background-color);
  color: var(--primary-color);
  cursor: pointer;
  transition: border 0.2s;
  font-size: 14px;
}

.radio-label.selected {
  border: 1px solid var(--primary-color);
}

.radio-input {
  display: none;
}
.pp-triangle {
    width: 0px;
    height: 0px;
    border-style: solid;
    border-width: 5.5px 0 5.5px 9px;
    border-color: transparent transparent transparent gray;
    transform: rotate(0);
    margin-top: 2px;
}

.pp-triangle-l {
   transform: rotate(180deg);
}
.pp-triangle-r {
   transform: rotate(0deg);
}
</style>