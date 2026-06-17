<template>
    <section class="planned-leave-panel">
        <div class="planned-leave-header">
            <div>
                <p class="planned-leave-title">計画有給一覧</p>
                <p class="planned-leave-summary">{{ year }}年度 計画有給 {{ plannedLeaveCount }}日</p>
            </div>
            <div class="planned-leave-year-control">
                <span>年度</span>
                <YearPicker :selected-year="year" right="auto" @set-date="setDate"/>
            </div>
        </div>

        <div v-if="loading" class="planned-leave-state">
            読み込み中
        </div>

        <div v-else-if="paidHolidays.length" class="planned-leave-list">
            <div v-for="(item, index) in paidHolidays" :key="index" class="planned-leave-row">
                <div>
                    <p class="planned-leave-date">{{ formatDate(item.shift_day) }}</p>
                    <p class="planned-leave-weekday">{{ formatWeekday(item.shift_day) }}</p>
                </div>
                <span :class="['planned-leave-status', { 'is-used': isUsed(item.shift_day) }]">
                    {{ isUsed(item.shift_day) ? '使用済み' : '予定' }}
                </span>
            </div>
        </div>

        <div v-else class="planned-leave-state">
            {{ year }}年度の計画有給はありません
        </div>
    </section>
</template>

<script lang="ts" setup>
import YearPicker from '@/components/Global/YearPicker.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { DateTime } from 'luxon';
import { Shift } from '@/interface/workInterface';
import { useApi } from '@/composables/api';

const year = ref(DateTime.now().year)
const props = defineProps<{
    userId: number | string
}>()
const paidHolidays = ref<Shift[]>([])
const loading = ref(false)
const api = useApi()
const plannedLeaveCount = computed(() => paidHolidays.value.length)

const setDate = (val: {year: number}) => {
    year.value = val.year
    getPlannedLeaves()
}
const shiftDayValue = (value: string | number | Date) => value.toString()
const formatDate = (value: string | number | Date) => {
    return DateTime.fromISO(shiftDayValue(value)).toFormat('yyyy / M / d')
}
const formatWeekday = (value: string | number | Date) => {
    return DateTime.fromISO(shiftDayValue(value)).toFormat('ccc', { locale: 'ja' })
}
const isUsed = (value: string | number | Date) => {
    return shiftDayValue(value) < DateTime.now().toISODate()
}
const getPlannedLeaves = async() => {
    loading.value = true

    try {
        const response = await api.post('/get_planned_leaves', {user_id: props.userId, year: year.value})
        paidHolidays.value = response
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    getPlannedLeaves()
})
watch(() => props.userId, () => {
    getPlannedLeaves()
})
</script>

<style scoped>
.planned-leave-panel {
    display: flex;
    flex-direction: column;
    gap: 18px;
    color: var(--primary-color);
}
.planned-leave-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--calendarBorder);
}
.planned-leave-title {
    font-size: 15px;
    line-height: 1.4;
}
.planned-leave-summary {
    margin-top: 3px;
    font-size: 12px;
    opacity: 0.72;
}
.planned-leave-year-control {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    white-space: nowrap;
}
.planned-leave-year-control :deep(.monthPicker) {
    width: 120px;
    height: 34px;
    border: 1px solid var(--formBorder);
    border-radius: 4px;
    background: var(--background-color);
}
.planned-leave-year-control :deep(.monthPicker > div:first-child)::after {
    content: "▼";
    margin-left: 8px;
    font-size: 9px;
    opacity: 0.65;
}
.planned-leave-year-control :deep(.month-grid) {
    right: 0 !important;
}
.planned-leave-list {
    display: grid;
    gap: 8px;
}
.planned-leave-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-height: 48px;
    padding: 10px 12px;
    border: 1px solid var(--calendarBorder);
    border-radius: 4px;
    background: var(--bg3);
}
.planned-leave-date {
    font-size: 14px;
    line-height: 1.3;
}
.planned-leave-weekday {
    margin-top: 2px;
    font-size: 11px;
    opacity: 0.7;
}
.planned-leave-status {
    min-width: 56px;
    padding: 3px 8px;
    border: 1px solid var(--formBorder);
    border-radius: 3px;
    background: var(--background-color);
    font-size: 12px;
    line-height: 1.4;
    text-align: center;
}
.planned-leave-status.is-used {
    background: var(--bg2);
    opacity: 0.76;
}
.planned-leave-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 160px;
    border: 1px dashed var(--calendarBorder);
    border-radius: 4px;
    background: var(--bg3);
    font-size: 14px;
    opacity: 0.78;
}
@media (max-width: 640px) {
    .planned-leave-header {
        align-items: flex-start;
        flex-direction: column;
    }
    .planned-leave-year-control {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
