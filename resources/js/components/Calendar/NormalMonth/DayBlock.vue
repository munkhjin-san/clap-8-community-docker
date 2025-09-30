<template>
    <div @click.stop="createAtTime" class="month-drop-area cal-m-row" :class="{activeMonth: thisMonth}" @mouseenter="enter" @mouseleave="leave">
        <Transition name="modalFade">
            <div v-if="dragActive && draggingCalendar" @mouseup="gotMove" class="month-drop-popup"></div>
        </Transition>
        <div :class="{'cal-todayTitle' : thisDay}" :id="'day_val_m_' + day.day_full" class="cal-m-day-head-section">
            <div @click="emit('jumpToDate', day.day_full)" :title="day.day_full" :class="['cal-m-day-title', {'special-day': specialDay, 'isSaturday' : isSaturday}]">
                <p>{{ day.day_short }}</p>
                <p class="pc" style="margin-left: 5px;white-space: nowrap;overflow: hidden;" v-if="day.day_holiday">{{ day.day_holiday }}</p>
                <p class="pc" style="margin-left: 5px;white-space: nowrap;overflow: hidden;" v-else-if="anniversaryDay">GLOWD周年記念</p>
            </div>
            
            <div @click.stop="emit('addRecord', 'day', day.day_full)" class="m-record-add pc">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 32 32" fill="#9b9b9b">
                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                </svg>
            </div>
        </div>   
        <transition-group name="modalFade" tag="div" style="display: flex;flex-direction: column;gap: 10px;padding: 0 0 10px 0;">
            <CardWrap
                v-for="record in records"
                :record="record"
                :key="record.id"
                @fromMonth="val => emit('fromMonth', val)"                
            />
        </transition-group>    
        <transition-group name="modalFade" tag="div" style="display: flex;flex-direction: column;gap: 10px;padding: 0 0 10px 0;">
            <GoogleEventWrap
                v-for="event in googleDayEvents"
                :googleEvent="event"
                :key="event.id"      
                :day="day.day_full"     
            />
        </transition-group>   
</div>
</template>

<script setup lang="ts">
import { DateTime } from 'luxon'
import CardWrap from './CardWrap.vue'
import { ref, computed, inject, } from 'vue'
import { useCalendar } from '@/composables/calendar'
import GoogleEventWrap from './GoogleEventWrap.vue'
import { CalendarRecord, GoogleEventItem, NormalMonthDay } from '@/interface/calendarInterface'
    const props = defineProps<{
        day: NormalMonthDay
        records: CalendarRecord[];
        googleDayEvents: GoogleEventItem[];
        selectedYear: number;
        selectedMonth: number;
    }>()
    const emit = defineEmits(['fromMonth', 'addRecord', 'jumpToDate', 'create'])

    const dragActive = ref(false)     
    const {draggingCalendar, setDraggingCalendar} = useCalendar()
    const anniversaryDay = computed(() => {
        return DateTime.fromISO(props.day.day_full).month == 8 && DateTime.fromISO(props.day.day_full).day == 2
    })
    const isSaturday = computed (() => {
        return DateTime.fromISO(props.day.day_full).weekday == 6
    })
    const specialDay = computed(() => {
        return DateTime.fromISO(props.day.day_full).weekday == 7 || props.day.day_holiday
    })
    const thisMonth = computed(() => {
        const tDay = DateTime.fromISO(props.day.day_full)
        const thisMonth =  DateTime.fromObject({year: props.selectedYear, month: props.selectedMonth})
        return tDay.hasSame(thisMonth, 'month')
    })
    const thisDay = computed(() => {
        const tDay = DateTime.fromISO(props.day.day_full).toISODate() 
        const thisMonth =  DateTime.now().toISODate()
        return tDay === thisMonth 
    })
  
    const dropFinish = inject<Function>('dropFinish') as Function

    const gotMove = () => {
        if(draggingCalendar && draggingCalendar.value){
            const record = draggingCalendar.value
            setDraggingCalendar(null)
            const record_date = DateTime.fromSQL(record.date_start)
            const date = props.day.day_full
            const merge = DateTime.fromISO(date).set({hour: record_date.hour, minute: record_date.minute, second: 0}).toFormat('yyyy-MM-dd HH:mm:ss');
            dragActive.value = false
            if(dropFinish){
                dropFinish(record, merge)
            }
        }       
        
    }

    const enter = () => {
        if(draggingCalendar.value){
            if(!DateTime.fromISO(props.day.day_full).hasSame(DateTime.fromISO(draggingCalendar.value.date_start), 'day')){
                dragActive.value = true
            }                    
        }
    }

    const leave = () => {
        dragActive.value = false
    }

    const createAtTime = (event) => {                
        const date = props.day.day_full
        const time = DateTime.now().plus({hours: 1}).startOf('hour').toFormat('HH:mm:ss')
        const merge = `${date} ${time}`
        const d = {
            x: event.x,
            y: event.y,
            time: merge,
            stamp: DateTime.now()
        }
        emit('create', d)                
    }
     
    
</script>
<style>  


</style>
