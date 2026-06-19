<template>
<tr id="bottomTotal" class="w-row work-total-row">
    <td v-if="responsive.mobile" class="work-total-mobile-cell">
        <div class="work-total-mobile-card">
            <div class="work-total-mobile-head">
                <span v-if="dIndex == 0">集計</span>
                <strong>{{ data.user_name }}</strong>
            </div>
            <div class="work-total-mobile-grid">
                <div class="work-total-mobile-metric">
                    <span>労働</span>
                    <strong>{{ workTotalValue || '-' }}</strong>
                </div>
                <div v-if="hasHeader('研修時間')" class="work-total-mobile-metric">
                    <span>研修</span>
                    <strong>{{ trainingTotalTimeFormat || '-' }}</strong>
                </div>
                <div class="work-total-mobile-metric">
                    <span>残業</span>
                    <strong>{{ overtimeTotalValue || '-' }}</strong>
                </div>
                <div v-if="data?.month_weather_average !== null" class="work-total-mobile-metric">
                    <span>コンディション</span>
                    <strong>
                        <WeatherIcon :which="data?.month_weather_average" :size="17"/>
                    </strong>
                </div>
            </div>
            <div v-if="monthlySummaryItems.length" class="work-total-summary work-total-summary-mobile">
                <span
                    v-for="item in monthlySummaryItems"
                    :key="`${item.label}-${item.value}`"
                    class="work-total-chip"
                >
                    <span>{{ item.label }}</span>
                    <strong>{{ item.value }}</strong>
                </span>
            </div>
        </div>
    </td>
    <td v-if="!responsive.mobile" class="work-date-cell work-total-label-cell">
        <div v-if="dIndex == 0">
            <span>集計</span>
        </div>
    </td>
    <td v-if="!responsive.mobile" class="work-member-cell work-total-member-cell">
        <div class="work-total-member-content">
            <span class="work-total-member-name">{{ data.user_name }}</span>
            <span v-if="hasWeatherAverage" class="work-total-member-weather">
                <WeatherIcon :which="data?.month_weather_average" :size="17"/>
            </span>
        </div>
    </td>
    <!-- <td v-if="!responsive.mobile">
        <div v-if="data?.month_weather_average !== null" class="condition-area">
            <div>{{ responsive.mobile ? 'コンディション : ' : '' }}</div>
            <WeatherIcon :which="data?.month_weather_average" :size="17"/>
        </div>
    </td> -->
    <td v-if="!responsive.mobile && hasHeader('予定')"></td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile">{{ workTotalTimeFormat }}</td>
    <td v-if="!responsive.mobile && hasHeader('研修時間')">{{ trainingTotalTimeFormat }}</td>
    <td v-if="!responsive.mobile">
        <p v-if="data.work_type == 1 || showOverTime">
            {{ data?.month_over_time ? overTimeFormat : '' }}
        </p>
        <p v-else></p>
    </td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile && hasHeader('諸手当')"></td>
    <td v-if="!responsive.mobile && hasHeader('インシデント')"></td>
    <td v-if="!responsive.mobile && hasHeader('コメント')"></td>
    <td v-if="!responsive.mobile && hasHeader('経費')">{{ costsTotalValue }}</td>
    <td v-if="!responsive.mobile && hasHeader('実績')" class="work-total-actual-cell">
        <div v-if="actualTotalLines.length" class="work-total-actual-lines">
            <span
                v-for="line in actualTotalLines"
                :key="line"
            >
                {{ line }}
            </span>
        </div>
    </td>
    <td v-if="!responsive.mobile && hasHeader('車両使用')"></td>
    <td v-if="!responsive.mobile && hasHeader('マイカー使用')">{{ mileageTotalValue }}</td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile"></td>
</tr>
</template>

<script setup>
import { useResponsive } from '@/store/responsive';
import { computed } from 'vue';
import WeatherIcon from '../Global/WeatherIcon.vue';

const props = defineProps({
    data: {type: Object, default: null},
    hasHeader: {type: Function},
    dIndex: {type: Number}
})

const responsive = useResponsive()
const hasWeatherAverage = computed(() => props.data?.month_weather_average !== null && props.data?.month_weather_average !== undefined)

const showOverTime = computed(() => {
    const monthAvg = props.data
    if(monthAvg){
        if(monthAvg.month_should_work_time && monthAvg.month_work_time && monthAvg.month_annual_leave != null){
            return monthAvg.month_work_time + monthAvg.month_annual_leave > monthAvg.month_should_work_time
        }else{
            return monthAvg.month_work_time > monthAvg.month_should_work_time
        }
    }
    return false
})

const formatMinutes = (time, mobileLabel = '') => {
    if(time === null || time === undefined || time === '') return ''
    const minutesValue = Number(time)
    if(!minutesValue) return ''

    const hours = Math.floor(minutesValue / 60)
    const minutes = minutesValue % 60
    const label = `${hours}時間${minutes}分`
    return responsive.mobile && mobileLabel ? `${mobileLabel}${label}` : label
}

const workTotalTimeFormat = computed(() => {
    return formatMinutes(props.data?.month_work_time, '労働時間合計：')
})

const stripMobileLabel = (value) => {
    return String(value ?? '').replace(/^.*：\s*/, '')
}

const workTotalValue = computed(() => {
    return stripMobileLabel(workTotalTimeFormat.value)
})

const trainingTotalTimeFormat = computed(() => {
    return formatMinutes(props.data?.month_training_minutes)
})

const overTimeFormat = computed(() => {
    const minutes = props.data.month_over_time
    if (minutes === 0) {
        return responsive.mobile ? '残業時間合計：0時間' : '0時間';
    } else {
        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;
        let formatted = '';
        
        if (hours > 0) {
            formatted += hours + '時間';
        }
        
        if (remainingMinutes > 0) {
            formatted += remainingMinutes + '分';
        }
        
        return responsive.mobile ? '残業時間合計：' + formatted : formatted;
    }
})

const overtimeTotalValue = computed(() => {
    return stripMobileLabel(overTimeFormat.value)
})

const formatNumber = (value) => {
    const number = Number(value)
    return Number.isFinite(number) ? number.toLocaleString() : ''
}

const actualUnitLabel = (result) => {
    if (result?.unit_id === 'COUNT') return '件'
    if (result?.unit_id === 'HOUR') return '時間'
    if (result?.unit_id === 'CUSTOM') return result?.unit_label || '単位'
    return '円'
}

const costsTotalValue = computed(() => {
    const costs = Number(props.data?.month_total_costs ?? 0)
    return costs > 0 ? `${formatNumber(costs)}円` : ''
})

const mileageTotalValue = computed(() => {
    const mileage = Number(props.data?.month_mileage ?? 0)
    return mileage > 0 ? `${formatNumber(mileage)}km` : ''
})

const actualTotalLines = computed(() => {
    return (props.data?.month_total_results ?? [])
        .map((result) => {
            const amount = Number(result?.total_amount ?? 0)
            return amount > 0 ? `${formatNumber(amount)}${actualUnitLabel(result)}` : ''
        })
        .filter(Boolean)
})

const monthlySummaryItems = computed(() => {
    const items = []
    const costs = Number(props.data?.month_total_costs ?? 0)
    const mileage = Number(props.data?.month_mileage ?? 0)
    const incentive = Number(props.data?.month_total_incentive ?? 0)

    if (costs > 0) {
        items.push({ label: '経費', value: `${formatNumber(costs)}円` })
    }
    if (mileage > 0) {
        items.push({ label: 'マイカー', value: `${formatNumber(mileage)}km` })
    }
    if (incentive > 0) {
        items.push({ label: 'インセンティブ', value: `${formatNumber(incentive)}件` })
    }
    ;(props.data?.month_total_results ?? []).forEach((result) => {
        const amount = Number(result?.total_amount ?? 0)
        if (amount > 0) {
            items.push({ label: '実績', value: `${formatNumber(amount)}${actualUnitLabel(result)}` })
        }
    })

    return items
})
</script>

<style scoped>
.work-total-row {
    background-color: #606060;
    color: #fff;
}

.work-total-row td {
    border-bottom: thin solid transparent;
}

.work-total-row .work-date-cell,
.work-total-row .work-member-cell {
    background-color: #606060 !important;
    color: #fff !important;
}

.work-total-member-cell {
    text-align: left;
    padding-left: 14px !important;
    overflow: visible !important;
    white-space: nowrap !important;
}

.work-total-member-content {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-width: max-content;
    width: max-content;
}

.work-total-member-name {
    flex: 0 0 auto;
    white-space: nowrap;
}

.work-total-member-weather {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 17px;
    width: 17px;
    min-width: 17px;
    line-height: 1;
}

.work-total-actual-cell {
    height: auto !important;
    line-height: 1.45;
    white-space: normal !important;
}

.work-total-actual-lines {
    display: flex;
    flex-direction: column;
    gap: 2px;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 3px 0;
}

.work-total-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: center;
    padding: 4px 6px;
}

.work-total-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    max-width: 100%;
    padding: 2px 7px;
    border: 1px solid rgba(255, 255, 255, .28);
    color: rgba(255, 255, 255, .88);
    font-size: 11px;
    line-height: 1.4;
    white-space: nowrap;
}

.work-total-chip strong {
    font-weight: 500;
    color: #fff;
}

@media (max-width: 959px) {
    .work-total-row {
        background: transparent;
        color: var(--primary-color);
        padding-bottom: 0 !important;
    }

    .work-total-row:last-child {
        margin-bottom: 0 !important;
    }

    .work-total-mobile-cell {
        display: block !important;
        max-width: none !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .work-total-mobile-card {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 14px;
        background: #606060;
        color: #fff;
    }

    .work-total-mobile-head {
        align-items: center;
        display: flex;
        justify-content: space-between;
        gap: 12px;
    }

    .work-total-mobile-head span {
        font-size: 12px;
        opacity: 0.75;
    }

    .work-total-mobile-head strong {
        font-size: 14px;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .work-total-mobile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .work-total-mobile-metric {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-height: 48px;
        padding: 8px 10px;
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: 4px;
    }

    .work-total-mobile-metric span {
        font-size: 11px;
        opacity: 0.75;
    }

    .work-total-mobile-metric strong {
        align-items: center;
        display: flex;
        min-height: 18px;
        font-size: 13px;
        font-weight: 500;
    }

    .work-total-summary-mobile {
        justify-content: flex-start;
        padding: 0;
    }
}
</style>
