<template>
  <div class="p-6 space-y-4 text-[var(--primary-color)] h-[calc(100%-120px)] overflow-y-auto">
    <!-- Header / Controls -->
    <div class="bg-[var(--background-color)] p-5 flex items-center gap-4 border-solid border border-[var(--normalBorder)] shadow-sm">
      <h1 class="text-lg font-bold">月別内訳 • プロジェクト収支</h1>
      <div class="ml-auto flex items-center gap-3">
        <label class="c-button">
            <span class="px-[7px] cursor-pointer">予算をアップロード</span>
            <input type="file" class="hidden" @change="uploadBudget" />
        </label>
        <CommandButton 
          :buttons="[
            { title: 'テンプレートダウンロード', action: () => downloadTemplate() }
          ]"
        />
        <label class="text-sm">会計年度</label>
        <input
          type="number"
          v-model="fiscalYear"
          class="w-24 px-2 py-1 text-right text-[var(--primary-color)] bg-[var(--background-color)] border border-solid border-[var(--normalBorder)] focus:outline-none focus:border-[var(--hoverBorder)]"
        />
        <label class="text-sm">開始月</label>
        <select
          v-model="startMonth"
          class="px-2 py-1 text-[var(--primary-color)] bg-[var(--background-color)] border border-solid border-[var(--normalBorder)] focus:outline-none focus:border-[var(--hoverBorder)]"
        >
          <option v-for="m in 12" :key="m" :value="m">{{ monthLabel(m) }}</option>
        </select>
        <!-- <button @click="evenDistribute()" class="px-3 py-1 bg-blue-600">Evenly 1/12</button>
        <button @click="clearAll()" class="px-3 py-1">Clear all</button> -->
      </div>
    </div>

    <!-- Table -->
    <div class="bg-[var(--background-color)] overflow-x-auto border border-solid border-[var(--normalBorder)] shadow-sm">
      <table class="min-w-[1400px] w-full text-sm text-[var(--primary-color)]">
        <thead class="bg-[var(--bg3)] border-b [border-bottom-style:solid] border-[var(--normalBorder)]">
          <tr>
            <th class="sticky left-0 bg-[var(--bg3)] z-10 text-left px-4 py-2 w-40 text-xs">
              月 • 年
            </th>
            <th
              v-for="(p, i) in periods"
              :key="i"
              class="px-3 py-2 text-center text-xs font-medium"
            >
              {{ monthLabel(p.month) }} {{ p.year }}
            </th>
          </tr>
        </thead>
        <tbody>
          <!-- SALES -->
          <tr class="section-row">
            <td class="sticky left-0 section-cell z-10 px-4 py-2">売上</td>
            <td v-for="p in periods" :key="'sales-h-'+p.period"></td>
          </tr>
          <tr v-for="row in salesRows" :key="row.key" class="sheet-row">
            <td class="sticky left-0 sheet-label z-10 px-4 py-2">{{ row.label }}</td>
            <td v-for="p in periods" :key="row.key + p.period" class="sheet-cell px-2 py-2">
              <input
                v-if="row.input"
                type="number"
                class="w-full px-2 py-1 text-right table-input"
                v-model.number="payload[p.period][row.key]"
              />
              <div v-else class="text-right text-accent">
                {{ fmt(amounts[p.period][row.key]) }}
              </div>
            </td>
          </tr>

          <!-- EXPENSES -->
          <tr class="section-row">
            <td class="sticky left-0 section-cell z-10 px-4 py-2">販管費</td>
            <td v-for="p in periods" :key="'exp-h-'+p.period"></td>
          </tr>
          <tr v-for="row in expenseRows" :key="row.key" class="sheet-row">
            <td class="sticky left-0 sheet-label z-10 px-4 py-2">{{ row.label }}</td>
            <td v-for="p in periods" :key="row.key + p.period" class="sheet-cell px-2 py-2">
              <input
                v-if="row.input"
                type="number"
                class="w-full px-2 py-1 text-right table-input"
                v-model.number="payload[p.period][row.key]"
              />
              <div v-else :class="row.key === 'total_expenses' ? 'text-negative' : ''" class="text-right">
                {{ fmt(amounts[p.period][row.key]) }}
              </div>
            </td>
          </tr>

          <!-- PROFIT -->
          <tr class="section-row">
            <td class="sticky left-0 section-cell z-10 px-4 py-2">利益</td>
            <td
              v-for="p in periods"
              :key="'profit-'+p.period"
              class="sheet-cell px-2 py-2 text-right"
              :class="profitPerMonth[p.period] >= 0 ? 'text-profit-positive' : 'text-profit-negative'"
            >
              <span class="text-[11px]">{{ subArrow(amounts[p.period]['final_profit']) }}</span>{{ fmt(amounts[p.period]['final_profit']) }}
            </td>
          </tr>
        </tbody>

        <!-- Annual totals -->
        <tfoot class="bg-[var(--background-color)] border-t [border-top-style:solid] border-[var(--normalBorder)]">
          <tr class="border-b [border-bottom-style:solid] border-[var(--normalBorder)]">
            <td class="sticky left-0 sheet-label z-10 px-4 py-2">年間合計 売上高</td>
            <td class="px-3 py-2 text-right text-accent" colspan="12">
              {{ fmt(annualTotals.sales) }}
            </td>
          </tr>
          <tr class="border-b [border-bottom-style:solid] border-[var(--normalBorder)]">
            <td class="sticky left-0 sheet-label z-10 px-4 py-2">年間合計 販管費</td>
            <td class="px-3 py-2 text-right text-negative" colspan="12">
              {{ fmt(annualTotals.expenses) }}
            </td>
          </tr>
          <tr>
            <td class="sticky left-0 sheet-label !border-b-0 z-10 px-4 py-2">年間合計 利益</td>
            <td
              class="px-3 py-2 text-right"
              :class="annualTotals.profit >= 0 ? 'text-profit-positive' : 'text-profit-negative'"
              colspan="12"
            >
              <span class="text-[11px]">{{ subArrow(annualTotals.profit) }}</span>{{ fmt(annualTotals.profit) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
      <button class="bg-[var(--bg3)] px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition" @click="save()">年度予算保存する</button>
      <!-- <button class="bg-gray-700 px-4 py-2 rounded" @click="logPayload()">Debug: log payload</button> -->
    </div>
  </div>
</template>

<script setup lang="ts">
import CommandButton from '@/components/Global/CommandButton.vue';
import { useApi } from '@/composables/api'
import axios from 'axios';
import { DateTime } from 'luxon';
import { reactive, ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps<{
  year: number
  selectedProjectName: string
  selectedProjectId: number
}>()
/** Config **/
const fiscalYear = ref(props.year || DateTime.now().year)
const startMonth = ref(3) // March → February

/** Lines (keys are your metric codes for inputs; auto rows are computed) **/
const salesRows = [
  { key: 'sales', label: '合計 売上高', input: true },
  { key: 'internal_sales', label: '合計 内部売上高合計', input: true },
  { key: 'total_sales', label: '= 売上高 (自動)', input: false },
]
const expenseRows = [
  { key: 'salaries', label: '合計 給料手当', input: true },
  { key: 'outsourcing', label: '合計 外注費', input: true },
  { key: 'internal_orders', label: '合計 内部発注合計', input: true },
  { key: 'sga_other', label: '合計 販管費その他', input: true },
  { key: 'indirect', label: '合計 間接費配賦', input: false },
  { key: 'bonus', label: '業績連動型賞与引当金', input: false },
  { key: 'total_expenses', label: '= 費用合計（自動）', input: false },
]
const api = useApi()
const route = useRoute()
/** Period helpers **/
const pad2 = (n:number) => String(n).padStart(2, '0')
const monthName = (m:number) => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][m-1]
const monthLabel = (m:number) => ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'][m-1]
const subArrow = (v: number | null | string) => {
  console.log(v)
  if (v == null || v === '—') return ''
  const n = typeof v === 'number' ? v : Number(v)
  if (!Number.isFinite(n) || n === 0) return ''
  return n > 0 ? '↑' : '↓'
}
const generateFiscalPeriods = (startYear:number, startM:number) => {
  const out: {year:number; month:number; period:string}[] = []
  let y = startYear, m = startM
  for (let i=0; i<12; i++) {
    out.push({ year: y, month: m, period: `${y}-${pad2(m)}-01` })
    m++
    if (m > 12) { m = 1; y++ }
  }
  return out
}

const periods = computed(() => generateFiscalPeriods(fiscalYear.value, startMonth.value))
/** Payload shape: payload[period] = { key: number|null } **/
const payload = reactive<Record<string, Record<string, number|null>>>({})

const ensurePayload = () => {
  for (const p of periods.value) {
    payload[p.period] ||= {}
    // initialize inputs only; autos are computed
    for (const r of [...salesRows, ...expenseRows].filter(r => r.input)) {
      if (payload[p.period][r.key] == null) payload[p.period][r.key] = null
    }
  }
}
watch(periods, ensurePayload, { immediate: true })

/** Computed amounts per period (auto rows) **/
const amounts = computed(() => {
  const map: Record<string, Record<string, number>> = {}

  for (const p of periods.value) {
    const row = payload[p.period] || {}
    const sales = num(row.sales)
    const internal_sales = num(row.internal_sales)

    const salaries = num(row.salaries)
    const outsourcing = num(row.outsourcing)
    const internal_orders = num(row.internal_orders)
    const sga_other = num(row.sga_other)
    const indirect = num((salaries + outsourcing + sga_other) * 0.1)
    const total_sales = sales + internal_sales
    
    const total_expenses = salaries + outsourcing + internal_orders + sga_other + indirect
     
    const profit = num(total_sales - total_expenses)
    const bonus = num(profit * 0.1)
    const final_profit = num(profit - bonus)
    map[p.period] = {
      sales,
      internal_sales,
      total_sales,
      salaries,
      outsourcing,
      internal_orders,
      sga_other,
      indirect,
      bonus,
      total_expenses,
      final_profit
    }
  }
  return map
})

const profitPerMonth = computed(() => {
  const pm: Record<string, number> = {}
  for (const p of periods.value) {
    const a = amounts.value[p.period]
    pm[p.period] = (a?.total_sales ?? 0) - (a?.total_expenses ?? 0)
  }
  return pm
})

/** Annual totals */
const annualTotals = computed(() => {
  let sales = 0, expenses = 0, profit = 0
  for (const p of periods.value) {
    const a = amounts.value[p.period]
    sales += a?.total_sales ?? 0
    expenses += a?.total_expenses ?? 0
    profit += (a?.total_sales ?? 0) - (a?.total_expenses ?? 0)
  }
  return { sales, expenses, profit }
})

/** Actions **/
const evenDistribute = () => {
  // even split the current annual sums across 12 months
  const annualSales = annualTotals.value.sales
  const annualExpenses = annualTotals.value.expenses
  const salesPerMonth = round2(annualSales / 12)
  const expPerMonth = round2(annualExpenses / 12)

  for (const p of periods.value) {
    // naive split: push everything into total_sales_input, total_expenses buckets
    payload[p.period].sales = salesPerMonth
    payload[p.period].internal_sales = 0
    payload[p.period].salaries = round2(expPerMonth * 0.5)
    payload[p.period].outsourcing = round2(expPerMonth * 0.2)
    payload[p.period].internal_orders = round2(expPerMonth * 0.1)
    payload[p.period].sga_other = round2(expPerMonth * 0.1)
    payload[p.period].indirect = round2(expPerMonth * 0.08)
    payload[p.period].bonus = round2(expPerMonth * 0.02)
  }
}

const clearAll = () => {
  for (const p of periods.value) {
    for (const r of [...salesRows, ...expenseRows].filter(r => r.input)) {
      payload[p.period][r.key] = null
    }
  }
}

const save = async() => {
  const projectId = Number(route.params.projectId)
  if (!projectId) return
  const req = buildMonthlyPlanRequest(
    'annual_budget',
    projectId,
    periods.value,
    payload,
    periods.value[0].period,     
    periods.value.at(-1)!.period 
  )
  console.log('Saving...', req)
  const data = await api.post(`/project_metrics/${[projectId]}/yearly_budget`, req)
  // translate to backend rows: { project_record_id, metric_id, period, value }
  // you’ll map keys -> metric_ids server-side or inject a lookup here.
  console.log(data)
}

const logPayload = () => {
  console.log(JSON.parse(JSON.stringify(payload)))
}
// keys you actually accept from the UI
const SALES_KEYS = ['sales', 'internal_sales'] as const
const EXP_KEYS   = ['salaries','outsourcing','internal_orders','sga_other','indirect','bonus'] as const
type SalesKey = typeof SALES_KEYS[number]
type ExpKey   = typeof EXP_KEYS[number]

const validPeriodSet = (periods: {period:string}[], start:string, end:string) => {
  // inclusive [start, end]
  const ok = new Set(periods.map(p => p.period))
  if (!ok.size) return false
  // quick guards: format YYYY-MM-01 and within range
  for (const p of ok) {
    if (!/^\d{4}-\d{2}-01$/.test(p)) return false
    if (p < start || p > end) return false
  }
  return true
}
const toNumOrNull = (v: unknown) => {
  if (v === '' || v == null) return null
  const n = typeof v === 'string' ? Number(v.replace(/,/g,'')) : Number(v)
  return Number.isFinite(n) ? n : null
}

const buildMonthlyPlanRequest = (
  scenarioCode: string,
  projectId: number,
  periods: {period:string}[],
  payload: Record<string, Record<string, number|null>>,
  fiscalStart: string,   // e.g. '2025-03-01'
  fiscalEnd: string      // e.g. '2026-02-01'
) => {
  // 1) validate period window
  if (!validPeriodSet(periods, fiscalStart, fiscalEnd)) {
    throw new Error('Invalid periods range or bad format')
  }

  // 2) build per-period blocks
  const monthly:any[] = []
  for (const { period } of periods) {
    const row = payload[period] || {}

    // pick only known keys, sanitize, and prune nulls
    const sales: Partial<Record<SalesKey, number|null>> = {}
    for (const k of SALES_KEYS) {
      const v = toNumOrNull(row[k])
      if (v !== null) sales[k] = round2(v)
    }

    const expenses: Partial<Record<ExpKey, number|null>> = {}
    for (const k of EXP_KEYS) {
      const v = toNumOrNull(row[k])
      if (v !== null) expenses[k] = round2(v)
    }

    // if nothing to send for this month, skip
    if (!Object.keys(sales).length && !Object.keys(expenses).length) continue

    monthly.push({ period, sales, expenses })
  }

  // 3) final request shape
  return {
    scenario_code: scenarioCode,               // e.g. 'annual_budget'
    project_record_id: projectId,
    months: monthly                            // [{ period, sales:{...}, expenses:{...} }, ...]
  }
}
const load = async() => {
  const projectId = Number(route.params.projectId)
  if (!projectId) return
  const fiscalStart = periods.value[0].period
  const fiscalEnd = periods.value.at(-1)!.period
  const params = {
    start: fiscalStart,
    end: fiscalEnd
  }
  const data = await api.get(`/project_metrics/${projectId}/sales_expenses`, params)
  if (data) {
    Object.assign(payload, data)
  }
}
const downloadTemplate = async() => {
  const projectName = props.selectedProjectName || 'project'
  const rows = [...salesRows, ...expenseRows].filter(r => r.input === true).map(r => r.label)
  
  const res = await axios.post('/download_yearly_template', {
    year: fiscalYear.value,
    month: startMonth.value,
    projectName,
    rows,
  }, {
    responseType: 'blob'
  })
  const cd = res.headers['content-disposition'];
  const name = filenameFromDisposition(cd) || 'download';
  const url = URL.createObjectURL(res.data);
  const a = document.createElement('a');
  a.href = url; a.download = name;
  document.body.appendChild(a); a.click(); a.remove();
  URL.revokeObjectURL(url);
  
}
const filenameFromDisposition = (h?: string | null) => {
    if (!h) return null;
    // RFC 5987 / basic filename=
    const mStar = /filename\*=(?:UTF-8'')?([^;]+)/i.exec(h);
    if (mStar) return decodeURIComponent(mStar[1].replace(/^"+|"+$/g, ''));
    const m = /filename="?([^"]+)"?/i.exec(h);
    return m ? m[1] : null;
}
const uploadBudget = async(ev: Event) => {
  const projectId = route.params.projectId
  if(!projectId) return
  const input = ev.target as HTMLInputElement
  if (!input.files?.length) return
  const file = input.files[0]
  const fd = new FormData();
  fd.append('file', file)
  fd.append('project_id', String(projectId))
  fd.append('year', String(fiscalYear.value))
  fd.append('projectName', String(props.selectedProjectName))

  const data = await api.post('/upload_yearly_budget', fd)
}
/** Utils **/
const fmt = (n?: number) =>
  typeof n === 'number' && Number.isFinite(n)
    ? new Intl.NumberFormat('ja-JP', { maximumFractionDigits: 0 }).format(n)
    : '—';
const num = (v: unknown) => (typeof v === 'number' && !Number.isNaN(v)) ? v : 0
const round2 = (x:number) => Math.round(x * 100) / 100

onMounted(load)
</script>

<style scoped>
.section-row {
    background-color: var(--bg3);
    color: var(--primary-color);
}
.section-cell {
    background-color: var(--bg3);
    border-bottom: 1px solid var(--normalBorder);
}
.sheet-row {
    background-color: var(--background-color);
}
.sheet-label {
    background-color: var(--bg3);
    border-bottom: 1px solid var(--normalBorder);
}
.sheet-cell {
    background-color: var(--background-color);
    border-bottom: 1px solid var(--normalBorder);
}
.text-accent {
    /* color: #63b3ed; */
    font-weight: bold;
}
.text-profit-positive {
    color: #16A34A;
    font-weight: bold;
}
.text-profit-negative {
    color: #DC2626;
    font-weight: bold;
}
.text-negative {
    /* color: #C084FC; */
    font-weight: bold;
}
.table-input {
    box-sizing: border-box !important;
    background-color: var(--background-color);
    border: 1px solid var(--normalBorder);
    color: var(--primary-color);
}
.table-input:focus {
    border-color: var(--hoverBorder);
    outline: none;
}
.c-button{
    color: var(--background-color);
    background-color: var(--primary-color);
    font-size: 12px;
    line-height: 1.5;
    white-space: nowrap;
    height: 25px;
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: center;
    width: fit-content;
    position: relative
}
</style>
