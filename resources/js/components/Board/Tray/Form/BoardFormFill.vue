<template>
<Modal persist @close="emit('close', false)">
<template #content>
<SurveyForm :survey="form" :answer-id="editId" @saved="emit('close', true)"/>
</template>
</Modal>
</template>
<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue';
import SurveyForm from '@/components/Survey/SurveyForm.vue';
import { CustomForm } from '@/interface/customFormInterface';

const props = defineProps<{
    form: CustomForm
    editId?: number | null
}>();
const emit = defineEmits<{
    'close': [flag: boolean]
}>();
</script>