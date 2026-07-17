export const LEARNING_MATERIAL_TYPES = {
    BASIC: '基礎知識',
    CASE_STUDY: 'ケーススタディ',
} as const

export const LESSON_MATERIAL_PRIORITY = {
    HEADER: 0,
    SECTION: 1,
} as const

export const LESSON_ANSWER_STATUS = {
    NOT_UNDERSTOOD: -1,
    DRAFT: 1,
    COMPLETED: 2,
} as const

export const LESSON_SECTION_STATUS = {
    NOT_STARTED: 0,
    DRAFT: 1,
    COMPLETED: 2,
} as const

export const LESSON_PORTFOLIO_STATUS = {
    NOT_STARTED: 0,
    DISCUSSION_DRAFT_READY: 1,
    DISCUSSION_COMPLETED: 2,
    FINAL_COMPLETED: 3,
} as const

export const LESSON_EXAM_STATUS = {
    PASSED: 'passed',
    FAILED: 'failed',
} as const

export const LESSON_PORTFOLIO_STATUS_LABELS: Record<number, string> = {
    [LESSON_PORTFOLIO_STATUS.NOT_STARTED]: '未着手',
    [LESSON_PORTFOLIO_STATUS.DISCUSSION_DRAFT_READY]: '知識研修',
    [LESSON_PORTFOLIO_STATUS.DISCUSSION_COMPLETED]: 'グループディスカッション',
    [LESSON_PORTFOLIO_STATUS.FINAL_COMPLETED]: 'ポートフォリオ',
}

export const LESSON_PORTFOLIO_PATH = {
    FIRST: 1,
    REPEAT: 2,
    SALARY_ISSUE: 3,
} as const

export const LESSON_PORTFOLIO_PATH_LABELS: Record<number, string> = {
    [LESSON_PORTFOLIO_PATH.FIRST]: '初回学習',
    [LESSON_PORTFOLIO_PATH.REPEAT]: '再学習',
    [LESSON_PORTFOLIO_PATH.SALARY_ISSUE]: '昇給課題',
}

export const LESSON_PROGRESS_STEP_LABELS = {
    BASIC: '知識研修',
    DISCUSSION: 'グループディスカッション',
    PORTFOLIO: 'ポートフォリオ',
    CASE_STUDY: 'ケーススタディ',
    SURVEY: 'チェックリスト',
    EXAM: '試験',
} as const

export type LearningMaterialType = typeof LEARNING_MATERIAL_TYPES[keyof typeof LEARNING_MATERIAL_TYPES]
export type LessonAnswerStatus = typeof LESSON_ANSWER_STATUS[keyof typeof LESSON_ANSWER_STATUS]
export type LessonSectionStatus = typeof LESSON_SECTION_STATUS[keyof typeof LESSON_SECTION_STATUS]
export type LessonPortfolioStatus = typeof LESSON_PORTFOLIO_STATUS[keyof typeof LESSON_PORTFOLIO_STATUS]
export type LessonExamStatus = typeof LESSON_EXAM_STATUS[keyof typeof LESSON_EXAM_STATUS]
