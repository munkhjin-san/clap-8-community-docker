<template>
<div class="taskOuterContainerBg" style="position: relative;" >
    <div
        id="recycleTaskButton" 
        title="完了した・削除したタスク" 
        :style="{right:'15px', background: which == 0 ? '#efefef' : '#000'}" 
        :class="{hiddenButton : createHidden}" 
        class="createBoardButton fileNewButton taskRecycleButton" 
        @click="switchView">
        <svg :style="{margin: 'auto', fill: which == 0 ? '#000' : '#fff'}" version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 27 32">
            <path d="M18.68 10.952c-0.427-0.035-0.797 0.289-0.832 0.716-0.104 1.271-0.173 2.542-0.243 3.812l-0.104 1.906-0.081 1.906-0.069 1.906c-0.023 0.635-0.035 1.271-0.046 1.906-0.023 1.271-0.046 2.553-0.023 3.824 0.012 0.37 0.289 0.693 0.682 0.728 0.416 0.035 0.774-0.266 0.809-0.67 0.116-1.271 0.196-2.542 0.266-3.812 0.035-0.635 0.081-1.271 0.104-1.906l0.081-1.906 0.069-1.906 0.046-1.906c0.023-1.271 0.058-2.553 0.058-3.824-0.012-0.393-0.312-0.739-0.716-0.774zM9.473 21.21l-0.069-1.918-0.081-1.906c-0.023-0.635-0.069-1.271-0.104-1.906-0.069-1.271-0.15-2.542-0.266-3.812-0.035-0.37-0.347-0.67-0.728-0.67-0.416-0.012-0.751 0.323-0.751 0.739-0.023 1.271 0 2.553 0.023 3.824 0.012 0.635 0.023 1.271 0.046 1.906l0.069 1.906 0.081 1.906 0.104 1.906c0.069 1.271 0.15 2.542 0.243 3.812 0.035 0.393 0.37 0.716 0.774 0.716 0.427 0 0.774-0.347 0.774-0.774 0-1.271-0.023-2.553-0.058-3.824l-0.058-1.906zM14.279 15.515c-0.023-1.271-0.046-2.542-0.092-3.824-0.023-0.404-0.335-0.728-0.739-0.739-0.427-0.012-0.786 0.312-0.809 0.739-0.046 1.271-0.069 2.542-0.092 3.824l-0.023 1.906-0.012 1.906 0.012 1.906c0 0.635 0.023 1.271 0.023 1.906 0.023 1.271 0.058 2.542 0.116 3.824 0.023 0.37 0.323 0.682 0.705 0.705 0.416 0.023 0.762-0.289 0.786-0.705 0.069-1.271 0.104-2.542 0.116-3.824 0.012-0.635 0.023-1.271 0.023-1.906l0.012-1.906-0.023-3.812z"></path> <path data-v-96232590="" d="M26.64 7.601v-0.012c0-0.531-0.439-0.97-0.982-0.959-0.127 0-0.3 0.012-0.451 0.023-0.231 0.012-0.451-0.046-0.647-0.162-0.312-0.196-0.682-0.404-0.855-0.485l-0.693-0.323c-0.231-0.104-0.474-0.196-0.705-0.289-0.947-0.37-1.918-0.682-2.9-0.924-0.416-0.104-0.947-0.208-1.282-0.277-0.116-0.023-0.196-0.139-0.196-0.254 0.035-0.451 0.081-1.178 0.092-1.536 0.023-0.439 0.023-0.866 0.046-1.305 0.012-0.554-0.416-1.017-0.97-1.028h-0.058l-1.814-0.046c-0.601-0.023-1.213-0.023-1.814-0.023l-1.814 0.012c-0.601 0.012-1.213 0.023-1.814 0.046h-0.081c-0.543 0.023-0.97 0.485-0.947 1.028l0.023 0.647c0.012 0.22 0.012 0.439 0.023 0.647 0.023 0.358 0.058 1.028 0.081 1.479 0 0.139-0.092 0.254-0.231 0.277-0.335 0.058-0.832 0.162-1.259 0.266-0.994 0.231-1.964 0.531-2.911 0.901-0.751 0.289-1.49 0.612-2.207 1.005-0.196 0.116-0.416 0.162-0.635 0.162h-0.485c-0.624 0-1.132 0.497-1.132 1.121v0.012l-0.023 3.5c0 0.635 0.508 1.155 1.144 1.155h0.751l1.074 18.622v0.023c0.046 0.635 0.578 1.144 1.225 1.132l18.449-0.116c0.578 0 1.063-0.462 1.097-1.051l1.040-18.784h0.901c0.543 0 0.994-0.439 0.982-0.994l-0.023-3.489zM10.755 2.38c0-0.081 0.012-0.162 0.012-0.254 0.277 0.012 0.555 0.012 0.832 0.023l1.814 0.012c0.601 0 1.213 0 1.814-0.012l0.82-0.023c0 0.081 0 0.162 0.012 0.254 0.012 0.393 0.035 0.994 0.035 1.352 0 0.058-0.046 0.104-0.104 0.104-0.543-0.046-1.721-0.116-2.576-0.116-0.832 0-2.091 0.081-2.53 0.116-0.069 0.012-0.116-0.046-0.127-0.104-0.012-0.335-0.023-0.97 0-1.352zM22.816 11.033v0.012l-1.201 18.126c-0.023 0.3-0.266 0.52-0.555 0.52l-15.203 0.023c-0.266 0-0.474-0.208-0.497-0.462l-1.19-18.218v-0.012c-0.035-0.612-0.543-1.086-1.167-1.086h-0.866c-0.116 0-0.208-0.092-0.208-0.208v-0.797c0-0.116 0.081-0.208 0.196-0.208 0.254-0.023 0.612-0.069 0.751-0.15h0.012c0.751-0.474 1.571-0.89 2.414-1.248s1.721-0.647 2.622-0.878c1.791-0.474 3.651-0.705 5.51-0.716 1.86 0 3.72 0.22 5.522 0.67 0.901 0.231 1.791 0.508 2.634 0.855 0.22 0.081 0.427 0.173 0.635 0.266l0.312 0.139 0.312 0.15c0.208 0.092 0.404 0.208 0.601 0.312 0 0 0.254 0.15 0.393 0.22 0.104 0.046 0.22 0.116 0.312 0.162 0.058 0.035 0.127 0.046 0.196 0.046h0.312c0.104 0 0.173 0.081 0.173 0.185l-0.012 1.19c0 0.104-0.081 0.185-0.185 0.185h-0.797c-0.543-0.012-0.982 0.393-1.028 0.924z"></path>
        </svg>   
    </div>
    <div
        id="taskAddButton" 
        title="新規作成" 
        :style="{bottom: '15px' ,right:'15px'}" 
        :class="{hiddenButton : createHidden}" 
        class="createBoardButton fileNewButton" 
        @click="newTask">
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
            <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
        </svg>        
    </div>
    
    <div class="taskSortWrap" v-if="calendarHide">
        <div @click.stop="menu.setMenu( {name : 'taskSortMenu', id: 789})" class="messageMenuContainer" style="margin-left: -5px;fill:var(--primary-color)">
            <svg style="transform: rotate(90deg);margin-right: -5px;" version="1.1" xmlns="http://www.w3.org/2000/svg" :width="responsive.mobile ? '13' : '12'" viewBox="0 0 47 32">
                <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
            </svg> 
            <svg style="transform: rotate(270deg);" version="1.1" xmlns="http://www.w3.org/2000/svg" :width="responsive.mobile ? '13' : '12'" viewBox="0 0 47 32">
                <path d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z"></path>
            </svg>
        </div>
        <div style="width:0px;height:0px">
            <TaskSortMenu 
                v-if="(menu.name == 'taskSortMenu' && menu.id == 789)"
                :sortIs="sortIs"
                @sortBy="sortBy"
            />                
        </div>
    </div>
    <Transition name="modalFade">
    <BoardTaskCreate 
        v-if="taskModalView" 
        @close="closeTaskModal" 
        @taskDeleted="taskDeleted"
        :editTaskData="editTaskData"/>
    </Transition>
    <div style="height:100%;overflow: hidden scroll;position: relative;background:inherit">
        <div class="no-comment-text" v-if="!sortedTasks?.length" style="font-size:14px;">
            <p>現在アイテムはありません</p>
        </div>
        <div :class="{collapseTasks : !viewActiveTask}" class="task-list-wrap" style="margin-top: 40px;">                 
            <div>
                <div class="task-list-wrap" v-if="sortedTasks.length">     
                    <TaskBox 
                        v-for="item in sortedTasks"
                        :key="item.id" 
                        boxClass="task-box-container"
                        :item="item"
                        :inTrash="which"
                        @editTask="editTask" 
                        @completeTaskBefore="completeTaskBefore"
                        @taskDeleted="taskDeleted"
                        @approveTask="approveTask"
                        @getTask="getTask"
                    />
                </div>
                
                
            </div>
           
        </div>    
    </div>
    <Transition name="modalFade">
        <RollDice 
            v-if="playNineWindow"
            :taskId="taskId"
            @close="closeNineWindow"
        />
    </Transition>
</div>
</template>
<script setup>
import TaskSortMenu from './TaskSortMenu.vue'
import BoardTaskCreate from './BoardTaskCreate.vue'
import TaskBox from './TaskBox.vue'
import TaskRequest from './TaskRequest.vue'
import moment from 'moment'
import { computed, inject, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive'
import { useUrlTask } from '@/store/urlTask'
import { useSharingDataStore } from '@/store/sharingData'
import { useTaskFeedback } from '@/store/taskFeedback'
import { useBadgeStore } from '@/store/badge'
import { useTaskRequest } from '@/store/taskRequest'
import { instance } from '@/utils/broadcaster'
import { onUnmounted } from 'vue'
import RollDice from '@/components/Global/RollDice.vue'
import axios from 'axios'
    const badge = useBadgeStore()
    const sharingData = useSharingDataStore()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const taskFeedBack = useTaskFeedback()
    const urlTask = useUrlTask()
    const taskList = ref([])
    const taskPathSelector = ref(0)
    const taskModalView = ref(false)
    const editTaskData = ref(null)
    const approveTaskData = ref(null)
    const viewActiveTask = ref(true)
    const viewCompletedTasks = ref(false)
    const createHidden = ref(false)
    const taskRequest = useTaskRequest()
    const playNineWindow = ref(false)
    const taskId = ref(null)
    const toCompleteList = ref([])
    const sortIs = ref({
        by: 'deadline',
        order: 'desc',
        myRecord: null
    })
    const calendarHide = ref(true)
    const which = ref(0)
    const board = inject('openedBoard')
    const activeListeners = new Set();
    const unorganizedTasks = ref([])
    const { notify, confirm } = inject('dialog')
    onMounted(() => {
        if (urlTask.id) {
            viewCompletedTasks.value = true;
        }
        if (sharingData.active && sharingData.to == 'task') {
            newTask();
        }

        
        getTask();
        const myTaskPriority = localStorage.getItem('my_task_priority')
        if(myTaskPriority){
            sortIs.value.myRecord = JSON.parse(myTaskPriority)
        }
        const taskSortDesc = localStorage.getItem('task_sort_desc')
        if(taskSortDesc){
            sortIs.value.order = JSON.parse(taskSortDesc)
        }
        instance.on(`task:${board.value.id}`, socketTaskHandler)
        activeListeners.add(`task:${board.value.id}`);
        


    })
    onUnmounted(() => {
        clearListeners()
    })

    watch(() => taskFeedBack.active, () => {            
        getTask();        
    })
    const clearListeners = () => {
        activeListeners.forEach(listener => {
            if (listener.startsWith('board:') || listener.startsWith('task:')) {
                instance.off(listener, socketTaskHandler);
                activeListeners.delete(listener);
            }
        });
    }
    const socketTaskHandler = () => {
        getTask()
    }
   
    const allTasks = computed(() => {
        return taskList.value;
    })
    const sortedTasks = computed(() => {
        const tasks = unorganizedTasks.value;

        if (!Array.isArray(tasks)) return [];
        
        const filteredTasks = tasks.filter(task =>
            task.executors.some(executor => executor.id === auth.activeUser.id)
        );

        if (!sortIs.value.myRecord) {
            const pinnedTasks = tasks.filter(task =>
                task.executors.some(executor => executor.id === auth.activeUser.id && executor.pivot.pin_flag === 1)
                || task.supervisors.some(supervisor => supervisor.id === auth.activeUser.id && supervisor.pivot.pin_flag === 1)
            );

            const unpinnedTasks = tasks.filter(task =>
                task.executors.some(executor => executor.id === auth.activeUser.id && executor.pivot.pin_flag !== 1)
                || task.supervisors.some(supervisor => supervisor.id === auth.activeUser.id && supervisor.pivot.pin_flag !== 1)
            );
            const notMyTasks = tasks.filter(task =>
                !task.executors.some(executor => executor.id === auth.activeUser.id)
                && !task.supervisors.some(supervisor => supervisor.id === auth.activeUser.id)
            );
            return [...pinnedTasks, ...unpinnedTasks, ...notMyTasks];
        }

        return filteredTasks;
    })
    const myTasks = computed(() => {
        let list = [];
        taskList.value.map(ob => {
            const me = ob.executors.filter(user => user.id == auth.activeUser.id);
            if (me.length) {
                list.push(ob);
            }
        });
        return list;
    })
    const completedTasks = computed(() => {
        let sortAbleTasks = sortIs.value.myRecord ? myTasks.value : allTasks.value
        let list = [];   
        sortAbleTasks.map(ob => {
            if(ob.end_at == null){
                list.push(ob);
            }else{
                const me = ob.executors.find(user => user.id == auth.activeUser.id);
                if (me?.pivot?.comp_flag === 1) {
                    list.push(ob);
                }
                else if (ob.comp_flag === 1) {
                    
                    list.push(ob);
                }else {
                    const all_members = ob.executors
                    const has_all_completed = all_members.filter(ob => ob.pivot.comp_flag == 1)
                    if(all_members.length == has_all_completed.length){
                        list.push(ob);
                    }
                }
            }                
        });
        return list;   
    })
    const computedTasks = computed(() => {
        return which.value == 0 ? incompletedTasks.value : completedTasks.value
    })
    const incompletedTasks = computed(() => {
        let list = [];
        let sortAbleTasks = sortIs.value.myRecord ? myTasks.value : allTasks.value    
        const completed = completedTasks.value.map( ob => ob.id)
        sortAbleTasks.map(ob => {
            if(ob.end_at == null){
                list.push(ob);
            }else{
                const has_completed = completed.indexOf(ob.id)
                if(has_completed == -1){
                    list.push(ob)
                }
            }           
        });
        return list 
    })
        
    const closeNineWindow = async (flag) => {
        if (flag) {
            const answer = await confirm('グラウドナインを中止しますか？\n中止した場合再度挑戦することはできません。');
            if (!answer) return;
        }
        playNineWindow.value = false;
        completeTask(taskId.value, 1, 0);
    };
    
    const switchView = () => {
        which.value = which.value == 1 ? 0 : 1
        getTask()
    }
    const newTask = (day) => {
        editTaskData.value = null;
        taskModalView.value = true;
    }
    const sortBy = (by) => {
        if(by == 'mytask'){
            sortIs.value.myRecord = !sortIs.value.myRecord
            localStorage.setItem('my_task_priority', JSON.stringify(sortIs.value.myRecord));
        }else if(by == 'deadline'){
            const order = sortIs.value.order == 'asc' ? 'desc' : 'asc'
            sortIs.value.order = order
            const order_send = sortIs.value.order === 'asc' ? 0 : 1
            localStorage.setItem('task_sort_desc', JSON.stringify(order_send))
        }

        
    }
    const taskDeleted = () => {
        getTask();
        taskModalView.value = false;
        badge.getTaskBadge(); 
    }
    const editTask = (task) => {
        const usersId = task.executors.map(ob => ob.id);
        
        if (usersId.indexOf(auth.activeUser.id) > -1 || task.supervisors.some(ob => ob.id == auth.activeUser.id)) {
            editTaskData.value = task;
            taskModalView.value = true;
        }
    }
    const completeTaskBefore = (task) => {        
        const userData = task.executors.find(obj => obj.id == auth.activeUser.id);
        const today = moment().format('YYYY-MM-DD')
        const end = moment(task.end_at).format('YYYY-MM-DD')
        const overdue = today > end
        const data = {
            active: true,
            data: task,
        }
        
        if(task.supervisors.length && userData.pivot.comp_flag == 0) {
            
            taskRequest.setTaskRequest(data)
            return
        }
        
        
        if (userData?.pivot.comp_flag === 1) {
            completeTask(task.id, 0, 0);
        } else {
            const canPlayNine = task.executors.some(
                member => member.id === auth.activeUser.id && member.pivot.glowd_nine === 1 && member.pivot.try_flag === 0
            );

            if (canPlayNine) {
                if (overdue) {
                    notify('期限内にタスクを完了しなかったため、<br>グラウドナインは適用されませんでした。');
                    completeTask(task.id, 1, 0);
                } else {
                    playNineWindow.value = true;
                    taskId.value = task.id;
                }
            } else {
                completeTask(task.id, 1, 0);
            }
        }
    }
    const completeTask = (task_id, compFlag, statusFlag) => {
        
        axios.post("/complete_task_api", { task_id: task_id, comp_flag: compFlag, status_flag: statusFlag }).then(response => {
            getTask();
            badge.getTaskBadge(); 
        })
    }
    const taskSelector = (flag) => {
        taskPathSelector.value = flag;
        getTask();
    }
    const getTask = () => {
        axios.post("/get_task_api", { record_id: board.value.id, which: which.value }).then(response => {
            unorganizedTasks.value = response.data
        });
    }
    const closeTaskModal = (update) => {
        taskModalView.value = false
        if (update) {
            getTask();
            badge.getTaskBadge(); 
        }
        
    }       
    defineExpose({newTask})

</script>
