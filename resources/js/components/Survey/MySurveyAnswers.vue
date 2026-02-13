<template>
<div class="text-[14px] relative">
    <div class="fixed m-auto top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" v-if="fetching">
        <div class="spinner-mini"></div>
    </div>
    <Teleport to="body">
        <MySurveyDetail 
            :answer="selectedAnswer"
            :form="selectedForm"
            @close="closeDetail"
            v-if="selectedForm"
        />
    </Teleport>
    <div class="post-header">
        <!-- <HamBurger v-if="responsive.mobile"/> -->
        <div class="post-search-wrap">
            <PostSearchBar 
                className="newChatMemberSearch" 
                customPlaceHolder="フォームを検索" 
                @search-start="onSearchStart"
            />                
        </div>            
    </div>
    <div class="p-[20px] pt-0 text-[var(--primary-color)]">
        <div v-if="surveys.length" class="forms-table-wrap">
            <div class="forms-table-outer">
                <table class="forms-table">
                    <colgroup>
                        <col style="width:220px" />
                        <col />
                        <col style="width:120px" />
                        <col style="width:110px" />
                        <col style="width:64px" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th>タイトル</th>
                            <th>説明</th>
                            <th>繰り返し</th>
                            <th class="th-right">自分の回答</th>
                            <th class="th-center">詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="form in surveys" :key="form.id">
                            <tr
                                :id="`survey_row_${form.id}`"
                                class="data-row"
                                :class="{ expanded: expandedFormId === form.id }"
                                @click="toggleExpanded(form.id)"
                            >
                                <td class="td-title" :title="form.title">{{ form.title }}</td>
                                <td>
                                    <div
                                        v-if="form.description"
                                        class="leading-snug line-clamp-2 break-words opacity-80"
                                        :title="plainTextFromHtml(form.description)"
                                    >
                                        {{ plainTextFromHtml(form.description) }}
                                    </div>
                                    <div v-else class="opacity-50">-</div>
                                </td>
                                <td class="td-repeat">{{ repeatText(form) }}</td>
                                <td class="td-count">
                                    <span class="count-pill" :class="{ 'has-draft': tempSavedCount(form) > 0 }">
                                        {{ form.survey_answers?.length ? form.survey_answers.length : 0 }}件
                                        <span
                                            v-if="tempSavedCount(form) > 0"
                                            class="draft-dot"
                                            :title="`一時保存 ${tempSavedCount(form)}件`"
                                            aria-label="一時保存あり"
                                        />
                                    </span>
                                </td>
                                <td class="td-center">
                                    <button
                                        type="button"
                                        class="row-toggle"
                                        @click.stop="toggleExpanded(form.id)"
                                        :aria-expanded="expandedFormId === form.id"
                                        aria-label="詳細を開閉"
                                    >
                                        <span class="toggle-icon" :class="{ open: expandedFormId === form.id }">
                                            <Back :size="10" />
                                        </span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-show="expandedFormId === form.id" class="detail-row">
                                <td colspan="5" class="detail-cell">
                                    <Transition name="accordion">
                                        <div v-show="expandedFormId === form.id" class="accordion-body">
                                            <div class="answers-block">
                                                <div class="answers-header">
                                                    <div class="answers-header-left">
                                                        <div class="font-semibold">自分の回答</div>
                                                        <router-link
                                                            class="new-answer-btn"
                                                            :to="{ name: 'survey-form', params: { surveyId: form.id }, query: { openFormId: form.id } }"
                                                            @click.stop
                                                        >
                                                            新規回答
                                                        </router-link>
                                                    </div>
                                                    <div class="answers-header-right">
                                                        <div v-if="tempSavedCount(form) > 0" class="draft-hint">
                                                            <span class="draft-dot-inline" aria-hidden="true" />
                                                            一時保存 {{ tempSavedCount(form) }}件
                                                        </div>
                                                        <div class="answers-count">{{ form.survey_answers?.length ? form.survey_answers.length : 0 }}件</div>
                                                    </div>
                                                </div>

                                                <div v-if="!form.survey_answers?.length" class="empty-state">回答がありません</div>

                                                <div v-else class="answers-grid">
                                                    <div v-for="answer in form.survey_answers" :key="answer.id" class="answer-card">
                                                        <div class="answer-card-top">
                                                            <div class="answer-meta">
                                                                <div class="answer-label">回答日</div>
                                                                <div class="answer-value">
                                                                    {{ answer.created_at && DateTime.fromISO(answer.created_at).toFormat('yyyy/M/d HH:mm') }}
                                                                </div>
                                                            </div>

                                                            <span
                                                                class="status-pill"
                                                                :class="{ 'is-done': answer.status == 2, 'is-draft': answer.status == 1 }"
                                                            >
                                                                {{ answer.status == 2 ? '回答済み' : answer.status == 1 ? '一時保存中' : '' }}
                                                            </span>
                                                        </div>

                                                        <div class="answer-card-body">
                                                            <div class="answer-meta">
                                                                <div class="answer-label">対象月</div>
                                                                <div class="answer-value">
                                                                    {{ answer.target_date && DateTime.fromISO(answer.target_date).toFormat('yyyy年M月') }}
                                                                </div>
                                                            </div>

                                                            <div class="answer-meta">
                                                                <div class="answer-value">
                                                                    <div class="answer-actions">
                                                                        <router-link
                                                                            class="jump-link"
                                                                            :to="{ name: 'dashboard', params: { type: 'forms', itemId: form.id }, query: { answerId: answer.id, openFormId: form.id } }"
                                                                        >
                                                                            詳細
                                                                        </router-link>
                                                                        <router-link
                                                                            v-if="answer.status == 1"
                                                                            class="jump-link"
                                                                            :to="{ name: 'survey-form', params: { surveyId: form.id }, query: { answerId: answer.id, openFormId: form.id } }"
                                                                        >
                                                                            編集
                                                                        </router-link>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </Transition>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div v-if="lastPage > 1" class="mt-3">
                <PostSearchPager
                    :possiblePage="lastPage"
                    :activePath="currentPage"
                    @setNavi="onSetNavi"
                    @setActivePage="onSetActivePage"
                />
            </div>
        </div>

        <div v-else-if="fetchLoader > 0" class="no-comment-text">現在、データはありません。</div>
    </div>
</div>
</template>
<script setup lang="ts">
import { CustomForm } from '@/interface/customFormInterface';
import { DateTime } from 'luxon';
import { computed, defineAsyncComponent, nextTick, onMounted, ref, watch } from 'vue';
import PostSearchBar from '../Post/PostSearchBar.vue';
import PostSearchPager from '../Post/PostSearchPager.vue';
import Back from '../Icons/Back.vue';
import { useRoute, useRouter } from 'vue-router';
import { useApi } from '@/composables/api';

const surveys = ref<CustomForm[]>([]);
const fetchLoader = ref(0);
const route = useRoute()
const router = useRouter()
const api = useApi()

const perPage = 10;
const currentPage = ref(1);
const lastPage = ref(1);
const expandedFormId = ref<number | null>(null);
const fetching = ref(false);

const MySurveyDetail = defineAsyncComponent(() => import('@/components/Survey/MySurveyDetail.vue'))
onMounted(() => {

    getMyForms(1);
})

const parseOpenFormId = () => {
    const raw = route.query.openFormId;
    const value = Array.isArray(raw) ? raw[0] : raw;
    const id = Number(value);
    return Number.isFinite(id) && id > 0 ? id : null;
}

const applyOpenFormIdOnce = async () => {
    if (route.params.itemId) return; // detail view open; don't interfere
    const openFormId = parseOpenFormId();
    if (!openFormId) return;

    const existsOnPage = surveys.value.some(s => s.id === openFormId);
    if (!existsOnPage) {
        await router.replace({
            name: route.name as any,
            params: route.params,
            query: { ...route.query, openFormId: undefined },
        });
        return;
    }

    expandedFormId.value = openFormId;
    await nextTick();
    document.getElementById(`survey_row_${openFormId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });

    await router.replace({
        name: route.name as any,
        params: route.params,
        query: { ...route.query, openFormId: undefined },
    });
}

const getMyForms = async (page = 1) => {
    expandedFormId.value = null;
    fetching.value = true;
    const data = await api.get('/get_my_surveys', {
        page,
        per_page: perPage,
        keyword: keyword.value ? keyword.value : undefined,
    });
    fetchLoader.value++
    fetching.value = false;

    if (!data) {
        surveys.value = [];
        currentPage.value = 1;
        lastPage.value = 1;
        return;
    }

    if (Array.isArray(data)) {
        surveys.value = data;
        currentPage.value = 1;
        lastPage.value = 1;
        return;
    }

    surveys.value = data.data ?? [];
    currentPage.value = data.current_page ?? page;
    lastPage.value = data.last_page ?? 1;

    await applyOpenFormIdOnce();
}
const tempSavedCount = (form: CustomForm) => {
    return form.survey_answers?.filter(answer => answer.status == 1).length ?? 0;
}

const repeatText = (form: CustomForm) => {
    return form.repeat_setting == 0 ? '1回のみ' : `毎月${form.repeat_day ? form.repeat_day + '日' : ''}`;
}
const plainTextFromHtml = (html: string) => {
    try {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return (doc.body?.textContent || '').replace(/\s+/g, ' ').trim();
    } catch (e) {
        return String(html || '').replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
    }
}
const selectedForm = computed(() => {
    const surveyId = route.params.itemId;
    if (!surveyId) return null;
    return surveys.value.find(survey => survey.id === Number(surveyId)) || null;
})

const selectedAnswer = computed(() => {
    const answerId = route.query.answerId;
    if (!answerId || !selectedForm.value) return null;
    return selectedForm.value.survey_answers?.find(answer => answer.id === Number(answerId)) || null;
})
const keyword = ref('');

const onSearchStart = (key: string) => {
    keyword.value = key;
}

watch(keyword, () => {
    currentPage.value = 1;
    expandedFormId.value = null;
    getMyForms(1);
})

const onSetNavi = (delta: number) => {
    const next = currentPage.value + delta;
    if (next < 1 || next > lastPage.value) return;
    currentPage.value = next;
    expandedFormId.value = null;
    getMyForms(next);
}

const onSetActivePage = (page: number) => {
    if (page < 1 || page > lastPage.value) return;
    currentPage.value = page;
    expandedFormId.value = null;
    getMyForms(page);
}

const toggleExpanded = (formId: number) => {
    expandedFormId.value = expandedFormId.value === formId ? null : formId;
}

watch(
    () => route.params.itemId,
    (itemId) => {
        const id = Number(itemId);
        if (Number.isFinite(id) && id > 0) {
            expandedFormId.value = id;
        }
    }
)

watch(
    () => route.query.openFormId,
    () => {
        applyOpenFormIdOnce();
    }
)

const closeDetail = () => {
    const openFormId = Number(route.params.itemId) || expandedFormId.value || undefined;
    router.push({
        name: 'dashboard',
        params: { type: 'forms' },
        query: openFormId ? { openFormId } : {},
    })
}
</script>
<style scoped>
.forms-table-wrap{
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.forms-table-outer{
    background: var(--background-color);
    border-radius: 0;
    overflow-x: auto;
}
.forms-table{
    width: 100%;
    min-width: 760px;
    border-collapse: collapse;
    table-layout: fixed;
}
th{
    padding: 16px 12px;
    font-size: 12px;
    font-weight: 700;
    opacity: 0.85;
    background: var(--bg3);
    text-align: left;
    border-bottom: 1px solid var(--calendarBorder);
}
td{
    padding: 16px 12px;
    font-size: 13px;
    line-height: 1.5;
    vertical-align: top;
    border-bottom: 1px solid var(--calendarBorder);
    overflow: hidden;
}
.th-right{
    text-align: right;
}
.th-center{
    text-align: center;
}
.data-row{
    cursor: pointer;
}
.data-row:hover{
    background: var(--bg3);
}
.data-row.expanded{
    background: var(--selected-background);
}
.data-row.expanded td{
    border-bottom: none;
}
.td-title{
    word-break: break-word;
}
.title-cell{
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
.chev{
    width: 14px;
    min-width: 14px;
    line-height: 1.2;
    margin-top: 2px;
    opacity: 0.8;
    transition: transform 0.18s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.chev.open{
    transform: rotate(90deg);
}
.chev :deep(svg){
    cursor: inherit;
}
.title-text{
    min-width: 0;
    word-break: break-word;
}
.td-repeat{
    white-space: nowrap;
    font-size: 12px;
    opacity: 0.9;
}
.td-count{
    text-align: right;
}
.td-center{
    text-align: center;
    vertical-align: middle;
}

.row-toggle{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: inherit;
}

.toggle-icon{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    opacity: 0.85;
    transform: rotate(-90deg);
    transition: transform 0.18s ease;
}

.toggle-icon.open{
    transform: rotate(90deg);
}
.count-pill{
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    border-radius: 999px;
    padding: 3px 10px;
    white-space: nowrap;
    position: relative;
}

.count-pill.has-draft{
    padding-right: 18px;
}

.draft-dot{
    position: absolute;
    top: 50%;
    right: 8px;
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #ef4444;
    transform: translateY(-50%);
    box-shadow: 0 0 0 2px var(--background-color);
}
.detail-row td{
    border-bottom: 1px solid var(--calendarBorder);
}
.detail-cell{
    padding: 0;
    background: var(--selected-background);
}
.accordion-body{
    padding: 12px;
}
.answers-block{
    border-top: 1px solid var(--calendarBorder);
    padding-top: 12px;
}
.answers-header{
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
    padding: 0 4px;
}

.answers-header-left{
    display: inline-flex;
    align-items: baseline;
    gap: 10px;
    min-width: 0;
}

.new-answer-btn{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 28px;
    padding: 0 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    background: var(--primary-color);
    color: var(--background-color);
}

.new-answer-btn:hover{
    opacity: 0.9;
}

.answers-header-right{
    display: inline-flex;
    align-items: baseline;
    gap: 10px;
    min-width: 0;
}

.draft-hint{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #ef4444;
    white-space: nowrap;
}

.draft-dot-inline{
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #ef4444;
    box-shadow: 0 0 0 2px var(--selected-background);
}
.answers-count{
    font-size: 12px;
    opacity: 0.75;
    white-space: nowrap;
}
.empty-state{
    margin-top: 10px;
    border: 1px dashed var(--formBorder);
    background: var(--bg3);
    border-radius: 10px;
    padding: 12px;
    font-size: 13px;
    opacity: 0.9;
}
.answers-grid{
    margin-top: 10px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 12px;
}

.answer-card{
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    border-radius: 10px;
    overflow: hidden;
}

.answer-card-top{
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: var(--bg3);
    border-bottom: 1px solid var(--formBorder);
}

.answer-card-body{
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px 12px;
}

.answer-meta{
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.answer-label{
    font-size: 12px;
    font-weight: 700;
    opacity: 0.75;
}

.answer-value{
    font-size: 13px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}
.answers-table{
    margin-top: 10px;
    border-top: 1px solid var(--formBorder);
    background: var(--background-color);
}
.answers-head{
    display: grid;
    grid-template-columns: 180px 140px 120px 1fr;
    background: var(--bg3);
    border-bottom: 1px solid var(--formBorder);
}
.ath{
    padding: 10px 12px;
    font-size: 12px;
    font-weight: 700;
    opacity: 0.85;
}
.answers-row{
    display: grid;
    grid-template-columns: 180px 140px 120px 1fr;
    border-bottom: 1px solid var(--formBorder);
}
.answers-row:last-child{
    border-bottom: none;
}
.atd{
    padding: 10px 12px;
    font-size: 13px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}
.status-pill{
    font-size: 12px;
    font-weight: 600;
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    padding: 3px 10px;
    border-radius: 999px;
    white-space: nowrap;
}

.status-pill.is-draft{
    border-color: #ef4444;
    color: #ef4444;
    background: var(--background-color);
}
.answer-actions{
    display: flex;
    gap: 12px;
    font-size: 13px;
    white-space: nowrap;
}

.accordion-enter-active,
.accordion-leave-active{
    transition: max-height 0.25s ease, opacity 0.2s ease;
    overflow: hidden;
}
.accordion-enter-from,
.accordion-leave-to{
    max-height: 0;
    opacity: 0;
}
.accordion-enter-to,
.accordion-leave-from{
    max-height: 1200px;
    opacity: 1;
}
@media screen and (max-width: 959px) {
    .forms-table{
        min-width: 760px;
    }
}
</style>
