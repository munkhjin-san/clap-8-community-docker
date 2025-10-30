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
type StatusSeries = {
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

const props = withDefaults(
  defineProps<{
    series?: ChartPoint[];
    metrics?: MetricKey[];
    memberSeries?: MemberSeries[];
    statusSeries?: StatusSeries[];
    actualSeries?: number[];
    labels?: string[];
    targetSeries?: number[];
    mode?: 'aggregate' | 'member' | 'status';
  }>(),
  {
    series: () => [],
    metrics: () => ['amount', 'count'],
    memberSeries: () => [],
    statusSeries: () => [],
    actualSeries: () => [],
    labels: () => [],
    targetSeries: () => [],
    mode: 'aggregate',
  },
);

const activeMode = computed<'aggregate' | 'member' | 'status'>(() => {
  if (props.mode === 'status' && (props.statusSeries.length || props.actualSeries.length)) {
    return 'status';
  }
  if (props.mode === 'member' && props.memberSeries.length) {
    return 'member';
  }
  if (!props.mode) {
    if (props.statusSeries.length || props.actualSeries.length) return 'status';
    if (props.memberSeries.length) return 'member';
  }
  return 'aggregate';
});

const resolvedLabels = computed(() => {
  if (props.labels.length) return props.labels;
  if (activeMode.value === 'member' && props.memberSeries.length) {
    return props.memberSeries[0]?.points.map(point => point.label) ?? [];
  }
  if (activeMode.value === 'status' && props.statusSeries.length) {
    // Fall back to aggregate series labels if provided
    if (props.series.length) {
      return props.series.map(point => point.label);
    }
    return props.statusSeries[0]?.values.map((_, idx) => `Period ${idx + 1}`) ?? [];
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

const chartData = computed(() => {
  const labels = resolvedLabels.value;

  if (activeMode.value === 'status') {
    const palette = [
      '#2563eb',
      '#0ea5e9',
      '#10b981',
      '#f97316',
      '#facc15',
      '#a855f7',
      '#ec4899',
    ];

    const datasets: any[] = [];

    props.statusSeries.forEach((series, index) => {
      const color = palette[index % palette.length];
      datasets.push({
        label: series.label,
        data: series.values,
        borderColor: color,
        backgroundColor: toRGBA(color, 0.3),
        tension: 0.3,
        fill: index === 0 ? 'origin' : '-1',
        stack: 'weighted',
        pointRadius: 3,
      });
    });

    if (props.actualSeries.length) {
      const hasActual = props.actualSeries.some(value => value > 0);
      if (hasActual) {
        datasets.push({
          label: '実績 (受注済/竣工済)',
          data: props.actualSeries,
          borderColor: '#1f2937',
          backgroundColor: 'rgba(31, 41, 55, 0.1)',
          tension: 0.2,
          fill: false,
          pointRadius: 4,
          borderWidth: 2,
          stack: 'actual',
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
          pointRadius: 4,
          borderWidth: 2,
          stack: 'target',
        });
      }
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
        pointRadius: 4,
      };
    });

    return {
      labels,
      datasets,
    };
  }

  const datasets: any[] = [];

  if (props.metrics.includes('amount')) {
    datasets.push({
      label: '金額 (円)',
      data: props.series.map(point => point.amount),
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37, 99, 235, 0.15)',
      tension: 0.25,
      fill: true,
      pointRadius: 4,
    });
  }

  if (props.metrics.includes('count')) {
    datasets.push({
      label: '案件数',
      data: props.series.map(point => point.count),
      borderColor: '#f97316',
      backgroundColor: 'rgba(249, 115, 22, 0.15)',
      tension: 0.25,
      fill: true,
      pointRadius: 4,
      yAxisID: datasets.length ? 'y1' : 'y',
    });
  }

  return {
    labels,
    datasets,
  };
});

const chartOptions = computed(() => {
  if (activeMode.value === 'status') {
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
              return `${context.dataset.label}: ${new Intl.NumberFormat('ja-JP').format(value)} 円`;
            },
          },
        },
      },
      scales: {
        x: {
          ticks: {
            maxRotation: 0,
          },
        },
        y: {
          stacked: props.statusSeries.length > 0,
          beginAtZero: true,
          ticks: {
            callback(value: string | number) {
              return `${new Intl.NumberFormat('ja-JP').format(Number(value))} 円`;
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
              return `${context.dataset.label}: ${new Intl.NumberFormat('ja-JP').format(value)} 円`;
            },
          },
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
              return `${new Intl.NumberFormat('ja-JP').format(Number(value))} 円`;
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
          return `${new Intl.NumberFormat('ja-JP').format(Number(value))} 円`;
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
              return `${context.dataset.label}: ${new Intl.NumberFormat('ja-JP').format(value)} 円`;
            }
            return `${context.dataset.label}: ${value} 件`;
          },
        },
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
