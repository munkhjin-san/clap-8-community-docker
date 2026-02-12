<template>


    <div :class="boxClass">
        <div class="month-card-inner">            
            <div :id="'task_box_' + item.id" :style="{backgroundColor: taskColor.mycolor, color: taskColor.color, position: 'relative', cursor: 'pointer', width: '-webkit-fill-available', padding: '5px'}">
                <div :class="[{'justify-between' : isBoard, 'gap-[10px]' : !isBoard},'flex', 'items-center', 'w-full', 'relative']">
                    
                    <div @click="viewTaskUsers" class="flex w-fit">
                        <div v-for="user in taskUsers.slice(0, 3)" style="position:relative;">
                            <div v-if="user" :title="user.name?.toString()" class="column-01">
                                <UserPanel size="15" :disableInstant="true" :user="user" imgClass="u_icon_15"/>   
                                <div title="タスクが完了しました" v-if="user.pivot.progress_flag > 0" class="completed-badge-large completed-badge-medium" :style="{background: taskStatusBackgrounds[user.pivot.progress_flag]}"></div>                         
                            </div>
                        </div>                                                                                       
                        <p style="margin-top:2px;cursor:pointer;font-size: 12px;margin-left: 3px;" v-if="taskUsers && taskUsers.length > 3">({{taskUsers.length}})</p>                                            
                    </div> 
                    
                </div>               
                
                <div :style="{overflow: 'hidden', transition: 'height 0.1s ease', marginTop: '5px'}">
                    <p ref="taskBody" class="truncate" v-html="truncatedRemarks"></p>
                </div>  

                <div v-if="item.end_at" style="margin-top: 5px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{ dateDetail(item.end_at!) }}</div>                         
                </div>
                <div v-if="item.response_time && item.end_at" style="margin-top: 5px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{timeFormat(item.response_time)}}</div>
                </div>
            </div>
        </div>
    </div> 

</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, useTemplateRef, ref } from 'vue'
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
import { useMessageUsers } from '@/store/messageUsers'
import { useUrlTask } from '@/store/urlTask';
import { useUrlTaskEdit } from '@/store/urlTaskEdit'
import UserPanel from '@/components/Global/UserPanel.vue';
import { Task, CommandButtonInterface } from '@/interface/globalInterface';
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
import BoardIcon from '@/components/Board/Mixed/BoardIcon.vue';
import BoardTitle from '@/components/Board/Mixed/BoardTitle.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
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
    const dynamicHeight = ref('auto')
    const playNineWindow = ref(false)
    const taskBody = useTemplateRef('taskBody')
    const taskRequest = useTaskRequest()
    const badge = useBadgeStore()
    const router = useRouter()
    const route = useRoute()
    const api = useApi()
    const { ask, ping, toast } = useDialog()
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
       
        await api.del('/task_item', {task_id: props.item.id},{
            ask: 'タスクを削除しますか？',
            toast: '削除しました。',
        })
        emit('getBoardTasks')
        badge.getTaskBadge()
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
            const answer = await ask('グラウドナインを中止しますか？\n中止した場合再度挑戦することはできません。');
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
                    ping('期限内にタスクを完了しなかったため、<br>グラウドナインは適用されませんでした。');
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
        updating.status = true
        await api.patch(`/complete_task`, params)
        emit('getBoardTasks')
        badge.getTaskBadge()
        badge.getRemindBadge()
        updating.status = false

    }
    const pinTask = async() => {
        await api.put('/task_update_pin', {id: selfMember.value?.pivot.id})
        emit('getBoardTasks')
        if(taskBody.value && taskBody.value?.clientHeight > 162){
            setTruncate()
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
