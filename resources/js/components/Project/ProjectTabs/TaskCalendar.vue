<template>
    <div class="pop-new">
        <div class="min-h-[60px] flex items-center w-full justify-between"
            :class="'bg-[var(--background-color)]'">
            <div id="taskMenuHeader" class="flex items-center w-full flex-row-reverse flex-wrap gap-[10px] py-[15px]">
                <div class="flex items-center gap-[15px] justify-center">

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
                    <div class="popup-sticky-header" ref="stickyHeaderRef" >
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
                            <div :class="['flex-[0.5]', {'w-[30px]' : virtualSpan.selectedMonth}]"></div>
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
                        :key="`${virtualSpan.selectedYear}_${virtualSpan.selectedMonth}_${flowKey}`"
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
                        :default-viewport="{ x: 20, y: 0, zoom: 1 }"
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
                            <GanttTaskBody 
                                v-if="project && stickyHeaderRef && stickyHeaderRef?.scrollWidth" 
                                :sub-task-count="0" 
                                :task="nodeProps.data.task"
                                :actual-width="nodeProps.data.actualWidth"
                                :interval="virtualSpan.interval" 
                                :from="'project'" 
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

        <FloatButton @action="createTask({})" v-if="project" >
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>
        <Transition name="modalFade">
            <GanttFullText :data="quickEdit" v-if="quickEdit.id && project"
                @close="Object.assign(quickEdit, { id: null, text: '', editable: false })" />
        </Transition>
        <Transition name="modalFade">
            <TaskCreate :pre-data="preData" :project="project" @close="createWindow = false"
                v-if="createWindow && project" />
                <div v-if="createWindow && project">ggggg</div>
        </Transition>
        <Transition name="modalFade">
            <SubTaskControl :pre-data="subPreData" :project="project" @close="clearSubPreData"
                v-if="subPreData.active && project" />
        </Transition>
    </div>
</template>
<script setup lang="ts">

import { useElementSize, useWindowSize } from '@vueuse/core'
import { VueFlow, useVueFlow, type Node, type Edge, VueFlowStore, MarkerType, EdgeMouseEvent } from '@vue-flow/core'
import CustomEdge from '@/components/Task/Gantt/CustomEdge.vue'
import { Handle, Position } from '@vue-flow/core'
import { GanttProjectMethodsKey } from '@/interface/keys';
import { computed, ref, onMounted, reactive, provide, inject, useTemplateRef, onUnmounted } from 'vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import { useRoute, useRouter } from 'vue-router';
import { DateTime, Interval } from "luxon";
import { Project, QuickEditText, SubTaskPreData, VirtualSpan } from '@/interface/projectInterface';
import GanttFullText from '@/components/Task/Gantt/GanttFullText.vue';
import GanttTaskComment from '@/components/Task/Gantt/GanttTaskComment.vue';
import { Task } from '@/interface/globalInterface';
import TaskCreate from '@/components/Task/TaskCreate.vue';
import TaskCategorizer from '@/components/Task/Gantt/TaskCategorizer.vue';
import SubTaskControl from '@/components/Task/SubTaskControl.vue';
import { useBadgeStore } from '@/store/badge';
import GanttTaskBody from '@/components/Task/Gantt/GanttTaskBody.vue';
import { useAuthUserStore } from '@/store/auth'
import TaskSpanSelector from '@/components/Task/Gantt/TaskSpanSelector.vue';
import DateBlock from '@/components/Task/Gantt/DateBlock.vue';
import { useResponsive } from '@/store/responsive'
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';
const props = defineProps<{
    userList: any;
}>();
const auth = useAuthUserStore()
const { onConnect, addEdges } = useVueFlow()
const { width, height } = useWindowSize()
const project = ref<Project | null>(null)
const quickEdit = reactive<QuickEditText>({
    text: '',
    id: <number | null>null,
    editable: false,
})
const api = useApi()
const responsive = useResponsive()
const commentView = ref<number | null>(null)
const route = useRoute()
const router = useRouter()
const flowKey = ref(0)
const stickyHeaderRef = useTemplateRef('stickyHeaderRef')
const { width: headerWidth, height:headerHeight } = useElementSize(stickyHeaderRef)
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

        const positioningData = offSetXBuilder(task)
        nodes.push({
            id: task.id.toString(),
            type: 'custom',
            label: task.title as string,
            position: { x: positioningData.x, y: topOffset },
            data: { task: task, mainTask: null, actualWidth: positioningData.actualWidth },
            style:{
                width: `${positioningData.width}%`,
                minWidth: '60px'
            }
        })
        topOffset += checkSelfIncluded(task) ? 116 : 87
        task.sub_tasks.forEach((subTask) => {
            const subPositioningData = offSetXBuilder(subTask)
            
            const isDifferentMonth = DateTime.fromISO(task.start_at).month != DateTime.fromISO(subTask.start_at).month
            const isDifferentYear = DateTime.fromISO(task.start_at).year != DateTime.fromISO(subTask.start_at).year
            const isNotTaskMonth = virtualSpan.selectedMonth && DateTime.fromISO(subTask.start_at).month != virtualSpan.selectedMonth
            const isNotTaskYear = virtualSpan.selectedYear && DateTime.fromISO(subTask.start_at).year != virtualSpan.selectedYear
            
            const elementWidthSub = subPositioningData.width
            const isInactiveSubTask = (virtualSpan.selectedMonth && isDifferentMonth && isNotTaskMonth) || (virtualSpan.selectedYear && !virtualSpan.selectedMonth && isDifferentYear && isNotTaskYear)

            topOffset += 15
            nodes.push({
                id: subTask.id.toString(),
                type: 'custom',
                label: subTask.title as string,
                position: { x: isInactiveSubTask ? headerWidth.value : subPositioningData.x, y: topOffset },
                data: { task: subTask, mainTask: task, actualWidth: subPositioningData.actualWidth },
                connectable: false,
                style:{
                    width: `${elementWidthSub}%`,
                    minWidth: '60px',
                    opacity: (virtualSpan.selectedMonth && isNotTaskMonth) || (virtualSpan.selectedYear && !virtualSpan.selectedMonth && isNotTaskYear) ? 0.5 : 1
                }
            })
            
            edges.push({
                id: subTask.id.toString(),
                source: task.id.toString(),
                target: subTask.id.toString(),
                type: 'smoothstep',
                style:{
                    strokeWidth: stroke.value,
                    strokeDasharray: isInactiveSubTask ? '5.5' : '0'
                },
                markerEnd: MarkerType.ArrowClosed,
                label: isInactiveSubTask ? `【${DateTime.fromISO(subTask.start_at).toLocaleString(DateTime.DATE_SHORT)} ~ ${DateTime.fromISO(subTask.end_at).toLocaleString(DateTime.DATE_SHORT)}】${subTask.remarks?.slice(0, 15)}` : '',
                labelBgPadding: [8, 4],
                labelBgStyle: { fill: 'var(--bg3)', color: 'var(--primary-color)', opacity: 0.8 },
                labelStyle: { fill: 'var(--primary-color)', fontSize: 12 }
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
            return `calc(${todayLineOffset.value}% + ${roundedWider - 30}px)`
        }
        const widerMonth = outerWidth / 12.5 > 60 ? outerWidth / 12.5 : 60
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
    const outerWidth = headerWidth.value
    let offsetX = 0
    let width = 0
    let actualWidth = NaN
    const dayByDuration = DateTime.fromISO(task.end_at).diff(DateTime.fromISO(task.start_at), 'days').as('days')
    if(virtualSpan.selectedYear){
        
        if(virtualSpan.selectedMonth){
            const taskStartInstance = DateTime.fromISO(task.start_at)
            const taskEndInstance = DateTime.fromISO(task.end_at)
            const selectedInstance = DateTime.fromObject({ year: virtualSpan.selectedYear, month: virtualSpan.selectedMonth, })      
            const startOfThisMonth = selectedInstance.startOf('month')
            const daysInMonth = selectedInstance.daysInMonth as number
            const widthOfPerDayByPixel = (headerWidth.value - 30) / daysInMonth 
            const widthOfPerDayByPercent = 100 / daysInMonth

            const taskStartsInThisMonth = taskStartInstance.month == virtualSpan.selectedMonth && taskStartInstance.year == virtualSpan.selectedYear
            const taskEndsInThisMonth = taskEndInstance.month == virtualSpan.selectedMonth && taskEndInstance.year == virtualSpan.selectedYear

            const startPoint = taskStartsInThisMonth ? taskStartInstance : startOfThisMonth
            const endPoint = taskEndsInThisMonth ? taskEndInstance : selectedInstance.endOf('month')

            const offsetPre = taskStartsInThisMonth ? ((taskStartInstance.day - 1) * widthOfPerDayByPixel) : 0
            const taskDurationInThisMonth = endPoint.diff(startPoint, 'days').as('days')
            const widthOfTaskInThisMonth = taskDurationInThisMonth * widthOfPerDayByPercent
            offsetX = offsetPre
            width = widthOfTaskInThisMonth
            const taskWidthinPixel = (headerWidth.value - 30) * width / 100 
            actualWidth = taskWidthinPixel < 200 ? taskWidthinPixel : NaN
            

        }else{
            //Year selected
            const startDate = DateTime.fromISO(task.start_at);
            const endDate = DateTime.fromISO(task.end_at);
            const selectedYear = virtualSpan.selectedYear;

            const yearStart = DateTime.fromObject({ year: selectedYear, month: 1, day: 1 });
            const yearEnd = DateTime.fromObject({ year: selectedYear, month: 12, day: 31 });

            const totalDaysInYear = DateTime.fromObject({ year: selectedYear }).daysInYear;
            const dayOfYear = startDate.diff(yearStart, 'days').as('days');
            const startPoint = startDate.year !== selectedYear ? yearStart : startDate;
            const endPoint = endDate.year !== selectedYear ? yearEnd : endDate;
            const taskDayDuration = endPoint.diff(startPoint, 'days').as('days');
            const percent = (dayOfYear / totalDaysInYear) * 100;
            const wider = Math.max(outerWidth, 750);
            const offsetDelta = Math.floor(percent * (wider / 100));

            const percentOfOffsetDelta = (offsetDelta / outerWidth) * 100;
            const widthDelta = ((taskDayDuration + 1) / totalDaysInYear) * 100;
            const rightOffsetPercent = 30 / (outerWidth / 100);

            const overflowed = percentOfOffsetDelta + widthDelta > (100 - rightOffsetPercent);
            width = overflowed ? (100 - rightOffsetPercent) - percentOfOffsetDelta : widthDelta;
            offsetX = offsetDelta;
            const actualDelta = Math.floor(width * wider / 100)
            actualWidth = actualDelta < 200 ? actualDelta : NaN

        }

    }else{
        if(!scrollableParent.value){
            return {x: 0, width: 0}
        }
        const fixedParentWidth = scrollableParent.value.clientWidth
        const offsetMain = virtualSpan.interval.start ? DateTime.fromISO(task.start_at).diff(virtualSpan.interval.start, 'days').as('days') : 0;
        const totalSpan = virtualSpan.interval.count('days')
        const startPercent = Math.floor(offsetMain / total * 100)
        offsetX = Math.floor(startPercent * (fixedParentWidth / 100))
        const rightOffsetPercent = 30 / (fixedParentWidth / 100);
        const widthDelta = Math.floor(((dayByDuration + 1)  / totalSpan) * 100)
        const finalWidth = startPercent + widthDelta > (100 - rightOffsetPercent) ? (100 - rightOffsetPercent) - startPercent : widthDelta
        width = finalWidth
        const actualDelta = Math.floor(width * fixedParentWidth / 100)
        actualWidth = actualDelta < 200 ? actualDelta : NaN
    }
    return {x: offsetX < 10 ? 10 : offsetX, width: width, actualWidth: actualWidth}
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
    const id = route.params.projectId
    const data = await api.get('/get_gantt_project_tasks', { id: id, user_id: selectedUser.value, progress_flag: selectedStatus.value } )
    project.value = data.project
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
    await api.del(`/task_item`, { task_id: task.id }, {
        ask: `${pattern}を削除しますか？`,
        toast: `${pattern}を削除しました。`,
    })
    getTask()
    badge.getTaskBadge()
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