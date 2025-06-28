<template>
    <td 
        class="!h-[20px] t-cell relative" 
        :class="[`time-index-${hour.split(':')[1]}`, {'unavailable-slot': isUnavailable }]" 
    >
        <Transition name="modalFade">
            <div 
                class="absolute w-full left-0 top-0 z-[5]" 
                v-if="highlighted == `${date.toString()} ${hour}`"
                :style="{
                    height: `${(duration.hour * 60 + duration.minute) / 15 * 20}px`,
                    maxHeight: stepUntil + 'px',
                    
                }"
            >
                <div 
                    class="h-full w-full opacity-80 text-[11px] leading-normal text-[white] flex items-center justify-center flex-col"
                    :style="{
                        backgroundColor: includeUnavailableSlot ? 'tomato' : 'var(--link-color)',
                    }"      
                    :id="`highlight-${date.toString()}-${hour}`"   
                              
                >
                    <p>{{ DateTime.fromFormat(hour, 'HH:mm').toFormat('H:mm') }} ~ 
                    {{ DateTime.fromFormat(hour, 'HH:mm').plus({ hours: duration.hour, minutes: duration.minute }).toFormat('H:mm') }}</p>
                    <p v-if="includeUnavailableSlot">(予約不可)</p>
                    
                </div>
        
            </div>
        </Transition>
    </td>
</template>
<script setup lang="ts">
import { DailySchedule } from '@/interface/calendarInterface';
import { DateTime } from 'luxon';
import { computed } from 'vue';

const props = defineProps<{
    dayData: DailySchedule;
    date: string;
    hour: string;
    duration: { hour: number; minute: number };
    highlighted: string | null;
    stepUntil: number
}>();


const hourData = computed(() => props.dayData[props.hour] )

const isUnavailable = computed(() => {
    if (!hourData.value) {
        return false
    }
    const isAvailable = Object.values(hourData.value).some((value) => value === false)
    return isAvailable
})

const includeUnavailableSlot = computed(() => {
    const startPoint = DateTime.fromISO(props.date).set({
        hour: parseInt(props.hour.split(':')[0]),
        minute: parseInt(props.hour.split(':')[1])
    })
    
    const endPoint = startPoint.plus({ hours: props.duration.hour, minutes: props.duration.minute }).minus({ minutes: 15 })
    
    if(startPoint.isValid && endPoint.isValid){
        let cursor = startPoint
        while (cursor <= endPoint) {
            const hourKey = cursor.toFormat('HH:mm')
            const data = props.dayData[hourKey]
            if (data && Object.values(data).some((value) => value === false)) {
                return true
            }
            cursor = cursor.plus({ minutes: 15 })
        }
    }
})



</script>