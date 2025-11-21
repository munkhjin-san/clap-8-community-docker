<template>
    <div class="scrollable w-full text-[var(--primary-color)] relative pb-[20px]" ref="sortParent" @scroll="handleScroll">
        <div v-if="responsive.mobile" class="mem-header-section" :style="{'transform': `translateY(${offset}px)`}">        
            <div class="post-header sticky top-0 z-[11] bg-[var(--bg2)]" >
                <HamBurger />          
            </div>
        </div>
        <div class="flex flex-col" v-for="data in combinedData">
            <div v-if="data.remind_task_untouched?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_task_untouched.length"
                    title="未対応タスク"
                    :expanded="expanded.remind_task_untouched"
                    @expand="expanded.remind_task_untouched = !expanded.remind_task_untouched"
                />
                <div v-if="expanded.remind_task_untouched" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.remind_task_untouched" class="min-w-0">
                        <ListBox 
                            boxClass=""
                            v-if="item"
                            :item="item"  
                            :isBoard="false"
                            @get-board-tasks="refreshData('remind_task_untouched')"
                            />
                    </div>
                </div>
            </div>
            <div v-if="data.remind_task_unfinished?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_task_unfinished.length"
                    title="対応中タスク" 
                    :expanded="expanded.remind_task_unfinished"
                    @expand="expanded.remind_task_unfinished = !expanded.remind_task_unfinished"
                />
                <div v-if="expanded.remind_task_unfinished" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.remind_task_unfinished" class="min-w-0">
                        <ListBox 
                            boxClass=""
                            v-if="item"
                            :item="item"  
                            :isBoard="false"
                            @get-board-tasks="refreshData('remind_task_unfinished')"
                            />
                    </div>
                </div>
            </div>
            <div v-if="data.remind_task_not_approved?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_task_not_approved.length"
                    title="タスク承認漏れ"
                    :expanded="expanded.remind_task_not_approved"
                    @expand="expanded.remind_task_not_approved = !expanded.remind_task_not_approved"
                />
                <div v-if="expanded.remind_task_not_approved" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.remind_task_not_approved" class="min-w-0">
                        <ListBox 
                            boxClass=""
                            v-if="item"
                            :item="item"  
                            :isBoard="false"
                            @get-board-tasks="refreshData('remind_task_not_approved')"
                            />
                    </div>
                </div>
            </div>
            <div v-if="data.remind_unchecked_messages?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_unchecked_messages.length"
                    title="未確認メッセージ"
                    :expanded="expanded.remind_unchecked_messages"
                    @expand="expanded.remind_unchecked_messages = !expanded.remind_unchecked_messages"
                />
                <div v-if="expanded.remind_unchecked_messages" class="md:grid flex flex-col md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.remind_unchecked_messages">
                        <UncheckedMessageItem 
                            boxClass=""
                            v-if="item"
                            :message="item"
                            @get-unchecked-messages="refreshData('remind_unchecked_messages')"  
                        />
                    </div>
                </div>
            </div>
            <div v-if=data.remind_unsigned_messages?.length>
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_unsigned_messages.length"
                    title="サイン依頼"
                    :expanded="expanded.remind_unsigned_messages"
                    @expand="expanded.remind_unsigned_messages = !expanded.remind_unsigned_messages"
                />
                <div v-if="expanded.remind_unsigned_messages" class="md:grid flex flex-col md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.remind_unsigned_messages">
                        <UncheckedMessageItem 
                            boxClass=""
                            v-if="item"
                            :message="item"
                        />
                    </div>
                </div>
            </div>
            <div v-if="data.remind_reminded_messages?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_reminded_messages.length"
                    title="リマインドメッセージ"
                    :expanded="expanded.remind_reminded_messages"
                    @expand="expanded.remind_reminded_messages = !expanded.remind_reminded_messages"
                />
                <div v-if="expanded.remind_reminded_messages" class="md:grid flex flex-col md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.remind_reminded_messages">
                        <UncheckedMessageItem 
                            boxClass=""
                            v-if="item"
                            :message="item"
                            @get-remind-messages="refreshData('remind_reminded_messages')"  
                        />
                    </div>
                </div>
            </div>
            <div v-if="data.remind_timesheet?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_timesheet.length"
                    title="タイムシート承認漏れ"
                    :expanded="expanded.remind_timesheet"
                    @expand="expanded.remind_timesheet = !expanded.remind_timesheet"
                />
                <div v-if="expanded.remind_timesheet" class="shift-submitted-masonry-inner mx-[20px]" style="display:flex; flex-direction: column; gap: 20px; width: fit-content;height: fit-content;">
                    <div v-for="item in data.remind_timesheet">
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
            <div v-if="data.remind_planned_leave?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_planned_leave.length"
                    title="計画有給"
                    :expanded="expanded.remind_planned_leave"
                    @expand="expanded.remind_planned_leave = !expanded.remind_planned_leave"
                />
                <div v-if="expanded.remind_planned_leave" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.remind_planned_leave">
                        <WorkMessage 
                            v-if="item"
                            :item="item.tempData"
                        />
                    </div>
                </div>
            </div>
            <div v-if="data.not_approved_increases?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.not_approved_increases.length"
                    title="人事考課承認漏れ"
                    :expanded="expanded.not_approved_increases"
                    @expand="expanded.not_approved_increases = !expanded.not_approved_increases"
                />
                <div v-if="expanded.not_approved_increases" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.not_approved_increases" class="p-[15px] bg-[var(--background-color)]">
                        <div class="flex flex-col gap-[15px] text-[13px]">
                            <UserPanel v-if="item.user" disable-instant with-name size="30" :user="item.user"/>
                            <div class="text-[gray]">{{ `${item.year}${item.which_half == 'first' ? '上期' : '下期'}` }}</div>
                            <div>{{ `メンター : ${item?.mentor?.name}`  }}</div>
                            <CommandButton 
                                :buttons="[
                                    {title: '対応', action: () => router.push({name: 'evaluation-approval', params: {memberId: item.user.id, span: `${item.year}-${item.which_half}`}})}
                                ]"
                            />
                        </div>
                    </div>
                </div>
                <router-view v-slot="{ Component }" v-if="route.fullPath.includes('evaluation-approval')">
                    <transition name="modalFade">
                        <component
                            :is="Component" 
                            :evaluations="data.not_approved_increases"
                        />
                    </transition>                    
                </router-view>
            </div>
            <div v-if="data.remind_project_not_approved?.length">
                <RemindHeader 
                    :offset="offset"
                    title="プロジェクト承認漏れ" 
                    :length="data.remind_project_not_approved?.length" 
                    :expanded="expanded.remind_project_not_approved"
                    @expand="expanded.remind_project_not_approved = !expanded.remind_project_not_approved"
                />
                <div v-if="expanded.remind_project_not_approved" class="shift-submitted-masonry-inner mx-[20px]" style="display:flex; flex-direction: column; gap: 20px; width: fit-content;height: fit-content;">
                    <div v-for="user in data.remind_project_not_approved">
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
                <router-view v-slot="{ Component }" v-if="route.fullPath.includes('project-approval')">
                    <transition name="modalFade">
                        <component
                            :is="Component" 
                            :projects="data.remind_project_not_approved"
                        />
                    </transition>                    
                </router-view>
            </div>
            <div v-if="data.remind_form?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_form.length"
                    title="未回答フォーム"
                    :expanded="expanded.remind_form"
                    @expand="expanded.remind_form = !expanded.remind_form"
                />
                <div v-if="expanded.remind_form" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="form in data.remind_form" class="relative bg-[var(--background-color)] cursor-pointer p-[20px] ">
                        <div class="w-full">{{ form.title }}</div>
                        <!-- <div class="mt-[20px] w-fit">
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
                        </div> -->
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
            <div v-if="data.remind_asset?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_asset.length"
                    title="物品受け取り依頼"
                    :expanded="expanded.remind_asset"
                    @expand="expanded.remind_asset = !expanded.remind_asset"
                />
                <div v-if="expanded.remind_asset" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.remind_asset" class="bg-[var(--background-color)] p-[10px] text-[12px]">
                        <div class="p-[10px] overflow-hidden break-words leading-normal">{{ item.item_name }}</div>
                        <AssetMovement 
                            :asset="item" 
                            :asset-request="item.requests[0]"
                        />
                    </div>
                </div>
            </div>
            <div v-if="data.remind_temp_reserved_schedules?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_temp_reserved_schedules.length"
                    title="未確定スケジュール"
                    :expanded="expanded.remind_temp_reserved_schedules"
                    @expand="expanded.remind_temp_reserved_schedules = !expanded.remind_temp_reserved_schedules"
                />
                <div v-if="expanded.remind_temp_reserved_schedules" class="grid md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <ConfirmSchedule 
                        v-for="item in data.remind_temp_reserved_schedules" 
                        :key="item.id"
                        :record="item"
                        @refresh="refreshData('remind_temp_reserved_schedules')"
                    />
                </div>
            </div>

            <div v-if="data.remind_departure_report?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_departure_report.length"
                    title="出発報告状況"
                    :expanded="expanded.remind_departure_report"
                    @expand="expanded.remind_departure_report = !expanded.remind_departure_report"
                />
                <div v-if="expanded.remind_departure_report" class="mx-[20px] overflow-hidden w-fit flex flex-col gap-[10px] bg-[var(--background-color)] p-[15px]">
                    <div v-for="item in data.remind_departure_report">
                        <div class="flex gap-[15px] text-[13px] items-center">
                            <UserPanel disable-instant with-name size="30" :user="item"/>
                            <div class="text-[gray]">{{ `${item.shift_records && item.shift_records.length && item.shift_records[0].departure_report ? DateTime.fromSQL(item.shift_records[0].departure_report).toFormat('M / d HH:mm') : '未報告'}` }}</div>
                        </div>
                    </div>
                </div>

            </div>
            <div v-if="data.remind_challenge?.length">
                <RemindHeader 
                    :offset="offset"
                    :length="data.remind_challenge?.length"
                    title="チャレンジ進捗待ち"
                    :expanded="expanded.remind_challenge_progress"
                    @expand="expanded.remind_challenge_progress = !expanded.remind_challenge_progress"
                />
                <div v-if="expanded.remind_challenge_progress" class="md:grid flex flex-col md:grid-cols-4 gap-5 mx-[20px] overflow-hidden">
                    <div v-for="item in data.remind_challenge" class="bg-[var(--message-background)] p-[15px] text-[var(--primary-color) min-h-full]">
                        
                        <div class="flex gap-3 mb-3 relative">
                            <PostIcon which="2" size="20"/>
                            <p>{{ item.title }}</p>
                            <div style="font-size:12px;color:grey;position:absolute;right:-10px;top:-10px">
                                {{ item.date_start }} ~ {{ item.date_end }}
                            </div>
                        </div>
                        <div class="text-sm leading-normal whitespace-break-spaces">
                            <p>{{ item.content_goal }}</p>
                        </div>
                        <button @click="router.push({name: 'post', query: {id: item.id} })" style="padding: 5px 10px; font-size: 12px; line-height: 1.5; border-radius: 0px; background: var(--primary-button); color: rgb(255, 255, 255); margin-top: 10px;">
                            対応
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="hasNoRemindData" class="no-comment-text">現在リマインドはありません。</div>
        
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
import { computed, nextTick, onMounted, provide, ref, useTemplateRef } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import UncheckedMessageItem from '../Board/Message/UncheckedMessageItem.vue';
import WorkMessage from '../Work/WorkMessage.vue';
import { useSortable, moveArrayElement } from '@vueuse/integrations/useSortable.mjs';
import RemindHeader from './RemindHeader.vue';
import CommandButton from '../Global/CommandButton.vue';
import { useSurveyUsers } from '@/store/surveyUsers';
import { useResponsive } from '@/store/responsive';
import HamBurger from '../Global/HamBurger.vue';
import AssetMovement from '../Asset/AssetMovement.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import ConfirmSchedule from './ConfirmSchedule.vue';
import { DateTime } from 'luxon';
import PostIcon from '../Post/PostIcon.vue';
const auth = useAuthUserStore()
const initialLoader = ref(true)
const combinedData = ref<{ [key: string]: any }[]>([])
const router = useRouter()
const { ping } = useDialog()
const sortParent = useTemplateRef('sortParent')
const responsive = useResponsive()
const expanded = ref({
    remind_task_untouched: true,
    remind_task_unfinished: true,
    remind_task_not_approved: true,
    remind_unchecked_messages: true,
    remind_unsigned_messages: true,
    remind_reminded_messages: true,
    remind_timesheet: true,
    remind_planned_leave: true,
    remind_project_not_approved: true,
    remind_form: true,
    not_approved_increases: true,
    remind_asset: true,
    remind_schedules: true,
    remind_temp_reserved_schedules: true,
    remind_departure_report: true,
    remind_challenge_progress: true
})

const api = useApi()
const offset = ref(0)
const prevScrollPosition = ref(0)
const route = useRoute()
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






const getData = async (path:string) => {
    const data = await api.get(path);
    return data;
}
const getRemindTotalData = async () => {
    
    const responses = await Promise.all([
        getData('/remind_unsigned_messages'),
        getData('/remind_unchecked_messages'),
        getData('/remind_task_not_approved'),
        getData('/remind_project_not_approved'),
        getData('/remind_timesheet'),
        getData('/remind_planned_leave'),
        getData('/remind_reminded_messages'),
        getData('/remind_task_untouched'),
        getData('/remind_task_unfinished'),
        getData('/remind_form'),
        getData('/remind_asset'),
        getData('/remind_temp_reserved_schedules'),
        getData('/remind_challenge_progress'),
        auth.id && [833,832].includes(auth.id) ? getData('/remind_departure_report') : Promise.resolve([]),

    ]);

    combinedData.value = responses.map((response, index) => ({
        ...response,
        order: index
    }));
    initialLoader.value = false
    const savedOrder = localStorage.getItem('savedSortOrder') ? JSON.parse(localStorage.getItem('savedSortOrder')!) : null;
    if (savedOrder) {
        if (savedOrder.length !== combinedData.value.length) {
            saveSortOrder()
        }
        reorderDataBySavedOrder(savedOrder);
    }
    
};
const hasNoRemindData = computed(() => {
  return combinedData.value.every(item => {
    return Object.entries(item).every(([key, value]) => {
      // ignore order or other meta fields
      if (key === 'order') return true;

      // only care about arrays (your remind_* fields)
      if (Array.isArray(value)) {
        return value.length === 0;
      }

      // ignore any non-array junk if it sneaks in
      return true;
    });
  });
});

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
        let response = await getData(`/${dataType}`);        

        const index = combinedData.value.findIndex(item => item.hasOwnProperty(dataType));
        if (index !== -1) {
            combinedData.value[index] = { ...response, order: combinedData.value[index].order };
        }
    } catch (e) {
        ping(e.response?.data.message || e?.message || 'エラーが発生しました。');
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
    getRemindTotalData()
})
defineExpose({
    refreshData
})

provide('getAssets', () => refreshData('remind_asset'))
</script>