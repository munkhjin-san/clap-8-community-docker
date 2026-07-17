import { inject, type Ref } from 'vue'
import type { LearningPortfolioSaveRequest } from '@/composables/learningApi'
import type { LearningPortfolio } from '@/types/learning'

export interface LearningValidatableRef {
    validate?: () => Promise<{ valid: boolean }>
}

export interface LearningBasicItemContext {
    loading: Ref<boolean[]>
    saveItems: (
        routeName: string,
        status: number,
        refs: LearningValidatableRef[],
        params: LearningPortfolioSaveRequest,
    ) => Promise<void>
    viewPortfolios: () => void | Promise<void>
}

export function useLearningDraftContext() {
    const portfolio = inject<Ref<LearningPortfolio | null>>('portfolio')
    const basicItem = inject<LearningBasicItemContext>('basicItem')
    const getLessonPortfolios = inject<() => void | Promise<void>>('getLessonPortfolios')

    if (!portfolio || !basicItem) {
        throw new Error('Learning draft context is missing.')
    }

    return {
        portfolio,
        basicItem,
        getLessonPortfolios,
    }
}
