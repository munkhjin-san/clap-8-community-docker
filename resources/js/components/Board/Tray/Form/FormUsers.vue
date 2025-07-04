<template>
<Modal @close="emit('close')">
    <template #title>
        <p>フォームの該当者</p>
    </template>
    <template #content>
        <div class="flex flex-col gap-[10px]">
            <div v-for="user in form.users">
                <div class="flex items-center gap-[10px]">
                    <UserPanel disable-instant :user="user" with-name/>
                    <div class="text-xs">（回答：{{userAnswers(user).length}}回）</div>
                </div>
                
                <div class="flex flex-col gap-4 text-sm mt-4">
                    <div v-for="answer in userAnswers(user)" class="flex p-2.5 bg-[var(--bg3)] justify-between">
                        <div class="flex flex-col gap-1">   
                            <div>回答日付：<span class="text-[gray] text-[12px]">{{ answer.updated_at && DateTime.fromISO(answer.updated_at).toFormat('yyyy/M/d HH:mm') }}</span></div>
                            <div :class="answer.status == 2 ? 'text-[green]' : answer.status == 1 ? 'text-[orange]' : ''">{{ answer.status == 2 ? '回答済み' : answer.status == 1 ? '一時保存中' : '' }}</div>
                        </div>
                        
                        <div v-if="hasPrivilage(answer)" @click="userAnswer = answer" class="jump-link">閲覧</div>
                        <div v-if="editable(answer)" @click="answer.id && emit('editAnswer', form, answer.id)" class="jump-link">編集</div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</Modal>
<MySurveyDetail v-if="userAnswer" :answer="userAnswer" :form="form" @close="userAnswer = null"/>
<!-- <Modal v-if="userAnswer" @close="userAnswer = null" style="z-index:45">
    <template #title>
        <p>{{userAnswer.user?.name}}: 回答内容</p>
    </template>
    <template #content>
        <div>
            <div v-for="(block, index) in form.blocks" class="mb-4">
                <div class="text-sm leading-normal">Q{{index + 1}}: {{ block.question }}</div>
                <div class="ml-[10px] mt-[10px]">                                                               
                    <div v-if="simpleTypes.includes(block.type)">
                        <div class="flex flex-col gap-[10px]">
                            <div v-if="userBlockAnswer(block.id)" class="flex items-center gap-[10px]">
                                <div class="ml-[10px] text-[13px]">{{ userBlockAnswer(block.id)?.text_answer }}</div>
                                <Files v-if="block.type == 'file'" :items="userBlockAnswer(block.id)?.files" :path="'survey_files'"/>
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <div class="flex flex-col gap-[20px]">
                            <div v-for="element in block.elements" class="flex flex-col gap-[10px]">
                                <div>
                                    
                                </div>

                                <div class="m-[10px] ml-[10px] flex flex-col gap-[10px]">
                                    <div v-for="el_answer in element?.answers?.filter(a => a.user_id == userAnswer?.user_id)" class="flex items-center gap-[10px] text-[13px]">
                                        <UserPanel v-if="el_answer.user" size="25" :user="el_answer.user" disable-instant with-name>
                                            <template v-if="el_answer.sub_text" #details>
                                                <div class="text-[13px] mt-[5px] ml-[10px] color-[gray]">{{ el_answer.sub_text }}</div>
                                            </template>
                                        </UserPanel>
                                    </div>
                                </div>
                                <div v-if="userBlockElementAnswer(block.id, Number(element.id))">
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

</Modal> -->
</template>
<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import MySurveyDetail from '@/components/Survey/MySurveyDetail.vue';
import { CustomForm, CustomFormUser, SurveyAnswer } from '@/interface/customFormInterface';
import { useAuthUserStore } from '@/store/auth';
import { DateTime } from 'luxon';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
const props = defineProps<{
    form: CustomForm
}>();
const emit = defineEmits<{
    'close': []
    'editAnswer': [form: CustomForm, answerId: number]
}>();
const repeative = computed(() => {
    return props.form.repeat_setting == 1;
});
const auth = useAuthUserStore()

const router = useRouter()

const userAnswer = ref<SurveyAnswer | null>(null)
const userAnswers = (user: CustomFormUser) => {
    const answers = props.form.survey_answers?.filter(a => a.user_id == user.id);
    return answers && answers.length > 0 ? answers : [];

}


const hasPrivilage = (answer: SurveyAnswer) => {
    return (answer.user_id == auth.id || props.form.admins?.some(admin => admin.id == auth.id) || [608, 610].includes(Number(auth.activeUser.id))) && answer.status == 2;
}
const editable = (answer: SurveyAnswer) => {
    return answer.status == 1 && (answer.user_id == auth.activeUser.id);
}
</script>