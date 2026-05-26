<template>
    <div class="period-range-picker" ref="pickerRef">
        <button class="picker-trigger" type="button" @click.stop="togglePanel">
            <span>{{ displayStart }}</span>
            <span v-if="displayStart !== displayEnd" class="trigger-separator">~</span>
            <span v-if="displayStart !== displayEnd">{{ displayEnd }}</span>
            <span v-if="totalBadge" style="position: static;text-indent: inherit;" class="side-notification side-notification--comment-only">{{ totalBadge }}</span>
        </button>
        <div
            v-if="open"
            class="picker-panel"
            @click.stop
        >
            <div class="picker-column flex">
                <header class="column-header">
                    <span>開始</span>
                    <div class="year-controls">
                        <button type="button" @click="adjustStartYear(-1)">‹</button>
                        <span>{{ startYearModel }}年</span>
                        <button type="button" @click="adjustStartYear(1)">›</button>
                    </div>
                </header>
                <div class="month-grid">
                    <button
                        v-for="m in months"
                        :key="`start-${m}`"
                        type="button"
                        :class="['month-cell', { selected: startMonthModel === m }]"
                        @click="setStartMonth(m)"
                    >
                        {{ m }}月
                        <span v-if="periodBadge && periodBadge[`${startYearModel}-${m.toString().padStart(2, '0')}`]" class="badge-count">{{ periodBadge[`${startYearModel}-${m.toString().padStart(2, '0')}`] }}</span>
                    </button>
                </div>
            </div>
            <div class="picker-column flex">
                <header class="column-header">
                    <span>終了</span>
                    <div class="year-controls">
                        <button type="button" @click="adjustEndYear(-1)">‹</button>
                        <span>{{ endYearModel }}年</span>
                        <button type="button" @click="adjustEndYear(1)">›</button>
                    </div>
                </header>
                <div class="month-grid">
                    <button
                        v-for="m in months"
                        :key="`end-${m}`"
                        type="button"
                        :class="['month-cell', { selected: endMonthModel === m }]"
                        @click="setEndMonth(m)"
                    >
                        {{ m }}月
                        <span v-if="periodBadge && periodBadge[`${endYearModel}-${m.toString().padStart(2, '0')}`]" class="badge-count">{{ periodBadge[`${endYearModel}-${m.toString().padStart(2, '0')}`] }}</span>

                    </button>
                </div>
            </div>
            <footer class="picker-footer">
                <div class="range-info">
                    <span>{{ rangeSummary }}</span>
                    <span v-if="rangeWarning" class="warning-text">{{ rangeWarning }}</span>
                </div>
                <button class="close-button" type="button" @click="open = false">
                    閉じる
                </button>
            </footer>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useDialog } from '@/composables/dialog';
import { isMobile } from '@/utils/tools';
import { DateTime } from 'luxon';
import { ComputedRef, computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type MonthNumber = 1|2|3|4|5|6|7|8|9|10|11|12
type RangeChangeSource = 'start' | 'end'

const props = defineProps<{
    start: string;
    end: string;
    maxMonths?: number;
    totalBadge?: number;
    periodBadge?: { [key:string]: number };
}>()

const emit = defineEmits<{
    (e: 'update:start', value: string): void;
    (e: 'update:end', value: string): void;
    (e: 'change', value: { start: string; end: string }): void;
}>()

const { ping } = useDialog()
const MAX_MONTHS = computed(() => Math.max(1, props.maxMonths ?? 12))
const months = Array.from({ length: 12 }, (_, idx) => (idx + 1) as MonthNumber)

const pickerRef = ref<HTMLElement | null>(null)
const open = ref(false)

const parseMonth = (value: string): DateTime => {
    if (!value) return DateTime.now().startOf('month')
    const dt = DateTime.fromFormat(`${value}-01`, 'yyyy-MM-dd', { zone: 'Asia/Tokyo' })
    return dt.isValid ? dt.startOf('month') : DateTime.now().startOf('month')
}

const currentStart: ComputedRef<DateTime> = computed(() => parseMonth(props.start))
const currentEnd: ComputedRef<DateTime> = computed(() => parseMonth(props.end))

const startYearModel = ref<number>(currentStart.value.year)
const startMonthModel = ref<MonthNumber | null>(currentStart.value.month as MonthNumber)
const endYearModel = ref<number>(currentEnd.value.year)
const endMonthModel = ref<MonthNumber | null>(currentEnd.value.month as MonthNumber)

const clampYear = (year: number) => {
    const now = DateTime.now().year
    const min = Math.min(currentStart.value.year, currentEnd.value.year, now - 5)
    const max = Math.max(currentStart.value.year, currentEnd.value.year, now + 6)
    return Math.min(Math.max(year, min), max)
}

const syncFromProps = () => {
    startYearModel.value = currentStart.value.year
    startMonthModel.value = currentStart.value.month as MonthNumber
    endYearModel.value = currentEnd.value.year
    endMonthModel.value = currentEnd.value.month as MonthNumber
}

watch(() => [props.start, props.end], syncFromProps)

const formatLabel = (dt: DateTime) => dt.toFormat('yyyy年M月')
const displayStart = computed(() => formatLabel(currentStart.value))
const displayEnd = computed(() => formatLabel(currentEnd.value))

const monthsBetween = (start: DateTime, end: DateTime) =>
    Math.round(end.diff(start, 'months').months ?? 0)

const rangeSummary = computed(() => {
    const monthsDiff = monthsBetween(currentStart.value, currentEnd.value) + 1
    return `${monthsDiff}ヶ月`
})

const rangeWarning = computed(() => {
    const diff = monthsBetween(currentStart.value, currentEnd.value)
    return diff + 1 > MAX_MONTHS.value ? `最大${MAX_MONTHS.value}ヶ月まで` : ''
})

const emitRange = (start: DateTime, end: DateTime) => {
    const startStr = start.toFormat('yyyy-MM')
    const endStr = end.toFormat('yyyy-MM')
    emit('update:start', startStr)
    emit('update:end', endStr)
    emit('change', { start: startStr, end: endStr })
}

const buildMonthDate = (year: number, month: MonthNumber) =>
    DateTime.fromObject({ year, month, day: 1 }, { zone: 'Asia/Tokyo' }).startOf('month')
const hasStartMonth = () => startMonthModel.value != null
const hasEndMonth = () => endMonthModel.value != null
const clearEnd = (year = startYearModel.value) => {
  endYearModel.value = year
  endMonthModel.value = null
}
const keepRangeWithinLimit = (start: DateTime, end: DateTime, source: RangeChangeSource) => {
    let nextStart = start.startOf('month')
    let nextEnd = end.startOf('month')
    const monthsDiff = monthsBetween(nextStart, nextEnd) + 1

    if (monthsDiff > MAX_MONTHS.value) {
        if (source === 'start') {
            nextEnd = nextStart.plus({ months: MAX_MONTHS.value - 1 })
        } else {
            nextStart = nextEnd.minus({ months: MAX_MONTHS.value - 1 })
        }
    }

    return { start: nextStart, end: nextEnd }
}

const tryUpdateRange = (start: DateTime, end: DateTime, source: RangeChangeSource, options?: { silent?: boolean }) => {
  if (!start.isValid || !end.isValid) {
    if (!options?.silent) ping('有効な日付を選択してください。')
    return false
  }

  if (end < start) {
    startYearModel.value = start.year
    startMonthModel.value = start.month as MonthNumber
    clearEnd(start.year)
    return true
  }

  const nextRange = keepRangeWithinLimit(start, end, source)

  startYearModel.value = nextRange.start.year
  startMonthModel.value = nextRange.start.month as MonthNumber
  endYearModel.value = nextRange.end.year
  endMonthModel.value = nextRange.end.month as MonthNumber

  emitRange(nextRange.start, nextRange.end)
  return true
}


const adjustStartYear = (delta: number) => {
    startYearModel.value = clampYear(startYearModel.value + delta)
    startMonthModel.value = null
}
const adjustEndYear = (delta: number) => {
    endYearModel.value = clampYear(endYearModel.value! + delta)
    endMonthModel.value = null
}
const setStartMonth = (month: MonthNumber) => {
  const nextStart = buildMonthDate(startYearModel.value, month)

  if (!hasEndMonth()) {
    startYearModel.value = nextStart.year
    startMonthModel.value = nextStart.month as MonthNumber
    return
  }

  const currentEnd = buildMonthDate(endYearModel.value!, endMonthModel.value!)
  tryUpdateRange(nextStart, currentEnd, 'start')
}

const setEndMonth = (month: MonthNumber) => {
  if (!hasStartMonth()) {
    endMonthModel.value = month
    return
  }

  const currentStart = buildMonthDate(startYearModel.value, startMonthModel.value!)
  const nextEnd = buildMonthDate(endYearModel.value, month)
  tryUpdateRange(currentStart, nextEnd, 'end')
}



const handleDocumentClick = (event: MouseEvent) => {
    if (!pickerRef.value) return
    if (pickerRef.value.contains(event.target as Node)) return
    open.value = false
}

const togglePanel = () => {
    open.value = !open.value
}

watch(open, (isOpen) => {
    if (isOpen) {
        syncFromProps()
        document.addEventListener('click', handleDocumentClick)
    } else {
        document.removeEventListener('click', handleDocumentClick)
    }
})

onMounted(syncFromProps)
onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick)
})
</script>

<style scoped lang="scss">
.badge-count{
    background: #F28C28;
    color: white;
    border-radius: 999px;
    padding: 1px 6px;
    font-size: 10px;
    line-height: 1;
}
.period-range-picker {
    position: relative;
    display: inline-flex;
}
.picker-trigger {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    font-size: 14px;
    color: var(--primary-color);
    cursor: pointer;
    min-width: 40px;
}
.picker-trigger:hover {
    background: var(--bg2);
}
.trigger-separator {
    opacity: 0.6;
}
.picker-panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 20;
    display: grid;
    grid-template-columns: repeat(2, minmax(180px, auto));
    column-gap: 16px;
    row-gap: 12px;
    padding: 16px;
    background: var(--background-color);
    box-shadow: 0 1px 2px 0 rgb(60 64 67 / 30%), 0 2px 6px 2px rgb(60 64 67 / 15%);
    box-sizing: border-box;
    width: max-content;
    min-width: 360px;
}
.picker-column {
    flex: 0 0 auto;
    min-width: 200px;
    flex-direction: column;
    gap: 8px;
}
.column-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    color: var(--primary-color);
}
.year-controls {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.year-controls button {
    border: none;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    padding: 2px 6px;
    font-size: 14px;
}
.year-controls button:hover {
    background: var(--bg2);
    border-radius: 4px;
}
.month-grid {
    position: static;
    display: grid;
    grid-template-columns: repeat(3, minmax(60px, auto));
    gap: 6px;
    box-shadow: none;
}
.month-cell {
    border: 1px solid transparent;
    padding: 6px 0;
    background: var(--bg2);
    color: var(--primary-color);
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}
.month-cell:hover {
    border-color: var(--primary-color);
}
.month-cell.selected {
    background: var(--primary-color);
    color: var(--background-color);
}
.picker-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
    width: 100%;
    grid-column: 1 / -1;
}
.range-info {
    display: flex;
    flex-direction: column;
    font-size: 12px;
    color: var(--text-color);
}
.warning-text {
    color: tomato;
    margin-top: 2px;
}
.close-button {
    border: none;
    background: var(--primary-color);
    color: var(--background-color);
    padding: 6px 12px;
    cursor: pointer;
    font-size: 12px;
}
.close-button:hover {
    opacity: 0.85;
}
@media screen and (max-width: 540px) {
    .picker-panel {
        // left: 50%;
        // right: auto;
        grid-template-columns: 1fr;
        min-width: 240px;
        // transform: translateX(-50%);
    }
    .month-grid {
        grid-template-columns: repeat(4, minmax(48px, 1fr));
    }
}
</style>
