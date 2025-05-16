<template>
<div :id="`day_val_${day.full}`" class="day-tc" :style="{ minHeight: `${layer * 70 + 10 + (hourFulldayRecords.length * 35)}px`, zIndex: indexOrder }">
    <div :ref="`cal_${day.full}`" :class="['day-label', {isPastDay : isPastDay}, {isToday : isToday}]">
        <div @click="listView(day)" v-html="computedDay(day)" style="display: flex;flex-direction: column;gap: 10px;" :class="[{'special-day': specialDay, 'isSaturday' : isSaturday}]"></div>
    </div>
    <div style="position: sticky;left: 30px;z-index: 2;">
        <div v-if="hourFulldayRecords.length" class="hour-full-day-wrap" style="left:0;top: 10px;font-size: 12px;">
            <div style="display: flex;flex-direction: column;gap:10px">
                <div style="position: relative;cursor: pointer;width: fit-content;" v-for="record in hourFulldayRecords">
                    <AllDayRecord                        
                        :key="record.id"
                        :record="record"
                        :day="day"
                        @setDayIndex="val => emit('setDayIndex', val)"
                    />
                </div>              
            </div>            
        </div>
    </div>
    
    <HourBlock
        v-for="(hour, hourIndex) in hoursOfDay" 
        :hour="hour"
        :day="day"
        :key="hourIndex"
        :hourRecords="hourRecords(hourIndex)"
        :fullDayIndex="hourFulldayRecords.length"
        @create="val => emit('create', val)"
        @setDayIndex="val => emit('setDayIndex', val)"
    />
</div>
</template>
<script setup lang="ts">
import HourBlock from './HourBlock.vue';
import AllDayRecord from '../AllDayRecord.vue';
import { computed } from 'vue';
import { DateTime } from 'luxon';
import { NormalHourDay, CalendarRecord } from '@/interface/calendarInterface';
    const props = defineProps<{
        day: NormalHourDay;
        hoursOfDay:string[];
        records: CalendarRecord[]
        orderCreator: Function
        activeDay: DateTime | null
    }>()
    const emit = defineEmits(['releaseScroll', 'load', 'create', 'setListView', 'setDayIndex'])

    const indexOrder = computed(() => {
        if(props.activeDay){
            return props.activeDay.hasSame(DateTime.fromSQL(props.day.full), 'day') ? 12 : 'unset'
        }
        return 'unset'
    })
    const isSaturday = computed(() => {
        return DateTime.fromISO(props.day.full).weekday === 6
    })
    const specialDay = computed(() => {
        return DateTime.fromISO(props.day.full).weekday === 7 || props.day.day_holiday
    })
    const hourFulldayRecords = computed(() => {
        if (props.records && props.records.length) {
            return props.records.filter((ob) => {
                
                const startDateTime = DateTime.fromSQL(ob.date_start);
                const endDateTime = DateTime.fromSQL(ob.date_end);
                const dayToCompare = DateTime.fromISO(props.day.full).startOf('day');
                const isSameDay = startDateTime.equals(dayToCompare);
                const durationInHours = Math.abs(startDateTime.diff(endDateTime, 'hours').hours);
                return isSameDay && durationInHours >= 23;
            });
        }
        return [];
    });
    const layer = computed(() => {
        const num = dayRecords.value.map(ob => ob.order)
        const max = num.length ? Math.max(...num) + 1 : 0;
        return max
        
    })
    const isPastDay = computed(() => {
        return DateTime.fromISO(props.day.full).diff(DateTime.now(), 'day').as('days') < 0
    })
    const isToday = computed(() => {
        const givenDate = DateTime.fromISO(props.day.full).startOf('day');
        const today = DateTime.now().startOf('day');
        return givenDate.equals(today);
    })
    const dayRecords = computed(() => {
        if(props.records && props.records.length){               
            const list = props.records.filter(ob => {
                const startDateTime = DateTime.fromSQL(ob.date_start);
                const endDateTime = DateTime.fromSQL(ob.date_end);
                const dayToCompare = DateTime.fromISO(props.day.full).startOf('day');
                const isSameDay = startDateTime.hasSame(dayToCompare, 'day');
                const durationInHours = Math.abs(startDateTime.diff(endDateTime, 'hours').hours);
                return isSameDay && durationInHours < 23
            })
            const sortedList = list.slice().sort((a, b) => {
                return DateTime.fromSQL(a.date_start).toMillis() - DateTime.fromSQL(b.date_start).toMillis() ||
                DateTime.fromSQL(a.updated_at).toMillis() - DateTime.fromSQL(b.updated_at).toMillis();
            });               

            const ordered = props.orderCreator(0, sortedList, props.day.full)
            return ordered
        }
        return []
    })

    const listView = (day) => {
        emit('setListView', day.full)
    }
    const computedDay = (day:NormalHourDay) => {
        const top = DateTime.fromISO(day.full).toFormat('d')
        const bottom =  DateTime.fromISO(day.full).toFormat('ccc')
        return `<span>${top}</span><span>${bottom}</span>`
    }
    
    const hourRecords = (hour:number) => {
        if(dayRecords.value && dayRecords.value.length){               
            return dayRecords.value.filter(ob => DateTime.fromSQL(ob.date_start).hour == hour && Math.abs(DateTime.fromSQL(ob.date_start).diff(DateTime.fromSQL(ob.date_end), 'hours').hours) < 23)
        }
        return []
    }  
</script>