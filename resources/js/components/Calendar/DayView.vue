<template>
    <div 
        id="cal_day_view" v-dragscroll.x="{active: !$store.state.mobile}" ref="cal_day_view" class="calendar-day-root" @scroll="$emit('scroll', $event)">
        <div class="calendar-container-outer">
            <div class="calendar-header">
                <div class="day-label" style="min-height:30px;border-right: none;"></div>
                <div v-for="hour in hoursOfDay" class="hour-tc">
                    <div :ref="`hour_${hour}`">{{ hour == '0:00' ? '' : hour }}</div>
                </div>
            </div>
            <!-- <div v-for="(day, dayIndex) in daysOfMonth" :key="dayIndex" class="day">
                <div class="day-label">{{ day.day }}</div>
                <div v-for="(hour, hourIndex) in hoursOfDay" :key="hourIndex" class="hour-slot"></div>
            </div> -->
            <div ref="dayParent">
                <DayRows 
                    :ref="`day_row_${day.full}`"
                    v-for="(day, dayIndex) in daysOfMonth" 
                    :key="dayIndex"
                    :day="day"
                    :hoursOfDay="hoursOfDay"
                    :dayRecords="dayRecords(day)"
                    @releaseScroll="$emit('releaseScroll')"
                    @load="val => $emit('load', val)"
                />
            </div>
            
        </div>
    </div>
</template>
  
<script>
import moment from 'moment';
import DayRows from './Day/DayRows.vue'
import { nextTick } from 'vue'
import { dragscroll } from 'vue-dragscroll'
export default {
    props: ['daysOfMonth', 'records'],
    emits: ['scroll', 'load', 'releaseScroll'],
    data(){
        return{
            touchStartX: 0,
            touchStartY: 0,
            scrollingDirection: null,
        }
    },
    directives: {
        dragscroll
    },
    components:{
        DayRows
    },
    mounted(){
        const now = moment().subtract(1, 'hour').startOf('hour').format('H:mm')
        console.log('ref',this.$refs[`hour_${now}`])
        const el = this.$refs[`hour_${now}`]
        if(el.length){

            el[0].scrollIntoView({block : 'start', inline: "start" })
            setTimeout(() => {
                const rect = {
                    x: el[0].scrollLeft,
                    y: el[0].scrollTop
                }
                this.$store.commit('setCalendarOffset', rect)
            }, 100);
        }
        
    },
    computed: {
        
        hoursOfDay() {
            const hours = [];
            let currentHour = moment().startOf('day');
            for (let i = 0; i < 24; i++) {
                hours.push(currentHour.format('H:mm'));
                currentHour.add(1, 'hour');
            }
            return hours;
        },
        
    },
    methods:{
        dayRecords(day){
            const index = moment(day.full).format('YYYY-MM')
            if(this.records && this.records.length){               
                const list = this.records.filter(ob => moment(ob.date_start).isSame(moment(day.full), 'day'))
                const sortedList = list.slice().sort((a, b) => {
                    return new Date(a.date_start) - new Date(b.date_start);
                });               

                const ordered = this.orderCreator(0, sortedList, day.full)           
                console.log('check', ordered.length == sortedList.length)
                return ordered
            }
            return []
            
        },
        orderCreator(order, list, date){
            let break_point_rear = moment(date).startOf('day')
            let cooked = [];
            let reserved = [];
            for (let i = 0; i < list.length; i++) {
                let item = list[i]
                if(i == 0){
                    item['order'] = order
                    cooked.push(item)
                    break_point_rear = moment(item.date_end)
                }else{
                    if(moment(item.date_start).isSameOrAfter(break_point_rear)){
                        item['order'] = order
                        cooked.push(item)
                        break_point_rear = moment(item.date_end)
                    }
                    else{
                        reserved.push(item)
                    }
                }
            }
            if(reserved.length){
                let uld = this.orderCreator(order + 1, reserved, date);
                cooked = cooked.concat(uld)
            }
            return cooked
            

        }
    }
};
</script>
  
<style>
.calendar-container-outer {
    display: flex;
    flex-direction: column;
    width: 200vw;
    min-width: 200vw;
    color: var(--primary-color);
    background: var(--background-color);
}  
.calendar-header {
    display: flex;
    position: sticky;
    top: 0;
    background: var(--background-color);
    z-index: 11;
}
  
.hour-tc {
    flex: 1;
    text-align: center;
    border-bottom: 1px solid var(--calendarBorder);
    height: 30px;
    display: flex;
    position: relative;
    z-index: 7;
    font-weight: 600;
}
.hour-tc > div{
    position: absolute;
    left: -15px;
    top:0;
    bottom: 0;
    margin: auto 0;
    font-size: 13px;
    height: fit-content;
}
  
.day-tc {
    display: flex;
    box-sizing: border-box;
    position: relative;
    max-width: 100%;
    /* overflow: hidden; */
}
  
.day-label {
    width: 30px;
    flex: unset;
    text-align: center;
    border-right: 1px solid var(--calendarBorder);
    border-bottom: 1px solid var(--calendarBorder);
    min-height: 240px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    position: sticky;
    left: 0;
    background: var(--background-color);
    z-index: 5;
    z-index: 5;
    flex-direction: column;
    gap: 10px;
    font-weight: 600;
}

.hour-slot {
    flex: 1;
    text-align: center;
    border-right: 1px solid var(--calendarBorder);
    border-bottom: 1px solid var(--calendarBorder);
    box-sizing: border-box;
    min-height: 240px;
    display: flex;
    width: calc((200vh - 30px) / 24);
    position: relative;
}
.isToday{
    background: rgb(197, 175, 114);
    color: rgb(255, 255, 255);
}
.min-slot{
    flex: 1;
    position: relative;
}
.calendar-card{
    font-size: 12px;
    background: var(--task-background);
    height: 60px;
    max-height: 60px;
    overflow: hidden;
    position: absolute;
    z-index: 1;
}
.calendar-card-inner{
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 5px;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
}
.calendar-day-root{
    width: 100%;
    height: calc(100% - 60px);
    overflow: hidden scroll;
}
@media screen and (max-width: 959px) {
    .calendar-container-outer {
        width: 500vw;
        min-width: 500vw;
    } 
    .calendar-day-root{
        overflow: auto;
        -webkit-overflow-scrolling: unset;
    }
}
</style>