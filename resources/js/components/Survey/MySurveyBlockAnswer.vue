<template>
    <div class="mt-[15px] ml-[15px]">
        <div class="leading-normal whitespace-break-spaces" v-if="simpleTypes.includes(block.type)">{{ blockAnswer?.text_answer }}</div>
        <div v-else-if="block.type === 'checkbox' || block.type === 'radio'" class="flex flex-col gap-[10px]">
            <div v-for="(el, index) in block.elements" :key="index">
                <label class="flex items-start gap-[10px] text-sm" :class="{'opacity-50': !isChecked(el)}">
                    <input type="checkbox" :class="[block.type === 'checkbox' ? 'custom-f-checkbox' : 'custom-f-radio', 'mt-[2px]']" :checked="isChecked(el)" disabled>
                    <div class="leading-normal whitespace-break-spaces min-w-0">{{ el.value }}</div>
                </label>
                <div v-if="subTextAnswer(el)" class="mt-[10px] text-[gray] text-[12px] ml-[10px]">{{ subTextAnswer(el) }}</div>
                <Files v-if="el.files?.length" class="mt-[10px] ml-[10px]" :items="el.files" :path="'survey_files'"/>
            </div>
        </div>
        <Files v-else-if="block.type == 'file'" :items="blockAnswer?.files" :path="'survey_files'"/>
    </div>
</template>
<script setup lang="ts">
import { CustomFormBlock, CustomFormBlockElement, SurveyBlockAnswer } from '@/interface/customFormInterface';
import 'styles/customForm.css'
import Files from '@/components/Global/Files.vue';

const props = defineProps<{
    blockAnswer: SurveyBlockAnswer | null;
    block: CustomFormBlock
}>();
const simpleTypes = ['multitext', 'singletext', 'date', 'time', 'select']
const isChecked = (element: CustomFormBlockElement) => {
    return props.blockAnswer?.element_answers.some(answer => answer.custom_form_block_element_id === element.id);
}
const subTextAnswer = (element: CustomFormBlockElement) => {
    const answer = props.blockAnswer?.element_answers.find(answer => answer.custom_form_block_element_id === element.id);
    return answer ? answer.sub_text : '';
}

</script>
