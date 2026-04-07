<template>
    <div class="gantt-layout">
        <ListLayout 
            v-if="from == 'board'" 
            :board="props.board" 
            :isBoard="isBoard"
        />
        <div class="min-h-[60px] text-[var(--primary-color)] flex items-center" v-if="!route.params.taskId && from !== 'board'">
            <div @click="router.push({name: 'project'})" class="w-[60px] min-h-[60px] flex items-center justify-center cursor-pointer">
                <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg"><path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path></svg>
            </div>
            <div class="max-w-[calc(100% - 160px)] overflow-hidden overflow-ellipsis whitespace-nowrap leading-normal [@media_screen_and(max-width:959px)]:text-[13px]"><span class="pc">プロジェクト／</span>ガントチャート</div>
            <div style="margin: 0 20px 0 auto;">
                <GanttMonthPicker 
                    v-if="viewType == 'day'"
                    v-model:month="sortData.month"
                    v-model:year="sortData.year"
                    right="0"
                    @setDate="setDate"
                    ref="monthpicker"
                />
                <GanttYearPicker v-if="viewType == 'month'" @setDate="setDate" :interval="maxInterval" v-model="sortData.year"/>
            </div>
        </div>
        <router-view v-if="from !== 'board'" v-slot="{ Component }">
            <component :is="Component"/>       
        </router-view>
        


    </div>
</template>

<script setup lang="ts">
import GanttMonthPicker from './Gantt/GanttMonthPicker.vue';
import { ref, computed, onMounted, provide, reactive, onUnmounted } from 'vue';
import { Board, Task} from '@/interface/globalInterface';
import { GanttMethodsKey} from '@/interface/keys'
import { Project } from '@/interface/projectInterface';
import { FastCreateData } from '@/interface/calendarInterface'
import { useMenuStore } from "@/store/menu";
import { instance } from '@/utils/broadcaster';
import { useRoute, useRouter } from 'vue-router';
import { DateTime, DateTimeUnit, Interval, MonthNumbers } from 'luxon';
import ListLayout from './List/ListLayout.vue';
import GanttYearPicker from './Gantt/GanttYearPicker.vue';
import { useApi } from '@/composables/api';
const props = defineProps<{
    from?: string
    board?: Board
    maxInterval: Interval
}>()
const route = useRoute()
const router = useRouter()
const menu = useMenuStore()
const projectRecord = ref<Project[]>([])
const fastCreate = ref<FastCreateData>({
    x: 0,
    y: 0,
    time: '',
    stamp: null
})
const create = ref(false)
const preData = reactive<Partial<Task>>({})
const viewType = ref<DateTimeUnit>('month')
const sortData = reactive({
    year: DateTime.now().year,
    month: DateTime.now().month,
    from: '',
    to: '',
    id: <number| null>null,
    divisions: [],
    projects: props.board ? [props.board.project?.id as number] : [],    
    ignoreSpan: props.from == 'board' ? 1 : 0,
    unit: ''
})
const initialLoader = ref(true)
const api = useApi()
onMounted(async() => {
    const savedViewType = localStorage.getItem('gant_view_type')
    if(savedViewType){
        viewType.value = savedViewType as DateTimeUnit
    }
    await spanBuilder(DateTime.now())
    if (!isBoard.value){ 
        getGanttProjects()
    }
    
    const el = document.getElementById(`g-task-${DateTime.now().startOf('week').toISODate()}`)
    el?.scrollIntoView({block: 'start', inline: 'start'})
    instance.on('refresh:task', TaskSocketHandler)
})
onUnmounted(() => {
    instance.off('refresh:task', TaskSocketHandler)
})
const trash = ref(0)

const isBoard = computed(() => {
    return props.from == 'board' && !props.board?.project?.id
})
const spanBuilder = async(date: DateTime) => {   
    if(!date.isValid) return
    const units: Record<DateTimeUnit, DateTimeUnit> = {
        year: 'year',
        quarter: 'year',
        month: 'year',
        week: 'month',
        day: 'month',
        hour: 'day',
        minute: 'hour',
        second: 'minute',
        millisecond: 'second',
    }

    let from = date.startOf(units[viewType.value])
    let to = date.endOf(units[viewType.value])
    if(viewType.value == 'year' && props.maxInterval.isValid && props.maxInterval.start?.isValid && props.maxInterval.end?.isValid){
        from = props.maxInterval.start
        to = props.maxInterval.end
    }
    sortData.year = date.year
    sortData.month = date.month as MonthNumbers
    sortData.from = from.toISODate() as string
    sortData.to = to.toISODate() as string
    sortData.unit = viewType.value
    sortData.id = route.params?.projectId ? Number(route.params.projectId) : null
}
const TaskSocketHandler = async(id:number) => {
    const exist = projectRecord.value.find( t => t.id == id)
    if(exist){
        reload({id: id})
    }
}



const resetFastCreate = () => {
    fastCreate.value = { x: 0, y: 0, time: '', stamp: null}
}

const getGanttProjects = async() => {
    projectRecord.value = await getProjects()
}
const setDate = () => {

    const instance = viewType.value == 'month' ? DateTime.fromObject({year: sortData.year}) : DateTime.fromObject({year: sortData.year, month:sortData.month})
    spanBuilder(instance)
    reload({})
}

const getProjects = async(src?:any) => {
    return await api.get('/get_gantt_projects',  sortData)
}
const clearPreData = () => {
    Object.keys(preData).forEach((key) => {
        delete (preData as any)[key];
    });
}
const createTask = (args: Partial<Task>) => {    
    clearPreData()
    Object.assign(preData, args);   
    create.value = true
}

const reload = async(args: Partial<typeof sortData>) => {
    Object.assign(sortData, args);
    const data = await getProjects()
    if(args?.id){
        const task = data[0]
        const index = projectRecord.value.findIndex(item => item.id === task.id);
        if (index !== -1) {
            projectRecord.value[index] = { ...projectRecord.value[index], ...task };
        }
    }else{
        projectRecord.value = data
    }
}

const reactiveActiveProject = computed(() => {
    const target = projectRecord.value.find( t => t.id == Number(route.params.taskId))
    return target ? target : null
})
const setViewType = (type:DateTimeUnit) => {
    viewType.value = type
    localStorage.setItem('gant_view_type', type)
    spanBuilder(DateTime.now())
    reload({})
}

const jumpTo = (instance:DateTime) => {
    if(viewType.value == 'day') return
    viewType.value = viewType.value == 'year' ? 'month' : 'day'
    localStorage.setItem('gant_view_type', viewType.value)
    spanBuilder(instance)
    reload({})
}
provide(GanttMethodsKey, {
    create: (args) => createTask(args),
    reload: (args) => reload(args),
    fastCreate: (args) => {
        fastCreate.value = args;
        menu.setMenu({ id: 896, name: 'taskCreateFast' });
    },
    jumpTo: (instance) => jumpTo(instance),
    refreshBoardTasks: function (): void {
        throw new Error('Function not implemented.');
    }
})

defineExpose({setDate})
</script>


