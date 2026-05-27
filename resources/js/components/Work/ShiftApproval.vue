<template>
    <div class="work-modal" @mousedown="emit('closeModal')">
        <div class="work-modal-inner overstyle" @mousedown.stop>
            <Transition name="modalFade">
                <div class="work-loader" v-if="loading == 0">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div> 
            </Transition>
            <div class="recordFormTitle" style="z-index: 26; gap:30px;">
                <p style="font-size: 18px;">{{ approveYear }}年{{ approveMonth }}月の勤怠予定承認</p>
                <div @click="emit('closeModal')" class="cursor-pointer" style="margin: auto 0 auto auto;">
                    <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>
            <div style="margin: 10px 0 30px; display: flex; gap: 30px; position: relative; justify-content: space-between;">
                <button style="margin: unset;" class="work-button" @click.stop="menu.setMenu( { id: 199, name: 'workMemberSelector'})">メンバー</button>
                <MonthPickerNew
                    v-model:month="approveMonth"
                    v-model:year="approveYear"
                    :right="'auto'" 
                    @setDate="setDate"
                />
                <button style="margin: unset;background-color: tomato;" class="work-button" @click="approveAll">一括承認</button>
                <Transition name="modalFade">
                    <WorkMembers 
                        v-if="menu.id == 199 && menu.name == 'workMemberSelector'"
                        :workUsers="workUsers"
                        :workGroups="workGroups"
                        :loading="loading"
                        customStyle="width: fit-content; left:0; top:40px; max-width: 100%;"
                        v-model:users="checkedUsers"
                    />
                </Transition>
            </div>
            <div v-if="loading !== 0 && filterGroups.length" style="height: calc(100% - 128px); overflow: auto;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>
                                日付
                            </th>
                            <th v-for="user in filterGroups" class="p-[10px]">
                                <div>
                                    {{ user.name }}
                                </div>
                                <div class="text-[12px] mt-[10px]">休日設定日数：{{ calculatedHoliday(user) }}</div>
                                <div class="text-[12px] mt-[10px]">所定労働時間：{{ user.should_work_hours / 60 }}時間 ({{ user.work_day_num }}日)</div>
                                <div class="text-[12px] mt-[10px]">勤務時間:{{ user.planned_shift_data.workMinutes / 60 }}時間 ({{ user.planned_shift_data.workDays }}日)</div>
                                <div class="text-[12px] mt-[10px]">有給時間:{{ user.planned_shift_data.paidLeaveMinutes / 60 }}時間</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="shift, index in shiftRecords">
                            <td :class="[getDayClass(index)]">
                                {{ dayFormatter(index) }}
                            </td>
                            <td v-for="user in filterGroups">
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <div>
                                        <span 
                                            :class="getShiftClass(shift[user.id]?.old_shift?.shift_type)"
                                            v-if="shift[user.id]?.old_shift"
                                        >
                                            {{ shift[user.id]?.old_shift?.shift_type.abbreviation }} ➞
                                        </span>
                                        <span :class="getShiftClass(shift[user.id]?.shift_type)">
                                            {{ shift[user.id]?.shift_type?.abbreviation }}
                                        </span>
                                        <span>{{statuses[shift[user.id]?.status_flag]}}</span>
                                    </div>
                                    <div v-if="authorityCheck(user, shift[user.id]) && shift[user.id]?.status_flag !== 1" class="authority-buttons">
                                        <CommandButton 
                                            customClass="custom-padding" 
                                            v-if="shift[user.id]?.status_flag == 2" 
                                            :buttons="[
                                                {title: '承認', action:() => shiftApprove(shift[user.id], 3)}, 
                                                {title: '差戻', action:() => shiftApprove(shift[user.id])}
                                            ]" 
                                        />
                                        <CommandButton 
                                            customClass="custom-padding" 
                                            v-else-if="shift[user.id]?.status_flag == 3" 
                                            :buttons="[{title: '取消', action:() => shiftApprove(shift[user.id], 2)}]" 
                                        />
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else-if="loading != 0" style="height: calc(100% - 128px); display: flex; align-items: center; justify-content: center;">
                メンバーを選択してください。
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import CommandButton from '../Global/CommandButton.vue';
import { useAuthUserStore } from '@/store/auth';
import { useMenuStore } from '@/store/menu';
import { getShiftWithWorkGroup } from '../../utils/workApi';
import WorkMembers from './WorkMembers.vue';
import { useBadgeStore } from '@/store/badge';
import { DateTime } from 'luxon';
import MonthPickerNew from '../Global/MonthPickerNew.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useDashboardStore } from '@/store/dashboard';
import { usePublicHolidayStore } from '@/store/publicHoliday';
    const props = defineProps([
        'selectedYear',
        'selectedMonth',
        'workGroups',
        'usersCheckArray'
    ])
    const emit = defineEmits([
        'closeModal'
    ])
    const nextMonthOrCurrent = computed(() => {
        const now = DateTime.now()
        const selectedDate = DateTime.fromObject({year: props.selectedYear, month: props.selectedMonth, day: now.day})
        if(selectedDate.day >= 25 && props.selectedMonth == selectedDate.month){
            
            const updatedDate = selectedDate.plus({ months: 1 });
            return { year: updatedDate.year, month: updatedDate.month }
        } 
        return { year: selectedDate.year, month: selectedDate.month }
    })
    const menu = useMenuStore()
    const approveYear = ref(nextMonthOrCurrent.value.year)
    const approveMonth = ref(nextMonthOrCurrent.value.month)
    const shiftRecords = ref([])
    const auth = useAuthUserStore()
    const workUsers = ref([])
    const workGroups = ref([])
    const loading = ref(0)
    const badge = useBadgeStore()
    const checkedUsers = ref([])
    
    const statuses = ['', '', ' : 申請中', ' : 承認済']
    const api = useApi()
    const { ask, ping } = useDialog()
    const { getBatchDashboardData } = useDashboardStore()
    const publicHolidayStore = usePublicHolidayStore()
    onMounted(async() => {
        publicHolidayStore.ensureLoaded()
        console.log('usersCheckArray', props.usersCheckArray)
        await fetchWorkGroups()
        const exist = workUsers.value.filter(ob => props.usersCheckArray.includes(ob.id))
        checkedUsers.value = exist.map(ob => ob.id)
    })

    const yearlyHolidays = computed(() => {
        return publicHolidayStore.between(new Date(props.selectedYear + '-01-01'), new Date(props.selectedYear + '-12-31'))
    })

    const filterGroups = computed(() => {
        return workUsers.value.filter(user => checkedUsers.value.find(id => id == user.id))
    })
    
    const getDayClass = (date) => {
        const day = DateTime.fromSQL(date).day
        return {
            'shift-saturday': day === 6,
            'shift-sunday': day === 0,
            'shift-everyholiday' : holiday(date),
            'today' : date === props.currentDay
        }
    }
    const getShiftClass = (shift) => {
        return shift && [0,5,14,15,16,3].includes(shift?.id) ? 'shift-sunday' : ''
    }
    const holiday = (day) => {
        const dayInstance = DateTime.fromSQL(day)

        return yearlyHolidays.value.find(h => DateTime.fromJSDate(h.date).hasSame(dayInstance, 'day'));
    }
    const dayFormatter = (value) => {
        if(value){
            const date = DateTime.fromSQL(value).toFormat('M / d (ccc)')
            return date
        }
    }
    const authorityCheck = (user, shift) => {
        return (auth.activeUser.work_authority > user?.work_authority || [608, 610].includes(auth.activeUser.id)) && shift
    }
    const fetchWorkGroups = async() => {
        const yearMonth = DateTime.fromObject({year: approveYear.value, month: approveMonth.value}).toFormat('yyyy-MM')
        try {
            const data = await getShiftWithWorkGroup(yearMonth, checkedUsers.value)
            workUsers.value = data.work_users
            shiftRecords.value = data.shift_records
            workGroups.value = data.work_groups
        } catch (e) {
            ping(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            loading.value ++    
        }
    }
    
    const approveAll = async() => {
        if(!checkedUsers.value || !checkedUsers.value.length){
            ping('メンバーを選択してください。')
            return
        }
        const answer = await ask('選択中メンバー全員の勤怠予定を纏めて承認します。<br>よろしいですか。')
        if(!answer.value) return
        const userIds = checkedUsers.value
        
        const yearMonth = DateTime.fromObject({year: approveYear.value, month: approveMonth.value}).toFormat('yyyy-MM')

        await api.patch('/shift_approve_all', {user_ids: userIds, year_month: yearMonth}, {
            toast: '承認しました。',
        })
        getBatchDashboardData(['timesheet']) 

        fetchWorkGroups()
        
    }
    const shiftApprove = async(shift, status) => {
        if(!status){
            const answer = await ask(`${shift?.shift_day}の勤怠予定を差戻します。よろしいでしょうか。`)
            if(!answer.value) return
        }
        const shiftId = shift?.id
        await api.patch('/shift_approve', {shift_id: shiftId, status: status}, {
            toast: status == 3 ? '承認しました。' : status == 2 ? '承認取消しました。' : '差戻しました。',
        })
        getBatchDashboardData(['timesheet'])

        fetchWorkGroups()
   
    }
    const setDate = (date) => {
        approveMonth.value = date.month
        approveYear.value = date.year
        loading.value = 0
        fetchWorkGroups()
    }
    const calculatedHoliday = (user) => {
        if(user.holiday_shifts){
            const minutesPerDay = user.work_time_day
            const totalMinutes = user.holiday_shifts
            if (!totalMinutes || !minutesPerDay) return '0日';

            const totalDays = Math.floor(totalMinutes / minutesPerDay);
            const remainingMinutes = totalMinutes % minutesPerDay;
            const remainingHours = Math.floor(remainingMinutes / 60);

            let result = `${totalDays}日`;
            if (remainingHours > 0) result += `${remainingHours}時間`;

            return result;
        }
        return '0日';
    }
</script>
<style scoped lang="scss">
    
    .overstyle{
        width: fit-content;
        overflow: hidden;
        max-width: 90%;
    }
    table{
        font-size: 12px;
        background: var(--background-color);
        border-collapse: separate;
        border-spacing: 0;
        color: var(--primary-color);
        thead{
            text-align: center;
            width: 100px;
            background-color: #606060;
            font-size: 12px;
            color: #fff;
            z-index: 2;
            white-space: nowrap;
            height: 40px;
            position: sticky;
            top: 0;
            th{
                border-right: 1px solid var(--calendarBorder);
                border-left: none;
                border-top: none;
                text-align: center;
                font-weight: 400;
                vertical-align: middle;
            }
            th:first-child{
                border-left:1px solid var(--calendarBorder);
                position: sticky;
                left: 0;
                background-color: #606060;
            }
            
        }
        tbody{
            tr{
                td{
                    border-bottom: 1px solid var(--calendarBorder);
                    border-right: 1px solid var(--calendarBorder);
                    vertical-align: middle;
                    text-align: center;
                    box-sizing: border-box;
                    padding: 10px;
                    white-space: nowrap;
                }
                td:first-child{
                    border-left:1px solid var(--calendarBorder);
                    position: sticky;
                    left: 0;
                    background-color: var(--background-color);
                    z-index: 1;
                }
            }
        }
    }
    .user-wrapper{
        display: flex;
        margin: 10px 10px 10px 0;
        font-size: 13px;
        height: fit-content;
        align-items: center;
        border: solid thin var(--calendarBorder);
        padding: 5px;
        background-color: var(--background-color);
        color: var(--primary-color);
        cursor: pointer;
    }
    .user-wrapper input[type="radio"] {
        display: none;
    }

    .user-wrapper input[type="radio"] + label {  
        padding: 5px;
        cursor: pointer;
    }

    .selected {
        background-color: var(--bg2);
    }
    .authority-buttons {
        display: flex; 
        justify-content: center; 
        gap: 10px; 
        align-items: center;
        font-size: 12px;
    }
    ::-webkit-scrollbar {
        height: 4px;
    }
    @media (max-width: 959px) {
        .overstyle{
            width: 100%;
        }
    }
</style>