<template>
    <div class="mobile-finance-compare-table" :class="{ 'mobile-finance-compare-table--compact': compact }">
        <div class="mobile-finance-compare-table__head"></div>
        <div class="mobile-finance-compare-table__head">{{ leftLabel }}<template v-if="leftSublabel"><br>{{ leftSublabel }}</template></div>
        <div class="mobile-finance-compare-table__head">{{ rightLabel }}<template v-if="rightSublabel"><br>{{ rightSublabel }}</template></div>
        <div class="mobile-finance-compare-table__head">{{ deltaHeading }}</div>
        <template v-for="metric in metricDisplayItems" :key="metric.key">
            <div class="mobile-finance-compare-table__label">{{ metric.label }}</div>
            <div class="mobile-finance-compare-table__value relative">
                <span v-if="showMark(leftEntry, leftScenario)" class="inline text-[9px] absolute right-0">＊</span>
                {{ formatMobileMetric(leftEntry, leftScenario, metric.key, scale) }}
            </div>
            <div class="mobile-finance-compare-table__value relative">
                <span v-if="showMark(rightEntry, rightScenario)" class="inline text-[9px] absolute right-0">＊</span>
                {{ formatMobileMetric(rightEntry, rightScenario, metric.key, scale) }}
            </div>
            <div class="mobile-finance-compare-table__delta" :class="deltaClass(metric.key)">
                {{ deltaDisplay(metric.key) }}
            </div>
        </template>
    </div>
</template>
<script setup lang="ts">
import { computed } from 'vue';
import {
    comparisonDeltaClass,
    comparisonDeltaDisplay,
    comparisonGapClass,
    comparisonGapDisplay,
    formatMobileMetric,
    metricDisplayItems,
} from './financeMetrics';
import type { MetricDisplayKey, ScenarioKey, UnitData } from './financeMetrics';

const props = withDefaults(defineProps<{
    mode: 'yearDelta' | 'gap'
    leftLabel: string
    rightLabel: string
    leftSublabel?: string
    rightSublabel?: string
    leftEntry?: Partial<UnitData> | null
    rightEntry?: Partial<UnitData> | null
    leftScenario: ScenarioKey
    rightScenario: ScenarioKey
    scale: number
    compact?: boolean
}>(), {
    leftSublabel: '',
    rightSublabel: '',
    leftEntry: null,
    rightEntry: null,
    compact: false,
})

const deltaHeading = computed(() => (props.mode === 'gap' ? '差分' : ''))

const showMark = (entry: Partial<UnitData> | null | undefined, scenario: ScenarioKey) =>
    Boolean(entry?.is_forecast) && (props.mode === 'yearDelta' || scenario === 'settlement')

const deltaDisplay = (key: MetricDisplayKey) =>
    props.mode === 'yearDelta'
        ? comparisonDeltaDisplay(props.leftEntry, props.rightEntry, props.leftScenario, key)
        : comparisonGapDisplay(props.leftEntry, props.rightEntry, props.leftScenario, props.rightScenario, key, props.scale)

const deltaClass = (key: MetricDisplayKey) =>
    props.mode === 'yearDelta'
        ? comparisonDeltaClass(props.leftEntry, props.rightEntry, props.leftScenario, key)
        : comparisonGapClass(props.leftEntry, props.rightEntry, props.leftScenario, props.rightScenario, key)
</script>
<style scoped lang="scss">
.mobile-finance-compare-table {
    display: grid;
    grid-template-columns: minmax(72px, 0.9fr) repeat(3, minmax(0, 1fr));
    border-top: 1px solid var(--calendarBorder);
}
.mobile-finance-compare-table__head,
.mobile-finance-compare-table__label,
.mobile-finance-compare-table__value,
.mobile-finance-compare-table__delta {
    padding: 16px 12px;
    border-top: 1px solid var(--calendarBorder);
}
.mobile-finance-compare-table__head {
    border-top: 0;
    font-size: 12px;
    text-align: right;
    line-height: 1.45;
    color: var(--primary-color);
    opacity: 0.78;
}
.mobile-finance-compare-table__label {
    font-size: 13px;
    font-weight: 600;
}
.mobile-finance-compare-table__value {
    font-size: 13px;
    text-align: right;
    font-weight: 700;
}
.mobile-finance-compare-table__delta {
    font-size: 13px;
    text-align: right;
    font-weight: 700;
}
.mobile-finance-compare-table__delta--positive {
    color: green;
}
.mobile-finance-compare-table__delta--negative {
    color: tomato;
}
.mobile-finance-compare-table__delta--neutral {
    color: var(--primary-color);
    opacity: 0.7;
}
.mobile-finance-compare-table--compact {
    grid-template-columns: minmax(56px, 0.8fr) repeat(3, minmax(0, 1fr));
    border-top: 0;
}
.mobile-finance-compare-table--compact .mobile-finance-compare-table__head,
.mobile-finance-compare-table--compact .mobile-finance-compare-table__label,
.mobile-finance-compare-table--compact .mobile-finance-compare-table__value,
.mobile-finance-compare-table--compact .mobile-finance-compare-table__delta {
    padding: 10px 10px;
    font-size: 11px;
}
.mobile-finance-compare-table--compact .mobile-finance-compare-table__value,
.mobile-finance-compare-table--compact .mobile-finance-compare-table__delta {
    font-weight: 600;
}
@media screen and (max-width: 959px) {
    .mobile-finance-compare-table {
        grid-template-columns: minmax(64px, 0.85fr) repeat(3, minmax(0, 1fr));
    }
    .mobile-finance-compare-table__head,
    .mobile-finance-compare-table__label,
    .mobile-finance-compare-table__value,
    .mobile-finance-compare-table__delta {
        padding: 14px 10px;
        align-self: center;
    }
}
</style>
