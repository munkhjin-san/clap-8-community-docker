import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useLearningApi } from '@/composables/learningApi'
import { LESSON_MATERIAL_PRIORITY, LESSON_SECTION_STATUS } from '@/config/learning'
import type { LearningExam, LearningExamAttempt, LearningMaterial, LearningPortfolio, LearningSection } from '@/types/learning'

export function useLearningLessonView() {
    const route = useRoute()
    const learningApi = useLearningApi()
    const materials = ref<LearningMaterial[]>([])
    const portfolio = ref<LearningPortfolio | null>(null)
    const examData = ref<LearningExam | null>(null)
    const examAttempts = ref<LearningExamAttempt[]>([])
    const examRemaining = ref(0)
    const loading = ref(false)

    const themeId = computed(() => {
        const param = route.params.lessonThemeId
        return Array.isArray(param) ? param[0] : param
    })

    const filteredMaterials = computed(() => {
        return materials.value.filter((material) => material.priority === LESSON_MATERIAL_PRIORITY.SECTION)
    })

    const sectionsStatus = computed<LearningSection[]>(() => {
        return portfolio.value?.lesson_sections ?? []
    })

    const sectionsCompleted = computed(() => {
        return sectionsStatus.value.length > 0
            && filteredMaterials.value.length > 0
            && sectionsStatus.value.filter((section) => section.status === LESSON_SECTION_STATUS.COMPLETED).length === filteredMaterials.value.length
    })

    const getLessons = async() => {
        if (!themeId.value) {
            materials.value = []
            examData.value = null
            examAttempts.value = []
            examRemaining.value = 0
            return
        }

        const data = await learningApi.getThemeMaterials(themeId.value)
        materials.value = data ?? []
    }

    const getLessonPortfolios = async() => {
        if (!themeId.value) {
            portfolio.value = null
            return
        }

        portfolio.value = await learningApi.getPortfolio(themeId.value)
    }

    const refresh = async() => {
        if (!themeId.value) {
            materials.value = []
            portfolio.value = null
            examData.value = null
            examAttempts.value = []
            examRemaining.value = 0
            return
        }

        loading.value = true
        try {
            const data = await learningApi.getLessonView(themeId.value)
            materials.value = data?.materials ?? []
            portfolio.value = data?.portfolio ?? null
            examData.value = data?.exam?.exam ?? null
            examAttempts.value = data?.exam?.attempts ?? []
            examRemaining.value = data?.exam?.remaining_attempts ?? 0
        } finally {
            loading.value = false
        }
    }

    watch(themeId, () => {
        refresh()
    }, { immediate: true })

    return {
        materials,
        portfolio,
        examData,
        examAttempts,
        examRemaining,
        filteredMaterials,
        sectionsStatus,
        sectionsCompleted,
        loading,
        getLessons,
        getLessonPortfolios,
        refresh,
    }
}
