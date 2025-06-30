<template>
<Modal @close="emit('close')">
    <template #title>
        <p>フォームの該当者</p>
    </template>
    <template #content>
        <div class="flex flex-col gap-[10px]">
            <div v-for="user in form.users">
                <UserPanel disable-instant :user="user" with-name>
                    <template #details>
                        <div class="text-[12px] ml-[10px] mt-[5px]">
                            <div class="jump-link">回答（{{userAnswers(user).length}}）</div>
                        </div>
                    </template>
                </UserPanel>
            </div>
        </div>
    </template>
</Modal>
</template>
<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import { CustomForm, CustomFormUser } from '@/interface/customFormInterface';
import { computed } from 'vue';
const props = defineProps<{
    form: CustomForm
}>();
const emit = defineEmits<{
    'close': []
}>();
const repeative = computed(() => {
    return props.form.repeat_setting == 1;
});
const userAnswers = (user: CustomFormUser) => {
    const answers = props.form.survey_answers?.filter(a => a.user_id == user.id);
    return answers && answers.length > 0 ? answers : [];

}
</script>