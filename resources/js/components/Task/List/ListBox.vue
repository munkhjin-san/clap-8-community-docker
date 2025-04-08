<template>


    <div :class="boxClass">
        <div class="flex justify-between">            
            <div :id="'task_box_' + item.id" class="task-box-inner" :style="{backgroundColor: taskColor.mycolor, color: taskColor.color, position: 'relative', cursor: 'pointer', width: '-webkit-fill-available'}">
                <div :class="[{'justify-between' : isBoard, 'gap-[10px]' : !isBoard},'flex', 'items-center', 'w-full', 'relative']" :style="{ marginTop: responsive.mobile ? '0' : '5px'}">
                    <div class="flex items-center gap-[10px] text-sm overflow-hidden" @click.stop="router.push(`/project/${item.project_record_id}`)" v-if="item.project">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" :fill="taskColor.mycolor === myColor ? '#000' : 'var(--primary-color)'" height="23" width="20" @click.stop="router.push(`/project/gantt-chart/${item.project_record_id}`)" viewBox="0 0 45 32">
                            <path d="M32.084 18.983c0.433 0.409 0.938 0.722 1.492 0.95 0.433 0.18 1.191 0.265 1.732 0.265 0.553 0 1.191-0.096 1.72-0.277 0.553-0.192 1.059-0.529 1.492-0.938s0.782-0.914 1.023-1.456c0.241-0.553 0.373-1.155 0.373-1.756 0.024-1.203-0.457-2.43-1.323-3.284-0.433-0.433-0.938-0.782-1.504-1.011-0.565-0.241-1.179-0.361-1.78-0.361s-1.215 0.12-1.78 0.361c-0.565 0.241-1.083 0.577-1.504 1.011-0.854 0.866-1.347 2.081-1.323 3.284-0.012 1.203 0.517 2.394 1.383 3.212zM33.829 14.28c0.397-0.385 0.926-0.601 1.48-0.601s1.083 0.217 1.48 0.601c0.397 0.385 0.638 0.914 0.65 1.48s-0.18 1.131-0.577 1.552c-0.385 0.433-1.071 0.686-1.564 0.698-0.481 0.012-1.155-0.289-1.54-0.71-0.192-0.217-0.349-0.457-0.445-0.722s-0.144-0.541-0.132-0.83c0-0.553 0.253-1.083 0.65-1.468zM35.308 23.471c1.107 0 2.262 0.289 3.164 0.902 0.926 0.626 1.78 1.684 2.202 2.37 0.325 0.517 0.866 0.77 1.395 0.553 0.517-0.204 0.686-0.83 0.553-1.371-0.12-0.541-0.577-1.395-1.059-2.045-0.505-0.662-1.167-1.323-2.25-1.949-1.215-0.71-2.454-1.035-4.018-1.035s-2.803 0.325-4.018 1.035c-1.083 0.626-1.744 1.287-2.25 1.949-0.493 0.65-0.938 1.504-1.059 2.045s0.048 1.167 0.553 1.371c0.541 0.217 1.083-0.036 1.395-0.553 0.421-0.686 1.275-1.756 2.201-2.37s2.081-0.902 3.188-0.902z"></path>
                            <path d="M43.898 29.931c-1.768-0.18-3.537-0.253-5.305-0.301-0.89-0.024-1.78-0.024-2.671-0.036l-13.353-0.12-10.683-0.048h-8.782c-0.337 0-0.602-0.265-0.602-0.602 0.012-1.889-0.096-7.122-0.144-10.298-0.060-3.188-0.144-7.916-0.204-10.43-0.072-2.514-0.084-5.029-0.325-7.531-0.024-0.301-0.277-0.541-0.589-0.553-0.325-0.012-0.602 0.253-0.638 0.577-0.265 2.49-0.265 4.992-0.337 7.507-0.060 2.514-0.204 8.469-0.241 11.116-0.036 2.635-0.024 8.974 0.060 11.489 0 0-0.096-3.176 0 0.036 0.024 0.686 0.577 1.215 1.263 1.227 1.78 0.012 3.429 0.012 5.209 0.012h5.341l10.683-0.048 13.353-0.12c0.89-0.012 1.78-0.012 2.671-0.036 1.78-0.048 3.561-0.12 5.341-0.301 0.373-0.036 0.662-0.337 0.686-0.722 0-0.433-0.325-0.77-0.734-0.818z"></path>
                            <path d="M6.953 20.547l0.926-1.095 0.938-1.119c0.614-0.758 1.239-1.504 1.853-2.262s1.227-1.504 1.829-2.274c0.313-0.385 0.626-0.782 0.926-1.167 0.072-0.096 0.217-0.096 0.301-0.012l4.439 4.451c0.445 0.421 1.155 0.409 1.588-0.036l0.048-0.060c1.023-1.083 2.033-2.201 3.020-3.308 0.986-1.119 1.961-2.25 2.923-3.392 0.77-0.914 2.37-2.875 3.056-3.717 0.096-0.108 0.277-0.036 0.265 0.108-0.060 0.517-0.144 1.299-0.18 1.72-0.060 0.662-0.156 1.323-0.060 2.021 0.024 0.168 0.144 0.313 0.325 0.361 0.204 0.048 0.397-0.072 0.493-0.253 0.301-0.614 0.421-1.263 0.577-1.901 0.156-0.65 0.301-1.299 0.445-1.949s0.289-1.299 0.421-1.961c0.072-0.325 0.313-1.227 0.385-1.997s-0.361-1.167-0.95-1.059c-0.589 0.108-1.54 0.505-2.165 0.734l-1.877 0.722-1.865 0.734c-0.614 0.265-1.516 0.65-1.72 0.794s-0.313 0.385-0.253 0.589c0.060 0.205 0.217 0.385 0.577 0.349 0.361-0.048 1.191-0.241 1.829-0.409l1.72-0.469c0.144-0.036 0.253 0.132 0.156 0.241-0.734 0.818-2.406 2.719-3.188 3.633-0.962 1.143-1.925 2.286-2.863 3.441-0.614 0.746-1.215 1.504-1.817 2.262-0.12 0.156-0.349 0.168-0.493 0.036l-4.391-4.211c-0.048-0.036-0.084-0.072-0.144-0.108-0.469-0.313-1.095-0.18-1.432 0.277-0.577 0.77-1.143 1.54-1.708 2.322-0.577 0.782-1.143 1.576-1.708 2.358-0.565 0.794-1.131 1.588-1.684 2.382l-0.83 1.203-0.818 1.203c-0.217 0.313-0.144 0.758 0.205 0.974 0.277 0.18 0.674 0.108 0.902-0.156z"></path>
                        </svg>
                        <p class="text-sm overflow-hidden text-ellipsis whitespace-nowrap">{{item.project.name}}</p>
                    </div>
                    <div class="flex items-center gap-[10px] text-sm overflow-hidden" @click.stop="jumpToTask" v-else-if="item.board">
                        <BoardIcon :item="item.board" size="30"/>
                        <BoardTitle class="whitespace-nowrap" :item="item.board"/>
                    </div>
                    <svg v-if="!isBoard" :fill="taskColor.mycolor === myColor ? '#000' : 'var(--primary-color)'" style="transform: rotate(180deg);" version="1.1" width="11" height="11" class="cursor-pointer" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                    <div @click="viewTaskUsers" class="flex w-fit">
                        <div v-for="user in taskUsers.slice(0, 3)" style="position:relative;">
                            <div v-if="user" :title="user.name" class="column-01">
                                <UserPanel size="15" :disableInstant="true" :user="user" imgClass="u_icon_15"/>   
                                <div title="タスクが完了しました" v-if="user.pivot.progress_flag > 0" class="completed-badge-large completed-badge-medium" :style="{background: taskStatusBackgrounds[user.pivot.progress_flag]}"></div>                         
                            </div>
                        </div>                                                                                       
                        <p style="margin-top:2px;cursor:pointer;font-size: 12px;margin-left: 3px;" v-if="taskUsers && taskUsers.length > 3">({{taskUsers.length}})</p>                                            
                    </div> 
                    <div v-if="isBoard" class="flex gap-[5px] items-center">
                        <div v-if="selfMember?.pivot?.pin_flag == 1">
                            <svg version="1.1" class="dot-menu" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                                <path d="M19.713 28.513c0.045-0.043 0.121-0.125 0.187-0.193 0.067-0.070 0.128-0.148 0.192-0.22 0.122-0.151 0.236-0.306 0.34-0.466 0.414-0.641 0.679-1.346 0.817-2.061 0.137-0.716 0.151-1.449 0.033-2.176-0.062-0.386-0.164-0.773-0.311-1.149-0.037-0.095-0.022-0.198 0.040-0.277l3.236-4.041 3.276-4.116c0.070-0.089 0.184-0.134 0.297-0.121 0.133 0.013 0.267 0.022 0.401 0.022 0.466 0.005 0.925-0.055 1.364-0.169 0.44-0.115 0.861-0.282 1.258-0.502 0.397-0.221 0.773-0.489 1.117-0.834l0.008-0.008 0.005-0.006c0.427-0.434 0.42-1.131-0.013-1.559l-10.277-10.307c-0.44-0.44-1.152-0.441-1.593-0.001l-0.005 0.006c-0.347 0.347-0.618 0.728-0.837 1.129-0.217 0.404-0.38 0.829-0.489 1.269-0.143 0.567-0.191 1.16-0.141 1.75 0.010 0.109-0.034 0.218-0.12 0.286l-4.122 3.291-4.038 3.237c-0.078 0.062-0.184 0.076-0.277 0.040-0.376-0.147-0.762-0.247-1.148-0.31-0.727-0.117-1.46-0.103-2.176 0.033-0.716 0.138-1.42 0.405-2.062 0.818-0.16 0.104-0.316 0.218-0.467 0.339-0.072 0.065-0.149 0.125-0.22 0.193-0.068 0.065-0.15 0.142-0.193 0.187l-0.622 0.621c-0.486 0.485-0.487 1.271-0.001 1.756l0.001 0.002 5.901 5.914c0.058 0.058 0.059 0.15 0.004 0.21-0.199 0.217-0.399 0.433-0.6 0.648-0.394 0.424-0.787 0.852-1.185 1.27-0.796 0.843-1.596 1.679-2.387 2.528l-1.179 1.279-1.167 1.288c-0.775 0.862-1.555 1.722-2.321 2.593-0.333 0.378-0.325 0.964 0.053 1.333 0.365 0.355 0.955 0.347 1.338 0.008 0.863-0.758 1.714-1.529 2.567-2.297l1.288-1.169 1.279-1.179c0.847-0.79 1.685-1.592 2.527-2.386 0.419-0.401 0.846-0.792 1.271-1.186 0.216-0.199 0.431-0.399 0.647-0.6 0.061-0.055 0.153-0.053 0.211 0.005l5.916 5.901c0.484 0.485 1.269 0.484 1.753-0.001l0.625-0.623zM6.029 13.958c0.341-0.224 0.749-0.388 1.182-0.474 0.43-0.088 0.887-0.099 1.316-0.032 0.431 0.065 0.834 0.212 1.162 0.42l0.018 0.011c0.428 0.27 0.907 0.285 1.415-0.086l4.759-3.878 4.764-3.898c0.344-0.281 0.505-0.751 0.375-1.206-0.141-0.493-0.155-1.027-0.032-1.541 0.027-0.117 0.211-0.237 0.335-0.111l1.351 1.329 5.164 5.123 1.339 1.368c0.135 0.134-0.014 0.343-0.149 0.374-0.516 0.127-1.037 0.111-1.501-0.043-0.429-0.14-0.923-0.014-1.226 0.356l-0.013 0.018-3.894 4.744-3.88 4.759c-0.393 0.519-0.37 0.961-0.085 1.411l0.010 0.018c0.209 0.329 0.357 0.732 0.42 1.163 0.066 0.43 0.055 0.885-0.034 1.317-0.086 0.434-0.25 0.839-0.474 1.182 0 0.001-0.001 0.002-0.001 0.003-0.071 0.109-0.228 0.122-0.32 0.029l-6.010-6.024-6.022-6.010c-0.093-0.092-0.081-0.248 0.028-0.32 0.001 0 0.001 0 0.002-0.001z"></path>
                            </svg>
                        </div>
                        <ItemMenu 
                            v-if="completeButtonFilter"
                            :items="itemsCollection"
                        />
                    </div>
                    
                </div>               
                
                <div :style="{height: `${dynamicHeight}`, overflow: 'hidden', transition: 'height 0.1s ease', marginTop: '10px'}">
                    <p ref="taskBody" style="line-height: 1.5;white-space: break-spaces;word-break: break-all;" v-html="truncatedRemarks"></p>
                </div>  
                <div @click="setTruncate" class="jump-link" style="margin-top:10px" v-if="dynamicHeight !== 'auto'">{{ dynamicHeight == '162px' ? '続きを表示する' : '閉じる' }}</div>

                <div v-if="item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{ dateDetail(item.end_at!) }}</div>                         
                </div>
                <div v-if="item.response_time && item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{timeFormat(item.response_time)}}</div>
                </div>
                <div @click="viewSupervisors" v-if="!canModify && supervisors.length" style="display:flex;width: fit-content; margin-top: 15px;align-items: center">
                    <div v-for="user in supervisors.slice(0, 3)" style="position:relative;">
                        <div v-if="user" :title="user.name" class="column-01">
                            <UserPanel size="15" :disableInstant="true" :user="user" imgClass="u_icon_15"/>                            
                        </div>
                        <div class="column-01" v-else>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" class="u_icon_15">
                                <circle cx="15" cy="15" r="15" fill="#ddd"/>
                            </svg>
                        </div>
                    </div>
                    <span style="margin-left: 5px;">(承認者)</span>
                </div>
                <div v-if="canModify" style="display: flex;margin-top: auto;gap: 15px;min-height: 25px;align-items: end;">
                    <div @click.stop="viewApprovalUsers('未承認', waitingApprovalUsers)" style="display: flex;font-size: 12px;cursor: pointer">未承認 ({{ waitingApprovalUsers.length}})</div>
                    <div @click.stop="viewApprovalUsers('未完了', unCompletedUsers)" style="display: flex;font-size: 12px;cursor: pointer">未完了 ({{ unCompletedUsers.length}})</div> 
                    <div @click.stop="viewApprovalUsers('完了', completedUsers)" style="display: flex;font-size: 12px;cursor: pointer">完了 ({{ completedUsers.length }})</div>
                </div>
                <div v-if="statusApprove" style="margin-top: 15px;color:tomato;">
                    未承認
                </div>
                <div v-else-if="isTask && completeButtonFilter" style="display:flex;align-items: center;margin-top: 15px;position:relative;white-space: nowrap;flex-wrap: wrap;gap: 10px;">
    
                    <GanttButton v-if="isExecutor" :status="isExecutor.pivot.progress_flag" :loading="updating.status" viewType="button" @action="(flag) => completeTaskBefore(flag)"/>
                    <!-- <CommandButton
                        v-if="isExpired"
                        :buttons="[
                            {title: '延長', action:() => extendTask()}
                        ]"
                    /> -->
                </div>
            </div>
        </div>
        <Transition name="modalFade">
            <RollDice 
                v-if="playNineWindow"
                :taskId="item.id"
                @close="closeNineWindow"
            />
        </Transition>
    </div> 

</template>

<script setup lang="ts">
import { computed, inject, nextTick, onMounted, reactive, useTemplateRef, ref } from 'vue'
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
import { useMessageUsers } from '@/store/messageUsers'
import { useUrlTask } from '@/store/urlTask';
import { useUrlTaskEdit } from '@/store/urlTaskEdit'
import UserPanel from '@/components/Global/UserPanel.vue';
import axios from 'axios';
import { Task, CommandButtonInterface, Dialog } from '@/interface/globalInterface';
import { timeFormat, urlCheck } from '@/utils/tools';
import { DateTime } from 'luxon';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import GanttButton from '../Gantt/GanttButton.vue';
import RollDice from '@/components/Global/RollDice.vue';
import { taskStatusBackgrounds } from '@/utils/tools';
import { dateDetail } from '@/utils/workApi';
import { useTaskRequest } from '@/store/taskRequest'
import { useTaskUsers } from '@/store/taskUsers';
import { useBadgeStore } from '@/store/badge';
import { useRoute, useRouter } from 'vue-router';
import CommandButton from '@/components/Global/CommandButton.vue';
import BoardIcon from '@/components/Board/Mixed/BoardIcon.vue';
import BoardTitle from '@/components/Board/Mixed/BoardTitle.vue';
    const messageUsers = useMessageUsers()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    const urlTask = useUrlTask()
    const urlTaskEdit = useUrlTaskEdit()
    const taskUsersStore = useTaskUsers()
    const props = defineProps<{
        item: Task
        boxClass: string;
        isBoard: boolean
    }>()

    const updating = reactive({
        status: false
    })
    const emit = defineEmits(['reload', 'editTask', 'setActiveTask', 'getBoardTasks'])
    const { notify, info, confirm } = inject<Dialog>('dialog')!;
    const dynamicHeight = ref('auto')
    const playNineWindow = ref(false)
    const taskBody = useTemplateRef('taskBody')
    const taskRequest = useTaskRequest()
    const badge = useBadgeStore()
    const router = useRouter()
    const route = useRoute()
    onMounted(() => {
        if(urlTask.id == props.item.id){
            nextTick(() => {                  
                var elem = document.getElementById('task_box_' + props.item.id) as HTMLDivElement;          
                if(elem){
                    elem.scrollIntoView({block: 'center' });   
                    setTimeout(() => {
                        elem.classList.add("reached");
                        setTimeout(() => {
                            elem.classList.remove("reached");                    
                        }, 5000);  
                        urlTask.setUrlTaskId(null)   
                    }, 150);  
                }          
            });
            if(urlTaskEdit.active){             
                emit('editTask', props.item)      
            }
        } 
        checkTruncate()
    })

    const myColor = computed(() => {
        if(auth && auth.user && colors){
            const color = colors.filter(ob => ob.id == auth.user?.color)
            if(color.length){
                return color[0].light
            }
            return ''
        }
        return ''
    })
    const isExecutor = computed(() => {
        return props.item.executors.find(ob => ob.id == auth.activeUser.id)
    })
    const isTask = computed(() => {
        const included = taskUsers.value.some(ob => ob.id === auth.activeUser.id) || supervisors.value.some(ob => ob.id == auth.activeUser.id);
        return props.item.end_at && included ? true : false
    })
    const taskColor = computed(() => {
        const userIds = taskUsers.value.map(ob => ob.id)
        const me = userIds.filter(ob => ob == auth.activeUser.id)
        const colors = {
            mycolor: me && me.length ? myColor.value : (route.name === 'remind' ? "var(--message-background)" : responsive.mobile ? "var(--message-background)" : "var(--task-background)"),
            color: me && me.length ? "#000" : "var(--primary-color)"
        }
        return colors
    })
    const completeButtonFilter = computed(() => {            
        const included = taskUsers.value.some(ob => ob.id === auth.activeUser.id);
        return included || canModify.value        
    })
    const completedUsers = computed(() => {
        return props.item.executors.filter(ob => ob.pivot.progress_flag == 2)
    })
    const unCompletedUsers = computed(() => {
        return props.item.executors.filter(ob => (ob.pivot.progress_flag == 0 || ob.pivot.progress_flag == 1) && ob.pivot.status_flag == 0)
    })
    const waitingApprovalUsers = computed(() => {
        return props.item.executors.filter(ob => ob.pivot.status_flag == 1)
    })
    const taskUsers = computed(() => {
        return props.item?.executors ?? []
    })
    const supervisors = computed(() => {
        return props.item?.supervisors ?? []
    })
    const selfMember = computed(() => {
        return taskUsers.value.find(ob => ob.id == auth.activeUser.id) || supervisors.value.find(ob => ob.id == auth.activeUser.id);
    })
    const isOverdue = computed(() => {
        if (!props.item?.end_at) return false;
    
        const endDate = DateTime.fromISO(props.item.end_at).toISODate();
        const now = DateTime.now().toISODate();
        
        return endDate ? now > endDate : false;
    })
    const dateColor = computed(() => {
        return isOverdue.value ? 'tomato' : '#89898F'
    })
    const canModify = computed(() => {
        return supervisors.value.some(ob => ob.id === auth.activeUser.id)
    })
    const statusApprove = computed(() => {
        const me = taskUsers.value.find(ob => ob.id == auth.activeUser.id)
        if(me && me.pivot.status_flag == 1){
            return true
        }else{
            return false
        }
    })
    const itemsCollection = computed(() => {
        const items:CommandButtonInterface[] = []
        if((!supervisors.value.length && props.isBoard) || canModify.value){
            items.push(
                { title: '編集', action:() => emit('editTask', props.item)}, 
                { title: '削除', action:() => deleteTask()}
            )
        }
        items.push(
            { title: selfMember.value?.pivot?.pin_flag == 1 ? 'ピン留めを外す' : 'ピン留め', action:() => pinTask()}
        )
        return items
    })
    const isExpired = computed(() => {
        if (props.item?.end_at) {
            const me = taskUsers.value.filter(ob => ob.id === auth.activeUser.id);
            const taskIncompleteForMe = me.length ? me[0]?.pivot?.comp_flag : false;

            const dateTime = props.item.end_at ? DateTime.fromISO(props.item.end_at) : null;
            const expired = dateTime && dateTime.isValid 
                ? dateTime.toISODate() < DateTime.now().toISODate() 
                : false;

            return !taskIncompleteForMe && expired;
        }
    })
    const deleteTask = async() => {
        const confirmed = await confirm('タスクを削除しますか。')
        if(!confirmed.value) return

        try{
            await axios.delete('/task_item', {data: {task_id: props.item.id}})
            info('削除しました。')
            badge.getTaskBadge()
            emit('getBoardTasks')
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }


    const viewTaskUsers = () => {
        const data = {
            active: true,
            userList: taskUsers.value,
            title: 'タスクメンバー',
            isTask: props.item.end_at !== null
        }
        messageUsers.setMessageUsers(data)
    }
    const viewSupervisors = () => {
        const data = {
            active: true,
            userList: supervisors.value,
            title: 'タスク承認者',
        }
        messageUsers.setMessageUsers(data)
    }
    const viewApprovalUsers = (title, users) => {
        const data = {
            active: true,
            userList: users,
            title: `タスク${title ? title : ''}メンバー`,
            task: props.item,
        }
        taskUsersStore.setTaskUsers(data)
    }
    const closeNineWindow = async (flag) => {
        if (flag) {
            const answer = await confirm('グラウドナインを中止しますか？\n中止した場合再度挑戦することはできません。');
            if (!answer.value) return;
        }
        playNineWindow.value = false;
        updateStatus(2);
    };
    const completeTaskBefore = (flag) => {        
        const userData = taskUsers.value.find(obj => obj.id == auth.activeUser.id);
        const data = {
            active: true,
            data: props.item,
        }
        if(supervisors.value.length && flag == 2) {
            taskRequest.setTaskRequest(data)
            return
        }
        if (flag == 2) {
            const canPlayNine = taskUsers.value.some(
                member => member.id === auth.activeUser.id && member.pivot.glowd_nine === 1 && member.pivot.try_flag === 0
            );
            if (canPlayNine) {
                if (isOverdue.value) {
                    notify('期限内にタスクを完了しなかったため、<br>グラウドナインは適用されませんでした。');
                    updateStatus(flag);
                } else {
                    playNineWindow.value = true;
                }
            } else {
                updateStatus(flag);
            }
        } else {
            updateStatus(flag)
        }
    }
    
    const updateStatus = async(flag: number) => {

        const params = {
            id: props.item.id,
            params: {progress_flag: flag}
        }
        try{
            updating.status = true
            await axios.patch(`/complete_task`, params)
            emit('getBoardTasks')
            badge.getTaskBadge()
            badge.getRemindBadge()
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')        
        }finally {
            updating.status = false
        }
    }
    const pinTask = async() => {
        try {
            await axios.put('/task_update_pin', {id: selfMember.value?.pivot.id})
            emit('getBoardTasks')
            if(taskBody.value && taskBody.value?.clientHeight > 162){
                setTruncate()
            }
        } catch (e) {

        }
    }
    const checkTruncate = () => {
        if(taskBody.value && !selfMember.value?.pivot?.pin_flag){
            if(taskBody.value?.clientHeight > 162){
                setTruncate()
            }
        } 
    }
    const truncatedRemarks = computed(() => {
        const remarks = urlCheck(props.item.remarks)
        return remarks
    })
    const setTruncate = () => {
        dynamicHeight.value = dynamicHeight.value == '162px' ? `${taskBody.value?.clientHeight}px` : '162px'
    }
    const jumpToTask = () => {
        const link = document.createElement('a');
        link.href = `${window.location.origin}/board/${props.item.board_id}?t=${props.item.id}&action=true`;                
        document.body.appendChild(link);            
        link.click();   
        link.remove();
    }
    const extendTask = () => {
        console.log(props.item)
    }
</script>
