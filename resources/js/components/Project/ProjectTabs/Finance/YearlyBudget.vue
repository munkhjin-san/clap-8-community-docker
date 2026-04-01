<template>
  <div v-if="hasPrivilage" class="py-0 px-5 space-y-4 text-[var(--primary-color)] h-[calc(100%-70px)]">
    <!-- Header / Controls -->
    <div ref="controlRef" class="bg-[var(--background-color)] p-5 sticky top-0 z-20 flex items-center gap-3 border-solid border border-[var(--normalBorder)] shadow-sm flex-wrap">
      <div class="flex flex-col">
        <h1 class="text-base font-bold">月別内訳 • プロジェクト収支</h1>
        <div class="text-xs text-[var(--primary-color)] opacity-80">
          {{ fiscalYear }} (開始 {{ monthLabel(startMonth) }})
          <!-- FY{{ fiscalYear }} (開始 {{ monthLabel(startMonth) }}) • シナリオ: {{ activeScenarioLabel }} -->
          <span
            v-if="lockState.is_locked"
            class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 text-[11px] border border-solid border-[var(--normalBorder)] bg-[var(--bg3)]"
          >
            確定済み
            <span v-if="lockState.locked_at" class="opacity-80">({{ lockState.locked_at }})</span>
          </span>
          <span v-if="dirty && !isReadOnly" class="ml-2 text-[var(--alert-color, #ef4444)]">未保存</span>
        </div>
      </div>
      <!-- <label class="text-sm">会計年度</label>
      <input
        type="number"
        v-model="fiscalYear"
        class="w-24 px-2 py-1 text-right text-[var(--primary-color)] bg-[var(--background-color)] border border-solid border-[var(--normalBorder)] focus:outline-none focus:border-[var(--hoverBorder)]"
      /> -->
      <label class="flex items-center gap-1">
          <span class="text-sm">会計年度</span>
          <select v-model.number="fiscalYear" class="text-[var(--primary-color)] px-2 py-1 bg-[var(--background-color)] text-sm border border-solid border-[var(--normalBorder)] focus:outline-none focus:border-[var(--hoverBorder)]">
              <option v-for="year in fiscalYearOptions" :key="`fy-start-${year}`" :value="year">
                  {{ year }}
              </option>
          </select>
      </label>
      <!-- <label class="text-sm">開始月</label>
      <select
        v-model="startMonth"
        disabled
        class="px-2 py-1 text-[var(--primary-color)] bg-[var(--background-color)] border border-solid border-[var(--normalBorder)] focus:outline-none focus:border-[var(--hoverBorder)]"
      >
        <option v-for="m in 12" :key="m" :value="m">{{ monthLabel(m) }}</option>
      </select> -->
      <button class="text-xs px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition" @click="downloadTemplate">テンプレートDL</button>
      <label class="text-xs px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition cursor-pointer">
        アップロード
        <input type="file" class="hidden" accept=".xlsx,.xls" :disabled="isReadOnly" @change="uploadTemplate" />
      </label>
      <button
        class="text-xs px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="isReadOnly"
        @click="copyFirstMonthToAll"
      >1月を全月にコピー</button>
      <button
        class="text-xs px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="isReadOnly"
        @click="clearAll"
      >クリア</button>
      
      <!-- <label class="text-sm">シナリオ</label>
      <select
        v-model="selectedScenarioId"
        class="px-2 py-1 text-[var(--primary-color)] bg-[var(--background-color)] border border-solid border-[var(--normalBorder)] focus:outline-none focus:border-[var(--hoverBorder)]"
      >
        <option :value="null">ベース (デフォルト)</option>
        <option v-for="s in scenarios" :key="s.id" :value="s.id">{{ s.name }} ({{ s.code }})</option>
      </select>
      <button
        class="bg-[var(--bg3)] px-3 py-1 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition text-xs disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="isReadOnly"
        @click="createScenario"
      >
        新規シナリオ
      </button> -->
      <div class="flex items-center gap-2 ml-auto">
        <button
          v-if="lockState.is_locked && auth.isAdmin"
          class="ml-auto text-xs px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition"
          @click="unlockPlan"
        >確定解除</button>
        <button
          v-if="!lockState.is_locked && (auth.isAdmin || auth.isBoss) "
          class="text-xs px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="isReadOnly"
          @click="confirmAndLock"
        >確定</button>
        <button
          class="text-xs bg-[var(--bg3)] px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="isReadOnly"
          @click="save"
        >保存</button>
        <input
          v-model="filterTerm"
          type="text"
          placeholder="科目検索 (名称)"
          class="px-2 py-1 text-[var(--primary-color)] bg-[var(--background-color)] border border-solid border-[var(--normalBorder)] focus:outline-none focus:border-[var(--hoverBorder)]"
        />
      </div>
    </div>

    

    <!-- Table -->
    <div v-if="fiscalYear !== currentFiscalYear || lockState.is_locked" :style="{ height: calcHeight }" :class="['bg-[var(--background-color)] overflow-x-auto border border-solid border-[var(--normalBorder)] shadow-sm']">
      <table class="min-w-[1400px] w-full text-sm text-[var(--primary-color)]">
        <thead class="bg-[var(--bg3)] border-b [border-bottom-style:solid] border-[var(--normalBorder)] top-0 sticky z-[11]">
          <tr>
            <th class="sticky left-0 bg-[var(--bg3)] z-10 text-left px-4 py-2 w-32 md:w-52 text-xs">
              科目
            </th>
            <th
              v-for="p in periods"
              :key="p.period_index"
              class="px-3 py-2 text-center text-xs font-medium"
            >
              {{ monthLabel(p.calendar_month) }} {{ p.calendar_year }}
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-for="acct in displayAccounts" :key="acct.id">
            <tr v-if="isGroupRow(acct)" class="section-row">
              <td class="sticky left-0 section-cell z-10 px-4 py-2">{{ acct.name }}</td>
              <td v-for="p in periods" :key="acct.id + '-h-' + p.period_index"></td>
            </tr>
            <tr v-else class="sheet-row">
              <td
                class="sticky left-0 sheet-label z-10 py-2"
                :style="{ paddingLeft: `${16 + Math.max(acct.depth, 0) * 12}px` }"
              >
                {{ acct.name }}
                <span v-if="acct.is_formula" class="ml-1 text-[11px] opacity-70">式</span>
              </td>
              <td
                v-for="p in periods"
                :key="acct.id + '-' + p.period_index"
                class="sheet-cell px-2 py-2 text-right"
              >
                <input
                  v-if="acct.is_postable"
                  type="text"
                  class="w-full px-2 py-1 text-right table-input"
                  :disabled="isReadOnly"
                  inputmode="numeric"
                  :value="formatNumber(payload[p.period_index][acct.id])"
                  @input="onAmountInput($event, p.period_index, acct.id)"
                />
                <template v-else-if="acct.is_formula">
                  {{ fmt(formulaValues[p.period_index]?.[acct.id]) }}
                </template>
                <template v-else>—</template>
              </td>
            </tr>
          </template>
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
            <td class="sticky left-0 sheet-label z-10 px-4 py-2">年間合計 販売管理費計</td>
            <td class="px-3 py-2 text-right text-negative" colspan="12">
              {{ fmt(annualTotals.totalExpenses) }}
            </td>
          </tr>
          <tr>
            <td class="sticky left-0 sheet-label !border-b-0 z-10 px-4 py-2">年間合計 営業損益金額</td>
            <td
              class="px-3 py-2 text-right"
              :class="annualTotals.profit >= 0 ? 'text-profit-positive' : 'text-profit-negative'"
              colspan="12"
            >
              {{ fmt(annualTotals.profit) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div v-else>
      <p class="text-center">今年の予算が確定しています</p>
    </div>
    <!-- Toolbar -->
    <!-- <div class="flex flex-wrap gap-3 bg-[var(--background-color)] p-3 border border-[var(--normalBorder)] shadow-sm">
      
      <button
        class="px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="isReadOnly"
        @click="pasteClipboard"
      >ペースト貼付</button>
      <button
        class="px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="isReadOnly"
        @click="copyFirstMonthToAll"
      >1月を全月にコピー</button>
      <button
        v-if="!lockState.is_locked"
        class="px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="isReadOnly"
        @click="confirmAndLock"
      >確定</button>
      <button
        class="bg-[var(--bg3)] px-4 py-2 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="isReadOnly"
        @click="save"
      >保存</button>
      
    </div> -->
  </div>
  <div v-else class="w-full h-[calc(100%-67px)] flex items-center justify-center">
    権限がありません。
  </div>
</template>

<script setup lang="ts">
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import axios from 'axios'
import { DateTime } from 'luxon';
import { reactive, ref, computed, watch, onMounted, useTemplateRef, nextTick, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useProject } from '@/composables/project';

type Account = {
  id: number;
  code: string;
  name: string;
  path: string;
  depth: number;
  is_postable: boolean;
  is_active: boolean;
  is_formula?: boolean;
  formula?: string | null;
  parent_id: number | null;
}

type PeriodRow = {
  period_index: number;
  calendar_year: number;
  calendar_month: number;
}
const { selectedProject } = useProject()
const props = defineProps<{
  year: number
  hasPrivilage: boolean
}>()
const generateFiscalPeriods = (startYear: number, startM: number): PeriodRow[] => {
  const out: PeriodRow[] = []
  let y = startYear, m = startM
  for (let i = 1; i <= 12; i++) {
    out.push({ period_index: i, calendar_year: y, calendar_month: m })
    m++
    if (m > 12) { m = 1; y++ }
  }
  return out
}
const api = useApi()
const route = useRoute()
const { ask, toast, askInput } = useDialog()
const auth = useAuthUserStore()

const fiscalYear = ref(DateTime.now().year)
const startMonth = ref(3) // March → February

const planYearId = ref<number | null>(null)
const scenarios = ref<{id:number; name:string; code:string; weight:number}[]>([])
const selectedScenarioId = ref<number | null>(null)
const accounts = ref<Account[]>([])
const periods = ref<PeriodRow[]>(generateFiscalPeriods(fiscalYear.value, startMonth.value))
const dirty = ref(false)
const isLoading = ref(false)
const filterTerm = ref('')
const lastLoaded = reactive({
  fiscalYear: fiscalYear.value,
  startMonth: startMonth.value,
  scenarioId: selectedScenarioId.value,
})
const initialized = ref(false)

const lockState = reactive<{
  is_locked: boolean
  locked_by_user_id: number | null
  locked_at: string | null
}>({
  is_locked: false,
  locked_by_user_id: null,
  locked_at: null,
})
const now = DateTime.now();
const currentFiscalYear = now.month >= 3 ? now.year : now.year - 1;
const isReadOnly = computed(() => lockState.is_locked && !auth.isAdmin)
const controlRef = useTemplateRef<HTMLDivElement>('controlRef')
const controlRefHeight = ref(0)

const minFiscalYear = 2024
const maxFiscalYear = DateTime.now().year + 2
const fiscalYearOptions = computed<number[]>(() =>
    Array.from({ length: maxFiscalYear - minFiscalYear + 1 }, (_, index) => minFiscalYear + index)
)

const calcHeight = computed(() => {
  const h = Number(controlRefHeight.value ?? 0) || 0
  return `calc(100% - ${h + 40}px)`
})


// payload[period_index][account_id] = value
const payload = reactive<Record<number, Record<number, number | null>>>({})

const filterMatch = (a: Account) => {
  const term = filterTerm.value.trim().toLowerCase()
  if (!term) return true
  return a.code.toLowerCase().includes(term) || a.name.toLowerCase().includes(term)
}
const formulaAccounts = computed(() => accounts.value.filter(a => a.is_active && a.is_formula && filterMatch(a)))
const postableAccounts = computed(() => accounts.value.filter(a => a.is_active && a.is_postable && filterMatch(a)))
const inputAccounts = postableAccounts
const displayAccounts = computed(() => {
  return accounts.value
    .filter(a => a.is_active && filterMatch(a))
    .sort((a, b) => a.code.localeCompare(b.code, 'ja', { numeric: true }))
})
const isGroupRow = (acct: Account) => acct.depth === 0 && !acct.is_postable && !acct.is_formula

const ensurePayload = () => {
  for (const p of periods.value) {
    payload[p.period_index] ||= {}
    for (const acct of inputAccounts.value) {
      if (payload[p.period_index][acct.id] == null) payload[p.period_index][acct.id] = null
    }
  }
}
watch([periods, inputAccounts], ensurePayload, { immediate: true })

const amountFor = (periodIndex: number, accountId: number) => {
  const v = payload[periodIndex]?.[accountId]
  return typeof v === 'number' && !Number.isNaN(v) ? v : 0
}

const sumBy = (periodIndex: number, predicate: (a: Account) => boolean) => {
  let total = 0
  for (const acct of inputAccounts.value) {
    if (predicate(acct)) total += amountFor(periodIndex, acct.id)
  }
  return total
}

const profitPerMonth = computed<Record<number, number>>(() => {
  const map: Record<number, number> = {}
  for (const p of periods.value) {
    const sales = sumBy(p.period_index, a => a.path.startsWith('/4000/'))
    const cogs = sumBy(p.period_index, a => a.path.startsWith('/5000/'))
    const sga = sumBy(p.period_index, a => a.path.startsWith('/6000/'))
    const nonOpIn = sumBy(p.period_index, a => a.path.startsWith('/7000/'))
    const nonOpOut = sumBy(p.period_index, a => a.path.startsWith('/7100/'))
    const tax = sumBy(p.period_index, a => a.path.startsWith('/9000/'))
    map[p.period_index] = sales - cogs - sga + nonOpIn - nonOpOut - tax
  }
  return map
})

const annualTotals = computed(() => {
  let sales = 0
  for (const p of periods.value) {
    const s = sumBy(p.period_index, a => a.path.startsWith('/4000/'))
    sales += s
  }
  const sgaId = accountByCode.value.get('6270')?.id
  const profitId = accountByCode.value.get('9130')?.id
  const bonusId = accountByCode.value.get('9120')?.id
  const expenses = sgaId ? periods.value.reduce((acc, p) => acc + (formulaValues.value[p.period_index]?.[sgaId] ?? 0), 0) : 0
  const bonuses = bonusId ? periods.value.reduce((acc, p) => acc + (formulaValues.value[p.period_index]?.[bonusId] ?? 0), 0) : 0
  const totalExpenses = expenses + bonuses
  const profit = sales - totalExpenses
  return { sales, totalExpenses, profit }
})

const accountByCode = computed(() => {
  const map = new Map<string, Account>()
  accounts.value.forEach(a => map.set(a.code, a))
  return map
})
const periodStartByIndex = computed<Record<number, DateTime>>(() => {
  const map: Record<number, DateTime> = {}
  for (const p of periods.value) {
    map[p.period_index] = DateTime.fromObject({
      year: p.calendar_year,
      month: p.calendar_month,
      day: 1,
    }).startOf('day')
  }
  return map
})
const transitionDate = computed(() => {
  const raw = selectedProject.value?.transitioned_at
  if (!raw) return null
  const dt = DateTime.fromISO(raw)
  return dt.isValid ? dt.startOf('day') : null
})
const bonusRateForPeriod = (periodIndex: number) => {
  const transition = transitionDate.value
  if (!transition) return 0.1
  const periodStart = periodStartByIndex.value[periodIndex]
  if (!periodStart) return 0.1
  return periodStart > transition ? 0.1 : 0.2
}

const formulaValues = computed<Record<number, Record<number, number>>>(() => {
  const map: Record<number, Record<number, number>> = {}
  const periodIndices = periods.value.map(p => p.period_index)
  for (const pIdx of periodIndices) {
    map[pIdx] = {}
    const memo: Record<string, number> = {}
    for (const acct of formulaAccounts.value) {
      const val = evaluateFormulaAccount(acct, pIdx, memo, new Set())
      map[pIdx][acct.id] = val
    }
  }
  return map
})

const evaluateFormulaAccount = (
  acct: Account,
  periodIndex: number,
  memo: Record<string, number>,
  stack: Set<number>
): number => {
  const key = `${acct.id}-${periodIndex}`
  if (memo[key] !== undefined) return memo[key]
  if (stack.has(acct.id)) {
    memo[key] = 0
    return 0 // prevent cycles
  }
  if (!acct.is_formula) {
    const val = amountFor(periodIndex, acct.id)
    memo[key] = val
    return val
  }

  stack.add(acct.id)
  let expr = acct.formula || ''
  if (acct.code === '9120' && !expr.includes('{bonus_rate}')) {
    const normalized = expr.replace(/\s+/g, '')
    if (normalized === '[9110]*0.2' || normalized === '[9110]*0.1') {
      expr = '[9110]*{bonus_rate}'
    }
  }
  
  expr = expr.replace(/\{bonus_rate\}/g, String(bonusRateForPeriod(periodIndex)))
  const tokenRe = /\[([0-9]{4})(\/\*)?\]/g
  let replaced = expr.replace(tokenRe, (_, code: string, isSection: string) => {
    if (isSection) {
      return String(sumBy(periodIndex, a => a.path.startsWith(`/${code}/`)))
    }
    const dep = accountByCode.value.get(code)
    if (!dep) return '0'
    return String(evaluateFormulaAccount(dep, periodIndex, memo, stack))
  })
  replaced = replaced.replace(/\s+/g, '')
  if (!/^[0-9+\-*/().]+$/.test(replaced)) {
    stack.delete(acct.id)
    memo[key] = 0
    return 0
  }
  try {
    // eslint-disable-next-line no-new-func
    const fn = new Function(`return (${replaced});`)
    const val = fn()
    const num = typeof val === 'number' && Number.isFinite(val) ? val : 0
    
    if (acct.code === '9120' && num < 0) {
      stack.delete(acct.id)
      memo[key] = 0
      return 0
    }
    memo[key] = num
    stack.delete(acct.id)
    return toInt(num)
  } catch {
    stack.delete(acct.id)
    memo[key] = 0
    return 0
  }
}
const toInt = (val, fallback = 0) => {
  const n = Number(val)
  return Number.isFinite(n) ? Math.trunc(n) : fallback
}

const clearAll = () => {
  if (isReadOnly.value) {
    toast('確定済みのため編集できません。')
    return
  }
  for (const p of periods.value) {
    for (const acct of inputAccounts.value) {
      payload[p.period_index][acct.id] = null
    }
  }
}

const save = async () => {
  if (isReadOnly.value) {
    toast('確定済みのため編集できません。')
    return
  }
  const projectId = Number(route.params.projectId || selectedProject.value?.id)
  if (!projectId) return

  const months: { period_index: number; account_id: number; amount: number }[] = []
  for (const p of periods.value) {
    for (const acct of inputAccounts.value) {
      const v = toNumOrNull(payload[p.period_index][acct.id])
      if (v === null) continue
      months.push({
        period_index: p.period_index,
        account_id: acct.id,
        amount: round2(v),
      })
    }
  }
  await api.post(`/projects/${projectId}/plan/grid`, {
    plan_year_id: planYearId.value,
    fiscal_year: fiscalYear.value,
    start_month: startMonth.value,
    scenario_id: selectedScenarioId.value,
    months,
  })
  dirty.value = false
  toast(months.length ? `保存しました（更新 ${months.length} セル）` : '保存しました（全てクリア）')
}

const confirmAndLock = async () => {
  if (isReadOnly.value) return
  const projectId = Number(route.params.projectId || selectedProject.value?.id)
  if (!projectId) return

  const msg = dirty.value
    ? '未保存の変更があります。保存してから確定しますか？\n確定後はPMは編集できません。'
    : '確定しますか？\n確定後はPMは編集できません。'
  const ok = await ask(msg, {
    answers: [
      { value: true, label: '確定' },
      { value: false, label: 'キャンセル' },
    ],
  })
  if (!ok.value) return

  if (dirty.value) {
    await save()
  }

  await api.post(`/projects/${projectId}/plan/lock`, {
    plan_year_id: planYearId.value,
    fiscal_year: fiscalYear.value,
    start_month: startMonth.value,
    scenario_id: selectedScenarioId.value,
  })
  toast('確定しました')
  await load()
}

const unlockPlan = async () => {
  const projectId = Number(route.params.projectId || selectedProject.value?.id)
  if (!projectId) return

  const ok = await ask('確定解除しますか？（PMも編集できる状態に戻ります）', {
    answers: [
      { value: true, label: '解除' },
      { value: false, label: 'キャンセル' },
    ],
  })
  if (!ok.value) return

  await api.post(`/projects/${projectId}/plan/unlock`, {
    plan_year_id: planYearId.value,
    fiscal_year: fiscalYear.value,
    start_month: startMonth.value,
    scenario_id: selectedScenarioId.value,
  })
  toast('確定解除しました')
  await load()
}

const loadScenarios = async (projectId: number) => {
  const data = await api.get(`/projects/${projectId}/plan/scenarios`)
  scenarios.value = data || []
}

const load = async () => {
  const projectId = Number(route.params.projectId || selectedProject.value?.id)
  if (!projectId) return

  isLoading.value = true
  await loadScenarios(projectId)

  const data = await api.get(`/projects/${projectId}/plan/grid`, {
    fiscal_year: fiscalYear.value,
    start_month: startMonth.value,
    scenario_id: selectedScenarioId.value,
  })

  planYearId.value = data?.plan_year_id ?? null
  selectedScenarioId.value = data?.scenario_id ?? selectedScenarioId.value ?? null
  accounts.value = data?.accounts ?? []
  lockState.is_locked = Boolean(data?.lock?.is_locked)
  lockState.locked_by_user_id = data?.lock?.locked_by_user_id ?? null
  lockState.locked_at = data?.lock?.locked_at ?? null

  periods.value = (data?.periods ?? []).map((p: any) => ({
    period_index: Number(p.period_index),
    calendar_year: Number(p.calendar_year),
    calendar_month: Number(p.calendar_month),
  }))

  // reset payload
  for (const key of Object.keys(payload)) {
    // @ts-ignore delete index signature
    delete payload[key as any]
  }
  ensurePayload()

  const amountMap = data?.amounts ?? {}
  Object.entries(amountMap).forEach(([pIdx, row]: [string, any]) => {
    const periodIndex = Number(pIdx)
    payload[periodIndex] ||= {}
    Object.entries(row || {}).forEach(([acctId, val]: [string, any]) => {
      payload[periodIndex][Number(acctId)] = toNumOrNull(val)
    })
  })

  ensurePayload()
  dirty.value = false
  lastLoaded.fiscalYear = fiscalYear.value
  lastLoaded.startMonth = startMonth.value
  lastLoaded.scenarioId = selectedScenarioId.value
  initialized.value = true
  isLoading.value = false
}

const activeScenarioLabel = computed(() => {
  if (!selectedScenarioId.value) return 'ベース'
  const found = scenarios.value.find(s => s.id === selectedScenarioId.value)
  return found ? found.name : 'シナリオ'
})

const downloadTemplate = async () => {
  const projectId = Number(route.params.projectId || selectedProject.value?.id)
  if (!projectId) return
  const res = await axios.get(`/projects/${projectId}/plan/template`, {
    params: {
      fiscal_year: fiscalYear.value,
      start_month: startMonth.value,
      scenario_id: selectedScenarioId.value,
    },
    responseType: 'blob',
  })
  const url = URL.createObjectURL(res.data)
  const a = document.createElement('a')
  a.href = url
  a.download = `plan_${fiscalYear.value}.xlsx`
  document.body.appendChild(a)
  a.click()
  a.remove()
  URL.revokeObjectURL(url)
}

const uploadTemplate = async (ev: Event) => {
  if (isReadOnly.value) {
    toast('確定済みのため編集できません。')
    return
  }
  const projectId = Number(route.params.projectId || selectedProject.value?.id)
  if (!projectId) return
  const input = ev.target as HTMLInputElement
  if (!input.files?.length) return
  const file = input.files[0]
  const fd = new FormData()
  fd.append('file', file)
  fd.append('fiscal_year', String(fiscalYear.value))
  fd.append('start_month', String(startMonth.value))
  if (selectedScenarioId.value != null) fd.append('scenario_id', String(selectedScenarioId.value))

  // dry run first
  fd.append('dry_run', '1')
  const preview = await api.post(`/projects/${projectId}/plan/template`, fd)
  const summary = preview?.summary || {}
  const unknown = (summary.unknown_accounts || []).join(', ')
  const msg = [
    `インポート確認`,
    `行: ${summary.rows_matched ?? 0}/${summary.rows_total ?? 0}`,
    `セル: ${summary.cells_applied ?? 0}/${summary.cells_total ?? 0}`,
    unknown ? `不明な科目: ${unknown}` : '',
  ].filter(Boolean).join('\n')
  const confirm = await ask(msg || '適用しますか？', {
    answers: [
      { value: true, label: '適用' },
      { value: false, label: 'キャンセル' }
    ]
  })
  if (!confirm.value) { input.value = ''; return }

  // actual apply
  const fdApply = new FormData()
  fdApply.append('file', file)
  fdApply.append('fiscal_year', String(fiscalYear.value))
  fdApply.append('start_month', String(startMonth.value))
  if (selectedScenarioId.value != null) fdApply.append('scenario_id', String(selectedScenarioId.value))
  const applied = await api.post(`/projects/${projectId}/plan/template`, fdApply)
  input.value = ''
  dirty.value = true
  toast(`インポート完了: 行 ${applied?.summary?.rows_matched ?? 0}/${applied?.summary?.rows_total ?? 0}`)
  await load()
}

const pasteClipboard = async () => {
  if (isReadOnly.value) {
    toast('確定済みのため編集できません。')
    return
  }
  try {
    const text = await navigator.clipboard.readText()
    await applyPastedBlock(text)
  } catch (e) {
    console.warn('Clipboard read failed', e)
  }
}

const applyPastedBlock = async (text: string) => {
  if (!text) return
  const lines = text.split(/\r?\n/).filter(l => l.trim() !== '')
  const periodOrder = periods.value.map(p => p.period_index)
  const accountByCode = new Map(inputAccounts.value.map(a => [a.code, a.id]))
  const nameCounts: Record<string, number> = {}
  inputAccounts.value.forEach(a => { nameCounts[a.name] = (nameCounts[a.name] || 0) + 1 })
  const accountByName = new Map(inputAccounts.value.filter(a => nameCounts[a.name] === 1).map(a => [a.name, a.id]))

  let matchedRows = 0
  let totalRows = 0
  const unknown: string[] = []

  for (const line of lines) {
    const cells = line.split('\t')
    if (cells.length < 2) continue
    totalRows++
    const code = cells[0].trim()
    const maybeName = cells[1]?.trim()
    let accountId = accountByCode.get(code)
    if (!accountId && maybeName) accountId = accountByName.get(maybeName)
    if (!accountId) {
      unknown.push(code || maybeName || '(不明)')
      continue
    }
    matchedRows++
    const periodCount = periodOrder.length
    let valueCells = cells.slice(1)
    // If template rows include account name as second column, drop it
    if (valueCells.length === periodCount + 1) {
      valueCells = valueCells.slice(1)
    }
    // trim or pad to 12 cells
    valueCells = valueCells.slice(0, periodCount)
    while (valueCells.length < periodCount) valueCells.push('')
    const values = valueCells.map(toNumOrNull)
    periodOrder.forEach((pIdx, i) => {
      const v = values[i]
      if (v !== null && v !== undefined) {
        payload[pIdx] ||= {}
        payload[pIdx][accountId] = v
      }
    })
  }
  if (totalRows > 0) {
    const msg = [
      `ペースト内容`,
      `行: ${matchedRows}/${totalRows}`,
      unknown.length ? `不明な科目: ${unknown.join(', ')}` : ''
    ].filter(Boolean).join('\n')
    const ok = await ask(msg || '適用しますか？', {
      answers: [
        { value: true, label: '適用' },
        { value: false, label: 'キャンセル' }
      ]
    })
    if (!ok.value) return
    dirty.value = true
    toast(`ペースト適用: 行 ${matchedRows}/${totalRows}`)
  }
}

const copyFirstMonthToAll = () => {
  if (isReadOnly.value) {
    toast('確定済みのため編集できません。')
    return
  }
  const first = periods.value[0]?.period_index
  if (!first) return
  for (const acct of inputAccounts.value) {
    const base = toNumOrNull(payload[first]?.[acct.id])
    if (base === null) continue
    for (const p of periods.value) {
      payload[p.period_index][acct.id] = base
    }
  }
}

const createScenario = async () => {
  const projectId = Number(route.params.projectId || selectedProject.value?.id)
  if (!projectId) return

  const { input, decision } = await askInput('シナリオ名', {
    label: 'シナリオ名',
    required: true,
    placeholder: '例: ストレッチ',
  })
  if (!decision.value || !input) return
  const code = input.trim().toLowerCase().replace(/\s+/g, '_')
  const payload = { name: input.trim(), code, weight: 1.0 }
  const res = await api.post(`/projects/${projectId}/plan/scenarios`, payload)
  const newId = res?.id
  await loadScenarios(projectId)
  selectedScenarioId.value = newId ?? null
  await load()
  toast('シナリオを作成しました')
}



const monthLabel = (m: number) => ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'][m-1]
const fmt = (n?: number) =>
  typeof n === 'number' && Number.isFinite(n)
    ? new Intl.NumberFormat('ja-JP', { maximumFractionDigits: 0 }).format(n)
    : '—';
const round2 = (x: number) => Math.round(x * 100) / 100
const toNumOrNull = (v: unknown) => {
  if (v === '' || v == null) return null
  const n = typeof v === 'string' ? Number(v.replace(/,/g, '')) : Number(v)
  return Number.isFinite(n) ? n : null
}
const formatNumber = (v: unknown) => {
  const n = typeof v === 'number' ? v : Number(String(v ?? '').replace(/,/g, ''))
  return Number.isFinite(n) ? n.toLocaleString('ja-JP') : ''
}

const parseNumber = (s: string) => {
  const cleaned = s.replace(/,/g, '').trim()
  if (cleaned === '') return 0
  const n = Number(cleaned)
  return Number.isFinite(n) ? n : 0
}

const onAmountInput = (e: Event, periodIndex: number, acctId: string | number) => {
  const el = e.target as HTMLInputElement
  const cleaned = el.value.replace(/[^\d,]/g, '')
  el.value = cleaned

  payload[periodIndex][acctId] = parseNumber(cleaned)
}
onMounted(() => {
  load()
  const el = controlRef.value
  if (!el) return

  const ro = new ResizeObserver(() => {
    controlRefHeight.value = el.offsetHeight ?? 0
  })

  ro.observe(el)
  controlRefHeight.value = el.offsetHeight ?? 0

  onBeforeUnmount(() => ro.disconnect())
})
watch(payload, () => {
  if (!initialized.value) return
  if (isLoading.value) return
  if (isReadOnly.value) return
  dirty.value = true
}, { deep: true })
watch(fiscalYear, async () => {
  if (!initialized.value) return
  periods.value = generateFiscalPeriods(fiscalYear.value, startMonth.value)
  await load()
})

watch(startMonth, async () => {
  if (!initialized.value) return
  periods.value = generateFiscalPeriods(fiscalYear.value, startMonth.value)
  await load()
})

watch(selectedScenarioId, async (n, o) => {
  if (!initialized.value) return
  if (n === o) return
  await load()
})
</script>

<style scoped>
.sheet-row {
    background-color: var(--background-color);
    font-size: 13px;
}
.section-row {
    background-color: var(--bg3);
    color: var(--primary-color);
    font-size: 13px;
}
.section-cell {
    background-color: var(--bg3);
    border-bottom: 1px solid var(--normalBorder);
}
tfoot {
    font-size: 13px;
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
@media screen and (max-width:959px) {
    .sheet-row {
      font-size: 12px;
    }
    tfoot {
      font-size: 12px;
    }
}
</style>
