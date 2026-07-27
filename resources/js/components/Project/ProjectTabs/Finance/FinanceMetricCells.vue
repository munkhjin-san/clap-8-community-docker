<template>
    <td>
        <div class="flex items-center gap-[5px]">
            <div class="inner-col">{{ display('sales') }}<span v-if="markFor('sales')" class="text-[11px] inline">＊</span></div>
            <DeltaNumbers v-if="showDelta" type="sales" :planned="plannedFor('sales')" :actual="numericFor('sales')" />
        </div>
    </td>
    <td>
        <div class="flex items-center gap-[5px]">
            <div class="inner-col">{{ display('expense') }}<span v-if="markFor('expense')" class="text-[11px] inline">＊</span></div>
            <DeltaNumbers v-if="showDelta" type="expense" :planned="plannedFor('expense')" :actual="numericFor('expense')" />
        </div>
    </td>
    <td>
        <div class="flex items-center gap-[5px]">
            <div class="inner-col">{{ display('profit') }}<span v-if="markFor('profit')" class="text-[11px] inline">＊</span></div>
            <DeltaNumbers v-if="showDelta" type="profit" :planned="plannedFor('profit')" :actual="numericFor('profit')" />
        </div>
    </td>
    <td :data-cell="rightBorder ? 'right-border' : undefined">
        <div class="flex items-center gap-[5px]">
            <div class="inner-col">{{ rate.display }}<span v-if="markForRate" class="text-[11px] inline">＊</span></div>
            <DeltaNumbers v-if="showDelta" type="profit_rate" :planned="baseRate.value" :actual="rate.value" />
        </div>
    </td>
</template>
<script setup lang="ts">
import { computed } from 'vue';
import DeltaNumbers from './DeltaNumbers.vue';
import { amountOfMoneyParser } from '@/utils/tools';
import { metricNumericValue, percentizer } from './financeMetrics';
import type { Key, ScenarioKey, UnitData } from './financeMetrics';

const props = withDefaults(defineProps<{
    entry?: Partial<UnitData> | null
    scenario: ScenarioKey
    baseEntry?: Partial<UnitData> | null
    showDelta?: boolean
    forecastMark?: 'always' | 'positive' | 'none'
    rightBorder?: boolean
}>(), {
    entry: null,
    baseEntry: null,
    showDelta: false,
    forecastMark: 'none',
    rightBorder: false,
})

const numericFor = (key: Key) => metricNumericValue(props.entry, props.scenario, key)
const display = (key: Key) => amountOfMoneyParser(numericFor(key))
const plannedFor = (key: Key) => metricNumericValue(props.baseEntry, 'yearly_plan', key)

const rate = computed(() => percentizer(props.entry))
const baseRate = computed(() => percentizer(props.baseEntry))

const markFor = (key: Key) => {
    if (props.forecastMark === 'none' || !props.entry?.is_forecast) return false
    if (props.forecastMark === 'always') return true
    const value = numericFor(key)
    if (Number.isNaN(value)) return false
    return key === 'profit' ? value !== 0 : value > 0
}
const markForRate = computed(() => {
    if (props.forecastMark === 'none' || !props.entry?.is_forecast) return false
    if (props.forecastMark === 'always') return true
    return rate.value.value !== 0
})
</script>
