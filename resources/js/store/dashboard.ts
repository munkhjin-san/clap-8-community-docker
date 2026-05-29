import { Asset } from "@/interface/assetInterface";
import { CalendarRecord } from "@/interface/calendarInterface";
import { CustomForm } from "@/interface/customFormInterface";
import { pendingTimesheedData, UserWithShift } from "@/interface/dashboard";
import { Message, Task, User } from "@/interface/globalInterface";
import { Incident } from "@/interface/incident";
import { Post } from "@/interface/postInterface";
import { Evaluation, Project, ProjectAssignRecord } from "@/interface/projectInterface";
import { Shift, WorkItem } from "@/interface/workInterface";
import axios from "axios";
import { DateTime } from "luxon";
import { defineStore } from "pinia";
import { computed, ref } from "vue";
import { useDashboardGoalsStore } from "./dashboardGoals";

export const useDashboardStore = defineStore('dashboardStore', () => {
    const collection = ref({
        pendingEvaluations: [] as Evaluation[],
        assets: {
            in_use: [] as Asset[],
            waiting_approval: [] as Asset[],
        },
        incidents: {
            attention: [] as Incident[],
        },
        overdueGoals: [] as any[], //data ignored just for layout purposes
        challenges: [] as Post[],
        forms: [] as CustomForm[],
        
        pendingApprovalTasks: [] as Task[],
        pendingGoalsUserForHR: [] as User[],
        remindedMessages: [] as Message[],
        schedules: {
            temp_schedules: [] as CalendarRecord[],
            this_week_schedules: [] as CalendarRecord[],
            next_week_schedules: [] as CalendarRecord[],
        },
        pendingDailyReports: [] as any[],
        mustCheckMessages: [] as Message[],
        mustSignMessages: [] as Message[],
        unfinishedTasks: [] as Task[],
        untouchedTasks: [] as Task[],      
        personnelEvaluation: {
            pendingEvaluations: [] as any[],
        },  
        timesheet: {
            pendingTimesheets: [] as pendingTimesheedData[],
            departuresReportUsers: [] as UserWithShift[],
            pendingPlannedLeaves: [] as any[],
            pendingAttendance: null as any,
        },
        projects: {
            officer_approval_waiting: [] as Project[],
            assign_approval_waiting: [] as ProjectAssignRecord[],
        },
        notices: [] as any[],
        systemUpdates: [] as number[],
    })

    const annualLeaveData = ref<{
        remaining_days: number;
        planned_leaves_this_year: Shift[];
        planned_leaves_last_year: Shift[];
        refreshed_at: string | null;
        fetched: boolean;
        fetching: boolean;
    }>({
        remaining_days: 0,
        planned_leaves_this_year: [],
        planned_leaves_last_year: [],
        refreshed_at: null,
        fetched: false,
        fetching: false,
    })
    const lastUpdated = ref<DateTime | null>(null);
    const dashboardPostReminderTypes = [
        'nice_follow_up',
        'challenge_relay_received',
        'challenge_relay_returned',
    ]
    const dashboardPostBadgeCount = computed(() => {
        return collection.value.challenges.filter((challenge) => {
            const attentionType = (challenge as Post & { attention_type?: string }).attention_type
            return !dashboardPostReminderTypes.includes(attentionType ?? '')
        }).length
    })

    const getBatchDashboardData = async (requestedData?: string[]) => {
        try {
            const indexes = requestedData && requestedData.length > 0 ? requestedData : Object.keys(collection.value);
            const res = await axios.get('/dashboard_data', {
                params: {
                    requestedData: indexes,
                }
            });

            // Special-case: backend currently returns BOTH overdueGoals and overdueGraveCount
            // inside the overdueGraveCount payload.
            const overduePayload = (res.data as any)?.overdueGraveCount;
            if (overduePayload && typeof overduePayload === 'object') {
                if (Array.isArray(overduePayload.overdueGoals)) {
                    (collection.value as any).overdueGoals = overduePayload.overdueGoals;
                }
                if (typeof overduePayload.overdueGraveCount === 'number') {
                    (collection.value as any).overdueGraveCount = overduePayload.overdueGraveCount;
                }
            }

            for (const key in collection.value) {
                if (res.data.hasOwnProperty(key)) {
                    (collection.value as any)[key] = res.data[key];
                }
            }

            lastUpdated.value = DateTime.now();
            
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
        }
    }

    const badgeCount = computed(() => {
        const thisMonth = DateTime.now().month
        const ASSET_CONFIRM_DEADLINE_MONTH = 3
        const departuresCount = collection.value.timesheet.departuresReportUsers.filter(user => user.shift_records.some(shift => !shift.departure_report)).length
        const normalCheckMessages = collection.value.mustCheckMessages.filter(message => !message.check_request_deadline || DateTime.fromSQL(message.check_request_deadline) > DateTime.now()).length
        // const inconfirmedAssets = thisMonth >= ASSET_CONFIRM_DEADLINE_MONTH ? collection.value.assets.in_use.filter(asset => !asset.confirm_logs.length).length : 0
        const inconfirmedAssets = 0
        const total = departuresCount + 
        inconfirmedAssets + collection.value.assets.waiting_approval.length + 
        dashboardPostBadgeCount.value + collection.value.forms.length + 
        collection.value.pendingGoalsUserForHR.length + 
        collection.value.schedules.temp_schedules.length + collection.value.pendingDailyReports.length +
        normalCheckMessages + collection.value.mustSignMessages.length + 
        collection.value.unfinishedTasks.length + collection.value.untouchedTasks.length +
        collection.value.personnelEvaluation.pendingEvaluations.length + 
        collection.value.notices.length + collection.value.projects.assign_approval_waiting.length + 
        collection.value.projects.officer_approval_waiting.length +
        collection.value.incidents.attention.length + collection.value.systemUpdates.length + 
        collection.value.systemUpdates.length

        return total
    })
    const goalsStore = useDashboardGoalsStore()
    const pulseBadgeCount = computed(() => {
        const overdueGoals = goalsStore.myGoals.filter(goalsStore.isGoalOverWeek)
        const overdueCheckMessages = collection.value.mustCheckMessages.filter(message => message.check_request_deadline && DateTime.fromSQL(message.check_request_deadline) < DateTime.now()).length
        const overdueApprovalGoalsCount =
            goalsStore.pendingMembers.flatMap(user => user.outcome_goals ?? []).filter(goalsStore.isGoalOverWeek).length +
            goalsStore.managersGoals.flatMap(user => user.outcome_goals ?? []).filter(goalsStore.isGoalOverWeek).length
        const pendingTimesheetsCount = collection.value.timesheet.pendingTimesheets.length
        const needed = (goalsStore.requiredGoalData?.this_span?.needed_count || 0) + (goalsStore.requiredGoalData?.previous_span?.needed_count || 0) + (goalsStore.unfinishedPreviousSpanGoals.length ?? 0)
        const pendingAttendanceCount = collection.value.timesheet.pendingAttendance ? 1 : 0
        const incidentsNeedingAttention = collection.value.incidents.attention.length
        return overdueGoals.length + overdueApprovalGoalsCount + needed + pendingAttendanceCount + overdueCheckMessages + pendingTimesheetsCount + incidentsNeedingAttention
    })
    const getAnnualLeaveData = async () => {
        try {
            annualLeaveData.value.fetching = true;
            const res = await axios.get('/annual_leave_data');
            annualLeaveData.value.remaining_days = res.data.remaining_days ?? 0;
            annualLeaveData.value.planned_leaves_this_year = res.data.planned_leaves_this_year ?? [];
            annualLeaveData.value.planned_leaves_last_year = res.data.planned_leaves_last_year ?? [];
            annualLeaveData.value.refreshed_at = DateTime.now().toISO();
            annualLeaveData.value.fetched = true;
        } catch (error) {
            console.error('Error fetching annual leave data:', error);
        } finally {
            annualLeaveData.value.fetching = false;
        }
    }
    const pendingTimeSheets = computed(() => {
        return collection.value.timesheet.pendingTimesheets.some(user => user.has_pending_timecards)
    })
    return {
        collection,
        getBatchDashboardData,
        badgeCount,
        annualLeaveData,
        getAnnualLeaveData,
        pulseBadgeCount,
        pendingTimeSheets
    }
});
