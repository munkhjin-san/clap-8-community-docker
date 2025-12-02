<template>
    <div class="period-range-picker" ref="pickerRef">
        <button class="picker-trigger" type="button" @click.stop="togglePanel">
            <span>{{ displayStart }}</span>
            <span class="trigger-separator">~</span>
            <span>{{ displayEnd }}</span>
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
            <div class="picker-column md:flex hidden">
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
import { DateTime } from 'luxon'
import { ComputedRef, computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

type MonthNumber = 1|2|3|4|5|6|7|8|9|10|11|12

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
const startMonthModel = ref<MonthNumber>(currentStart.value.month as MonthNumber)
const endYearModel = ref<number>(currentEnd.value.year)
const endMonthModel = ref<MonthNumber>(currentEnd.value.month as MonthNumber)

const clampYear = (year: number) => {
    const now = DateTime.now().year
    const min = Math.min(currentStart.value.year, currentEnd.value.year, now - 5)
    const max = Math.max(currentStart.value.year, currentEnd.value.year, now + 6)
    return Math.min(Math.max(year, min), max)
}

let syncing = false

const syncFromProps = () => {
    syncing = true
    startYearModel.value = currentStart.value.year
    startMonthModel.value = currentStart.value.month as MonthNumber
    endYearModel.value = currentEnd.value.year
    endMonthModel.value = currentEnd.value.month as MonthNumber
    syncing = false
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

const normalizeRange = (start: DateTime, end: DateTime, constrainedBy: 'start' | 'end') => {
    let s = start.startOf('month')
    let e = end.startOf('month')

    if (e < s) {
        if (constrainedBy === 'start') {
            e = s
        } else {
            s = e
        }
    }

    const diff = monthsBetween(s, e)
    const maxSpan = MAX_MONTHS.value - 1
    if (diff > maxSpan) {
        if (constrainedBy === 'start') {
            e = s.plus({ months: maxSpan })
        } else {
            s = e.minus({ months: maxSpan })
        }
    }

    return { start: s, end: e }
}

const updateRange = (constrainedBy: 'start' | 'end') => {
    if (syncing) return

    const startCandidate = DateTime.fromObject({
        year: startYearModel.value,
        month: startMonthModel.value,
        day: 1,
    }, { zone: 'Asia/Tokyo' })

    const endCandidate = DateTime.fromObject({
        year: endYearModel.value,
        month: endMonthModel.value,
        day: 1,
    }, { zone: 'Asia/Tokyo' })

    if (!startCandidate.isValid || !endCandidate.isValid) return

    const { start, end } = normalizeRange(startCandidate, endCandidate, constrainedBy)

    syncing = true
    startYearModel.value = start.year
    startMonthModel.value = start.month as MonthNumber
    endYearModel.value = end.year
    endMonthModel.value = end.month as MonthNumber
    syncing = false

    emitRange(start, end)
}

watch([startYearModel, startMonthModel], () => {
    if (syncing) return
    updateRange('start')
})
watch([endYearModel, endMonthModel], () => {
    if (syncing) return
    updateRange('end')
})

const adjustStartYear = (delta: number) => {
    startYearModel.value = clampYear(startYearModel.value + delta)
}
const adjustEndYear = (delta: number) => {
    endYearModel.value = clampYear(endYearModel.value + delta)
}
const setStartMonth = (month: MonthNumber) => {
    startMonthModel.value = month
}
const setEndMonth = (month: MonthNumber) => {
    endMonthModel.value = month
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
    z-index: 30;
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
        left: 0;
        right: auto;
        grid-template-columns: 1fr;
        min-width: 240px;
    }
    .month-grid {
        grid-template-columns: repeat(4, minmax(48px, 1fr));
    }
}
</style>
