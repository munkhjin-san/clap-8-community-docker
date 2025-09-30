<template>
    <div class="w-day-item" :style="{position: 'relative', minHeight: `${layer * 70 + 30 + (fullDayIndex * 30)}px`}" @click.self.stop="createAtTime" @mouseenter="enter" @mouseleave="leave" @mousedown="setBeforeState" @touchstart="setBeforeState">
        <CardWrap
            v-for="item in data.records" 
            :record="item"
            :user="data.user"
            :fullDayIndex="fullDayIndex"
        />
        <GoogleEventWrap
            v-for="item in data.googleEvents" 
            :record="item"
            :user="data.user"
            :fullDayIndex="fullDayIndex" 
            :offset="layer"
        />
        <div v-if="dragActive && draggingCalendar" style="position: absolute;left: 0;top:0;z-index: 9;height: 100%;width: 100%;display: flex;">
            <div @mouseup="gotMove(val)" v-for="val in hours" class="min-separete">
                <div class="min-popup">{{ fullDate(val) }}</div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import CardWrap from './CardWrap.vue';
import { computed, inject, Ref, ref } from 'vue';
import { DateTime } from 'luxon';
import { CalendarRecord, MemberHourDay } from '@/interface/calendarInterface';
import { useCalendar } from '@/composables/calendar';
import GoogleEventWrap from './GoogleEventWrap.vue';

    const props = defineProps<{
        data: MemberHourDay
        fullDayIndex: number;
    }>()
    const emit = defineEmits(['create'])

    const dragActive = ref(false)
    const beforeState = ref(0)
    const {draggingCalendar, setDraggingCalendar} = useCalendar()
    const googleItemLayer = computed(() => {
        if(props.data.googleEvents.length){
            const num = props.data.googleEvents.map(ob => Number(ob.order))
            const max = num.length ? Math.max(...num) + 1 : 0;
            return max   
        }
        return 0        
    })
    const layer = computed(() => {
        if(googleItemLayer.value){
            const num = props.data.googleEvents.map(ob => Number(ob.order))
            const max = num.length ? Math.max(...num) + 1 : 0;
            return max   
        }
        const num = props.data.records.map(ob => Number(ob.order))
        const max = num.length ? Math.max(...num) + 1 : 0;
        return max        
    })
    const hours = computed(() => {
        return [
            { val: '00' },
            { val: '30' },
        ]
    })

    const enter = () => {
        if(draggingCalendar.value && draggingCalendar.value.active_user_id == props.data.user.id){
            dragActive.value = true
        }
        
    }
    const leave = () => {
        dragActive.value = false
    }
    const setBeforeState = (event) => {
        beforeState.value = event.x     
    }
    const dropFinish = inject<Function>('dropFinish') as Function
    const gotMove = (val) => {
        if(draggingCalendar.value){
            const record = draggingCalendar.value
            setDraggingCalendar(null)
            const date = props.data.date
            const time = props.data?.hour?.split(":") || 0;
            const min = val.val
            const merge = DateTime.fromISO(date)
            .set({ 
                hour: Number(time[0]),
                minute: Number(min),
                second: 0 
            })
            .toFormat('yyyy-MM-dd HH:mm:ss');
            dragActive.value = false
            if(dropFinish){
                dropFinish(record, merge)
            }
        }       
    }
    const fullDate = (val) => {
        const date = props.data.date
        const time = props.data?.hour?.split(":") || 0;
        const min = val.val
        const merge = DateTime.fromISO(date)
            .set({ 
                hour: Number(time[0]),
                minute: Number(min),
                second: 0 
            })
            .toFormat('yyyy-MM-dd HH:mm');
        return merge
    }
    const createAtTime = (event) => {
        if(Math.abs(event.x - beforeState.value) > 15) {
            return
        }
        const targetElement = event.target;
        const elementWidth = targetElement.offsetWidth;
        const clickX = event.clientX - targetElement.getBoundingClientRect().left;
        let min = ''
        if (clickX < elementWidth / 2) {
            min = '00'
        } else {
            min = '30'
        }
        const date = props.data.date
        const time = props.data?.hour?.split(":") || 0;
        const merge = DateTime.fromISO(date)
            .set({ 
                hour: Number(time[0]),
                minute: Number(min),
                second: 0 
            })
            .toFormat('yyyy-MM-dd HH:mm:ss');
        const d = {
            x: event.x,
            y: event.y,
            time: merge,
            stamp: DateTime.now()
        }
        emit('create', d, props.data.user)
        
    }
        



</script>