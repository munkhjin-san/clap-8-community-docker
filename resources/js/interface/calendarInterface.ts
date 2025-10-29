import { DateTime } from "luxon";
import { User, CommonFile, Facility, Task } from "./globalInterface";

export interface CalendarGroup {
    id: number;
    name: string;
    selected: number;
    users: CalendarGroupUser[]
}
export interface CalendarGroupUser extends User{
    pivot: {
        selected_as_calendar_member: number
    }
}
export interface NormalHourDay {
    full: string, 
    day: string, 
    day_holiday: string | null
}
export interface CalendarRecord{
    id: number;
    release_flag: number;
    edit_all: number;
    repetition_type: number;
    r_group_id: string;
    created_user: number;
    updated_user: number;
    user_id: number;
    title: string;
    date_start: string;
    date_end: string;
    expiration_start: string;
    expiration_end: string;
    repeat_week: string;
    repeat_days: string;
    repeat_month: string;
    qualified_institution: number;
    qualified_car: number;
    zoom_url:string;
    zoom_value: number;
    zoom_account: string;
    zoom_account_pass: string;
    zoom_id: string;
    zoom_pass: string;
    zoom_waiting_room: number;
    remarks: string;
    referrer: string;
    shift: number;
    descendant_of: number;
    created_at: string;
    updated_at: string;
    calendar_users: CalendarGroupUser[];
    calendar_view_users: CalendarGroupUser[];
    updated_by: User;
    created_by: User;
    files: CommonFile[];
    width?: number;
    x?: number;
    y?: number;
    from?: string;
    order?: number;
    active_user_id?: number;
    facilities: Facility[];
    task: Task;
    members_only: number;
}


export interface NormalMonthDay {
    day_full: string;
    day_holiday: string;
    day_short: string;
}
export type WeeksArray = NormalMonthDay[][];

export interface NormalHourDay {
    full: string, 
    day: string, 
    day_holiday: string | null
}

export interface MemberMonthDay {
    day_full: string;
    day_holiday: string;
    day_short: string;
    records: CalendarRecord[]
}

export interface MemberHourDay {
    user: CalendarGroupUser
    records: CalendarRecord[]
    googleEvents: GoogleEventItem[]
    date: string
    hour?: string
}

export interface FastCreateData {
    x: number
    y: number
    time: string
    stamp: DateTime | null
}

export interface RepeatDataType {
    weekly: WeeklyRepeat
    monthly: MonthlyRepeat
    yearly: YearlyRepeat
}
interface WeeklyRepeat {
    selected_days: boolean[],
    repeat_date_from: string
    repeat_date_to: string
}
interface MonthlyRepeat {
    selected_day:number
    repeat_date_from: string
    repeat_date_to:string
}
interface YearlyRepeat{
    selected_month: number
    selected_day: number
    year_from: number
    year_to: number
}

export interface FacilityItem {
    label: string;
    value: number;
    selected: boolean;
  }
export interface FacilityData {
    qualified_car: FacilityItem[]
    qualified_institution: FacilityItem[]
    zoom_value: FacilityItem[]
}

export interface TimeSlot {
    [userId: string]: boolean;
}

export interface DailySchedule {
    [time: string]: TimeSlot; 
}

export interface DateSchedule {
    [date: string]: DailySchedule; 
}

export interface Fac {
    label: string;
    value: number;
    selected: number
}
export interface FacList {
    qualified_institution: Fac[];
    zoom_value: Fac[];
    qualified_care: Fac[];

}

export interface GoogleCalendar {
    id: string,
    summary: string,
    events: GoogleEventItem[]
    description?: string
    timeZone?: string
    accessRole?: string
    backgroundColor?: string
    foregroundColor?: string
    selected?: boolean
}

export interface GoogleEventItem {
    all_day: boolean
    calendarId: string
    calendarName: string
    color: string
    foregroundColor: string
    textColor: string
    end_date: string
    end_time: string | null
    id: string
    start_date: string
    start_time: string | null
    summary: string
    description: string | null
    timezone: string
    user_info: GoogleUserInfo
    order: number
}
export interface GoogleUserInfo {
    avatar_url: string,
    name: string
}
export interface GoogleCalendarListResponse {
    calendars: GoogleCalendar[]
    user_info: GoogleUserInfo
    status: string,
    calendar_ids: string[]
}
export interface ProjectSetting {
    id: number;
    name: string;
    color: string | null;
}

