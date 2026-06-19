<template>
    <section class="planned-leave-panel">
        <div class="planned-leave-header">
            <div>
                <p class="planned-leave-summary">{{ year }}年度 計画有給 {{ plannedLeaveCount }}日</p>
            </div>
            <div class="planned-leave-year-control">
                <YearPicker :selected-year="year" right="auto" @set-date="setDate"/>
            </div>
        </div>

        <div v-if="loading" class="planned-leave-state">
            読み込み中
        </div>

        <div v-else-if="paidHolidays.length" class="planned-leave-list">
            <PlannedLeaveItem 
                v-for="(item, index) in paidHolidays" 
                :workTemp="workTemp" 
                :pmApprovalNeeded="pmApprovalNeeded" 
                :selectableProjects="selectableProjects" 
                :key="index" 
                :item="item"
                @updated="getPlannedLeaves"
            />
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
import { Shift, tempData } from '@/interface/workInterface';
import { useApi } from '@/composables/api';
import PlannedLeaveItem from './PlannedLeave/PlannedLeaveItem.vue';

const year = ref(DateTime.now().year)
const props = defineProps<{
    userId: number | string
}>()

const paidHolidays = ref<Shift[]>([])
const workTemp = ref<tempData | null>(null)
const pmApprovalNeeded = ref(false)
const selectableProjects = ref<{id: number, name: string}[]>([])
const loading = ref(false)
const api = useApi()
const plannedLeaveCount = computed(() => paidHolidays.value.length)

const setDate = (val: {year: number}) => {
    year.value = val.year
    getPlannedLeaves()
}

const getPlannedLeaves = async() => {
    loading.value = true

    try {
        const response = await api.post('/get_planned_leaves', {user_id: props.userId, year: year.value})
        paidHolidays.value = response.paidholidays
        workTemp.value = response.workTemp
        pmApprovalNeeded.value = response.pmApprovalNeeded ?? false
        selectableProjects.value = response.selectableProjects ?? []
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
    height: 34px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    padding: 0 12px;
}

.planned-leave-year-control :deep(.month-grid) {
    right: 0 !important;
}
.planned-leave-list {
    display: grid;
    gap: 15px;
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
