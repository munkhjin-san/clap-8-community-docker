import type { User } from "./globalInterface"

export type ApplicationStatus = 'submitted' | 'reviewing' | 'confirmed' | 'denied'
export type ApplicationStatusFilter = ApplicationStatus | 'all'
export type ApplicationType = 'name_change' | 'address_change' | 'dependent_change' | 'leave_request' | 'work_location_change' | 'commute_change'
export type ApplicationTypeFilter = ApplicationType | 'all'

export type SelectOption<T extends string> = {
    value: T
    label: string
}

export const applicationStatusOptions: SelectOption<ApplicationStatus>[] = [
    { value: 'submitted', label: '申請中' },
    { value: 'reviewing', label: 'レビュー中' },
    { value: 'confirmed', label: '承認済み' },
    { value: 'denied', label: '却下' },
]

export const applicationStatusFilterOptions: SelectOption<ApplicationStatusFilter>[] = [
    { value: 'all', label: 'すべて' },
    ...applicationStatusOptions,
]

export const applicationTypeOptions: SelectOption<ApplicationType>[] = [
    { value: 'name_change', label: '氏名変更' },
    { value: 'address_change', label: '住所変更' },
    { value: 'dependent_change', label: '扶養追加・削除' },
    { value: 'leave_request', label: '休職申請' },
    { value: 'work_location_change', label: '勤務地変更' },
    { value: 'commute_change', label: '交通費変更' },
]

export const applicationTypeFilterOptions: SelectOption<ApplicationTypeFilter>[] = [
    { value: 'all', label: 'すべて' },
    ...applicationTypeOptions,
]

export const applicationStatusLabel = (status: ApplicationStatus) => {
    return applicationStatusOptions.find(option => option.value === status)?.label ?? status
}

export const applicationTypeLabel = (type: ApplicationType) => {
    return applicationTypeOptions.find(option => option.value === type)?.label ?? type
}


export type FileSummary = {
    id: number
    name: string
}

export type EmployeeChangeApplication = {
    id: number
    type: ApplicationType
    type_label: string
    status: ApplicationStatus
    status_label: string
    effective_date: string | null
    review_comment: string | null
    created_at: string
    user: User | null
    reviewed_by: User | null
    profile_detail: Record<string, any> | null
    leave_detail: Record<string, any> | null
    commute_detail: Record<string, any> | null
    files: FileSummary[]
}
