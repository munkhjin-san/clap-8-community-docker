export interface WorkItem {
    date: string
    endDate: string
    notification_user: NotificationUser
}

interface NotificationUser {
    id: number
    name: string
    icon_id: number
}
export interface ChosenDateShiftType {
    id: number | null
    name: string
    abbreviation: string
    value: number | null
}
export interface CustomFieldData {
    type_id: number
    value_text: string
    value_int: number
}
export interface CustomInfoType {
    customType: CustomType
}
interface CustomType {
    form_type: string
    title: string
    id: number
    custom_field_parts_records: CustomParts
}
interface CustomParts {
    custom_part: CustomPartsInterface
}
interface CustomPartsInterface{
    parts_value: number | null
    parts_lavel: string | null
}

export interface AttendanceData {
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
export interface User {
    id: number
    name: string
    icon_id: number
    position_id: number
    user_code: number
    work_time_day: number
    work_type: number
}