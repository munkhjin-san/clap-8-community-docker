import {
    LEARNING_MATERIAL_TYPES,
    LESSON_ANSWER_STATUS,
    LESSON_MATERIAL_PRIORITY,
    LESSON_PORTFOLIO_STATUS,
    LESSON_PORTFOLIO_STATUS_LABELS,
    LESSON_PORTFOLIO_PATH_LABELS,
} from '@/config/learning'
import type { LearningMaterial, LearningPortfolio } from '@/types/learning'

export type LearningStageState = 'empty' | 'locked' | 'available' | 'in_progress' | 'completed' | 'attention'
export type BasicLearningStatus = 'completed' | 'uncompleted' | null

export interface LearningStage {
    key: string
    label: string
    state: LearningStageState
}

export const isEnabled = (value: boolean | number | null | undefined) => value === true || value === 1

export const isCompletedAnswerStatus = (status: number | null | undefined) => status === LESSON_ANSWER_STATUS.COMPLETED

export const isNotUnderstoodAnswerStatus = (status: number | null | undefined) => {
    return status === LESSON_ANSWER_STATUS.NOT_UNDERSTOOD
}

export const isBasicMaterial = (material: Pick<LearningMaterial, 'material_type'>) => {
    return material.material_type === LEARNING_MATERIAL_TYPES.BASIC
}

export const isCaseStudyMaterial = (material: Pick<LearningMaterial, 'material_type'>) => {
    return material.material_type === LEARNING_MATERIAL_TYPES.CASE_STUDY
}

export const isLearnerTaskMaterial = (material: Pick<LearningMaterial, 'priority'>) => {
    return material.priority === LESSON_MATERIAL_PRIORITY.SECTION
}

export const getRequiredBasicMaterials = (materials: LearningMaterial[] = []) => {
    return materials.filter((material) => isBasicMaterial(material) && isLearnerTaskMaterial(material))
}

export const getCaseStudyMaterials = (materials: LearningMaterial[] = []) => {
    return materials.filter(isCaseStudyMaterial)
}

export const areMaterialsCompletedByAnswer = (materials: LearningMaterial[] = []) => {
    return materials.length > 0 && materials.every((material) => isCompletedAnswerStatus(material.answer?.status))
}

export const getBasicLearningStatus = (materials: LearningMaterial[] = []): BasicLearningStatus => {
    const basicMaterials = getRequiredBasicMaterials(materials)
    if (!basicMaterials.length) return null

    if (areMaterialsCompletedByAnswer(basicMaterials)) {
        return 'completed'
    }

    return basicMaterials.some((material) => isNotUnderstoodAnswerStatus(material.answer?.status))
        ? 'uncompleted'
        : null
}

export const areCaseStudiesCompletedByAnswer = (materials: LearningMaterial[] = []) => {
    const caseStudies = getCaseStudyMaterials(materials)
    return areMaterialsCompletedByAnswer(caseStudies)
}

export const isPortfolioComplete = (portfolio?: Pick<LearningPortfolio, 'status'> | null) => {
    return Number(portfolio?.status ?? 0) >= LESSON_PORTFOLIO_STATUS.FINAL_COMPLETED
}

export const getPortfolioStatusLabels = (status: number | null | undefined) => {
    const currentStatus = Number(status ?? 0)

    return Object.entries(LESSON_PORTFOLIO_STATUS_LABELS)
        .map(([value, label]) => ({ value: Number(value), label }))
        .filter((item) => item.value > LESSON_PORTFOLIO_STATUS.NOT_STARTED && item.value <= currentStatus)
}

export const getPortfolioStatusLabel = (status: number | null | undefined) => {
    return LESSON_PORTFOLIO_STATUS_LABELS[Number(status ?? 0)] ?? '未着手'
}

export const getPortfolioPathLabel = (path: number | null | undefined) => {
    return LESSON_PORTFOLIO_PATH_LABELS[Number(path ?? 0)] ?? '学習'
}
