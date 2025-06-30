<template>
<Modal @close="emit('close')">
    <template #title>
        <p>回答: {{ form.title }}</p>
    </template>
    <template #content>
        <div class="relative">
            <CustomFormDetail :form="form" mode="board"/>
        </div>

    </template>
</Modal>
</template>
<script setup lang="ts">
import CustomFormDetail from '@/components/AccountControl/CustomForm/CustomFormDetail.vue';
import Modal from '@/components/Global/Modal.vue';
import { CustomForm } from '@/interface/customFormInterface';
const emit = defineEmits<{
    'close': []
}>();

const props = defineProps<{
    form: CustomForm
}>();
</script>