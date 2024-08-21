<template>
<tr id="bottomTotal" class="w-row" style="background-color: #606060;color:white">
    <td style="border-bottom: thin solid transparent;">
        <div v-if="dIndex == 0">
            <span>集計</span>
            <div class="cursor-pointer" @click="emit('csvGenerate')" v-if="data.access_csv">CSV</div>
        </div>
    </td>
    <td>{{ data.user_name }}</td>
    <td v-if="!responsive.mobile && hasHeader('予定')"></td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile"></td>
    <td>{{ workTotalTimeFormat }}</td>
    <td>
        <p v-if="data.work_type == 1 || showOverTime">
            {{ data?.month_over_time ? overTimeFormat : '' }}
        </p>
        <p v-else></p>
    </td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile"></td>
    <td>{{ data?.month_achievement_average }}</td>
    <td>
        <div v-if="data?.month_weather_average !== null" class="conidtion-area">
            <div>{{ responsive.mobile ? 'コンディション : ' : '' }}</div>
            <WeatherIcon :which="data?.month_weather_average" :size="17"/>
        </div>
    </td>
    <td v-if="!responsive.mobile"></td>
    <td v-if="!responsive.mobile && hasHeader('経費')">{{ data?.mont_total_costs ? `${data?.mont_total_costs}円` : ''}}</td>
    <td v-if="!responsive.mobile && hasHeader('インセンティブ')">{{ data?.mont_total_incentive ? `${data?.mont_total_incentive}件` : ''}}</td>
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
const emit = defineEmits(['csvGenerate'])
const responsive = useResponsive()
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
const workTotalTimeFormat = computed(() => {
    const time = props.data.month_work_time
    if(time){
        const hours = Math.floor(time / 60);
        const minutes = time % 60;
        
        return responsive.mobile ? `労働時間合計： ${hours}時間${minutes}分` : `${hours}時間${minutes}分`;
    }
    return ''
})

const overTimeFormat = computed(() => {
    const minutes = props.data.month_over_time
    if (minutes === 0) {
        return responsive.mobile ? '残業時間合計： 0時間' : '0時間';
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

const hasCondition = computed(() => {
    const index = props.data?.month_weather_average
    const mobileTitle = responsive.mobile ? 'コンディション : ' : ''
    if(index != null){
        return `<div class="condition-area"><div>${mobileTitle}</div><img class="condition-img" src="images/icon_${index}.svg" width="17" height="17"/></div>`
    }
    return ''
    
})
</script>