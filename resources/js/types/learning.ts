import type { User } from '@/interface/globalInterface'
import type {
    LearningMaterialType,
    LessonAnswerStatus,
    LessonExamStatus,
    LessonPortfolioStatus,
    LessonSectionStatus,
} from '@/config/learning'

export interface LearningTheme {
    id: number
    title: string | null
    active: number | boolean
    archive: number | boolean
    portfolio: number | boolean
    has_case_study: number | boolean
    prompt_id: string | null
    assistant_id: string | null
    custom_form_id: number | null
    salary_issue_target?: number | boolean
    previous_version?: number | null
    discussion_date: string | null
    guidance: string | null
    episode_guidance: string | null
    title_guidance: string | null
    survey_completed?: boolean
    survey_date?: string | null
    lesson_portfolio?: LearningPortfolio | null
    materials?: LearningMaterial[]
    access_members?: User[]
    categories?: LearningThemeCategory[]
    ai_configs?: LearningThemeAiConfig[]
    progress?: LearningThemeProgress
}

export interface LearningThemeAiConfig {
    id?: number
    lesson_theme_id: number
    config_key: string
    lesson_material_id: number | null
    model: string | null
    instructions: string | null
    settings: Record<string, unknown> | null
    created_at?: string | null
    updated_at?: string | null
}

export interface LearningPersonalMaterial {
    id: number
    lesson_theme_id: number
    user_id: number
    lesson_theme_ai_config_id: number | null
    config_key: string
    content: string | null
    understand: boolean | null
    important_point: string | null
    source_snapshot: Record<string, unknown> | null
    generated_at: string | null
    completed_at: string | null
    created_at?: string | null
    updated_at?: string | null
}

export interface LearningThemeCategory {
    id: number
    name: string
    position: number
    is_default: boolean | number
    created_at?: string | null
    updated_at?: string | null
}

export interface LearningThemeProgress {
    basic: {
        total: number
        answer_total: number
        understanding_total: number
        completed: boolean
        not_understood: boolean
    }
    case_study: {
        total: number
        completed: boolean
    }
    exam: {
        available: boolean
        passed: boolean
        exhausted: boolean
        attempts_count: number
        remaining_attempts: number
        latest_score: number | null
        latest_status: string | null
    }
    survey: {
        available: boolean
        completed: boolean
        completed_at: string | null
    }
    portfolio: {
        required: boolean
        status: number
        draft_ready: boolean
        discussion_completed: boolean
        completed: boolean
    }
    theme_completed: boolean
    basic_completed: boolean
    case_completed: boolean
    exam_available: boolean
    exam_passed: boolean
    exam_exhausted: boolean
    survey_available: boolean
    survey_completed: boolean
    portfolio_required: boolean
    portfolio_ready: boolean
}

export interface LearningMaterialVersion {
    id: number
    lesson_theme_id: number
    version_no: number
    is_default: boolean
    label: string | null
    materials_count?: number
    created_at?: string | null
    updated_at?: string | null
}

export interface LearningMaterial {
    id: number
    lesson_theme_id: number | null
    lesson_material_version_id?: number | null
    assistant_id: string | null
    prompt_id: string | null
    priority: number
    status?: number
    user_id: number | null
    updated_by: number | null
    title: string | null
    content: string | null
    content_detailed: string | null
    has_feedback: number | boolean
    has_question: number | boolean | null
    has_understand: number | boolean
    material_type: LearningMaterialType | string | null
    created_at: string | null
    updated_at: string | null
    deleted_at: string | null
    answer?: LearningAnswer | null
    answers?: LearningAnswer[]
    summaries?: LearningSummary[]
}

export interface LearningAnswer {
    id: number
    material_id: number | null
    user_id: number | null
    answer: string | null
    ai_review: string | null
    cant_understand: string | null
    reason_dnt_und: string | null
    status: LessonAnswerStatus | number | null
    created_at: string | null
    updated_at: string | null
}

export interface LearningPortfolio {
    id?: number
    lesson_theme_id: number
    user_id: number | null
    status: LessonPortfolioStatus | number
    attempt_no?: number
    path?: number
    salary_issue_id?: number | null
    understand?: number
    title?: string | null
    portfolio_title?: string | null
    content?: string | null
    episode?: string | null
    public_title?: string | null
    public_content?: string | null
    noticed?: string | null
    basic_knowledge?: string | null
    positive_feedback?: string | null
    negative_feedback?: string | null
    ai_review_pre?: string | null
    ai_review_final?: string | null
    lesson_sections?: LearningSection[]
    lesson_form?: LearningForm | null
    lesson_theme?: Pick<LearningTheme, 'id' | 'title'>
    user?: User
    claps?: LearningClap[]
    progress?: LearningThemeProgress
    updated_at?: string | null
    created_at?: string | null
}

export interface LearningSection {
    id: number
    portfolio_id: number | null
    material_id: number | null
    user_id: number | null
    status: LessonSectionStatus | number
    content: string | null
    lesson_material?: Pick<LearningMaterial, 'id' | 'title'> | null
}

export interface LearningSummary {
    id: number
    lesson_material_id: number | null
    title: string | null
    content: string | null
    questions?: LearningSummaryQuestion[]
    answers?: LearningSummaryAnswer[]
}

export interface LearningSummaryQuestion {
    id: number
    lesson_summary_id: number | null
    question: string | null
    content: string | null
    answer?: LearningSummaryAnswer | null
}

export interface LearningSummaryAnswer {
    id?: number
    created_at?: string | null
    updated_at?: string | null
    user_id?: number | null
    lesson_summary_id: number
    lesson_summary_question_id: number
    answer_val: number
}

export interface LearningForm {
    id: number
    user_id: number
    lesson_theme_id: number
    question1: string | null
    answer1: string | null
    question2: string | null
    answer2: string | null
    question3: string | null
    answer3: string | null
    content: string | null
}

export interface LearningClap {
    from_user: number
    record_id: number
}

export interface LearningExam {
    id: number
    lesson_theme_id: number
    title: string | null
    description: string | null
    passing_score: number
    max_attempts: number
    questions?: LearningExamQuestion[]
}

export interface LearningExamQuestion {
    id: number
    lesson_exam_id: number
    prompt: string
    explanation: string | null
    correct_explanation?: string | null
    position: number
    options: LearningExamOption[]
}

export interface LearningExamOption {
    id: number
    lesson_exam_question_id: number
    label: string
    is_correct?: boolean
}

export interface LearningExamAttempt {
    id: number
    lesson_exam_id: number
    user_id: number
    score: number
    attempt_number: number
    status: LessonExamStatus
    submitted_at: string | null
    user?: User
}

export interface LearningExamPayload {
    exam: LearningExam | null
    attempts: LearningExamAttempt[]
    remaining_attempts: number
    final_attempt_answers: LearningFinalExamAnswer[]
    reveal_answers: boolean
}

export interface LearningLessonViewPayload {
    materials: LearningMaterial[]
    portfolio: LearningPortfolio | null
    exam: LearningExamPayload
}

export interface LearningPreviousExperiencePayload {
    has_experience: boolean
    theme: Pick<LearningTheme, 'id' | 'title'> | null
    portfolio: LearningPortfolio | null
    personal_material: LearningPersonalMaterial | null
    can_generate_personal_material: boolean
}

export interface LearningFinalExamAnswer {
    question_id: number
    option_id: number
    is_correct: boolean
}

export interface LearningParticipantProgress {
    id?: number
    user: User
    progress?: LearningThemeProgress
    answers?: Array<{ title: string | null; answer: string | null }>
    cant_understand?: string | null
    reason_dnt_und?: string | null
}

export interface LearningAttemptSummary {
    id: number
    attempt_no: number
    path: number
    status: number
    title: string | null
    created_at: string | null
}

export interface LearningThemeState {
    theme_id: number
    cleared: boolean
    attempts: LearningAttemptSummary[]
    current: { id: number; attempt_no: number; status: number; path: number } | null
    options: { path1: boolean; path2: boolean; path3: boolean }
}

export interface LearningChallengeGoalOption {
    goal_id: number
    title: string | null
    start_date: string | null
    end_date: string | null
    selectable: boolean
    reason: string | null
}

export interface LearningThemeChallengeOptions {
    theme_axis: string | null
    salary_target: boolean
    cleared: boolean
    eligible: boolean
    reason: string | null
    span: { year: number; which_half: string }
    goals: LearningChallengeGoalOption[]
}
