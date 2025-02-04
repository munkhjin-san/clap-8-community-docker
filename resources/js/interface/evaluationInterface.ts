import { User } from "./globalInterface"
import { ProjectGoal } from "./projectInterface"

export interface EvaluationRecord {
    id: number
    user_id: number
    mentor_id: number | null
    general_position? : string
    new_position? : string
    current_level?: string    
    year: string
    which_half: string
    grade?: string
    current_salary_rank?: string
    after_salary_rank?: string
    vision: string
    mentor_comment: string
    status: number
    checklist: EvaluationSkill[]
    candidate: EvaluationCandidate[]
    outcome_goals: ProjectGoal[]
    mentor: User
    user: User
}

export interface EvaluationSkill {
    content: string
}

export interface EvaluationCandidate {
    next_candidate : string
}
