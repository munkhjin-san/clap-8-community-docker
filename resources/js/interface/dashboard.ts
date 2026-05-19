import { Asset } from "./assetInterface"
import { CalendarRecord } from "./calendarInterface"
import { Message, Task, User } from "./globalInterface"
import { Incident } from "./incident"
import { Post } from "./postInterface"
import { Project, ProjectAssignRecord } from "./projectInterface"
import { Shift } from "./workInterface"

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
    data: Message[]
}

export type DashboardTaskCard = CardBase & {
    layout: 'task'
    data: Task[]
}

export type DashboardSurveyCard = CardBase & {
    layout: 'survey'
    data: any[]
}

export type DashboardOverdueGoalCard = CardBase & {
    layout: 'monthly_goals'
    data: any
}
export type DashboardProjectCard = CardBase & {
    layout: 'project'
    data: {
        officer_approval_waiting: Project[],
        assign_approval_waiting: ProjectAssignRecord[],
        comments?: {
            type: 'project_detail' | 'confirmation_item' | 'finance',
            project_id: number,
            project_name: string,
            section?: string,
            period?: string,
            month_label?: string,
            count: number,
        }[],
    }
}
export type DashboardChallengeCard = CardBase & {
    layout: 'challenge'
    data: Post[]
}

export type DashboardAssetCard = CardBase & {
    layout: 'assets'
    data: {
        in_use: Asset[]
        waiting_approval?: Asset[]
    }
}

export type DashboardIncidentCard = CardBase & {
    layout: 'incidents'
    data: {
        attention: Incident[]
    }
}

export type DashboardScheduleCard = CardBase & {
    layout: 'schedules'
    data: {
        temp_schedules: CalendarRecord[]
    }
}
export type DashboardPersonnelEvaluationCard = CardBase & {
    layout: 'personnelEvaluation'
    data: {
        pendingEvaluations: any[],
        pendingAssignments: ProjectAssignRecord[],
    }
}
export type DashboardNoticeCard = CardBase & {
    layout: 'notice'
    data: any[]
}
export type pendingTimesheedData = {
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
export type DashboardTimesheetCard = CardBase & {
    layout: 'timesheet'
    data: {
        pendingTimesheets: pendingTimesheedData[]
        departuresReportUsers: UserWithShift[],
        pendingPlannedLeaves: any[],
        pendingAttendance?: {
            user_id: number
            date_year_month: string
        } | null
    }
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

export type DashboardCard = DashboardMessageCard | DashboardTaskCard | DashboardSurveyCard | DashboardOverdueGoalCard | DashboardChallengeCard | DashboardAssetCard | DashboardIncidentCard | DashboardScheduleCard | DashboardPersonnelEvaluationCard | DashboardTimesheetCard | DashboardNoticeCard | DashboardProjectCard 
