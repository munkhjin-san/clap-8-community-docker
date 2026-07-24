import type { Asset } from "./assetInterface"
import type { CalendarRecord } from "./calendarInterface"
import type { CustomForm } from "./customFormInterface"
import type { EmployeeChangeApplication } from "./employeeInterface"
import type { EvaluationRecord } from "./evaluationInterface"
import type { Message, Task, User } from "./globalInterface"
import type { Incident } from "./incident"
import type { NoticeRecord } from "./notice"
import type { Post } from "./postInterface"
import type { Project, ProjectAssignRecord } from "./projectInterface"
import type { PlannedLeaveChangeRequest, Shift, plannedLeave } from "./workInterface"
import type { EmergencyContactRecord } from "./supportInterface"

export type UserWithShift = User & {
    shift_records: Shift[]
}

export type CardBase = {
    title: string
    type: string
    col: string
    order?: number
    canResize?: boolean
    canFullscreen?: boolean
}

export type DashboardMessageCard = CardBase & {
    layout: 'message'
    data: DashboardMessageCardData
}

export type DashboardTaskCard = CardBase & {
    layout: 'task'
    data: DashboardTaskCardData
}

export type DashboardSurveyCard = CardBase & {
    layout: 'survey'
    data: DashboardSurveyCardData
}

export type DashboardOverdueGoalCard = CardBase & {
    layout: 'monthly_goals'
    data: DashboardOverdueGoalCardData
}
export type DashboardProjectCard = CardBase & {
    layout: 'project'
    data: DashboardProjectCardData
}
export type DashboardChallengeCard = CardBase & {
    layout: 'challenge'
    data: DashboardChallengeCardData
}

export type DashboardAssetCard = CardBase & {
    layout: 'assets'
    data: DashboardAssetCardData
}

export type DashboardIncidentCard = CardBase & {
    layout: 'incidents'
    data: DashboardIncidentCardData
}

export type DashboardScheduleCard = CardBase & {
    layout: 'schedules'
    data: DashboardScheduleCardData
}
export type DashboardPersonnelEvaluationCard = CardBase & {
    layout: 'personnelEvaluation'
    data: DashboardPersonnelEvaluationCardData
}
export type DashboardNoticeCard = CardBase & {
    layout: 'notice'
    data: DashboardNoticeCardData
}

export type DashboardMessageCardData = Message[]
export type DashboardTaskCardData = Task[]
export type DashboardSurveyCardData = CustomForm[]
export type DashboardOverdueGoalCardData = unknown[]
export type DashboardChallengeData = Omit<Post, 'id'> & {
    id: number | string
    relay_id?: number
    attention_type?: 'nice_follow_up' | 'progress_need' | 'update_need' | 'challenge_relay_received' | 'challenge_relay_returned' | 'nice_relay_glowd_nine' | 'rakuaward_nominate'
    attention_checkpoint?: number
    attention_deadline?: string | null
    attention_is_overdue?: boolean
    attention_progress_percent?: number
    relay_root_post_id?: number
    glowd_nine_source?: 'relay' | 'rakuaward' | 'challenge_award'
    declined_by_user?: User | null
    source_post_id?: number
    source_post_title?: string | null
}
export type DashboardChallengeCardData = DashboardChallengeData[]
export type DashboardNoticeCardData = NoticeRecord[]
export type DashboardSystemUpdatesData = number[]
export type DashboardPendingDailyReportsData = unknown[]
export type DashboardPendingGoalsForHrData = User[]

export type DashboardProjectCommentData = {
    type: 'project_detail' | 'confirmation_item' | 'finance',
    project_id: number,
    project_name: string,
    section?: string,
    period?: string,
    month_label?: string,
    count: number,
}

export type DashboardProjectCardData = {
    officer_approval_waiting: Project[],
    assign_approval_waiting: ProjectAssignRecord[],
    comments?: DashboardProjectCommentData[],
}

export type DashboardAssetCardData = {
    in_use: Asset[]
    waiting_approval: Asset[]
}

export type DashboardIncidentCardData = {
    attention: Incident[]
    emergency_contacts: EmergencyContactRecord[]
    pending_candidates: IncidentCandidate[]
    dismissed_candidates: IncidentCandidate[]
}

export type IncidentCandidateSource = 'daily_report_streak' | 'outcome_goal_submission' | 'outcome_goal_pm_approval'

export type IncidentCandidateContext = {
    // daily_report_streak
    missed_dates?: string[]
    missed_count?: number
    shift_record_ids?: number[]
    occurrence_ids?: number[]
    project_ids?: number[]
    manager_names?: string[]
    // outcome goal (submission / pm_approval)
    project_goal_id?: number
    goal_title?: string
    goal_owner_id?: number
    goal_owner_name?: string
    end_date?: string | null
    pm_id?: number
    pm_name?: string
    pm_names?: string[]
    submitted_at?: string | null
    incident_type?: string
}

export type IncidentCandidate = {
    id: number
    source_type: IncidentCandidateSource
    subject_user_id: number
    project_record_id: number | null
    audience: 'pm' | 'director'
    context: IncidentCandidateContext | null
    status: 'pending' | 'incident_created' | 'dismissed'
    decision_reason: string | null
    decided_by: number | null
    decided_at: string | null
    resulting_incident_id: number | null
    created_at: string
    updated_at: string
    subject: (Pick<User, 'id' | 'name'> & {
        icon_path?: string | null
        icon_bg?: string | null
        position_id?: number | null
    }) | null
    project: { id: number; name: string } | null
    decided_by_user?: (Pick<User, 'id' | 'name'> & {
        icon_path?: string | null
        icon_bg?: string | null
    }) | null
}

export type DashboardIncidentAlertCardData = IncidentCandidate[]

export type DashboardScheduleCardData = {
    temp_schedules: CalendarRecord[]
    this_week_schedules: CalendarRecord[]
    next_week_schedules: CalendarRecord[]
}

export type DashboardPersonnelEvaluationCardData = {
    pendingEvaluations: EvaluationRecord[],
    pendingAssignments: ProjectAssignRecord[],
    pendingChangeRequests: EmployeeChangeApplication[] | null,
}

export type DashboardPendingPlannedLeave = plannedLeave

export type PendingTimesheetData = {
    overtime: number
    shift: {
        month: number
        count: number        
    }[]
    timecard: {
        month: number
        count: number
    }[]
    user: User
    has_pending_timecards: boolean
}
export type AutoApprovedTimesheetRecord = {
    segment_id: number
    timecard_record_id: number
    project_id: number
    project_name: string
    day: string
    start_time: string
    end_time: string
    comment: string | null
    weather: number | null
    approved_at: string | null
}
export type AutoApprovedTimesheetData = {
    user: User
    read: boolean
    records: AutoApprovedTimesheetRecord[]
}
export type DashboardTimesheetCard = CardBase & {
    layout: 'timesheet'
    data: DashboardTimesheetCardData
}
export type DashboardTimesheetCardData = {
    pendingTimesheets: PendingTimesheetData[]
    autoApprovedTimesheets: AutoApprovedTimesheetData[]
    departuresReportUsers: UserWithShift[],
    pendingPlannedLeaves: DashboardPendingPlannedLeave[],
    pendingAttendance?: {
        user_id: number
        date_year_month: string
    } | null,
    pendingPlannedLeaveChangeRequests: PlannedLeaveChangeRequest[],
}
export type SpanRequiredGoalData = {
    year: number,
    half: string,
    needed_count: number,
    total_slot: number,
    created_count: number,
}
export type GoalRequiredData = {
    user: User,
    this_span: SpanRequiredGoalData,
    previous_span: SpanRequiredGoalData,
}

export type DashboardCollectionData = {
    pendingEvaluations: EvaluationRecord[]
    assets: DashboardAssetCardData
    incidents: DashboardIncidentCardData
    incidentAlerts: DashboardIncidentAlertCardData
    overdueGoals: DashboardOverdueGoalCardData
    challenges: DashboardChallengeCardData
    forms: DashboardSurveyCardData
    pendingApprovalTasks: DashboardTaskCardData
    pendingGoalsUserForHR: DashboardPendingGoalsForHrData
    remindedMessages: DashboardMessageCardData
    schedules: DashboardScheduleCardData
    pendingDailyReports: DashboardPendingDailyReportsData
    mustCheckMessages: DashboardMessageCardData
    mustSignMessages: DashboardMessageCardData
    unfinishedTasks: DashboardTaskCardData
    untouchedTasks: DashboardTaskCardData
    personnelEvaluation: DashboardPersonnelEvaluationCardData
    timesheet: DashboardTimesheetCardData
    projects: DashboardProjectCardData
    notices: DashboardNoticeCardData
    systemUpdates: DashboardSystemUpdatesData
    overdueGraveCount?: number
}

export type DashboardResponseData = Partial<Omit<DashboardCollectionData, 'overdueGraveCount'>> & {
    overdueGraveCount?: number | {
        overdueGoals?: DashboardCollectionData['overdueGoals']
        overdueGraveCount?: number
    }
}

export const DASHBOARD_COLLECTION_KEYS = [
    'pendingEvaluations',
    'assets',
    'incidents',
    'incidentAlerts',
    'overdueGoals',
    'challenges',
    'forms',
    'pendingApprovalTasks',
    'pendingGoalsUserForHR',
    'remindedMessages',
    'schedules',
    'pendingDailyReports',
    'mustCheckMessages',
    'mustSignMessages',
    'unfinishedTasks',
    'untouchedTasks',
    'personnelEvaluation',
    'timesheet',
    'projects',
    'notices',
    'systemUpdates',
] as const

export type DashboardCollectionKey = typeof DASHBOARD_COLLECTION_KEYS[number]

export const createDashboardAssetCardData = (): DashboardAssetCardData => ({
    in_use: [],
    waiting_approval: [],
})

export const createDashboardIncidentCardData = (): DashboardIncidentCardData => ({
    attention: [],
    emergency_contacts: [],
    pending_candidates: [],
    dismissed_candidates: [],
})

export const createDashboardIncidentAlertCardData = (): DashboardIncidentAlertCardData => []

export const createDashboardScheduleCardData = (): DashboardScheduleCardData => ({
    temp_schedules: [],
    this_week_schedules: [],
    next_week_schedules: [],
})

export const createDashboardPersonnelEvaluationCardData = (): DashboardPersonnelEvaluationCardData => ({
    pendingEvaluations: [],
    pendingAssignments: [],
    pendingChangeRequests: [],
})

export const createDashboardTimesheetCardData = (): DashboardTimesheetCardData => ({
    pendingTimesheets: [],
    departuresReportUsers: [],
    pendingPlannedLeaves: [],
    pendingAttendance: null,
    pendingPlannedLeaveChangeRequests: [],
    autoApprovedTimesheets: [],
})

export const createDashboardProjectCardData = (): DashboardProjectCardData => ({
    officer_approval_waiting: [],
    assign_approval_waiting: [],
})

export const createDashboardCollectionData = (): DashboardCollectionData => ({
    pendingEvaluations: [],
    assets: createDashboardAssetCardData(),
    incidents: createDashboardIncidentCardData(),
    incidentAlerts: createDashboardIncidentAlertCardData(),
    overdueGoals: [],
    challenges: [],
    forms: [],
    pendingApprovalTasks: [],
    pendingGoalsUserForHR: [],
    remindedMessages: [],
    schedules: createDashboardScheduleCardData(),
    pendingDailyReports: [],
    mustCheckMessages: [],
    mustSignMessages: [],
    unfinishedTasks: [],
    untouchedTasks: [],
    personnelEvaluation: createDashboardPersonnelEvaluationCardData(),
    timesheet: createDashboardTimesheetCardData(),
    projects: createDashboardProjectCardData(),
    notices: [],
    systemUpdates: [],
})

export type DashboardCard = DashboardMessageCard | DashboardTaskCard | DashboardSurveyCard | DashboardOverdueGoalCard | DashboardChallengeCard | DashboardAssetCard | DashboardIncidentCard | DashboardScheduleCard | DashboardPersonnelEvaluationCard | DashboardTimesheetCard | DashboardNoticeCard | DashboardProjectCard
