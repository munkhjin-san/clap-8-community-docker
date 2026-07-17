<template>
    <div class="exam-question-editor">
        <div class="exam-question-editor__head">
            <div class="form-lbl text-sm">設問 {{ index + 1 }}</div>
            <button
                v-if="canRemove"
                class="custom-remove-btn"
                type="button"
                @click="emit('remove-question', index)"
            >
                削除
            </button>
        </div>
        <textarea
            v-model="question.prompt"
            class="custom-o-input w-full mt-[10px] mb-2 !box-border"
            placeholder="問題文"
        ></textarea>
        <textarea
            v-model="question.explanation"
            class="custom-o-input w-full !box-border"
            placeholder="解説（任意）"
        ></textarea>
        <div class="exam-question-editor__option-head">
            <div class="form-lbl text-xs text-gray">選択肢</div>
            <button
                class="custom-add-btn"
                type="button"
                @click="emit('add-option', index)"
            >
                ＋ 選択肢を追加
            </button>
        </div>
        <div class="exam-question-editor__options">
            <div
                v-for="(option, optionIndex) in question.options"
                :key="option.uuid"
                class="exam-question-editor__option-row"
            >
                <input
                    v-model="option.label"
                    class="custom-o-input w-full"
                    placeholder="選択肢"
                />
                <label class="exam-question-editor__option-check">
                    <input
                        type="radio"
                        :name="`question-${question.uuid}`"
                        :checked="option.is_correct"
                        @change="emit('set-correct-option', index, optionIndex)"
                    >
                    <span>正解</span>
                </label>
                <button
                    v-if="question.options.length > 2"
                    class="custom-remove-btn"
                    type="button"
                    @click="emit('remove-option', index, optionIndex)"
                >
                    削除
                </button>
            </div>
        </div>
        <textarea
            v-model="question.correct_explanation"
            class="custom-o-input w-full !box-border mt-[10px]"
            placeholder="正答の解説（任意）"
        ></textarea>
    </div>
</template>

<script setup lang="ts">
import type { EditableExamQuestion } from './examEditorTypes'

defineProps<{
    question: EditableExamQuestion
    index: number
    canRemove: boolean
}>()

const emit = defineEmits<{
    'remove-question': [index: number]
    'add-option': [questionIndex: number]
    'remove-option': [questionIndex: number, optionIndex: number]
    'set-correct-option': [questionIndex: number, optionIndex: number]
}>()
</script>

<style scoped>
.exam-question-editor{
    border: solid 1px var(--bg3);
    padding: 15px;
    background: var(--bg3);
}

.exam-question-editor__head,
.exam-question-editor__option-head{
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.exam-question-editor__option-head{
    margin-top: 10px;
}

.exam-question-editor__options{
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 10px;
    white-space: nowrap;
}

.exam-question-editor__option-row{
    display: flex;
    align-items: center;
    gap: 10px;
}

.exam-question-editor__option-check{
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
}

.custom-add-btn{
    font-size: 12px;
    color: var(--primary-color);
    cursor: pointer;
    border: none;
    background: transparent;
}

.custom-remove-btn{
    font-size: 12px;
    color: tomato;
    cursor: pointer;
    border: none;
    background: transparent;
}
</style>
