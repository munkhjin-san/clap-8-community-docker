import { CommonFile, Office, User } from "./globalInterface"
import { Project } from "./projectInterface"

export interface Asset {
    id: number
    gl_number: string
    item_name: string
    specs: string
    model_number: string
    classification: number
    value: number
    status: number
    current_user: User | null
    current_project: Project | null
    requests: AssetRequest[]
    current_office: AssetOffice | null
    request_logs: AssetRequest[]
    external_user: string | null
    created_at: string
    updated_at: string
    confirm_logs: AssetConfirmLog[]
    office_id: number | null
    office: Office | null

    asset_category_item_id?: number | null
    field_values?: Array<{
        id: number
        asset_record_id: number
        asset_category_item_field_id: number
        value: string | null
        field?: {
            id: number
            asset_category_item_id: number
            key: string | null
            label: string | null
            input_type: 'shorttext' | 'longtext' | 'password'
            placeholder: string | null
            rules: string | null
            visible: 'public' | 'private' | 'user'
            editable: boolean
        }
    }>
}

export interface AssetUser {
    name: string;
    id: number;
    pivot: userPivot
}

interface userPivot {
    created_at: string;
    updated_at: string;
    deleted_at: string;
}
export interface AssetRequest {
    id: number;
    asset_record_id: number;
    send_user: User | null;
    recieve_user: User | null;
    not_broken: number;
    status: number;
    return: number;
    created_at: string;
    steps: AssetRequestStep[]
    files: CommonFile[]
    from_project: number | null
    to_project: number | null
    from_external_user: string | null
    to_external_user: string | null
    memo: string | null
}

export interface AssetRequestStep {
    id: number;
    value: number;
    created_at: string;
    updated_at: string;
    approved_at: string;
    approver: User | null
    creator: User | null

}
interface AssetOffice {
    id: number;
    name: string;
}

export interface AssetConfirmLog {
    id: number;
    asset_record_id: number;
    user: User | null;
    memo: string | null;
    files: CommonFile[];
    created_at: string;
    updated_at: string;
}