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
                    />
                </div>              
            </div>            
        </div>
        <div v-if="fullDayGoogleEvents.length" class="hour-full-day-wrap" :style="{ left: '0', top: `${hourFulldayRecords.length ? hourFulldayRecords.length * 25 + (hourFulldayRecords.length * 10 - 10) + 20 : 10}px`, fontSize: '12px' }">
            <div style="display: flex;flex-direction: column;gap:10px">
                <div style="position: relative;cursor: pointer;width: fit-content;" v-for="record in fullDayGoogleEvents">
                    <AllDayGoogleEvent                        
                        :key="record.id"
                        :record="record"
                        :day="day"
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
        :hourGoogleEvents="hourGoogleEvents(hourIndex)"
        :fullDayIndex="hourFulldayRecords.length + fullDayGoogleEvents.length"
        @create="val => emit('create', val)"
        @setDayIndex="val => emit('setDayIndex', val)"
    />
</div>
</template>
<script setup lang="ts">
import HourBlock from './HourBlock.vue';
import AllDayRecord from '../AllDayRecord.vue';
import { computed, Ref } from 'vue';
import { DateTime } from 'luxon';
import { NormalHourDay, CalendarRecord, GoogleEventItem } from '@/interface/calendarInterface';
import AllDayGoogleEvent from '../AllDayGoogleEvent.vue';
    const props = defineProps<{
        day: NormalHourDay;
        hoursOfDay:string[];
        records: CalendarRecord[]
        orderCreator: Function
        activeDay: DateTime | null
        googleEventOrderCreator: Function
        googleEvents: GoogleEventItem[]
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
    const thisGoogleEvents = computed(() => {
        return props.googleEvents.filter((ob) => {
            const startDate = DateTime.fromISO(ob.start_date);
            const endDate = ob.end_date ? DateTime.fromISO(ob.end_date) : startDate;
            const dayDate = DateTime.fromISO(props.day.full);
            return (dayDate >= startDate && dayDate <= endDate);
        });
    });
    
    const fullDayGoogleEvents = computed(() => {
        return thisGoogleEvents.value.filter((ob) => {

            if(ob.all_day || ob.end_date !== ob.start_date ) return true;
            if(ob.start_time && ob.end_time){
                const t1 = ob.start_time;
                const t2 = ob.end_time;
                const base = DateTime.fromISO("2025-01-01T00:00:00");
                const d1 = base.set({
                    hour: parseInt(t1.split(":")[0], 10),
                    minute: parseInt(t1.split(":")[1], 10)
                });

                let d2 = base.set({
                    hour: parseInt(t2.split(":")[0], 10),
                    minute: parseInt(t2.split(":")[1], 10)
                });
                if (d2 < d1) {
                    d2 = d2.plus({ days: 1 });
                }
                const diff = d2.diff(d1, "hours").hours;
                return diff > 23;
            }
            return false;
        });
    })
    const layer = computed(() => {
        if(dayGoogleEvents.value.length){
            const googleEventsNum = dayGoogleEvents.value.map(ob => ob.order)
            return Math.max(...googleEventsNum) + 1
        }
        const num = dayRecords.value.map((ob: CalendarRecord) => ob.order)
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
    const dayGoogleEvents: Ref<GoogleEventItem[]> = computed(() => {
        const highestOrder = dayRecords.value.length ? Math.max(...dayRecords.value.map((ob: CalendarRecord) => ob.order)) + 1 : 0;
        const events = thisGoogleEvents.value.filter((ob: GoogleEventItem) => fullDayGoogleEvents.value.findIndex(fob => fob.id == ob.id) === -1)
        const sortedItems = events.slice().sort((a, b) => {
            const dateA = DateTime.fromFormat(`${a.start_date} ${a.start_time}`, 'yyyy-MM-dd HH:mm').toMillis();
            const dateB = DateTime.fromFormat(`${b.start_date} ${b.start_time}`, 'yyyy-MM-dd HH:mm').toMillis();
            return dateA - dateB;
        });
        const ordered = props.googleEventOrderCreator(highestOrder, sortedItems, props.day.full)
        return ordered
    })
    const listView = (day: NormalHourDay) => {
        emit('setListView', day.full)
    }
    const computedDay = (day: NormalHourDay) => {
        const top = DateTime.fromISO(day.full).toFormat('d')
        const bottom =  DateTime.fromISO(day.full).toFormat('ccc')
        return `<span>${top}</span><span>${bottom}</span>`
    }
    
    const hourRecords = (hour:number) => {
        if(dayRecords.value && dayRecords.value.length){               
            return dayRecords.value.filter((ob: CalendarRecord) => DateTime.fromSQL(ob.date_start).hour == hour && Math.abs(DateTime.fromSQL(ob.date_start).diff(DateTime.fromSQL(ob.date_end), 'hours').hours) < 23)
        }
        return []
    }  
    const hourGoogleEvents = (hour:number) => {
        const items = dayGoogleEvents.value.filter((ob: GoogleEventItem) => ob.start_time && DateTime.fromFormat(ob.start_time, 'HH:mm').hour == hour)
        return items
    }
</script>