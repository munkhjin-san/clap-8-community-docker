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
            <div class="approval-toolbar">
                <button style="margin: unset;" class="work-button" @click.stop="menu.setMenu( { id: 199, name: 'workMemberSelector'})">メンバー</button>
                <div class="approval-month-control">
                    <span>月度</span>
                    <MonthPickerNew
                        v-model:month="approveMonth"
                        v-model:year="approveYear"
                        :right="'auto'"
                        @setDate="setDate"
                    />
                </div>
                <div class="approval-summary">
                    <span>選択 {{ filterGroups.length }}名</span>
                    <span>承認対象 {{ approvablePendingCount }}件</span>
                    <span v-if="outOfScopePendingCount">担当外 {{ outOfScopePendingCount }}件</span>
                    <span v-if="approvableProjectSummary">{{ approvableProjectSummary }}</span>
                </div>
                <button
                    style="margin: unset;"
                    :class="['work-button', 'approve-all-button', { 'is-disabled': approvablePendingCount === 0 }]"
                    @click="approveAll"
                >
                    {{ bulkApproveLabel }}
                </button>
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
            <div v-if="loading !== 0 && filterGroups.length" class="approval-table-wrapper">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>
                                日付
                            </th>
                            <th v-for="user in filterGroups" :key="user.id" class="p-[10px]">
                                <div class="approval-user-card">
                                    <div class="approval-user-name">{{ user.name }}</div>
                                    <div class="approval-user-stats">
                                        <span>休日 {{ calculatedHoliday(user) }}</span>
                                        <span>所定 {{ user.should_work_hours / 60 }}時間 / {{ user.work_day_num }}日</span>
                                        <span>勤務 {{ user.planned_shift_data.workMinutes / 60 }}時間 / {{ user.planned_shift_data.workDays }}日</span>
                                        <span>有給 {{ user.planned_shift_data.paidLeaveMinutes / 60 }}時間</span>
                                        <span>出退勤 {{ DateTime.fromISO(user.planned_shift_data.startTime).toFormat('HH:mm') }} ~ {{ DateTime.fromISO(user.planned_shift_data.endTime).toFormat('HH:mm') }}</span>
                                    </div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="shift, index in shiftRecords" :key="index">
                            <td :class="[getDayClass(index)]">
                                {{ dayFormatter(index) }}
                            </td>
                            <td v-for="user in filterGroups" :key="user.id" :class="approvalCellClass(user, shift[user.id])">
                                <div v-if="shift[user.id]" class="approval-cell">
                                    <div class="approval-shift-line">
                                        <span 
                                            :class="['approval-shift-type', getShiftClass(shift[user.id]?.shift_type)]"
                                        >
                                            {{ shift[user.id]?.shift_type?.abbreviation }}
                                        </span>
                                        <span :class="['approval-status', statusClass(shift[user.id]?.status_flag)]">
                                            {{ statusLabel(shift[user.id]?.status_flag) }}
                                        </span>
                                    </div>
                                    <div v-if="shiftRequiresProject(shift[user.id])" :class="['approval-project-chip', { 'is-missing': !shift[user.id]?.department }]">
                                        <span>プロジェクト</span>
                                        <strong>{{ projectName(shift[user.id]) }}</strong>
                                    </div>
                                    <div v-else class="approval-non-project">
                                        プロジェクト対象外
                                    </div>
                                    <div class="approval-before" v-if="shift[user.id]?.old_shift">
                                        変更前:
                                        <span :class="getShiftClass(shift[user.id]?.old_shift?.shift_type)">
                                            {{ shift[user.id]?.old_shift?.shift_type.abbreviation }}
                                        </span>
                                        <span v-if="shift[user.id]?.old_shift?.department">
                                            / {{ shift[user.id].old_shift.department.name }}
                                        </span>
                                    </div>
                                    <div class="approval-note" v-if="isSelfShift(shift[user.id])">
                                        本人のため承認不可
                                    </div>
                                    <div class="approval-note" v-else-if="isOutOfScopeShift(user, shift[user.id])">
                                        担当外プロジェクト
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
                                <span v-else class="approval-empty">-</span>
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
    
    const statusLabels = {
        1: '確定済',
        2: '申請中',
        3: '承認済',
    }
    const api = useApi()
    const { ask, ping } = useDialog()
    const { getBatchDashboardData } = useDashboardStore()
    const publicHolidayStore = usePublicHolidayStore()
    onMounted(async() => {
        publicHolidayStore.ensureLoaded()
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
    const managedProjectIds = computed(() => {
        if (auth.isAdmin) return null

        return new Set(
            workGroups.value
                .filter(group => (group.manager ?? []).some(manager => Number(manager.id) === Number(auth.activeUser.id)))
                .map(group => Number(group.id))
        )
    })
    const selectedShiftEntries = computed(() => {
        const entries = []

        Object.values(shiftRecords.value ?? {}).forEach((dayShifts) => {
            filterGroups.value.forEach((user) => {
                const shift = dayShifts?.[user.id]
                if (shift) entries.push({ user, shift })
            })
        })

        return entries
    })
    const approvablePendingCount = computed(() => {
        return selectedShiftEntries.value.filter(({ user, shift }) => {
            return Number(shift.status_flag) === 2 && authorityCheck(user, shift)
        }).length
    })
    const outOfScopePendingCount = computed(() => {
        return selectedShiftEntries.value.filter(({ user, shift }) => {
            return isOutOfScopeShift(user, shift)
        }).length
    })
    const approvableProjectSummary = computed(() => {
        const names = [...new Set(
            selectedShiftEntries.value
                .filter(({ user, shift }) => authorityCheck(user, shift))
                .filter(({ shift }) => shiftRequiresProject(shift))
                .map(({ shift }) => projectName(shift))
                .filter(Boolean)
        )]

        if (!names.length) return ''
        if (names.length <= 2) return names.join(' / ')

        return `${names.slice(0, 2).join(' / ')} 他${names.length - 2}件`
    })
    const bulkApproveLabel = computed(() => {
        return approvablePendingCount.value ? `一括承認（${approvablePendingCount.value}件）` : '一括承認'
    })
    
    const getDayClass = (date) => {
        const day = DateTime.fromSQL(date)
        return {
            'shift-saturday': day.weekday === 6,
            'shift-sunday': day.weekday === 7,
            'shift-everyholiday' : holiday(date),
            // 'today' : day.toISODate() === DateTime.now().toISODate()
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
    const statusLabel = (status) => statusLabels[Number(status)] ?? '-'
    const statusClass = (status) => {
        return {
            'is-pending': Number(status) === 2,
            'is-approved': Number(status) === 3,
            'is-confirmed': Number(status) === 1,
        }
    }
    const projectName = (shift) => shift?.department?.name ?? 'プロジェクト未設定'
    const shiftRequiresProject = (shift) => {
        const shiftType = shift?.shift_type
        if (!shiftType) return false

        return ![0, 18].includes(Number(shiftType.id)) && Number(shiftType.full_day) !== 2
    }
    const isSelfShift = (shift) => {
        return shift && Number(shift.user_id) === Number(auth.activeUser.id)
    }
    const authorityCheck = (user, shift) => {
        if (!shift || isSelfShift(shift)) return false
        if (auth.isAdmin) return true

        return shift.department_id && managedProjectIds.value?.has(Number(shift.department_id))
    }
    const isOutOfScopeShift = (user, shift) => {
        return shiftRequiresProject(shift) && Number(shift.status_flag) === 2 && !authorityCheck(user, shift) && !isSelfShift(shift)
    }
    const approvalCellClass = (user, shift) => {
        return {
            'approval-cell-td': true,
            'is-actionable': authorityCheck(user, shift),
            'is-out-of-scope': isOutOfScopeShift(user, shift),
            'is-self': isSelfShift(shift),
        }
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
        if(approvablePendingCount.value === 0){
            ping('承認できる申請中の勤怠予定がありません。')
            return
        }
        const projectText = approvableProjectSummary.value ? `<br>対象プロジェクト: ${approvableProjectSummary.value}` : ''
        const answer = await ask(`承認対象 ${approvablePendingCount.value}件を一括承認します。${projectText}<br>よろしいですか。`)
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
            const answer = await ask(`${shift?.shift_day} ${projectName(shift)} の勤怠予定を差戻します。よろしいでしょうか。`)
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
        display: flex;
        flex-direction: column;
        width: min(1180px, 95vw);
        height: 85%;
        overflow: hidden;
        max-width: 95%;
    }
    .overstyle > .work-loader {
        inset: 0;
        height: 100%;
        bottom: auto;
        z-index: 20;
    }
    .recordFormTitle,
    .approval-toolbar {
        flex: 0 0 auto;
    }
    .approval-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin: 10px 0 18px;
        position: relative;
        flex-wrap: wrap;
    }
    .approval-summary {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 260px;
        overflow: hidden;
        white-space: nowrap;
        color: var(--primary-color);
        font-size: 12px;
    }
    .approval-summary span {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 2px 8px;
        border: 1px solid var(--calendarBorder);
        border-radius: 3px;
        background: var(--bg3);
    }
    .approval-month-control {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--primary-color);
        font-size: 12px;
        white-space: nowrap;
    }
    .approval-month-control :deep(.monthPicker) {
        width: 128px;
        height: 34px;
        border: 1px solid var(--formBorder);
        border-radius: 4px;
        background: var(--background-color);
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }
    .approval-month-control :deep(.monthPicker:hover) {
        border-color: var(--primary-color);
        background: var(--bg3);
    }
    .approval-month-control :deep(.monthPicker > div:first-child) {
        align-items: center;
        display: flex;
        height: 100%;
        justify-content: center;
        width: 100%;
    }
    .approval-month-control :deep(.monthPicker > div:first-child)::after {
        content: "";
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid currentColor;
        margin-left: 8px;
        opacity: 0.65;
    }
    .approval-month-control :deep(#activateButton) {
        align-items: center;
        display: flex;
        font-size: 13px;
        height: 100%;
    }
    .approval-month-control :deep(.month-grid) {
        right: 0 !important;
    }
    .approve-all-button {
        background-color: tomato;
    }
    .approve-all-button.is-disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .approval-table-wrapper {
        flex: 1 1 auto;
        height: auto;
        min-height: 0;
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }
    .approval-user-card {
        min-width: 180px;
    }
    .approval-user-name {
        font-size: 13px;
        line-height: 1.4;
    }
    .approval-user-stats {
        display: grid;
        gap: 4px;
        margin-top: 8px;
        color: rgba(255, 255, 255, 0.86);
        font-size: 11px;
        line-height: 1.25;
        text-align: left;
    }
    .approval-cell-td {
        min-width: 190px;
    }
    .approval-cell {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        min-width: 170px;
        text-align: left;
    }
    .approval-shift-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .approval-shift-type {
        font-size: 13px;
        line-height: 1.3;
    }
    .approval-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 56px;
        padding: 2px 8px;
        border: 1px solid var(--formBorder);
        border-radius: 3px;
        background: var(--background-color);
        font-size: 11px;
        line-height: 1.4;
        white-space: nowrap;
    }
    .approval-status.is-pending {
        border-color: tomato;
        color: tomato;
    }
    .approval-project-chip {
        display: grid;
        gap: 2px;
        padding: 7px 8px;
        border: 1px solid var(--calendarBorder);
        border-radius: 4px;
        background: var(--bg3);
        line-height: 1.25;
    }
    .approval-project-chip span {
        font-size: 10px;
        opacity: 0.65;
    }
    .approval-project-chip strong {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 12px;
        font-weight: 500;
    }
    .approval-non-project {
        padding: 6px 8px;
        border: 1px dashed var(--calendarBorder);
        border-radius: 4px;
        background: var(--bg3);
        color: var(--primary-color);
        font-size: 11px;
        line-height: 1.3;
        opacity: 0.62;
        text-align: center;
    }
    .approval-before {
        color: var(--primary-color);
        font-size: 11px;
        line-height: 1.3;
        opacity: 0.72;
    }
    .approval-note {
        color: var(--primary-color);
        font-size: 11px;
        line-height: 1.3;
        opacity: 0.62;
        text-align: center;
    }
    .approval-empty {
        color: var(--primary-color);
        opacity: 0.32;
    }
    .is-out-of-scope .approval-project-chip,
    .is-self .approval-project-chip {
        opacity: 0.72;
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
            max-width: 100%;
            height: 100%;
        }
        .approval-toolbar {
            gap: 10px;
            margin: 8px 0 12px;
        }
        .approval-summary {
            min-width: 100%;
            flex-wrap: wrap;
            white-space: normal;
        }
        .approval-table-wrapper {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            width: 100%;
        }
        table {
            min-width: max-content;
        }
        table tbody tr td {
            padding: 8px;
        }
        .approval-user-card {
            min-width: 160px;
        }
        .approval-cell {
            min-width: 150px;
        }
    }
</style>
