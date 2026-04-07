<template>
<div @mousedown="setBeforeState" @touchstart="setBeforeState" @click.self.stop="createAtTime" class="hour-slot" @mouseenter="enter" @mouseleave="leave">
    <CardWrap 
        v-for="record in hourRecords"
        :record="record"
        :key="record.id"
        :fullDayIndex="fullDayIndex"
        @setDayIndex="val => emit('setDayIndex', val)"
        @setParentDroppable="dragActive = true"
    />
    <GoogleEventWrap 
        v-for="(record, index) in hourGoogleEvents"
        :record="record"
        :order="index"
        :fullDayIndex="fullDayIndex"
    />
    <div v-if="dragActive && draggingCalendar" style="position: absolute;left: 0;top:0;z-index: 9;height: 100%;width: 100%;display: flex;">
        <div @mouseup="gotMove(val)" v-for="val in hours" class="min-separete">
            <div class="min-popup">{{ fullDate(val) }}</div>
        </div>
    </div>
</div>
        
   
</template>
<script setup lang="ts">
import { CalendarRecord, GoogleEventItem, NormalHourDay } from '@/interface/calendarInterface';
import CardWrap from './CardWrap.vue';
import { computed, inject, Ref, ref } from 'vue';
import { DateTime } from 'luxon';
import { useCalendar } from '@/composables/calendar';
import GoogleEventWrap from './GoogleEventWrap.vue';
    const dragActive = ref(false)
    const beforeState = ref(0)
    const {draggingCalendar, setDraggingCalendar} = useCalendar()
    const props = defineProps<{
        hourRecords: CalendarRecord[];
        hour: string;
        day: NormalHourDay;
        fullDayIndex: number;
        hourGoogleEvents: GoogleEventItem[];
    }>()
    const emit = defineEmits(['create', 'setDayIndex'])

    const hours = computed(() => {
        return [
            { val: '00' },
            { val: '30' },
        ]
    })   

    const setBeforeState = (event: MouseEvent | TouchEvent) => {
        beforeState.value = 'clientX' in event ? event.clientX : event.touches[0].clientX
    }

    const createAtTime = (event: MouseEvent) => {
        if(Math.abs(event.x - beforeState.value) > 15) {
            return
        }
        const targetElement = event.currentTarget as HTMLElement | null
        if (!targetElement) return
        const elementWidth = targetElement.offsetWidth;
        const clickX = event.clientX - targetElement.getBoundingClientRect().left       
        const min = (clickX < elementWidth / 2) ? 0 : 30
        const date = props.day.full
        const [hour] = props.hour?.split(':') ?? ['0', '0']
        const merge = DateTime.fromISO(date)
            .set({ 
                hour: Number(hour),
                minute: min,
                second: 0 
            }).toFormat('yyyy-MM-dd HH:mm:ss');
        const d = {
            x: event.x,
            y: event.y,
            time: merge,
            stamp: DateTime.now()
        }
        emit('create', d)        
    }

    const enter = () => {
        if(draggingCalendar.value){
            dragActive.value = true
        }
        
    }
    const leave = () => {
        dragActive.value = false
    }

    const dropFinish = inject<Function>('dropFinish') as Function

    const gotMove = (val:{val:string}) => {
        if(draggingCalendar && draggingCalendar.value){
            const record = draggingCalendar.value
            setDraggingCalendar(null)
            const date = props.day.full
            const time = props.hour.split(":");
            const min = val.val
            const merge = DateTime.fromISO(date)
            .set({ 
                hour: Number(time[0]),
                minute: Number(min),
                second: 0 
            })
            .toISO();
            dragActive.value = false
            if(dropFinish){
                dropFinish(record, merge)
            }
        }           
    }
    const fullDate = (val:{val:string}) => {
        const date = props.day.full
        const time = props.hour.split(":");
        const min = val.val
        const merge = DateTime.fromISO(date)
            .set({ 
                hour: Number(time[0]),
                minute: Number(min),
                second: 0 
            })
            .toFormat('HH:mm');
        return merge
    }
</script>