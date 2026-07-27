import { useApi } from '@/composables/api'
import type {
    LearningAnswer,
    LearningExam,
    LearningExamAttempt,
    LearningExamPayload,
    LearningLessonViewPayload,
    LearningMaterial,
    LearningMaterialVersion,
    LearningParticipantProgress,
    LearningPersonalMaterial,
    LearningPortfolio,
    LearningPreviousExperiencePayload,
    PortfolioSectionExam,
    PortfolioSectionExamResult,
    LearningSummaryAnswer,
    LearningTheme,
    LearningThemeAiConfig,
    LearningThemeCategory,
    LearningThemeState,
    LearningThemeChallengeOptions,
} from '@/types/learning'

export interface LearningPortfolioSaveRequest {
    theme_id: number | string | string[]
    params: Partial<LearningPortfolio>
}

export interface LearningLessonFormSaveRequest {
    question1: string
    question2: string
    question3: string
    answer1: string
    answer2: string
    answer3: string
    lesson_theme_id: number | string | string[]
    status: number
    form_content: string
}

export interface LearningAnswerSaveRequest {
    id?: number | null
    params: Partial<LearningAnswer> & {
        material_id: number | null
    }
}

export interface LearningExamSubmitRequest {
    lesson_theme_id: number | string | string[]
    lesson_material_id?: number | null
    answers: Array<{
        question_id: number
        option_id: number
    }>
}

export interface LearningAdminExamSaveRequest {
    lesson_theme_id: number
    lesson_material_id?: number | null
    exam_id: number | null
    title: string | null
    description: string | null
    passing_score: number
    max_attempts: number
    questions: Array<{
        id: number | null
        prompt: string
        explanation: string
        correct_explanation: string
        position: number
        options: Array<{
            id: number | null
            label: string
            is_correct: boolean
        }>
    }>
}

export interface LearningSectionUpdateRequest {
    update_content: string
    lesson_theme_id: number | string | string[]
    title: string | null
    material_id: number | string | string[]
    section_status: number
    has_case_study: number | boolean
}

export interface LearningMaterialSaveRequest {
    id?: number | null
    params: Omit<Partial<LearningMaterial>, 'lesson_theme_id' | 'priority'> & {
        lesson_theme_id: number | string | string[]
        priority: number | null
    }
}

export interface LearningThemeSaveRequest {
    id?: number | null
    params: Partial<LearningTheme>
    access_members: number[]
    category_ids?: number[]
}

export type LearningThemeAiConfigSaveRequest = Pick<LearningThemeAiConfig, 'config_key' | 'lesson_material_id' | 'model' | 'instructions' | 'settings'>

export interface LearningSummarySaveRequest {
    id?: number | null
    params: {
        lesson_material_id: number | null
        title: string
    }
    questions: Array<{
        id: number | null
        question: string | null
        content: string | null
    }>
    deleted: number[]
}
export type LearningPreviousExperience = LearningPreviousExperiencePayload

export interface LearningPersonalMaterialFeedbackRequest {
    understand: boolean
    important_point?: string | null
}

export function useLearningApi() {
    const api = useApi()

    const getLearnerThemes = async() => {
        return await api.get('/get_lesson_themes') as LearningTheme[]
    }

    const getAdminThemes = async() => {
        return await api.get('/get_learning_themes') as LearningTheme[]
    }

    const deleteTheme = async(id: number) => {
        return await api.del('/delete_learning_theme', { id }, {
            ask: 'テーマを削除しますか？',
            toast: 'テーマを削除しました。',
        })
    }

    const saveTheme = async(payload: LearningThemeSaveRequest, isEdit: boolean) => {
        return await api.post('/create_learning_theme', payload, {
            toast: isEdit ? '編集しました。' : '保存しました。',
        }) as LearningTheme
    }

    const saveThemeAiConfig = async(themeId: number, payload: LearningThemeAiConfigSaveRequest) => {
        return await api.post(`/lesson_theme/${themeId}/ai_config`, payload, {
            toast: 'AI設定を保存しました。',
        }) as LearningThemeAiConfig
    }

    const getOpenAiModels = async() => {
        return await api.get('/openai/models') as string[]
    }

    const getThemeCategories = async() => {
        return await api.get('/lesson_theme_categories') as LearningThemeCategory[]
    }

    const saveThemeCategory = async(payload: Pick<LearningThemeCategory, 'name'> & {
        id?: number | null
    }, isEdit: boolean) => {
        return await api.post('/lesson_theme_category', payload, {
            toast: isEdit ? 'カテゴリーを更新しました。' : 'カテゴリーを追加しました。',
        }) as LearningThemeCategory
    }

    const deleteThemeCategory = async(id: number) => {
        return await api.del('/lesson_theme_category', { id }, {
            ask: 'カテゴリーを削除しますか？',
            toast: 'カテゴリーを削除しました。',
        })
    }

    const setDefaultThemeCategory = async(id: number) => {
        return await api.put(`/lesson_theme_category/${id}/default`, {}, {
            toast: 'デフォルトカテゴリーを更新しました。',
        }) as LearningThemeCategory[]
    }

    const reorderThemeCategories = async(ids: number[]) => {
        return await api.put('/lesson_theme_categories/reorder', {
            ids,
        }, {
            toast: 'カテゴリーを保存しました。',
        }) as LearningThemeCategory[]
    }

    const getThemeMaterials = async(themeId: number | string, versionId?: number | null) => {
        const params: Record<string, number | string> = { lesson_theme_id: themeId }
        if (versionId) params.version_id = versionId
        return await api.get('/get_lessons', params) as LearningMaterial[]
    }

    const getLessonView = async(themeId: number | string) => {
        return await api.get('/get_lesson_view', { lesson_theme_id: themeId }) as LearningLessonViewPayload
    }

    const deleteMaterial = async(id: number) => {
        return await api.del('/lesson_remove_record', { id }, {
            ask: '削除しますか？',
            toast: '削除しました。',
        })
    }

    const getMaterialVersions = async(themeId: number | string) => {
        return await api.get(`/lesson_theme/${themeId}/material_versions`) as LearningMaterialVersion[]
    }

    const createMaterialVersion = async(themeId: number | string, payload: { copy_from?: number | null; label?: string | null }) => {
        return await api.post(`/lesson_theme/${themeId}/material_versions`, payload, {
            toast: '新しいバージョンを作成しました。',
        }) as LearningMaterialVersion
    }

    const setDefaultMaterialVersion = async(themeId: number | string, versionId: number) => {
        return await api.put(`/lesson_theme/${themeId}/material_versions/${versionId}/default`, {}, {
            toast: 'デフォルトのバージョンを変更しました。',
        })
    }

    const deleteMaterialVersion = async(themeId: number | string, versionId: number) => {
        return await api.del(`/lesson_theme/${themeId}/material_versions/${versionId}`, {}, {
            ask: 'このバージョンを削除しますか？このバージョンの教材も削除されます。',
            toast: 'バージョンを削除しました。',
        })
    }

    const getLearnerThemeState = async(themeId: number | string) => {
        return await api.get(`/lesson_theme/${themeId}/learner_state`) as LearningThemeState
    }

    const startLearningAttempt = async(themeId: number | string) => {
        return await api.post(`/lesson_theme/${themeId}/start_attempt`, {}, {
            toast: '新しい学習を開始しました。',
        })
    }

    const deleteLearningAttempt = async(themeId: number | string, portfolioId: number) => {
        return await api.del(`/lesson_theme/${themeId}/attempt/${portfolioId}`, {}, {
            ask: 'この学習を削除しますか？未完了の内容は失われます。',
            toast: '削除しました。',
        })
    }

    const getThemeChallengeOptions = async(themeId: number | string) => {
        return await api.get(`/lesson_theme/${themeId}/challenge_options`) as LearningThemeChallengeOptions
    }

    const createThemeChallenge = async(themeId: number | string, goalId: number) => {
        return await api.post(`/lesson_theme/${themeId}/challenge`, { goal_id: goalId }, {
            toast: '昇給課題を作成しました。',
        })
    }

    const deleteSummary = async(id: number) => {
        return await api.del('/lesson_remove_summary', { id }, {
            ask: '削除しますか？',
            toast: '削除しました。',
        })
    }

    const saveMaterial = async(payload: LearningMaterialSaveRequest, isEdit: boolean) => {
        return await api.post('/lesson_add_record', payload, {
            toast: isEdit ? '編集しました。' : '保存しました。',
        }) as LearningMaterial
    }

    const saveMaterialSummary = async(payload: LearningSummarySaveRequest, isEdit: boolean) => {
        return await api.post('/add_material_summary', payload, {
            toast: isEdit ? '編集しました。' : '保存しました。',
        }) as { id: number }
    }

    const getMaterial = async(materialId: number | string) => {
        return await api.get('/get_material', { id: materialId }) as LearningMaterial | null
    }

    const getPortfolio = async(themeId: number | string) => {
        return await api.post('/get_lesson_portfolio', { lesson_theme_id: themeId }) as LearningPortfolio | null
    }

    const savePortfolio = async(payload: LearningPortfolioSaveRequest) => {
        return await api.post('/save_lesson_portfolio', payload) as LearningPortfolio | null
    }

    const saveLessonForm = async(payload: LearningLessonFormSaveRequest) => {
        return await api.post('/save_lesson_form', payload)
    }

    const saveAnswer = async(payload: LearningAnswerSaveRequest, options?: Record<string, unknown>) => {
        return await api.post('/update_lesson_answer', payload, options) as LearningAnswer | null
    }

    const updateSection = async(payload: LearningSectionUpdateRequest) => {
        return await api.post('/section_update', payload)
    }

    const saveSummaryAnswers = async(answers: LearningSummaryAnswer[]) => {
        return await api.post('/save_summary_answers', { answers })
    }

    const getPortfolios = async(themeId: number | string) => {
        return await api.get('/get_portfolios_list', { theme_id: themeId }) as LearningPortfolio[]
    }

    const getAdminPortfolios = async(themeId: number | string) => {
        const response = await api.get(`/admin/learning/themes/${themeId}/progress`, { section: 'portfolio' }) as {
            portfolio_participants?: LearningPortfolio[]
        }

        return response.portfolio_participants ?? []
    }

    const getPortfolioProgress = async(themeId: number | string) => {
        const response = await api.get(`/admin/learning/themes/${themeId}/progress`, { section: 'portfolio' }) as {
            portfolio_participants?: LearningPortfolio[]
            section_exams?: PortfolioSectionExam[]
            section_exam_results?: Record<number, Record<number, PortfolioSectionExamResult>>
        }

        return {
            portfolios: response.portfolio_participants ?? [],
            sectionExams: response.section_exams ?? [],
            examResults: response.section_exam_results ?? {},
        }
    }

    const getLegacyPortfolios = async(themeId: number | string) => {
        return await api.get('/get_portfolios_list', { theme_id: themeId }) as LearningPortfolio[]
    }

    const getPortfolioView = async(themeId: number | string, id: number) => {
        return await api.get('/get_portfolio_view', { lesson_theme_id: themeId, id }) as LearningPortfolio[]
    }

    const updatePortfolioStatus = async(id: number, value: number) => {
        return await api.put('/update_portfolio_status', { id, value }, { toast: '保存しました。' })
    }

    const deleteAdminPortfolio = async(id: number) => {
        return await api.del(`/admin/learning/portfolio/${id}`, {}, {
            ask: 'このポートフォリオを削除しますか？削除は記録されます。',
            toast: '削除しました。',
        })
    }

    const getMaterialProgressList = async(themeId: number | string) => {
        const response = await api.get(`/admin/learning/themes/${themeId}/progress`, { section: 'case_study' }) as {
            case_study_participants?: LearningParticipantProgress[] | Record<string, LearningParticipantProgress>
        }
        const rows = response.case_study_participants ?? []
        if (Array.isArray(rows)) {
            return rows
        }

        return Object.values(rows)
    }

    const getLegacyMaterialProgressList = async(themeId: number | string) => {
        const response = await api.get('/get_material_list', { lesson_theme_id: themeId })
        if (Array.isArray(response)) {
            return response as LearningParticipantProgress[]
        }

        return Object.values(response ?? {}) as LearningParticipantProgress[]
    }

    const getLearningExam = async(themeId: number | string, materialId?: number | null) => {
        const params: Record<string, number | string> = { lesson_theme_id: themeId }
        if (materialId) params.lesson_material_id = materialId
        return await api.get('/learning_exam', params) as LearningExamPayload
    }

    const submitLearningExam = async(payload: LearningExamSubmitRequest) => {
        return await api.post('/learning_exam_submit', payload, {
            toast: '試験を送信しました。',
        }) as LearningExamAttempt
    }

    const getAdminExam = async(themeId: number | string, materialId?: number | null) => {
        const params: Record<string, number | string> = { lesson_theme_id: themeId }
        if (materialId) params.lesson_material_id = materialId
        return await api.get('/lesson_exam', params) as {
            exists: boolean
            exam: LearningExam | null
        }
    }

    const saveAdminExam = async(payload: LearningAdminExamSaveRequest, isEdit: boolean) => {
        return await api.post('/lesson_exam', payload, {
            toast: isEdit ? '試験を更新しました。' : '試験を追加しました。',
        })
    }

    const deleteAdminExam = async(examId: number) => {
        return await api.del('/lesson_exam', { exam_id: examId }, {
            ask: '試験を削除しますか？',
            toast: '試験を削除しました。',
        })
    }

    const getExamAttempts = async(themeId: number | string) => {
        return await api.get('/lesson_exam_attempts', { lesson_theme_id: themeId }) as LearningExamAttempt[]
    }

    const getSupportAccountId = async() => {
        return await api.get('/get_support_account') as number | string | null
    }
    const getPreviousExperience = async(themeId: number | string) => {
        return await api.get('/get_previous_experience', { lesson_theme_id: themeId }) as LearningPreviousExperiencePayload
    }

    const generatePersonalMaterial = async(themeId: number | string) => {
        return await api.post(
            `/lesson_theme/${themeId}/personal_materials/portfolio_recurring_trainee/generate`,
            {},
            { silent: true },
        ) as LearningPersonalMaterial
    }

    const savePersonalMaterialFeedback = async(themeId: number | string, payload: LearningPersonalMaterialFeedbackRequest, options?: { silent?: boolean }) => {
        return await api.post(`/lesson_theme/${themeId}/personal_materials/portfolio_recurring_trainee/feedback`, payload,
            options?.silent ? {} : { toast: '保存しました。' }) as LearningPersonalMaterial
    }

    return {
        getLearnerThemes,
        getAdminThemes,
        deleteTheme,
        saveTheme,
        saveThemeAiConfig,
        getOpenAiModels,
        getThemeCategories,
        saveThemeCategory,
        deleteThemeCategory,
        setDefaultThemeCategory,
        reorderThemeCategories,
        getThemeMaterials,
        getLessonView,
        deleteMaterial,
        getMaterialVersions,
        createMaterialVersion,
        setDefaultMaterialVersion,
        deleteMaterialVersion,
        getLearnerThemeState,
        startLearningAttempt,
        deleteLearningAttempt,
        getThemeChallengeOptions,
        createThemeChallenge,
        deleteSummary,
        saveMaterial,
        saveMaterialSummary,
        getMaterial,
        getPortfolio,
        savePortfolio,
        saveLessonForm,
        saveAnswer,
        updateSection,
        saveSummaryAnswers,
        getPortfolios,
        getAdminPortfolios,
        getPortfolioProgress,
        getLegacyPortfolios,
        getPortfolioView,
        updatePortfolioStatus,
        deleteAdminPortfolio,
        getMaterialProgressList,
        getLegacyMaterialProgressList,
        getLearningExam,
        submitLearningExam,
        getAdminExam,
        saveAdminExam,
        deleteAdminExam,
        getExamAttempts,
        getSupportAccountId,
        getPreviousExperience,
        generatePersonalMaterial,
        savePersonalMaterialFeedback,
    }
}
