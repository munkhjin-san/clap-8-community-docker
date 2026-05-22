<template>
    <section class="finance-analysis-panel">
        <div class="finance-analysis-panel__header">
            <div class="finance-analysis-panel__title">
                <span class="finance-analysis-panel__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                        <rect x="5" y="7" width="14" height="11" rx="3"
                                stroke="currentColor" stroke-width="1.7"/>

                        <path d="M12 7V4"
                                stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <circle cx="12" cy="3.5" r="1.2" fill="currentColor"/>

                        <circle class="ai-eye" cx="9" cy="12" r="1.2" fill="currentColor"/>
                        <circle class="ai-eye" cx="15" cy="12" r="1.2" fill="currentColor"/>

                        <path d="M9.5 15H14.5"
                                stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>

                        <path d="M3.5 11V14"
                                stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M20.5 11V14"
                                stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
                <div>
                    <p class="finance-analysis-panel__eyebrow">AI分析</p>
                    <h3>{{ titleText }}</h3>
                </div>
            </div>
            <div class="finance-analysis-panel__actions">
                <span v-if="stale && !loading" class="finance-analysis-panel__stale">再分析が必要</span>
                <button type="button" class="finance-analysis-panel__icon-button" title="再分析" @click="$emit('retry')">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.65 6.35A7.95 7.95 0 0 0 12 4a8 8 0 1 0 7.45 5.08h-2.13A6 6 0 1 1 12 6c1.66 0 3.14.69 4.22 1.78L13 11h8V3l-3.35 3.35Z"/>
                    </svg>
                </button>
                <button type="button" class="finance-analysis-panel__icon-button" title="閉じる" @click="$emit('close')">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="m18.3 5.71-1.41-1.41L12 9.17 7.11 4.3 5.7 5.71 10.59 10.6 5.7 15.49l1.41 1.41L12 12.01l4.89 4.89 1.41-1.41-4.89-4.89 4.89-4.89Z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div v-if="loading" class="finance-analysis-panel__state">
            <span class="finance-analysis-panel__spinner"></span>
            <span>分析中...</span>
        </div>

        <div v-else-if="error" class="finance-analysis-panel__error">
            <p>{{ error }}</p>
            <button type="button" @click="$emit('retry')">再試行</button>
        </div>

        <div v-else-if="result" class="finance-analysis-panel__body">
            <p v-if="result.summary" class="finance-analysis-panel__summary">{{ result.summary }}</p>

            <div class="finance-analysis-panel__meta">
                <span>{{ scopeLabel }}</span>
                <span>{{ basisLabel }}</span>
                <span>{{ result.scope.project_count }}件</span>
            </div>

            <div class="finance-analysis-panel__sections">
                <div v-if="result.highlights.length" class="finance-analysis-panel__section">
                    <h4>ポイント</h4>
                    <ul>
                        <li v-for="item in result.highlights" :key="item">{{ item }}</li>
                    </ul>
                </div>
                <div v-if="result.risks.length" class="finance-analysis-panel__section">
                    <h4>リスク</h4>
                    <ul>
                        <li v-for="item in result.risks" :key="item">{{ item }}</li>
                    </ul>
                </div>
                <div v-if="result.recommended_actions.length" class="finance-analysis-panel__section">
                    <h4>次の確認</h4>
                    <ul>
                        <li v-for="item in result.recommended_actions" :key="item">{{ item }}</li>
                    </ul>
                </div>
                <div v-if="result.data_notes.length" class="finance-analysis-panel__section finance-analysis-panel__section--muted">
                    <h4>データ注記</h4>
                    <ul>
                        <li v-for="item in result.data_notes" :key="item">{{ item }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type FinanceAnalysisScope = {
    grouping: 'range' | 'fiscal'
    include_forecast_settlement: boolean
    project_count: number
    period_start?: string
    period_end?: string
    fiscal_years?: number[]
    analysis_basis?: string
}

type FinanceAnalysisResult = {
    headline: string
    summary: string
    highlights: string[]
    risks: string[]
    recommended_actions: string[]
    data_notes: string[]
    scope: FinanceAnalysisScope
    generated_at: string
}

const props = defineProps<{
    result: FinanceAnalysisResult | null
    loading: boolean
    error: string
    stale: boolean
}>()

defineEmits<{
    retry: []
    close: []
}>()

const titleText = computed(() => props.result?.headline || '財務分析')
const scopeLabel = computed(() => {
    const scope = props.result?.scope
    if (!scope) return ''
    if (scope.grouping === 'fiscal') {
        return (scope.fiscal_years ?? []).map(year => `FY${year}`).join(' / ')
    }
    if (scope.period_start && scope.period_end) {
        return scope.period_start === scope.period_end
            ? scope.period_start
            : `${scope.period_start} - ${scope.period_end}`
    }
    return ''
})
const basisLabel = computed(() => props.result?.scope.analysis_basis ?? '')
</script>

<style scoped>
.finance-analysis-panel {
    border-top: 1px solid var(--normalBorder);
    border-bottom: 1px solid var(--normalBorder);
    background: color-mix(in srgb, var(--background-color) 88%, var(--bg2));
    padding: 14px 24px 16px;
}

.finance-analysis-panel__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.finance-analysis-panel__title {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.finance-analysis-panel__title h3,
.finance-analysis-panel__title p {
    margin: 0;
}

.finance-analysis-panel__title h3 {
    color: var(--primary-color);
    font-size: 15px;
    font-weight: 600;
    line-height: 1.4;
}

.finance-analysis-panel__eyebrow {
    color: gray;
    font-size: 11px;
    line-height: 1.3;
}

.finance-analysis-panel__icon {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    color: #fff;
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}

.finance-analysis-panel__actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 0 0 auto;
}

.finance-analysis-panel__stale {
    color: #F28C28;
    font-size: 11px;
    white-space: nowrap;
}

.finance-analysis-panel__icon-button {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--normalBorder);
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
}

.finance-analysis-panel__icon-button:hover {
    border-color: var(--hoverBorder);
    background: var(--bg3);
}

.finance-analysis-panel__state,
.finance-analysis-panel__error {
    margin-top: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--normalText);
    font-size: 13px;
}

.finance-analysis-panel__spinner {
    width: 15px;
    height: 15px;
    border: 2px solid var(--normalBorder);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: finance-analysis-spin .8s linear infinite;
}

.finance-analysis-panel__error {
    color: tomato;
}

.finance-analysis-panel__error p {
    margin: 0;
}

.finance-analysis-panel__error button {
    border: 1px solid currentColor;
    background: transparent;
    color: tomato;
    padding: 4px 10px;
    cursor: pointer;
}

.finance-analysis-panel__body {
    margin-top: 12px;
}

.finance-analysis-panel__summary {
    margin: 0;
    color: var(--primary-color);
    font-size: 13px;
    line-height: 1.7;
}

.finance-analysis-panel__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}

.finance-analysis-panel__meta span {
    border: 1px solid var(--normalBorder);
    color: gray;
    font-size: 11px;
    padding: 3px 8px;
}

.finance-analysis-panel__sections {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px 22px;
    margin-top: 14px;
}

.finance-analysis-panel__section h4 {
    margin: 0 0 6px;
    color: var(--primary-color);
    font-size: 12px;
    font-weight: 600;
}

.finance-analysis-panel__section ul {
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.finance-analysis-panel__section li {
    position: relative;
    padding-left: 12px;
    color: var(--normalText);
    font-size: 12px;
    line-height: 1.6;
}

.finance-analysis-panel__section li::before {
    content: "";
    position: absolute;
    left: 0;
    top: .7em;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--primary-color);
}

.finance-analysis-panel__section--muted li,
.finance-analysis-panel__section--muted h4 {
    color: gray;
}

@keyframes finance-analysis-spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .finance-analysis-panel {
        padding: 12px 16px 14px;
        overflow: hidden auto;
    }

    .finance-analysis-panel__sections {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .finance-analysis-panel__header {
        gap: 10px;
    }

    .finance-analysis-panel__title h3 {
        font-size: 14px;
    }
}
</style>
