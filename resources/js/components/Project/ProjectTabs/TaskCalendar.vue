<template>
    <div class="pop-new">
        <div class="task-gantt-toolbar-shell min-h-[60px] flex items-center w-full justify-between"
            :class="'bg-[var(--background-color)]'">
            <div id="taskMenuHeader" class="flex items-center w-full flex-row-reverse flex-wrap gap-[10px] py-[15px]">
                <div class="flex items-center gap-[15px] justify-center">

                    <div v-if="project" class="task-gantt-view-toggle task-gantt-panel-toggle" aria-label="左パネル幅">
                        <button
                            v-for="option in ganttPanelModeOptions"
                            :key="option.value"
                            type="button"
                            class="task-gantt-chip-button"
                            :class="{ active: ganttPanelMode === option.value }"
                            :aria-pressed="ganttPanelMode === option.value"
                            :title="option.title"
                            @click="setGanttPanelMode(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <div v-if="project" class="task-gantt-view-toggle" aria-label="表示単位">
                        <button
                            v-for="option in ganttModeOptions"
                            :key="option.value"
                            type="button"
                            class="task-gantt-chip-button"
                            :class="{ active: ganttMode === option.value }"
                            :aria-pressed="ganttMode === option.value"
                            @click="setGanttMode(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <button
                        v-if="project"
                        type="button"
                        class="c-bar-button task-gantt-today-button !text-[12px] whitespace-nowrap !px-[8px]"
                        :disabled="isCurrentMonthSelected"
                        @click="jumpToToday"
                    >
                        今日
                    </button>
                    <div id="memberDropDown" class="relative flex border border-solid border-[var(--formBorder)]">
                        <MemberDropDown
                            v-if="project"
                            v-model="selectedUser"
                            :users="projectAssignableUsers"
                            :disabled="!projectAssignableUsers.length"
                        />
                    </div>
                </div>
            </div>
        </div>
        <div class="task-gantt-wrap" v-if="project">
            <div id="taskGanttScroll" class="task-gantt-scroll" ref="scrollableParent" @mousedown="onMouseDown">
                <div
                    class="task-gantt-canvas"
                    :class="`task-gantt-panel-${ganttPanelMode}`"
                    :style="ganttCanvasStyle"
                >
                    <div class="task-gantt-header-row">
                        <div class="task-gantt-left-header">
                            <div class="task-gantt-project-title">{{ project.name }}</div>
                            <div class="task-gantt-project-range">{{ timelineRangeText }}</div>
                        </div>
                        <div class="task-gantt-time-header" ref="stickyHeaderRef" :style="{ width: `${timelineWidth}px` }">
                            <div class="task-gantt-major-row">
                                <div class="task-gantt-period-controls">
                                    <div class="task-gantt-period-picker">
                                        <GanttMonthPicker
                                            v-model:month="ganttPickerMonth"
                                            v-model:year="ganttPickerYear"
                                            :display-mode="ganttMode"
                                            :show-navigation="true"
                                            :previous-badge="previousPeriodUnreadCommentCount"
                                            :next-badge="nextPeriodUnreadCommentCount"
                                            :previous-title="previousPeriodUnreadTitle"
                                            :next-title="nextPeriodUnreadTitle"
                                            right="-42px"
                                            @setDate="setGanttPickerDate"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="task-gantt-minor-row">
                                <div
                                    v-for="segment in timelineMinorSegments"
                                    :key="`minor-${segment.key}`"
                                    class="task-gantt-minor-cell"
                                    :style="segmentStyle(segment)"
                                >
                                    <span>{{ segment.label }}</span>
                                    <small v-if="segment.subLabel">{{ segment.subLabel }}</small>
                                </div>
                            </div>
                            <div v-if="todayCheck" class="task-gantt-today-line" :style="{ left: `${todayOffset}%` }">
                                <span></span>
                            </div>
                        </div>
                    </div>
                    <div v-if="loading" class="task-gantt-empty" :style="{ width: `${ganttCanvasWidth}px` }">
                        読み込み中...
                    </div>
                    <div v-else-if="!ganttGroups.length" class="task-gantt-empty" :style="{ width: `${ganttCanvasWidth}px` }">
                        この期間のタスクはありません
                    </div>
                    <div v-else class="task-gantt-body-grid">
                        <div
                            v-for="group in ganttGroups"
                            :key="group.task.id"
                            class="task-gantt-group"
                            :style="{ height: `${group.height}px` }"
                        >
                            <div class="task-gantt-left-cell">
                                <div class="task-gantt-left-main" :style="{ top: `${mainRowTop}px` }">
                                    <div class="task-gantt-left-title-line">
                                        <button type="button" class="task-gantt-title-button" :title="taskTooltip(group.task)" @click.stop="openTaskText(group.task)">
                                            {{ taskLineTitle(group.task) }}
                                        </button>
                                        <ItemMenu v-if="canModifyTask(group.task)" class="task-gantt-main-menu" :items="taskMenuItems(group.task)" fit="taskGanttScroll"/>
                                    </div>
                                    <div class="task-gantt-left-meta" :title="taskTooltip(group.task)">
                                        <span>{{ taskDateRange(group.task) }}</span>
                                    </div>
                                    <div class="task-gantt-left-tools">
                                        <button type="button" class="task-gantt-users" :title="taskMemberTitle(group.task)" @click.stop="showTaskUsers(group.task)">
                                            <span v-for="user in group.task.executors?.slice(0, mainExecutorPreviewLimit)" :key="user.id" class="task-gantt-user-avatar">
                                                <UserPanel :user="user" size="15" imgClass="u_icon_15" :disableInstant="true"/>
                                                <span
                                                    v-if="user.pivot.progress_flag > 0"
                                                    class="task-gantt-user-status"
                                                    :style="{ background: taskStatusBackgrounds[user.pivot.progress_flag] }"
                                                ></span>
                                            </span>
                                            <span v-if="group.task.executors?.length > mainExecutorPreviewLimit" class="task-gantt-user-more">
                                                +{{ group.task.executors.length - mainExecutorPreviewLimit }}
                                            </span>
                                        </button>
                                        <button
                                            type="button"
                                            class="task-gantt-comment"
                                            :class="{ 'has-unread': hasUnreadComments(group.task) }"
                                            :title="taskCommentTitle(group.task)"
                                            @click.stop="commentView = group.task.id"
                                        >
                                            <span class="task-gantt-comment-total">{{ taskCommentTotal(group.task) }}</span>
                                            <span v-if="taskUnreadCommentCount(group.task)" class="task-gantt-comment-unread">
                                                {{ unreadCommentLabel(group.task) }}
                                            </span>
                                        </button>
                                        <GanttButton
                                            v-if="taskExecutor(group.task)"
                                            viewType="button"
                                            :status="taskProgress(group.task)"
                                            :loading="updatingTaskId === group.task.id"
                                            @action="(flag) => updateStatus(group.task, flag)"
                                        />
                                    </div>
                                </div>
                                <div
                                    v-for="subTask in group.subTasks"
                                    :key="subTask.task.id"
                                    class="task-gantt-left-sub"
                                    :style="{ top: `${subTask.top - 8}px` }"
                                >
                                    <button type="button" class="task-gantt-sub-title" :title="taskTooltip(subTask.task)" @click.stop="openTaskText(subTask.task)">
                                        {{ taskLineTitle(subTask.task) }}
                                    </button>
                                    <div class="task-gantt-sub-meta">
                                        <span class="task-gantt-sub-range" :title="taskTooltip(subTask.task)">{{ taskDateRange(subTask.task) }}</span>
                                        <button type="button" class="task-gantt-sub-users" :title="taskMemberTitle(subTask.task)" @click.stop="showTaskUsers(subTask.task)">
                                            <span v-for="user in subTask.task.executors?.slice(0, subExecutorPreviewLimit)" :key="user.id" class="task-gantt-user-avatar task-gantt-sub-avatar">
                                                <UserPanel :user="user" size="13" imgClass="u_icon_13" :disableInstant="true"/>
                                                <span
                                                    v-if="user.pivot.progress_flag > 0"
                                                    class="task-gantt-user-status"
                                                    :style="{ background: taskStatusBackgrounds[user.pivot.progress_flag] }"
                                                ></span>
                                            </span>
                                            <span v-if="subTask.task.executors?.length > subExecutorPreviewLimit" class="task-gantt-sub-user-more">
                                                +{{ subTask.task.executors.length - subExecutorPreviewLimit }}
                                            </span>
                                        </button>
                                        <button
                                            type="button"
                                            class="task-gantt-sub-comment"
                                            :class="{ 'has-unread': hasUnreadComments(subTask.task) }"
                                            :title="taskCommentTitle(subTask.task)"
                                            @click.stop="commentView = subTask.task.id"
                                        >
                                            <span class="task-gantt-comment-total">{{ taskCommentTotal(subTask.task) }}</span>
                                            <span v-if="taskUnreadCommentCount(subTask.task)" class="task-gantt-comment-unread">
                                                {{ unreadCommentLabel(subTask.task) }}
                                            </span>
                                        </button>
                                        <GanttButton
                                            v-if="taskExecutor(subTask.task)"
                                            viewType="button"
                                            :status="taskProgress(subTask.task)"
                                            :loading="updatingTaskId === subTask.task.id"
                                            @action="(flag) => updateStatus(subTask.task, flag)"
                                        />
                                        <ItemMenu v-if="canModifyTask(subTask.task)" class="task-gantt-sub-menu" :items="taskMenuItems(subTask.task, group.task)" fit="taskGanttScroll"/>
                                    </div>
                                </div>
                            </div>
                            <div class="task-gantt-track" :style="{ width: `${timelineWidth}px` }">
                                <div
                                    v-for="segment in timelineGridSegments"
                                    :key="`grid-${group.task.id}-${segment.key}`"
                                    class="task-gantt-grid-segment"
                                    :style="segmentStyle(segment)"
                                ></div>
                                <div v-if="todayCheck" class="task-gantt-track-today" :style="{ left: `${todayOffset}%` }"></div>
                                <template v-for="connector in group.connectors" :key="`connector-${group.task.id}-${connector.taskId}`">
                                    <div
                                        class="task-gantt-connector-vertical"
                                        :style="connectorVerticalStyle(connector)"
                                    ></div>
                                    <div
                                        class="task-gantt-connector-horizontal"
                                        :style="connectorHorizontalStyle(connector)"
                                    ></div>
                                </template>
                                <button
                                    v-if="group.bar"
                                    type="button"
                                    :class="['task-gantt-bar', 'task-gantt-main-bar', barClass(group.bar)]"
                                    :style="barStyle(group.bar)"
                                    :title="taskTooltip(group.task)"
                                    @click.stop="openTaskText(group.task)"
                                >
                                    <span class="task-gantt-bar-title">{{ taskLineTitle(group.task) }}</span>
                                    <span v-if="taskUnreadCommentCount(group.task)" class="task-gantt-bar-unread">
                                        {{ unreadCommentLabel(group.task) }}
                                    </span>
                                </button>
                                <button
                                    v-for="subTask in group.subTasks"
                                    :key="`bar-${subTask.task.id}`"
                                    v-show="subTask.bar"
                                    type="button"
                                    :class="['task-gantt-bar', 'task-gantt-sub-bar', barClass(subTask.bar)]"
                                    :style="barStyle(subTask.bar)"
                                    :title="taskTooltip(subTask.task)"
                                    @click.stop="openTaskText(subTask.task)"
                                >
                                    <span class="task-gantt-bar-title">{{ taskLineTitle(subTask.task) }}</span>
                                    <span v-if="taskUnreadCommentCount(subTask.task)" class="task-gantt-bar-unread">
                                        {{ unreadCommentLabel(subTask.task) }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Transition name="smLoad">
            <GanttTaskComment
                v-if="commentingTask"
                :task="commentingTask"
                @close="commentView = null"
                @comment-count-change="syncTaskCommentCount"
            />
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
        </Transition>
        <Transition name="modalFade">
            <SubTaskControl :pre-data="subPreData" :project="project" @close="clearSubPreData"
                v-if="subPreData.active && project" />
        </Transition>
    </div>
</template>
<script setup lang="ts">

import { useElementSize, useWindowSize } from '@vueuse/core'
import { type Node, type Edge, VueFlowStore, MarkerType, EdgeMouseEvent } from '@vue-flow/core'
import { GanttProjectMethodsKey } from '@/interface/keys';
import { computed, ref, onMounted, reactive, provide, watch, useTemplateRef, onUnmounted, nextTick } from 'vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import { useRoute } from 'vue-router';
import { DateTime, Interval, MonthNumbers } from "luxon";
import { Project, ProjectMember, QuickEditText, SubTaskPreData, VirtualSpan } from '@/interface/projectInterface';
import GanttFullText from '@/components/Task/Gantt/GanttFullText.vue';
import GanttTaskComment from '@/components/Task/Gantt/GanttTaskComment.vue';
import { MenuList, Task } from '@/interface/globalInterface';
import TaskCreate from '@/components/Task/TaskCreate.vue';
import GanttMonthPicker from '@/components/Task/Gantt/GanttMonthPicker.vue';
import SubTaskControl from '@/components/Task/SubTaskControl.vue';
import { useBadgeStore } from '@/store/badge';
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive'
import AddIcon from '@/components/Form/AddIcon.vue';
import { useApi } from '@/composables/api';
import UserPanel from '@/components/Global/UserPanel.vue';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import GanttButton from '@/components/Task/Gantt/GanttButton.vue';
import { useMessageUsers } from '@/store/messageUsers';
import { taskStatusBackgrounds } from '@/utils/tools';
import colors from 'assets/colors.json';
import MemberDropDown from '@/components/Global/MemberDropDown.vue';
const props = defineProps<{
    userList: any;
}>();
const auth = useAuthUserStore()
const { width } = useWindowSize()
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
const stickyHeaderRef = useTemplateRef('stickyHeaderRef')
const { width: headerWidth } = useElementSize(stickyHeaderRef)
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
const selectedUser = ref<number[]>([])
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
const suppressMemberWatch = ref(false)
const badge = useBadgeStore()
const messageUsers = useMessageUsers()
const flowInstance = ref<VueFlowStore | null>(null)
const todayLineOffset = ref(-100)
const cursorPos = ref([0, 0])
const beforeState = ref(0)
const scrollableParent = useTemplateRef('scrollableParent')
const updatingTaskId = ref<number | null>(null)
const mainRowTop = 10
const mainBarTop = 23
const mainBarHeight = 24
const subRowStart = 92
const subRowGap = 34
const subBarHeight = 12
const ownTaskSubRowOffset = 10
type GanttPanelMode = 'compact' | 'normal' | 'wide'
const ganttPanelStorageKey = 'project-task-calendar-panel-mode'
const ganttPanelMode = ref<GanttPanelMode>('normal')
const ganttPanelModeOptions: { value: GanttPanelMode, label: string, title: string }[] = [
    { value: 'compact', label: '狭', title: 'ガントを広く表示' },
    { value: 'normal', label: '標', title: '標準表示' },
    { value: 'wide', label: '広', title: '詳細を広く表示' },
]
const isGanttPanelMode = (value: string | null): value is GanttPanelMode => {
    return value === 'compact' || value === 'normal' || value === 'wide'
}
const setGanttPanelMode = (mode: GanttPanelMode) => {
    ganttPanelMode.value = mode
}
const sideColumnWidth = computed(() => {
    const mobile = width.value < 720
    if (ganttPanelMode.value === 'compact') return 0
    if (ganttPanelMode.value === 'wide') {
        return mobile
            ? Math.min(420, Math.max(320, width.value - 40))
            : Math.min(680, Math.max(500, Math.round(width.value * 0.45)))
    }
    return mobile ? 230 : 300
})
const mainExecutorPreviewLimit = computed(() => ganttPanelMode.value === 'wide' ? 7 : 3)
const subExecutorPreviewLimit = computed(() => ganttPanelMode.value === 'wide' ? 6 : 2)
type GanttMode = 'year' | 'month'
const ganttModeOptions: { value: GanttMode, label: string }[] = [
    { value: 'year', label: '年' },
    { value: 'month', label: '月' },
]

const uniqueProjectUsers = (target: Project | null) => {
    const users = [...(target?.manager ?? []), ...(target?.members ?? [])]
    const seen = new Set<number>()

    return users.filter((user): user is ProjectMember => {
        const id = Number(user?.id)
        if (!id || seen.has(id)) return false
        seen.add(id)
        return true
    })
}

const projectAssignableUsers = computed(() => uniqueProjectUsers(project.value))

const sameNumberList = (left: number[], right: number[]) => {
    if (left.length !== right.length) return false
    const sortedLeft = [...left].sort((a, b) => a - b)
    const sortedRight = [...right].sort((a, b) => a - b)
    return sortedLeft.every((value, index) => value === sortedRight[index])
}

const defaultSelectedUsersForProject = (target: Project | null) => {
    const projectUserIds = uniqueProjectUsers(target).map(user => Number(user.id))
    const activeUserId = Number(auth.activeUser.id)

    if (activeUserId && projectUserIds.includes(activeUserId)) {
        return [activeUserId]
    }

    return projectUserIds
}

const setSelectedUsersSilently = (users: number[]) => {
    suppressMemberWatch.value = true
    selectedUser.value = users
}

const applyDefaultMemberSelection = () => {
    const defaultUsers = defaultSelectedUsersForProject(project.value)
    if (!sameNumberList(selectedUser.value, defaultUsers)) {
        setSelectedUsersSilently(defaultUsers)
        return true
    }

    return false
}

const defaultTimelineYear = computed(() => {
    return DateTime.now().year
})

const defaultMonthForYear = (year: number) => {
    const now = DateTime.now()
    if (year === now.year) return now.month
    return 1
}

const ganttMode = computed<GanttMode>(() => virtualSpan.selectedMonth ? 'month' : 'year')

const ganttPickerYear = computed<number>({
    get: () => virtualSpan.selectedYear ?? defaultTimelineYear.value,
    set: (year) => {
        virtualSpan.selectedYear = year || defaultTimelineYear.value
    },
})

const ganttPickerMonth = computed<MonthNumbers>({
    get: () => (virtualSpan.selectedMonth ?? defaultMonthForYear(ganttPickerYear.value)) as MonthNumbers,
    set: (month) => {
        virtualSpan.selectedMonth = month
    },
})

const setYearlyTimeline = () => {
    virtualSpan.selectedYear = defaultTimelineYear.value
    virtualSpan.selectedMonth = null
}

const setMonthlyTimeline = () => {
    const year = defaultTimelineYear.value
    virtualSpan.selectedYear = year
    virtualSpan.selectedMonth = defaultMonthForYear(year)
}

const syncGanttPeriod = () => {
    if (!virtualSpan.selectedYear) {
        virtualSpan.selectedYear = defaultTimelineYear.value
    }
    if (ganttMode.value === 'month' && !virtualSpan.selectedMonth) {
        virtualSpan.selectedMonth = defaultMonthForYear(virtualSpan.selectedYear)
    }
}

const scrollTodayIntoView = async (behavior: ScrollBehavior = 'smooth') => {
    if (!todayCheck.value) return
    await nextTick()
    if (!scrollableParent.value) return
    const targetLeft = sideColumnWidth.value + (timelineWidth.value * todayOffset.value / 100) - (scrollableParent.value.clientWidth / 2)
    scrollableParent.value.scrollTo({
        left: Math.max(0, targetLeft),
        behavior,
    })
}

const setGanttMode = async (mode: GanttMode) => {
    const year = virtualSpan.selectedYear ?? defaultTimelineYear.value
    virtualSpan.selectedYear = year
    virtualSpan.selectedMonth = mode === 'month'
        ? virtualSpan.selectedMonth ?? defaultMonthForYear(year)
        : null
    await scrollTodayIntoView()
}

const setGanttPickerDate = async () => {
    syncGanttPeriod()
    await scrollTodayIntoView()
}
onMounted(async () => {

    window.addEventListener("mouseup", onMouseUp);
    const storedPanelMode = window.localStorage.getItem(ganttPanelStorageKey)
    if (isGanttPanelMode(storedPanelMode)) {
        ganttPanelMode.value = storedPanelMode
    }
    await getTask(0)
    await badge.getTaskCommentBadge()
    const defaultSelectionChanged = applyDefaultMemberSelection()
    if (defaultSelectionChanged) {
        await getTask()
    }
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

const findTaskById = (taskId: number) => {
    if (!project.value) return null
    const main = project.value.tasks.find(t => t.id == taskId)
    if (main) return main
    return project.value.tasks.flatMap(t => t.sub_tasks ?? []).find(s => s.id == taskId) ?? null
}

const syncTaskCommentCount = (taskId: number, count: number) => {
    const task = findTaskById(taskId)
    if (task) {
        task.comments_count = count
    }
}
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
watch(selectedUser, () => {
    if (suppressMemberWatch.value) {
        suppressMemberWatch.value = false
        return
    }
    getTask()
}, { deep: true })
watch(ganttPanelMode, (mode) => {
    if (typeof window === 'undefined') return
    window.localStorage.setItem(ganttPanelStorageKey, mode)
})
interface TimelineSegment {
    key: string
    label: string
    subLabel?: string
    left: number
    width: number
}

interface GanttBar {
    task: Task
    left: number
    width: number
    top: number
    height: number
    background: string
    color: string
    borderColor: string
    isOwn: boolean
    clippedStart: boolean
    clippedEnd: boolean
}

interface GanttSubTaskRow {
    task: Task
    top: number
    bar: GanttBar | null
}

interface GanttConnector {
    taskId: number
    color: string
    x: number
    top: number
    height: number
    horizontalLeft: number
    horizontalTop: number
    horizontalWidth: number
}

interface GanttGroup {
    task: Task
    bar: GanttBar | null
    subTasks: GanttSubTaskRow[]
    connectors: GanttConnector[]
    height: number
}

const maxDate = (first: DateTime, second: DateTime) => first.toMillis() > second.toMillis() ? first : second
const minDate = (first: DateTime, second: DateTime) => first.toMillis() < second.toMillis() ? first : second
const clampPercent = (value: number) => Math.min(100, Math.max(0, value))

const timelineStart = computed(() => {
    if (virtualSpan.selectedYear && virtualSpan.selectedMonth) {
        return DateTime.fromObject({ year: virtualSpan.selectedYear, month: virtualSpan.selectedMonth, day: 1 }).startOf('day')
    }
    if (virtualSpan.selectedYear) {
        return DateTime.fromObject({ year: virtualSpan.selectedYear, month: 1, day: 1 }).startOf('day')
    }
    return DateTime.fromObject({ year: defaultTimelineYear.value, month: 1, day: 1 }).startOf('day')
})

const timelineEnd = computed(() => {
    if (virtualSpan.selectedYear && virtualSpan.selectedMonth) {
        return DateTime.fromObject({ year: virtualSpan.selectedYear, month: virtualSpan.selectedMonth, day: 1 }).endOf('month').startOf('day')
    }
    if (virtualSpan.selectedYear) {
        return DateTime.fromObject({ year: virtualSpan.selectedYear, month: 12, day: 31 }).startOf('day')
    }
    return DateTime.fromObject({ year: defaultTimelineYear.value, month: 12, day: 31 }).startOf('day')
})

const timelineTotalDays = computed(() => {
    const days = timelineEnd.value.diff(timelineStart.value, 'days').days
    return Math.max(1, Math.floor(days) + 1)
})

const timelineWidth = computed(() => {
    const availableWidth = Math.max(640, width.value - sideColumnWidth.value - 80)
    if (virtualSpan.selectedYear && virtualSpan.selectedMonth) {
        return Math.max(availableWidth, timelineTotalDays.value * 46)
    }
    if (virtualSpan.selectedYear) {
        return Math.max(availableWidth, 12 * 112)
    }
    if (timelineTotalDays.value <= 95) {
        return Math.max(availableWidth, timelineTotalDays.value * 38)
    }
    const monthCount = Math.max(1, Math.ceil(timelineEnd.value.diff(timelineStart.value, 'months').months))
    return Math.max(availableWidth, monthCount * 116)
})

const ganttCanvasWidth = computed(() => sideColumnWidth.value + timelineWidth.value)
const ganttCanvasStyle = computed(() => ({
    width: `${ganttCanvasWidth.value}px`,
    minWidth: `${ganttCanvasWidth.value}px`,
    '--task-gantt-side-width': `${sideColumnWidth.value}px`,
}))

const segmentFromRange = (start: DateTime, end: DateTime, label: string, key: string, subLabel?: string): TimelineSegment => {
    const clampedStart = maxDate(start.startOf('day'), timelineStart.value)
    const clampedEnd = minDate(end.startOf('day'), timelineEnd.value)
    const leftDays = Math.max(0, Math.floor(clampedStart.diff(timelineStart.value, 'days').days))
    const durationDays = Math.max(1, Math.floor(clampedEnd.diff(clampedStart, 'days').days) + 1)

    return {
        key,
        label,
        subLabel,
        left: clampPercent((leftDays / timelineTotalDays.value) * 100),
        width: Math.max(0.25, (durationDays / timelineTotalDays.value) * 100),
    }
}

const timelineMajorSegments = computed<TimelineSegment[]>(() => {
    if (virtualSpan.selectedYear && virtualSpan.selectedMonth) {
        const label = timelineStart.value.toFormat('yyyy LLLL')
        return [segmentFromRange(timelineStart.value, timelineEnd.value, label, label)]
    }
    if (virtualSpan.selectedYear) {
        const label = virtualSpan.selectedYear.toString()
        return [segmentFromRange(timelineStart.value, timelineEnd.value, label, label)]
    }

    const segments: TimelineSegment[] = []
    let cursor = timelineStart.value.startOf('month')
    while (cursor.toMillis() <= timelineEnd.value.toMillis()) {
        const monthEnd = cursor.endOf('month').startOf('day')
        const label = cursor.toFormat('LLL yyyy')
        segments.push(segmentFromRange(cursor, monthEnd, label, label))
        cursor = cursor.plus({ months: 1 })
    }
    return segments
})

const timelineMinorSegments = computed<TimelineSegment[]>(() => {
    const segments: TimelineSegment[] = []
    if (virtualSpan.selectedYear && virtualSpan.selectedMonth) {
        let cursor = timelineStart.value
        while (cursor.toMillis() <= timelineEnd.value.toMillis()) {
            const key = cursor.toISODate() ?? cursor.toString()
            segments.push(segmentFromRange(cursor, cursor, cursor.toFormat('d'), key, cursor.toFormat('ccc')))
            cursor = cursor.plus({ days: 1 })
        }
        return segments
    }

    if (virtualSpan.selectedYear) {
        let cursor = timelineStart.value
        while (cursor.toMillis() <= timelineEnd.value.toMillis()) {
            const monthEnd = cursor.endOf('month').startOf('day')
            const key = cursor.toFormat('yyyy-LL')
            segments.push(segmentFromRange(cursor, monthEnd, cursor.toFormat('LLL'), key))
            cursor = cursor.plus({ months: 1 })
        }
        return segments
    }

    let cursor = timelineStart.value
    while (cursor.toMillis() <= timelineEnd.value.toMillis()) {
        const weekEnd = minDate(cursor.plus({ days: 6 }), minDate(cursor.endOf('month').startOf('day'), timelineEnd.value))
        const weekIndex = Math.floor((cursor.day - 1) / 7) + 1
        const key = `${cursor.toISODate()}-${weekIndex}`
        segments.push(segmentFromRange(cursor, weekEnd, `W${weekIndex}`, key))
        cursor = weekEnd.plus({ days: 1 })
    }
    return segments
})

const timelineGridSegments = computed(() => timelineMinorSegments.value)

const timelineRangeText = computed(() => `${timelineStart.value.toFormat('yyyy/LL/dd')} - ${timelineEnd.value.toFormat('yyyy/LL/dd')}`)

const todayCheck = computed(() => {
    const today = DateTime.now()
    return today.startOf('day').toMillis() >= timelineStart.value.toMillis() && today.startOf('day').toMillis() <= timelineEnd.value.toMillis()
})

const isCurrentMonthSelected = computed(() => {
    const today = DateTime.now()
    return virtualSpan.selectedYear === today.year && virtualSpan.selectedMonth === today.month
})

const todayOffset = computed(() => {
    if (!todayCheck.value) return -100
    const today = DateTime.now()
    const elapsedDays = today.diff(timelineStart.value, 'days').days
    return clampPercent((elapsedDays / timelineTotalDays.value) * 100)
})

const jumpToToday = async () => {
    const today = DateTime.now()
    virtualSpan.selectedYear = today.year
    virtualSpan.selectedMonth = today.month
    await scrollTodayIntoView()
}

const normalTaskRange = (task: Task) => {
    const start = DateTime.fromISO(task.start_at).startOf('day')
    const end = DateTime.fromISO(task.end_at).startOf('day')
    if (!start.isValid || !end.isValid) return null
    return start.toMillis() <= end.toMillis()
        ? { start, end }
        : { start: end, end: start }
}

const taskOverlapsTimeline = (task: Task) => {
    const range = normalTaskRange(task)
    if (!range) return false
    return range.start.toMillis() <= timelineEnd.value.toMillis() && range.end.toMillis() >= timelineStart.value.toMillis()
}

const filteredTasks = computed(() => {
    if (!project.value) return []
    return project.value.tasks.filter((task) => {
        return taskOverlapsTimeline(task) || task.sub_tasks?.some((subTask) => taskOverlapsTimeline(subTask))
    })
})

const myTaskColor = computed(() => {
    const colorIndex = auth.user && auth.user.color ? auth.user.color : 0
    return colors[colorIndex]?.light ?? colors.find(color => color.id == colorIndex)?.light ?? 'var(--task-background)'
})

const isOwnTask = (task: Task) => {
    return task.executors?.some(executor => executor.id == auth.activeUser.id) ?? false
}

const taskPalette = (task: Task) => {
    const own = isOwnTask(task)
    return {
        background: own ? myTaskColor.value : 'var(--task-background)',
        color: own ? '#000' : 'var(--primary-color)',
        borderColor: own ? myTaskColor.value : 'var(--calendarBorder)',
        isOwn: own,
    }
}

const barAnchorOffset = (bar: GanttBar, pixelOffset: number) => {
    const percentOffset = (pixelOffset / timelineWidth.value) * 100
    const maxOffset = Math.max(0, bar.width / 2)
    return clampPercent(bar.left + Math.min(percentOffset, maxOffset))
}

const buildTaskBar = (task: Task, top: number, height: number): GanttBar | null => {
    const range = normalTaskRange(task)
    if (!range || !taskOverlapsTimeline(task)) return null

    const clampedStart = maxDate(range.start, timelineStart.value)
    const clampedEnd = minDate(range.end, timelineEnd.value)
    const leftDays = Math.max(0, clampedStart.diff(timelineStart.value, 'days').days)
    const durationDays = Math.max(1, Math.floor(clampedEnd.diff(clampedStart, 'days').days) + 1)
    const palette = taskPalette(task)

    return {
        task,
        top,
        height,
        left: clampPercent((leftDays / timelineTotalDays.value) * 100),
        width: Math.max(0.6, (durationDays / timelineTotalDays.value) * 100),
        background: palette.background,
        color: palette.color,
        borderColor: palette.borderColor,
        isOwn: palette.isOwn,
        clippedStart: range.start.toMillis() < timelineStart.value.toMillis(),
        clippedEnd: range.end.toMillis() > timelineEnd.value.toMillis(),
    }
}

const ganttGroups = computed<GanttGroup[]>(() => {
    return filteredTasks.value.map((task) => {
        const bar = buildTaskBar(task, mainBarTop, mainBarHeight)
        const groupSubRowStart = subRowStart + (isOwnTask(task) ? ownTaskSubRowOffset : 0)
        const subTasks = (task.sub_tasks ?? [])
            .filter(subTask => taskOverlapsTimeline(subTask))
            .map((subTask, index) => ({
                task: subTask,
                top: groupSubRowStart + index * subRowGap,
                bar: buildTaskBar(subTask, groupSubRowStart + index * subRowGap, subBarHeight),
            }))
        const height = Math.max(92, groupSubRowStart + subTasks.length * subRowGap + 18)
        const connectors = subTasks.flatMap((subTask) => {
            if (!bar || !subTask.bar) return []
            const branchX = barAnchorOffset(bar, 11)
            const childX = barAnchorOffset(subTask.bar, 7)
            const horizontalLeft = Math.min(branchX, childX)
            const horizontalTop = subTask.bar.top + subTask.bar.height / 2
            const top = bar.top + bar.height - 1
            return [{
                taskId: subTask.task.id,
                color: taskPalette(subTask.task).borderColor,
                x: branchX,
                top,
                height: Math.max(0, horizontalTop - top),
                horizontalLeft,
                horizontalTop,
                horizontalWidth: Math.max(0.2, Math.abs(childX - branchX)),
            }]
        })

        return { task, bar, subTasks, connectors, height }
    }).filter(group => group.bar || group.subTasks.length)
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
    if (!virtualSpan.selectedYear) {
        setMonthlyTimeline()
    }

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
    const data = await api.get('/get_gantt_project_tasks', { id: id, user_ids: selectedUser.value, progress_flag: selectedStatus.value } )
    project.value = data.project
    loader.value++
    loading.value = false
    if (project.value && load == 0) {
        virtualSpan.interval = Interval.fromDateTimes(DateTime.fromISO(project.value.date_start), DateTime.fromISO(project.value.date_end))
        setMonthlyTimeline()
        const spanIncludesToday = virtualSpan.interval.contains(DateTime.now())
        if(spanIncludesToday) {
            drawInitalTodayLine()
            await scrollTodayIntoView('auto')
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

const segmentStyle = (segment: TimelineSegment) => ({
    left: `${segment.left}%`,
    width: `${segment.width}%`,
})

const barStyle = (bar: GanttBar | null) => {
    if (!bar) return { display: 'none' }
    return {
        left: `${bar.left}%`,
        top: `${bar.top}px`,
        width: `${bar.width}%`,
        height: `${bar.height}px`,
        minWidth: bar.height === mainBarHeight ? '34px' : '28px',
        backgroundColor: bar.background,
        color: bar.color,
        borderColor: bar.borderColor,
    }
}

const barClass = (bar: GanttBar | null) => ({
    'is-own': !!bar?.isOwn,
    'is-clipped-start': !!bar?.clippedStart,
    'is-clipped-end': !!bar?.clippedEnd,
    'has-unread-comment': !!bar?.task && hasUnreadComments(bar.task),
})

const connectorVerticalStyle = (connector: GanttConnector) => ({
    left: `${connector.x}%`,
    top: `${connector.top}px`,
    height: `${connector.height}px`,
    borderColor: connector.color,
})

const connectorHorizontalStyle = (connector: GanttConnector) => ({
    left: `${connector.horizontalLeft}%`,
    top: `${connector.horizontalTop}px`,
    width: `${connector.horizontalWidth}%`,
    borderColor: connector.color,
})

const dateInputValue = (value: string) => {
    const parsed = DateTime.fromISO(value)
    return parsed.isValid ? parsed.toISODate() ?? '' : ''
}

const taskDateRange = (task: Task, compact = true) => {
    const start = DateTime.fromISO(task.start_at)
    const end = DateTime.fromISO(task.end_at)
    if (!start.isValid || !end.isValid) return ''
    const format = compact && virtualSpan.selectedYear && start.year === end.year && start.year === virtualSpan.selectedYear
        ? 'M/d'
        : 'yyyy/M/d'
    return `${start.toFormat(format)} - ${end.toFormat(format)}`
}

const taskLineTitle = (task: Task) => {
    return task.title || task.remarks || `タスク #${task.id}`
}

const taskMemberTitle = (task: Task) => {
    const names = (task.executors ?? [])
        .map(user => user.name)
        .filter((name): name is string => !!name)
    return names.length ? names.join(', ') : 'メンバーなし'
}

const taskTooltip = (task: Task) => {
    return [
        taskLineTitle(task),
        taskDateRange(task, false),
        `メンバー: ${taskMemberTitle(task)}`,
        taskCommentTitle(task),
    ].join('\n')
}

const taskExecutor = (task: Task) => {
    return task.executors?.find(executor => executor.id == auth.activeUser.id)
}

const taskProgress = (task: Task) => {
    return taskExecutor(task)?.pivot.progress_flag ?? 0
}

const includesMe = (task: Task) => {
    return [...task.executors ?? [], ...task.supervisors ?? []].map(user => user.id).includes(Number(auth.activeUser.id))
}

const canModifyTask = (task: Task) => {
    const managerIds = project.value?.manager?.map(manager => manager.id) ?? []
    return includesMe(task) || managerIds.includes(Number(auth.activeUser.id))
}

const openTaskText = (task: Task) => {
    Object.assign(quickEdit, {
        text: task.remarks || task.title || '',
        id: task.id,
        editable: canModifyTask(task),
    })
}

const taskMenuItems = (task: Task, mainTask: Task | null = null): MenuList[] => {
    const items: MenuList[] = []
    if (!task.parent_task_id) {
        items.push({ title: 'サブタスク追加', action: () => Object.assign(subPreData, { mainTaskId: task.id, subTaskData: {}, active: true }) })
        items.push({ title: '編集', action: () => createTask(task) })
    } else if (mainTask) {
        items.push({ title: '編集', action: () => Object.assign(subPreData, { mainTaskId: mainTask.id, subTaskData: task, active: true }) })
    }
    items.push({ title: '削除', action: () => remove(task) })
    return items
}

const showTaskUsers = (task: Task) => {
    messageUsers.setMessageUsers({
        active: true,
        userList: task.executors ?? [],
        title: 'タスクメンバー',
        isTask: true,
    })
}

// Memoized once per render: taskUnreadCommentCount was called ~4x per task
// (title, bar, tooltip, comment button), each doing an O(badges) filter → O(tasks*badges).
const taskUnreadMap = computed(() => {
    const map = new Map<number, number>()
    const walk = (tasks: Task[] = []) => {
        for (const task of tasks) {
            const badges = badge.taskCommentBadgeByFilter([{ by: 'task_id', value: task.id }])
            map.set(task.id, badges?.[0]?.comments ?? 0)
            if (task.sub_tasks?.length) walk(task.sub_tasks)
        }
    }
    walk(project.value?.tasks ?? [])
    return map
})
const taskUnreadCommentCount = (task: Task) => taskUnreadMap.value.get(task.id) ?? 0

const hasUnreadComments = (task: Task) => taskUnreadCommentCount(task) > 0

const unreadCommentLabel = (task: Task) => {
    const unread = taskUnreadCommentCount(task)
    return unread > 9 ? '9+' : unread.toString()
}

// Total = the task's real comment count. (Was `comments_count || unread || 0`,
// which showed the UNREAD count as the total whenever comments_count was 0.)
const taskCommentTotal = (task: Task) => task.comments_count ?? 0

const taskCommentTitle = (task: Task) => {
    const total = taskCommentTotal(task)
    const unread = taskUnreadCommentCount(task)
    return unread ? `コメント: ${total}（未読 ${unread}）` : `コメント: ${total}`
}

const adjacentPeriodRange = (direction: -1 | 1) => {
    if (virtualSpan.selectedYear && virtualSpan.selectedMonth) {
        const period = DateTime.fromObject({
            year: virtualSpan.selectedYear,
            month: virtualSpan.selectedMonth,
            day: 1,
        }).plus({ months: direction })
        return {
            start: period.startOf('month'),
            end: period.endOf('month').startOf('day'),
            label: period.toFormat('yyyy年M月'),
        }
    }

    const year = (virtualSpan.selectedYear ?? defaultTimelineYear.value) + direction
    return {
        start: DateTime.fromObject({ year, month: 1, day: 1 }).startOf('day'),
        end: DateTime.fromObject({ year, month: 12, day: 31 }).startOf('day'),
        label: `${year}年`,
    }
}

const taskOverlapsDateRange = (task: Task, start: DateTime, end: DateTime) => {
    const range = normalTaskRange(task)
    if (!range) return false
    return range.start.toMillis() <= end.toMillis() && range.end.toMillis() >= start.toMillis()
}

const allProjectTasks = computed(() => {
    return project.value?.tasks.flatMap(task => [task, ...(task.sub_tasks ?? [])]) ?? []
})

const adjacentPeriodUnreadCommentCount = (direction: -1 | 1) => {
    const period = adjacentPeriodRange(direction)
    return allProjectTasks.value.reduce((sum, task) => {
        const unread = taskUnreadCommentCount(task)
        if (!unread || taskOverlapsTimeline(task)) return sum
        return taskOverlapsDateRange(task, period.start, period.end) ? sum + unread : sum
    }, 0)
}

const previousPeriodUnreadCommentCount = computed(() => adjacentPeriodUnreadCommentCount(-1))
const nextPeriodUnreadCommentCount = computed(() => adjacentPeriodUnreadCommentCount(1))

const adjacentPeriodUnreadTitle = (direction: -1 | 1) => {
    const period = adjacentPeriodRange(direction)
    const count = direction < 0 ? previousPeriodUnreadCommentCount.value : nextPeriodUnreadCommentCount.value
    const action = direction < 0 ? '前の期間' : '次の期間'
    return count > 0 ? `${period.label}の未読コメント: ${count}` : action
}

const previousPeriodUnreadTitle = computed(() => adjacentPeriodUnreadTitle(-1))
const nextPeriodUnreadTitle = computed(() => adjacentPeriodUnreadTitle(1))

const updateDate = async (task: Task, event: Event, column: 'start_at' | 'end_at') => {
    const target = event.target as HTMLInputElement
    if (!target.value) return
    await api.patch('/quick_edit_task', {
        id: task.id,
        column,
        value: target.value,
    }, {
        toast: '更新しました',
    })
    await getTask()
}

const updateStatus = async (task: Task, flag: number) => {
    updatingTaskId.value = task.id
    try {
        await api.patch('/complete_task', {
            id: task.id,
            params: { progress_flag: flag },
        })
        await getTask()
        badge.getTaskBadge()
    } finally {
        updatingTaskId.value = null
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
const onMouseDown = (ev: MouseEvent) => {
    const target = ev.target as HTMLElement
    if (target.closest('button, input, textarea, select, .rt')) return
    cursorPos.value = [ev.pageX, ev.pageY];
    beforeState.value = ev.pageX
    window.addEventListener("mousemove", onMouseHold);
}

/** @param {MouseEvent} ev */
const onMouseUp = (ev: MouseEvent) => {
    window.removeEventListener("mousemove", onMouseHold);
}

/** @param {MouseEvent} ev */
const onMouseHold = (ev: MouseEvent) => {
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
.task-gantt-toolbar-shell {
    position: relative;
    z-index: 24;
    overflow: visible;
}

#taskMenuHeader {
    position: relative;
    z-index: 95;
    overflow: visible;
    padding-left: 15px;
    padding-right: 15px;
}

#taskMenuHeader > div {
    overflow: visible;
}

#taskSpanSelector,
#taskCategory,
#taskStatusCategroy {
    z-index: 160 !important;
}

.task-gantt-view-toggle {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--normalBorder);
    background: var(--background-color);
}

.task-gantt-chip-button {
    min-width: 36px;
    height: 28px;
    border: 0;
    border-right: 1px solid var(--normalBorder);
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    font-size: 12px;
    line-height: 1;
}

.task-gantt-chip-button:last-child {
    border-right: 0;
}

.task-gantt-chip-button.active {
    border-color: var(--primary-button);
    background: var(--primary-button);
    color: #fff;
}

.task-gantt-panel-toggle .task-gantt-chip-button {
    min-width: 32px;
}

.task-gantt-today-button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.task-gantt-wrap {
    position: relative;
    z-index: 1;
    flex: 1;
    min-height: 0;
    width: 100%;
    background: var(--background-color);
}

.task-gantt-scroll {
    height: 100%;
    width: 100%;
    overflow: auto;
    cursor: grab;
    overscroll-behavior: contain;
}

.task-gantt-scroll:active {
    cursor: grabbing;
}

.task-gantt-canvas {
    box-sizing: border-box;
    min-height: 100%;
    color: var(--primary-color);
    transition: width 0.18s ease, min-width 0.18s ease;
}

.task-gantt-header-row {
    position: sticky;
    top: 0;
    z-index: 25;
    display: flex;
    height: 70px;
    min-height: 70px;
    background: var(--background-color);
}

.task-gantt-left-header {
    position: sticky;
    left: 0;
    z-index: 22;
    box-sizing: border-box;
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 8px;
    padding: 0;
    border-right: 1px solid var(--calendarBorder);
    border-bottom: 1px solid var(--calendarBorder);
    background: var(--background-color);
}

.task-gantt-left-header,
.task-gantt-left-cell {
    width: var(--task-gantt-side-width);
    max-width: var(--task-gantt-side-width);
    transition: width 0.18s ease, max-width 0.18s ease, border-color 0.18s ease;
}

.task-gantt-project-title {
    max-width: calc(100% - 32px);
    margin: 0 16px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 15px;
    line-height: 1.25;
}

.task-gantt-project-range {
    max-width: calc(100% - 32px);
    margin: 0 16px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #89898F;
    font-size: 11px;
    line-height: 1.2;
}

.task-gantt-panel-wide .task-gantt-project-title {
    font-size: 16px;
}

.task-gantt-time-header {
    position: relative;
    box-sizing: border-box;
    flex: 0 0 auto;
    height: 70px;
    overflow: visible;
    background: var(--background-color);
    box-shadow: inset 0 -1px 0 var(--calendarBorder);
    transition: width 0.18s ease;
}

.task-gantt-major-row,
.task-gantt-minor-row {
    position: absolute;
    left: 0;
    width: 100%;
}

.task-gantt-major-row {
    top: 0;
    height: 34px;
}

.task-gantt-period-controls {
    position: absolute;
    top: 0;
    left: 50%;
    z-index: 8;
    display: flex;
    align-items: center;
    height: 34px;
    transform: translateX(-50%);
}

.task-gantt-period-picker {
    display: flex;
    align-items: center;
    min-width: 0;
}

.task-gantt-minor-row {
    top: 34px;
    height: 36px;
}

.task-gantt-major-cell,
.task-gantt-minor-cell {
    position: absolute;
    top: 0;
    bottom: 0;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    overflow: hidden;
    border-right: 1px solid var(--calendarBorder);
    text-align: center;
    white-space: nowrap;
}

.task-gantt-major-cell {
    font-size: 15px;
}

.task-gantt-minor-cell {
    flex-direction: column;
    gap: 1px;
    border-top: 1px solid var(--calendarBorder);
    color: #89898F;
    font-size: 12px;
}

.task-gantt-minor-cell small {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 9px;
    line-height: 1;
}

.task-gantt-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 240px;
    color: #89898F;
    font-size: 13px;
}

.task-gantt-body-grid {
    position: relative;
    z-index: 1;
}

.task-gantt-group {
    position: relative;
    z-index: 1;
    display: flex;
    border-bottom: 1px solid color-mix(in srgb, var(--calendarBorder) 72%, transparent);
}

.task-gantt-group:hover,
.task-gantt-group:has(.rt .active) {
    z-index: 35;
}

.task-gantt-left-cell {
    position: sticky;
    left: 0;
    z-index: 10;
    box-sizing: border-box;
    flex: 0 0 auto;
    overflow: visible;
    border-right: 1px solid var(--calendarBorder);
    background: var(--background-color);
}

.task-gantt-panel-compact .task-gantt-left-header,
.task-gantt-panel-compact .task-gantt-left-cell {
    overflow: hidden;
    border-right: 0;
    pointer-events: none;
}

.task-gantt-left-cell .rt {
    z-index: 40;
}

.task-gantt-left-cell .rt:has(.active),
.task-gantt-left-cell .b-m {
    z-index: 180;
}

.task-gantt-sub-menu {
    flex: 0 0 auto;
}

.task-gantt-sub-menu .boardMenuContainer {
    width: 20px;
    height: 20px;
    min-width: 20px;
    min-height: 20px;
    color: #89898F;
}

.task-gantt-sub-menu .dot-menu {
    height: 11px;
    fill: currentColor;
}

.task-gantt-sub-menu .b-m li {
    height: 28px;
    color: var(--primary-color);
    font-size: 12px;
    line-height: 28px;
}

.task-gantt-left-main,
.task-gantt-left-sub {
    position: absolute;
    left: 12px;
    right: 10px;
}

.task-gantt-left-main {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.task-gantt-left-title-line,
.task-gantt-left-tools {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.task-gantt-left-title-line {
    justify-content: space-between;
}

.task-gantt-title-button,
.task-gantt-sub-title {
    min-width: 0;
    overflow: hidden;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    font: inherit;
    line-height: 1.25;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.task-gantt-title-button {
    font-size: 14px;
}

.task-gantt-sub-title {
    flex: 1;
    font-size: 12px;
}

.task-gantt-panel-wide .task-gantt-sub-title {
    font-size: 12px;
}

.task-gantt-sub-meta {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 6px;
    min-width: 0;
}

.task-gantt-left-meta,
.task-gantt-sub-range {
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 0;
    overflow: hidden;
    color: #89898F;
    font-size: 10px;
    line-height: 1.2;
    white-space: nowrap;
}

.task-gantt-left-meta {
    flex: 0 0 auto;
}

.task-gantt-sub-range {
    flex: 0 1 auto;
    max-width: 72px;
    text-overflow: ellipsis;
}

.task-gantt-panel-wide .task-gantt-sub-range {
    max-width: 136px;
}

.task-gantt-sub-users,
.task-gantt-sub-comment {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    font: inherit;
}

.task-gantt-sub-users {
    max-width: 46px;
    min-width: 16px;
    padding: 0;
}

.task-gantt-panel-wide .task-gantt-sub-users {
    max-width: 132px;
}

.task-gantt-sub-avatar {
    margin-right: -5px;
}

.task-gantt-sub-avatar .task-gantt-user-status {
    width: 5px;
    height: 5px;
}

.task-gantt-sub-user-more {
    margin-left: 5px;
    color: #89898F;
    font-size: 9px;
    line-height: 1;
}

.task-gantt-sub-comment {
    min-width: 18px;
    height: 18px;
    justify-content: center;
    padding: 0 4px;
    border: 1px solid color-mix(in srgb, var(--calendarBorder) 72%, transparent);
    border-radius: 8px;
    color: #89898F;
    font-size: 9px;
    line-height: 1;
}

.task-gantt-date-input {
    width: 92px;
    min-width: 0;
    border: 1px solid color-mix(in srgb, var(--calendarBorder) 72%, transparent);
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 10px;
    line-height: 1.2;
}

.task-gantt-left-tools {
    min-height: 22px;
    max-width: 100%;
    overflow: visible;
    flex-wrap: nowrap;
    margin-top: 2px;
}

.task-gantt-panel-wide .task-gantt-left-tools {
    gap: 14px;
}

.task-gantt-left-tools .c-button {
    flex: 0 0 auto;
    height: 20px !important;
    max-width: 64px;
    overflow: hidden;
    font-size: 11px;
    line-height: 1.2;
}

.task-gantt-left-tools .primary-selection {
    overflow: hidden;
    text-overflow: ellipsis;
}

.task-gantt-users,
.task-gantt-comment {
    display: flex;
    align-items: center;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    font: inherit;
}

.task-gantt-users {
    gap: 0;
    min-width: 28px;
    padding: 0;
}

.task-gantt-user-avatar {
    position: relative;
    display: inline-flex;
    margin-right: -4px;
}

.task-gantt-user-status {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.task-gantt-user-more {
    margin-left: 6px;
    color: #89898F;
    font-size: 10px;
}

.task-gantt-comment {
    min-width: 20px;
    height: 20px;
    justify-content: center;
    padding: 0 5px;
    border: 1px solid color-mix(in srgb, var(--calendarBorder) 72%, transparent);
    border-radius: 8px;
    font-size: 10px;
    line-height: 1;
}

.task-gantt-comment,
.task-gantt-sub-comment {
    position: relative;
    overflow: visible;
}

.task-gantt-comment.has-unread,
.task-gantt-sub-comment.has-unread {
    border-color: #f28c28;
    background: color-mix(in srgb, #f28c28 12%, transparent);
    color: #f28c28;
}

.task-gantt-comment-total {
    line-height: 1;
}

.task-gantt-comment-unread {
    position: absolute;
    top: -7px;
    right: -7px;
    z-index: 2;
    min-width: 14px;
    height: 14px;
    padding: 0 3px;
    border-radius: 999px;
    background: #f28c28;
    box-shadow: 0 0 0 1px var(--background-color);
    color: #fff;
    font-size: 9px;
    line-height: 14px;
    text-align: center;
}

.task-gantt-sub-comment .task-gantt-comment-unread {
    top: -6px;
    right: -6px;
    min-width: 13px;
    height: 13px;
    font-size: 8px;
    line-height: 13px;
}

.task-gantt-left-sub {
    display: flex;
    align-items: center;
    gap: 8px;
    height: 24px;
    min-width: 0;
    padding-left: 14px;
}

.task-gantt-left-sub .c-button {
    flex: 0 0 auto;
    height: 18px !important;
    max-width: 54px;
    overflow: hidden;
    font-size: 10px;
    line-height: 1.15;
}

.task-gantt-left-sub .primary-selection {
    overflow: hidden;
    text-overflow: ellipsis;
}

.task-gantt-left-sub::before {
    position: absolute;
    left: 0;
    width: 7px;
    height: 7px;
    border-left: 1px solid var(--calendarBorder);
    border-bottom: 1px solid var(--calendarBorder);
    content: "";
}

.task-gantt-track {
    position: relative;
    flex: 0 0 auto;
    overflow: hidden;
    transition: width 0.18s ease;
    background:
        linear-gradient(to bottom, color-mix(in srgb, var(--calendarBorder) 18%, transparent), transparent 1px) 0 0 / 100% 34px;
}

.task-gantt-grid-segment {
    position: absolute;
    top: 0;
    bottom: 0;
    box-sizing: border-box;
    border-right: 1px solid color-mix(in srgb, var(--calendarBorder) 68%, transparent);
    pointer-events: none;
}

.task-gantt-bar {
    position: absolute;
    z-index: 4;
    display: flex;
    align-items: center;
    overflow: hidden;
    border: 1px solid;
    box-shadow: 0 1px 2px rgb(0 0 0 / 8%);
    cursor: pointer;
    font: inherit;
    line-height: 1;
    text-align: left;
    white-space: nowrap;
}

.task-gantt-bar-title {
    display: block;
    flex: 1 1 auto;
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
    padding: 0 9px;
    text-overflow: ellipsis;
}

.task-gantt-main-bar {
    border-width: 2px;
    font-size: 12px;
}

.task-gantt-sub-bar {
    opacity: 0.88;
    border-width: 1px;
    font-size: 10px;
}

.task-gantt-bar:not(.is-own) {
    box-shadow: none;
}

.task-gantt-bar.has-unread-comment {
    z-index: 6;
    overflow: visible;
}

.task-gantt-bar.has-unread-comment .task-gantt-bar-title {
    padding-right: 18px;
}

.task-gantt-bar-unread {
    position: absolute;
    top: -8px;
    right: -8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 999px;
    background: #f28c28;
    box-shadow: 0 0 0 1px var(--background-color);
    color: #fff;
    font-size: 9px;
    line-height: 1;
    pointer-events: none;
}

.task-gantt-sub-bar .task-gantt-bar-unread {
    top: -7px;
    right: -7px;
    min-width: 14px;
    height: 14px;
    font-size: 8px;
}

.task-gantt-bar.is-clipped-start {
    border-top-left-radius: 3px;
    border-bottom-left-radius: 3px;
}

.task-gantt-bar.is-clipped-end {
    border-top-right-radius: 3px;
    border-bottom-right-radius: 3px;
}

.task-gantt-connector-vertical,
.task-gantt-connector-horizontal {
    position: absolute;
    z-index: 3;
    pointer-events: none;
}

.task-gantt-connector-vertical {
    border-left: 2px solid;
}

.task-gantt-connector-horizontal {
    border-top: 2px solid;
}

.task-gantt-today-line,
.task-gantt-track-today {
    position: absolute;
    width: 1px;
    background: #ef4444;
    pointer-events: none;
}

.task-gantt-today-line {
    top: 0;
    bottom: 0;
    z-index: 5;
}

.task-gantt-today-line span {
    position: absolute;
    top: 9px;
    left: -4px;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #ef4444;
}

.task-gantt-track-today {
    top: 0;
    bottom: 0;
    z-index: 2;
    opacity: 0.72;
}

@media screen and (max-width: 720px) {
    .task-gantt-project-title {
        max-width: calc(100% - 20px);
        margin: 0 10px;
        font-size: 13px;
    }

    .task-gantt-project-range {
        max-width: calc(100% - 20px);
        margin: 0 10px;
    }

    .task-gantt-date-input {
        width: 80px;
    }
}

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
