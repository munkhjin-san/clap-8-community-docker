<template>
    <div>
        <div class="si-box lesson-material-settings__priority">
            <div>
                <div
                    v-for="priorityOption in priorityOptions"
                    :key="priorityOption.value"
                    class="lesson-material-settings__priority-row"
                >
                    <input
                        :id="String(priorityOption.value)"
                        v-model="priority"
                        class="fish-eye"
                        name="answer"
                        type="radio"
                        :value="priorityOption.value"
                    >
                    <label
                        class="lesson-material-settings__priority-label"
                        :for="String(priorityOption.value)"
                    >
                        {{ priorityOption.content }}
                    </label>
                </div>
                <span
                    v-if="priorityError"
                    class="form-error lesson-material-settings__error"
                >
                    {{ priorityError }}
                </span>
                <p class="form-helper lesson-material-settings__helper">
                    「ヘッダー」はレッスンページ上部の自由記述エリア、「セクション」は下部のカード一覧として表示されます。
                </p>
            </div>
        </div>

        <div
            v-if="hasCaseStudy && !isHeader && priority !== null"
            class="si-box"
        >
            <div class="lesson-material-settings__section-title">タイプ</div>
            <OptionSelector
                v-model="materialType"
                :initialValue="materialType"
                :options="materialTypeOptions"
                unit=""
            />
            <p class="form-helper lesson-material-settings__helper">
                {{ materialTypeDescription }}
            </p>
        </div>

        <div
            v-if="isHeader"
            class="si-box"
        >
            <p class="form-helper lesson-material-settings__helper lesson-material-settings__helper--flat">
                ヘッダーはイントロダクションとして表示されるため、理解依頼・質問依頼・理解チェックは利用できません。
            </p>
        </div>

        <div
            v-if="!isHeader && !hasCaseStudy"
            class="si-box"
        >
            <div class="switchLabel">
                <p class="form-lbl lesson-material-settings__switch-label">「理解」依頼</p>
            </div>
            <div class="selectSwitchArea lesson-material-settings__switch">
                <input
                    id="for_understand"
                    v-model="hasUnderstand"
                    type="checkbox"
                    :disabled="hasQuestion || hasExam"
                >
                <label
                    for="for_understand"
                    class="cursor-pointer lesson-material-settings__toggle-label"
                    :class="{ 'disabled-toggle': hasQuestion || hasExam }"
                >
                    <span></span>
                    <div class="switch-toggle"></div>
                </label>
            </div>
            <p class="form-helper lesson-material-settings__helper">
                ONにすると受講者へ「理解したか」を回答してもらい、完了しない限り次の要素に進めません。
            </p>
        </div>

        <div
            v-if="!isHeader"
            class="si-box"
        >
            <div class="switchLabel">
                <p class="form-lbl lesson-material-settings__switch-label">「質問」依頼</p>
            </div>
            <div class="selectSwitchArea lesson-material-settings__switch">
                <input
                    id="for_question"
                    v-model="hasQuestion"
                    type="checkbox"
                    :disabled="hasUnderstand || hasExam"
                >
                <label
                    for="for_question"
                    class="cursor-pointer lesson-material-settings__toggle-label"
                    :class="{ 'disabled-toggle': hasUnderstand || hasExam }"
                >
                    <span></span>
                    <div class="switch-toggle"></div>
                </label>
            </div>
            <p class="form-helper lesson-material-settings__helper">
                ONにすると受講者へ質問投稿を求めるタスクが表示されます。理解依頼と同時に設定することはできません。
            </p>
        </div>

        <div
            v-if="!isHeader"
            class="si-box"
        >
            <div class="switchLabel">
                <p class="form-lbl lesson-material-settings__switch-label">「試験」依頼</p>
            </div>
            <div class="selectSwitchArea lesson-material-settings__switch">
                <input
                    id="for_exam"
                    v-model="hasExam"
                    type="checkbox"
                    :disabled="hasUnderstand || hasQuestion"
                >
                <label
                    for="for_exam"
                    class="cursor-pointer lesson-material-settings__toggle-label"
                    :class="{ 'disabled-toggle': hasUnderstand || hasQuestion }"
                >
                    <span></span>
                    <div class="switch-toggle"></div>
                </label>
            </div>
            <p class="form-helper lesson-material-settings__helper">
                ONにすると受講者はこのセクションを完了する前に試験に合格する必要があります。理解依頼・質問依頼と同時に設定することはできません。
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import OptionSelector from '@/components/Form/OptionSelector.vue'
import { LEARNING_MATERIAL_TYPES, LESSON_MATERIAL_PRIORITY } from '@/config/learning'

withDefaults(defineProps<{
    hasCaseStudy: boolean
    isHeader: boolean
    materialTypeDescription: string
    priorityError?: string
}>(), {
    priorityError: '',
})

const priority = defineModel<number | null>('priority', { required: true })
const materialType = defineModel<string>('materialType', { required: true })
const hasUnderstand = defineModel<boolean>('hasUnderstand', { required: true })
const hasQuestion = defineModel<boolean>('hasQuestion', { required: true })
const hasExam = defineModel<boolean>('hasExam', { required: true })

const priorityOptions = [
    { value: LESSON_MATERIAL_PRIORITY.HEADER, content: 'ヘッダー' },
    { value: LESSON_MATERIAL_PRIORITY.SECTION, content: 'セクション' },
]
const materialTypeOptions = [
    LEARNING_MATERIAL_TYPES.BASIC,
    LEARNING_MATERIAL_TYPES.CASE_STUDY,
]
</script>

<style scoped>

.lesson-material-settings__priority-row{
    display: flex;
    align-items: center;
    padding: 5px 0;
}

.lesson-material-settings__priority-label{
    margin-left: 10px;
    cursor: pointer;
}

.lesson-material-settings__section-title{
    margin-bottom: 15px;
    font-size: 14px;
}

.lesson-material-settings__switch{
    display: flex;
    width: 100%;
}

.lesson-material-settings__switch-label{
    white-space: nowrap;
    font-size: 14px;
}

.lesson-material-settings__toggle-label{
    min-width: 80px;
}

.lesson-material-settings__helper{
    margin-top: 5px;
    font-size: 12px;
    color: gray;
}

.lesson-material-settings__helper--flat{
    margin: 0;
}

.lesson-material-settings__error{
    font-size: 11px;
    color: tomato;
}
</style>
