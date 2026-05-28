<template>
    <div v-if="contract" class="contract-findings">
        <div class="contract-findings__header">
            <div class="contract-findings__summary">
                <span class="contract-findings__label">リスク概要</span>
                <span :class="['contract-findings__badge', `contract-findings__badge--${contract.overallRisk}`]">
                    {{ severityLabel(contract.overallRisk) }}
                </span>
            </div>
            <button
                v-if="contract.findings.length"
                type="button"
                class="contract-findings__export"
                @click="downloadFindings"
            >
                リスク一覧をダウンロード
            </button>
        </div>

        <div class="contract-findings__filters">
            <button
                class="contract-findings__filter contract-findings__filter--high"
                :class="{ 'contract-findings__filter--active': activeFilter === 'high' }"
                @click="toggleFilter('high')"
            >
                高 <span>{{ counts.high }}</span>
            </button>
            <button
                class="contract-findings__filter contract-findings__filter--medium"
                :class="{ 'contract-findings__filter--active': activeFilter === 'medium' }"
                @click="toggleFilter('medium')"
            >
                中 <span>{{ counts.medium }}</span>
            </button>
            <button
                class="contract-findings__filter contract-findings__filter--low"
                :class="{ 'contract-findings__filter--active': activeFilter === 'low' }"
                @click="toggleFilter('low')"
            >
                低 <span>{{ counts.low }}</span>
            </button>
            <button
                v-if="activeFilter"
                type="button"
                class="contract-findings__filter-reset"
                @click="clearFilter"
            >
                リセット
            </button>
        </div>

        <div v-if="filteredFindings.length" class="contract-findings__list">
            <article
                v-for="(finding, idx) in filteredFindings"
                :key="`${finding.issue}-${idx}`"
                class="contract-findings__item"
            >
                <header class="contract-findings__item-header">
                    <div class="contract-findings__item-meta">
                        <button
                            type="button"
                            class="contract-findings__item-section"
                            @click="focusFinding(finding, idx)"
                        >
                            {{ sectionLabel(finding) }}
                        </button>
                        <button type="button" class="contract-findings__item-title" @click="toggleOpen(idx)">
                            {{ finding.issue }}
                        </button>
                    </div>
                    <div class="contract-findings__item-actions">
                        <button
                            v-if="canFocusFinding(finding)"
                            type="button"
                            class="contract-findings__jump"
                            @click="focusFinding(finding, idx)"
                        >
                            該当箇所へ移動
                        </button>
                        <span :class="['contract-findings__badge', `contract-findings__badge--${finding.severity}`]">
                            {{ severityLabel(finding.severity) }}
                        </span>
                    </div>
                </header>
                <transition name="fade">
                    <div v-if="isOpen(idx)" class="contract-findings__item-body">
                        <p v-if="finding.category" class="contract-findings__item-line">
                            <span class="contract-findings__item-label">カテゴリ</span>
                            <span>{{ finding.category }}</span>
                        </p>
                        <p v-if="finding.score" class="contract-findings__item-line">
                            <span class="contract-findings__item-label">スコア</span>
                            <span>{{ finding.score }}</span>
                        </p>
                        <p v-if="finding.rationale" class="contract-findings__item-line">
                            <span class="contract-findings__item-label">理由</span>
                            <span>{{ finding.rationale }}</span>
                        </p>
                        <p v-if="finding.suggestion" class="contract-findings__item-line">
                            <span class="contract-findings__item-label">提案</span>
                            <span>{{ finding.suggestion }}</span>
                        </p>
                        <p v-if="finding.quote" class="contract-findings__item-line">
                            <span class="contract-findings__item-label">該当条文</span>
                            <span>{{ finding.quote }}</span>
                        </p>
                        <p v-if="finding.negotiation_tip" class="contract-findings__item-line">
                            <span class="contract-findings__item-label">交渉ポイント</span>
                            <span>{{ finding.negotiation_tip }}</span>
                        </p>
                    </div>
                </transition>
            </article>
        </div>
        <p v-else class="contract-findings__empty">現在のフィルター条件に一致するリスクはありません。</p>
    </div>
    <p v-else class="contract-findings__empty">レビュー結果はまだありません。</p>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

type Severity = 'high' | 'medium' | 'low' | 'unknown'

type Finding = {
    section?: string
    location?: string
    category?: string
    issue: string
    severity: Severity
    rationale: string
    suggestion: string
    quote?: string
    score?: number
    negotiation_tip?: string
    page?: number
    anchor?: {
        clause_id?: string
        page?: number
        query?: string
        fallback_query?: string
        matched_text?: string
        paragraph_index?: number
    } | null
}

const props = defineProps<{
    contract: {
        overallRisk: Severity
        findings: Finding[]
    } | null
    exportFilename?: string
}>()

const emit = defineEmits<{
    (e: 'focus-finding', finding: Finding): void
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

const isOpen = (index: number) => openItems.value.has(index)

const canFocusFinding = (finding: Finding) => {
    return Boolean(finding.anchor?.query || finding.section || finding.page)
}

const sectionLabel = (finding: Finding) => {
    if (finding.section && finding.location) {
        return `${finding.section} / ${finding.location}`
    }
    if (finding.section && finding.page) {
        return `${finding.section} / p.${finding.page}`
    }
    if (finding.section) {
        return finding.section
    }
    if (finding.page) {
        return `p.${finding.page}`
    }
    return '関連箇所へ移動'
}

const sanitizeFilename = (value?: string) => {
    const name = (value || 'detected-risks.txt')
        .replace(/[\\/:*?"<>|]+/g, '-')
        .replace(/\s+/g, ' ')
        .trim()

    return name.toLowerCase().endsWith('.txt') ? name : `${name}.txt`
}

const lineValue = (label: string, value?: string | number | null) => {
    if (value === null || value === undefined || value === '') {
        return ''
    }

    return `${label}: ${value}`
}

const activeFilterLabel = () => {
    if (!activeFilter.value) {
        return '全件'
    }

    return `${severityLabel(activeFilter.value)}リスク`
}

/**
 * Builds a plain-text report so users can keep or share the currently visible risk list without extra export dependencies.
 */
const createFindingsText = () => {
    const findings = filteredFindings.value
    const lines = [
        '検出されたリスク',
        `総合リスク: ${severityLabel(props.contract?.overallRisk ?? 'unknown')}`,
        `出力対象: ${activeFilterLabel()}`,
        `件数: ${findings.length}`,
        `高: ${counts.value.high} / 中: ${counts.value.medium} / 低: ${counts.value.low}`,
        '',
    ]

    findings.forEach((finding, index) => {
        const detailLines = [
            `${index + 1}. [${severityLabel(finding.severity)}] ${finding.issue}`,
            lineValue('関連箇所', sectionLabel(finding)),
            lineValue('カテゴリ', finding.category),
            lineValue('スコア', finding.score),
            lineValue('理由', finding.rationale),
            lineValue('提案', finding.suggestion),
            lineValue('該当条文', finding.quote),
            lineValue('交渉ポイント', finding.negotiation_tip),
        ].filter(Boolean)

        lines.push(...detailLines, '')
    })

    return lines.join('\n')
}

/**
 * Downloads the visible finding list as UTF-8 text for lightweight review handoff.
 */
const downloadFindings = () => {
    if (!props.contract?.findings.length) {
        return
    }

    const blob = new Blob([createFindingsText()], { type: 'text/plain;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = sanitizeFilename(props.exportFilename)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
}

const focusFinding = (finding: Finding, index: number) => {
    if (!openItems.value.has(index)) {
        openItems.value.add(index)
    }
    emit('focus-finding', finding)
}

const toggleOpen = (index: number) => {
    if (openItems.value.has(index)) {
        openItems.value.delete(index)
    } else {
        openItems.value.add(index)
    }
}

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
    gap: 10px;
    color: var(--primary-color);
}

.contract-findings__header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    font-size: 14px;
}

.contract-findings__summary {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.contract-findings__label {
    color: var(--sub-color, var(--font-color, #666));
}

.contract-findings__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    align-self: flex-start;
    min-width: 52px;
    max-width: 100%;
    padding: 2px 8px;
    border-radius: 0;
    border: 1px solid var(--calendarBorder);
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
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
    color: var(--primary-color);
    border-color: var(--calendarBorder);
    background: var(--bg3);
}

.contract-findings__badge--unknown {
    color: var(--sub-color, var(--font-color, #666));
    background: var(--bg3);
}

.contract-findings__filters {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.contract-findings__filter {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 8px;
    border-radius: 0;
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.contract-findings__filter span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    padding: 0 5px;
    border-radius: 0;
    color: inherit;
}

.contract-findings__filter--high {
    color: #d14343;
    border-color: rgba(209, 67, 67, 0.35);
    background: rgba(209, 67, 67, 0.08);
}

.contract-findings__filter--medium {
    color: #ff8a00;
    border-color: rgba(255, 138, 0, 0.35);
    background: rgba(255, 138, 0, 0.08);
}

.contract-findings__filter--low {
    color: var(--primary-color);
    border-color: var(--calendarBorder);
    background: var(--bg3);
}

.contract-findings__filter--active {
    border-color: currentColor;
}

.contract-findings__filter-reset {
    padding: 3px 8px;
    border-radius: 0;
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 11px;
    cursor: pointer;
}

.contract-findings__export {
    flex-shrink: 0;
    padding: 3px 8px;
    border-radius: 0;
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.contract-findings__list {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--calendarBorder);
    overflow: hidden;
    background: var(--background-color);
}

.contract-findings__item {
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.contract-findings__item + .contract-findings__item {
    border-top: 1px solid var(--calendarBorder);
}

.contract-findings__item-header {
    display: flex;
    justify-content: space-between;
    gap: 8px;
}

.contract-findings__item-actions {
    display: flex;
    align-items: flex-start;
    gap: 6px;
}

.contract-findings__item-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.contract-findings__item-section {
    width: fit-content;
    padding: 0;
    border: none;
    background: transparent;
    font-size: 11px;
    color: var(--sub-color, var(--font-color, #666));
    cursor: pointer;
}

.contract-findings__item-section:hover,
.contract-findings__item-title:hover {
    text-decoration: underline;
}

.contract-findings__item-title {
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary-color);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    line-height: 1.4;
}

.contract-findings__item-body {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 12px;
    color: var(--primary-color);
    line-height: 1.5;
}

.contract-findings__item-line {
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding-left: 10px;
    border-left: 2px solid var(--calendarBorder);
}

.contract-findings__item-label {
    font-weight: 600;
    font-size: 11px;
    color: var(--sub-color, var(--font-color, #666));
}

.contract-findings__jump {
    padding: 3px 8px;
    border: 1px solid var(--calendarBorder);
    border-radius: 0;
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.contract-findings__empty {
    font-size: 12px;
    color: var(--sub-color, var(--font-color, #666));
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
        padding: 10px;
    }

    .contract-findings__item-title {
        font-size: 13px;
    }
}
</style>
