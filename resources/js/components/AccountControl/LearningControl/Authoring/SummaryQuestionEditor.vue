<template>
    <div class="summary-question-editor">
        <div class="summary-question-editor__question">
            <input
                v-model="question.question"
                class="custom-o-input w-full"
                placeholder="質問"
                type="text"
            />
        </div>
        <div class="summary-question-editor__content">
            <div class="summary-question-editor__label">提示する内容</div>
            <RichEditor
                :initilaValue="question.content"
                @content-updated="emit('content-updated', index, $event)"
            />
        </div>
        <div class="summary-question-editor__actions">
            <button
                class="summary-question-editor__icon-button"
                title="質問追加"
                type="button"
                @click="emit('add-question')"
            >
                <svg
                    aria-hidden="true"
                    height="12"
                    viewBox="0 0 32 32"
                    width="12"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                </svg>
            </button>
            <button
                v-if="canRemove"
                class="summary-question-editor__icon-button"
                title="質問削除"
                type="button"
                @click="emit('remove-question', index, question.id)"
            >
                <svg
                    aria-hidden="true"
                    viewBox="0 0 16.79 2.88"
                    width="10"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path d="m1.15,2.67c2.42.14,4.85.2,7.28.2,2.43,0,4.85-.05,7.28-.25.56-.05,1.03-.5,1.08-1.08.06-.65-.43-1.23-1.08-1.29C10.86-.11,6-.05,1.15.21.54.25.04.74,0,1.36c-.05.68.47,1.27,1.15,1.31Z"/>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import RichEditor from '@/components/Global/RichEditor.vue'
import type { EditableSummaryQuestion } from './summaryEditorTypes'

defineProps<{
    question: EditableSummaryQuestion
    index: number
    canRemove: boolean
}>()

const emit = defineEmits<{
    'add-question': []
    'remove-question': [index: number, questionId: number | null]
    'content-updated': [index: number, content: string]
}>()
</script>

<style scoped>
.summary-question-editor{
    display: flex;
    align-items: center;
    flex-direction: column;
    gap: 10px;
}

.summary-question-editor__question,
.summary-question-editor__content{
    width: 100%;
}

.summary-question-editor__label{
    margin-bottom: 15px;
    font-size: 14px;
}

.summary-question-editor__actions{
    display: flex;
}

.summary-question-editor__icon-button{
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border: 0;
    background: transparent;
    cursor: pointer;
}

.summary-question-editor__icon-button svg{
    fill: var(--primary-color);
}
</style>
