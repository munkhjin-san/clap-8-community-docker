<template>
    <div class="scrollable w-full text-[var(--primary-color)] relative pb-[20px]" ref="sortParent" @scroll="handleScroll">
        <div v-if="responsive.mobile" class="mem-header-section" :style="{'transform': `translateY(${offset}px)`}">        
            <div class="post-header sticky top-0 z-[11] bg-[var(--bg2)]" >
                <HamBurger />          
            </div>
        </div>
        <div class="flex flex-col" v-for="data in combinedData">
            <div v-if="data.not_started_tasks?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.not_started_tasks.length"
                    title="未対応タスク"
                    :expanded="expanded.not_started_tasks"
                    @expand="expanded.not_started_tasks = !expanded.not_started_tasks"
                />
                <div v-if="expanded.not_started_tasks" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.not_started_tasks">
                        <ListBox 
                            boxClass=""
                            v-if="item"
                            :item="item"  
                            :isBoard="false"
                            @get-board-tasks="refreshData('not_started_tasks')"
                            />
                    </div>
                </div>
            </div>
            <div v-if="data.not_completed_tasks?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.not_completed_tasks.length"
                    title="対応中タスク" 
                    :expanded="expanded.not_completed_tasks"
                    @expand="expanded.not_completed_tasks = !expanded.not_completed_tasks"
                />
                <div v-if="expanded.not_completed_tasks" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.not_completed_tasks">
                        <ListBox 
                            boxClass=""
                            v-if="item"
                            :item="item"  
                            :isBoard="false"
                            @get-board-tasks="refreshData('not_completed_tasks')"
                            />
                    </div>
                </div>
            </div>
            <div v-if="data.not_approved_tasks?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.not_approved_tasks.length"
                    title="タスク承認漏れ"
                    :expanded="expanded.not_approved_tasks"
                    @expand="expanded.not_approved_tasks = !expanded.not_approved_tasks"
                />
                <div v-if="expanded.not_approved_tasks" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.not_approved_tasks">
                        <ListBox 
                            boxClass=""
                            v-if="item"
                            :item="item"  
                            :isBoard="false"
                            @get-board-tasks="refreshData('not_approved_tasks')"
                            />
                    </div>
                </div>
            </div>
            <div v-if="data.unchecked_messages?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.unchecked_messages.length"
                    title="未確認メッセージ"
                    :expanded="expanded.unchecked_messages"
                    @expand="expanded.unchecked_messages = !expanded.unchecked_messages"
                />
                <div v-if="expanded.unchecked_messages" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.unchecked_messages">
                        <UncheckedMessageItem 
                            boxClass=""
                            v-if="item"
                            :message="item"
                            @get-unchecked-messages="refreshData('unchecked_messages')"  
                        />
                    </div>
                </div>
            </div>
            <div v-if=data.unsigned_messages?.length>
                <RemindHeader 
                    :offset="offset"
                    :length="data.unsigned_messages.length"
                    title="サイン依頼"
                    :expanded="expanded.unsigned_messages"
                    @expand="expanded.unsigned_messages = !expanded.unsigned_messages"
                />
                <div v-if="expanded.unsigned_messages" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.unsigned_messages">
                        <UncheckedMessageItem 
                            boxClass=""
                            v-if="item"
                            :message="item"
                        />
                    </div>
                </div>
            </div>
            <div v-if="data.reminded_messages?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.reminded_messages.length"
                    title="リマインドメッセージ"
                    :expanded="expanded.reminded_messages"
                    @expand="expanded.reminded_messages = !expanded.reminded_messages"
                />
                <div v-if="expanded.reminded_messages" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.reminded_messages">
                        <UncheckedMessageItem 
                            boxClass=""
                            v-if="item"
                            :message="item"
                            @get-remind-messages="refreshData('reminded_messages')"  
                        />
                    </div>
                </div>
            </div>
            <div v-if="data.not_approved_time_sheets?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.not_approved_time_sheets.length"
                    title="タイムシート承認漏れ"
                    :expanded="expanded.not_approved_time_sheets"
                    @expand="expanded.not_approved_time_sheets = !expanded.not_approved_time_sheets"
                />
                <div v-if="expanded.not_approved_time_sheets" class="shift-submitted-masonry-inner" style="display:flex; flex-direction: column; gap: 20px; width: fit-content;height: fit-content;">
                    <div v-for="item in data.not_approved_time_sheets">
                        <div style="display: grid; gap: 20px;">
                            <div style="display:flex;gap:35px;position:relative">
                                <div style="display:flex;gap: 10px">
                                    <UserPanel :disableInstant="true" size="30" :user="item.user" imgClass="userNormalIcon"/>
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
                                    <button class="shift-button" @click="router.push({name: 'timesheet', query: {user_id: item.user.id}})" style="white-space: nowrap;">対応</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="data.paid_leaves?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.paid_leaves.length"
                    title="計画有給"
                    :expanded="expanded.paid_leaves"
                    @expand="expanded.paid_leaves = !expanded.paid_leaves"
                />
                <div v-if="expanded.paid_leaves" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.paid_leaves">
                        <WorkMessage 
                            v-if="item"
                            :item="item.tempData"
                        />
                    </div>
                </div>
            </div>
            <div v-if="data.not_approved_projects?.length">
                <RemindHeader 
                    :offset="offset"
                    title="プロジェクト承認漏れ" 
                    :length="data.not_approved_projects?.length" 
                    :expanded="expanded.not_approved_projects"
                    @expand="expanded = !expanded"
                />
                <div v-if="expanded.not_approved_projects" class="shift-submitted-masonry-inner" style="display:flex; flex-direction: column; gap: 20px; width: fit-content;height: fit-content;">
                    <div v-for="user in data.not_approved_projects">
                        <div style="display: grid; gap: 20px;">
                            <div style="display:flex;gap:35px;position:relative">
                                <div style="display:flex;gap: 10px">
                                    <UserPanel :disableInstant="true" size="30" :user="user" imgClass="userNormalIcon"/>
                                    <div >
                                        <p style="margin-top: 5px">{{ user?.name }}</p>
                                        <div style="display:flex;flex-direction: column;gap:5px;margin-top: 10px;">
                                            <div class="number-chip" v-if="getGoals(user.outcome_goals).length">成果目標 : <strong style="color:var(--primary-color)">{{ getGoals(user.outcome_goals).length }}件</strong></div>
                                            <div class="number-chip" v-if="user.salary_issues?.length">昇給課題 : <strong style="color:var(--primary-color)">{{ user.salary_issues.length }}件</strong></div>
                                        </div>
                                    </div>                                        
                                </div>                                  
                                
                                <div style="margin-left: auto">                                        
                                    <button class="shift-button" @click="router.push({name: 'project-approval', params: { userId: user?.id}})" style="white-space: nowrap;">対応</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <router-view v-slot="{ Component }">
                    <transition name="modalFade">
                        <component
                            :is="Component" 
                            :projects="data.not_approved_projects"
                        />
                    </transition>
                    
                </router-view>
            </div>
            <div v-if="data.not_answered_forms?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.not_answered_forms.length"
                    title="未回答フォーム"
                    :expanded="expanded.not_answered_forms"
                    @expand="expanded.not_answered_forms = !expanded.not_answered_forms"
                />
                <div v-if="expanded.not_answered_forms" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="form in data.not_answered_forms" class="relative bg-[var(--background-color)] cursor-pointer p-[20px] ">
                        <div class="w-full">{{ form.title }}</div>
                        <div class="mt-[20px] w-fit">
                            <div @click.stop="surveyUsers.setSurveyUsers({title: 'フォーム管理者', active: true, users: form.admins || []})" class="flex text-[12px] items-center leading-normal">
                                <div>管理者 : </div>
                                <div class="flex ml-[5px]">
                                    <UserPanel v-for="admin in form.admins?.slice(0, 3)" :user="admin" size="15" disable-instant/>
                                    <p class="ml-[3px] mt-[3px]" v-if="form.admins && form.admins?.length > 3">{{ `...(${form.admins?.length}人)` }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-[10px] w-fit">
                            <div @click.stop="surveyUsers.setSurveyUsers({title: 'フォーム対象者', active: true, users: form.users || []})" class="flex text-[12px] items-center leading-normal">
                                <div>対象者 : </div>
                                <div class="flex ml-[5px] items-center">
                                    <div v-for="user in form.users?.slice(0, 3)" class="relative h-fit">
                                        <UserPanel :user="user" size="15" disable-instant/>
                                        <div v-if="user.is_answered" title="回答済み" class="completed-badge-large completed-badge-medium" style="background: green;"></div>
                                    </div>                                
                                    <p class="ml-[3px] mt-[3px]" v-if="form.users && form.users?.length > 3">{{ `...(${form.users?.length}人)` }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-[10px]">
                            <CommandButton 
                                :buttons="[
                                    {title: '回答', action: () => router.push(`/survey/${form.id}`)},
                                ]"
                            />
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="combinedData.every(item => Object.values(item).every(value => !value.length))" class="no-comment-text">現在リマインドはありません。</div>
        
        <Transition name="modalFade">
            <div class="cal-month-loader" style="height: 100%; top: 0; background-color: var(--bg2);" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
    </div>
</template>
<script lang="ts" setup>
import { useAuthUserStore } from '@/store/auth';
import ListBox from '../Task/List/ListBox.vue';
import UserPanel from '../Global/UserPanel.vue';
import axios from 'axios';
import { inject, nextTick, onMounted, provide, ref, useTemplateRef } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Dialog, Task } from '@/interface/globalInterface';
import UncheckedMessageItem from '../Board/Message/UncheckedMessageItem.vue';
import { notApproved, plannedLeave } from '@/interface/workInterface';
import { User } from '@/interface/globalInterface';
import WorkMessage from '../Work/WorkMessage.vue';
import { useSortable, moveArrayElement } from '@vueuse/integrations/useSortable.mjs';
import RemindHeader from './RemindHeader.vue';
import CommandButton from '../Global/CommandButton.vue';
import { useSurveyUsers } from '@/store/surveyUsers';
import { useResponsive } from '@/store/responsive';
import HamBurger from '../Global/HamBurger.vue';
const auth = useAuthUserStore()
const initialLoader = ref(true)
const combinedData = ref<{ [key: string]: any }[]>([])
const router = useRouter()
const { notify } = inject<Dialog>('dialog')!
const sortParent = useTemplateRef('sortParent')
const surveyUsers = useSurveyUsers()
const responsive = useResponsive()
const expanded = ref({
    not_started_tasks: true,
    not_completed_tasks: true,
    not_approved_tasks: true,
    unchecked_messages: true,
    unsigned_messages: true,
    reminded_messages: true,
    not_approved_time_sheets: true,
    paid_leaves: true,
    not_approved_projects: true,
    not_answered_forms: true
})
const offset = ref(0)
const prevScrollPosition = ref(0)
const handleScroll = () => {
    if(!sortParent.value ) return
    const currentScrollPosition = sortParent.value.scrollTop
    offset.value = currentScrollPosition > prevScrollPosition.value ? -95 : 0
    prevScrollPosition.value = currentScrollPosition   
}
const getGoals = (outcome_goals) => {
    if (auth.id == 631) {
        return outcome_goals.filter(goal => goal.status == 3)
    } else {
        return []
    }
}
const getUnsignedMessages = async() => {
    try{
        const response = await axios.get('/get_unsigned_messages')
        return response.data
    }catch (e){
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')  
    }
}
const getUncheckedMessages = async() => {
    try{
        const response = await axios.get('/get_unchecked_messages')
        return response.data
    }catch (e){
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const getNotApproved = async() => {
    if(auth && auth.activeUser.position_id == 6 || auth.activeUser.id == 610 || auth.activeUser.id == 608){
        try{
            const response = await axios.get('/not_approved')
            return response.data
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
        }
    }
}
const getTaskNotApproved = async() => {
    try {
        const response = await axios.get('/task_not_approved')
        return response.data
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
    }
}
const getProjectNotApproved = async() => {
    try {
        const response = await axios.get('/project_not_approved')
        return response.data
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
    }
    
}
const getRemindMessages = async() => {
    try{
        const response = await axios.get('/get_remind_messages')
        return response.data
    }catch (e){
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }     
}
const getPlannedShifts = async() => {
    
    try{
        const response = await axios.get('/get_temp_data')
        return response.data
    }catch (e){
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
    }
    
}
const changeUser = async(user: User) => {
    
    router.push({name: 'timesheet', query: {user_id: user.id}})
}
const getNotStartedTasks = async() => {
    try {
        const response = await axios.get('/get_not_started_tasks')
        return response.data
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
    }
}
const getNotCompletedTasks = async() => {
    try {
        const response = await axios.get('/get_not_completed_tasks')
        return response.data
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const getNotAnsweredForms = async() => {
    try {
        const response = await axios.get('/get_not_answered_forms')
        return response.data
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}

const performTasksOnMounted = async () => {
    try {
        const responses = await Promise.all([
            getUnsignedMessages(),
            getUncheckedMessages(),
            getNotApproved(),
            getTaskNotApproved(),
            getProjectNotApproved(),
            getRemindMessages(),
            getPlannedShifts(),
            getNotStartedTasks(),
            getNotCompletedTasks(),
            getNotAnsweredForms()
        ]);

        combinedData.value = responses.map((response, index) => ({
            ...response,
            order: index
        }));
        initialLoader.value = false
        const savedOrder = localStorage.getItem('savedSortOrder') ? JSON.parse(localStorage.getItem('savedSortOrder')!) : null;
        if (savedOrder) {
            reorderDataBySavedOrder(savedOrder);
        }
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。');
    }
};

const saveSortOrder = () => {
    const order = combinedData.value.map((item, index) => ({
        name: Object.keys(item).find(key => key !== 'order') || '',
        index: index
    }));
    localStorage.setItem('savedSortOrder', JSON.stringify(order));
};

const reorderDataBySavedOrder = (savedOrder) => {
    const reorderedData = savedOrder.map(order => {
        return combinedData.value.find(item => item.hasOwnProperty(order.name));
    }).filter(item => item);

    combinedData.value = reorderedData;
};

const refreshData = async (dataType) => {
    try {
        let response;
        switch (dataType) {
            case 'unsigned_messages':
                response = await getUnsignedMessages();
                break;
            case 'unchecked_messages':
                response = await getUncheckedMessages();
                break;
            case 'not_approved_tasks':
                response = await getTaskNotApproved();
                break;
            case 'not_approved_projects':
                response = await getProjectNotApproved();
                break;
            case 'not_started_tasks':
                response = await getNotStartedTasks();
                await refreshData('not_completed_tasks');
                break;
            case 'not_completed_tasks':
                response = await getNotCompletedTasks();
                break;
            case 'reminded_messages':
                response = await getRemindMessages();
                break;
            case 'not_answered_forms': 
                response = await getNotAnsweredForms();
                break;
            default:
                throw new Error('Invalid data type');
        }

        const index = combinedData.value.findIndex(item => item.hasOwnProperty(dataType));
        if (index !== -1) {
            combinedData.value[index] = { ...response, order: combinedData.value[index].order };
        }
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。');
    }
};

useSortable(sortParent, combinedData.value, {
    animation: 150,
    handle: '.handler',
    onUpdate: (e) => {
        moveArrayElement(combinedData.value, e.oldIndex, e.newIndex, e);
        nextTick(() => {
            saveSortOrder();
        });
    }
});
onMounted(() => {
    performTasksOnMounted()
})
defineExpose({
    refreshData
})
</script>