<template>
    <div class="pop-root" :style="{ position: from == 'board' ? 'relative' : 'absolute' }">
        <div class="min-h-[60px] flex items-center w-full justify-between"
            :class="from == 'board' ? 'bg-[var(--background-color)]' : 'bg-[var(--bg2)]'">
            <div class="flex items-center w-full">
                <div v-if="from !== 'board'" @click="router.push({ name: 'gantt-chart' })"
                    class="w-[60px] m-h-[60px] flex items-center justify-center cursor-pointer">
                    <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z">
                        </path>
                    </svg>
                </div>
                <div v-if="from !== 'board'" class="max-w-[calc(100%-280px)] overflow-hidden overflow-ellipsis whitespace-nowrap leading-normal flex items-center gap-2">
                    {{ project?.name }}
                    <WeatherIcon v-if="project?.project_conditions.length" :which="project.project_conditions[0].value" size="16" />
                </div>
                <div class="flex items-center gap-[15px] ml-auto">

                    <TaskSpanSelector 
                        v-model:year="virtualSpan.selectedYear" 
                        v-model:month="virtualSpan.selectedMonth"
                        v-if="project" 
                        @update="getTask" 
                        @reset="drawInitalTodayLine"
                        :project="project"
                    />
                    <TaskCategorizer 
                        v-if="project" 
                        v-model:user="selectedUser" 
                        v-model:status="selectedStatus"
                        class="mr-[15px] ml-auto" 
                        :user-options="[...project?.manager ?? [], ...project?.members ?? []]"
                        :statusOptions="categoryOptions" 
                        @update="getTask" 
                    />
                </div>
            </div>
        </div>
        <div class="flex h-[calc(100%-60px)] w-full">
            <div class="h-full w-full">
                <div class="pop-body overflow-auto" ref="scrollableParent" v-if="project" @mousedown="onMouseDown">
                    <div v-if="props.from !== 'board'" class="popup-sticky-header" ref="stickyHeaderRef" >
                        <div v-if="!virtualSpan.selectedMonth && !virtualSpan.selectedYear" class="flex items-center gap-[10px] h-full ml-[10px]">
                            <div>{{ virtualSpan.interval.start?.toLocaleString() }}</div>
                        </div>
                        <div v-if="virtualSpan.selectedMonth || virtualSpan.selectedYear" class="flex items-center min-w-[100%] h-full">
                            <DateBlock 
                                v-for="item in selectedSpan" 
                                :date="item"
                                :selected-year="virtualSpan.selectedYear"
                                :selected-month="virtualSpan.selectedMonth"
                                :key="`${item.value}_${item.unit}_${virtualSpan.selectedYear}_${virtualSpan.selectedMonth}`"
                                @set-line="(value: number) => { todayLineOffset = value }"
                            />
                        </div>
                        <div v-if="!virtualSpan.selectedMonth && !virtualSpan.selectedYear" class="flex items-center gap-[10px] mr-[10px]">
                            <div>{{ virtualSpan.interval.end?.toLocaleString() }}</div>
                        </div>
                        <div class="absolute w-full">
                            <div v-if="todayCheck" class="today-line" :style="{ left: leftOffsetCalculator }">
                                <div style="position: relative;width: 0;height: 0;">
                                    <div class="today-chip"></div>
                                </div>
                            </div>
                        </div>

                    </div>
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
                        :default-viewport="{ x: 0, y: 80, zoom: 1 }"
                        @pane-ready="(vueFlowInstance) => flowInitilized(vueFlowInstance)"
                        @edge-click="linkClick"
                        :style="{ 
                            height: `${flowTasks.totalHeight}px`, 
                            minHeight: `${flowTasks.totalHeight}px`, 
                            minWidth: stickyHeaderRef && stickyHeaderRef?.clientWidth ? `${stickyHeaderRef?.clientWidth}px` : '100%' 
                        }"
                    >

                        <template #node-custom="nodeProps">
                            <Handle type="target" :position="Position.Left" :connectable="false" style="opacity: 0"/>
                            <Handle type="source" :position="Position.Left" :connectable="false" style="opacity: 0"/>
                            <GanttTaskBody v-if="project"
                                :actual-width="nodeProps.data.task.actual_width" 
                                :sub-task-count="0" 
                                :task="nodeProps.data.task"
                                :interval="virtualSpan.interval" 
                                :from="from" 
                                :mainTask="nodeProps.data.mainTask"
                                :project="project" @setFullText="(data) => Object.assign(quickEdit, data)"
                                @setCommentingTaskId="(id) => commentView = id"/>
                        </template>

                        <template #edge-custom="edgeProps">
                            <CustomEdge v-bind="edgeProps" />
                        </template>
                    </VueFlow>
                </div>
            </div>
        </div>
        <Transition name="smLoad">
            <GanttTaskComment v-if="commentingTask" :task="commentingTask" @close="commentView = null" />
        </Transition>

        <FloatButton @action="createTask({})" v-if="project" type="plus" />
        <Transition name="modalFade">
            <GanttFullText :data="quickEdit" v-if="quickEdit.id && project"
                @close="Object.assign(quickEdit, { id: null, text: '', editable: false })" />
        </Transition>
        <Transition name="modalFade">
            <TaskCreate :pre-data="preData" :project="project" @close="createWindow = false"
                v-if="createWindow && project" />
        </Transition>
        <Transition name="modalFade">
            <SubTaskControl :pre-data="subPreData" :project="project" @close="clearSubPreData"
                v-if="subPreData.active && project" />
        </Transition>
    </div>
</template>
<script setup lang="ts">

import { useWindowSize } from '@vueuse/core'
import { VueFlow, useVueFlow, type Node, type Edge, VueFlowStore, MarkerType, EdgeMouseEvent } from '@vue-flow/core'
import CustomEdge from './CustomEdge.vue'
import { Handle, Position } from '@vue-flow/core'
import { GanttProjectMethodsKey } from '@/interface/keys';
import { computed, ref, onMounted, reactive, provide, inject, useTemplateRef, watch, onUnmounted } from 'vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import { useRoute, useRouter } from 'vue-router';
import { DateTime, DateTimeUnit, Interval } from "luxon";
import { Project, QuickEditText, SubTaskPreData, VirtualSpan } from '@/interface/projectInterface';
import GanttFullText from './GanttFullText.vue';
import GanttTaskComment from './GanttTaskComment.vue';
import axios from 'axios';
import { Dialog, Task } from '@/interface/globalInterface';
import TaskCreate from '../TaskCreate.vue';
import TaskCategorizer from './TaskCategorizer.vue';
import SubTaskControl from '../SubTaskControl.vue';
import { useBadgeStore } from '@/store/badge';
import WeatherIcon from '@/components/Global/WeatherIcon.vue';
import GanttTaskBody from './GanttTaskBody.vue'
import { useAuthUserStore } from '@/store/auth'
import TaskSpanSelector from './TaskSpanSelector.vue'
import DateBlock from './DateBlock.vue'
import { useResponsive } from '@/store/responsive'

const props = defineProps<{
    boardProject?: Project
    from?: string
}>()

const auth = useAuthUserStore()
const { onConnect, addEdges } = useVueFlow()
const { width, height } = useWindowSize()
const project = ref<Project | null>(null)
const quickEdit = reactive<QuickEditText>({
    text: '',
    id: <number | null>null,
    editable: false,
})

const responsive = useResponsive()
const commentView = ref<number | null>(null)
const route = useRoute()
const router = useRouter()
const stickyHeaderRef = useTemplateRef('stickyHeaderRef')
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
const units = ['year', 'month', 'day'] as const;
const selectedUser = ref<number | null>(null)
const selectedStatus = ref<number>(-1)
const categoryOptions = [
    { value: -1, label: 'すべて' },
    { value: 0, label: '未対応' },
    { value: 1, label: '対応中' },
    { value: 2, label: '完了' },
]
const loader = ref(0)
const loading = ref(true)
const createWindow = ref(false)
const badge = useBadgeStore()
const flowInstance = ref<VueFlowStore | null>(null)
const todayLineOffset = ref(-100)
const cursorPos = ref([0, 0])
const beforeState = ref(0)
const scrollableParent = useTemplateRef('scrollableParent')
onConnect((params) => {
    addEdges([params])
})

onMounted(async () => {

    window.addEventListener("mouseup", onMouseUp);
    await getTask(0)
    const interval = virtualSpan.interval
    switch (true) {
        case interval.length('months') > 12:
            virtualSpan.unit = units[0]
            break;
        case interval.length('months') <= 12 && interval.length('months') > 1:
            virtualSpan.unit = units[1]
            break;
        case interval.length('months') <= 1:
            virtualSpan.unit = units[2]
            break;
        default:
            break;
    }

})
onUnmounted(() => {
    window.removeEventListener("mouseup", onMouseUp);
})  

const flowTasks = computed(() => {
    const nodes = <Node[]>[]
    const edges = <Edge[]>[]
    let topOffset = 20
    if (!stickyHeaderRef.value) return {
        nodes: [],
        totalHeight: 0,
        edges: []
    }
    const checkSelfIncluded = (taskRecord: Task) => {
        const executors = taskRecord.executors.map(e => e.id)
        return executors.includes(auth.activeUser.id!)
    }
    const windowWidth = width.value
    filteredTasks.value.forEach((task) => {
        const offsetX = offSetXBuilder(task).x
        const elementWidth = offSetXBuilder(task).width
        nodes.push({
            id: task.id.toString(),
            type: 'custom',
            label: task.title as string,
            position: { x: offsetX, y: topOffset },
            data: { task: task, mainTask: null },
            style:{
                width: `${elementWidth}%`,
                minWidth: '60px'
            }
        })
        topOffset += checkSelfIncluded(task) ? 116 : 87
        task.sub_tasks.forEach((subTask) => {
            const offsetXSub = offSetXBuilder(subTask).x
            const elementWidthSub = offSetXBuilder(subTask).width
            topOffset += 15
            nodes.push({
                id: subTask.id.toString(),
                type: 'custom',
                label: subTask.title as string,
                position: { x: offsetXSub, y: topOffset },
                data: { task: subTask, mainTask: task },
                connectable: false,
                style:{
                    width: `${elementWidthSub}%`,
                    minWidth: '60px'
                }
            })

            edges.push({
                id: subTask.id.toString(),
                source: task.id.toString(),
                target: subTask.id.toString(),
                type: 'smoothstep',
                style:{
                    strokeWidth: stroke.value
                },
                markerEnd: MarkerType.ArrowClosed,
            })
            topOffset += checkSelfIncluded(subTask) ? 116 : 82

        })
        topOffset += 30
    })
    return {
        nodes: nodes,
        totalHeight: topOffset,
        edges: edges,
        totalWidth: windowWidth
    }
})
const stroke = ref(2)
const leftOffsetCalculator = computed(() => {
    if(!stickyHeaderRef.value) return 0
    const outerWidth = stickyHeaderRef.value.scrollWidth
    if(virtualSpan.selectedYear){
        if(virtualSpan.selectedMonth){
            const instance = DateTime.fromObject({ year: virtualSpan.selectedYear, month: virtualSpan.selectedMonth })
            const daysInMonth = instance.daysInMonth as number
            const wider = outerWidth / daysInMonth > 60 ? outerWidth / daysInMonth : 60
            const roundedWider = Math.floor(wider) / 2
            return `calc(${todayLineOffset.value}% + ${roundedWider}px)`
        }
        const widerMonth = outerWidth / 12 > 60 ? outerWidth / 12 : 60
        const roundedWiderMonth = Math.floor(widerMonth) / 2
        return `calc(${todayLineOffset.value}% + ${roundedWiderMonth}px)`
    }
    return `calc(${todayLineOffset.value}%)`
})
const commentingTask = computed(() => {
    if (!project.value) return null
    const main = project.value.tasks.find(t => t.id == commentView.value)
    const sub = project.value.tasks.flatMap(t => t.sub_tasks).find(s => s.id == commentView.value)
    return main || sub
})
interface SelectableSpanData {
    value: number
    unit: string
}
const selectedSpan = computed((): SelectableSpanData[] => {
    const data = <SelectableSpanData[]>[]
    if(virtualSpan.selectedMonth && virtualSpan.selectedYear) {
        const daysofMonth = DateTime.fromObject({ year: virtualSpan.selectedYear, month: virtualSpan.selectedMonth }).daysInMonth as number
        
        for(let i = 1; i <= daysofMonth; i++) {
            data.push({ value: i, unit: '日' })
        }
 
    } else if(virtualSpan.selectedYear) {
        for(let i = 1; i <= 12; i++) {
            data.push({ value: i, unit: '月' })
        }
    }   
    return data
})


const todayCheck = computed(() => {
    if(virtualSpan.selectedYear){
        if(virtualSpan.selectedMonth) {
            return virtualSpan.selectedYear == DateTime.now().year && virtualSpan.selectedMonth == DateTime.now().month
        }
        return virtualSpan.selectedYear == DateTime.now().year
    }
    const expandStart = virtualSpan.interval.start?.minus({ day: 1 })
    const expandEnd = virtualSpan.interval.end?.plus({ day: 1 })
    if (!expandStart?.isValid || !expandEnd?.isValid) return []
    const expnadedInterval = Interval.fromDateTimes(expandStart, expandEnd)
    return expnadedInterval.contains(DateTime.now())
})

const filteredTasks = computed(() => {
    if (!project.value) return []
    let expandStart = virtualSpan.interval.start?.minus({ day: 1 }) as DateTime
    let expandEnd = virtualSpan.interval.end?.plus({ day: 1 }) as DateTime
    if(virtualSpan.selectedYear) {
        expandStart = DateTime.fromObject({ year: virtualSpan.selectedYear, month: virtualSpan.selectedMonth ? virtualSpan.selectedMonth : 1, day: 1 }).minus({ day: 1 })
        expandEnd = DateTime.fromObject({ year: virtualSpan.selectedYear, month: virtualSpan.selectedMonth ? virtualSpan.selectedMonth : 12, day: DateTime.fromObject({ year: virtualSpan.selectedYear, month: virtualSpan.selectedMonth ? virtualSpan.selectedMonth : 12 }).daysInMonth }).plus({ day: 1 })
    }
    if (!expandStart?.isValid || !expandEnd?.isValid) return []
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


const offSetXBuilder = (task: Task) => {
    const total = virtualSpan.interval.count('days')
    if(!stickyHeaderRef.value) return {x: 0, width: 0}
    const outerWidth = stickyHeaderRef.value.scrollWidth
    let offsetX = 0
    let width = 0
    const dayByDuration = DateTime.fromISO(task.end_at).diff(DateTime.fromISO(task.start_at), 'days').as('days')
    if(virtualSpan.selectedYear){
        const taskStartInstance = DateTime.fromISO(task.start_at)
        if(virtualSpan.selectedMonth){
            const selectedInstance = DateTime.fromObject({ year: virtualSpan.selectedYear, month: virtualSpan.selectedMonth, })            
            if(selectedInstance.month == taskStartInstance.month){
                const daysInMonth = selectedInstance.daysInMonth as number
                const calculatedMinimumWidth = 60 * daysInMonth
                const wider = outerWidth > calculatedMinimumWidth ? outerWidth : calculatedMinimumWidth
                const day = taskStartInstance.day - 1
                const percent = (day / daysInMonth) * 100
                offsetX = Math.floor(percent * (wider / 100))
                width = Math.floor(((dayByDuration + 1) / daysInMonth) * 100)

            }
        }else{
            const totalDaysInYear = DateTime.fromObject({ year: virtualSpan.selectedYear }).daysInYear as number
            const dayOfYear = DateTime.fromISO(task.start_at).diff(DateTime.fromObject({ year: virtualSpan.selectedYear }), 'days').as('days')
            const percent = (dayOfYear / totalDaysInYear) * 100
            const wider = outerWidth > 720 ? outerWidth : 720
            offsetX = Math.floor(percent * (wider / 100))
            width = Math.floor(((dayByDuration + 1)  / dayOfYear) * 100)

        }

    }else{
        const offsetMain = virtualSpan.interval.start ? DateTime.fromISO(task.start_at).diff(virtualSpan.interval.start, 'days').as('days') : 0;
        const totalSpan = virtualSpan.interval.count('days')
        const startPercent = Math.floor(offsetMain / total * 100)
        offsetX = Math.floor(startPercent * (outerWidth / 100))
        width = Math.floor(((dayByDuration + 1)  / totalSpan) * 100)
    }
    return {x: offsetX < 10 ? 10 : offsetX, width: width}
}




const flowInitilized = (vueFlowInstance: VueFlowStore) => {
    flowInstance.value = vueFlowInstance
    if (flowInstance.value)
        flowInstance.value.setViewport({ x: 20, y: 0, zoom: 1 })
}
const drawInitalTodayLine = () => {

    const parentWidth = responsive.mobile ? window.innerWidth : window.innerWidth - 60
    const today = DateTime.now();
    const startDay = virtualSpan.interval.start?.startOf('day')
    const endDay = virtualSpan.interval.end?.endOf('day')
    if(!startDay?.isValid || !endDay?.isValid || !parentWidth) return 0
    const totalDays = endDay.diff(startDay, 'hours').as('hours');
    const elapsedDays = today.diff(startDay, "hours").as('hours');
    const percentage = ((elapsedDays / totalDays) * 100).toFixed(2);
    todayLineOffset.value = Number(percentage)



}
const getTask = async (load?: number) => {
    loading.value = true
    const id = props.boardProject ? props.boardProject.id : route.params.projectId
    project.value = await axios('/get_gantt_project_tasks', { params: { id: id, user_id: selectedUser.value, progress_flag: selectedStatus.value } }).then(res => res.data.project)

    loader.value++
    loading.value = false
    if (project.value && load == 0) {
        virtualSpan.interval = Interval.fromDateTimes(DateTime.fromISO(project.value.date_start), DateTime.fromISO(project.value.date_end))
        const spanIncludesToday = virtualSpan.interval.contains(DateTime.now())
        if(spanIncludesToday) {
            drawInitalTodayLine()
        }
    }
}

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
const remove = async (task: Task) => {
    let pattern = 'タスク';
    if (task.parent_task_id) {
        pattern = 'サブタスク'
    }
    const answer = await confirm(`${pattern}を削除しますか。`)
    if (!answer.value) return

    try {
        axios.delete(`/task_item`, { params: { task_id: task.id } }).then(() => {
            getTask()
        })
        info('削除しました。')
        badge.getTaskBadge()
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }

}

const linkClick = (edgeMouseEvent: EdgeMouseEvent) => {
    const targetNode = edgeMouseEvent.edge.targetNode
    if (targetNode && scrollableParent.value) {
        const sp = scrollableParent.value
        const currentScrollLeft = sp.scrollLeft
        const currentScrollTop = sp.scrollTop
        const clientWidth = sp.clientWidth
        const clientHeight = sp.clientHeight
        const targetNodeX = targetNode.computedPosition.x
        const targetNodeY = targetNode.computedPosition.y
        
        let newLeft = currentScrollLeft
        if (targetNodeX < currentScrollLeft || targetNodeX > (currentScrollLeft + clientWidth)) {
            newLeft = targetNodeX - clientWidth / 2
        }
        
        let newTop = currentScrollTop
        if (targetNodeY > (currentScrollTop + clientHeight)) {
            newTop = targetNodeY - clientHeight / 2
        }
        
        sp.scrollTo({
            left: newLeft,
            top: newTop,
            behavior: 'smooth'
        })
    }
};
const onMouseDown = (ev) => {
    cursorPos.value = [ev.pageX, ev.pageY];
    beforeState.value = ev.pageX
    window.addEventListener("mousemove", onMouseHold);
}

/** @param {MouseEvent} ev */
const onMouseUp = (ev) => {
    window.removeEventListener("mousemove", onMouseHold);
}

/** @param {MouseEvent} ev */
const onMouseHold = (ev) => {
    ev.preventDefault();

    requestAnimationFrame(() => {
        const delta = [
        ev.pageX - cursorPos.value[0],
        ev.pageY - cursorPos.value[1],
        ];
        
        cursorPos.value = [ev.pageX, ev.pageY];

        if (!scrollableParent.value) return;
        scrollableParent.value.scrollBy({
            left: -delta[0],
            // top: -delta[1],
        });
        
    });
}
provide(GanttProjectMethodsKey, {
    createTask: (args) => createTask(args),
    refreshProject: () => getTask(),
    remove: (task) => remove(task),
    addSubTask: (data) => Object.assign(subPreData, data)
})
</script>
<style>
.vue-flow__handle {
    background: transparent !important;
    width: 0 !important;
    height: 0 !important;
}

.pp-wrap {
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