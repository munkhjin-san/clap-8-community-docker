<template>
    <div id="cal_day_view" 
        :style="{
            overflow: 'auto',
            scrollSnapType: isDragging ? '' : '',
            cursor: 'grab'
        }" 
        ref="dayViewRoot" 
        class="calendar-day-root" 
        @scroll="emit('scroll', $event)"
        @mousedown="onMouseDown" 
        @mouseup="onMouseUp"
    >
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
                        <HourTitle :id="`d_day_${index}`" ref="hourItem" v-for="(hour, index) in hoursOfDay" :key="hour" :hour="hour"/>
                    </div>
                    <div :style="{width: barWidth}" class="hour-bar"></div>
                </div>        
            </div>
            <div ref="dayParent">
                <DayTile 
                    ref="dayItems"
                    v-for="(day, index) in daysOfMonth" 
                    :key="day.full"
                    :day="day"
                    :hoursOfDay="hoursOfDay"
                    :orderCreator="orderCreator"
                    :records="records"
                    :activeDay="activeDay"
                    @releaseScroll="emit('releaseScroll')"
                    @load="val => emit('load', val)"
                    @create="val => emit('create', val)"
                    @setListView="(val) => emit('setListView', val)"
                    @setDayIndex="setActiveDay"
                />
            </div>            
        </div>
    </div>
</template>
  
<script setup>
import moment from 'moment';
import DayTile from './DayTile.vue'
import HourTitle from './HourTitle.vue';
import { computed, onMounted, onUnmounted, watch, ref, inject } from 'vue';
import { useFocused } from '@/store/focused';
import { useTempRecord } from '@/store/tempRecord';
    const focused = useFocused()
    const tempRecord = useTempRecord()
    const props = defineProps(['daysOfMonth', 'records', 'initialLoader'])
    const emit = defineEmits(['scroll', 'load', 'releaseScroll', 'create', 'setListView'])
    const draggingCalendar = inject('draggingCalendar')
    const isDragging = ref(false)
    const cursorPos = ref([0, 0])
    const currentMinute = ref(null)
    const activeDay = ref(null)
    const dayItems = ref([])
    const hourItem = ref([])
    const dayViewRoot = ref(null)

    onUnmounted(() => {
        window.removeEventListener("mouseup", onMouseUp);
    })
    watch(() => focused.active, () => {
        currentMinute.value = getCurrentMinute()
    })
    onMounted(() => {
        localStorage.setItem('viewType', 0)
        window.addEventListener("mouseup", onMouseUp);
        localStorage.setItem('viewType', 0)
        if(tempRecord.id){
            
        }else{
            const index = moment().subtract(1, 'hour').startOf('hour').hour()  
            const el = hourItem.value[index]
            if(el){
                el.$el.scrollIntoView({block : 'start', inline: "start" })
            }
        }
        currentMinute.value = getCurrentMinute()
        setInterval(() => {
            currentMinute.value = getCurrentMinute();
        }, 600000);       
    })

    const hoursOfDay = computed(() => {
        const hours = [];
        let currentHour = moment().startOf('day');
        for (let i = 0; i < 24; i++) {
            hours.push(currentHour.format('H:mm'));
            currentHour.add(1, 'hour');
        }
        return hours;
    })
    const barWidth = computed(() => {
        const timeString = currentMinute.value;

        // Parse the time and calculate the total minutes
        const time = moment(timeString, 'HH:mm');
        const totalMinutes = time.hours() * 60 + time.minutes();

        // Calculate the percentage of 24 hours
        const percentageOf24Hours = (totalMinutes / (24 * 60)) * 100;
        return `${percentageOf24Hours}%`
    })

    const getCurrentMinute = () => {
        return moment().format('HH:mm');
    }
    const setActiveDay = (val) => {
        if(val){
            activeDay.value = moment(val)
        }
    } 
    const onMouseDown = (ev) => {
        cursorPos.value = [ev.pageX, ev.pageY];
        window.addEventListener("mousemove", onMouseHold);
    }

    /** @param {MouseEvent} ev */
    const onMouseUp = (ev) => {
        window.removeEventListener("mousemove", onMouseHold);
        isDragging.value = false;
    }

    /** @param {MouseEvent} ev */
    const onMouseHold = (ev) => {
        ev.preventDefault();
        if(draggingCalendar.value) return
        requestAnimationFrame(() => {
            const delta = [
                ev.pageX - cursorPos.value[0],
                ev.pageY - cursorPos.value[1],
            ];

            cursorPos.value = [ev.pageX, ev.pageY];
            if (!dayViewRoot) return;
            dayViewRoot.value.scrollBy({
                left: -delta[0],
                // top: -delta[1],
            });
            
        });
    }
    const orderCreator = (order, list, date) => {
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
            let uld = orderCreator(order + 1, reserved, date);
            cooked = cooked.concat(uld)
        }
        return cooked
        

    }
    

</script>