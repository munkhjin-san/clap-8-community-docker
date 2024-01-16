<template>
<div @mousedown="setBeforeState" @touchstart="setBeforeState" @click.self.stop="createAtTime" class="hour-slot" @mouseenter="enter" @mouseleave="leave">
    <CardWrap 
        v-for="record in hourRecords"
        :record="record"
        :key="record.id"
        :fullDayIndex="fullDayIndex"
        @setDayIndex="val => $emit('setDayIndex', val)"
        @setParentDroppable="dragActive = true"
    />
    <div v-if="dragActive && $store.state.draggingCalendar" style="position: absolute;left: 0;top:0;z-index: 9;height: 100%;width: 100%;display: flex;">
        <div @mouseup="gotMove(val)" v-for="val in hours" class="min-separete">
            <div class="min-popup">{{ fullDate(val) }}</div>
        </div>
    </div>
</div>
        
   
</template>
<script setup>
    import moment  from 'moment';
    import CardWrap from './CardWrap.vue';
    import { computed, inject, ref } from 'vue';
    import { useStore } from 'vuex';
    const store = useStore()
    const dragActive = ref(false)
    const beforeState = ref(0)

    const props = defineProps(['hourRecords', 'hour', 'day', 'fullDayIndex'])
    const emit = defineEmits(['create', 'setDayIndex'])

    const hours = computed(() => {
        return [
            { val: '00' },
            { val: '30' },
        ]
    })   

    const setBeforeState = (event) => {
        beforeState.value = event.x   
    }

    const createAtTime = (event) => {
        if(Math.abs(event.x - beforeState.value) > 15) {
            return
        }
        const targetElement = event.target;
        const elementWidth = targetElement.offsetWidth;
        const clickX = event.clientX - targetElement.getBoundingClientRect().left       
        const min = (clickX < elementWidth / 2) ? 0 : 30
        const date = props.day.full
        const time = props.hour.split(":");
        const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm:ss');
        const d = {
            x: event.x,
            y: event.y,
            time: merge,
            stamp: moment()
        }
        emit('create', d)        
    }

    const enter = () => {
        if(store.state.draggingCalendar){
            dragActive.value = true
        }
        
    }
    const leave = () => {
        dragActive.value = false
    }

    const dropFinish = inject('dropFinish')

    const gotMove = (val) => {
        if(store.state.draggingCalendar){
            const record = store.state.draggingCalendar
            store.commit('setDraggingCalendar', null)
            const date = props.day.full
            const time = props.hour.split(":");
            const min = val.val
            const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm:ss');
            dragActive.value = false
            if(dropFinish){
                dropFinish(record, merge)
            }
        }           
    }

    const fullDate = (val) => {
        const date = props.day.full
        const time = props.hour.split(":");
        const min = val.val
        const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm');
        return merge
    }
</script>