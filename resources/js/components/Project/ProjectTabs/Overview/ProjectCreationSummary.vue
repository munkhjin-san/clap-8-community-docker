<template>
    <div class="summary-root">
        <div v-if="rows.length" class="summary-list">
            <div v-for="row in rows" :key="row.key" class="summary-item">
                <p class="summary-label">{{ row.label }}</p>
                <p v-if="row.value" class="summary-value">{{ row.value }}</p>
                <Files
                    v-if="row.files?.length"
                    class="mt-2"
                    :items="row.files"
                    path="project_files"
                />
            </div>
        </div>
        <p v-else class="summary-empty">該当項目はありません</p>
    </div>
</template>

<script setup lang="ts">
import Files from '@/components/Global/Files.vue'
import type { CustomFormBlock, SurveyBlockAnswer } from '@/interface/customFormInterface'
import { computed } from 'vue'
import {
    getProjectCreationAnsweredBlocks,
    isProjectCreationSpecData,
} from './projectCreationForm'

type SummaryRow = {
    key: string
    label: string
    value?: string
    files?: SurveyBlockAnswer['files']
}

const props = defineProps<{
    category: string
    editData?: unknown
}>()

const formatAnswer = (block: CustomFormBlock, answer: SurveyBlockAnswer) => {
    if (answer.text_answer?.trim()) {
        return answer.text_answer.trim()
    }

    const elementValues = answer.element_answers
        ?.filter((item) => item.checked)
        .map((item) => {
            const subText = item.sub_text?.trim()
            const elementValue = block.elements.find(
                (element) => Number(element.id) === Number(item.custom_form_block_element_id)
            )?.value ?? item.element?.value ?? ''
            return subText ? `${elementValue} ${subText}`.trim() : elementValue
        })
        .filter(Boolean)

    return elementValues?.length ? elementValues.join(' / ') : ''
}

const rows = computed<SummaryRow[]>(() => {
    if (!isProjectCreationSpecData(props.editData)) return []

    return getProjectCreationAnsweredBlocks(props.editData, props.category).map(({ block, answer }) => ({
        key: `${block.id}`,
        label: block.question || '未入力',
        value: block.type === 'file' ? undefined : formatAnswer(block, answer) || '未入力',
        files: block.type === 'file' ? answer.files : undefined,
    }))
})
</script>

<style scoped>
.summary-root {
    margin-top: 8px;
}
.summary-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.summary-item {
    padding: 10px;
    border: 1px solid var(--calendarBorder);
    border-radius: 6px;
    background: var(--background-color);
}
.summary-label {
    font-size: 12px;
    opacity: 0.75;
    margin-bottom: 4px;
}
.summary-value {
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
    white-space: pre-line;
}
.summary-empty {
    font-size: 13px;
    opacity: 0.7;
}
</style>
