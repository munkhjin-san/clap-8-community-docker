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
                        @setDayIndex="val => $emit('setDayIndex', val)"
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
        @create="val => $emit('create', val)"
        @setDayIndex="val => $emit('setDayIndex', val)"
    />
</div>
</template>
<script setup>
    import moment from 'moment';
    import HourBlock from './HourBlock.vue';
    import AllDayRecord from '../AllDayRecord.vue';
    import { computed } from 'vue';
    const props = defineProps(['day', 'hoursOfDay', 'records', 'orderCreator', 'activeDay'])
    const emit = defineEmits(['releaseScroll', 'load', 'create', 'setListView', 'setDayIndex'])

    const indexOrder = computed(() => {
        if(props.activeDay){
            return props.activeDay.isSame(moment(props.day.full), 'day') ? 12 : 'unset'
        }
        return 'unset'
    })
    const isSaturday = computed(() => {
        return moment(props.day.full).day() === 6
    })
    const specialDay = computed(() => {
        return moment(props.day.full).day() === 0 || props.day.day_holiday
    })
    const hourFulldayRecords = computed(() => {
        if(props.records && props.records.length){   
            return  props.records.filter(ob => moment(ob.date_start).isSame(moment(props.day.full), 'day') && Math.abs(moment(ob.date_start).diff(moment(ob.date_end), 'hours')) >= 23)            
            
        }
        return []
    })
    const layer = computed(() => {
        const num = dayRecords.value.map(ob => ob.order)
        const max = num.length ? Math.max(...num) + 1 : 0;
        return max
        
    })
    const isPastDay = computed(() => {
        return moment(props.day.full).isBefore(moment(), 'day')
    })
    const isToday = computed(() => {
        return moment(props.day.full).isSame(moment(), 'day')
    })
    const fistDayOfMonth = computed(() => {
        return props.day.day == 1
    })
    const dayRecords = computed(() => {
        if(props.records && props.records.length){               
            const list = props.records.filter(ob => moment(ob.date_start).isSame(moment(props.day.full), 'day') && Math.abs(moment(ob.date_start).diff(moment(ob.date_end), 'hours')) < 23)
            const sortedList = list.slice().sort((a, b) => {
                return new Date(a.date_start) - new Date(b.date_start) ||
                new Date(a.updated_at) - new Date(b.updated_at);
            });               

            const ordered = props.orderCreator(0, sortedList, props.day.full)           
            console.log('check', ordered.length == sortedList.length)
            return ordered
        }
        return []
    })

    const listView = (day) => {
        emit('setListView', day.full)
    }
    const computedDay = (day) => {
        moment.locale('ja')
        const top = moment(day.full).format('D')
        const bottom =  moment(day.full).format('ddd')
        return `<span>${top}</span><span>${bottom}</span>`
    }
    
    const hourRecords = (hour) => {
        if(dayRecords.value && dayRecords.value.length){               
            return dayRecords.value.filter(ob => moment(ob.date_start).format('H') == hour && Math.abs(moment(ob.date_start).diff(moment(ob.date_end), 'hours')) < 23)
        }
        return []
    }   
</script>