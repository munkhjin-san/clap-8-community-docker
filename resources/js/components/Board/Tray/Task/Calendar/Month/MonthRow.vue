<template>
    <div class="month-drop-area" :class="{sideMonth: !thisMonth}">
        <div :class="{todayTitle : thisDay}" :id="'mAnchor_' + day.day_full" class="m-day-head-section">
            <div @click="addTask(day.day_full)" :title="day.day_full" class="m-day-title">{{ day.day_short }}</div>
        </div>   
        <transition-group @click="changeView(day.day_full)" name="calendarItem" tag="div">
            <MonthRecordCard 
                v-for="record in records.slice(0, taskCount)"
                :record="record"
                :key="record.id"
                :myColor="myColor"
                :thisMonth="thisMonth" 
            />
            <div class="taskLength" v-if="records.length > taskCount">...({{ records.length }})</div>
        </transition-group>     


    </div>
</template>

<script>
import MonthRecordCard from './MonthRecordCard.vue'
import moment from 'moment';
    export default {
        props:['day', 'records', 'selectedYear', 'selectedMonth', 'myColor', 'taskCount'],
        mounted() {
        },
        computed:{
            thisMonth(){
                const tDay = moment(this.day.day_full).format('YYYY-MM') 
                const thisMonth =  moment([this.selectedYear, this.selectedMonth]).format('YYYY-MM')
                return tDay === thisMonth
            },
            thisDay(){
                const tDay = moment(this.day.day_full).format('YYYY-MM-DD') 
                const thisMonth =  moment().format('YYYY-MM-DD')
                return tDay === thisMonth 
            },
        },
        methods:{
            changeView(day){
                emitter.emit('listView', day)
            },
            addTask(day){
                this.$emit('addTask', day)
            },
        },
        components:{
            MonthRecordCard
        }
    }
</script>
<style>
   
    .taskLength{
        font-size: 11px;
        cursor: pointer;
    }
    .sideMonth{
        background: var(--bg3);
    }
    .todayTitle > div > svg {
        fill: #fff !important;
    }
    .todayTitle{
        background: var(--calendarToday);
        color: var(--primary-color);
    }
    .m-day-head-section{
        display: flex;
        width: 100%;
        height: 30px;
        font-size: 12px;
        align-items: center;
    }
    .m-day-add{
        width:40px;
        height: 40px;
        display: flex;
        place-content: center;
        align-items: center;
        margin-left: auto;
        cursor: pointer;
    }
    .m-day-title{
        height: 100%;
        width: -webkit-fill-available;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        width: -moz-available;
    }
    @media screen and (max-width: 959px) {
        .taskLength{
            font-size: 10px;
        }
    }
</style>
