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
import { computed, nextTick, onMounted, useTemplateRef, ref } from 'vue'
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
import { useMessageUsers } from '@/store/messageUsers'
import { useUrlTask } from '@/store/urlTask';
import { useUrlTaskEdit } from '@/store/urlTaskEdit'
import UserPanel from '@/components/Global/UserPanel.vue';
import { Task } from '@/interface/globalInterface';
import { timeFormat, urlCheck } from '@/utils/tools';
import { DateTime } from 'luxon';
import { taskStatusBackgrounds } from '@/utils/tools';
import { dateDetail } from '@/utils/workApi';
import { useRoute } from 'vue-router';
    const messageUsers = useMessageUsers()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    const urlTask = useUrlTask()
    const urlTaskEdit = useUrlTaskEdit()
    const props = defineProps<{
        item: Task
        boxClass: string;
        isBoard: boolean
    }>()
    const emit = defineEmits(['reload', 'editTask', 'setActiveTask', 'getBoardTasks'])
    const dynamicHeight = ref('auto')
    const taskBody = useTemplateRef('taskBody')
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
    const taskColor = computed(() => {
        const userIds = taskUsers.value.map(ob => ob.id)
        const me = userIds.filter(ob => ob == auth.activeUser.id)
        const colors = {
            mycolor: me && me.length ? myColor.value : (route.name === 'remind' ? "var(--message-background)" : responsive.mobile ? "var(--message-background)" : "var(--task-background)"),
            color: me && me.length ? "#000" : "var(--primary-color)"
        }
        return colors
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


    const viewTaskUsers = () => {
        const data = {
            active: true,
            userList: taskUsers.value,
            title: 'タスクメンバー',
            isTask: props.item.end_at !== null
        }
        messageUsers.setMessageUsers(data)
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
</script>
