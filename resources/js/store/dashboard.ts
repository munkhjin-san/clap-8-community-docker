import { Asset } from "@/interface/assetInterface";
import { CalendarRecord } from "@/interface/calendarInterface";
import { CustomForm } from "@/interface/customFormInterface";
import { pendingTimesheedData, UserWithShift } from "@/interface/dashboard";
import { Message, Task, User } from "@/interface/globalInterface";
import { Post } from "@/interface/postInterface";
import { Evaluation } from "@/interface/projectInterface";
import { Shift, WorkItem } from "@/interface/workInterface";
import axios from "axios";
import { DateTime } from "luxon";
import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useDashboardStore = defineStore('dashboardStore', () => {
    const collection = ref({
        pendingEvaluations: [] as Evaluation[],
        assets: {
            in_use: [] as Asset[],
            waiting_approval: [] as Asset[],
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

        }
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
        const ASSET_CONFIRM_DEADLINE_MONTH = 4
        const departuresCount = collection.value.timesheet.departuresReportUsers.filter(user => user.shift_records.some(shift => !shift.departure_report)).length
        const inconfirmedAssets = thisMonth >= ASSET_CONFIRM_DEADLINE_MONTH ? collection.value.assets.in_use.filter(asset => !asset.confirm_logs.length).length : 0
        const total = departuresCount + 
        inconfirmedAssets + collection.value.assets.waiting_approval.length + 
        collection.value.challenges.length + collection.value.forms.length + 
        collection.value.pendingGoalsUserForHR.length + 
        collection.value.schedules.temp_schedules.length + collection.value.pendingDailyReports.length +
        collection.value.mustCheckMessages.length + collection.value.mustSignMessages.length + 
        collection.value.unfinishedTasks.length + collection.value.untouchedTasks.length +
        collection.value.personnelEvaluation.pendingEvaluations.length + 
        collection.value.timesheet.pendingTimesheets.length
        console.log('total', total)
        return total
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

    return {
        collection,
        getBatchDashboardData,
        badgeCount,
        annualLeaveData,
        getAnnualLeaveData
    }
});