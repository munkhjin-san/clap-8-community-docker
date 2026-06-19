import {
    DASHBOARD_COLLECTION_KEYS,
    createDashboardCollectionData,
    type DashboardCollectionData,
    type DashboardResponseData,
} from "@/interface/dashboard";
import type { Incident } from "@/interface/incident";
import type { Shift } from "@/interface/workInterface";
import axios from "axios";
import { DateTime } from "luxon";
import { defineStore } from "pinia";
import { computed, ref } from "vue";
import { useDashboardGoalsStore } from "./dashboardGoals";

export const useDashboardStore = defineStore('dashboardStore', () => {
    const collection = ref<DashboardCollectionData>(createDashboardCollectionData())

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
            return !dashboardPostReminderTypes.includes(challenge.attention_type ?? '')
        }).length
    })
    const isNewIncident = (incident: Incident) => !incident.last_read_at && !(incident.read_histories?.length)
    const hasUnreadIncidentUpdates = (incident: Incident) => !isNewIncident(incident) && (incident.unread_update_logs_count ?? 0) > 0
    const unreadIncidentCommentCount = computed(() => collection.value.incidents.attention.reduce((total, incident) => total + (incident.unread_comments_count ?? 0), 0))
    const newIncidentCount = computed(() => collection.value.incidents.attention.filter(isNewIncident).length)
    const updatedIncidentCount = computed(() => collection.value.incidents.attention.filter(hasUnreadIncidentUpdates).length)
    const incidentBadgeCount = computed(() => updatedIncidentCount.value + unreadIncidentCommentCount.value)
    const activeEmergencyContactCount = computed(() => collection.value.incidents.emergency_contacts.filter((contact) => contact.status !== 'complete').length)
    const autoApprovedTimesheetCount = computed(() => {
        return collection.value.timesheet.autoApprovedTimesheets.reduce((total, item) => {
            return total + (item.read ? 0 : item.records.length)
        }, 0)
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
            const data: DashboardResponseData = res.data
            const overduePayload = data.overdueGraveCount;
            if (overduePayload && typeof overduePayload === 'object') {
                if (Array.isArray(overduePayload.overdueGoals)) {
                    collection.value.overdueGoals = overduePayload.overdueGoals;
                }
                if (typeof overduePayload.overdueGraveCount === 'number') {
                    collection.value.overdueGraveCount = overduePayload.overdueGraveCount;
                }
            }

            for (const key of DASHBOARD_COLLECTION_KEYS) {
                if (Object.prototype.hasOwnProperty.call(data, key)) {
                    Object.assign(collection.value, { [key]: data[key] });
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
        (collection.value.personnelEvaluation.pendingEvaluations.length ?? 0) + 
        (collection.value.personnelEvaluation.pendingAssignments.length ?? 0) +
        (collection.value.personnelEvaluation.pendingChangeRequests?.length ?? 0) +
        collection.value.notices.length + collection.value.projects.assign_approval_waiting.length + 
        collection.value.projects.officer_approval_waiting.length +
        autoApprovedTimesheetCount.value +
        incidentBadgeCount.value + collection.value.systemUpdates.length 
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
        return overdueGoals.length + overdueApprovalGoalsCount + needed + pendingAttendanceCount + overdueCheckMessages + pendingTimesheetsCount + newIncidentCount.value + activeEmergencyContactCount.value
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
        pendingTimeSheets,
        autoApprovedTimesheetCount,
        newIncidentCount,
        updatedIncidentCount,
        unreadIncidentCommentCount,
        incidentBadgeCount,
        activeEmergencyContactCount,
    }
});
