<template>
    <div id="cal_day_view" 
        :style="{
            overflow: 'auto',
            scrollSnapType: isDragging ? '' : '',
            cursor: 'grab'
        }" 
        ref="cal_day_view" 
        class="calendar-day-root" 
        @scroll="$emit('scroll', $event)"
        @mousedown="onMouseDown" 
        @mouseup="onMouseUp"
    >
        <!-- <div @click="scrollTo(-1)" class="week-scroll-button">
            <svg class="dot-menu" version="1.1" width="12" height="12" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
            </svg>
        </div>
        <div @click="scrollTo(1)" class="week-scroll-button" style="left: auto; right: 0;">
            <svg class="dot-menu" version="1.1" width="12" height="12" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(180deg);">
                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
            </svg>
        </div> -->
        <Transition name="modalFade">
            <div class="cal-day-loader" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="calendar-container-outer">
            <div class="calendar-header">   
            
                <div class="day-label" style="min-height:30px;border-right: none;background: var(--past-calendar);z-index: -1;"></div>
                <div style="display: flex;width: 100%;position: relative;">
                    <div style="display: flex;width: 100%;">
                        <HourItem :ref="`hour_${hour}`" v-for="hour in hoursOfDay" :key="hour" :hour="hour"/>
                    </div>
                    <div :style="{width: barWidth}" class="hour-bar"></div>
                </div>
                <!-- <div v-for="hour in hoursOfDay" :class="['hour-tc', { 'past-hour': pastHour }]">
                    <div :ref="`hour_${hour}`">{{ hour == '0:00' ? '' : hour }}</div>
                </div> -->
            </div>
            <div ref="dayParent">
                <DayRows 
                    :ref="`day_row_${day.full}`"
                    v-for="(day) in daysOfMonth" 
                    :key="day.full"
                    :day="day"
                    :hoursOfDay="hoursOfDay"
                    :orderCreator="orderCreator"
                    :records="records"
                    :facilitiesList="facilitiesList"
                    @releaseScroll="$emit('releaseScroll')"
                    @load="val => $emit('load', val)"
                    @scrollToTime="scrollToTime"
                    @create="val => $emit('create', val)"
                    @edit="val => $emit('edit', val)"
                    @delete="val => $emit('delete', val)"
                    @dropFinish="(record, date) => $emit('dropFinish', record, date)"
                    @setListView="(val) => $emit('setListView', val)"
                />
            </div>
            
        </div>
    </div>
</template>
  
<script>
import moment from 'moment';
import DayRows from './Day/DayRows.vue'
import HourItem from './Day/HourItem.vue';
export default {
    props: ['daysOfMonth', 'records', 'initialLoader', 'facilitiesList'],
    emits: ['scroll', 'load', 'releaseScroll', 'edit', 'dropFinish', 'delete', 'create', 'setListView'],
    data(){
        return{
            touchStartX: 0,
            touchStartY: 0,
            scrollingDirection: null,
            isDragging: false,
            cursorPos: [0, 0],
            el: null,
            currentTime: moment().format(),
            currentMinute: this.getCurrentMinute(),
        }
    },
    components:{
        DayRows,
        HourItem
    },
    unmounted(){
        window.removeEventListener("mouseup", this.onMouseUp);
    },
    watch:{
        '$store.state.focused'(){            
            this.currentMinute = this.getCurrentMinute();
        }
    },
    mounted(){
        localStorage.setItem('viewType', 0)
        window.addEventListener("mouseup", this.onMouseUp);
        localStorage.setItem('viewType', 0)
        if(this.$store.state.tempRecord){
            
        }else{
            const now = moment().subtract(1, 'hour').startOf('hour').format('H:mm')        
            const el = this.$refs[`hour_${now}`][0].$refs[`hour_item_${now}`]
            if(el){
                el.scrollIntoView({block : 'start', inline: "start" })
                setTimeout(() => {
                    const rect = {
                        x: el.scrollLeft,
                        y: el.scrollTop
                    }
                    this.$store.commit('setCalendarOffset', rect)
                }, 100);
            }
        }
        setInterval(() => {
            this.currentMinute = this.getCurrentMinute();
        }, 600000);
        
        
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
        barWidth(){
            const timeString = this.currentMinute;

            // Parse the time and calculate the total minutes
            const time = moment(timeString, 'HH:mm');
            const totalMinutes = time.hours() * 60 + time.minutes();

            // Calculate the percentage of 24 hours
            const percentageOf24Hours = (totalMinutes / (24 * 60)) * 100;
            return `${percentageOf24Hours}%`
        }
    },
    methods:{
        getCurrentMinute() {
            return moment().format('HH:mm');
        },
        scrollTo(index){
            
            requestAnimationFrame(() => {
                const val = window.innerWidth / 6 * index
                this.$refs.cal_day_view.scrollBy({
                    left: val,
                    behavior: "smooth",
                });
            })
        },
        onMouseDown(ev) {
            this.cursorPos = [ev.pageX, ev.pageY];
            // this.isDragging = true;
            window.addEventListener("mousemove", this.onMouseHold);
        },

        /** @param {MouseEvent} ev */
        onMouseUp(ev) {
            window.removeEventListener("mousemove", this.onMouseHold);
            this.isDragging = false;
        },

    /** @param {MouseEvent} ev */
        onMouseHold(ev) {
            ev.preventDefault();
            if(this.$store.state.draggingCalendar) return

            requestAnimationFrame(() => {
                const delta = [
                ev.pageX - this.cursorPos[0],
                ev.pageY - this.cursorPos[1],
                ];
                
                this.cursorPos = [ev.pageX, ev.pageY];

                if (!this.$refs.cal_day_view) return;
                this.$refs.cal_day_view.scrollBy({
                    left: -delta[0],
                    // top: -delta[1],
                });
                
            });
        },
        scrollToTime(val){
            const el = this.$refs[`hour_${val}`][0].$refs[`hour_item_${val}`]
            if(el){               
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' })
            }
        },
        dayRecords(day){
            
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

</style>