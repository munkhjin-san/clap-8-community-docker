<template>
    <BaseLayout
        :title="data.title" 
        :count="actionCount"
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
        :class="{'pulse-border' : data.data.pendingTimesheets.length > 0 && !fullscreen}"
    >
        <template #icon>
            <svg class="side-app-icon mr-1" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" style="width: auto; height: 17px;">
                <path d="M30.827 10.021c-0.79-1.951-1.963-3.748-3.442-5.253s-3.264-2.716-5.214-3.531c-1.963-0.816-4.080-1.237-6.183-1.237-2.116 0.013-4.233 0.433-6.183 1.249s-3.723 2.040-5.202 3.544c-1.479 1.504-2.652 3.289-3.442 5.24-0.778 1.951-1.173 4.054-1.16 6.132 0.013 2.091 0.433 4.182 1.237 6.107 0.816 1.925 1.989 3.697 3.48 5.151s3.264 2.626 5.189 3.391c1.925 0.778 4.207 1.147 6.069 1.147 0.956 0 2.065-0.115 3.085-0.306s2.014-0.484 2.983-0.867c1.925-0.765 3.697-1.925 5.189-3.378s2.69-3.213 3.493-5.138c0.816-1.925 1.249-4.016 1.262-6.107 0.025-2.091-0.37-4.194-1.16-6.145zM28.367 21.304c-0.65 1.632-1.645 3.123-2.869 4.386s-2.716 2.282-4.335 2.983-3.57 1.071-5.176 1.071-3.544-0.382-5.163-1.084c-1.619-0.688-3.111-1.708-4.335-2.971s-2.218-2.754-2.881-4.373c-0.663-1.619-1.007-3.378-0.994-5.138s0.382-3.493 1.071-5.1c0.688-1.606 1.696-3.060 2.932-4.284 2.486-2.435 5.916-3.837 9.383-3.812 3.468-0.013 6.884 1.39 9.358 3.825 1.237 1.211 2.244 2.677 2.92 4.284 0.688 1.594 1.045 3.34 1.058 5.087s-0.319 3.493-0.969 5.125z"></path><path d="M17.594 16.064c-0.026-0.038-0.064-0.064-0.089-0.102l-0.79-9.74c-0.026-0.357-0.306-0.65-0.676-0.676-0.408-0.038-0.765 0.268-0.803 0.676l-0.841 10.441c0 0.076-0.013 0.178 0 0.255 0.013 0.102 0.025 0.191 0.051 0.293 0.013 0.51 0.242 1.020 0.688 1.364l6.489 5.049c0.293 0.229 0.727 0.242 1.033-0.013 0.357-0.28 0.408-0.803 0.128-1.16l-5.189-6.387z"></path>
            </svg>
        </template>
        <div class="m-5">
            <div v-if="data.data.pendingAttendance">
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                                勤怠確定
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <p>{{ data.data.pendingAttendance?.date_year_month }}の勤怠を確定してください。</p>
                                <div class="mt-3 text-right">
                                    <router-link :to="{ name: 'timesheet', query: { user_id: data.data.pendingAttendance?.user_id, attendanceMonth: data.data.pendingAttendance?.date_year_month } }">対応</router-link>
                                </div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div v-if="data.data.pendingPlannedLeaves && data.data.pendingPlannedLeaves.length">
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                                計画有給
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div v-for="item in data.data.pendingPlannedLeaves">
                                    <WorkMessage
                                        v-if="item"
                                        :item="item"
                                    />
                                </div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div class="text-[13px] p-2 bg-[var(--bg3)] rounded mt-3 w-fit" v-if="dashboardStore.annualLeaveData.remaining_days">
                有給残日数: {{ dashboardStore.annualLeaveData.remaining_days }}日
            </div>
            <div v-if="dashboardStore.annualLeaveData.planned_leaves_last_year.length || dashboardStore.annualLeaveData.planned_leaves_this_year.length" class="mt-3">
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem hide-actions static :tile="true" class="rm-p" v-if="dashboardStore.annualLeaveData.planned_leaves_last_year.length">
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                昨年計画有給（{{ dashboardStore.annualLeaveData.planned_leaves_last_year.length }}件）
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div class="flex flex-wrap gap-3">
                                    <div v-for="item in dashboardStore.annualLeaveData.planned_leaves_last_year" class="text-[gray] text-[12px] px-2 rounded-full bg-[var(--background-color)] flex justify-between ">
                                        <p>{{DateTime.fromISO(item.shift_day.toString()).toFormat('y / M / d', { locale: 'ja' })}}</p>
                                    </div>
                                </div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                    <ExpansionPanelItem hide-actions static :tile="true" class="rm-p" v-if="dashboardStore.annualLeaveData.planned_leaves_this_year.length">
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                今年計画有給（{{ dashboardStore.annualLeaveData.planned_leaves_this_year.length }}件）
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div class="flex flex-wrap gap-3">
                                    <div v-for="item in dashboardStore.annualLeaveData.planned_leaves_this_year" class="text-[gray] text-[12px] px-2 rounded-full bg-[var(--background-color)] flex justify-between ">
                                        <p>{{DateTime.fromISO(item.shift_day.toString()).toFormat('y / M / d', { locale: 'ja' })}}</p>
                                    </div>
                                </div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div class="mt-3" v-if="data.data.departuresReportUsers && data.data.departuresReportUsers.length">
                <p class="text-sm mb-3">
                    <span class="text-[11px] rounded-full bg-[var(--bg3)] px-1 mr-1 py-0.5">PM</span>
                    出発報告状況
                </p>
                <div class="mx-3 mb-3 overflow-hidden w-fit flex flex-col gap-[10px] bg-[var(--background-color)]">
                    <div v-for="item in data.data.departuresReportUsers">
                        <div class="flex gap-[15px] text-[13px] items-center">
                            <UserPanel disable-instant with-name size="25" :user="item"/>
                            <div class="text-[gray]">{{ `${item.shift_records && item.shift_records.length && item.shift_records[0].departure_report ? DateTime.fromSQL(item.shift_records[0].departure_report).toFormat('M / d HH:mm') : '未報告'}` }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="data.data.pendingTimesheets.length" class="mt-3">
                <p class="my-2 text-sm overflow-hidden whitespace-nowrap text-ellipsis">
                    <span class="text-[11px] rounded-full bg-[var(--bg3)] px-1 py-0.5">PM</span>
                    承認依頼
                </p>
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                        v-for="item in data.data.pendingTimesheets"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                                <div class="flex items-center">
                                    <UserPanel disable-instant with-name size="25" :user="item.user"/>
                                    <span class="text-[gray] text-[12px]">（{{ 
                                        (item?.shift ? item.shift.map(s => s.count).reduce((a, b) => a + b, 0) : 0) + 
                                        (item?.timecard ? item.timecard.map(t => t.count).reduce((a, b) => a + b, 0) : 0) + 
                                        (item?.overtime ? item.overtime : 0)
                                    }}件）</span>
                                </div>
                                
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div class="flex flex-col gap-1">
                                    <template v-if="item.timecard && item.timecard.length">
                                        <div v-for="(timecard) in item.timecard" class="text-[12px]">
                                            日報申請 : {{ timecard.month }}月分<span class="text-[gray] text-[12px]">（{{timecard.count}}件）</span>
                                        </div>
                                    </template>
                                    <div v-if="item.overtime">残業申請 : <span class="text-[gray] text-[12px]">（{{ item.overtime }}件）</span></div>
                                    <template v-if="item.shift && item.shift.length">
                                        <div v-for="(shift) in item.shift" class="text-[12px]">
                                            勤怠予定申請 : {{ shift.month }}月分<span class="text-[gray] text-[12px]">（{{shift.count}}件）</span>
                                        </div>                                        
                                    </template>
                                    <div class="ml-auto">
                                        <router-link :to="{name: 'timesheet', query: {user_id: item.user.id}}" class="jump-link mt-2" @click="">対応する</router-link>
                                    </div>
                                    
                                </div>                                
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div v-if="data.data.pendingPlannedLeaveChangeRequests.length" class="mt-3">
                <p class="my-2 text-sm overflow-hidden whitespace-nowrap text-ellipsis">
                    <span class="text-[11px] rounded-full bg-[var(--bg3)] px-1 py-0.5">PM</span>
                    計画有給変更申請
                </p>
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                        v-for="item in data.data.pendingPlannedLeaveChangeRequests"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                <div class="flex items-center truncate">
                                    <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                                    <div class="flex items-center truncate">
                                        <UserPanel v-if="item.user" disable-instant with-name size="25" :user="item.user"/>
                                    </div>
                                </div>
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div class="flex flex-col gap-1">
                                    <div class="text-[12px]">
                                        変更前 : {{ DateTime.fromISO(item.original_date.toString()).toFormat('y / M / d', { locale: 'ja' }) }}
                                    </div>
                                    <div class="text-[12px]">
                                        変更後 : {{ DateTime.fromISO(item.requested_date.toString()).toFormat('y / M / d', { locale: 'ja' }) }}
                                    </div>
                                    <div>
                                        <div class="text-[12px]">
                                            理由 : {{ item.reason || '未入力' }}
                                        </div>
                                    </div>
                                    <router-link :to="{name: 'timesheet', query: {action: 'shift_confirm', user_id: item.user?.id, tab:'planned_leave'}}" class="jump-link mt-2 ml-auto" @click="">対応する</router-link>
                                </div>                                
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div v-if="autoApprovedTimesheets.length" class="mt-3">
                <p class="my-2 text-sm overflow-hidden whitespace-nowrap text-ellipsis">
                    <span class="text-[11px] rounded-full bg-[var(--bg3)] px-1 py-0.5">PM</span>
                    自動承認
                </p>
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                        v-for="item in autoApprovedTimesheets"
                        :key="`auto-approved-${item.user.id}`"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded" @click="markAutoApprovedAsRead(item)">
                                <div v-if="!item.read" class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5"></div>
                                <div class="flex items-center">
                                    <UserPanel disable-instant with-name size="25" :user="item.user"/>
                                    <span class="text-[gray] text-[12px]">（{{ item.records.length }}件）</span>
                                </div>
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div class="flex flex-col gap-2">
                                    <div
                                        v-for="record in item.records"
                                        :key="record.segment_id"
                                        class="auto-approved-row"
                                    >
                                        <div class="auto-approved-row__main">
                                            <div class="auto-approved-row__date">
                                                <span>{{ formatAutoApprovedDay(record.day) }}</span>
                                                <WeatherIcon v-if="record.weather !== null" :which="record.weather" size="15"/>
                                            </div>
                                            <div class="auto-approved-row__project">
                                                {{ record.project_name }}
                                            </div>
                                            <div class="auto-approved-row__time">
                                                {{ formatTime(record.start_time) }} - {{ formatTime(record.end_time) }}
                                            </div>
                                        </div>
                                        <div v-if="record.comment" class="auto-approved-row__comment">
                                            {{ record.comment }}
                                        </div>
                                    </div>
                                </div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div v-if="nothingTodo">
                <div class="text-sm text-[gray] mb-3 text-center">
                    対応事項はありません。
                </div>
            </div>
        </div>
    </BaseLayout>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import BaseLayout from './BaseLayout.vue';
import { DateTime } from 'luxon';
import { useRouter, useRoute } from 'vue-router';
import WorkMessage from '@/components/Work/WorkMessage.vue';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import { storeToRefs } from 'pinia';
import type { AutoApprovedTimesheetData, DashboardTimesheetCard } from '@/interface/dashboard';
import { useDashboardStore } from '@/store/dashboard';
import { computed, onMounted, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import WeatherIcon from '@/components/Global/WeatherIcon.vue';
import { useApi } from '@/composables/api';

const props = defineProps<{
    data: DashboardTimesheetCard
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()
const router = useRouter()
const route = useRoute()
const dashboardStore = useDashboardStore();
const auth = useAuthUserStore();
const api = useApi();
const markingAutoApprovedUsers = ref<number[]>([])
const autoApprovedTimesheets = computed(() => props.data.data.autoApprovedTimesheets ?? [])
const autoApprovedCount = computed(() => {
    return autoApprovedTimesheets.value.reduce((total, item) => total + (item.read ? 0 : item.records.length), 0)
})
const departureReportCount = computed(() => {
    return props.data.data.departuresReportUsers.filter((user) => {
        return user.shift_records?.some((shift: any) => !shift.departure_report)
    }).length
})

const actionCount = computed(() => {
    return (
        (props.data.data.pendingAttendance ? 1 : 0) +
        props.data.data.pendingPlannedLeaves.length +
        props.data.data.pendingPlannedLeaveChangeRequests.length +
        departureReportCount.value +
        props.data.data.pendingTimesheets.length +
        autoApprovedCount.value
    )
})
const formatAutoApprovedDay = (day: string) => {
    return DateTime.fromISO(day).setLocale('ja').toFormat('M / d (ccc)')
}
const formatTime = (time: string | null) => {
    return time ? time.slice(0, 5) : ''
}
const markAutoApprovedAsRead = async (item: AutoApprovedTimesheetData) => {
    if (item.read || markingAutoApprovedUsers.value.includes(item.user.id)) return;

    const segmentIds = item.records.map(record => record.segment_id)
    if (!segmentIds.length) return;

    try {
        markingAutoApprovedUsers.value.push(item.user.id)
        const data = await api.post('/dashboard_timesheet_auto_approved_read', { segment_ids: segmentIds }, { silent: true })
        if (data) {
            item.read = true
        }
    } catch (error) {
        console.error('Failed to mark auto-approved timesheets as read:', error)
    } finally {
        markingAutoApprovedUsers.value = markingAutoApprovedUsers.value.filter(userId => userId !== item.user.id)
    }
}
onMounted(() => {
    if(auth?.user?.user_code){
        if(!dashboardStore.annualLeaveData.fetched && !dashboardStore.annualLeaveData.fetching){
            dashboardStore.getAnnualLeaveData();
        }
    }
})
const nothingTodo = computed(() => {
    return !props.data.data.pendingAttendance && !props.data.data.pendingTimesheets.length && !autoApprovedTimesheets.value.length && !props.data.data.departuresReportUsers.length && !props.data.data.pendingPlannedLeaves.length && !props.data.data.pendingPlannedLeaveChangeRequests.length && !dashboardStore.annualLeaveData.planned_leaves_this_year.length && !dashboardStore.annualLeaveData.planned_leaves_last_year.length
})
defineExpose({
    cardType: props.data.type,
})

</script>
<style scoped>

.highlight-border {
  animation: highlight-border 1s ease-in-out 5;
}

.auto-approved-row {
    border-bottom: 1px solid var(--border-color);
    padding: 6px 0;
    font-size: 12px;
}

.auto-approved-row:last-child {
    border-bottom: 0;
}

.auto-approved-row__main {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 8px;
}

.auto-approved-row__date {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.auto-approved-row__project {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.auto-approved-row__time {
    color: gray;
    white-space: nowrap;
}

.auto-approved-row__comment {
    color: gray;
    line-height: 1.5;
    margin-top: 4px;
    white-space: pre-wrap;
}

@keyframes highlight-border {
  0%, 100% {
    border-color: transparent;
  }

  50% {
    border-color: tomato;
  }
}
</style>
