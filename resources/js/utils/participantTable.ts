import type {
    LearningParticipantProgress,
    LearningPortfolio,
    ParticipantRow,
    PortfolioSectionExam,
    PortfolioSectionExamResult,
} from '@/types/learning'
import { LESSON_PORTFOLIO_STATUS, LESSON_PORTFOLIO_STATUS_LABELS } from '@/config/learning'
import { getPortfolioPathLabel } from '@/utils/learningProgress'

const PORTFOLIO_STAGES = Object.entries(LESSON_PORTFOLIO_STATUS_LABELS)
    .map(([value, label]) => ({ value: Number(value), label }))
    .filter((stage) => stage.value > LESSON_PORTFOLIO_STATUS.NOT_STARTED)

// Portfolio themes: one participant per user, one entry per attempt, and one
// exam cell per section exam (from the theme's master list, filled per user).
export const portfolioParticipantRows = (
    portfolios: LearningPortfolio[],
    sectionExams: PortfolioSectionExam[],
    examResults: Record<number, Record<number, PortfolioSectionExamResult>>,
): ParticipantRow[] => {
    const groups = new Map<number, LearningPortfolio[]>()

    for (const portfolio of portfolios) {
        const userId = portfolio.user_id ?? 0
        if (!groups.has(userId)) {
            groups.set(userId, [])
        }
        groups.get(userId)!.push(portfolio)
    }

    return [...groups.entries()].map(([userId, attempts]) => {
        const userResults = examResults[userId] ?? {}

        return {
            userId,
            userName: attempts[0]?.user?.name ?? null,
            entries: attempts.map((portfolio) => {
                const status = portfolio.status ?? 0
                return {
                    key: `p-${portfolio.id}`,
                    methodLabel: getPortfolioPathLabel(portfolio.path),
                    attemptNo: portfolio.attempt_no ?? 1,
                    statusChips: PORTFOLIO_STAGES.map((stage) => ({
                        label: stage.label,
                        done: stage.value <= status,
                    })),
                    files: [],
                    portfolioId: portfolio.id ?? null,
                    detail: { type: 'portfolio', portfolio } as const,
                }
            }),
            examRows: sectionExams.map((exam) => {
                const result = userResults[exam.material_id] ?? null
                return {
                    key: `m-${exam.material_id}`,
                    title: exam.title,
                    attemptCount: result?.attempt_count ?? null,
                    maxAttempts: exam.max_attempts,
                    score: result?.latest_score ?? null,
                    passed: result ? result.passed : null,
                }
            }),
        }
    })
}

// Case-study themes: one participant per user; the learning-method column is
// empty, status is derived from theme progress, and the (theme-level) exam is a
// single exam cell. Case-study answers/understanding live in the detail modal.
export const caseStudyParticipantRows = (
    participants: LearningParticipantProgress[],
): ParticipantRow[] => {
    return participants.map((participant) => {
        const progress = participant.progress
        const exam = progress?.exam
        const statusChips = [
            { label: '基礎知識', done: Boolean(progress?.basic.completed) },
            { label: 'ケーススタディ', done: Boolean(progress?.case_study.completed) },
            { label: 'チェックリスト', done: Boolean(progress?.survey.completed) },
            ...(progress?.pledge?.required
                ? [{ label: '誓約書', done: Boolean(progress.pledge.signed) }]
                : []),
        ]

        // The signed 誓約書 copy hangs under the chips as a file link.
        const files = progress?.pledge?.signed && progress.pledge.signature_id
            ? [{
                key: `pledge-${progress.pledge.signature_id}`,
                label: '誓約書（署名済み）',
                href: `/lesson_pledge_file/${progress.pledge.signature_id}`,
                // signed copies are always stored as PDF
                ext: 'pdf',
            }]
            : []

        const examRows = exam && (exam.available || (exam.attempts_count ?? 0) > 0)
            ? [{
                key: `cs-exam-${participant.user?.id}`,
                title: '試験',
                attemptCount: exam.attempts_count ?? null,
                maxAttempts: (exam.attempts_count ?? 0) + (exam.remaining_attempts ?? 0) || null,
                score: exam.latest_score ?? null,
                passed: exam.latest_status ? exam.latest_status === 'passed' : null,
            }]
            : []

        return {
            userId: participant.user?.id ?? 0,
            userName: participant.user?.name ?? null,
            entries: [{
                key: `cs-${participant.user?.id}`,
                methodLabel: null,
                attemptNo: 1,
                statusChips,
                files,
                portfolioId: null,
                detail: { type: 'caseStudy', participant } as const,
            }],
            examRows,
        }
    })
}
