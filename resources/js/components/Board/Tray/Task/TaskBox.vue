<template>
    <div :class="boxClass">
        <div style="display:flex; justify-content:space-between;">            
            <div :id="'task_box_' + item.id" class="task-box-inner" :style="{backgroundColor: taskColor.mycolor, color: taskColor.color, position: 'relative', cursor: 'pointer'}" @dblclick.prevent="emit('editTask', item)">
                <div class="task-box-header" :style="{display: 'flex', width: '100%', position: 'relative', marginTop: responsive.mobile ? '0' : '5px'}">
                    <div @click="viewTaskUsers" style="display:flex;width: fit-content;">
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
                        </div>                                                                                       
                        <p style="margin-top:2px;cursor:pointer;font-size: 12px;margin-left: 3px;" v-if="taskUsers && taskUsers.length > 3">({{taskUsers.length}})</p>                                            
                    </div> 
                    <div v-if="canModify && inTrash == 0" @click.stop="menu.setMenu( {name: 'taskBMenu', id: item.id})" class="taskMenuWrap cursor-pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 7 32" class="dot-menu" style="width: -webkit-fill-available;">
                            <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                            <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                            <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                        </svg>
                    </div>
                    <Transition name="modalFade"> 
                        <div id="taskBMenu" class="boxMenuComment cursor-pointer" v-if="menu.name == 'taskBMenu' && menu.id == item.id" style="z-index:2;top: 20px;right: 15px;box-shadow:none;background-color: unset;">                  
                            <ul class="messageMenuList">
                                <li @click="emit('editTask', item); menu.setMenu( {name: '', id: null})" class="boxMenuItems cursor-pointer">編集</li>
                                <li @click="deleteTask(); menu.setMenu( {name: '', id: null})" class="boxMenuItems cursor-pointer">削除</li>                          
                            </ul>                                                 
                        </div>
                    </Transition>

                </div>

                
                
                <div v-if="item.title" style="margin-top: 10px;width: 100%;">
                    <p style="line-height: 1.5;word-break: break-word;white-space: break-spaces;">{{ item.title }}</p>
                </div>
                <div style="margin-top:10px;line-height: 1.5;word-break: break-word;white-space: break-spaces;" v-html="urlCheck(item.remarks)"></div>

                <div v-if="item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{detailsDateText(item.end_at)}}</div>                         
                </div>
                <div v-if="completeButtonFilter" style="display:flex;align-items: center;margin-top: 15px;position:relative;white-space: nowrap;flex-wrap: wrap;gap: 10px 0;">
                    <button v-if="isTask" class="shift-button" style="margin-right: 7px;" @dblclick.stop @click="emit('completeTaskBefore', item)" >{{ completeFlag ? '未完了' : '完了' }}</button>
                    <button v-if="inTrash == 0" @dblclick.stop @click="emit('editTask', item)" class="shift-button" style="margin-right: 7px;">編集</button>
                    <button v-if="isExpired && isTask" @dblclick.stop class="shift-button" @click="untilTomorrow">本日対応</button>
                </div>
            </div>
        </div>
    </div> 

</template>

<script setup>
import moment from 'moment';
import Autolinker from 'autolinker';
import { computed, inject, nextTick, onMounted } from 'vue'
import colors from '../../../../../assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useMessageUsers } from '@/store/messageUsers'
import { useUrlTask } from '@/store/urlTask';
import { useUrlTaskEdit } from '@/store/urlTaskEdit'
import UserIcon from '../../Mixed/UserIcon.vue';
    const messageUsers = useMessageUsers()
    const responsive = useResponsive()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const urlTask = useUrlTask()
    const urlTaskEdit = useUrlTaskEdit()
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

    const pushInstantUser = inject('pushInstantUser')
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
    const completeFlag = computed(() => {
        if(props.item.comp_flag == 1){
            return true
        }else{
            const me = props.item.to_users.find(ob => ob.id == auth.activeUser.id)
            if(me && me.comp_flag == 1){
                return true
            }else{
                return false
            }
        }
    })
    const isTask = computed(() => {
        return props.item.end_at ? true : false
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
        if(userData.length) return true;
        else return false;             
    })
    const taskUsers = computed(() => {
        return props.item.to_users ? props.item.to_users : []
    })
    const canModify = computed(() => {
        const users = taskUsers.value.map(ob => ob.id);
        const me = users.filter(ob => ob == auth.activeUser.id)
        return me && me.length
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

        return !taskIncomplete && !taskIncompleteforMe && expired
    })
    const isToday = computed(() => {
        const taskIncomplete = props.item.comp_flag
        const me = taskUsers.value.filter( ob => ob.id == auth.activeUser.id)
        const taskIncompleteforMe = me.length ? me[0].pivot.comp_flag : false
        const expired = props.item.end_at <= moment().format('YYYY-MM-DD HH:mm:ss')

        return !taskIncomplete && !taskIncompleteforMe && expired
    })

    const untilTomorrow = () => {
        const today = moment().startOf('day').add(1, 'days').format('YYYY-MM-DD HH:mm:ss');
        updateDate(props.item.id, today)
    }
    const updateDate = (id, date) => {
        axios.post('/task_update_api', {task_id: id, date: date}).then(response => {
            emit('taskDeleted')
        });
    }
    
    const deleteTask = async() => {
        const confirmed = await confirm('タスクを削除しますか。')
        if(!confirmed) return

        try{
            await axios.post('/task_delete_api', {task_id: props.item.id})
            info('削除しました。')
            emit('taskDeleted')
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    const detailsDateText = (value) => {
        moment.locale('ja');
        const thisYear = moment().year();
        const taskYear = moment(value).year();
        const hasTime = moment(value).format('HH:mm:ss') !== '00:00:00' && moment(value).format('HH:mm:ss') !== '12:00:00'            
        return (thisYear == taskYear) ? hasTime ? moment(value).format('M/D (dd) HH:mm') : moment(value).format('M/D (dd)')  :              
        hasTime ? moment(value).format('YYYY/M/D (dd) HH:mm') : moment(value).format('YYYY/M/D (dd)')               
    } 
    
    const urlCheck = (text) => {
        if(text){                
            var linkedText = Autolinker.link(text, {stripPrefix: false});              
            return linkedText;                
        }            
    }
    const viewTaskUsers = () => {
        const data = {
            active: true,
            userList: taskUsers.value,
            title: 'タスクメンバー'
        }
        messageUsers.setMessageUsers(data)
    }
</script>
