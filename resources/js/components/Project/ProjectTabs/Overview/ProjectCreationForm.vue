<template>
    <div v-if="hasPrivilage" class="project-apply-form border-[var(--calendarBorder)]">
        <div v-if="loading" class="py-[40px] text-center text-sm opacity-70">
            フォームを読み込み中です。
        </div>
        <div v-else-if="errorMessage" class="py-[20px] text-sm text-[tomato]">
            {{ errorMessage }}
        </div>
        <div v-else-if="currentForm" class="flex flex-col gap-[20px]">
            <div class="text-[16px] font-semibold">{{ currentForm.title }}</div>
            <div v-if="currentForm.description" class="rich-wrapper" v-html="currentForm.description"></div>
            <div class="flex flex-col gap-[30px]" :key="renderKey">
                <div v-for="block in visibleBlocks" :key="block.id">
                    <SurveyBlock
                        ref="blocks"
                        :block="block"
                        :answer="answer.block_answers.find((item) => item.custom_form_block_id === block.id)"
                        file-path="/project_files"
                        @selection-change="onSelectionChange"
                    />
                </div>
            </div>
        </div>
    </div>
    <div v-else class="h-[calc(100%-115px)] w-full flex items-center justify-center">
        権限がありません
    </div>
</template>

<script setup lang="ts">
import SurveyBlock from '@/components/Survey/SurveyBlock.vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import type { CustomForm, SurveyAnswer } from '@/interface/customFormInterface'
import { computed, reactive, ref, useTemplateRef, watch } from 'vue'
import type { ComponentExposed } from 'vue-component-type-helpers'
import { useProject } from '@/composables/project'
import {
    createProjectCreationSpecData,
    getProjectCreationVisibleBlocks,
    isProjectCreationSpecData,
    type ProjectCreationSpecData,
} from './projectCreationForm'

const props = withDefaults(defineProps<{
    hasPrivilage?: boolean
    editData?: unknown
    projectTypeId?: number | null
}>(), {
    hasPrivilage: true,
    editData: undefined,
    projectTypeId: null,
})

const api = useApi()
const { ping } = useDialog()
const { selectedProject } = useProject()
const loading = ref(false)
const errorMessage = ref('')
const form = ref<CustomForm | null>(null)
const renderKey = ref(0)
const blocks = useTemplateRef<ComponentExposed<typeof SurveyBlock>[]>('blocks')
const answer = reactive<Pick<SurveyAnswer, 'block_answers' | 'target_date'>>({
    block_answers: [],
    target_date: null,
})
const selections = reactive<Record<number, { type: 'radio' | 'checkbox'; elementIds: number[] }>>({})

const effectiveEditData = computed(() => props.editData ?? selectedProject.value?.specs?.spec_data)
const effectiveProjectTypeId = computed(() => props.projectTypeId ?? selectedProject.value?.project_type_id ?? null)

const currentForm = computed(() => {
    if (form.value) return form.value
    if (isProjectCreationSpecData(effectiveEditData.value)) {
        return {
            id: effectiveEditData.value.form_id ?? 0,
            title: effectiveEditData.value.form_title,
            description: effectiveEditData.value.form_description,
            blocks: effectiveEditData.value.blocks,
        } as CustomForm
    }
    return null
})

const visibleBlocks = computed(() => {
    const targetBlocks = currentForm.value?.blocks ?? []
    return getProjectCreationVisibleBlocks(targetBlocks, selections)
})

const resetSelections = () => {
    for (const key of Object.keys(selections)) {
        delete selections[Number(key)]
    }
}

const hydrateSelectionsFromAnswer = () => {
    resetSelections()
    const targetBlocks = form.value?.blocks ?? []
    for (const block of targetBlocks) {
        if (block.type !== 'radio' && block.type !== 'checkbox') continue
        const blockAnswer = answer.block_answers.find((item) => item.custom_form_block_id === block.id)
        if (!blockAnswer) continue
        const elementIds = blockAnswer.element_answers
            ?.filter((item) => item.checked && item.custom_form_block_element_id)
            .map((item) => Number(item.custom_form_block_element_id))
            .filter(Boolean) ?? []
        if (!elementIds.length) continue
        selections[block.id] = {
            type: block.type,
            elementIds,
        }
    }
}

const hydrateFromSpec = (spec: ProjectCreationSpecData) => {
    form.value = {
        id: spec.form_id ?? 0,
        title: spec.form_title,
        description: spec.form_description,
        blocks: JSON.parse(JSON.stringify(spec.blocks)),
    } as CustomForm
    answer.block_answers = JSON.parse(JSON.stringify(spec.answer.block_answers ?? []))
    answer.target_date = spec.answer.target_date ?? null
    hydrateSelectionsFromAnswer()
    renderKey.value += 1
}

const loadActiveForm = async () => {
    if (!effectiveProjectTypeId.value) {
        form.value = null
        errorMessage.value = 'プロジェクト種別を選択してください。'
        return
    }

    loading.value = true
    errorMessage.value = ''
    const data = await api.get('/get_active_project_creation_form', { project_type_id: effectiveProjectTypeId.value })
    loading.value = false

    if (!data) {
        form.value = null
        errorMessage.value = '進行中の案件作成フォームが見つかりません。'
        return
    }

    form.value = data as CustomForm
    answer.block_answers = []
    answer.target_date = null
    resetSelections()
    renderKey.value += 1
}

watch(
    () => effectiveEditData.value,
    (value) => {
        if (isProjectCreationSpecData(value)) {
            errorMessage.value = ''
            loading.value = false
            hydrateFromSpec(value)
            return
        }
        form.value = null
        answer.block_answers = []
        answer.target_date = null
        resetSelections()
        loadActiveForm()
    },
    { immediate: true }
)
watch(
    () => effectiveProjectTypeId.value,
    () => {
        if (!isProjectCreationSpecData(effectiveEditData.value)) {
            loadActiveForm()
        }
    }
)

const onSelectionChange = (payload: { blockId: number; type: 'radio' | 'checkbox'; elementIds: number[] }) => {
    console.log(payload)
    selections[payload.blockId] = {
        type: payload.type,
        elementIds: payload.elementIds,
    }
}

const buildPayload = (): ProjectCreationSpecData | undefined => {
    if (!currentForm.value) return undefined

    const targets = blocks.value?.filter(Boolean) ?? []
    const blockAnswers = targets.map((block) => block.extractedData)
    console.log(blockAnswers)
    return createProjectCreationSpecData(currentForm.value, {
        block_answers: blockAnswers,
        target_date: answer.target_date,
    })
}

const validate = () => {
    if (!currentForm.value) {
        ping('進行中のプロジェクト作成フォームが見つかりません。')
        return { valid: false, payload: undefined }
    }

    const targets = blocks.value?.filter(Boolean) ?? []
    let valid = true

    for (const block of targets) {
        valid = block.isValid() && valid
    }
    const payload = valid ? buildPayload() : undefined
    if (!valid) {
        ping('必須項目が未入力です。')
    }
    return { valid, payload }
}

const getPayload = () => buildPayload()

defineExpose({ validate, getPayload })
</script>
