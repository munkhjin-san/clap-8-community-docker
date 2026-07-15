import type { LearningParticipantProgress } from '@/types/learning'

export type CaseStudyExamStatus = 'passed' | 'failed' | string

export const isCaseStudyBasicCompleted = (item: LearningParticipantProgress) => {
    return Boolean(item.progress?.basic.completed)
}

export const isCaseStudyBasicNotUnderstood = (item: LearningParticipantProgress) => {
    return Boolean(item.progress?.basic.not_understood)
}

export const isCaseStudyCompleted = (item: LearningParticipantProgress) => {
    return Boolean(item.progress?.case_study.completed)
}

export const isCaseStudySurveyCompleted = (item: LearningParticipantProgress) => {
    return Boolean(item.progress?.survey.completed)
}

export const getCaseStudyAttemptCount = (item: LearningParticipantProgress) => {
    return item.progress?.exam.attempts_count ?? null
}

export const getCaseStudyLatestScore = (item: LearningParticipantProgress) => {
    return item.progress?.exam.latest_score ?? null
}

export const getCaseStudyLatestExamStatus = (item: LearningParticipantProgress): CaseStudyExamStatus | null => {
    return item.progress?.exam.latest_status ?? null
}

export const getCaseStudyStatusLabels = (item: LearningParticipantProgress) => {
    const labels: string[] = []

    if (isCaseStudyBasicCompleted(item)) {
        labels.push('✅基礎知識')
    } else if (isCaseStudyBasicNotUnderstood(item)) {
        labels.push('❌基礎知識')
    }

    if (isCaseStudyCompleted(item)) {
        labels.push('✅ケーススタディ')
    }

    if (isCaseStudySurveyCompleted(item)) {
        labels.push('✅チェックリスト')
    }

    return labels
}

export const getCaseStudyExamStatusLabel = (status: CaseStudyExamStatus | null) => {
    if (status === 'passed') return '合格'
    if (status === 'failed') return '不合格'
    return ''
}

export const buildCaseStudyParticipantCsvRows = (items: LearningParticipantProgress[]) => {
    return items.map((item) => {
        const answers = (item.answers ?? [])
            .map((answer) => answer.answer ?? '')
            .join('\n')
        const score = getCaseStudyLatestScore(item)

        return {
            '氏名': item.user?.name ?? '',
            'ステータス': getCaseStudyStatusLabels(item).join('\n'),
            'ケーススタディ答え': answers,
            '試験受験回': String(getCaseStudyAttemptCount(item) ?? ''),
            '試験スコア': score !== null ? `${score}%` : '',
            '試験結果': getCaseStudyExamStatusLabel(getCaseStudyLatestExamStatus(item)),
        }
    })
}
