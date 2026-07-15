import type { LearningParticipantProgress, LearningPortfolio } from '@/types/learning'

export const collectLearningParticipantIds = (
    materialParticipants: LearningParticipantProgress[],
    portfolios: LearningPortfolio[],
) => {
    const ids = new Set<number>()

    materialParticipants.forEach((item) => {
        if (item.user?.id) {
            ids.add(item.user.id)
        }
    })

    portfolios.forEach((portfolio) => {
        if (portfolio.user?.id) {
            ids.add(portfolio.user.id)
        }
    })

    return ids
}
