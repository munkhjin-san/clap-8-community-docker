<template>
    <div v-if="contract" class="contract-findings">
        <div class="contract-findings__header">
            <span class="contract-findings__label">全体リスク</span>
            <span :class="['contract-findings__badge', `contract-findings__badge--${contract.overallRisk}`]">
                {{ severityLabel(contract.overallRisk) }}
            </span>
        </div>

        <div class="contract-findings__filters">
            <button
                class="contract-findings__filter"
                :class="{'contract-findings__filter--active': activeFilter === 'high'}"
                @click="toggleFilter('high')"
            >
                高 <span>{{ counts.high }}</span>
            </button>
            <button
                class="contract-findings__filter"
                :class="{'contract-findings__filter--active': activeFilter === 'medium'}"
                @click="toggleFilter('medium')"
            >
                中 <span>{{ counts.medium }}</span>
            </button>
            <button
                class="contract-findings__filter"
                :class="{'contract-findings__filter--active': activeFilter === 'low'}"
                @click="toggleFilter('low')"
            >
                低 <span>{{ counts.low }}</span>
            </button>
            <button
                v-if="activeFilter"
                class="contract-findings__filter-reset"
                @click="clearFilter"
            >
                すべて表示
            </button>
        </div>

        <div v-if="filteredFindings.length" class="contract-findings__list">
            <article
                v-for="(finding, idx) in filteredFindings"
                :key="idx"
                class="contract-findings__item"
            >
                <header class="contract-findings__item-header">
                    <div class="contract-findings__item-meta">
                        <div class="contract-findings__item-section">{{ finding.section || '（セクション未設定）' }}</div>
                        <button class="contract-findings__item-title" @click="toggleOpen(idx)">
                            {{ finding.issue }}
                        </button>
                    </div>
                    <span :class="['contract-findings__badge', `contract-findings__badge--${finding.severity}`]">
                        {{ severityLabel(finding.severity) }}
                    </span>
                </header>
                <transition name="fade">
                    <div v-if="isOpen(idx)" class="contract-findings__item-body">
                        <p v-if="finding.category" class="contract-findings__item-line">
                            <span class="contract-findings__item-label">リスク分類</span>
                            <span>{{ finding.category }}</span>
                        </p>
                        <p v-if="finding.score" class="contract-findings__item-line">
                            <span class="contract-findings__item-label">重要度スコア</span>
                            <span>{{ finding.score }}</span>
                        </p>
                        <p class="contract-findings__item-line">
                            <span class="contract-findings__item-label">判断理由</span>
                            <span>{{ finding.rationale }}</span>
                        </p>
                        <p class="contract-findings__item-line">
                            <span class="contract-findings__item-label">対応方針・修正提案</span>
                            <span>{{ finding.suggestion }}</span>
                        </p>
                        <p v-if="finding.quote" class="contract-findings__item-line">
                            <span class="contract-findings__item-label">該当条文抜粋</span>
                            <span>{{ finding.quote }}</span>
                        </p>
                        <p v-if="finding.negotiation_tip" class="contract-findings__item-line">
                            <span class="contract-find__item-label">交渉ポイント</span>
                            <span>{{ finding.negotiation_tip }}</span>
                        </p>
                    </div>
                </transition>
            </article>
        </div>
        <p v-else class="contract-findings__empty">該当する懸念事項はありません。</p>
    </div>
    <p v-else class="contract-findings__empty">レビュー結果がまだありません。</p>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

type Severity = 'high' | 'medium' | 'low' | 'unknown'

type Finding = {
    section?: string
    category: string
    issue: string
    severity: Severity
    rationale: string
    suggestion: string
    quote: string
    score: number
    negotiation_tip: string
}

const props = defineProps<{
    contract: {
        overallRisk: Severity
        findings: Finding[]
    } | null
}>()

const openItems = ref<Set<number>>(new Set())
const activeFilter = ref<Severity | null>(null)

const severityLabel = (severity: Severity) => {
    switch (severity) {
        case 'high':
            return '高'
        case 'medium':
            return '中'
        case 'low':
            return '低'
        default:
            return '不明'
    }
}

const counts = computed(() => {
    const findings = props.contract?.findings ?? []
    return {
        high: findings.filter(f => f.severity === 'high').length,
        medium: findings.filter(f => f.severity === 'medium').length,
        low: findings.filter(f => f.severity === 'low').length,
    }
})

const filteredFindings = computed(() => {
    const findings = props.contract?.findings ?? []
    if (!activeFilter.value || activeFilter.value === 'unknown') {
        return findings
    }
    return findings.filter(f => f.severity === activeFilter.value)
})

const toggleOpen = (index: number) => {
    if (openItems.value.has(index)) {
        openItems.value.delete(index)
    } else {
        openItems.value.add(index)
    }
}

const isOpen = (index: number) => openItems.value.has(index)

const toggleFilter = (severity: Severity) => {
    activeFilter.value = activeFilter.value === severity ? null : severity
    openItems.value.clear()
}

const clearFilter = () => {
    activeFilter.value = null
    openItems.value.clear()
}
</script>

<style scoped>
.contract-findings {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.contract-findings__header {
    display: flex;
    gap: 12px;
    align-items: center;
    font-size: 14px;
}

.contract-findings__label {
    color: var(--font-color, #555);
}

.contract-findings__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 52px;
    padding: 2px 10px;
    border-radius: 999px;
    border: 1px solid var(--calendarBorder);
    font-size: 12px;
    font-weight: 600;
}

.contract-findings__badge--high {
    color: #d14343;
    border-color: rgba(209, 67, 67, 0.4);
    background: rgba(209, 67, 67, 0.08);
}

.contract-findings__badge--medium {
    color: #ff8a00;
    border-color: rgba(255, 138, 0, 0.4);
    background: rgba(255, 138, 0, 0.08);
}

.contract-findings__badge--low {
    color: #4c566a;
    border-color: rgba(76, 86, 106, 0.3);
    background: rgba(76, 86, 106, 0.08);
}

.contract-findings__filters {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.contract-findings__filter {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    border: 1px solid var(--calendarBorder);
    background: transparent;
    color: var(--font-color, #444);
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.contract-findings__filter--active {
    background: var(--primary-color);
    color: var(--background-color);
    border-color: transparent;
}

.contract-findings__filter-reset {
    padding: 6px 12px;
    border-radius: 8px;
    border: none;
    background: var(--bg2);
    color: var(--font-color, #555);
    font-size: 12px;
    cursor: pointer;
}

.contract-findings__list {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--calendarBorder);
    overflow: hidden;
    background: var(--background-color);
}

.contract-findings__item {
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.contract-findings__item + .contract-findings__item {
    border-top: 1px solid var(--calendarBorder);
}

.contract-findings__item-header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.contract-findings__item-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.contract-findings__item-section {
    font-size: 12px;
    color: var(--font-color, #666);
}

.contract-findings__item-title {
    text-align: left;
    font-size: 14px;
    font-weight: 600;
    color: var(--primary-color);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}

.contract-findings__item-title:hover {
    text-decoration: underline;
}

.contract-findings__item-body {
    display: flex;
    flex-direction: column;
    gap: 10px;
    font-size: 13px;
    color: var(--font-color, #444);
    line-height: normal;
}

.contract-findings__item-line {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.contract-findings__item-label {
    font-weight: 600;
    font-size: 12px;
    color: var(--font-color, #666);
}

.contract-findings__empty {
    font-size: 13px;
    color: var(--font-color, #777);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media (max-width: 959px) {
    .contract-findings__item {
        padding: 12px 14px;
    }
    .contract-findings__item-title {
        font-size: 13px;
    }
}
</style>
