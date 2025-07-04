<template>
<tr>
    <td rowspan="4" class="!border-b-0 relative text-[12px] no-hover !w-[45px] !max-w-[45px] min-w-[40px]" v-if="hour.split(':')[1] == '00'">
        <div class="absolute top-0 right-[10px]">{{ DateTime.fromFormat(hour, 'HH:mm').toFormat('H時') }}</div>
    </td>
    <DayRowBlock 
        v-for="[date, data] in scheduleEntries" 
        :date="date" 
        :dayData="data" 
        :hour="hour" 
        :duration="duration"
        :highlighted="highlighted"
        :stepUntil="stepUntil"
        @click="emit('setHighlight', data, hour, date)"
    />
</tr>
</template>
<script setup lang="ts">
import { DailySchedule, DateSchedule } from '@/interface/calendarInterface';
import { DateTime } from 'luxon';
import DayRowBlock from './DayRowBlock.vue';
import { computed } from 'vue';

const props = defineProps<{
    blockData: DateSchedule;
    duration: { hour: number; minute: number };
    hour: string;
    highlighted: string[];
}>();

const emit = defineEmits<{
    setHighlight:[dayData: DailySchedule, hour: string, date: string]
}>();

const scheduleEntries = computed((): [string, DailySchedule][] =>  Object.entries(props.blockData));

const stepUntil = computed(() => {
    const startPoint = DateTime.fromFormat(props.hour, 'HH:mm')
    const endPoint = DateTime.fromFormat('21:00', 'HH:mm')
    if (!startPoint.isValid || !endPoint.isValid) {
        return 0
    }
    const diffInMinutes = endPoint.diff(startPoint, "minutes").minutes;
    return Math.floor(diffInMinutes / 15) * 20;
})
</script>