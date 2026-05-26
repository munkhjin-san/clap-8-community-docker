<script setup lang="ts">
import { computed, ref } from 'vue';
import {
    Chart as ChartJS,
    registerables,
    type ChartData,
    type ChartOptions,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(...registerables);

type GroupingMode = 'range' | 'fiscal'
type MetricKey = 'sales' | 'expense' | 'profit'
type ScenarioKey = 'yearly_plan' | 'profit' | 'settlement'

interface UnitData {
    sales?: number
    expense?: number
    profit?: number
    has_data?: boolean
    is_forecast?: boolean
}

interface PeriodEntry {
    yearly_plan?: UnitData | null
    profit?: UnitData | null
    settlement?: UnitData | null
}

interface PeriodCell {
    year: number
    month: number
    period: string
}

type MonthlyScenarioValues = {
    value: number
    isForecast: boolean
}

type MonthlyChartRow = {
    period: string
    label: string
    values: Record<ScenarioKey, MonthlyScenarioValues>
}

type ChartSummary = {
    base: number
    target: number
    variance: number
    achievementRate: number | null
    baseForecast: boolean
    targetForecast: boolean
}

const props = defineProps<{
    grouping: GroupingMode
    periods: PeriodCell[]
    projectNames: string[]
    projectPeriods: Record<string, Record<string, PeriodEntry>>
    activeFiscalYears: number[]
    comparisonProjectPeriods: Record<string, Record<number, Record<string, PeriodEntry>>>
    activeView: MetricKey
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
    key: string
    base: ScenarioKey
    target: ScenarioKey
    label: string
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
const projectLabels = computed(() =>
    props.projectNames.length ? props.projectNames : Object.keys(props.projectPeriods ?? {})
);
const activeComparison = computed(() =>
    comparisonPairs.find(pair => pair.key === selectedComparisonKey.value) ?? comparisonPairs[0]
);
const baseScenarioLabel = computed(() => scenarioLabels[activeComparison.value.base]);
const targetScenarioLabel = computed(() => scenarioLabels[activeComparison.value.target]);
const baseMetricLabel = computed(() => `${metricLabel.value}${baseScenarioLabel.value}`);
const targetMetricLabel = computed(() => `${metricLabel.value}${targetScenarioLabel.value}`);
const varianceLabel = computed(() => `${metricLabel.value}差分`);
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

const normalizeUnit = (unit?: UnitData | null): UnitData | null => {
    if (!unit || unit.has_data === false) return null;

    const sales = Number(unit.sales ?? 0);
    const expense = Number(unit.expense ?? 0);
    const explicitProfit = Number(unit.profit);

    return {
        sales,
        expense,
        profit: Number.isFinite(explicitProfit) ? explicitProfit : sales - expense,
        has_data: true,
        is_forecast: Boolean(unit.is_forecast),
    };
};

const metricValue = (unit: UnitData | null | undefined, metric: MetricKey) => {
    if (!unit) return 0;
    if (metric === 'profit') {
        const explicitProfit = Number(unit.profit);
        if (Number.isFinite(explicitProfit)) return explicitProfit;
        return Number(unit.sales ?? 0) - Number(unit.expense ?? 0);
    }
    return Number(unit[metric] ?? 0);
};

const buildFiscalPeriods = (fiscalYear: number): PeriodCell[] => {
    const periods: PeriodCell[] = [];

    for (let month = 3; month <= 12; month += 1) {
        periods.push({
            year: fiscalYear,
            month,
            period: `${fiscalYear}-${String(month).padStart(2, '0')}`,
        });
    }

    for (let month = 1; month <= 2; month += 1) {
        periods.push({
            year: fiscalYear + 1,
            month,
            period: `${fiscalYear + 1}-${String(month).padStart(2, '0')}`,
        });
    }

    return periods;
};

const formatPeriodLabel = (period: PeriodCell) => `${period.year}/${String(period.month).padStart(2, '0')}`;

const emptyScenarioValues = (): Record<ScenarioKey, MonthlyScenarioValues> => ({
    yearly_plan: { value: 0, isForecast: false },
    profit: { value: 0, isForecast: false },
    settlement: { value: 0, isForecast: false },
});

const buildRows = (
    periods: PeriodCell[],
    periodResolver: (projectName: string, period: string) => PeriodEntry | undefined,
) => periods.map((period): MonthlyChartRow => {
    const values = emptyScenarioValues();

    projectLabels.value.forEach((projectName) => {
        const entry = periodResolver(projectName, period.period);

        (['yearly_plan', 'profit', 'settlement'] as ScenarioKey[]).forEach((scenario) => {
            const unit = normalizeUnit(entry?.[scenario]);
            if (!unit) return;

            values[scenario].value += metricValue(unit, props.activeView);
            values[scenario].isForecast = values[scenario].isForecast || Boolean(unit.is_forecast);
        });
    });

    return {
        period: period.period,
        label: formatPeriodLabel(period),
        values,
    };
});

const summarizeRows = (rows: MonthlyChartRow[]): ChartSummary => {
    const base = rows.reduce((sum, row) => sum + row.values[activeComparison.value.base].value, 0);
    const target = rows.reduce((sum, row) => sum + row.values[activeComparison.value.target].value, 0);
    const variance = target - base;

    return {
        base,
        target,
        variance,
        achievementRate: base === 0 ? null : (target / base) * 100,
        baseForecast: rows.some(row => row.values[activeComparison.value.base].isForecast),
        targetForecast: rows.some(row => row.values[activeComparison.value.target].isForecast),
    };
};

const chartDataForRows = (rows: MonthlyChartRow[]): ChartData<'bar'> => {
    const variances = rows.map(row =>
        row.values[activeComparison.value.target].value - row.values[activeComparison.value.base].value
    );

    return {
        labels: rows.map(row => row.label),
        datasets: [
            {
                label: baseScenarioLabel.value,
                backgroundColor: scenarioColors[activeComparison.value.base],
                data: rows.map(row => row.values[activeComparison.value.base].value),
            },
            {
                label: targetScenarioLabel.value,
                backgroundColor: scenarioColors[activeComparison.value.target],
                data: rows.map(row => row.values[activeComparison.value.target].value),
            },
            {
                label: '差分',
                backgroundColor: variances.map(value => value < 0 ? '#dc2626' : '#16a34a'),
                data: variances,
            },
        ],
    };
};

const rangeRows = computed(() =>
    buildRows(
        props.periods,
        (projectName, period) => props.projectPeriods?.[projectName]?.[period]
    )
);
const rangeSummary = computed(() => summarizeRows(rangeRows.value));
const rangeChartData = computed<ChartData<'bar'>>(() => chartDataForRows(rangeRows.value));

const fiscalCharts = computed(() =>
    props.activeFiscalYears.map((fiscalYear) => {
        const rows = buildRows(
            buildFiscalPeriods(fiscalYear),
            (projectName, period) => props.comparisonProjectPeriods?.[projectName]?.[fiscalYear]?.[period]
        );

        return {
            key: `fy-${fiscalYear}`,
            title: `${fiscalYear}年度`,
            rows,
            summary: summarizeRows(rows),
            data: chartDataForRows(rows),
        };
    })
);

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

const varianceIsPositive = (variance: number) => {
    if (variance === 0) return false;
    return props.activeView === 'expense'
        ? variance < 0
        : variance > 0;
};

const varianceStatusClass = (variance: number) => {
    if (variance === 0) return 'bar-finance-kpi__value--neutral';
    return varianceIsPositive(variance)
        ? 'bar-finance-kpi__value--positive'
        : 'bar-finance-kpi__value--negative';
};

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: 'gray',
                font: {
                    size: 14,
                    family: 'Noto Sans JP',
                },
            },
        },
        tooltip: {
            callbacks: {
                label(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }

                    const value = Number(context.parsed.y ?? 0);
                    label += context.dataset.label === '差分'
                        ? formatSignedCurrency(value)
                        : formatCurrency(value);

                    return label;
                },
            },
        },
    },
    scales: {
        x: {
            title: {
                display: true,
                text: '月',
            },
            ticks: {
                autoSkip: false,
                maxRotation: 0,
                minRotation: 0,
            },
        },
        y: {
            beginAtZero: true,
            grace: '12%',
            title: {
                display: true,
                text: '金額 (¥)',
            },
            ticks: {
                callback(value) {
                    return compactCurrency.format(Number(value));
                },
            },
        },
    },
}));

const chartHeight = computed(() => {
    if (props.grouping === 'fiscal') return 420;
    return Math.max(360, Math.min(560, 240 + (rangeRows.value.length * 24)));
});
</script>

<template>
    <div class="bar-finance">
        <section class="bar-finance-summary">
            <div class="bar-finance-summary__header">
                <div>
                    <p class="bar-finance-summary__title">{{ metricLabel }}月別推移</p>
                    <p class="bar-finance-summary__period">選択プロジェクト {{ projectLabels.length }}件</p>
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
                    <span class="bar-finance-summary__count">{{ grouping === 'range' ? rangeRows.length : 12 }}ヶ月</span>
                </div>
            </div>
            <div v-if="grouping === 'range'" class="bar-finance-kpis">
                <article class="bar-finance-kpi">
                    <span class="bar-finance-kpi__label">{{ baseMetricLabel }}</span>
                    <strong class="bar-finance-kpi__value">
                        {{ formatCurrency(rangeSummary.base) }}<span v-if="rangeSummary.baseForecast" class="bar-finance-kpi__forecast">＊</span>
                    </strong>
                </article>
                <article class="bar-finance-kpi">
                    <span class="bar-finance-kpi__label">{{ targetMetricLabel }}</span>
                    <strong class="bar-finance-kpi__value">
                        {{ formatCurrency(rangeSummary.target) }}<span v-if="rangeSummary.targetForecast" class="bar-finance-kpi__forecast">＊</span>
                    </strong>
                    <span class="bar-finance-kpi__meta">{{ achievementLabel }} {{ formatPercent(rangeSummary.achievementRate) }}</span>
                </article>
                <article class="bar-finance-kpi">
                    <span class="bar-finance-kpi__label">{{ varianceLabel }}</span>
                    <strong class="bar-finance-kpi__value" :class="varianceStatusClass(rangeSummary.variance)">
                        {{ formatSignedCurrency(rangeSummary.variance) }}
                    </strong>
                    <span class="bar-finance-kpi__meta">{{ baseScenarioLabel }}比 {{ rangeSummary.base ? formatPercent(rangeSummary.variance / Math.abs(rangeSummary.base) * 100) : '—' }}</span>
                </article>
            </div>
        </section>

        <div v-if="grouping === 'range'" class="chart-container" :style="{ height: chartHeight + 'px' }">
            <Bar :data="rangeChartData" :options="chartOptions" />
        </div>

        <div v-else class="bar-finance-grid">
            <section
                v-for="chart in fiscalCharts"
                :key="chart.key"
                class="bar-finance-fiscal-card"
            >
                <div class="bar-finance-fiscal-card__header">
                    <div>
                        <p class="bar-finance-summary__title">{{ chart.title }}</p>
                        <p class="bar-finance-summary__period">{{ metricLabel }}月別推移</p>
                    </div>
                </div>
                <div class="bar-finance-kpis">
                    <article class="bar-finance-kpi">
                        <span class="bar-finance-kpi__label">{{ baseMetricLabel }}</span>
                        <strong class="bar-finance-kpi__value">
                            {{ formatCurrency(chart.summary.base) }}<span v-if="chart.summary.baseForecast" class="bar-finance-kpi__forecast">＊</span>
                        </strong>
                    </article>
                    <article class="bar-finance-kpi">
                        <span class="bar-finance-kpi__label">{{ targetMetricLabel }}</span>
                        <strong class="bar-finance-kpi__value">
                            {{ formatCurrency(chart.summary.target) }}<span v-if="chart.summary.targetForecast" class="bar-finance-kpi__forecast">＊</span>
                        </strong>
                        <span class="bar-finance-kpi__meta">{{ achievementLabel }} {{ formatPercent(chart.summary.achievementRate) }}</span>
                    </article>
                    <article class="bar-finance-kpi">
                        <span class="bar-finance-kpi__label">{{ varianceLabel }}</span>
                        <strong class="bar-finance-kpi__value" :class="varianceStatusClass(chart.summary.variance)">
                            {{ formatSignedCurrency(chart.summary.variance) }}
                        </strong>
                        <span class="bar-finance-kpi__meta">{{ baseScenarioLabel }}比 {{ chart.summary.base ? formatPercent(chart.summary.variance / Math.abs(chart.summary.base) * 100) : '—' }}</span>
                    </article>
                </div>
                <div class="chart-container chart-container--fiscal">
                    <Bar :data="chart.data" :options="chartOptions" />
                </div>
            </section>
        </div>
    </div>
</template>

<style>
.bar-finance {
    width: 100%;
}

.bar-finance-summary,
.bar-finance-fiscal-card {
    border: 1px solid var(--normalBorder);
    border-radius: 6px;
    background: var(--background-color);
    padding: 16px;
    margin-bottom: 18px;
}

.bar-finance-summary__header,
.bar-finance-fiscal-card__header {
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

.bar-finance-grid {
    display: grid;
    gap: 18px;
}

.bar-finance .chart-container {
    position: relative;
    min-height: 320px;
    width: 100%;
}

.bar-finance .chart-container--fiscal {
    height: 420px;
}

@media screen and (max-width: 959px) {
    .bar-finance-summary,
    .bar-finance-fiscal-card {
        padding: 14px;
    }

    .bar-finance-summary__header,
    .bar-finance-fiscal-card__header {
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
