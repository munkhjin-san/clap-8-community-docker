<template>
    <div class="mx-5">
      <div class="flex flex-wrap items-center gap-4 md:justify-between justify-center">
        <div class="text-sm"><span class="p-[5px] text-xs bg-[var(--bg3)] mr-[10px]">期間</span> {{ selectProject?.date_start && selectProject.date_end ? `${DateTime.fromISO(selectProject.date_start).toLocaleString(DateTime.DATE_SHORT)}  ~  ${DateTime.fromISO(selectProject.date_end).toLocaleString(DateTime.DATE_SHORT)}` : '未設定' }}</div>
        <div class="flex items-center gap-4 flex-wrap md:justify-normal justify-center">
          <!-- <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs uppercase p-[5px] tracking-wide bg-[var(--bg3)]">グラフ表示</span>
            <div class="flex rounded border border-[var(--normalBorder)] overflow-hidden text-sm">
              <button
                v-for="option in displayOptions"
                :key="option.value"
                type="button"
                class="px-3 py-1 transition"
                :class="option.value === mode ? 'bg-[var(--hoverBorder)] !text-white' : 'bg-[var(--background-color)]'"
                @click="mode = option.value"
              >
                {{ option.label }}
              </button>
            </div>
          </div> -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs uppercase p-[5px] tracking-wide bg-[var(--bg3)]">粒度</span>
            <div class="flex rounded border border-[var(--normalBorder)] overflow-hidden text-sm">
              <button
                v-for="option in grainOptions"
                :key="option.value"
                type="button"
                class="px-3 py-1 transition"
                :class="option.value === grain ? 'bg-[var(--hoverBorder)] !text-white' : 'bg-[var(--background-color)]'"
                @click="setGrain(option.value)"
              >
                {{ option.label }}
              </button>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs uppercase p-[5px] tracking-wide bg-[var(--bg3)]">期間</span>
            <div class="flex items-center gap-2">
              <button class="ghost-button" type="button" @click="shiftPeriod(-1)">前へ</button>
              <span class="text-sm font-medium min-w-[140px] text-center">{{ periodLabel }}</span>
              <button class="ghost-button" type="button" @click="shiftPeriod(1)">次へ</button>
            </div>
          </div>
        </div>
        
      </div>
        
        <div v-if="loading" class="mt-4 text-sm opacity-70">
            データを読み込んでいます...
        </div>
        <div v-else-if="hasData" class="mt-4">
            <span class="text-sm text-[gray]">着地予測＝確度別加重（A=90%, B=70%, C=50%, D,E=30%）</span>
            <table class="report-table mt-2">
                <thead>
                    <tr>
                        <th class="h-cell">営業ステージ</th>
                        <th>メンバー</th>
                        <th v-for="bucket in buckets" :key="bucket.key">{{ bucket.label }}</th>
                        <th>合計</th>
                        <th v-if="hasPrivilage">詳細</th>
                    </tr>
                </thead>

                <tbody>
                    <!-- group by status -->
                    <template v-for="group in grouped" :key="group.status">
                      <!-- one row per member inside the status group -->
                      <tr v-for="(row, idx) in group.rows" :key="row.memberId" :class="{ 'border-diff-row': group.status === '目標値' && idx === group.rows.length - 1 }">
                          <!-- render status cell once with rowspan -->
                          <td :class="{ 'border-diff': group.status === '目標値'}" v-if="idx === 0" style="border-right: 1px solid var(--calendarBorder); position: sticky; left: 0; background-color: var(--background-color);" :rowspan="group.rows.length">{{ group.status }}</td>
                          <td>{{ row.memberName }}</td>

                          <!-- render each quarter cell -->
                          <td v-for="bucket in buckets" :key="bucket.key">
                          {{ formatCell(row.q[bucket.key]) }}
                          </td>
                          <td>
                              {{ formatCell(totalOfRow(row)) }}
                          </td>
                          <td v-if="hasPrivilage">
                            <span @click="emit('view', row.caseId)" class="jump-link">詳細</span>
                          </td>
                      </tr>
                    </template>
                </tbody>

                <!-- optional totals per status -->
                <tfoot>
                    <tr>
                      <th class="h-cell">合計</th>
                      <th></th>
                      <th v-for="bucket in buckets" :key="'t-' + bucket.key">
                          {{ formatCell(totalByBucket[bucket.key]) }}
                      </th>
                      <th>
                          {{ formatCell(totalByAllBuckets) }}
                      </th>
                      <th v-if="hasPrivilage"></th>
                    </tr>
                    <tr>
                      <th class="h-cell">着地予測 = Σ(売上金額 × 確度率)</th>
                      <th></th>
                      <th v-for="bucket in buckets" :key="'p-' + bucket.key">
                        {{ formatCell(totalByPrediction[bucket.key]) }}
                      </th>
                      <th>
                        {{ formatCell(totalByAllPrediction) }}
                      </th>
                      <th v-if="hasPrivilage"></th>
                    </tr>
                    <tr>
                      <th class="h-cell">合計目標値</th>
                      <th></th>
                      <th v-for="bucket in buckets" :key="bucket.key">
                        {{ formatCell(totalByGoal[bucket.key]) }}
                      </th>
                      <th>
                        {{ formatCell(totalByAllGoal) }}
                      </th>
                      <th v-if="hasPrivilage"></th>
                    </tr>
                </tfoot>
            </table>
            <LineChart
              mode="status"
              :labels="chartLabels"
              :status-series="statusChartSeries"
              :actual-series="actualChartSeries"
              :target-series="targetChartSeries"
            />
        </div>
        <div v-else class="mt-4 text-sm opacity-70">
            表示可能な案件データがありません。
        </div>
    </div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { Project } from '@/interface/projectInterface';
import { DateTime } from 'luxon';
import { computed, ref, watch } from 'vue';
import LineChart from './LineChart.vue';

const statusOrder = ['目標値', '★竣工済', '①受注済未竣工', '②確度A', '③確度B', '④確度C', '⑤確度D、E'];
const fallbackStatus = '未分類';

type Grain = 'month' | 'quarter' | 'year';

const props = defineProps<{
    selectProject: Project;
    refreshKey?: number;
    hasPrivilage: boolean;
}>();
const emit = defineEmits<{
  (e: 'view', val: number | null): void,
}>()
const api = useApi();
const grainOptions = [
  { label: '月次', value: 'month' as const },
  { label: '四半期', value: 'quarter' as const },
  { label: '年次', value: 'year' as const },
];
const displayOptions = [
  { label: 'ステージ', value: 'stage' as const },
  { label: 'メンバー', value: 'member' as const },
  { label: '集計', value: 'aggregate' as const}
]
type Mode = 'stage' | 'member' | 'aggregate'
const grain = ref<Grain>('quarter');
const mode = ref<Mode>('stage');
const currentPeriod = ref(DateTime.now().startOf('month').minus({ months: 1 }));
const loading = ref(false);

type CaseRecord = {
  id: number;
  project_id: number;
  report_date: string | null;
  status: string;
  client_name: string | null;
  case_count: number;
  amount: number;
  notes: string | null;
  state: 'draft' | 'submitted';
  submitted_at: string | null;
  reporter: {
    id: number;
    name: string;
    icon_path: string | null;
    icon_bg: string | null;
  } | null;
};

const cases = ref<CaseRecord[]>([]);



const loadCases = async() => {
  if (!props.selectProject) return;
  
  const data = await api.get(
    `/projects/${props.selectProject.id}/cases`,
    { state: 'submitted' },
    { loadingRef: loading },
  );
  cases.value = (data?.cases ?? []) as CaseRecord[];
  
}
watch(
  () => [props.selectProject?.id, props.refreshKey],
  async ([projectId]) => {
    if (!projectId) {
      cases.value = [];
      return;
    }
    await loadCases();
  },
  { immediate: true },
);
const periodLabel = computed(() => {
  switch (grain.value) {
    case 'month':
      return currentPeriod.value.toFormat('yyyy年M月');
    case 'quarter': {
      const quarter = Math.floor((currentPeriod.value.month - 1) / 3) + 1;
      return `${currentPeriod.value.year}年 第${quarter}四半期`;
    }
    case 'year':
      return `${currentPeriod.value.year}年`;
    default:
      return currentPeriod.value.toFormat('yyyy年M月');
  }
});
const setGrain = (next: Grain) => {
  if (grain.value === next) return;
  grain.value = next;
  if (next === 'year') {
    currentPeriod.value = currentPeriod.value.startOf('year');
  } else if (next === 'quarter') {
    currentPeriod.value = currentPeriod.value.startOf('quarter');
  }
}

const shiftPeriod = (step: number) => {
  if (grain.value === 'month') {
    currentPeriod.value = currentPeriod.value.plus({ months: step });
  } else if (grain.value === 'quarter') {
    currentPeriod.value = currentPeriod.value.plus({ months: step * 3 }).startOf('quarter');
  } else {
    currentPeriod.value = currentPeriod.value.plus({ years: step }).startOf('year');
  }
}

type Bucket = {
  key: string;
  label: string;
  start: DateTime;
  end: DateTime;
};

const buildBuckets = (nextGrain: Grain, base: DateTime): Bucket[] => {
  if (nextGrain === 'month') {
    return [-1, 0, 1].map(offset => {
      const start = base.plus({ months: offset }).startOf('month');
      return {
        key: start.toFormat('yyyy-LL'),
        label: start.toFormat('yyyy年M月'),
        start,
        end: start.endOf('month'),
      };
    });
  }

  if (nextGrain === 'quarter') {
    const baseQuarter = base.startOf('quarter');
    return [0, 1, 2, 3].map(idx => {
      const start = baseQuarter.plus({ months: idx * 3 });
      const quarter = Math.floor((start.month - 1) / 3) + 1;
      return {
        key: `${start.year}Q${quarter}`,
        label: `${start.year}年度/第${quarter}Q`,
        start,
        end: start.endOf('quarter'),
      };
    });
  }

  return [-1, 0, 1].map(offset => {
    const start = base.plus({ years: offset }).startOf('year');
    return {
      key: `${start.year}`,
      label: `${start.year}年度`,
      start,
      end: start.endOf('year'),
    };
  });
}

const buckets = computed<Bucket[]>(() => buildBuckets(grain.value, currentPeriod.value));

type Cell = { amount: number; count: number } | null;
type Row = { memberId: number; memberName: string; q: Record<string, Cell>, caseId: number };
type Group = { status: string; rows: Row[] };

const createEmptyRow = (): Record<string, Cell> => {
  const template: Record<string, Cell> = {};
  buckets.value.forEach(bucket => {
    template[bucket.key] = null;
  });
  return template;
}

const findBucket = (dt: DateTime): Bucket | undefined => {
  return buckets.value.find(bucket => dt >= bucket.start && dt <= bucket.end);
}

const grouped = computed<Group[]>(() => {
  if (!buckets.value.length) return [];

  const groupMaps = new Map<string, Map<number, Row>>();
  statusOrder.forEach(status => groupMaps.set(status, new Map()));
  groupMaps.set(fallbackStatus, new Map());

  cases.value.forEach(entry => {
    if (entry.state !== 'submitted' || !entry.report_date) return;
    const dt = DateTime.fromISO(entry.report_date);
    if (!dt.isValid) return;

    const bucket = findBucket(dt);
    if (!bucket) return;

    const statusKey = groupMaps.has(entry.status) ? entry.status : fallbackStatus;
    const rowsMap = groupMaps.get(statusKey)!;
    const reporterId = entry.reporter?.id ?? 0;
    const reporterName = entry.reporter?.name ?? '未設定メンバー';
    const caseId = entry?.id
    let row = rowsMap.get(reporterId);
    if (!row) {
      row = {
        memberId: reporterId,
        memberName: reporterName,
        caseId: caseId,
        q: createEmptyRow(),
      };
      rowsMap.set(reporterId, row);
    }

    const baseCell = row.q[bucket.key] ?? { amount: 0, count: 0 };
    baseCell.amount += entry.amount ?? 0;
    baseCell.count += entry.case_count ?? 0;
    row.q[bucket.key] = baseCell;
  });

  const groups: Group[] = [];
  statusOrder.forEach(status => {
    const rows = Array.from(groupMaps.get(status)!.values());
    if (rows.length) {
      rows.sort((a, b) => a.memberName.localeCompare(b.memberName, 'ja'));
      groups.push({ status, rows });
    }
  });

  const fallbackRows = Array.from(groupMaps.get(fallbackStatus)!.values());
  if (fallbackRows.length) {
    fallbackRows.sort((a, b) => a.memberName.localeCompare(b.memberName, 'ja'));
    groups.push({ status: fallbackStatus, rows: fallbackRows });
  }

  return groups;
});
type Status = typeof statusOrder[number];

// Weights for "prediction" (A–E only). Tweak in one place.
const PREDICTION_WEIGHTS: Record<Status, number> = {
  '目標値': 0,
  '★竣工済': 0,
  '①受注済未竣工': 0,
  '②確度A': 0.9,
  '③確度B': 0.7,
  '④確度C': 0.5,
  '⑤確度D、E': 0.3,
};
const totalByAllGoal = computed<{ amount: number; count: number } | null>(() => {
  const buckets = totalByGoal.value;
  if (!buckets) return null;

  let amount = 0;
  let count = 0;

  for (const cell of Object.values(buckets) as Cell[]) {
    if (!cell) continue;
    const a = Number(cell.amount);
    const c = Number(cell.count);
    if (Number.isFinite(a)) amount += a;
    // if (Number.isFinite(c)) count += c;
  }

  return amount === 0 && count === 0 ? null : { amount, count };
})
const totalByGoal = computed<Record<string, Cell>>(() => {
  const totals: Record<string, Cell> = {}
  for (const b of buckets.value) totals[b.key] = null;
  const goal = grouped.value.find(g => g.status === '目標値')
  if (!goal) return totals
  for (const row of goal.rows) {
    for (const [period, cell] of Object.entries(row.q)) {
        if (!cell) continue;
        const a = Number(cell.amount);
        const c = Number(cell.count);
        if (!Number.isFinite(a) || !Number.isFinite(c)) continue;

        if (!totals[period]) totals[period] = { amount: 0, count: 0 };

        (totals[period] as { amount: number; count: number }).amount += a;
        // (totals[period] as { amount: number; count: number }).count  += c;

      }
  }
  return totals
})
const totalByPrediction = computed<Record<string, Cell>>(() => {
  // initialize all periods to null
  const totals: Record<string, Cell> = {};
  for (const b of buckets.value) totals[b.key] = null;

  for (const group of grouped.value) {
    const w = PREDICTION_WEIGHTS[group.status as Status] ?? 0;
    if (w <= 0) continue; // skip completed/ordered or unknowns

    for (const row of group.rows) {
      for (const [period, cell] of Object.entries(row.q)) {
        if (!cell) continue;
        const a = Number(cell.amount);
        const c = Number(cell.count);
        if (!Number.isFinite(a) || !Number.isFinite(c)) continue;

        if (!totals[period]) totals[period] = { amount: 0, count: 0 };

        // weighted amount, raw count
        (totals[period] as { amount: number; count: number }).amount += a * w;
        // (totals[period] as { amount: number; count: number }).count  += c;

        // If you ALSO want weighted counts, use: += c * w
      }
    }
  }

  return totals;
});
const totalByAllPrediction = computed<{ amount: number; count: number } | null>(() => {
  const buckets = totalByPrediction.value;
  if (!buckets) return null;

  let amount = 0;
  let count = 0;

  for (const cell of Object.values(buckets) as Cell[]) {
    if (!cell) continue;
    const a = Number(cell.amount);
    const c = Number(cell.count);
    if (Number.isFinite(a)) amount += a;
    // if (Number.isFinite(c)) count += c;
  }

  return amount === 0 && count === 0 ? null : { amount, count };
});
const totalByBucket = computed<Record<string, Cell>>(() => {
  const totals: Record<string, Cell> = {};
  buckets.value.forEach(bucket => {
    totals[bucket.key] = null;
  });

  grouped.value.forEach(group => {
    if (group.status == '目標値') return;
    group.rows.forEach(row => {
      Object.entries(row.q).forEach(([key, cell]) => {
        if (!cell) return;
        if (!totals[key]) {
          totals[key] = { amount: 0, count: 0 };
        }
        (totals[key] as { amount: number; count: number }).amount += cell.amount;
        // (totals[key] as { amount: number; count: number }).count += cell.count;
      });
    });
  });

  return totals;
});
const totalByAllBuckets = computed<{ amount: number; count: number } | null>(() => {
  const buckets = totalByBucket.value;
  if (!buckets) return null;

  let amount = 0;
  let count = 0;

  for (const cell of Object.values(buckets) as Cell[]) {
    if (!cell) continue;
    const a = Number(cell.amount);
    const c = Number(cell.count);
    if (Number.isFinite(a)) amount += a;
    // if (Number.isFinite(c)) count += c;
  }

  return amount === 0 && count === 0 ? null : { amount, count };
});
const chartLabels = computed(() => buckets.value.map(bucket => bucket.label));

const statusChartSeries = computed(() => {
  // optional: preindex groups by status for faster lookups
  const byStatus = new Map<string, typeof grouped.value[number]>();
  for (const g of grouped.value) byStatus.set(g.status, g);

  const values = buckets.value.map(bucket => {
    let total = 0;

    for (const status of statusOrder) {
      const weight = PREDICTION_WEIGHTS[status as Status] ?? 0;
      if (weight <= 0) continue;

      const group = byStatus.get(status);
      if (!group) continue;

      for (const row of group.rows) {
        const cell = row.q[bucket.key];
        if (cell) total += cell.amount * weight;
      }
    }

    return Math.round(total);
  });

  return values.some(v => v > 0)
    ? [{ label: "着地予測", values }]
    : [];
});


const actualStatusSet = new Set<Status>(['★竣工済', '①受注済未竣工']);
const actualChartSeries = computed(() => {
  return buckets.value.map(bucket => {
    let total = 0;
    grouped.value.forEach(group => {
      if (!actualStatusSet.has(group.status as Status)) return;
      group.rows.forEach(row => {
        const cell = row.q[bucket.key];
        if (cell) {
          total += cell.amount;
        }
      });
    });
    return total;
  });
});
const targetChartSeries = computed(() => {
  const targetGroup = grouped.value.find(group => group.status === '目標値');
  if (!targetGroup) {
    return buckets.value.map(() => 0);
  }
  return buckets.value.map(bucket => {
    let total = 0;
    targetGroup.rows.forEach(row => {
      const cell = row.q[bucket.key];
      if (cell) {
        total += cell.amount;
      }
    });
    return Math.round(total);
  });
});
const totalOfRow = (row: Row): Cell => {
  let amount = 0;
  let count = 0;
  Object.values(row.q).forEach(cell => {
    if (!cell) return;
    amount += cell.amount;
    // count += cell.count;
  });
  return amount + count > 0 ? { amount, count } : null;
}

const formatCell = (cell: Cell | { amount: number; count: number } | undefined) => {
  if (!cell) return '—';
  const amt = new Intl.NumberFormat('ja-JP').format(cell.amount);
  return cell.count > 0 ? `${amt}円/${cell.count}件` : `${amt}円`;
}

const hasData = computed(() => grouped.value.some(group => group.rows.length > 0));
</script>
<style scoped>
/* Optional styling, adjust to your Tailwind or vanilla CSS setup */
.report-table {
    width: 100%;
    border-collapse: separate;
    font-size: 13px;
    line-height: 1.5;
    white-space: nowrap;
}
.report-table th {
  padding: 8px 10px;
  text-align: left;
  background-color: var(--bg3);
}
.border-diff {
  border-bottom: 2px solid var(--primary-color) !important;
}
tr.border-diff-row > td {
  border-bottom: 2px solid var(--primary-color);
}
.report-table td {
    padding: 10px;
    border-bottom: 1px solid var(--calendarBorder);
    font-weight: 400;
    text-align: left;
    border-left: none;
}
.report-table th:nth-child(1),
.report-table td:nth-child(1),
.report-table th:nth-child(2),
.report-table td:nth-child(2) {
  text-align: left;
}
.h-cell {
    width: 60px;
    min-width: 60px;
    background-color: var(--bg3);
    border-bottom: none;
    text-align: end;
    position: sticky;
    left: 0;
    z-index: 1;
}
.report-table tfoot th {
  background: var(--bg3);
  font-weight: 600;
}
.ghost-button {
  padding: 6px 12px;
  border: 1px solid var(--normalBorder);
  background: var(--background-color);
  transition: background 0.2s ease, border-color 0.2s ease;
}
.ghost-button:hover {
  background: var(--bg3);
  border-color: var(--hoverBorder);
}
</style>
