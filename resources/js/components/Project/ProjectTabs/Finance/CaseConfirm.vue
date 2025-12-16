<template>
  <div class="case-confirm space-y-4">
    <section class="case-card case-header">
      <div class="case-header__project">
        <p class="case-eyebrow">期間</p>
        <p class="case-header__value">
          {{ selectProject?.date_start && selectProject.date_end ? `${DateTime.fromISO(selectProject.date_start).toLocaleString(DateTime.DATE_SHORT)}  ~  ${DateTime.fromISO(selectProject.date_end).toLocaleString(DateTime.DATE_SHORT)}` : '未設定' }}
        </p>
      </div>
      <div class="case-header__controls">
        <div class="control-group">
          <div class="control-chip-group">
            <button
              v-for="option in grainOptions"
              :key="option.value"
              type="button"
              class="chip-button"
              :class="{ active: option.value === grain }"
              @click="setGrain(option.value)"
            >
              {{ option.label }}
            </button>
          </div>
        </div>
        <div class="control-group period-group">
          <!-- <span class="case-chip-label">期間</span> -->
          <div class="period-nav">
            <div @click="shiftPeriod(-1)" class="work-prevmonth justify-center">
                <Back size="13"/>
            </div>
            <span class="period-label">{{ periodLabel }}</span>
            <div @click="shiftPeriod(1)" class="work-nextmonth justify-center">
                <Back size="13" class="rotate-180"/>
            </div>
          </div>
        </div>
        <div class="control-group control-group--actions">
          <button
            class="settings-button"
            type="button"
            ref="settingsButtonRef"
            @click.stop="toggleSettings"
          >
            表示設定
          </button>
          <Transition name="focus-fade">
            <div
              v-if="settingsOpen"
              class="settings-popover"
              ref="settingsPanelRef"
              @click.stop
            >
              <div class="settings-section">
                <p>表表示</p>
                <div class="chip-group">
                  <button
                    v-for="option in tableModeOptions"
                    :key="option.value"
                    type="button"
                    class="chip-button"
                    :class="{ active: tableMode === option.value }"
                    @click="tableMode = option.value; settingsOpen = false"
                  >
                    {{ option.label }}
                  </button>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </section>

    <section class="case-card hero-card" v-if="stageLegend.length">
      
      <div class="stage-legend">
        <span v-for="item in stageLegend" :key="item.label" class="legend-chip">
          {{ item.label }} = {{ item.weight }}%
        </span>
      </div>
    </section>

    <section class="case-card table-card">
      <div class="table-card__header">
        <div>
          <p class="case-eyebrow">実績テーブル</p>
        </div>
      </div>
      <div v-if="loading" class="mt-4 text-sm opacity-70">
        データを読み込んでいます...
      </div>
      <div v-else-if="hasData" class="table-section mt-4">
        <Transition name="focus-fade" mode="out-in">
          <div class="table-scroll" :key="`table-${visualScopeKey}-${tableMode}`">
            <table class="report-table mt-2">
              <thead>
                <tr>
                  <th class="h-cell">区分</th>
                  <th>メンバー</th>
                  <th v-for="bucket in buckets" :key="bucket.key" :class="cellClass(bucket.key)">{{ bucket.label }}</th>
                  <th>期間合計</th>
                  <!-- <th>評価</th> -->
                  <th v-if="hasPrivilage" class="subhead">詳細</th>
                </tr>
              </thead>
              <tbody v-if="tableMode === 'detail'">
                <template v-for="group in grouped" :key="group.status">
                  <tr
                    v-for="(row, idx) in group.rows"
                    :key="`${group.status}-${row.memberId}`"
                    :class="{ 'border-diff-row': group.status === '目標値' && idx === group.rows.length - 1 }"
                  >
                    <td
                      :class="[{ 'border-diff': group.status === '目標値'}, statusToneClass(group.status)]"
                      v-if="idx === 0"
                      style="border-right: 1px solid var(--calendarBorder); position: sticky; left: 0; background-color: var(--background-color); z-index: 1;"
                      :rowspan="group.rows.length"
                      :title="statusHint(group.status)"
                    >
                      <div class="status-cell">
                        <span>{{ group.status }}</span>
                        <span v-if="statusMini(group.status)" class="status-mini">{{ statusMini(group.status) }}</span>
                      </div>
                    </td>
                    <td>{{ row.memberName }}</td>
                    <td
                      v-for="bucket in buckets"
                      :key="bucket.key"
                      :class="cellClass(bucket.key)"
                    >
                      <div class="cell-value">{{ formatCell(row.q[bucket.key]) }}</div>
                      <div class="cell-flags">

                        <span
                          class="cell-chip"
                          v-if="cellContributionLabel(group.status, row, bucket.key)"
                          title="寄与 = 確度別加重の貢献値"
                        >
                          {{ cellContributionLabel(group.status, row, bucket.key) }}
                        </span>
                      </div>
                    </td>
                    <td>
                      {{ formatCell(totalOfRow(row)) }}
                    </td>

                    <td v-if="hasPrivilage">
                      <span @click="requestDetail(row, group.status)" class="jump-link">確認</span>
                    </td>
                  </tr>
                </template>
              </tbody>
              <tbody v-else>
                <tr v-for="row in summaryRows" :key="row.status" :class="{ 'border-diff-row': row.status === '目標値'}">
                  <td
                    class="sticky left-0 bg-[var(--background-color)] status-summary z-10"
                    :class="statusToneClass(row.status)"
                    style="border-right: 1px solid var(--calendarBorder);"
                    :title="statusHint(row.status)"
                  >
                    <div class="status-cell">
                      <span>{{ row.status }}</span>
                      <span v-if="statusMini(row.status)" class="status-mini">{{ statusMini(row.status) }}</span>
                    </div>
                  </td>
                  <td class="summary-pill-cell">
                    <span class="summary-pill">要約表示</span>
                  </td>
                  <td
                    v-for="bucket in buckets"
                    :key="bucket.key"
                    :class="cellClass(bucket.key)"
                  >
                    <div class="cell-value summary">
                      {{ formatCell(row.totals[bucket.key]) }}
                    </div>
                    <div class="cell-flags">
                      <span
                        class="cell-chip"
                        v-if="cellContributionLabel(row.status, row, bucket.key)"
                        title="寄与 = 確度別加重の貢献値"
                      >
                        {{ cellContributionLabel(row.status, row, bucket.key) }}
                      </span>
                    </div>
                  </td>
                  <td>
                    {{ formatCell(row.totalAll) }}
                  </td>
                  <td v-if="hasPrivilage">—</td>
                </tr>
              </tbody>
              <tfoot>
                <tr v-if="pipelineEnabled">
                  <th class="h-cell"><p>着地予測</p> 
                    <span class="status-mini">= 実績 + Σ(案件金額 × 確度)</span>
                  </th>
                  <th></th>
                  <th v-for="bucket in buckets" :key="'p-' + bucket.key" :class="cellClass(bucket.key)">
                    {{ formatCell(totalByPrediction[bucket.key]) }}
                  </th>
                  <th>
                    {{ formatCell(totalByAllPrediction) }}
                  </th>
                  <!-- <th></th> -->
                  <th v-if="hasPrivilage"></th>
                </tr>
                <tr v-else>
                  <th class="h-cell">実績合計</th>
                  <th></th>
                  <th v-for="bucket in buckets" :key="'a-' + bucket.key" :class="cellClass(bucket.key)">
                    {{ formatCell(totalByBucket[bucket.key]) }}
                  </th>
                  <th>
                    {{ formatCell(totalByAllBuckets) }}
                  </th>
                  <th v-if="hasPrivilage"></th>
                </tr>
                <tr v-if="goalEnabled">
                  <th class="h-cell">目標合計</th>
                  <th></th>
                  <th v-for="bucket in buckets" :key="bucket.key" :class="cellClass(bucket.key)">
                    {{ formatCell(totalByGoal[bucket.key]) }}
                  </th>
                  <th>
                    {{ formatCell(totalByAllGoal) }}
                  </th>
                  <!-- <th></th> -->
                  <th v-if="hasPrivilage"></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </Transition>
        <div class="perceived-loader" v-if="isVisualSwitching">
          <div class="perceived-loader__pulse"></div>
        </div>
      </div>
      <div v-else class="mt-4 text-sm opacity-70">
        表示可能な実績データがありません。
      </div>
    </section>

    <section class="case-card chart-card">
      <Transition name="focus-fade" mode="out-in">
        <div class="chart-shell" :key="`chart-${visualScopeKey}`">
          <div class="chart-extra-controls">
            <!-- <button class="chip-button" @click="toggleGraphModes">
              {{ showGraphModes ? 'ビューを閉じる' : '他のビュー' }}
            </button> -->
            <div class="chip-group">
              <button
                v-for="option in displayOptions"
                :key="`chart-${option.value}`"
                type="button"
                class="chip-button"
                :class="{ active: mode === option.value }"
                @click="mode = option.value"
              >
                {{ option.label }}
              </button>
            </div>
          </div>
          <LineChart
            :mode="mode"
            :labels="chartLabels"
            :unit-label="unitLabel"
            :stage-series="stageChartSeries"
            :actual-series="chartActualSeries"
            :target-series="chartTargetSeries"
            :member-series="memberChartSeries"
            :aggregate-series="chartAggregateSeries"
            :future-start-index="futureStartIndex"
            :focus-start-index="focusBucketIndex"
            :focus-length="focusHighlightLength"
          />
        </div>
      </Transition>
    </section>
  </div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { Project } from '@/interface/projectInterface';
import { DateTime } from 'luxon';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import LineChart from './LineChart.vue';
import {
  STAGE_PIPELINE_LIST,
  STAGE_LABEL,
  STAGE_WEIGHT,
  DELIVERY_LABEL,
  type RecordKind,
  type Stage,
  type DeliveryStatus,
} from '@/utils/case';
import { useRouter } from 'vue-router';
import Back from '@/components/Icons/Back.vue';

const pipelineStatusLabels: string[] = [];
const fallbackStatus = '未分類';

type Grain = 'day' | 'month' | 'quarter' | 'year';

type CaseTimelineEntry = {
  id: number;
  reportDate: string;
  amount: number;
  caseCount: number;
  kind: RecordKind;
  stage: Stage | null;
  delivery_status: DeliveryStatus | null;
};
type CaseTimeline = Record<string, CaseTimelineEntry[]>;
type CaseDetailPayload = {
  memberId: number;
  memberName: string;
  status: string;
  activeCase: CaseTimelineEntry | null;
  reportDate: string | null;
  timeline: CaseTimeline;
};

const props = defineProps<{
    selectProject: Project;
    refreshKey?: number;
    hasPrivilage: boolean;
}>();
const unitCode = computed(() => props.selectProject?.unit_id ?? 'JPY');
const unitLabel = computed(() => {
  if (unitCode.value === 'COUNT') return '件';
  if (unitCode.value === 'HOUR') return '時間';
  if (unitCode.value === 'CUSTOM') return props.selectProject?.custom_unit_label || '単位';
  return '円';
});
const hasForecast = computed(() => false);
const hasGoals = computed(() => props.selectProject?.has_goals ?? false);
const emit = defineEmits<{
  (e: 'view', val: CaseDetailPayload): void,
}>()
const api = useApi();
const grainOptions = [
  { label: '日々', value: 'day' as const}, 
  { label: '月次', value: 'month' as const },
  { label: '四半期', value: 'quarter' as const },
  { label: '年次', value: 'year' as const },
];
const displayOptions = [
  { label: '項目', value: 'stage' as const },
  { label: 'メンバー', value: 'member' as const },
  { label: '集計', value: 'aggregate' as const}
]
const tableModeOptions = [
  { label: '要約', value: 'summary' as const },
  { label: '詳細', value: 'detail' as const },
];
type Mode = 'stage' | 'member' | 'aggregate'
const grain = ref<Grain>('month');
const mode = ref<Mode>('stage');
const tableMode = ref<'detail' | 'summary'>('detail');
const viewScope = ref<'focus' | 'cumulative'>('focus');
const isVisualSwitching = ref(false);
const showGraphModes = ref(false);
const settingsOpen = ref(false);
const settingsButtonRef = ref<HTMLElement | null>(null);
const settingsPanelRef = ref<HTMLElement | null>(null);
let switchTimer: ReturnType<typeof setTimeout> | null = null;
const triggerVisualSwitch = () => {
  if (switchTimer) clearTimeout(switchTimer);
  isVisualSwitching.value = true;
  switchTimer = setTimeout(() => {
    isVisualSwitching.value = false;
  }, 220);
};
const toggleSettings = () => {
  settingsOpen.value = !settingsOpen.value;
};
const handleSettingsClickOutside = (event: MouseEvent) => {
  if (!settingsOpen.value) return;
  const target = event.target as Node;
  if (
    settingsButtonRef.value?.contains(target) ||
    settingsPanelRef.value?.contains(target)
  ) {
    return;
  }
  settingsOpen.value = false;
};
onMounted(() => {
  document.addEventListener('click', handleSettingsClickOutside);
});
onBeforeUnmount(() => {
  if (switchTimer) clearTimeout(switchTimer);
  document.removeEventListener('click', handleSettingsClickOutside);
});
const currentPeriod = ref(DateTime.now());
const loading = ref(false);

type CaseRecord = {
  id: number;
  project_id: number;
  report_date: string | null;
  status: string;
  kind: RecordKind;
  stage: Stage | null;
  delivery_status: DeliveryStatus | null;
  probability: number | null;
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
const DEFAULT_ACTUAL_STATUS_LABELS = ['実績', '進行中', '完了', 'キャンセル'];
const actualStatusLabels = computed(() => {
  const rows = props.selectProject?.actual_statuses ?? [];
  if (rows.length === 0) return DEFAULT_ACTUAL_STATUS_LABELS;
  return [...rows]
    .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
    .map(r => r.label || r.custom_label || '実績');
});

const visibleCases = computed(() => cases.value.filter(entry => {
  if (!hasForecast.value && pipelineStatusLabels.includes(entry.status)) return false;
  if (!hasGoals.value && entry.status === '目標値') return false;
  return true;
}));
const pipelineEnabled = computed(() => hasForecast.value);
const goalEnabled = computed(() => hasGoals.value);
const activePipelineLabels = computed(() => pipelineEnabled.value ? pipelineStatusLabels : []);
const statusOrder = computed(() => {
  const order: string[] = [];
  if (goalEnabled.value) order.push('目標値');
  order.push(...actualStatusLabels.value);
  if (pipelineEnabled.value) order.push(...pipelineStatusLabels);
  return order;
});

const stageLegend = computed(() => pipelineEnabled.value
  ? STAGE_PIPELINE_LIST.map(stage => ({
      label: STAGE_LABEL[stage],
      weight: Math.round(STAGE_WEIGHT[stage] * 100),
    }))
  : []);
const stageLabelToCode = Object.fromEntries(
  Object.entries(STAGE_LABEL).map(([code, label]) => [label, code as Stage]),
);
const stageWeightSum = computed(() =>
  activePipelineLabels.value.reduce((sum, label) => {
    const code = stageLabelToCode[label];
    return sum + (code ? STAGE_WEIGHT[code] ?? 0 : 0);
  }, 0)
);

const statusMetaMap = computed(() => {
  const meta: Record<string, { mini: string; hint: string; tone: 'actual' | 'target' | 'other' }> = {
    '目標値': { mini: '', hint: '年度・四半期ごとの目標値', tone: 'target' },
  };
  actualStatusLabels.value.forEach(label => {
    meta[label] = { mini: label === '実績' ? '' : '実績', hint: '実績項目', tone: 'actual' };
  });
  return meta;
});

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
    case 'day':
      return currentPeriod.value.toFormat('yyyy年M月d日')
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
  if (grain.value === 'day') {
    currentPeriod.value = currentPeriod.value.plus({ days: step });
  } else if (grain.value === 'month') {
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
  if (nextGrain === 'day') {
    return [-3, -2, -1, 0, 1, 2, 3].map(offset => {
      const start = base.plus({ days: offset }).startOf('day');
      return {
        key: start.toFormat('yyyy-MM-dd'),
        label: start.toFormat('yyyy年M月d日'),
        start,
        end: start.endOf('day'),
      }
    })
  }
  if (nextGrain === 'month') {
    return [-2, -1, 0, 1, 2].map(offset => {
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

  return [-2, -1, 0, 1, 2].map(offset => {
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
const bucketLabels = computed(() => buckets.value.map(bucket => bucket.label));
const nowBucketEnd = computed(() => {
  const now = DateTime.now();
  if (grain.value === 'day') return now.endOf('day');
  if (grain.value === 'month') return now.endOf('month');
  if (grain.value === 'quarter') return now.endOf('quarter');
  return now.endOf('year');
});
const isFutureBucketKey = (key: string) => {
  const bucket = buckets.value.find(b => b.key === key);
  if (!bucket) return false;
  return bucket.start > nowBucketEnd.value;
};
const futureStartIndex = computed(() => {
  const idx = buckets.value.findIndex(bucket => bucket.start > nowBucketEnd.value);
  return idx === -1 ? null : idx;
});

type Cell = { amount: number; count: number } | null;
type Row = { memberId: number; memberName: string; q: Record<string, Cell>; latestCase: CaseTimelineEntry | null; timeline: CaseTimeline };
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
  statusOrder.value.forEach(status => groupMaps.set(status, new Map()));
  groupMaps.set(fallbackStatus, new Map());

  visibleCases.value.forEach(entry => {
    if (entry.state !== 'submitted' || !entry.report_date) return;
    const dt = DateTime.fromISO(entry.report_date);
    if (!dt.isValid) return;

    const bucket = findBucket(dt);
    if (!bucket) return;

    const statusKey = groupMaps.has(entry.status)
      ? entry.status
      : (entry.kind === 'ACTUAL' && actualStatusLabels.value[0]) ? actualStatusLabels.value[0] : fallbackStatus;
    const rowsMap = groupMaps.get(statusKey)!;
    const reporterId = entry.reporter?.id ?? 0;
    const reporterName = entry.reporter?.name ?? '未設定メンバー';
    const caseMeta: CaseTimelineEntry = {
      id: entry.id,
      reportDate: entry.report_date,
      amount: entry.amount ?? 0,
      caseCount: entry.case_count ?? 0,
      kind: entry.kind,
      stage: entry.stage,
      delivery_status: entry.delivery_status,
    };
    let row = rowsMap.get(reporterId);
    if (!row) {
      row = {
        memberId: reporterId,
        memberName: reporterName,
        latestCase: caseMeta,
        q: createEmptyRow(),
        timeline: {},
      };
      rowsMap.set(reporterId, row);
    }
    const existingDate = row.latestCase?.reportDate ? DateTime.fromISO(row.latestCase.reportDate) : null;
    const nextDate = entry.report_date ? DateTime.fromISO(entry.report_date) : null;
    if (!existingDate || (nextDate && nextDate > existingDate)) {
      row.latestCase = caseMeta;
    }
    const periodKey = entry.report_date ?? bucket.start?.startOf('month').toISODate();
    if (periodKey) {
      if (!row.timeline[periodKey]) {
        row.timeline[periodKey] = [];
      }
      row.timeline[periodKey]!.push(caseMeta);
    }

    const baseCell = row.q[bucket.key] ?? { amount: 0, count: 0 };
    baseCell.amount += entry.amount ?? 0;
    baseCell.count += entry.case_count ?? 0;
    row.q[bucket.key] = baseCell;
  });

  const groups: Group[] = [];
  statusOrder.value.forEach(status => {
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
const goalGroupRows = computed(() => grouped.value.find(g => g.status === '目標値')?.rows ?? []);
const memberGoalMap = computed(() => {
  const map = new Map<number, Record<string, Cell>>();
  goalGroupRows.value.forEach(row => {
    const copy: Record<string, Cell> = {};
    Object.entries(row.q).forEach(([key, cell]) => {
      copy[key] = cell ? { amount: cell.amount, count: cell.count } : null;
    });
    map.set(row.memberId, copy);
  });
  return map;
});
const predictionWeights = computed<Record<string, number>>(() => {
  const base: Record<string, number> = {};
  actualStatusLabels.value.forEach(label => { base[label] = 1; });
  if (pipelineEnabled.value) {
    STAGE_PIPELINE_LIST.forEach(stage => {
      base[STAGE_LABEL[stage]] = STAGE_WEIGHT[stage];
    });
  }
  return base;
});
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
    if (Number.isFinite(c)) count += c;
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
        (totals[period] as { amount: number; count: number }).count  += c;

      }
  }
  return totals
})
const totalByPrediction = computed<Record<string, Cell>>(() => {
  // initialize all periods to null
  const totals: Record<string, Cell> = {};
  for (const b of buckets.value) totals[b.key] = null;

  for (const group of grouped.value) {
    const weight = predictionWeights.value[group.status] ?? 0;
    if (weight <= 0) continue; // skip non-pipeline rows

    for (const row of group.rows) {
      for (const [period, cell] of Object.entries(row.q)) {
        if (!cell) continue;
        const a = Number(cell.amount);
        const c = Number(cell.count);
        if (!Number.isFinite(a) || !Number.isFinite(c)) continue;

        if (!totals[period]) totals[period] = { amount: 0, count: 0 };

        // weighted amount, raw count
        (totals[period] as { amount: number; count: number }).amount += a * weight;
        (totals[period] as { amount: number; count: number }).count += c;
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
    if (Number.isFinite(c)) count += c;
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
        (totals[key] as { amount: number; count: number }).count += cell.count;
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
    if (Number.isFinite(c)) count += c;
  }

  return amount === 0 && count === 0 ? null : { amount, count };
});
const chartLabels = computed(() => buckets.value.map(bucket => bucket.label));

const focusBucketIndex = computed<number | null>(() => {
  if (!buckets.value.length) return null;
  const target = currentPeriod.value;
  const idx = buckets.value.findIndex(bucket => target >= bucket.start && target <= bucket.end);
  return idx >= 0 ? idx : 0;
});
const focusBucketKey = computed(() => {
  const idx = focusBucketIndex.value;
  if (idx == null) return null;
  return buckets.value[idx]?.key ?? null;
});
const focusHighlightLength = computed(() => 1);
const focusForecastValue = computed<Cell>(() => {
  const key = focusBucketKey.value;
  if (!key) return null;
  return totalByPrediction.value[key] ?? null;
});
const focusGoalValue = computed<Cell>(() => {
  const key = focusBucketKey.value;
  if (!key) return null;
  return totalByGoal.value[key] ?? null;
});
const focusActualValue = computed<Cell>(() => {
  const key = focusBucketKey.value;
  if (!key) return null;
  return totalByBucket.value[key] ?? null;
});

const visualScopeKey = computed(() => `${grain.value}-${viewScope.value}-${focusBucketKey.value ?? 'none'}`);
watch(grain, () => {
  viewScope.value = 'focus';
  triggerVisualSwitch();
});
watch(viewScope, () => {
  triggerVisualSwitch();
});
watch(() => focusBucketKey.value, () => {
  triggerVisualSwitch();
});

type SummaryRow = {
  status: string;
  totals: Record<string, Cell>;
  totalAll: Cell;
};
const summaryRows = computed<SummaryRow[]>(() => {
  return grouped.value.map(group => {
    const totals: Record<string, Cell> = createEmptyRow();
    let amount = 0;
    let count = 0;
    group.rows.forEach(row => {
      Object.entries(row.q).forEach(([key, cell]) => {
        if (!cell) return;
        if (!totals[key]) totals[key] = { amount: 0, count: 0 };
        (totals[key] as { amount: number; count: number }).amount += cell.amount;
        (totals[key] as { amount: number; count: number }).count += cell.count;
        amount += cell.amount;
        count += cell.count;
      });
    });
    return {
      status: group.status,
      totals,
      totalAll: amount + count > 0 ? { amount, count } : null,
    };
  }).filter(row => row.totalAll);
});
const detailRows = computed(() => grouped.value.flatMap(group => group.rows));
const isSummaryRow = (row: Row | SummaryRow): row is SummaryRow => 'totals' in row;
const getCellFromRow = (row: Row | SummaryRow, bucketKey: string | null): Cell | null => {
  if (!bucketKey) return null;
  if (isSummaryRow(row)) {
    return row.totals[bucketKey] ?? null;
  }
  return row.q[bucketKey] ?? null;
};
const bucketIsEmpty = (bucketKey: string): boolean => {
  if (!bucketKey) return true;
  const rows = tableMode.value === 'summary' ? summaryRows.value : detailRows.value;
  return rows.every(row => {
    const cell = getCellFromRow(row, bucketKey);
    return !cell || ((!cell.amount || cell.amount === 0) && (!cell.count || cell.count === 0));
  });
};
const cellClass = (bucketKey: string) => {
  const classes: string[] = [];
  if (focusBucketKey.value === bucketKey) classes.push('active-period');
  if (isFutureBucketKey(bucketKey)) classes.push('future-cell');
  if (bucketIsEmpty(bucketKey)) classes.push('empty-period');
  return classes.join(' ');
};
  const goalCellForRow = (row: Row | SummaryRow, bucketKey: string | null): Cell | null => {
  if (!bucketKey) return null;
  if (isSummaryRow(row)) {
    return totalByGoal.value[bucketKey] ?? null;
  }
  const memberGoals = memberGoalMap.value.get(row.memberId);
  return memberGoals ? memberGoals[bucketKey] ?? null : null;
};
const targetForStatus = (row: Row | SummaryRow, status: string, bucketKey: string | null) => {
  const baseGoal = goalCellForRow(row, bucketKey);
  if (!baseGoal) return { amount: 0, count: 0 };
  if (activePipelineLabels.value.includes(status)) {
    const stageCode = stageLabelToCode[status];
    if (!stageCode || !stageWeightSum.value) return baseGoal;
    const ratio = (STAGE_WEIGHT[stageCode] ?? 0) / stageWeightSum.value;
    return {
      amount: baseGoal.amount * ratio,
      count: baseGoal.count * ratio,
    };
  }
  return baseGoal;
};

const cellContributionValue = (status: string, row: Row | SummaryRow, bucketKey: string): number | null => {
  const cell = getCellFromRow(row, bucketKey);
  if (!cell || !cell.amount) return null;
  if (!activePipelineLabels.value.includes(status)) return null;
  const stageCode = stageLabelToCode[status];
  if (!stageCode) return null;
  const weight = STAGE_WEIGHT[stageCode] ?? 1;
  return cell.amount * weight;
};
const cellContributionLabel = (status: string, row: Row | SummaryRow, bucketKey: string) => {
  const contribution = cellContributionValue(status, row, bucketKey);
  if (contribution == null) return null;
  return `寄与 ${formatShortCurrency(contribution)}`;
};

const stageChartStatuses = computed(() => {
  const base = [...actualStatusLabels.value];
  return pipelineEnabled.value ? [...base, ...pipelineStatusLabels] : base;
});

const statusStageSeries = computed(() => {
  const byStatus = new Map<string, typeof grouped.value[number]>();
  for (const g of grouped.value) byStatus.set(g.status, g);

  return actualStatusLabels.value
    .map(label => {
      const group = byStatus.get(label);
      const values = buckets.value.map(bucket => {
        let total = 0;
        if (group) {
          for (const row of group.rows) {
            const cell = row.q[bucket.key];
            if (cell) total += cell.amount;
          }
        }
        return Math.round(total);
      });
      return { label, values };
    })
    .filter(series => series.values.some(v => v !== 0));
});

const stageChartSeries = computed(() => {
  // 「項目」(stage) は「実績の内訳」を表示: 実績ステータスが1つ(=実績のみ)なら単一線にする。
  if (actualStatusLabels.value.length === 1 && actualStatusLabels.value[0] === '実績') {
    return [];
  }
  return statusStageSeries.value;
});

const memberChartSeries = computed(() => {
  const bucketCount = buckets.value.length;
  if (!bucketCount) return [];

  const map = new Map<number, { name: string; totals: number[] }>();

  grouped.value.forEach(group => {
    if (group.status === '目標値') return;
    group.rows.forEach(row => {
      const entry = map.get(row.memberId) ?? {
        name: row.memberName,
        totals: Array(bucketCount).fill(0),
      };

      buckets.value.forEach((bucket, idx) => {
        const cell = row.q[bucket.key];
        if (cell) {
          entry.totals[idx] += cell.amount;
        }
      });

      map.set(row.memberId, entry);
    });
  });

  return Array.from(map.values())
    .map(entry => ({
      label: entry.name,
      total: entry.totals.reduce((sum, value) => sum + value, 0),
      points: bucketLabels.value.map((label, idx) => ({
        label,
        amount: Math.round(entry.totals[idx] ?? 0),
        count: 0,
      })),
    }))
    .filter(series => series.points.some(point => point.amount !== 0))
    .sort((a, b) => b.total - a.total)
    .map(({ label, points }) => ({ label, points }));
});

const aggregateChartSeries = computed(() => {
  return bucketLabels.value.map((label, idx) => {
    let amount = 0;
    grouped.value.forEach(group => {
      if (group.status === '目標値') return;
      group.rows.forEach(row => {
        const cell = row.q[buckets.value[idx].key];
        if (cell) {
          amount += cell.amount;
        }
      });
    });
    return { label, amount: Math.round(amount), count: 0 };
  });
});


const actualStatusSet = computed(() => new Set<string>(actualStatusLabels.value));
const actualChartSeries = computed(() => {
  return buckets.value.map(bucket => {
    let total = 0;
    grouped.value.forEach(group => {
      if (!actualStatusSet.value.has(group.status)) return;
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
const chartActualSeries = computed(() => {
  // stage: 内訳がある場合は合計線を出さない。内訳がない(=実績のみ)場合は合計を出す。
  if (mode.value === 'stage') {
    return stageChartSeries.value.length ? [] : actualChartSeries.value;
  }
  return actualChartSeries.value;
});
const chartTargetSeries = computed(() => {
  // aggregate: 実績合計 + 目標値（stageは実績内訳のみなので目標線を出さない）
  if (mode.value === 'aggregate') return targetChartSeries.value;
  return [];
});

const chartAggregateSeries = computed(() => {
  // aggregate は「実績合計」を表示（複数ステータスがあっても合計にする）
  return bucketLabels.value.map((label, idx) => ({
    label,
    amount: Math.round(chartActualSeries.value[idx] ?? 0),
    count: 0,
  }));
});
const targetChartSeries = computed(() => {
  if (!goalEnabled.value) return [];
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
    count += cell.count;
  });
  return amount + count > 0 ? { amount, count } : null;
}

const formatAmount = (value: number | null | undefined) => {
  if (value == null || Number.isNaN(value)) return '—';
  return `${new Intl.NumberFormat('ja-JP').format(value)}${unitLabel.value}`;
};
const formatCell = (cell: Cell | { amount: number; count: number } | undefined) => {
  if (!cell) return '—';
  // Show only the main amount in the project unit; suppress secondary counts to avoid mixed units like 3時間/4件.
  return formatAmount(cell.amount);
}

const formatShortCurrency = (value: number | null | undefined) => {
  if (value == null || Number.isNaN(value)) return '—';
  const abs = Math.abs(value);
  const sign = value >= 0 ? '' : '-';
  if (unitCode.value === 'JPY') {
    if (abs >= 100000000) return `${sign}${(abs / 100000000).toFixed(1)}億${unitLabel.value}`;
    if (abs >= 10000) return `${sign}${(abs / 10000).toFixed(1)}万${unitLabel.value}`;
    if (abs >= 1000) return `${sign}${(abs / 1000).toFixed(1)}千${unitLabel.value}`;
  }
  return `${sign}${new Intl.NumberFormat('ja-JP').format(abs)}${unitLabel.value}`;
};


const ratio = (forecast: { amount: number } | null | undefined, goal: { amount: number } | null | undefined) => {
  if (!forecast || !goal || goal.amount === 0) return null;
  return forecast.amount / goal.amount;
};

const totalForecast = computed(() => totalByAllPrediction.value);
const totalGoal = computed(() => totalByAllGoal.value);
const totalActual = computed(() => totalByAllBuckets.value);



const focusProgressRatio = computed(() => ratio(focusForecastValue.value, focusGoalValue.value));
const focusActualRatio = computed(() => ratio(focusActualValue.value, focusGoalValue.value));


const toggleGraphModes = () => {
  showGraphModes.value = !showGraphModes.value;
  if (!showGraphModes.value && mode.value !== 'stage') {
    mode.value = 'stage';
  }
};


const statusAmountForBucket = (status: string, bucketKey: string | null) => {
  if (!bucketKey) return 0;
  const group = grouped.value.find(item => item.status === status);
  if (!group) return 0;
  return group.rows.reduce((sum, row) => sum + (row.q[bucketKey]?.amount ?? 0), 0);
};


const statusHint = (status: string) => statusMetaMap.value[status]?.hint ?? '';
const statusMini = (status: string) => statusMetaMap.value[status]?.mini ?? '';
const statusToneClass = (status: string) => {
  const tone = statusMetaMap.value[status]?.tone ?? 'other';
  return `tone-${tone}`;
};
const router = useRouter()
const requestDetail = (row: Row, status: string) => {
  if (status === '目標値') {
    emit('view', {
      memberId: row.memberId,
      memberName: row.memberName,
      status,
      activeCase: row.latestCase,
      reportDate: row.latestCase?.reportDate ?? null,
      timeline: row.timeline,
    });
  } else {
    router.push({name: 'timesheet', query: {user_id: row.memberId}})
  }
};

const hasData = computed(() => grouped.value.some(group => group.rows.length > 0));
</script>
<style scoped>
.case-confirm {
  padding: 0 1.25rem 2rem;
}
.case-card {
  border: 1px solid var(--calendarBorder);
  /* border-radius: 16px; */
  padding: 16px 20px;
  background: var(--background-color);
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
}
.case-header {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: space-between;
  align-items: center;
}
.case-header__project {
  min-width: 220px;
}
.case-header__controls {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}
.control-group {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.control-group--actions {
  position: relative;
}
.case-eyebrow {
  font-size: 11px;
  letter-spacing: 0.08em;
  color: #64748b;
  text-transform: uppercase;
  margin-bottom: 4px;
}
.case-header__value {
  font-size: 14px;
  font-weight: 600;
  color: var(--primary-color);
}
.case-chip-label {
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #475569;
}
.control-chip-group {
  display: flex;
  border: 1px solid var(--normalBorder);
  overflow: hidden;
}
.control-chip-group .chip-button {
  border-radius: 0;
  border: none;
}
.period-nav {
  display: flex;
  align-items: center;
  gap: 8px;
}
.period-label {
  font-size: 14px;
  font-weight: 600;
  text-align: center;
}
.table-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.table-card__title {
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
}
.table-scroll {
  overflow-x: auto;
}
.case-card.chart-card {
  border: none;
  background: transparent;
  box-shadow: none;
  padding: 0;
}
/* Optional styling, adjust to your Tailwind or vanilla CSS setup */
.report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    line-height: 1.5;
    white-space: nowrap;
}
.report-table th {
  padding: 6px 8px;
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
    padding: 6px 8px;
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
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}
.hero-summary {
  margin-top: 12px;
  padding: 12px 16px;
  border-radius: 10px;
  background: rgba(15, 23, 42, 0.05);
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
}
.table-section {
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.settings-button {
  border: 1px solid var(--normalBorder);
  padding: 6px 14px;
  font-size: 12px;
  font-weight: 600;
  background: var(--background-color);
  transition: background 0.2s ease;
}
.settings-button:hover {
  background: var(--bg3);
}
.settings-popover {
  position: absolute;
  top: 120%;
  right: 0;
  min-width: 260px;
  background: var(--background-color);
  border: 1px solid var(--normalBorder);
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
  padding: 12px;
  z-index: 5;
}
.settings-section {
  margin-bottom: 12px;
}
.settings-section:last-child {
  margin-bottom: 0;
}
.settings-section p {
  font-size: 11px;
  color: #475569;
  margin-bottom: 6px;
  letter-spacing: 0.05em;
}
.chip-group {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.chip-button {
  border: 1px solid var(--normalBorder);
  padding: 4px 10px;
  font-size: 12px;
  background: var(--background-color);
  /* transition: background 0.2s ease, color 0.2s ease; */
}
.chip-button.active {
  background: var(--hoverBorder);
  color: #fff;
  border-color: var(--hoverBorder);
}
.kpi-card {
  border: 1px solid var(--calendarBorder);
  border-radius: 8px;
  padding: 16px;
  background: var(--background-color);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.kpi-card.good {
  border-color: #22c55e;
  box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.2);
}
.kpi-card.warn {
  border-color: #eab308;
  box-shadow: 0 0 0 1px rgba(234, 179, 8, 0.2);
}
.kpi-card.bad {
  border-color: #ef4444;
  box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.2);
}
.kpi-card.neutral {
  border-color: var(--calendarBorder);
}
.kpi-card.muted {
  background: var(--bg3);
}
.kpi-value {
  font-size: 32px;
  font-weight: 600;
  color: var(--primary-color);
}
.kpi-subtext {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}
.kpi-subtext.multiline {
  line-height: 1.6;
}
.kpi-caption {
  font-size: 11px;
  color: #94a3b8;
  margin-top: 4px;
}
.kpi-label {
  text-transform: none;
  color: #475569;
  letter-spacing: 0.08em;
}
.kpi-delta {
  margin-top: 8px;
  font-size: 13px;
  font-weight: 500;
}
.kpi-delta.positive {
  color: #16a34a;
}
.kpi-delta.negative {
  color: #dc2626;
}
.kpi-trend {
  margin-top: 4px;
  font-size: 12px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.kpi-recommendation {
  margin-top: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #0f172a;
}
.kpi-trend.neutral {
  color: #94a3b8;
}
.kpi-trend.positive {
  color: #16a34a;
}
.kpi-trend.negative {
  color: #dc2626;
}
.perceived-loader {
  margin-top: 10px;
  display: flex;
  justify-content: center;
}
.perceived-loader__pulse {
  width: 90%;
  height: 12px;
  border-radius: 999px;
  background: linear-gradient(90deg, rgba(148, 163, 184, 0.25), rgba(148, 163, 184, 0.45), rgba(148, 163, 184, 0.25));
  animation: pulse 0.9s ease-in-out infinite;
}
@keyframes pulse {
  0% { opacity: 0.4; }
  50% { opacity: 0.9; }
  100% { opacity: 0.4; }
}
.chart-extra-controls {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 8px;
}
.health-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 600;
}
.health-pill.ghost {
  color: var(--primary-color);
}
.health-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: #9ca3af;
}
.health-dot.good {
  background: #22c55e;
}
.health-dot.warn {
  background: #eab308;
}
.health-dot.bad {
  background: #ef4444;
}
.subhead {
  color: #6b7280;
  font-weight: 500;
}
.jump-link {
  color: var(--primary-color);
  opacity: 0.75;
  font-weight: 500;
}
.stage-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 13px;
  color: #6b7280;
}
.legend-chip {
  padding: 4px 8px;
  background: var(--bg3);
  color: var(--primary-color);
}
.status-cell {
  display: flex;
  flex-direction: column;
}
.status-mini {
  font-size: 10px;
  color: #6b7280;
}
.tone-pipeline {
  border-left: 3px solid #2563eb;
}
.tone-actual {
  border-left: 3px solid #111827;
}
.tone-target {
  border-left: 3px solid #dc2626;
}
.tone-other {
  border-left: 3px solid transparent;
}
.status-summary {
  min-width: 120px;
}
.summary-metric {
  padding: 6px 8px;
  border-radius: 6px;
  background: rgba(148, 163, 184, 0.15);
  color: var(--primary-color);
}
.summary-pill-cell {
  text-align: left;
}
.summary-pill {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--primary-color);
  background: var(--bg3);
  padding: 4px 8px;
}
.summary-toggle {
  font-weight: 600;
  color: #475569;
}
.summary-toggle.summary-selected {
  background: var(--hoverBorder);
  color: #fff;
}
.cell-value {
  font-weight: 600;
}
.cell-value.summary {
  font-weight: 500;
}
.cell-flags {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: 4px;
}
.cell-chip {
  font-size: 11px;
  color: #475569;
}
.cell-chip.danger {
  color: #b91c1c;
  font-weight: 600;
}
.future-cell {
  color: #9ca3af;
  background: rgba(148, 163, 184, 0.12);
}
.active-period {
  /* background: rgba(239, 68, 68, 0.08); */
  background-color: var(--bg3);
}
.empty-period {
  opacity: 0.55;
}
.cell-value {
  font-weight: 600;
}
.cell-value.summary {
  font-weight: 500;
}
.cell-flags {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: 4px;
}
.cell-chip {
  font-size: 11px;
  color: #475569;
}
.cell-chip.danger {
  color: #b91c1c;
  font-weight: 600;
}
.chart-shell {
  margin-top: 24px;
  border: 1px solid var(--calendarBorder);
  /* border-radius: 12px; */
  padding: 12px;
  background: var(--background-color);
  transition: box-shadow 0.3s ease;
}
.chart-shell.mood-good {
  box-shadow: 0 10px 25px rgba(34, 197, 94, 0.15);
}
.chart-shell.mood-warn {
  box-shadow: 0 10px 25px rgba(234, 179, 8, 0.15);
}
.chart-shell.mood-bad {
  box-shadow: 0 10px 25px rgba(239, 68, 68, 0.18);
}
.eval-cell {
  text-align: center;
}
.eval-pill {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}
.eval-pill.tone-good {
  background: rgba(34, 197, 94, 0.15);
  color: #15803d;
}
.eval-pill.tone-warn {
  background: rgba(234, 179, 8, 0.18);
  color: #92400e;
}
.eval-pill.tone-bad {
  background: rgba(239, 68, 68, 0.2);
  color: #b91c1c;
}
.eval-pill.tone-neutral {
  background: rgba(148, 163, 184, 0.2);
  color: #475569;
}
.focus-fade-enter-active,
.focus-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.focus-fade-enter-from,
.focus-fade-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
@media screen and (max-width: 513px) {
  .settings-popover {
    left: 0;
    right: auto;
  }
}
</style>
