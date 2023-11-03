<template>
    <div class="month-drop-area cal-m-row" :class="{activeMonth: thisMonth}" @mouseenter="enter" @mouseleave="leave">
        <Transition name="modalFade">
            <div v-if="dragActive && $store.state.draggingCalendar" @mouseup="gotMove(val)" class="month-drop-popup"></div>
        </Transition>
        <div @click="$emit('jumpToDate', day.day_full)" :class="{'cal-todayTitle' : thisDay}" :id="'day_val_m_' + day.day_full" class="cal-m-day-head-section">
            <div :title="day.day_full" :class="['cal-m-day-title', {'special-day': specialDay, 'isSaturday' : isSaturday}]">
                <p>{{ day.day_short }}</p>
                <p class="pc" style="margin-left: 5px;white-space: nowrap;overflow: hidden;" v-if="day.day_holiday">{{ day.day_holiday }}</p>
                <p class="pc" style="margin-left: 5px;white-space: nowrap;overflow: hidden;" v-else-if="anniversaryDay">GLOWD周年記念</p>
            </div>
            
            <div @click.stop="$emit('addRecord', 'day', day.day_full)" class="m-record-add pc">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 32 32" fill="#9b9b9b">
                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                </svg>
            </div>
        </div>   
        <transition-group name="modalFade" tag="div" style="display: flex;flex-direction: column;gap: 10px;padding: 0 0 10px 0;">
            <MonthRecord
                v-for="record in records"
                :record="record"
                :key="record.id"
                :colors="colors"
                :facilitiesList="facilitiesList"
                @edit="val => $emit('edit', val)"
                @delete="val => $emit('delete', val)"
                @fromMonth="val => $emit('fromMonth', val)"
            />
        </transition-group>     


    </div>
</template>

<script>
import MonthRecord from './MonthRecord.vue'
import moment from 'moment';
    export default {
        props:['day', 'records', 'selectedYear', 'selectedMonth', 'colors', 'taskCount', 'facilitiesList'],
        emits: ['fromMonth', 'addRecord', 'dropFinish', 'jumpToDate', 'edit', 'delete', 'setParentDroppable'],
        data() {
            return{
                dragActive: false
            }
        },
        computed:{
            anniversaryDay(){
                return moment(this.day.day_full).month() == 7 && moment(this.day.day_full).date() == 2
            },
            isSaturday(){
                return moment(this.day.day_full).day() === 6
            },
            specialDay(){
                return moment(this.day.day_full).day() === 0 || this.day.day_holiday
            },
            thisMonth(){
                const tDay = moment(this.day.day_full)
                const thisMonth =  moment([this.selectedYear, this.selectedMonth])
                return tDay.isSame(thisMonth, 'month')
            },
            thisDay(){
                const tDay = moment(this.day.day_full).format('YYYY-MM-DD') 
                const thisMonth =  moment().format('YYYY-MM-DD')
                return tDay === thisMonth 
            },
        },
        methods:{
            gotMove(val){
                if(this.$store.state.draggingCalendar){
                    const record = this.$store.state.draggingCalendar
                    this.$store.commit('setDraggingCalendar', null)
                    const record_date = moment(record.date_start)
                    const date = this.day.day_full
                    const merge = moment(date).set('hour', record_date.hour()).set('minute', record_date.minute()).set('second', 0).format('YYYY-MM-DD HH:mm:ss');
                    console.log(merge)
                    this.dragActive = false
                    this.$emit('dropFinish', record, merge)
                }
                
                
            },
            enter(){
                if(this.$store.state.draggingCalendar){
                    if(!moment(this.day.day_full).isSame(moment(this.$store.state.draggingCalendar.date_start), 'date')){
                        this.dragActive = true
                    }                    
                }

            },
            leave(){
                this.dragActive = false
            },
            changeView(day){
                emitter.emit('listView', day)
            },
            addTask(day){
                this.$emit('addTask', day)
            },
        },
        components:{
            MonthRecord
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
    .cal-todayTitle > div > svg {
        fill: #fff !important;
    }
    .cal-todayTitle{
        background: #C5AF72;
        color: #fff !important;
    }
    .cal-m-day-head-section{
        display: flex;
        width: 100%;
        height: 35px;
        font-size: 12px;
        align-items: center;
        justify-content: space-between;
        font-weight: 600;
        cursor: pointer;
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
    .cal-m-day-title{
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        width: -moz-available;
        max-width: calc(100% - 38px);
        margin-left: 10px;
    }
    .m-record-add{
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: inherit;
        border-radius: 50px;
        margin-right: 3px;
        cursor: pointer;
    }
    .cal-m-row{
        background: var(--bg3);
        position: relative;
    }
    /* .m-record-add:hover{
        background: var(--normalBorder);
    } */
    .m-record-add:hover > svg{
        fill: var(--primary-color);
    }
    .activeMonth{
        background: var(--background-color);
    }
    .month-drop-popup{
        position: absolute;
        background: rgba(102, 154, 222, 60%);
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
    }
    .isSaturday{
        color: #0c68ab;
    }
    .special-day {
        color: tomato;
    }
    @media screen and (max-width: 959px) {
        .taskLength{
            font-size: 10px;
        }
    }
</style>
