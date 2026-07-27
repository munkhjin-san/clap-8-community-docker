// Pure display/aggregation helpers shared by ProjectTotalFinance and its child tables.

export type ScenarioKey = 'yearly_plan' | 'profit' | 'settlement'
export type Key = 'sales' | 'expense' | 'profit'
export type MetricDisplayKey = Key | 'profit_rate'

export interface UnitData {
    expense: number
    sales: number
    profit: number
    id?: number
    has_data?: boolean
    is_forecast?: boolean
}

export type MobileUnit = {
    label: string
    scale: number
}

export const emptyUnit: UnitData = {
    expense: 0,
    sales: 0,
    profit: 0,
    has_data: false,
    is_forecast: false,
}

export const metricDisplayItems: Array<{ key: MetricDisplayKey; label: string }> = [
    { key: 'sales', label: '売上' },
    { key: 'expense', label: '販管費' },
    { key: 'profit', label: '利益' },
    { key: 'profit_rate', label: '利益率' },
]

export const hasSettlementEntry = (unit?: Partial<UnitData> | null) => unit?.has_data === true

export const settlementValue = (unit: Partial<UnitData> | null | undefined, key: Key) =>
    hasSettlementEntry(unit) ? Math.round(Number(unit?.[key] ?? 0)) : NaN

export const settlementProfitValue = (unit: Partial<UnitData> | null | undefined) =>
    hasSettlementEntry(unit) ? Math.round(Number(unit?.profit ?? 0)) : NaN

export const normalizeUnitData = (unit?: Partial<UnitData> | null): UnitData => {
    const sales = Number(unit?.sales ?? 0)
    const expense = Number(unit?.expense ?? 0)
    const explicitProfit = unit?.profit
    const profit = Number.isFinite(Number(explicitProfit)) ? Number(explicitProfit) : sales - expense
    const has_data = unit?.has_data ?? unit !== undefined
    const is_forecast = unit?.is_forecast ?? false
    return {
        sales,
        expense,
        profit,
        has_data,
        is_forecast,
    }
}

export const percentizer = (data: Partial<UnitData> | null | undefined) => {
    if (data && 'has_data' in data && data.has_data === false) {
        return { value: 0, display: '—' }
    }
    const sales = Number(data?.sales ?? 0)
    const explicitProfit = data?.profit
    const derivedProfit = Number(data?.sales ?? 0) - Number(data?.expense ?? 0)
    const profit = Number.isFinite(Number(explicitProfit)) ? Number(explicitProfit) : derivedProfit
    if (!sales || Number.isNaN(sales)) {
        return { value: 0, display: '—' }
    }
    const value = (profit / sales) * 100
    if (!Number.isFinite(value)) {
        return { value: 0, display: '—' }
    }
    return { value, display: `${value.toFixed(2)}%` }
}

export const metricNumericValue = (
    unit: Partial<UnitData> | null | undefined,
    scenario: ScenarioKey,
    key: MetricDisplayKey,
) => {
    if (key === 'profit_rate') return percentizer(unit).value
    if (scenario === 'settlement') {
        return key === 'profit'
            ? settlementProfitValue(unit ?? undefined)
            : settlementValue(unit ?? undefined, key)
    }
    return key === 'profit'
        ? Number(unit?.profit ?? (Number(unit?.sales ?? 0) - Number(unit?.expense ?? 0)))
        : Number(unit?.[key] ?? 0)
}

export const comparisonDeltaDisplay = (
    left: Partial<UnitData> | null | undefined,
    right: Partial<UnitData> | null | undefined,
    scenario: ScenarioKey,
    key: MetricDisplayKey,
) => {
    const comparisonValue = metricNumericValue(left, scenario, key)
    const targetValue = metricNumericValue(right, scenario, key)

    if (Number.isNaN(comparisonValue) || Number.isNaN(targetValue)) return '—'

    const delta = targetValue - comparisonValue
    if (key === 'profit_rate') {
        return `${delta > 0 ? '+' : ''}${delta.toFixed(2)}pt`
    }

    if (comparisonValue === 0) {
        return targetValue === 0 ? '0.0%' : 'New'
    }

    const percent = (delta / Math.abs(comparisonValue)) * 100
    return `${percent > 0 ? '+' : ''}${percent.toFixed(1)}%`
}

export const comparisonDeltaClass = (
    left: Partial<UnitData> | null | undefined,
    right: Partial<UnitData> | null | undefined,
    scenario: ScenarioKey,
    key: MetricDisplayKey,
) => {
    const comparisonValue = metricNumericValue(left, scenario, key)
    const targetValue = metricNumericValue(right, scenario, key)

    if (Number.isNaN(comparisonValue) || Number.isNaN(targetValue) || comparisonValue === targetValue) {
        return 'mobile-finance-compare-table__delta--neutral'
    }

    const improved = key === 'expense'
        ? targetValue < comparisonValue
        : targetValue > comparisonValue

    return improved
        ? 'mobile-finance-compare-table__delta--positive'
        : 'mobile-finance-compare-table__delta--negative'
}

export const comparisonGapDisplay = (
    left: Partial<UnitData> | null | undefined,
    right: Partial<UnitData> | null | undefined,
    leftScenario: ScenarioKey,
    rightScenario: ScenarioKey,
    key: MetricDisplayKey,
    scale = 1,
) => {
    const leftValue = metricNumericValue(left, leftScenario, key)
    const rightValue = metricNumericValue(right, rightScenario, key)
    if (Number.isNaN(leftValue) || Number.isNaN(rightValue)) return '—'
    const delta = rightValue - leftValue
    if (key === 'profit_rate') {
        return `${delta > 0 ? '+' : ''}${delta.toFixed(2)}pt`
    }
    return `${delta > 0 ? '+' : ''}${formatMillions(delta, scale)}`
}

export const comparisonGapClass = (
    left: Partial<UnitData> | null | undefined,
    right: Partial<UnitData> | null | undefined,
    leftScenario: ScenarioKey,
    rightScenario: ScenarioKey,
    key: MetricDisplayKey,
) => {
    const leftValue = metricNumericValue(left, leftScenario, key)
    const rightValue = metricNumericValue(right, rightScenario, key)
    if (Number.isNaN(leftValue) || Number.isNaN(rightValue) || leftValue === rightValue) {
        return 'mobile-finance-compare-table__delta--neutral'
    }
    const improved = key === 'expense'
        ? rightValue < leftValue
        : rightValue > leftValue
    return improved
        ? 'mobile-finance-compare-table__delta--positive'
        : 'mobile-finance-compare-table__delta--negative'
}

const truncateDecimal = (value: number, fractionDigits: number) => {
    const factor = 10 ** fractionDigits
    return Math.trunc(value * factor) / factor
}

export const formatMillions = (value: number, scale = 1_000_000) => {
    if (Number.isNaN(value)) return 'ー'
    if (scale === 1) {
        return Math.round(value).toLocaleString('en-US')
    }
    const normalized = value / scale
    const abs = Math.abs(normalized)
    const maxFractionDigits = abs >= 1000 ? 0 : abs >= 100 ? 1 : abs >= 10 ? 2 : 3
    const truncated = truncateDecimal(normalized, maxFractionDigits)
    return truncated.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: maxFractionDigits,
    })
}

export const formatMobileMetric = (
    unit: Partial<UnitData> | null | undefined,
    scenario: ScenarioKey,
    key: MetricDisplayKey,
    scale?: number,
) => {
    if (key === 'profit_rate') {
        const rate = percentizer(unit)
        return rate.display === '—' ? 'ー' : rate.display
    }
    return formatMillions(metricNumericValue(unit, scenario, key), scale)
}

export const MONEY_UNITS: MobileUnit[] = [
    { label: '十億円', scale: 1_000_000_000 },
    { label: '百万円', scale: 1_000_000 },
    { label: '千円', scale: 1_000 },
    { label: '円', scale: 1 },
]

export const mobileMoneyUnit = (values: number[]): MobileUnit => {
    const maxAbs = values.reduce((max, value) => {
        if (!Number.isFinite(value)) return max
        return Math.max(max, Math.abs(value))
    }, 0)
    if (maxAbs >= 1_000_000_000) return MONEY_UNITS[0]
    if (maxAbs >= 1_000_000) return MONEY_UNITS[1]
    if (maxAbs >= 1_000) return MONEY_UNITS[2]
    return MONEY_UNITS[3]
}

export const unitValuesFromEntry = (unit: Partial<UnitData> | null | undefined, scenario: ScenarioKey): number[] => {
    const sales = metricNumericValue(unit, scenario, 'sales')
    const expense = metricNumericValue(unit, scenario, 'expense')
    const profit = metricNumericValue(unit, scenario, 'profit')
    return [sales, expense, profit].filter(value => Number.isFinite(value))
}

export const filteredSettlementUnit = (
    unit: Partial<UnitData> | null | undefined,
    includeForecast: boolean,
): UnitData | Partial<UnitData> | undefined => {
    if (!unit) return unit ?? undefined
    if (includeForecast || !unit.is_forecast) return unit
    return {
        ...emptyUnit,
        id: unit.id,
    }
}

export const aggregateScenarioUnits = (
    units: Array<Partial<UnitData> | UnitData | null | undefined>,
    scenario: ScenarioKey,
    includeForecast: boolean,
): UnitData => {
    let sales = 0
    let expense = 0
    let hasData = false
    let hasForecast = false

    units.forEach((rawUnit) => {
        if (!rawUnit) return
        const unit = scenario === 'settlement'
            ? filteredSettlementUnit(rawUnit, includeForecast)
            : rawUnit
        const normalized = normalizeUnitData(unit)
        if (!normalized.has_data) return
        sales += normalized.sales
        expense += normalized.expense
        hasData = true
        hasForecast = hasForecast || !!normalized.is_forecast
    })

    if (!hasData) return emptyUnit

    return {
        sales,
        expense,
        profit: sales - expense,
        has_data: true,
        is_forecast: scenario === 'settlement' ? hasForecast : false,
    }
}
