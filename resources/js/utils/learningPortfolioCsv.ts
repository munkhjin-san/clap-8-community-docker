import type { LearningPortfolio } from '@/types/learning'
import { getPortfolioStatusLabel, getPortfolioPathLabel } from '@/utils/learningProgress'

type PortfolioCsvRow = Record<string, string | null | undefined>

const getFormValue = (portfolio: LearningPortfolio, field: 'question' | 'answer', index: number) => {
    const key = `${field}${index}` as 'question1' | 'question2' | 'question3' | 'answer1' | 'answer2' | 'answer3'
    return portfolio.lesson_form?.[key] ?? ''
}

const portfolioStatus = (portfolio: LearningPortfolio) => {
    return portfolio.status
}

const buildUnderstandingText = (portfolio: LearningPortfolio) => {
    return (portfolio.lesson_sections ?? [])
        .map((section) => {
            return `${section.lesson_material?.title ?? ''}\n${section.content ?? ''}`
        })
        .join('\n\n')
}

const buildFormText = (portfolio: LearningPortfolio) => {
    if (!portfolio.lesson_form) return ''

    const lines: string[] = []

    for (let index = 1; index <= 3; index++) {
        lines.push(`Q: ${getFormValue(portfolio, 'question', index)}`)
        lines.push(`A: ${getFormValue(portfolio, 'answer', index)}`)
    }

    if (portfolio.lesson_form.content) {
        lines.push(`意見: ${portfolio.lesson_form.content}`)
    }

    return lines.join('\n')
}

export const buildPortfolioCsvRows = (portfolios: LearningPortfolio[]): PortfolioCsvRow[] => {
    return portfolios.map((portfolio) => ({
        '氏名': portfolio.user?.name ?? '',
        '学習方法': getPortfolioPathLabel(portfolio.path),
        '回数': `${portfolio.attempt_no ?? 1}回目`,
        'ステータス': getPortfolioStatusLabel(portfolioStatus(portfolio)),
        '基礎知識理解': buildUnderstandingText(portfolio),
        'ディスカッション用ポートフォリオ': `${portfolio.portfolio_title ?? ''}\n${portfolio.content ?? ''}`,
        'ポジティブフィードバック': portfolio.positive_feedback,
        'ネガティブフィードバック': portfolio.negative_feedback,
        'フィードバックによる発見と成長': portfolio.noticed,
        '本ポートフォリオ': `${portfolio.public_title ?? ''}\n${portfolio.public_content ?? ''}`,
        'アンケート': buildFormText(portfolio),
    }))
}
