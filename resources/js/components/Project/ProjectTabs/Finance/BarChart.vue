<script setup lang="ts">
import { computed, ref } from 'vue';
import { Chart, registerables } from 'chart.js';
import { Bar } from 'vue-chartjs';
import { FinancialData } from '@/interface/projectInterface';

Chart.register(...registerables);

type MetricKey = 'sales' | 'expense' | 'profit';
type ScenarioKey = 'yearly_plan' | 'profit' | 'settlement';
type UnitData = Partial<FinancialData> & {
    has_data?: boolean;
    is_forecast?: boolean;
};
type SummaryData = Partial<Record<ScenarioKey, UnitData>>;
type ProjectChartData = Record<string, Partial<Record<ScenarioKey, UnitData>>>;

const props = defineProps<{
    projectsData: ProjectChartData;
    summaryData?: SummaryData;
    activeView: MetricKey;
    periodLabel?: string;
}>();

const metricLabels: Record<MetricKey, string> = {
    sales: '売上',
    expense: '販管費',
    profit: '利益',
};

const scenarioLabels: Record<ScenarioKey, string> = {
    yearly_plan: '予算',
    profit: '計画',
    settlement: '実績',
};

const scenarioColors: Record<ScenarioKey, string> = {
    yearly_plan: '#0f766e',
    profit: '#ea580c',
    settlement: '#2563eb',
};

const comparisonPairs: Array<{
    key: string;
    base: ScenarioKey;
    target: ScenarioKey;
    label: string;
}> = [
    {
        key: 'yearly_plan:settlement',
        base: 'yearly_plan',
        target: 'settlement',
        label: '予算 / 実績',
    },
    {
        key: 'profit:settlement',
        base: 'profit',
        target: 'settlement',
        label: '計画 / 実績',
    },
    {
        key: 'yearly_plan:profit',
        base: 'yearly_plan',
        target: 'profit',
        label: '予算 / 計画',
    },
];

const selectedComparisonKey = ref('yearly_plan:settlement');

const yenFormatter = new Intl.NumberFormat('ja-JP', {
    maximumFractionDigits: 0,
});

const compactCurrency = new Intl.NumberFormat('ja-JP', {
    style: 'currency',
    currency: 'JPY',
    notation: 'compact',
    maximumFractionDigits: 1,
});

const metricLabel = computed(() => metricLabels[props.activeView]);
const projectLabels = computed(() => Object.keys(props.projectsData ?? {}));

const metricValue = (unit: UnitData | null | undefined, metric: MetricKey) => {
    if (!unit) return 0;
    if (metric === 'profit') {
        const explicitProfit = Number(unit.profit);
        if (Number.isFinite(explicitProfit)) return explicitProfit;
        return Number(unit.sales ?? 0) - Number(unit.expense ?? 0);
    }
    return Number(unit[metric] ?? 0);
};

const projectScenarioValue = (projectName: string, scenario: ScenarioKey) =>
    metricValue(props.projectsData?.[projectName]?.[scenario], props.activeView);

const fallbackSummary = computed<Required<SummaryData>>(() => {
    return projectLabels.value.reduce((summary, projectName) => {
        (['yearly_plan', 'profit', 'settlement'] as ScenarioKey[]).forEach((scenario) => {
            const unit = props.projectsData?.[projectName]?.[scenario];
            summary[scenario].sales = Number(summary[scenario].sales ?? 0) + Number(unit?.sales ?? 0);
            summary[scenario].expense = Number(summary[scenario].expense ?? 0) + Number(unit?.expense ?? 0);
            summary[scenario].profit = Number(summary[scenario].profit ?? 0) + metricValue(unit, 'profit');
            summary[scenario].is_forecast = Boolean(summary[scenario].is_forecast || unit?.is_forecast);
        });
        return summary;
    }, {
        yearly_plan: { sales: 0, expense: 0, profit: 0 },
        profit: { sales: 0, expense: 0, profit: 0 },
        settlement: { sales: 0, expense: 0, profit: 0 },
    } as Required<SummaryData>);
});

const summaryUnit = (scenario: ScenarioKey) =>
    props.summaryData?.[scenario] ?? fallbackSummary.value[scenario];

const activeComparison = computed(() =>
    comparisonPairs.find(pair => pair.key === selectedComparisonKey.value) ?? comparisonPairs[0]
);

const summary = computed(() => {
    const base = metricValue(summaryUnit(activeComparison.value.base), props.activeView);
    const target = metricValue(summaryUnit(activeComparison.value.target), props.activeView);
    const variance = target - base;
    const achievementRate = base === 0 ? null : (target / base) * 100;

    return {
        base,
        target,
        variance,
        achievementRate,
        baseForecast: Boolean(summaryUnit(activeComparison.value.base)?.is_forecast),
        targetForecast: Boolean(summaryUnit(activeComparison.value.target)?.is_forecast),
    };
});

const hasBase = computed(() => Math.abs(summary.value.base) > 0);
const baseScenarioLabel = computed(() => scenarioLabels[activeComparison.value.base]);
const targetScenarioLabel = computed(() => scenarioLabels[activeComparison.value.target]);
const baseMetricLabel = computed(() => `${metricLabel.value}${baseScenarioLabel.value}`);
const targetMetricLabel = computed(() => `${metricLabel.value}${targetScenarioLabel.value}`);
const achievementLabel = computed(() => {
    if (props.activeView === 'expense') return `${baseScenarioLabel.value}比`;
    if (activeComparison.value.base === 'yearly_plan' && activeComparison.value.target === 'settlement') {
        return '目標達成率';
    }
    if (activeComparison.value.base === 'profit' && activeComparison.value.target === 'settlement') {
        return '計画達成率';
    }
    return `${baseScenarioLabel.value}比`;
});
const varianceLabel = computed(() => `${metricLabel.value}差分`);
const varianceIsPositive = computed(() => {
    if (summary.value.variance === 0) return false;
    return props.activeView === 'expense'
        ? summary.value.variance < 0
        : summary.value.variance > 0;
});
const varianceStatusClass = computed(() => {
    if (summary.value.variance === 0) return 'bar-finance-kpi__value--neutral';
    return varianceIsPositive.value
        ? 'bar-finance-kpi__value--positive'
        : 'bar-finance-kpi__value--negative';
});

const formatCurrency = (value: number) => `${yenFormatter.format(Math.round(value))}円`;

const formatSignedCurrency = (value: number) => {
    const rounded = Math.round(value);
    if (rounded === 0) return '0円';
    if (rounded < 0) return `△${formatCurrency(Math.abs(rounded))}`;
    return `+${formatCurrency(rounded)}`;
};

const formatPercent = (value: number | null) => {
    if (value === null || !Number.isFinite(value)) return '—';
    return `${value.toFixed(1)}%`;
};

const chartData = computed(() => {
    const labels = projectLabels.value;
    const variances = labels.map((label) =>
        projectScenarioValue(label, activeComparison.value.target) - projectScenarioValue(label, activeComparison.value.base)
    );
    const datasets = [
        {
            label: baseScenarioLabel.value,
            backgroundColor: scenarioColors[activeComparison.value.base],
            data: labels.map(label => projectScenarioValue(label, activeComparison.value.base)),
        },
        {
            label: targetScenarioLabel.value,
            backgroundColor: scenarioColors[activeComparison.value.target],
            data: labels.map(label => projectScenarioValue(label, activeComparison.value.target)),
        },
        {
            label: '差分',
            backgroundColor: variances.map(value => value < 0 ? '#dc2626' : '#16a34a'),
            data: variances,
        },
    ];
    return {
        labels,
        datasets
    };
});

const chartOptions = computed(() => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y' as const,
        plugins: {
            legend: {
                position: 'top' as const,
                labels: {
                    color: 'gray',
                    font: {
                        size: 14,
                        family: 'Noto Sans JP'
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function (context: any) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.x !== null) {
                            const value = Number(context.parsed.x ?? 0);
                            label += context.dataset.label === '差分'
                                ? formatSignedCurrency(value)
                                : formatCurrency(value);
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: '金額 (¥)'
                },
                ticks: {
                    callback: function (value: any) {
                        return compactCurrency.format(value);
                    }
                }
            },
            y: {
                ticks: {
                    autoSkip: false
                }
            }
        }
    };
});

const chartHeight = computed(() => {
    const baseHeight = 180;
    const heightPerProject = 44;
    return baseHeight + (projectLabels.value.length * heightPerProject);
});
</script>

<template>
    <div class="bar-finance">
        <section class="bar-finance-summary">
            <div class="bar-finance-summary__header">
                <div>
                    <p class="bar-finance-summary__title">{{ metricLabel }}集計</p>
                    <p class="bar-finance-summary__period">{{ periodLabel || '選択期間' }}</p>
                </div>
                <div class="bar-finance-summary__actions">
                    <label class="bar-finance-compare">
                        <span class="bar-finance-compare__label">比較</span>
                        <select v-model="selectedComparisonKey" class="bar-finance-compare__select">
                            <option
                                v-for="pair in comparisonPairs"
                                :key="pair.key"
                                :value="pair.key"
                            >
                                {{ pair.label }}
                            </option>
                        </select>
                    </label>
                    <span class="bar-finance-summary__count">{{ projectLabels.length }}件</span>
                </div>
            </div>
            <div class="bar-finance-kpis">
                <article class="bar-finance-kpi">
                    <span class="bar-finance-kpi__label">{{ baseMetricLabel }}</span>
                    <strong class="bar-finance-kpi__value">
                        {{ formatCurrency(summary.base) }}<span v-if="summary.baseForecast" class="bar-finance-kpi__forecast">＊</span>
                    </strong>
                </article>
                <article class="bar-finance-kpi">
                    <span class="bar-finance-kpi__label">{{ targetMetricLabel }}</span>
                    <strong class="bar-finance-kpi__value">
                        {{ formatCurrency(summary.target) }}<span v-if="summary.targetForecast" class="bar-finance-kpi__forecast">＊</span>
                    </strong>
                    <span class="bar-finance-kpi__meta">{{ achievementLabel }} {{ formatPercent(summary.achievementRate) }}</span>
                </article>
                <article class="bar-finance-kpi">
                    <span class="bar-finance-kpi__label">{{ varianceLabel }}</span>
                    <strong class="bar-finance-kpi__value" :class="varianceStatusClass">
                        {{ formatSignedCurrency(summary.variance) }}
                    </strong>
                    <span class="bar-finance-kpi__meta">{{ baseScenarioLabel }}比 {{ hasBase ? formatPercent(summary.variance / Math.abs(summary.base) * 100) : '—' }}</span>
                </article>
            </div>
        </section>
        <div class="chart-container" :style="{ height: chartHeight + 'px' }">
            <Bar :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>

<style scoped>
.bar-finance {
    width: 100%;
}

.bar-finance-summary {
    border: 1px solid var(--normalBorder);
    border-radius: 6px;
    background: var(--background-color);
    padding: 16px;
    margin-bottom: 18px;
}

.bar-finance-summary__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 14px;
}

.bar-finance-summary__actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

.bar-finance-summary__title {
    color: var(--normalText);
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 4px;
}

.bar-finance-summary__period,
.bar-finance-summary__count,
.bar-finance-kpi__label,
.bar-finance-kpi__meta {
    color: gray;
    font-size: 12px;
    line-height: 1.4;
}

.bar-finance-summary__period {
    margin: 0;
}

.bar-finance-summary__count {
    white-space: nowrap;
}

.bar-finance-compare {
    display: flex;
    align-items: center;
    gap: 6px;
}

.bar-finance-compare__label {
    color: gray;
    font-size: 12px;
}

.bar-finance-compare__select {
    border: 1px solid var(--normalBorder);
    border-radius: 4px;
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 12px;
    min-height: 30px;
    padding: 4px 8px;
}

.bar-finance-kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.bar-finance-kpi {
    border: 1px solid var(--normalBorder);
    border-radius: 6px;
    padding: 12px;
    min-width: 0;
}

.bar-finance-kpi__label,
.bar-finance-kpi__meta {
    display: block;
}

.bar-finance-kpi__value {
    display: block;
    color: var(--normalText);
    font-size: clamp(18px, 2vw, 26px);
    line-height: 1.25;
    margin: 8px 0 4px;
    overflow-wrap: anywhere;
}

.bar-finance-kpi__value--positive {
    color: #15803d;
}

.bar-finance-kpi__value--negative {
    color: #b91c1c;
}

.bar-finance-kpi__value--neutral {
    color: var(--normalText);
}

.bar-finance-kpi__forecast {
    font-size: 12px;
    vertical-align: super;
}

.chart-container {
    position: relative;
    min-height: 320px;
    width: 100%;
}

@media screen and (max-width: 959px) {
    .bar-finance-summary {
        padding: 14px;
    }

    .bar-finance-summary__header {
        flex-direction: column;
    }

    .bar-finance-summary__actions {
        justify-content: flex-start;
        width: 100%;
    }

    .bar-finance-kpis {
        grid-template-columns: 1fr;
    }
}
</style>
