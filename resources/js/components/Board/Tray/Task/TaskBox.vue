<template>
    <div :class="boxClass">
        <div style="display:flex; justify-content:space-between;">            
            <div :id="'task_box_' + item?.id" class="task-box-inner" :style="{backgroundColor: taskColor.mycolor, color: taskColor.color, position: 'relative', cursor: 'pointer'}" @dblclick.prevent="emit('editTask', item)">
                <div class="task-box-header" :style="{display: 'flex', width: '100%', position: 'relative', marginTop: responsive.mobile ? '0' : '5px'}">
                    <div @click="viewApprovalUsers('', taskUsers)" style="display:flex;width: fit-content;">
                        <div v-for="user in taskUsers.slice(0, 3)" style="position:relative;">
                            <div v-if="user" :title="user.name" class="column-01">
                                <UserIcon size="30" :disableInstant="true" :user="user" imgClass="u_icon_15"/>                            
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
                            <!-- <div title="タスク承認待ち" v-if="user.pivot.status_flag == 1"  class="completed-badge" style="background-color: tomato;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="5px" viewBox="0 0 38 32" style="fill:#fff;margin:auto;">
                                    <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                </svg>                                           
                            </div>                                    -->
                        </div>                                                                                       
                        <p style="margin-top:2px;cursor:pointer;font-size: 12px;margin-left: 3px;" v-if="taskUsers && taskUsers.length > 3">({{taskUsers.length}})</p>                                            
                    </div> 
                </div>

                
                <div style="margin-top:10px;line-height: 1.5;word-break: break-word;white-space: break-spaces;" v-html="truncatedRemarks"></div>
                <div v-if="truncatedRemarks.length > 50" style="display: flex;justify-content: center;gap: 10px;align-items: center;margin-top:10px;" @click="setTruncate">                                      
                    <div title="すべて表示する" class="selector-accordion-el">
                        <svg class="dot-menu" version="1.1" width="11" height="11" :class="['selector-accordion-inactive' , {'selector-accordion-active' : menu.id === item.id}]" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                        </svg>
                    </div>
                </div>
                <!-- <div v-if="item.end_at && item.repeat" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">現在のタスクは{{dateDetail(item.end_at)}}</div>
                </div> -->
                <div v-if="item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{dueDetail}}</div>                         
                </div>
                <div v-if="item.response_time && item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{timeFormat(item.response_time)}}</div>
                </div>
                <div @click="viewSupervisors" v-if="!canModify && supervisors.length" style="display:flex;width: fit-content; margin-top: 15px;align-items: center">
                    <div v-for="user in supervisors.slice(0, 3)" style="position:relative;">
                        <div v-if="user" :title="user.name" class="column-01">
                            <UserIcon size="30" :disableInstant="true" :user="user" imgClass="u_icon_15"/>                            
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
import { computed, inject, nextTick, onMounted } from 'vue'
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useUrlTask } from '@/store/urlTask';
import { useUrlTaskEdit } from '@/store/urlTaskEdit'
import UserIcon from '../../Mixed/UserIcon.vue';
import { useTaskUsers } from '@/store/taskUsers';
import { useMessageUsers } from '@/store/messageUsers';
import { timeFormat, urlCheck } from '@/utils/tools';
import { dateDetail } from '@/utils/workApi';
    const taskUsersStore = useTaskUsers()
    const responsive = useResponsive()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const urlTask = useUrlTask()
    const urlTaskEdit = useUrlTaskEdit()
    const messageUsers = useMessageUsers()
    const props = defineProps(['item', 'boxClass', 'inTrash'])
    const emit = defineEmits(['taskDeleted', 'editTask', 'completeTaskBefore'])
    const { confirm, info, notify } = inject('dialog')
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
    })
    const truncatedRemarks = computed(() => {
        const remarks = urlCheck(props.item.remarks)
        if (menu.id === props.item.id) {
            return remarks
        } else {
            return remarks.length > 50 
            ? remarks.slice(0, 50) + '...'
            : remarks;
        }
    })
    const setTruncate = () => {
        if (menu.id === props.item.id) {
            menu.setMenu({name: null, id: null})
        } else {
            menu.setMenu({name: 'taskTruncate', id: props.item.id})
        }
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
    const buttonsCollection = computed(() => {
        const buttons = []
        if(isTask.value){
            buttons.push({
                title: isCompleted.value ? '未完了' : '完了',
                action: () => emit('completeTaskBefore', props.item)
            })
        }
        if((props.inTrash == 0 && !supervisors.value.length) || canModify.value){
            buttons.push(
                { title: '編集', action:() => emit('editTask', props.item)}, 
                { title: '削除', action:() => deleteTask()}
            )
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
