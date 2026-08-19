<template>
    <div class="pt-table-wrap scrollable">
        <table class="pt-table">
            <thead>
                <tr>
                    <th class="pt-th pt-th--name">氏名</th>
                    <th class="pt-th pt-th--path">学習方法</th>
                    <th class="pt-th">ステータス</th>
                    <template v-if="hasExamColumns">
                        <th class="pt-th pt-th--exam">試験セクション</th>
                        <th class="pt-th pt-th--exam">試験受験回</th>
                        <th class="pt-th pt-th--exam">試験スコア</th>
                        <th class="pt-th pt-th--exam">試験結果</th>
                    </template>
                    <th class="pt-th pt-th--actions">操作</th>
                </tr>
            </thead>
            <tbody
                v-for="row in rows"
                :key="row.userId"
                class="pt-group"
            >
                <tr
                    v-for="(exam, i) in displayExamRows(row)"
                    :key="exam.key"
                    class="pt-row"
                >
                    <td
                        v-if="i === 0"
                        class="pt-td pt-td--name"
                        :rowspan="displayExamRows(row).length"
                    >
                        {{ row.userName }}
                    </td>
                    <td
                        v-if="i === 0"
                        class="pt-td pt-td--path"
                        :rowspan="displayExamRows(row).length"
                    >
                        <div
                            v-for="entry in row.entries"
                            :key="entry.key"
                            class="pt-learn"
                        >
                            <span v-if="entry.methodLabel" class="pt-pill">{{ entry.methodLabel }}</span>
                            <span v-else class="pt-exam-empty">—</span>
                            <span
                                v-if="entry.attemptNo > 1"
                                class="pt-attempt"
                            >{{ entry.attemptNo }}回目</span>
                        </div>
                    </td>
                    <td
                        v-if="i === 0"
                        class="pt-td"
                        :rowspan="displayExamRows(row).length"
                    >
                        <div
                            v-for="entry in row.entries"
                            :key="entry.key"
                            class="pt-status"
                        >
                            <span
                                v-for="(chip, ci) in entry.statusChips"
                                :key="ci"
                                class="pt-pill"
                                :class="chip.done ? 'pt-pill--done' : 'pt-pill--pending'"
                            >
                                <span class="pt-pill__dot" />{{ chip.label }}
                            </span>
                            <!-- 誓約書 etc: the learner's own copy, under the chips. -->
                            <a
                                v-for="file in entry.files"
                                :key="file.key"
                                class="pt-file"
                                :href="file.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                :title="file.label"
                            >
                                <FileIcon :ext="file.ext" class="pt-file__icon" />{{ file.label }}
                            </a>
                        </div>
                    </td>
                    <template v-if="hasExamColumns">
                        <td class="pt-td pt-td--exam pt-td--exam-name">
                            {{ exam.title || '—' }}
                        </td>
                        <td class="pt-td pt-td--exam">
                            <span v-if="exam.attemptCount !== null">{{ exam.attemptCount }}／{{ exam.maxAttempts ?? '—' }}回</span>
                            <span v-else class="pt-exam-empty">—</span>
                        </td>
                        <td class="pt-td pt-td--exam">
                            <span v-if="exam.score !== null">{{ exam.score }}%</span>
                            <span v-else class="pt-exam-empty">—</span>
                        </td>
                        <td class="pt-td pt-td--exam">
                            <span
                                v-if="exam.passed !== null"
                                class="pt-pill"
                                :class="exam.passed ? 'pt-pill--done' : 'pt-pill--pending'"
                            >
                                <span class="pt-pill__dot" />{{ exam.passed ? '合格' : '不合格' }}
                            </span>
                            <span v-else class="pt-exam-empty">未受験</span>
                        </td>
                    </template>
                    <td
                        v-if="i === 0"
                        class="pt-td pt-td--actions"
                        :rowspan="displayExamRows(row).length"
                    >
                        <div
                            v-for="entry in row.entries"
                            :key="entry.key"
                            class="pt-actions"
                        >
                            <button
                                class="pt-btn pt-btn--detail"
                                type="button"
                                @click="openDetail(entry, row.userName)"
                            >
                                詳細
                            </button>
                            <button
                                v-if="auth.isAdmin"
                                class="pt-btn pt-btn--delete"
                                type="button"
                                @click="requestDelete(row, entry)"
                            >
                                削除
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <Modal
            v-if="detailEntry"
            size="large"
            @close="detailEntry = null"
        >
            <template #title>
                <div class="pt-detail-title">
                    <span class="pt-detail-title__name">{{ detailUserName }}</span>
                    <span v-if="detailEntry.methodLabel" class="pt-pill pt-detail-title__meta">
                        {{ detailEntry.methodLabel }}<template v-if="detailEntry.attemptNo > 1">・{{ detailEntry.attemptNo }}回目</template>
                    </span>
                </div>
            </template>

            <template #content>
                <!-- Portfolio detail -->
                <div v-if="detailEntry.detail.type === 'portfolio'" class="pt-detail">
                    <section
                        v-if="statusStages(detailEntry.detail.portfolio).length"
                        class="pt-detail__section"
                    >
                        <p class="pt-detail__heading">進捗ステータス</p>
                        <div class="pt-detail__status-rows">
                            <div
                                v-for="stage in statusStages(detailEntry.detail.portfolio)"
                                :key="stage.value"
                                class="pt-detail__status-row"
                            >
                                <span class="pt-pill pt-pill--done">
                                    <span class="pt-pill__dot" />{{ stage.label }}
                                </span>
                                <button
                                    class="pt-btn pt-btn--rollback"
                                    type="button"
                                    @click="rollback(detailEntry.detail.portfolio, stage.value)"
                                >
                                    差し戻す
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="pt-detail__section">
                        <p class="pt-detail__heading">ディスカッション用ポートフォリオ</p>
                        <p v-if="detailEntry.detail.portfolio.portfolio_title" class="pt-detail__subtitle">{{ detailEntry.detail.portfolio.portfolio_title }}</p>
                        <p class="pt-detail__text">{{ detailEntry.detail.portfolio.content || '—' }}</p>
                    </section>

                    <section class="pt-detail__section">
                        <p class="pt-detail__heading">本ポートフォリオ</p>
                        <p v-if="detailEntry.detail.portfolio.public_title" class="pt-detail__subtitle">{{ detailEntry.detail.portfolio.public_title }}</p>
                        <p class="pt-detail__text">{{ detailEntry.detail.portfolio.public_content || '—' }}</p>
                    </section>

                    <section v-if="detailEntry.detail.portfolio.lesson_form" class="pt-detail__section">
                        <p class="pt-detail__heading">アンケート</p>
                        <div
                            v-for="qa in surveyQa(detailEntry.detail.portfolio)"
                            :key="qa.index"
                            class="pt-detail__qa"
                        >
                            <p class="pt-detail__q"><span class="pt-detail__qa-label">Q.</span> {{ qa.question }}</p>
                            <p class="pt-detail__a"><span class="pt-detail__qa-label">A.</span> {{ qa.answer }}</p>
                        </div>
                        <p v-if="detailEntry.detail.portfolio.lesson_form.content" class="pt-detail__opinion">
                            <span class="pt-detail__qa-label">意見：</span>{{ detailEntry.detail.portfolio.lesson_form.content }}
                        </p>
                    </section>
                </div>

                <!-- Case-study detail -->
                <div v-else class="pt-detail">
                    <section class="pt-detail__section">
                        <p class="pt-detail__heading">ケーススタディ答え</p>
                        <div
                            v-for="(answer, ai) in detailEntry.detail.participant.answers ?? []"
                            :key="ai"
                            class="pt-detail__qa"
                        >
                            <p v-if="answer.title" class="pt-detail__subtitle">{{ answer.title }}</p>
                            <p class="pt-detail__a">{{ answer.answer || '—' }}</p>
                        </div>
                        <p v-if="!(detailEntry.detail.participant.answers ?? []).length" class="pt-detail__text">—</p>
                    </section>

                    <section class="pt-detail__section">
                        <p class="pt-detail__heading">基礎知識理解不能</p>
                        <p class="pt-detail__text">{{ detailEntry.detail.participant.cant_understand || '—' }}</p>
                    </section>

                    <section class="pt-detail__section">
                        <p class="pt-detail__heading">理解できない理由</p>
                        <p class="pt-detail__text">{{ detailEntry.detail.participant.reason_dnt_und || '—' }}</p>
                    </section>
                </div>
            </template>

            <template #menu>
                <button
                    v-if="auth.isAdmin && detailEntry.portfolioId"
                    class="pt-btn pt-btn--delete pt-detail__delete"
                    type="button"
                    @click="deleteFromModal(detailEntry.portfolioId)"
                >
                    このポートフォリオを削除
                </button>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { LearningPortfolio, ParticipantEntry, ParticipantExamCell, ParticipantRow } from '@/types/learning'
import { getPortfolioStatusLabels } from '@/utils/learningProgress'
import { useAuthUserStore } from '@/store/auth'
import Modal from '@/components/Global/Modal.vue'
import FileIcon from '@/components/Board/Mixed/FileIcon.vue'

const props = defineProps<{
    rows: ParticipantRow[]
}>()

const emit = defineEmits<{
    'rollback-status': [id: number | undefined, status: number]
    'delete-portfolio': [id: number | undefined]
    'delete-progress': [userId: number]
}>()

const auth = useAuthUserStore()

const detailEntry = ref<ParticipantEntry | null>(null)
const detailUserName = ref<string | null>(null)

const hasExamColumns = computed(() => props.rows.some((row) => row.examRows.length > 0))

const EMPTY_EXAM: ParticipantExamCell = {
    key: 'empty',
    title: null,
    attemptCount: null,
    maxAttempts: null,
    score: null,
    passed: null,
}

// Exam sections drive the rows; a user with no exam rows still renders one row
// so the merged name / method / status / actions cells have something to span.
const displayExamRows = (row: ParticipantRow): ParticipantExamCell[] => {
    return row.examRows.length ? row.examRows : [EMPTY_EXAM]
}

const openDetail = (entry: ParticipantEntry, userName: string | null) => {
    detailEntry.value = entry
    detailUserName.value = userName
}

const statusStages = (portfolio: LearningPortfolio) => {
    return getPortfolioStatusLabels(portfolio.status)
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
    detailEntry.value = null
}

// Portfolio themes delete one attempt; case-study themes have no portfolio, so
// there the whole of that learner's data for the theme is what goes.
const requestDelete = (row: ParticipantRow, entry: ParticipantEntry) => {
    if (entry.portfolioId) {
        emit('delete-portfolio', entry.portfolioId)
        return
    }

    emit('delete-progress', row.userId)
}

const deleteFromModal = (portfolioId: number) => {
    emit('delete-portfolio', portfolioId)
    detailEntry.value = null
}
</script>

<style scoped>
.pt-table-wrap{
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
}

.pt-table{
    width: 100%;
    border-collapse: collapse;
    color: var(--primary-color);
    background: var(--background-color);
    font-size: 13px;
}

/* ---------- header ---------- */
.pt-th{
    position: sticky;
    top: 0;
    z-index: 1;
    background: var(--bg3);
    color: var(--third-color);
    font-size: 11px;
    font-weight: 400;
    letter-spacing: 0.06em;
    text-align: left;
    padding: 14px 20px;
    white-space: nowrap;
    border-bottom: 1px solid var(--formBorder);
    border-right: 1px solid var(--calendarBorder);
}

.pt-th--name{ width: 170px; }
.pt-th--path{ width: 120px; }
.pt-th--exam{ width: 92px; }

.pt-th.pt-th--actions,
.pt-td.pt-td--actions{
    text-align: right;
    white-space: nowrap;
    border-right: none;
}

/* ---------- body ---------- */
.pt-td{
    padding: 16px 20px;
    vertical-align: middle;
    border-right: 1px solid var(--calendarBorder);
}

.pt-td--name{
    vertical-align: top;
    white-space: nowrap;
    font-size: 14px;
    color: var(--primary-color);
    padding-top: 18px;
}

.pt-attempt{
    display: block;
    margin-top: 6px;
    font-size: 11px;
    color: var(--third-color);
}

/* one clear divider between users; rows inside a group read as one block */
.pt-group:not(:last-child){
    border-bottom: 1px solid var(--formBorder);
}

/* faint hairline between stacked rows of the same user */
.pt-row + .pt-row > .pt-td{
    border-top: 1px solid var(--calendarBorder);
}

/* stacked learning method / status / actions (only when a user has >1 attempt) */
.pt-learn + .pt-learn,
.pt-status + .pt-status,
.pt-actions + .pt-actions{
    margin-top: 12px;
}

/* ---------- per-section exam columns ---------- */
.pt-td--exam-name{
    color: var(--primary-color);
}

.pt-td--exam{
    white-space: nowrap;
    font-size: 12px;
    color: var(--third-color);
}

.pt-exam-empty{
    color: var(--third-color);
}

/* ---------- chips ---------- */
.pt-status{
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.pt-pill{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 11px;
    font-size: 11px;
    line-height: 1.7;
    border-radius: 999px;
    background: var(--bg3);
    border: 1px solid var(--calendarBorder);
    color: var(--primary-color);
    white-space: nowrap;
}

/* File links sit under the status chips, indented to the chip text. */
.pt-file{
    /* full basis so it always breaks onto its own line under the chips */
    flex-basis: 100%;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-left: 4px;
    font-size: 12px;
    color: var(--primary-color);
    text-decoration: none;
    white-space: nowrap;
}

.pt-file:hover{
    text-decoration: underline;
}

.pt-file__icon :deep(.file-icon-01-mobile){
    width: auto;
    min-width: 0;
    height: 20px;
}

.pt-pill__dot{
    width: 6px;
    height: 6px;
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

/* ---------- action buttons ---------- */
.pt-actions{
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.pt-btn{
    padding: 6px 18px;
    font-size: 12px;
    line-height: 1.6;
    cursor: pointer;
    background: transparent;
    border: 1px solid var(--formBorder);
    color: var(--primary-color);
    white-space: nowrap;
    transition: background-color .15s ease, color .15s ease, border-color .15s ease;
}

/* 誓約書 link styled like the neighbouring buttons. */
.pt-btn--detail:hover{
    background: var(--primary-button);
    color: #fff;
    border-color: var(--primary-button);
}

.pt-btn--delete{
    color: #c0392b;
    border-color: #d9a6a0;
}

.pt-btn--delete:hover{
    background: #c0392b;
    color: #fff;
    border-color: #c0392b;
}

.pt-btn--rollback{
    padding: 4px 12px;
    font-size: 11px;
}

.pt-btn--rollback:hover{
    background: var(--bg3);
}

/* ---------- detail modal ---------- */
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
