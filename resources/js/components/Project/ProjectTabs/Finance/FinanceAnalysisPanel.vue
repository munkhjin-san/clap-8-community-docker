<template>
    <section :class="['finance-analysis-panel', `finance-analysis-panel--${variant}`]">
        <div class="finance-analysis-panel__header">
            <div class="finance-analysis-panel__title">
                <span class="finance-analysis-panel__icon" aria-hidden="true">
                    <AiIcon size="16" fill="#fff" :class="{'animate-pulse': loading}"/>
                </span>
                <div>
                    <p class="finance-analysis-panel__eyebrow">AI分析</p>
                    <h3>{{ titleText }}</h3>
                </div>
            </div>
            <div class="finance-analysis-panel__actions">
                <span v-if="stale && !loading" class="finance-analysis-panel__stale">再分析が必要</span>
                <button type="button" class="finance-analysis-panel__icon-button" title="再分析" @click="$emit('retry')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 406.7002 448.97456">
                        <path d="M269.42244,400.48149c89.40405-38.52608,127.74738-143.45953,84.52156-230.37382-4.00132-8.04547-.26147-17.82743,7.09537-22.04708,7.4958-4.29935,18.71269-3.19281,23.2254,5.40907,20.95447,39.94219,27.1756,85.82814,18.89384,129.76056-19.02756,100.93584-110.71041,171.77738-212.55189,165.33852C89.88917,442.20092,8.2668,362.26379.5443,261.0774c-2.28189-29.8992,2.63636-63.24923,14.27731-91.50091,25.44743-61.75894,78.66763-107.53931,144.41752-122.44033l-19.58257-16.43668c-7.42992-6.23632-8.21032-17.1677-2.31285-24.29177,6.18069-7.46619,16.86033-8.68422,24.91843-2.18939l51.8508,41.79173c6.84966,5.52083,8.93392,15.44934,4.04718,22.84488l-36.39742,55.08348c-5.60688,8.48539-17.40599,9.55259-24.3728,4.29712-8.40154-6.33776-9.11161-16.578-3.67234-25.07838l13.93379-21.77543c-31.98287,6.59331-59.7407,22.17515-82.69216,44.87814-41.19269,40.74673-58.67726,98.6188-45.74298,156.9487,11.22378,50.61602,47.48919,95.46628,97.6474,117.14014,41.87034,18.09258,90.2506,18.36429,132.55882.13279Z"/>
                    </svg>
                </button>
                <button type="button" class="finance-analysis-panel__icon-button" title="閉じる" @click="$emit('close')">
                    <CloseIcon size="10" fill="currentColor"/>
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
import AiIcon from '@/components/Icons/AiIcon.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue';
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

const props = withDefaults(defineProps<{
    result: FinanceAnalysisResult | null
    loading: boolean
    error: string
    stale: boolean
    variant?: 'inline' | 'aside'
}>(), {
    variant: 'inline',
})

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
    background: color-mix(in srgb, var(--background-color) 88%, var(--bg2));
    box-sizing: border-box !important;
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 0;
}

.finance-analysis-panel--inline {
    border-top: 1px solid var(--normalBorder);
    border-bottom: 1px solid var(--normalBorder);
    padding: 16px 24px 18px;
    max-height: min(34vh, 340px);
}

.finance-analysis-panel--aside {
    height: 100%;
    max-height: none;
    padding: 16px 18px 18px;
    border-left: 1px solid var(--normalBorder);
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
    background: var(--primary-button);
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
    min-height: 0;
    overflow: hidden auto;
    padding: 0 8px 4px 0;
    scrollbar-gutter: stable;
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

.finance-analysis-panel--aside .finance-analysis-panel__sections {
    grid-template-columns: 1fr;
    gap: 12px;
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
    }

    .finance-analysis-panel--inline {
        max-height: 42vh;
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
