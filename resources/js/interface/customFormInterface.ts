
import { User } from "./globalInterface"

export interface CustomForm {
    id: number	
    community_record_id?: number	
    title: string	
    description: string	
    created_at?: string	
    updated_at?: string	
    blocks: CustomFormBlock[]
    survey_answers?: SurveyAnswer[]

}

export interface CustomFormBlock {
    id: number	
    type: CustomFormBlockType
    question: string
    is_required: boolean
    elements: CustomFormBlockElement[]
    answers?: SurveyBlockAnswer[]
    order_number?: number
}
export type CustomFormBlockType = 'checkbox' | 'radio' | 'singletext' | 'multitext' | 'date' | 'time' | 'select'
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
}

export interface SurveyAnswer{
    id?: number	
    user_id?: number
    community_record_user_id?: number
    created_at?: string	
    updated_at?: string	
    block_answers: SurveyBlockAnswer[]
    user?: User
}

export interface SurveyBlockAnswer{
    id?: number		
    user_id?: number
    community_record_user_id?: number
    text_answer: string
    element_answers: SurverBlockElementAnswer[]
    custom_form_block_id?: number
    user?: User
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
}