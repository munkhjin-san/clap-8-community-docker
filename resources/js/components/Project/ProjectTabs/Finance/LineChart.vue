<template>
  <div class="case-line-chart">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup lang="ts">
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { computed, withDefaults } from 'vue';
import { Line } from 'vue-chartjs';

type ChartPoint = {
  label: string;
  amount: number;
  count: number;
};

type MetricKey = 'amount' | 'count';
type MemberSeries = {
  label: string;
  points: ChartPoint[];
};
type StageSeries = {
  label: string;
  values: number[];
};

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
);
const focusMarkerPlugin = {
  id: 'focusMarker',
  afterDraw(chart: any) {
    const index = chart.options?.plugins?.focusMarker?.index;
    if (index == null) return;
    const xScale = chart.scales.x;
    if (!xScale) return;
    const x = xScale.getPixelForValue(index);
    const { top, bottom } = chart.chartArea;
    const ctx = chart.ctx;
    ctx.save();
    ctx.strokeStyle = 'rgba(239,68,68,0.35)';
    ctx.setLineDash([4, 4]);
    ctx.beginPath();
    ctx.moveTo(x, top);
    ctx.lineTo(x, bottom);
    ctx.stroke();
    ctx.restore();
  },
};
const valueLabelPlugin = {
  id: 'valueLabels',
  afterDatasetsDraw(chart: any, _args: any, opts: any) {
    if (!opts?.enabled) return;
    const datasetIndex = 0;
    const dataset = chart.data.datasets[datasetIndex];
    if (!dataset) return;
    const meta = chart.getDatasetMeta(datasetIndex);
    const ctx = chart.ctx;
    ctx.save();
    ctx.font = '11px sans-serif';
    ctx.fillStyle = '#0f172a';
    ctx.textAlign = 'center';
    meta.data.forEach((point: any, idx: number) => {
      const value = dataset.data[idx];
      if (value == null || Number.isNaN(value)) return;
      ctx.fillText(formatAbbrev(Number(value)), point.x, point.y - 8);
    });
    ctx.restore();
  },
};
ChartJS.register(focusMarkerPlugin, valueLabelPlugin);

const props = withDefaults(
  defineProps<{
    series?: ChartPoint[];
    metrics?: MetricKey[];
    memberSeries?: MemberSeries[];
    stageSeries?: StageSeries[];
    aggregateSeries?: ChartPoint[];
    aggregateMetrics?: MetricKey[];
    actualSeries?: number[];
    labels?: string[];
    targetSeries?: number[];
    mode?: 'aggregate' | 'member' | 'stage';
    futureStartIndex?: number | null;
    focusStartIndex?: number | null;
    focusLength?: number;
    projectionSeries?: (number | null)[];
    unitLabel?: string;
  }>(),
  {
    series: () => [],
    metrics: () => ['amount', 'count'],
    memberSeries: () => [],
    stageSeries: () => [],
    aggregateSeries: () => [],
    aggregateMetrics: () => ['amount'],
    actualSeries: () => [],
    labels: () => [],
    targetSeries: () => [],
    mode: 'aggregate',
    futureStartIndex: null,
    focusStartIndex: null,
    focusLength: 1,
    projectionSeries: () => [],
    unitLabel: '円'
  },
);

const activeMode = computed<'aggregate' | 'member' | 'stage'>(() => {
  if (props.mode === 'stage' && (props.stageSeries.length || props.actualSeries.length)) {
    return 'stage';
  }
  if (props.mode === 'member' && props.memberSeries.length) {
    return 'member';
  }
  if (!props.mode) {
    if (props.stageSeries.length || props.actualSeries.length) return 'stage';
    if (props.memberSeries.length) return 'member';
  }
  return 'aggregate';
});

const resolvedLabels = computed(() => {
  if (props.labels.length) return props.labels;
  if (activeMode.value === 'member' && props.memberSeries.length) {
    return props.memberSeries[0]?.points.map(point => point.label) ?? [];
  }
  if (activeMode.value === 'stage' && props.stageSeries.length) {
    return props.stageSeries[0]?.values.map((_, idx) => props.series[idx]?.label ?? `Period ${idx + 1}`) ?? [];
  }
  if (activeMode.value === 'aggregate' && props.aggregateSeries.length) {
    return props.aggregateSeries.map(point => point.label);
  }
  return props.series.map(point => point.label);
});

const toRGBA = (hex: string, alpha: number) => {
  const normalized = hex.replace('#', '');
  if (normalized.length !== 6) {
    return `rgba(0,0,0,${alpha})`;
  }
  const bigint = parseInt(normalized, 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};
const formatAbbrev = (value: number) => {
  const abs = Math.abs(value);
  if (abs >= 100000000) return `${(value / 100000000).toFixed(1)}億`;
  if (abs >= 10000) return `${(value / 10000).toFixed(1)}万`;
  if (abs >= 1000) return `${(value / 1000).toFixed(1)}k`;
  return `${value}`;
};

const segmentVisualConfig = (color: string) => {
  const focusStart = props.focusStartIndex ?? null;
  const focusLen = props.focusLength ?? 1;
  const futureStart = props.futureStartIndex ?? null;
  const hasFocus = focusStart != null;
  const isFuture = (index: number) => futureStart != null && index >= futureStart;
  const isInFocus = (index: number) =>
    !hasFocus || (focusStart != null && index >= focusStart && index < focusStart + focusLen);
  return {
    segment: {
      borderDash: (ctx: any) => (isFuture(ctx.p0DataIndex) ? [6, 6] : undefined),
      borderColor: (ctx: any) => {
        let base = color;
        if (isFuture(ctx.p0DataIndex)) {
          base = toRGBA(color, 0.35);
        }
        if (!isInFocus(ctx.p0DataIndex)) {
          return toRGBA(base, 0.35);
        }
        return base;
      },
    },
    pointBackgroundColor: (ctx: any) => (isInFocus(ctx.dataIndex) ? color : toRGBA(color, 0.35)),
    pointBorderColor: (ctx: any) => (isInFocus(ctx.dataIndex) ? color : toRGBA(color, 0.35)),
    pointRadius: (ctx: any) => (isInFocus(ctx.dataIndex) ? 6 : 3),
  };
};

const chartData = computed(() => {
  const labels = resolvedLabels.value;

  if (activeMode.value === 'stage') {
    const palette = [
      '#2563eb',
      '#0ea5e9',
      '#10b981',
      '#f97316',
      '#facc15',
      '#a855f7',
      '#ec4899',
    ];

    const datasets: any[] = props.stageSeries.map((series, index) => {
      const color = palette[index % palette.length];
      return {
        label: series.label,
        data: series.values,
        borderColor: color,
        backgroundColor: toRGBA(color, 0.3),
        tension: 0.3,
        fill: index === 0 ? 'origin' : '-1',
        stack: 'weighted',
        ...segmentVisualConfig(color),
      };
    });

    if (props.actualSeries.length) {
      const hasActual = props.actualSeries.some(value => value > 0);
      if (hasActual) {
        datasets.push({
          label: '実績',
          data: props.actualSeries,
          borderColor: '#1f2937',
          backgroundColor: 'rgba(31, 41, 55, 0.1)',
          tension: 0.2,
          fill: false,
          stack: 'actual',
          ...segmentVisualConfig('#1f2937'),
        });
      }
    }

    if (props.targetSeries.length) {
      const hasTarget = props.targetSeries.some(value => value > 0);
      if (hasTarget) {
        datasets.push({
          label: '目標値',
          data: props.targetSeries,
          borderColor: '#ef4444',
          backgroundColor: 'rgba(239, 68, 68, 0.05)',
          borderDash: [6, 4],
          tension: 0.15,
          fill: false,
          stack: 'target',
          ...segmentVisualConfig('#ef4444'),
        });
      }
    }
    if (props.projectionSeries.length && props.projectionSeries.some(value => value != null)) {
      datasets.push({
        label: '到達予測',
        data: props.projectionSeries,
        borderColor: '#0f172a',
        pointBackgroundColor: '#0f172a',
        pointBorderColor: '#0f172a',
        pointRadius: 8,
        showLine: false,
        borderWidth: 0,
        spanGaps: true,
      });
    }

    return {
      labels,
      datasets,
    };
  }

  if (activeMode.value === 'member' && props.memberSeries.length) {
    const palette = [
      '#2563eb',
      '#f97316',
      '#10b981',
      '#dc2626',
      '#a855f7',
      '#14b8a6',
      '#facc15',
      '#ec4899',
      '#6b7280',
      '#fb7185',
    ];

    const datasets = props.memberSeries.map((member, index) => {
      const color = palette[index % palette.length];
      return {
        label: member.label || `メンバー${index + 1}`,
        data: member.points.map(point => point.amount),
        borderColor: color,
        backgroundColor: toRGBA(color, 0.2),
        tension: 0.25,
        fill: false,
        ...segmentVisualConfig(color),
      };
    });

    return {
      labels,
      datasets,
    };
  }

  const source = props.aggregateSeries.length ? props.aggregateSeries : props.series;
  const metrics = props.aggregateSeries.length ? props.aggregateMetrics : props.metrics;

  const datasets: any[] = [];

  if (metrics.includes('amount')) {
    datasets.push({
      label: '金額 (円)',
      data: source.map(point => point.amount),
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37, 99, 235, 0.15)',
      tension: 0.25,
      fill: true,
      ...segmentVisualConfig('#2563eb'),
    });
  }

  if (metrics.includes('count')) {
    datasets.push({
      label: '案件数',
      data: source.map(point => point.count),
      borderColor: '#f97316',
      backgroundColor: 'rgba(249, 115, 22, 0.15)',
      tension: 0.25,
      fill: true,
      yAxisID: datasets.length ? 'y1' : 'y',
      ...segmentVisualConfig('#f97316'),
    });
  }

  return {
    labels,
    datasets: props.projectionSeries.length && props.projectionSeries.some(value => value != null)
      ? [
          ...datasets,
          {
            label: '到達予測',
            data: props.projectionSeries,
            borderColor: '#0f172a',
            pointBackgroundColor: '#0f172a',
            pointBorderColor: '#0f172a',
            pointRadius: 8,
            showLine: false,
            borderWidth: 0,
            spanGaps: true,
          },
        ]
      : datasets,
  };
});

const chartOptions = computed(() => {
  if (activeMode.value === 'stage') {
    return {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index' as const,
      },
      plugins: {
        legend: {
          position: 'top' as const,
        },
        tooltip: {
          callbacks: {
            label(context: any) {
              const value = context.parsed.y;
              return `${context.dataset.label}: ${new Intl.NumberFormat('ja-JP').format(value)} ${props.unitLabel}`;
            },
          },
        },
        focusMarker: {
          index: props.focusStartIndex ?? null,
        },
        valueLabels: {
          enabled: true,
        },
      },
      scales: {
        x: {
          ticks: {
            maxRotation: 0,
          },
        },
        y: {
          stacked: props.stageSeries.length > 0,
          beginAtZero: true,
          ticks: {
            callback(value: string | number) {
              return `${new Intl.NumberFormat('ja-JP').format(Number(value))} ${props.unitLabel}`;
            },
          },
        },
      },
    };
  }

  if (activeMode.value === 'member') {
    return {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index' as const,
      },
      plugins: {
        legend: {
          position: 'top' as const,
        },
        tooltip: {
          callbacks: {
            label(context: any) {
              const value = context.parsed.y;
              return `${context.dataset.label}: ${new Intl.NumberFormat('ja-JP').format(value)} ${props.unitLabel}`;
            },
          },
        },
        focusMarker: {
          index: props.focusStartIndex ?? null,
        },
        valueLabels: {
          enabled: false,
        },
      },
      scales: {
        x: {
          ticks: {
            maxRotation: 0,
          },
        },
        y: {
          beginAtZero: true,
          ticks: {
            callback(value: string | number) {
              return `${new Intl.NumberFormat('ja-JP').format(Number(value))} ${props.unitLabel}`;
            },
          },
        },
      },
    };
  }

  const usingAmount = props.metrics.includes('amount');
  const usingCount = props.metrics.includes('count');
  const usingDualAxis = usingAmount && usingCount;

  const scales: {
    x?: { ticks: { maxRotation: number } };
    y?: {
      beginAtZero: boolean;
      ticks: { callback: (value: string | number) => string };
    };
    y1?: {
      position: 'right';
      beginAtZero: boolean;
      grid: { drawOnChartArea: boolean };
      ticks: { callback: (value: string | number) => string };
    };
  } = {
    x: {
      ticks: {
        maxRotation: 0,
      },
    },
  };

  if (usingAmount) {
    scales.y = {
      beginAtZero: true,
      ticks: {
        callback(value: string | number) {
          return `${new Intl.NumberFormat('ja-JP').format(Number(value))} ${props.unitLabel}`;
        },
      },
    };
  }

  if (usingDualAxis) {
    scales.y1 = {
      position: 'right' as const,
      beginAtZero: true,
      grid: {
        drawOnChartArea: false,
      },
      ticks: {
        callback(value: string | number) {
          return `${Number(value)} 件`;
        },
      },
    };
  } else if (usingCount && !usingAmount) {
    scales.y = {
      beginAtZero: true,
      ticks: {
        callback(value: string | number) {
          return `${Number(value)} 件`;
        },
      },
    };
  }

  return {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      intersect: false,
      mode: 'index' as const,
    },
    plugins: {
      legend: {
        position: 'top' as const,
      },
      tooltip: {
        callbacks: {
          label(context: any) {
            const value = context.parsed.y;
            if (context.dataset.label?.includes('金額')) {
              return `${context.dataset.label}: ${new Intl.NumberFormat('ja-JP').format(value)} ${props.unitLabel}`;
            }
            return `${context.dataset.label}: ${value} 件`;
          },
        },
      },
      focusMarker: {
        index: props.focusStartIndex ?? null,
      },
      valueLabels: {
        enabled: true,
      },
    },
    scales,
  };
});
</script>

<style scoped>
.case-line-chart {
  width: 100%;
  min-height: 260px;
  margin-top: 20px;
}
</style>
