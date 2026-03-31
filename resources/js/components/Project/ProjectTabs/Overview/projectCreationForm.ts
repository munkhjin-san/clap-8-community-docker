import type { CustomForm, CustomFormBlock, SurveyAnswer, SurveyBlockAnswer } from '@/interface/customFormInterface'

const resolveBlockCategoryRelations = (block: CustomFormBlock): { id: number; label: string }[] => {
    return (block.checkitemCategories
        ?? (block as CustomFormBlock & { checkitem_categories?: { id: number; label: string }[] }).checkitem_categories
        ?? [])
}

export interface ProjectCreationSpecData {
    type: 'project_creation_form'
    version: 1
    form_id: number | null
    form_title: string
    form_description: string
    blocks: CustomFormBlock[]
    answer: Pick<SurveyAnswer, 'block_answers' | 'target_date'>
    active_category_ids: number[]
    active_categories: string[]
}

type SelectionState = Record<number, { type: 'radio' | 'checkbox'; elementIds: number[] }>

const hasTextInput = (answer?: SurveyBlockAnswer | null): boolean => {
    return Boolean((answer?.text_answer || '').trim())
}

const hasCheckedElements = (answer?: SurveyBlockAnswer | null): boolean => {
    return Boolean(answer?.element_answers?.some((element) => element.checked))
}

const hasFiles = (answer?: SurveyBlockAnswer | null): boolean => {
    return Boolean(answer?.files?.length)
}

export const blockHasInput = (block: CustomFormBlock, answer?: SurveyBlockAnswer | null): boolean => {
    if (block.type === 'header') return false
    if (block.type === 'file') return hasFiles(answer)
    if (block.type === 'radio' || block.type === 'checkbox') return hasCheckedElements(answer)
    return hasTextInput(answer)
}

export const createProjectCreationSpecData = (
    form: Pick<CustomForm, 'id' | 'title' | 'description' | 'blocks'>,
    answer: Pick<SurveyAnswer, 'block_answers' | 'target_date'>
): ProjectCreationSpecData => {
    const blocks = JSON.parse(JSON.stringify(form.blocks ?? [])) as CustomFormBlock[]
    const normalizedAnswer = JSON.parse(JSON.stringify(answer ?? { block_answers: [], target_date: null })) as Pick<
        SurveyAnswer,
        'block_answers' | 'target_date'
    >

    const activeCategoryLabels = Array.from(
        new Set(
            blocks.flatMap((block) => {
                const blockAnswer = normalizedAnswer.block_answers.find((item) => item.custom_form_block_id === block.id)
                if (!blockHasInput(block, blockAnswer)) return []
                return resolveBlockCategoryRelations(block).map((category) => category.label)
            })
        )
    )
    const activeCategoryIds = Array.from(
        new Set(
            blocks.flatMap((block) => {
                const blockAnswer = normalizedAnswer.block_answers.find((item) => item.custom_form_block_id === block.id)
                if (!blockHasInput(block, blockAnswer)) return []
                return resolveBlockCategoryRelations(block).map((category) => Number(category.id)).filter(Boolean) ?? []
            })
        )
    )

    return {
        type: 'project_creation_form',
        version: 1,
        form_id: form.id ?? null,
        form_title: form.title ?? '',
        form_description: form.description ?? '',
        blocks,
        answer: normalizedAnswer,
        active_category_ids: activeCategoryIds,
        active_categories: activeCategoryLabels,
    }
}

export const isProjectCreationSpecData = (value: unknown): value is ProjectCreationSpecData => {
    if (!value || typeof value !== 'object') return false
    const candidate = value as Partial<ProjectCreationSpecData>
    return candidate.type === 'project_creation_form' && Array.isArray(candidate.blocks)
}

export const getProjectCreationVisibleBlocks = (
    blocks: CustomFormBlock[],
    selections: SelectionState
): CustomFormBlock[] => {
    const visible: CustomFormBlock[] = []
    const visibleIds = new Set<number>()

    for (const block of blocks) {
        const rawDependsOn = Array.isArray(block.depends_on) ? block.depends_on : block.depends_on ? [block.depends_on] : []
        if (!rawDependsOn.length) {
            visible.push(block)
            visibleIds.add(block.id)
            continue
        }

        const matches = rawDependsOn.some((condition) => {
            if (!condition?.block_id || !visibleIds.has(condition.block_id)) return false
            const selection = selections[condition.block_id]
            if (!selection) return false

            const expectedIds = Array.isArray(condition.element_ids) ? condition.element_ids.map((id) => Number(id)) : []
            if (!expectedIds.length) return false

            const conditionType = condition.type === 'checkbox' ? 'checkbox' : 'radio'
            if (selection.type !== conditionType) return false

            if (conditionType === 'checkbox') {
                const matchMode = condition.match === 'all' ? 'all' : 'any'
                return matchMode === 'all'
                    ? expectedIds.every((id) => selection.elementIds.includes(id))
                    : expectedIds.some((id) => selection.elementIds.includes(id))
            }

            return selection.elementIds[0] === expectedIds[0]
        })

        if (matches) {
            visible.push(block)
            visibleIds.add(block.id)
        }
    }

    return visible
}

export const getProjectCreationAnsweredBlocks = (
    spec: ProjectCreationSpecData,
    category?: string
): { block: CustomFormBlock; answer: SurveyBlockAnswer }[] => {
    const targetCategory = (category || '').trim()

    return spec.blocks
        .map((block) => ({
            block,
            answer: spec.answer.block_answers.find((item) => item.custom_form_block_id === block.id),
        }))
        .filter((item): item is { block: CustomFormBlock; answer: SurveyBlockAnswer } => {
            if (!item.answer || !blockHasInput(item.block, item.answer)) return false
            if (!targetCategory) return true
            return resolveBlockCategoryRelations(item.block).some((category) => category.label === targetCategory)
        })
}

export const getProjectCreationActiveCategories = (specData: unknown): Set<string> | null => {
    if (!isProjectCreationSpecData(specData)) return null

    const categories = specData.active_categories?.filter(Boolean) ?? []
    if (!categories.length) return null

    return new Set(categories.map((category) => category.trim()).filter(Boolean))
}

export const getProjectCreationActiveCategoryIds = (specData: unknown): Set<number> | null => {
    if (!isProjectCreationSpecData(specData)) return null

    const categoryIds = specData.active_category_ids
        ?.filter((id) => Number(id) > 0)
        .map((id) => Number(id)) ?? []

    if (!categoryIds.length) return null

    return new Set(categoryIds)
}
