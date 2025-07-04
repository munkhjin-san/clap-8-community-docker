<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>{{answer?.user?.name}}: 回答内容</p>
        </template>
        <template #content>
            <div v-if="answer && form">
                <div class="si-box" v-for="(block, index) in form.blocks" :key="index">
                    <div class="text-sm leading-normal font-semibold">Q{{index + 1}}: {{ block.question }}</div>
                    <MySurveyBlockAnswer 
                        :block="block"
                        :blockAnswer="blockAnswer(block)"
                    />
                </div>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { CustomForm, CustomFormBlock, SurveyAnswer } from '@/interface/customFormInterface';
import Modal from '../Global/Modal.vue';
import { useRouter } from 'vue-router';
import MySurveyBlockAnswer from './MySurveyBlockAnswer.vue';
const props = defineProps<{
    answer: SurveyAnswer | null;
    form: CustomForm | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();
const router = useRouter()

const blockAnswer = (block: CustomFormBlock) => {
    const targetAnswer = props.answer?.block_answers.find(answer => answer.custom_form_block_id === block.id);
    return targetAnswer ? targetAnswer : null;
}
</script>