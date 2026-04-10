
import { User } from "./globalInterface"
import { FileRecord } from "./trayInterface"
import type { ProjectType } from "./projectInterface"

export type CustomFormUsage = 'general' | 'project_creation'

export interface CustomForm {
    id: number	
    community_record_id?: number	
    title: string	
    description: string	
    created_at?: string	
    updated_at?: string	
    blocks: CustomFormBlock[]
    survey_answers?: SurveyAnswer[]
    users?: CustomFormUser[]
    admins?: CustomFormUser[]
    repeat_setting?: number
    repeat_day?: number
    board_record_id?: number | null
    has_prize?: boolean
    is_public?: boolean
    public_token?: string | null
    status?: number
    usage?: CustomFormUsage
    project_type_id?: number | null
    projectType?: ProjectType | null
    project_type?: ProjectType | null
}
export interface CustomFormUser extends User {
    pivot: {
        custom_form_id: number
        user_id: number
        prize: number
        try_flag: boolean
    }
    is_answered?: boolean
}
export interface CustomFormBlock {
    id: number	
    type: CustomFormBlockType
    question: string
    is_required: boolean
    elements: CustomFormBlockElement[]
    answers?: SurveyBlockAnswer[]
    order_number?: number
    placeholder?: string
    depends_on?: CustomFormBlockDependsOn[] | null
    categories?: string[] | null
    category_ids?: Array<number | string> | null
    checkitemCategories?: { id: number; label: string; name?: string }[]
    checkitem_categories?: { id: number; label: string; name?: string }[]
}
export type CustomFormBlockType = 'checkbox' | 'radio' | 'singletext' | 'multitext' | 'date' | 'time' | 'select' | 'file' | 'header'
export type CustomFormBlockState = 'control' | 'live'

export interface CustomFormBlockElement {
    id: number | string	
    value: string
    has_sub_text: boolean
    has_sub_text_required: boolean	
    is_required: boolean
    created_at?: string	
    updated_at?: string	
    answers?: SurverBlockElementAnswer[]
    placeholder?: string
}

export interface CustomFormBlockDependsOn {
    block_id: number
    type?: string
    element_ids?: number[]
    match?: string
}

export interface SurveyAnswer{
    id?: number	
    user_id?: number
    community_record_user_id?: number
    created_at?: string	
    updated_at?: string	
    block_answers: SurveyBlockAnswer[]
    user?: User
    status?: number
    target_date?: string | null
    custom_form?: CustomForm | null
    respondent_label?: string
}

export interface SurveyBlockAnswer{
    id?: number		
    user_id?: number
    community_record_user_id?: number
    text_answer: string
    element_answers: SurverBlockElementAnswer[]
    custom_form_block_id?: number
    user?: User
    files: FileRecord[]
    survey_answer?: SurveyAnswer
}

export interface SurverBlockElementAnswer{
    id?: number	
    user_id?: number
    community_record_user_id?: number
    custom_form_block_element_id?: number
    element?: CustomFormBlockElement	
    sub_text?: string
    checked: boolean
    user?: User
    survey_block_answer?: SurveyBlockAnswer 
}
