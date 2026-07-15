<template>
    <div class="pt-table-wrap scrollable">
        <table class="pt-table">
            <thead>
                <tr>
                    <th class="pt-th pt-th--name">氏名</th>
                    <th class="pt-th pt-th--path">学習方法</th>
                    <th class="pt-th">ステータス</th>
                    <th class="pt-th pt-th--actions">操作</th>
                </tr>
            </thead>
            <tbody
                v-for="group in userGroups"
                :key="group.userId"
                class="pt-group"
            >
                <tr
                    v-for="(portfolio, idx) in group.attempts"
                    :key="portfolio.id"
                    class="pt-row"
                >
                    <td
                        v-if="idx === 0"
                        class="pt-td pt-td--name"
                        :rowspan="group.attempts.length"
                    >
                        {{ group.user?.name }}
                    </td>
                    <td class="pt-td pt-td--path">
                        <span class="pt-pill">{{ getPortfolioPathLabel(portfolio.path) }}</span>
                        <span class="pt-attempt">{{ portfolio.attempt_no ?? 1 }}回目</span>
                    </td>
                    <td class="pt-td">
                        <div class="pt-status">
                            <span
                                v-for="stage in stageChips(portfolio)"
                                :key="stage.value"
                                class="pt-pill"
                                :class="stage.done ? 'pt-pill--done' : 'pt-pill--pending'"
                            >
                                <span class="pt-pill__dot" />{{ stage.label }}
                            </span>
                        </div>
                    </td>
                    <td class="pt-td pt-td--actions">
                        <div class="pt-actions">
                            <button
                                class="pt-btn pt-btn--detail"
                                type="button"
                                @click="openDetail(portfolio)"
                            >
                                詳細
                            </button>
                            <button
                                v-if="auth.isAdmin"
                                class="pt-btn pt-btn--delete"
                                type="button"
                                @click="emit('delete-portfolio', portfolio.id)"
                            >
                                削除
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <Modal
            v-if="detail"
            size="large"
            @close="detail = null"
        >
            <template #title>
                <div class="pt-detail-title">
                    <span class="pt-detail-title__name">{{ detail.user?.name }}</span>
                    <span class="pt-pill pt-detail-title__meta">{{ getPortfolioPathLabel(detail.path) }}・{{ detail.attempt_no ?? 1 }}回目</span>
                    <span class="pt-pill pt-detail-title__meta">{{ getPortfolioStatusLabel(portfolioStatus(detail)) }}</span>
                </div>
            </template>

            <template #content>
                <div class="pt-detail">
                    <section
                        v-if="statusStages(detail).length"
                        class="pt-detail__section"
                    >
                        <p class="pt-detail__heading">進捗ステータス</p>
                        <div class="pt-detail__status-rows">
                            <div
                                v-for="stage in statusStages(detail)"
                                :key="stage.value"
                                class="pt-detail__status-row"
                            >
                                <span class="pt-pill pt-pill--done">
                                    <span class="pt-pill__dot" />{{ stage.label }}
                                </span>
                                <button
                                    class="pt-btn pt-btn--rollback"
                                    type="button"
                                    @click="rollback(detail, stage.value)"
                                >
                                    差し戻す
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="pt-detail__section">
                        <p class="pt-detail__heading">ディスカッション用ポートフォリオ</p>
                        <p v-if="detail.portfolio_title" class="pt-detail__subtitle">{{ detail.portfolio_title }}</p>
                        <p class="pt-detail__text">{{ detail.content || '—' }}</p>
                    </section>

                    <section class="pt-detail__section">
                        <p class="pt-detail__heading">本ポートフォリオ</p>
                        <p v-if="detail.public_title" class="pt-detail__subtitle">{{ detail.public_title }}</p>
                        <p class="pt-detail__text">{{ detail.public_content || '—' }}</p>
                    </section>

                    <section v-if="detail.lesson_form" class="pt-detail__section">
                        <p class="pt-detail__heading">アンケート</p>
                        <div
                            v-for="qa in surveyQa(detail)"
                            :key="qa.index"
                            class="pt-detail__qa"
                        >
                            <p class="pt-detail__q"><span class="pt-detail__qa-label">Q.</span> {{ qa.question }}</p>
                            <p class="pt-detail__a"><span class="pt-detail__qa-label">A.</span> {{ qa.answer }}</p>
                        </div>
                        <p v-if="detail.lesson_form.content" class="pt-detail__opinion">
                            <span class="pt-detail__qa-label">意見：</span>{{ detail.lesson_form.content }}
                        </p>
                    </section>
                </div>
            </template>

            <template #menu>
                <button
                    v-if="auth.isAdmin"
                    class="pt-btn pt-btn--delete pt-detail__delete"
                    type="button"
                    @click="deleteFromModal(detail)"
                >
                    このポートフォリオを削除
                </button>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { LearningPortfolio } from '@/types/learning'
import type { User } from '@/interface/globalInterface'
import { getPortfolioStatusLabels, getPortfolioStatusLabel, getPortfolioPathLabel } from '@/utils/learningProgress'
import { LESSON_PORTFOLIO_STATUS, LESSON_PORTFOLIO_STATUS_LABELS } from '@/config/learning'
import { useAuthUserStore } from '@/store/auth'
import Modal from '@/components/Global/Modal.vue'

const PORTFOLIO_STAGES = Object.entries(LESSON_PORTFOLIO_STATUS_LABELS)
    .map(([value, label]) => ({ value: Number(value), label }))
    .filter((stage) => stage.value > LESSON_PORTFOLIO_STATUS.NOT_STARTED)

const props = defineProps<{
    portfolios: LearningPortfolio[]
}>()

const emit = defineEmits<{
    'rollback-status': [id: number | undefined, status: number]
    'delete-portfolio': [id: number | undefined]
}>()

const auth = useAuthUserStore()

const detail = ref<LearningPortfolio | null>(null)

interface UserGroup {
    userId: number
    user?: User
    attempts: LearningPortfolio[]
}

// Group every attempt under its owner so one user renders as one block of rows.
const userGroups = computed<UserGroup[]>(() => {
    const groups = new Map<number, UserGroup>()

    for (const portfolio of props.portfolios) {
        const userId = portfolio.user_id ?? 0
        if (!groups.has(userId)) {
            groups.set(userId, { userId, user: portfolio.user, attempts: [] })
        }
        groups.get(userId)!.attempts.push(portfolio)
    }

    return [...groups.values()]
})

const openDetail = (portfolio: LearningPortfolio) => {
    detail.value = portfolio
}

const portfolioStatus = (portfolio: LearningPortfolio) => {
    return portfolio.status
}

const statusStages = (portfolio: LearningPortfolio) => {
    return getPortfolioStatusLabels(portfolioStatus(portfolio))
}

const stageChips = (portfolio: LearningPortfolio) => {
    const current = portfolioStatus(portfolio)
    return PORTFOLIO_STAGES.map((stage) => ({ ...stage, done: stage.value <= current }))
}

const getFormValue = (portfolio: LearningPortfolio, field: 'question' | 'answer', index: number) => {
    const key = `${field}${index}` as 'question1' | 'question2' | 'question3' | 'answer1' | 'answer2' | 'answer3'
    return portfolio.lesson_form?.[key] ?? ''
}

const surveyQa = (portfolio: LearningPortfolio) => {
    return [1, 2, 3]
        .map((index) => ({
            index,
            question: getFormValue(portfolio, 'question', index),
            answer: getFormValue(portfolio, 'answer', index),
        }))
        .filter((qa) => qa.question || qa.answer)
}

const rollback = (portfolio: LearningPortfolio, stageValue: number) => {
    emit('rollback-status', portfolio.id, stageValue - 1)
    detail.value = null
}

const deleteFromModal = (portfolio: LearningPortfolio) => {
    emit('delete-portfolio', portfolio.id)
    detail.value = null
}
</script>

<style scoped>
.pt-table-wrap{
    width: 100%;
    overflow-x: auto;
}

.pt-table{
    width: 100%;
    border-collapse: collapse;
    color: var(--primary-color);
    background: var(--background-color);
    font-size: 13px;
}

.pt-th{
    position: sticky;
    top: 0;
    z-index: 1;
    background-color: rgb(96, 96, 96);
    color: #fff;
    text-align: left;
    padding: 12px 20px;
    white-space: nowrap;
}

.pt-th--name{
    width: 200px;
}

.pt-th--path{
    width: 140px;
}

.pt-th--actions,
.pt-td--actions{
    text-align: right;
    white-space: nowrap;
}

/* thin rule between attempts of the same user, bold rule between users */
.pt-row:not(:last-child){
    border-bottom: 1px solid var(--calendarBorder);
}

.pt-group:not(:last-child){
    border-bottom: 2px solid var(--formBorder);
}

.pt-td{
    padding: 14px 20px;
    vertical-align: middle;
}

.pt-td--name{
    vertical-align: top;
    border-right: 1px solid var(--calendarBorder);
}

.pt-attempt{
    display: block;
    margin-top: 6px;
    font-size: 11px;
    color: var(--third-color);
}


.pt-status{
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.pt-pill{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 12px;
    font-size: 11px;
    line-height: 1.6;
    border-radius: 999px;
    background: var(--bg3);
    color: var(--primary-color);
    white-space: nowrap;
}

.pt-pill__dot{
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
    background: var(--check-inactive);
}

.pt-pill--done .pt-pill__dot{
    background: #2e9e4f;
}

.pt-pill--pending{
    color: var(--third-color);
}

.pt-actions{
    display: inline-flex;
    gap: 8px;
    justify-content: flex-end;
}

.pt-btn{
    padding: 5px 16px;
    font-size: 12px;
    line-height: 1.6;
    cursor: pointer;
    background: transparent;
    border: 1px solid transparent;
    white-space: nowrap;
    transition: background-color .15s ease, color .15s ease;
}

.pt-btn--detail{
    color: var(--primary-color);
    border-color: var(--primary-color);
}

.pt-btn--detail:hover{
    background: var(--primary-button);
    color: #fff;
    border-color: var(--primary-button);
}

.pt-btn--delete{
    color: #c0392b;
    border-color: #c0392b;
}

.pt-btn--delete:hover{
    background: #c0392b;
    color: #fff;
}

.pt-btn--rollback{
    color: var(--primary-color);
    border-color: var(--calendarBorder);
    padding: 3px 12px;
    font-size: 11px;
}

.pt-btn--rollback:hover{
    background: var(--formBorder);
}

/* ---- Detail modal ---- */
.pt-detail-title{
    display: flex;
    align-items: center;
    gap: 12px;
}

.pt-detail-title__name{
    font-size: 16px;
}

.pt-detail-title__meta{
    font-size: 12px;
}

.pt-detail{
    color: var(--primary-color);
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.pt-detail__section{
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pt-detail__heading{
    margin: 0;
    font-size: 13px;
    color: var(--third-color);
}

.pt-detail__subtitle{
    font-size: 14px;
}

.pt-detail__text{
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.8;
    font-size: 13px;
}

.pt-detail__status-rows{
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pt-detail__status-row{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 4px 0;
    font-size: 13px;
}

.pt-detail__qa{
    padding: 10px 0;
    border-bottom: 1px dashed var(--calendarBorder);
    line-height: 1.7;
    font-size: 13px;
}

.pt-detail__qa-label{
    color: var(--third-color);
}

.pt-detail__a{
    white-space: pre-wrap;
    word-break: break-word;
}

.pt-detail__opinion{
    margin-top: 8px;
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 13px;
}

.pt-detail__delete{
    margin-right: 12px;
}
</style>
