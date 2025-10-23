<template>
<div class="h-full w-full overflow-y-auto text-[14px]">
    <Transition name="modalFade">
        <RouterView 
            :answer="selectedAnswer"
            :form="selectedForm"
            @close="router.push({ name: 'survey-answers' })"
        />
    </Transition>
    <div class="post-header sticky top-0 bg-[var(--bg2)] z-[6]">
        <HamBurger v-if="responsive.mobile"/>
        <div class="post-search-wrap">
            <PostSearchBar 
                className="newChatMemberSearch" 
                customPlaceHolder="フォームを検索" 
                @search-start="(key) => keyword = key"
            />                
        </div>            
    </div>
    <div class="flex flex-col gap-[20px] p-[20px] pt-0">
        <div v-for="form in searchResult" class="bg-[var(--background-color)] p-[20px] flex flex-col gap-[20px] color: var(--primary-color);">
            <div class="flex flex-col gap-[10px]">
                <div class="font-semibold">タイトル</div>
                <div>{{ form.title }}</div>
            </div>
            <div class="flex flex-col gap-[10px]">
                <div class="font-semibold">URL</div>
                <div><a class="jump-link" target="_blank" :href="urlGenerator(form)">{{ urlGenerator(form) }}</a></div>
            </div>
            <div class="flex flex-col gap-[10px]">
                <div class="font-semibold">繰り返し設定</div>
                <div>{{ form.repeat_setting == 0 ? '1回のみ' : `毎月${form.repeat_day ? form.repeat_day + '日' : ''}` }}</div>
            </div>
            <div>
                <p class="font-semibold">自分の回答</p>
                <table class="mt-[15px]">
                    <thead>
                        <th>日付</th>
                        <th>対象月</th>
                        <th>ステータス</th>
                        <th>詳細</th>
                    </thead>
                    <tbody>
                        <template v-if="!form.survey_answers?.length">
                            <tr>
                                <td colspan="4" class="text-[gray] !justify-center">回答がありません</td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr v-for="answer in form.survey_answers" :key="answer.id">
                                <td data-label="日付">{{ answer.created_at && DateTime.fromISO(answer.created_at).toFormat('yyyy/M/d HH:mm') }}</td>
                                <td data-label="対象月">{{ answer.target_date && DateTime.fromISO(answer.target_date).toFormat('yyyy年M月') }}</td>
                                <td data-label="ステータス">{{ answer.status == 2 ? '回答済み' : answer.status == 1 ? '一時保存中' : '' }}</td>
                                <td data-label="詳細">
                                    <div class="flex gap-[10px] justify-center">
                                        <CommandButton :buttons="[
                                            {title: '詳細', action: () => router.push({ name: 'survey-answers-detail', query: { surveyId: form.id, answerId: answer.id }})},
                                        ]"/>
                                        <CommandButton v-if="answer.status == 1" :buttons="[
                                            {title: '編集', action: () => router.push({ name: 'survey-form', params:{surveyId: form.id},  query: {  answerId: answer.id }})}
                                        ]"/>
                                    </div>

                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <!-- <div v-for="answer in form.survey_answers" :key="answer.id">
                    
                </div> -->
            </div>
        </div>        
    </div>
    <div class="no-comment-text" v-if="fetchLoader > 0 && !surveys.length">現在、データはありません。</div>
</div>
</template>
<script setup lang="ts">
import { CustomForm } from '@/interface/customFormInterface';
import { DateTime } from 'luxon';
import { computed, onMounted, ref } from 'vue';
import HamBurger from '../Global/HamBurger.vue';
import PostSearchBar from '../Post/PostSearchBar.vue';
import { useResponsive } from '@/store/responsive';
import { useRoute, useRouter } from 'vue-router';
import CommandButton from '../Global/CommandButton.vue';
import { useApi } from '@/composables/api';

const surveys = ref<CustomForm[]>([]);
const responsive = useResponsive()
const fetchLoader = ref(0);
const route = useRoute()
const router = useRouter()
const api = useApi()
onMounted(() => {
    getMyForms();
})

const getMyForms = async () => {
    const data = await api.get('/get_my_surveys');
    fetchLoader.value ++
    surveys.value = data;
}
const urlGenerator = (form: CustomForm) => {
    return `${window.location.origin}/survey/${form.id}`;
}
const selectedForm = computed(() => {
    const surveyId = route.query.surveyId;
    if (!surveyId) return null;
    return surveys.value.find(survey => survey.id === Number(surveyId)) || null;
})

const selectedAnswer = computed(() => {
    const answerId = route.query.answerId;
    if (!answerId || !selectedForm.value || !selectedForm.value) return null;
    return selectedForm.value.survey_answers?.find(answer => answer.id === Number(answerId)) || null;
})
const keyword = ref('');

const searchResult = computed(() => {
    if(!keyword.value) return surveys.value;
    return surveys.value.filter(survey => `${survey.title} ${survey.description}`.toLowerCase().includes(keyword.value.toLowerCase()));
});
</script>
<style scoped>
table {
    width: 100%;
    border-collapse: collapse;
}
td, th{
    font-size: 13px;
    padding: 10px;
    border: 1px solid var(--formBorder);
    text-align: center;
    font-weight: normal;
}
@media screen and (max-width: 959px) {
    thead{
        display: none;
    }
    td {
        display: flex;
        align-items: start;
        justify-content: space-between;
        
    }
    td:not(:last-child) {
        border-bottom: none;
    }
    td::before {
        content: attr(data-label);
    }
    tbody{
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

}
</style>
