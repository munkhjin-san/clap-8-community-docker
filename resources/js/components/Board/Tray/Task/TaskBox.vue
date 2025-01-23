<template>
    <div :class="boxClass">
        <div style="display:flex; justify-content:space-between;">            
            <div :id="'task_box_' + item?.id" class="task-box-inner" :style="{backgroundColor: taskColor.mycolor, color: taskColor.color, position: 'relative', cursor: 'pointer'}" @dblclick.prevent="emit('editTask', item)">
                <div class="task-box-header" :style="{marginTop: responsive.mobile ? '0' : '5px'}">
                    <div @click="viewApprovalUsers('', taskUsers)" style="display:flex;width: fit-content;">
                        <div v-for="user in taskUsers.slice(0, 3)" style="position:relative;">
                            <div v-if="user" :title="user.name" class="column-01">
                                <UserPanel size="30" :disableInstant="true" :user="user" imgClass="u_icon_15"/>                            
                            </div>
                            <div class="column-01" v-else>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" class="u_icon_15">
                                    <circle cx="15" cy="15" r="15" fill="#ddd"/>
                                </svg>
                            </div>
                            <div title="タスクが完了しました" v-if="user.pivot.comp_flag == 1"  class="completed-badge" style="">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="5px" viewBox="0 0 38 32" style="fill:#fff;margin:auto;">
                                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                </svg>                                           
                            </div>
                                        
                        </div>                                                                                       
                        <p style="margin-top:2px;cursor:pointer;font-size: 12px;margin-left: 3px;" v-if="taskUsers && taskUsers.length > 3">({{taskUsers.length}})</p>                                            
                    </div>
                    <div style="display: flex; gap: 5px; align-items: center;">
                        <div v-if="selfMember?.pivot?.pin_flag == 1">
                            <svg version="1.1" class="dot-menu" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                                <path d="M19.713 28.513c0.045-0.043 0.121-0.125 0.187-0.193 0.067-0.070 0.128-0.148 0.192-0.22 0.122-0.151 0.236-0.306 0.34-0.466 0.414-0.641 0.679-1.346 0.817-2.061 0.137-0.716 0.151-1.449 0.033-2.176-0.062-0.386-0.164-0.773-0.311-1.149-0.037-0.095-0.022-0.198 0.040-0.277l3.236-4.041 3.276-4.116c0.070-0.089 0.184-0.134 0.297-0.121 0.133 0.013 0.267 0.022 0.401 0.022 0.466 0.005 0.925-0.055 1.364-0.169 0.44-0.115 0.861-0.282 1.258-0.502 0.397-0.221 0.773-0.489 1.117-0.834l0.008-0.008 0.005-0.006c0.427-0.434 0.42-1.131-0.013-1.559l-10.277-10.307c-0.44-0.44-1.152-0.441-1.593-0.001l-0.005 0.006c-0.347 0.347-0.618 0.728-0.837 1.129-0.217 0.404-0.38 0.829-0.489 1.269-0.143 0.567-0.191 1.16-0.141 1.75 0.010 0.109-0.034 0.218-0.12 0.286l-4.122 3.291-4.038 3.237c-0.078 0.062-0.184 0.076-0.277 0.040-0.376-0.147-0.762-0.247-1.148-0.31-0.727-0.117-1.46-0.103-2.176 0.033-0.716 0.138-1.42 0.405-2.062 0.818-0.16 0.104-0.316 0.218-0.467 0.339-0.072 0.065-0.149 0.125-0.22 0.193-0.068 0.065-0.15 0.142-0.193 0.187l-0.622 0.621c-0.486 0.485-0.487 1.271-0.001 1.756l0.001 0.002 5.901 5.914c0.058 0.058 0.059 0.15 0.004 0.21-0.199 0.217-0.399 0.433-0.6 0.648-0.394 0.424-0.787 0.852-1.185 1.27-0.796 0.843-1.596 1.679-2.387 2.528l-1.179 1.279-1.167 1.288c-0.775 0.862-1.555 1.722-2.321 2.593-0.333 0.378-0.325 0.964 0.053 1.333 0.365 0.355 0.955 0.347 1.338 0.008 0.863-0.758 1.714-1.529 2.567-2.297l1.288-1.169 1.279-1.179c0.847-0.79 1.685-1.592 2.527-2.386 0.419-0.401 0.846-0.792 1.271-1.186 0.216-0.199 0.431-0.399 0.647-0.6 0.061-0.055 0.153-0.053 0.211 0.005l5.916 5.901c0.484 0.485 1.269 0.484 1.753-0.001l0.625-0.623zM6.029 13.958c0.341-0.224 0.749-0.388 1.182-0.474 0.43-0.088 0.887-0.099 1.316-0.032 0.431 0.065 0.834 0.212 1.162 0.42l0.018 0.011c0.428 0.27 0.907 0.285 1.415-0.086l4.759-3.878 4.764-3.898c0.344-0.281 0.505-0.751 0.375-1.206-0.141-0.493-0.155-1.027-0.032-1.541 0.027-0.117 0.211-0.237 0.335-0.111l1.351 1.329 5.164 5.123 1.339 1.368c0.135 0.134-0.014 0.343-0.149 0.374-0.516 0.127-1.037 0.111-1.501-0.043-0.429-0.14-0.923-0.014-1.226 0.356l-0.013 0.018-3.894 4.744-3.88 4.759c-0.393 0.519-0.37 0.961-0.085 1.411l0.010 0.018c0.209 0.329 0.357 0.732 0.42 1.163 0.066 0.43 0.055 0.885-0.034 1.317-0.086 0.434-0.25 0.839-0.474 1.182 0 0.001-0.001 0.002-0.001 0.003-0.071 0.109-0.228 0.122-0.32 0.029l-6.010-6.024-6.022-6.010c-0.093-0.092-0.081-0.248 0.028-0.32 0.001 0 0.001 0 0.002-0.001z"></path>
                            </svg>
                        </div> 
                        <ItemMenu 
                            v-if="completeButtonFilter"
                            :items="itemsCollention"
                        />
                    </div> 
                    
                </div>

                
                <div :style="{height: `${dynamicHeight}`, overflow: 'hidden', transition: 'height 0.1s ease', marginTop: '10px'}">
                    <p ref="taskBody" style="line-height: 1.5;white-space: pre-line;word-break: break-all;" v-html="truncatedRemarks"></p>
                </div>  
                <div @click="setTruncate" class="jump-link" style="margin-top:10px" v-if="dynamicHeight !== 'auto'">{{ dynamicHeight == '162px' ? '続きを表示する' : '閉じる' }}</div>

              
                <div v-if="item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{dueDetail}}</div>                         
                </div>
                <div v-if="item.response_time && item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{timeFormat(item.response_time)}}</div>
                </div>
                <div @click="viewSupervisors" v-if="!canModify && supervisors.length" style="display:flex;width: fit-content; margin-top: 15px;align-items: center">
                    <div v-for="user in supervisors.slice(0, 3)" style="position:relative;">
                        <div v-if="user" :title="user.name" class="column-01">
                            <UserPanel size="30" :disableInstant="true" :user="user" imgClass="u_icon_15"/>                            
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
                <div v-else-if="completeButtonFilter" style="display:flex;align-items: center;margin-top: 15px;position:relative;white-space: nowrap;flex-wrap: wrap;gap: 10px;">
                    <CommandButton 
                        v-if="buttonsCollection.length" 
                        :buttons="buttonsCollection" 
                        @dblclick.stop 
                    />
                </div>
            </div>
        </div>
    </div> 

</template>

<script setup>
import moment from 'moment';
import CommandButton from '../../../Global/CommandButton.vue';
import { computed, inject, nextTick, onMounted, ref } from 'vue'
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useUrlTask } from '@/store/urlTask';
import { useUrlTaskEdit } from '@/store/urlTaskEdit'
import UserPanel from '@/components/Global/UserPanel.vue'
import { useTaskUsers } from '@/store/taskUsers';
import { useMessageUsers } from '@/store/messageUsers';
import { timeFormat, urlCheck } from '@/utils/tools';
import { dateDetail } from '@/utils/workApi';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import axios from 'axios';
    const taskUsersStore = useTaskUsers()
    const responsive = useResponsive()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const urlTask = useUrlTask()
    const urlTaskEdit = useUrlTaskEdit()
    const messageUsers = useMessageUsers()
    const props = defineProps(['item', 'boxClass', 'inTrash'])
    const emit = defineEmits(['taskDeleted', 'editTask', 'completeTaskBefore', 'getTask'])
    const { confirm, info, notify } = inject('dialog')
    const dynamicHeight = ref('auto')
    const taskBody = ref(null)
    onMounted(() => {
        if(urlTask.id == props.item.id){
            nextTick(() => {                  
                var elem = document.getElementById('task_box_' + props.item.id);                    
                elem.scrollIntoView({block: 'center' });   
                setTimeout(() => {
                    elem.classList.add("reached");
                    setTimeout(() => {
                        elem.classList.remove("reached");                    
                    }, 5000);  
                    urlTask.setUrlTaskId(null)   
                }, 150);                  
            });
            if(urlTaskEdit.active){             
                emit('editTask', props.item)      
            }
            if(props.item.user_id == auth.activeUser.id){
                props.item.color = "#F7D5D5"
            }
        }    
        checkTruncate()
    })
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
    const dueDetail = computed(() => {
        if(props.item?.repeat){
            const formatters = [
                () => {},
                onceFormatter,
                weeklyFormatter,
                monthlyFormatter,
                yearlyFormatter
            ]
            const repeat = props.item.repeat
            const result = formatters[repeat.repeat_type](repeat)
            return result
        }else{
            return dateDetail(props.item?.end_at)
        }
    })
    const weeklyFormatter = (data) => {
        const dayOfWeeks = ['月','火','水','木','金','土','日']
        let title = `${moment(data.repeat_until).format('YYYY/M/D')}まで毎週`
        data?.day_of_week.forEach(day => {
            title = title + dayOfWeeks[day - 1]
        });
        return title
    }
    const monthlyFormatter = (data) => {    
        return `${moment(data.repeat_until).format('YYYY/M/D')}まで毎月${data.day_of_month}日`
    }
    const yearlyFormatter = (data) => {
        return `${moment(data.repeat_until).format('YYYY/M/D')}まで毎年${data.month}月${data.day_of_month}日`
    }
    const onceFormatter = (data) => {
        return dateDetail(data.end_at)
    }
    const supervisors = computed(() => {
        return props.item.supervisors ? props.item.supervisors : []
    })
    const myColor = computed(() => {
        if(auth.activeUser && colors){
            const color = colors.filter(ob => ob.id == auth.activeUser.color)
            if(color.length){
                return color[0].light
            }
            return ''
        }
        return ''
    })
    const completedUsers = computed(() => {
        return props.item.executors.filter(ob => ob.pivot.comp_flag == 1)
    })
    const unCompletedUsers = computed(() => {
        return props.item.executors.filter(ob => ob.pivot.comp_flag == 0 && ob.pivot.status_flag == 0)
    })
    const waitingApprovalUsers = computed(() => {
        return props.item.executors.filter(ob => ob.pivot.status_flag == 1)
    })
    const isCompleted = computed(() => {
        if(props.item.comp_flag == 1){
            return true
        }else{
            const me = props.item.executors.find(ob => ob.id == auth.activeUser.id)
            if(me && me.pivot.comp_flag == 1){
                return true
            }else{
                return false
            }
        }
    })
    const statusApprove = computed(() => {
        const me = props.item.executors.find(ob => ob.id == auth.activeUser.id)
        if(me && me.pivot.status_flag == 1){
            return true
        }else{
            return false
        }
    })
    
    const isTask = computed(() => {
        const included = taskUsers.value.find(ob => ob.id === auth.activeUser.id)
        return props.item.end_at && included ? true : false
    })
    const taskColor = computed(() => {
        const userIds = taskUsers.value.map(ob => ob.id)
        const me = userIds.filter(ob => ob == auth.activeUser.id)
        const colors = {
            mycolor: me && me.length ? myColor.value : (responsive.mobile ? "var(--message-background)" : "var(--task-background)"),
            color: me && me.length ? "#000" : "var(--primary-color)"
        }
        return colors
    })
    const completeButtonFilter = computed(() => {            
        var userData = taskUsers.value.filter(obj => obj.id == auth.activeUser.id);
        return userData.length || canModify.value
        if(userData.length) return true;
        else return false;             
    })
    const taskUsers = computed(() => {
        return props.item.executors ? props.item.executors : []
    })
    const canModify = computed(() => {
        return props.item.supervisors.some(ob => ob.id === auth.activeUser.id)
    })
    const dateColor = computed(() => {
        
        const now = moment().format('YYYY-MM-DD')
        const date_end = moment(props.item.end_at).format('YYYY-MM-DD')

        return now > date_end ? 'tomato' : '#89898F'
        

    })

    const isExpired = computed(() => {
        const taskIncomplete = props.item.comp_flag
        const me = taskUsers.value.filter( ob => ob.id == auth.activeUser.id)
        const taskIncompleteforMe = me.length ? me[0].pivot.comp_flag : false
        const expired = moment(props.item.end_at).format('YYYY-MM-DD') <= moment().format('YYYY-MM-DD')
        const notRepeat = !props.item.repeat_id 
        return !taskIncomplete && !taskIncompleteforMe && expired && notRepeat
    })
    const isToday = computed(() => {
        const taskIncomplete = props.item.comp_flag
        const me = taskUsers.value.filter( ob => ob.id == auth.activeUser.id)
        const taskIncompleteforMe = me.length ? me[0].pivot.comp_flag : false
        const expired = props.item.end_at <= moment().format('YYYY-MM-DD HH:mm:ss')

        return !taskIncomplete && !taskIncompleteforMe && expired
    })
    const selfMember = computed(() => {
        return taskUsers.value.find(ob => ob.id == auth.activeUser.id) || supervisors.value.find(ob => ob.id == auth.activeUser.id);
    })
    const itemsCollention = computed(() => {
        const items = []
        if((props.inTrash == 0 && !supervisors.value.length) || canModify.value){
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
    const buttonsCollection = computed(() => {
        const buttons = []
        if(isTask.value){
            buttons.push({
                title: isCompleted.value ? '未完了' : '完了',
                action: () => emit('completeTaskBefore', props.item)
            })
        }
        
        if(isExpired.value && isTask.value && !supervisors.value.length){
            buttons.push({ title: '本日対応',action: () => untilTomorrow()})
        }
        return buttons
    })
    const untilTomorrow = () => {
        const today = moment().startOf('day').add(1, 'days').format('YYYY-MM-DD');
        updateDate(props.item.id, today)
    }
    const updateDate = (id, date) => {
        axios.post('/task_update_api', {task_id: id, date: date}).then(response => {
            emit('taskDeleted')
        });
    }
    const pinTask = async() => {
        try {
            await axios.put('/task_update_pin', {id: selfMember.value?.pivot.id})
            emit('getTask')
            if(taskBody.value?.clientHeight > 162){
                setTruncate()
            }
        } catch (e) {

        }
    }
    const deleteTask = async() => {
        let question = props.item.repeat_id ? '繰り返しタスクすべて削除しますか。' : 'タスクを削除しますか。'
        let answers = [{label:'すべて', value:'all'}, {label:'このタスクのみ', value:'single'}, {label:'キャンセル', value:false}]
        const options = {
            answers: props.item.repeat_id ? answers : null
        }
        const answer = await confirm(question, options)
        if(answer == false) return
        const all_delete = answer == 'all'
        try{
            await axios.post('/task_delete_api', {task_id: props.item.id, all_delete: all_delete})
            info('削除しました。')
            emit('taskDeleted')
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    
    const viewTaskUsers = () => {
        const data = {
            active: true,
            userList: taskUsers.value,
            title: 'タスクメンバー',
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
</script>
