import { Asset } from "@/interface/assetInterface";
import { CalendarRecord } from "@/interface/calendarInterface";
import { CustomForm } from "@/interface/customFormInterface";
import { Message, Task, User, UserWithGoals } from "@/interface/globalInterface";
import { Post } from "@/interface/postInterface";
import { Evaluation } from "@/interface/projectInterface";
import { WorkItem } from "@/interface/workInterface";
import axios from "axios";
import { DateTime } from "luxon";
import { defineStore } from "pinia";
import { ref } from "vue";

export const useDashboardStore = defineStore('dashboardStore', () => {
    const collection = ref({
        pendingEvaluations: [] as Evaluation[],
        overdueGraveCount: 0,
        assets: [] as Asset[],
        challenges: [] as Post[],
        departuresReportUsers: [] as User[],
        forms: [] as CustomForm[],
        requiredGoalData: {
            user: {} as User,
            needs: 0 as number,
            year: 0 as number,
            half: '' as string,
        },
        pendingApprovalTasks: [] as Task[],
        overdueGoals: [] as UserWithGoals[],
        pendingPlannedLeaves: [] as WorkItem[],
        pendingGoalsUserForHR: [] as User[],
        remindedMessages: [] as Message[],
        tempSchedules: [] as CalendarRecord[],
        pendingDailyReports: [] as any[],
        mustCheckMessages: [] as Message[],
        mustSignMessages: [] as Message[],
        unfinishedTasks: [] as Task[],
        untouchedTasks: [] as Task[],        
    })
    const lastUpdated = ref<DateTime | null>(null);

    const getBatchDashboardData = async (requestedData?: string[]) => {
        try {
            const indexes = requestedData && requestedData.length > 0 ? requestedData : Object.keys(collection.value);
            console.log('Fetching dashboard data for:', indexes);
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

    return {
        collection,
        getBatchDashboardData,
    }
});