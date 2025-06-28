<template>
    <div class="sub-task-item" :style="{marginLeft: '-3px', width: `100%`}">   
        <div class="sub-task-inner task-card-inner width-smooth partial-opa" :id="`gantt-sub-${task.id}`" 
            :style="{ 
                width: `100%`, 
                background: background, color: color, 
            }"> 
            <div :style="{ 
                    width: isNaN(actualWidth) ? 0 : `calc(100% - ${actualWidth > 15 ? actualWidth : 15}px)`,
                    background: isExecutor || !theme.dark ? 'ffffff80' : 'rgb(101 101 101 / 50%)' 
                }" 
                class="mask-layer">
            </div>
            <div class="flex h-full z-[2]">                
                <div class="flex flex-col gap-[5px] h-full w-full">
                    <div @click="messageUsers.setMessageUsers({ active: true, userList: task.executors, title: 'タスクメンバー', isTask: true })" class="flex cursor-pointer w-fit items-center gap-[3px] min-h-[20px]">
                        <div class="flex w-fit"> 
                            <div v-for="user in task.executors?.slice(0, 3)" class="relative w-fit">
                                <UserPanel :force-color="includesMe ? 'light' : undefined" :disableInstant="true" :user="user" imgStyle="pointer-events: none" :imgClass="!isSubTask ? 'userMidIcon' : 'userSmallIcon'" :size="!isSubTask ? '25' : '15'"/>
                                <div title="タスクが完了しました" v-if="user.pivot.progress_flag > 0" class="completed-badge-large" :style="{background: taskStatusBackgrounds[user.pivot.progress_flag]}"></div>  
                            </div>
                        </div>
                        <div class="mt-[1px]" v-if="task?.executors && task?.executors.length > 3">({{ task?.executors.length }})</div>                
                    </div>
                    <div class="flex">
                        <div :style="{width: task.sub_tasks?.length || isSubTask ? 'calc(100% - 19px)' : '100%'}" class="flex flex-col gap-[5px]">
                            <div class="w-[fit-content] max-w-full overflow-hidden overflow-ellipsis whitespace-nowrap leading-[1.4]" :class="mainTask ? 'text-[14px]' : 'text-[16px]'" @click="emit('setFullText', {text: task.remarks as string, id: task.id as number, editable: includesMe || hasPrivilage})">{{task.remarks}}</div>
                        </div>
                    </div>
                    <div class="text-[12px] relative w-fit">
                        <span :class="['cursor-pointer']" @click="includesMe || hasPrivilage ? quickStart?.showPicker() : false">{{DateTime.fromISO(task.start_at).toLocaleString()}}</span> 
                        <span> ~ </span>
                        <span :class="['cursor-pointer']" @click="includesMe || hasPrivilage ? quickEnd?.showPicker() : false">{{ DateTime.fromISO(task.end_at).toLocaleString() }}</span>
                        <input type="date" ref="quickStart" @change="updateDate($event, 'start_at')" :value="task.start_at" class="absolute invisible left-[0] top-[0]"/>
                        <input type="date" ref="quickEnd" @change="updateDate($event, 'end_at')" :value="task.end_at" class="absolute invisible right-[0] top-[0]"/>
                    </div>
                    <div v-if="isExecutor" class="flex justify-between relative items-end mt-[5px]">
                        <GanttButton viewType="button" :status="isExecutor.pivot.progress_flag" :loading="updating" @action="(flag) => updateStatus(flag)"/>
                        <svg v-if="errorMessages.length" fill="tomato" class="min-w-[15px] min-h-[15px] little-alert-mark" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                            <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                        </svg> 
                        <div class="absolute ml-[5px] overflow-hidden leading-[1.2] bg-[var(--background-color)] flex flex-col gap-[5px] text-[tomato] text-[11px] bottom-[20px] shadow-me p-[5px] little-alert">
                            <p v-for="message in errorMessages" class="overflow-hidden leading-[1.2]">{{ message }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center absolute right-[5px] top-[10px] ">
                        <div @click="emit('setCommentingTaskId', Number(task.id))" class="flex items-start gap-[3px] cursor-pointer mr-[5px]">
                            <svg :fill="color" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33">
                                <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                            </svg>
                            <div v-if="task.comments_count">{{ task.comments_count }}</div>
                            <span v-if="commentBadge" class="text-[#fff] bg-[tomato] min-w-[12px] p-px h-[12px] text-center text-[10px] rounded-[50%] leading-[12px] [text-indent:1px]">
                                {{ commentBadge > 9 ? '9+' : commentBadge }}
                            </span>
                        </div>  
                        <ItemMenu v-if="includesMe || hasPrivilage" :items="buttons"/>
                    </div>
                </div>
            </div>         
        </div>      
    </div>  
</template>
<script setup lang="ts">
import { MenuList, Task } from '@/interface/globalInterface';
import UserPanel from '@/components/Global/UserPanel.vue';
import { computed, inject, ref, useTemplateRef } from 'vue';
import { GanttProjectMethods, GanttProjectMethodsKey } from '@/interface/keys';
import { DateTime, Interval } from 'luxon';
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth';
import GanttButton from './GanttButton.vue';
import { useMessageUsers } from '@/store/messageUsers';
import { useTheme } from '@/store/theme';
import { Project, QuickEditText } from '@/interface/projectInterface';
import { taskStatusBackgrounds } from '@/utils/tools';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { useBadgeStore } from '@/store/badge';
import { useApi } from '@/composables/api';
const badge = useBadgeStore()
const props = defineProps<{
  task: Task
  interval: Interval
  from?: string
  project: Project
  mainTask: Task | null
  actualWidth: number
}>()
const api = useApi()
const emit = defineEmits<{
    setFullText: [data: QuickEditText]
    setCommentingTaskId: [id: number]
}>()
const commentBadge = computed(() => {
    const badges = badge.taskCommentBadgeByFilter([{by: 'task_id', value: props.task.id}])
    return badges && badges.length ? badges[0].comments : 0
})
const theme = useTheme()
const {refreshProject, createTask, remove, addSubTask} = inject(GanttProjectMethodsKey) as GanttProjectMethods
const messageUsers = useMessageUsers()
const auth = useAuthUserStore()
const quickStart = useTemplateRef('quickStart')
const quickEnd = useTemplateRef('quickEnd')
const updating = ref(false)
const maskImage = computed(() => {
    return isNaN(props.actualWidth) ? 'unset' : `linear-gradient(to right, rgba(0, 0, 0, 1) ${(props.actualWidth > 15 ? props.actualWidth : 15)}px, rgba(0, 0, 0, 0.7) ${(props.actualWidth > 15 ? props.actualWidth : 15) + 1}px)`
})
const buttons = computed(() => { 
    const list:MenuList[] = []; 
    function addItem(title: string, action: () => void) {
        list.push({ title, action });
    }
    if(!isSubTask.value){
        addItem('サブタスク追加', () => addSubTask({mainTaskId: props.task.id, subTaskData: {}, active: true}))
        addItem('編集する', () => createTask(props.task as Task))
    }else if(isSubTask.value && props.mainTask){
        addItem('編集する', () => addSubTask({mainTaskId: Number(props.mainTask?.id), subTaskData: props.task, active: true}))
    }
    addItem('削除する', () => remove(props.task))
    return list
   
})
const background = computed(() => {
    const me = props.task?.executors?.filter(ob => ob.id == auth.activeUser.id)
    const colorIndex:number = auth.user && auth.user.color ? auth.user.color : 0
    return me && me.length ? colors[colorIndex]?.light : 'var(--task-background)'
})
const isExecutor = computed(() => {
    return props.task.executors?.find(ob => ob.id == auth.activeUser.id)
})
const color = computed(() => {
    return includesMe.value && theme.dark ? 'var(--background-color)' : 'var(--primary-color)'
})
const updateDate = async(event:Event, column:string) => {
    const target = event.target as HTMLInputElement
    if(!target.value){
        return
    }

    await api.patch(`/quick_edit_task`, {
        id: props.task.id,
        column: column,
        value: target.value
    }, {
        toast: '更新しました。'
    })
    await refreshProject({})

}
const updateStatus = async(flag: number) => {

    const params = {
        id: props.task.id,
        params: {progress_flag: flag}
    }

    await api.patch(`/complete_task`, params, {
        loadingRef: updating,
    })
    await refreshProject({})
 
}
const includesMe = computed(() => {
    return [...props.task.executors ?? [], ...props.task.supervisors ?? [] ].map(u => u.id).includes(Number(auth.activeUser.id))
})

const hasPrivilage = computed(() => {
    return includesMe.value || props.project.manager.map( m => m.id).includes(Number(auth.activeUser.id))
})

const isSubTask = computed(() => {
    return props.task.parent_task_id
})

const errorMessages = computed(() => {
    let messages:string[] = []
    if(props.mainTask){
        if(DateTime.fromISO(props.mainTask.start_at) > DateTime.fromISO(props.task.start_at) || DateTime.fromISO(props.mainTask.end_at) < DateTime.fromISO(props.task.end_at)){
            messages.push('メインタスクの期間内に収まっていません。')
        }
        const mainFinished = props.mainTask.executors.find( e => e.id == auth.activeUser.id)?.pivot.progress_flag === 2
        const subFinished = props.task.executors.find( e => e.id == auth.activeUser.id)?.pivot.progress_flag === 2
        if(mainFinished && !subFinished){
            messages.push('メインタスクは完了しています。')
        }
    }

    
    return messages
})
</script>
<style scoped>
.lg-triangle {
    width: 0px;
    height: 0px;
    border-style: solid;
    border-width: 5.5px 0 5.5px 9px;
    border-color: transparent transparent transparent v-bind(color);
    transform: rotate(0);
    margin-top: 2px;
}
.connector {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}
.mask-layer{
    position: absolute;
    height: 100%;
    right: 0px;
    top: 0px;
    z-index: 0;
    background: #ffffff80;
    opacity: 0.7;
    pointer-events: none;
}
</style>