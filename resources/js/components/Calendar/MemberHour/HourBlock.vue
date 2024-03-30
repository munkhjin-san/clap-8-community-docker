<template>
    <div class="w-day-item" :style="{position: 'relative', minHeight: `${layer * 70 + 30 + (fullDayIndex * 30)}px`}" @click.self.stop="createAtTime" @mouseenter="enter" @mouseleave="leave" @mousedown="setBeforeState" @touchstart="setBeforeState">
        <CardWrap
            v-for="item in data.records" 
            :record="item"
            :user="data.user"
            :fullDayIndex="fullDayIndex"
        />
        <div v-if="dragActive && draggingCalendar" style="position: absolute;left: 0;top:0;z-index: 9;height: 100%;width: 100%;display: flex;">
            <div @mouseup="gotMove(val)" v-for="val in hours" class="min-separete">
                <div class="min-popup">{{ fullDate(val) }}</div>
            </div>
        </div>
    </div>
</template>
<script setup>
import moment from 'moment'
import CardWrap from './CardWrap.vue';
import { computed, inject, ref } from 'vue';

    const props = defineProps(['data', 'fullDayIndex'])
    const emit = defineEmits(['create'])

    const dragActive = ref(false)
    const beforeState = ref(0)
    const draggingCalendar = inject('draggingCalendar')
    const layer = computed(() => {
        const num = props.data.records.map(ob => ob.order)
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
    const dropFinish = inject('dropFinish')
    const gotMove = (val) => {
        if(draggingCalendar.value){
            const record = draggingCalendar.value
            draggingCalendar.value = null
            const date = props.data.date
            const time = props.data.hour.split(":");
            const min = val.val
            const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm:ss');
            dragActive.value = false
            if(dropFinish){
                dropFinish(record, merge)
            }
        }       
    }
    const fullDate = (val) => {
        const date = props.data.date
        const time = props.data.hour.split(":");
        const min = val.val
        const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm');
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
        const time = props.data.hour.split(":");
        const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm:ss');
        const d = {
            x: event.x,
            y: event.y,
            time: merge,
            stamp: moment()
        }
        emit('create', d, props.data.user)
        
    }
        



</script>