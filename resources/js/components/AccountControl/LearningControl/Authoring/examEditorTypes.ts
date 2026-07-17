export interface EditableExamOption {
    id: number | null
    uuid: string
    label: string
    is_correct: boolean
}

export interface EditableExamQuestion {
    id: number | null
    uuid: string
    prompt: string
    explanation: string
    correct_explanation: string
    position: number
    options: EditableExamOption[]
}
