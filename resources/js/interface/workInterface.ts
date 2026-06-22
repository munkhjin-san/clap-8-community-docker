import { EmoteUser, TaskUser, User } from "./globalInterface"

export type WorkItem = {
    date: string
    endDate: string
    notification_user?: User | TaskUser
}

type NotificationUser = {
    id: number
    name: string
    icon_path: string
    icon_bg: string | null
}
export type ChosenDateShiftType = {
    id: number | null
    name: string
    abbreviation: string
    value: number | null
}
export type CustomFieldData = {
    id: number
    type_id: number
    value_text: string
    value_int: number
    updated_at?: string
    emoted_users?: EmoteUser[]
}
export type CustomInfoType = {
    customType: CustomType
}
type CustomType = {
    form_type: string
    title: string
    id: number
    custom_field_parts_records: CustomParts
}
type CustomParts = {
    custom_part: CustomPartsInterface
}
type CustomPartsInterface = {
    parts_value: number | null
    parts_lavel: string | null
}

export type AttendanceData = {
    annual_leave: number
    approved_count: number
    attendance_flag: boolean
    condolence_leave: number
    holiday_count: number
    holiday_worked_time: number
    month_move_allowance_count: number
    month_over_time: number
    month_stay_allowance_count: number
    night_over_time: number
    over_time: number
    shift_count: number
    shift_holidays: number
    should_work: number
    should_work_day: number
    transfer_leave: number
    unapproved_count: number
    unsaved_count: number
    user: User
    worked_time: number
    workedday_count: 4
}
export type RecordItem = {
    day_show: string
    day_full: Date | string
    shift: Shift
    time_card: TimeCard
    user_name: string
    allowances: string
    comment: string
    satisfy: string
    weather: number | null
    incident: string
    ability: any
    last: boolean
    user_id: number | null
    work_type: number | null
    work_time_day: number | undefined
}
export type Shift = {
    id: number | null
    user_id: number | null
    start_time: string
    end_time: string
    shift_type: ShiftType | undefined
    shift_day: Date | string
    overtime_request: any
    status_flag: number
    departure_report: string | null
    planned_leave_change_request?: PlannedLeaveChangeRequest | null
}
export type PlannedLeaveChangeRequestStatus = 'pending' | 'approved' | 'rejected'
export type PlannedLeaveChangeRequest = {
    id: number
    user_id: number
    shift_record_id: number | null
    approver_id: number | null
    pm_id: number | null
    project_id: number | null
    original_date: Date | string
    requested_date: Date | string
    reason: string | null
    pm_approval_required: boolean
    pm_approval_date: string | null
    approval_date: string | null
    status: PlannedLeaveChangeRequestStatus
    status_label: string
    created_at: string
    updated_at: string
    user?: User
    approver?: User | null
    pm_approver?: User | null
    pmApprover?: User | null
    project_record?: {
        id: number
        name: string
    } | null
}
export type TimeCard = {
    id: number | null
    user_id: number | null
    day: Date | undefined
    start_time: string
    end_time: string
    over_time: string
    work_time: number
    break_time: number
    status_flag: number
    stamp_flag: number
    custom_field_data_records: CustomFieldData[]
}
export type ShiftType = {
    id: number | null
    name: string
    abbreviation: string
}
export type notApproved = {
    overtime:  number
    shift: notApprovedShift[]
    timecard: number
    user: User
}

export type notApprovedShift = {
    month: number;
    count: number;
    user_id: number;
}
export type plannedLeave = {
    remaining_days: number;
    shift_count: number;
    tempData: tempData;
}
export type tempData = {
    date: string;
    endDate: string;
    period_end?: string;
    granted_days: number | null;
    id: number | string;
    notification_user?: User;
    planned_days: number;
    user_code: number | string | null;
    user_name?: string | null;
    grant_id?: number | null;
    source?: string;
    planning_allowed_from?: string;
    planning_allowed?: boolean;
}
