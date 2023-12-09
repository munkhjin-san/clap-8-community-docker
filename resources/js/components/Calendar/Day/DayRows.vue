<template>
<div :id="`day_val_${day.full}`" class="day-tc" :style="{ minHeight: `${layer * 70 + 10}px` }">
    <!-- <div :ref="`cal_${day.full}`" :class="['day-label', {isToday : isToday}]" v-html="computedDay(day)"></div> -->
    <div @click="listView(day)" :ref="`cal_${day.full}`" :class="['day-label', {isPastDay : isPastDay}, {isToday : isToday}]" v-html="computedDay(day)"></div>
    <HourRow
        v-for="(hour, hourIndex) in hoursOfDay" 
        :hour="hour"
        :day="day"
        :key="hourIndex"
        :hourRecords="hourRecords(hourIndex)"
        @scrollToTime="val => $emit('scrollToTime', val)"
        @edit="val => $emit('edit', val)"
        @create="val => $emit('create', val)"
        @delete="val => $emit('delete', val)"
        @dropFinish="(record, date) => $emit('dropFinish', record, date)"
        :facilitiesList="facilitiesList"
    />
    
    <!-- <div ref="cal_separetor" class="month-separetor" v-if="fistDayOfMonth">
        <div class="month-separetor-text">{{ fistDay }}</div>
        <div class="month-separetor-line"></div>
    </div> -->
    <!-- <SeparateLine 
        v-if="fistDayOfMonth"
        :day="day"
    /> -->

</div>
</template>
<script>
import moment from 'moment';
import HourRow from './HourRow.vue';
import SeparateLine from './SeparateLine.vue';
export default{
    props: ['day', 'hoursOfDay', 'records', 'facilitiesList', 'orderCreator'],
    emits: ['releaseScroll', 'load', 'scrollToTime', 'edit', 'dropFinish', 'delete', 'create', 'setListView'],
    components: {HourRow, SeparateLine},
    computed:{
        layer(){
            const num = this.dayRecords.map(ob => ob.order)
            const max = num.length ? Math.max(...num) + 1 : 0;
            return max
           
        },
        isPastDay(){
            return moment(this.day.full).isBefore(moment(), 'day')
        },
        isToday(){
            return moment(this.day.full).isSame(moment(), 'day')
        },
        fistDayOfMonth(){
            return this.day.day == 1
        },
        dayRecords(){
            if(this.records && this.records.length){               
                const list = this.records.filter(ob => moment(ob.date_start).isSame(moment(this.day.full), 'day'))
                const sortedList = list.slice().sort((a, b) => {
                    return new Date(a.date_start) - new Date(b.date_start);
                });               

                const ordered = this.orderCreator(0, sortedList, this.day.full)           
                console.log('check', ordered.length == sortedList.length)
                return ordered
            }
            return []
        }
    },
    mounted(){
        
        // if(this.$refs[`cal_${this.day.full}`] && this.isToday){
        //     // this.$refs[`cal_${this.day.full}`].scrollIntoView({block: 'center', behaviour: 'smooth'})
        //     // setTimeout(() => {
        //     //     this.$emit('releaseScroll', true)
        //     // }, 100);
        // }
        
        
    },
    methods:{
        enter(){
            if(this.$store.state.draggingCalendar){
                this.dragActive = true
            }
            
        },
        leave(){
            this.dragActive = false
        },
        listView(day){
            this.$emit('setListView', day.full)
        },
        computedDay(day){
            moment.locale('ja')
            const top = moment(day.full).format('D')
            const bottom =  moment(day.full).format('ddd')
            // const holiday = day.day_holiday ? `<span class="day-holiday">${day.day_holiday}</span>` : ''
            return `<span>${top}</span><span>${bottom}</span>`
        },
        hourRecords(hour){
            if(this.dayRecords && this.dayRecords.length){               
                return this.dayRecords.filter(ob => moment(ob.date_start).format('H') == hour)
            }
            return []
        }
    }
}

</script>