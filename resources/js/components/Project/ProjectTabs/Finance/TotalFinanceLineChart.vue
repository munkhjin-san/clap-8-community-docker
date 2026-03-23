<script setup lang="ts">
import { computed } from 'vue';
import {
    Chart as ChartJS,
    registerables,
    type ChartData,
    type ChartOptions,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(...registerables);

type GroupingMode = 'range' | 'fiscal'
type MetricKey = 'sales' | 'expense' | 'profit'
type ScenarioKey = 'yearly_plan' | 'profit' | 'settlement'

interface UnitData {
    sales?: number
    expense?: number
    profit?: number
    has_data?: boolean
}

interface PeriodTotalsEntry {
    yearly_plan?: UnitData | null
    profit?: UnitData | null
    settlement?: UnitData | null
}

interface PeriodCell {
    year: number
    month: number
    period: string
}

const props = defineProps<{
    grouping: GroupingMode
    activeView: MetricKey
    periods: PeriodCell[]
    periodTotals: Record<string, PeriodTotalsEntry>
    activeFiscalYears: number[]
    comparisonPeriodTotals: Record<number, Record<string, PeriodTotalsEntry>>
}>();

const scenarioStyles: Array<{
    key: ScenarioKey
    label: string
    borderColor: string
    backgroundColor: string
}> = [
    {
        key: 'yearly_plan',
        label: '予算',
        borderColor: '#0f766e',
        backgroundColor: 'rgba(15, 118, 110, 0.16)',
    },
    {
        key: 'profit',
        label: '計画',
        borderColor: '#ea580c',
        backgroundColor: 'rgba(234, 88, 12, 0.16)',
    },
    {
        key: 'settlement',
        label: '実績',
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37, 99, 235, 0.16)',
    },
];

const compactCurrency = new Intl.NumberFormat('ja-JP', {
    style: 'currency',
    currency: 'JPY',
    notation: 'compact',
    maximumFractionDigits: 1,
});

const fullCurrency = new Intl.NumberFormat('ja-JP', {
    style: 'currency',
    currency: 'JPY',
    maximumFractionDigits: 0,
});

const monthLabel = (year: number, month: number) => `${year}/${String(month).padStart(2, '0')}`;

const metricValue = (entry: PeriodTotalsEntry | undefined, scenario: ScenarioKey, metric: MetricKey) => {
    const unit = entry?.[scenario];
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

const chartOptions = computed<ChartOptions<'line'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: 'gray',
                font: {
                    size: 13,
                    family: 'Noto Sans JP',
                },
                usePointStyle: true,
                boxWidth: 10,
            },
        },
        tooltip: {
            callbacks: {
                label(context) {
                    const label = context.dataset.label ?? '';
                    return `${label}: ${fullCurrency.format(Number(context.parsed.y ?? 0))}`;
                },
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback(value) {
                    return compactCurrency.format(Number(value));
                },
            },
        },
        x: {
            ticks: {
                maxRotation: 0,
                autoSkip: false,
            },
        },
    },
}));

const rangeChartData = computed<ChartData<'line'>>(() => ({
    labels: props.periods.map((period) => monthLabel(period.year, period.month)),
    datasets: scenarioStyles.map((scenario) => ({
        label: scenario.label,
        data: props.periods.map((period) =>
            metricValue(props.periodTotals[period.period], scenario.key, props.activeView)
        ),
        borderColor: scenario.borderColor,
        backgroundColor: scenario.backgroundColor,
        pointBackgroundColor: scenario.borderColor,
        pointBorderColor: '#fff',
        pointBorderWidth: 1,
        pointRadius: 3,
        pointHoverRadius: 5,
        tension: 0.25,
        borderWidth: 2,
        fill: false,
    })),
}));

const fiscalCharts = computed(() =>
    props.activeFiscalYears.map((fiscalYear) => {
        const periods = buildFiscalPeriods(fiscalYear);
        const yearTotals = props.comparisonPeriodTotals[fiscalYear] ?? {};

        return {
            key: `fy-${fiscalYear}`,
            title: `${fiscalYear}`,
            data: {
                labels: periods.map((period) => monthLabel(period.year, period.month)),
                datasets: scenarioStyles.map((scenario) => ({
                    label: scenario.label,
                    data: periods.map((period) =>
                        metricValue(yearTotals[period.period], scenario.key, props.activeView)
                    ),
                    borderColor: scenario.borderColor,
                    backgroundColor: scenario.backgroundColor,
                    pointBackgroundColor: scenario.borderColor,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.25,
                    borderWidth: 2,
                    fill: false,
                })),
            } satisfies ChartData<'line'>,
        };
    })
);
</script>

<template>
    <div v-if="grouping === 'range'" class="chart-container">
        <Line :data="rangeChartData" :options="chartOptions" />
    </div>
    <div v-else class="grid gap-6 w-full">
        <section
            v-for="chart in fiscalCharts"
            :key="chart.key"
            class="rounded border border-solid border-[var(--normalBorder)] bg-[var(--background-color)] p-4"
        >
            <div class="mb-3 text-sm font-semibold text-[var(--normalText)]">
                {{ chart.title }}
            </div>
            <div class="chart-container">
                <Line :data="chart.data" :options="chartOptions" />
            </div>
        </section>
    </div>
</template>

<style scoped>
.chart-container {
    position: relative;
    min-height: 360px;
    width: 100%;
}
</style>
