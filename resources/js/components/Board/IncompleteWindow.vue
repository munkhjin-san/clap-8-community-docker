<template>
    <Transition name="modalFade">
    <div v-if="incompleteShow && !isEdit && !isJumpToMessage && !(route.name == 'timesheet' && route.query.user_id)" class="overlay">
            
        <div style="display:flex;flex-direction:column;width:100%;height:100%; margin: 20px 0;overflow: hidden auto;">
            <div @click="emit('closePopup')" class="modalCloseButton cursor-pointer">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" fill="#fff" style="margin: auto;">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg>
            </div>
            <div class="shift-submitted-masonry" v-if="notapprovedTimecards.length || notapprovedTasks.length || notapprovedProjects.length" style="padding: 0 25px; margin-top:50px;display: flex; gap: 30px;flex-wrap: wrap;">
                <div v-if="notapprovedTimecards.length" class="shift-submitted-masonry-inner" style="display:flex; flex-direction: column; gap: 20px; width: fit-content;height: fit-content;">
                    <div><strong>タイムシート承認漏れがあります</strong></div>
                    <div v-for="item in notapprovedTimecards">
                        <div style="display: grid; gap: 20px;">
                            <div style="display:flex;gap:35px;position:relative">
                                <div style="display:flex;gap: 10px">
                                    <UserIcon :disableInstant="true" size="30" :user="item.user" imgClass="userNormalIcon"/>
                                    <div >
                                        <p style="margin-top: 5px">{{ item.user?.name }}</p>
                                        <div style="display:flex;flex-direction: column;gap:5px;margin-top: 10px;">
                                            <div class="number-chip" v-if="item.timecard">日報申請 : <strong style="color:var(--primary-color)">{{ item.timecard }}件</strong></div>
                                            <div class="number-chip" v-if="item.overtime">残業申請 : <strong style="color:var(--primary-color)">{{ item.overtime }}件</strong></div>
                                            <template v-if="item.shift && item.shift.length">
                                                <div v-for="(shift) in item.shift" class="number-chip">
                                                    勤怠予定申請 : {{ shift.month }}月分<strong style="color:var(--primary-color)">{{shift.count}}件</strong>
                                                </div>
                                               
                                            </template>
                                        </div>
                                    </div>                                        
                                </div>                                  
                                
                                <div style="margin-left: auto">                                        
                                    <button class="shift-button" @click="changeUser(item.user)" style="white-space: nowrap;">対応</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="notapprovedProjects.length" class="shift-submitted-masonry-inner" style="display:flex; flex-direction: column; gap: 20px; width: fit-content;height: fit-content;">
                    <div><strong>プロジェクト承認漏れがあります</strong></div>
                    <div v-for="user in notapprovedProjects">
                        <div style="display: grid; gap: 20px;">
                            <div style="display:flex;gap:35px;position:relative">
                                <div style="display:flex;gap: 10px">
                                    <UserIcon :disableInstant="true" size="30" :user="user" imgClass="userNormalIcon"/>
                                    <div >
                                        <p style="margin-top: 5px">{{ user?.name }}</p>
                                        <div style="display:flex;flex-direction: column;gap:5px;margin-top: 10px;">
                                            <div class="number-chip" v-if="getGoals(user.outcome_goals).length">成果目標 : <strong style="color:var(--primary-color)">{{ getGoals(user.outcome_goals).length }}件</strong></div>
                                            <div class="number-chip" v-if="user.salary_issues?.length">昇給課題 : <strong style="color:var(--primary-color)">{{ user.salary_issues.length }}件</strong></div>
                                        </div>
                                    </div>                                        
                                </div>                                  
                                
                                <div style="margin-left: auto">                                        
                                    <button class="shift-button" @click="openCheckGoal(user)" style="white-space: nowrap;">対応</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="notapprovedTasks.length" class="shift-submitted-masonry-inner" style="display:flex; flex-direction: column; gap: 20px; width: fit-content;height: fit-content; max-width: 313px;">
                    <div><strong>タスク承認漏れがあります</strong></div>
                    <div v-for="item in notapprovedTasks" class="notapproved-wrap">
                        <div style="font-size:14px; line-height: 1.5;word-break: break-all;" v-html="urlCheck(item.remarks, 100)"></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 13px; font-weight: 600;">承認待ち : {{ item.executors.length }}</div>                                        
                            <button class="shift-button" @click="taskResponsed(item)" style="white-space: nowrap;">対応</button>
                        </div> 
                    </div>
                </div>
            </div>
            <div v-if="planShift" style="padding: 0 10px;">
                <div class="incompleted-title">計画有給を入力してください</div>
                <masonry-wall :items="[tempData]" :column-width="360" :gap="responsive.mobile ? 0 : 30">
                    <template v-slot:default="{item}">
                    <WorkMessage 
                        v-if="item"
                        :item="item"
                        @close="closeOverRide()"
                    />
                </template>
                </masonry-wall>
            </div>
            <div v-if="incompletedTasksList.length" style="padding: 0 10px;">
                <div class="incompleted-title">{{ `${incompletedTasksList.length} 件の期限を過ぎたタスクがあります`}}</div>

            <masonry-wall :items="incompletedTasksList" :column-width="360" :gap="responsive.mobile ? 0 : 30">
                <template v-slot:default="{item}">
                    <ListBox 
                        boxClass="incompleted-task-box-container"
                        v-if="item"
                        :item="item"  
                        :isBoard="true"
                        @editTask="editTask" 
                        @taskUserViewToggle="taskUserViewToggle"
                        @completeTaskBefore="completeTaskBefore"
                        @taskDeleted="taskDeleted"
                        @get-board-tasks="getIncompletedTasks"
                    />
                </template>
            </masonry-wall>
            </div>
            <div v-if="uncheckedMessages.length" style="padding: 0 10px;">
                <div class="incompleted-title" style="">未確認メッセージが{{uncheckedMessages.length}}件あります</div>
                <masonry-wall :items="uncheckedMessages" :column-width="360" :gap="responsive.mobile ? 0 : 30">
                    <template v-slot:default="{item}">
                        <UncheckedMessageItem 
                            boxClass="incompleted-task-box-container"
                            v-if="item"
                            :message="item"
                            :reminder="reminder"
                        />
                    </template>
                </masonry-wall>
            </div>
            <div v-if="unsignedMessages.length" style="padding: 0 10px;">
                <div class="incompleted-title" style="">{{ `${unsignedMessages.length}件の文書にサインする必要があります`}}</div>
                <masonry-wall :items="unsignedMessages" :column-width="360" :gap="responsive.mobile ? 0 : 30">
                    <template v-slot:default="{item}">
                        <UncheckedMessageItem 
                            boxClass="incompleted-task-box-container"
                            v-if="item"
                            :message="item"
                            :reminder="reminder"
                            @reload="() => getUnsignedMessages()"
                        />
                    </template>
                </masonry-wall>
            </div>
            <div v-if="remindMessages.length" style="padding: 0 10px;">
                <div class="incompleted-title" style="">リマインドメッセージが{{remindMessages.length}}件あります</div>
                <masonry-wall :items="remindMessages" :column-width="360" :gap="responsive.mobile ? 0 : 30">
                    <template v-slot:default="{item}">
                        <UncheckedMessageItem 
                            boxClass="incompleted-task-box-container"
                            v-if="item"
                            :message="item"
                            @remindRequest="remindRequest"
                            @reload="() => getRemindMessages()"
                        />
                    </template>
                </masonry-wall>
            </div>    
            
        
        </div>
        <Transition name="modalFade">
            <CheckGoal
                v-if="goals.length" 
                :projectGoals="goals"
                :memberData="memberData"
                @close="closeCheckGoal"
            />
        </Transition>
    </div>
    </Transition>
</template>

<script setup>
import TaskBoxpreload from "./Tray/Task/TaskBox.vue"
import WorkMessage from "../Work/WorkMessage.vue"
import UncheckedMessageItem from "./Message/UncheckedMessageItem.vue"
import CheckGoal from "../Global/CheckGoal.vue";
import { ref, onMounted, watch, computed, inject, provide } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useTaskFeedback } from '@/store/taskFeedback'
import UserIcon from "./Mixed/UserIcon.vue"
import { useRoute, useRouter } from "vue-router";
import { useCheckApproval } from "../../store/checkApproval";
import { useResponsive } from "@/store/responsive"
import axios from "axios";
import { useTaskRequest } from "@/store/taskRequest";
import Autolinker from 'autolinker';
import moment from "moment";
import ProjectGoalMore from "../Project/ProjectGoalMore.vue";
import ListBox from '@/components/Task/List/ListBox.vue'
    const route = useRoute()
    const router = useRouter()
    const auth = useAuthUserStore()
    const taskFeedback = useTaskFeedback()
    const emit = defineEmits(['closePopup'])
    const props = defineProps(['canGetRemind'])
    const incompletedTasksList = ref([])
    const unsignedMessages = ref([])
    const selectedComplete = ref({
                    status: false,
                    record: null
                })
    const reminder = 'reminder'
    const { notify, info } = inject('dialog')
    const remindMessages = ref([])
    const uncheckedMessages = ref([])
    const planShift = ref(false)
    const tempData = ref([])
    const remainingDays = ref(0)
    const notapprovedTimecards = ref([])
    const notapprovedTasks = ref([])
    const checkApproval = useCheckApproval()
    const taskRequest = useTaskRequest()
    const notapprovedProjects = ref([])
    const goals = ref([])
    const currentUserId = ref(null)
    const memberData = ref(null)
    const responsive = useResponsive()
    const closePopupIfNeeded = () => {
        if(!incompleteShow.value){
            closeOverRide()
        }
    }
    const performTasksOnMounted = async () => {
        try{
            await Promise.all([
                getIncompletedTasks(),
                getUnsignedMessages(),
                getUncheckedMessages(),
                getPlannedShifts(),
                getNotApproved(),
                getTaskNotApproved(),
                getProjectNotApproved(),
                getRemindMessages()
            ]);
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }finally{
            closePopupIfNeeded()
        }
    }
    onMounted(() => {
        performTasksOnMounted()
        // if (props.canGetRemind) {
        //     getRemindMessages()
        // }
    })
    watch(
        () => taskFeedback.active,
        (after, before) => {
            if (after === false) {
                getIncompletedTasks();
            }
        }
    )
    watch(
        () => checkApproval.approved,
        (after) => {
            if(after) {
                getNotApproved();
                getIncompletedTasks();
            }
        }
    )
    const openCheckGoal = (user) => {
        goals.value = user?.outcome_goals;
        currentUserId.value = user?.id;
        memberData.value = user
    }
    const closeCheckGoal = () => {
        goals.value = []
        currentUserId.value = null
        memberData.value = null
    }
    const updateGoalsAfterFetch = () => {
        if (currentUserId.value !== null) {
            const userProjects = notapprovedProjects.value.filter(project => project.id === currentUserId.value);
            const updatedGoals = userProjects.flatMap(project => project.outcome_goals);
            goals.value = updatedGoals;
        }
    };
    const getGoals = (outcome_goals) => {
        if ( auth.user.position_id <= 6 ) {
            return outcome_goals.filter(goal => 
                    (goal.status == 2 || goal.status == 4) && 
                    (goal.project.manager.some(manager => manager.id === auth.id) ||
                    goal.project.director_id === auth.id))
        } else if (auth.id == 631) {
            return outcome_goals.filter(goal => goal.status == 3)
        } else {
            return []
        }
    }
    const urlCheck = (text, limit) => {
        if(text){                
            var linkedText = Autolinker.link(text, {stripPrefix: false}); 
            if(linkedText.length > limit){
                return linkedText.slice(0, limit) + "...";
            }             
            return linkedText;                
        }            
    }
    const incompleteShow = computed(() =>{
        const hasItems =
            incompletedTasksList.value.length ||
            unsignedMessages.value.length ||
            remindMessages.value.length ||
            uncheckedMessages.value.length ||
            notapprovedTimecards.value.length ||
            notapprovedTasks.value.length || 
            notapprovedProjects.value.length
        const hasPlanShift = planShift.value

        return hasItems || hasPlanShift       
    })
    const isJumpToMessage = computed(() => {
        const url_string = window.location.href;
        const url = new URL(url_string);
        const b_id = url.searchParams.get("jump_message");
        if(b_id){
            return true
        }
        return false
    })
    const isEdit = computed(() => {
        const url_string = window.location.href;
        const url = new URL(url_string);
        const b_id = url.searchParams.get("task_edit");
        const action = url.searchParams.get("action");
        if(b_id || action){                
            return true          
        }
        return false
    })
        
    const closeOverRide = () => {
        emit('closePopup')
    }
    const changeUser = async(user) => {
        const authorized_user = auth.user.linked && auth.user.linked.length ? auth.user.linked.find(ob => ob.id === 610) : null
        if(authorized_user){
            await auth.setActiveUser(authorized_user.id)
        }
        router.push({name: 'timesheet', query: {user_id: user.id}})
    }
    const getProjectNotApproved = async() => {
        
        try {
            const data = await axios.get('/project_not_approved').then(res => res.data)
            notapprovedProjects.value = data
            updateGoalsAfterFetch()
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
        }
        
    }
    const getNotApproved = async() => {

        if(auth && auth.user.position_id == 6 || auth.activeUser.id == 610 || auth.activeUser.id == 608){
            try{
                const response = await axios.get('/not_approved')
                notapprovedTimecards.value = Object.values(response.data)
                checkApproval.setCheckApproval(false)
            } catch (e) {
                notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
            }
        }

    }
    const getTaskNotApproved = async() => {
        try {
            notapprovedTasks.value = await axios.get('/task_not_approved').then(res => res.data)
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
        }
    }
    const getPlannedShifts = async() => {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear() - 1;
        const targetDate = new Date(currentYear, 11, 20);

        if(currentDate > targetDate){
            try{
                const response = await axios.post('/get_temp_data', { user_code: auth.activeUser.user_code, year: currentDate.getFullYear() })
                if(response.data && response.data.tempData){
                    tempData.value = response.data.tempData
                    remainingDays.value = response.data.remaining_days
                    if(response.data.shift_count < tempData.value.planned_days){
                        planShift.value = true
                    }else{
                        planShift.value = false
                    }
                }
            }catch (e){
                notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
            }
        }
    }
    const remindRequest = async (data) => {
        try {
            const response = await axios.post('/remind_add', { id: data.id }).then(res => res.data)
            const message = response ? 'リマインドしました。' : 'リマインドを取り消しました。'
            info(message)
        } catch (e) { 
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            getRemindMessages()
        }     
    }

    const reload = () => {
        getUnsignedMessages()
        getRemindMessages()
        getUncheckedMessages()
    }
    const taskCompleted = () => {
        getIncompletedTasks()
        resetSelectedComplete()
    }
    const resetSelectedComplete = () => {
        const data = {
            status : false,
            record: null
        }
        selectedComplete.value = data
    }
    const getIncompletedTasks = async() => {
        try{
            const response = await axios.post('/get_incompleted_tasks')
            incompletedTasksList.value = response.data
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')    
        }
    }
    const getUnsignedMessages = async() => {
        try{
            const response = await axios.post('/get_unsigned_messages')
            unsignedMessages.value = response.data.message_list
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')  
        }
    }
    const taskResponsed = (task) => {
        const url = '/board/' + task.board_id + '?t='+ task.id + '&action=true'
        window.open(url, '_blank').focus();
    }
    const editTask = (task) => {
        const url = '/board/' + task.board_id + '?t='+ task.id + '&task_edit=true'
        window.open(url, '_blank').focus();
    }
    const getUncheckedMessages = async() => {
        try{
            const response = await axios.post('/get_unchecked_messages')
            uncheckedMessages.value = response.data
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    const getRemindMessages = async() => {
        try{
            const response = await axios.post('/get_remind_messages')
            remindMessages.value = response.data
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
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
        
        
        
        const canPlayNine = task.executors.some(member => member.id === auth.activeUser.id && member.pivot.glowd_nine === 1)
        if (canPlayNine && !overdue) {
            playNineWindow.value = true
        } else if (canPlayNine && overdue) {
            notify('期限内にタスクを完了しなかったため、グラウドナインは適用されませんでした。')
        }
        completeTask(task.id, 1, 0);       
       
    }
    const completeTask = (task_id, compFlag, statusFlag) => {
        
        axios.post("/complete_task_api", { task_id: task_id, comp_flag: compFlag, status_flag: statusFlag }).then(response => {
            getIncompletedTasks();
        })
    }
    const taskDeleted = () => {
        getIncompletedTasks()
    }
    provide('getUncheckedMessages', getUncheckedMessages)
    provide('refresh', getProjectNotApproved)
    // provide('boardItem', {
    //     refreshMessages: () => getRemindMessages(),
    // })
    defineExpose({getUnsignedMessages})
</script>
<style lang="scss">
.number-chip{
    font-size: 13px;
}
.incompleted-title{
    font-size:14px;
    color:#fff;
    margin: 20px auto 0;
    text-align: center;
}
.notapproved-wrap{
    display: flex; 
    gap: 10px; 
    flex-direction: column;
    padding: 10px;
    cursor: pointer;
}
.notapproved-wrap:hover{
    background: var(--bg2);
    color: var(--primary-color);
}
</style>

