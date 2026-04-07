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
                    :googleEventOrderCreator="googleEventOrderCreator"
                    :records="records"
                    :googleEvents="googleEvents"
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
  
<script setup lang="ts">
import DayTile from './DayTile.vue'
import HourTitle from './HourTitle.vue';
import { computed, onMounted, onUnmounted, watch, ref, useTemplateRef } from 'vue';
import { useFocused } from '@/store/focused';
import { NormalHourDay, CalendarRecord, GoogleEventItem } from '@/interface/calendarInterface';
import { DateTime } from 'luxon';
import { useCalendar } from '@/composables/calendar';
    const focused = useFocused()
    const props = defineProps<{
        daysOfMonth: NormalHourDay[]
        records: CalendarRecord[]
        initialLoader: boolean
        googleEvents: GoogleEventItem[]
    }>()
    const emit = defineEmits(['scroll', 'load', 'releaseScroll', 'create', 'setListView'])
    const {draggingCalendar} = useCalendar()
    const isDragging = ref(false)
    const cursorPos = ref([0, 0])
    const currentMinute = ref<string>('')
    const activeDay = ref<DateTime | null>(null)
    const dayItems = useTemplateRef('dayItems')
    const hourItem = useTemplateRef('hourItem')
    const dayViewRoot = useTemplateRef('dayViewRoot')
    const dayParent = useTemplateRef('dayParent')

    onUnmounted(() => {
        window.removeEventListener("mouseup", onMouseUp);
    })
    watch(() => focused.active, () => {
        currentMinute.value = getCurrentMinute()
    })
    onMounted(() => {
        localStorage.setItem('viewType', '0')
        window.addEventListener("mouseup", onMouseUp);
        currentMinute.value = getCurrentMinute()
        setInterval(() => {
            currentMinute.value = getCurrentMinute();
        }, 600000);       
    })

    const hoursOfDay = computed(() => {
        const hours:string[] = [];
        let currentHour = DateTime.now().startOf('day');
        for (let i = 0; i < 24; i++) {
            hours.push(currentHour.toFormat('H:mm'));
            currentHour = currentHour.plus({ hours: 1 })
        }
        return hours;
    })
    const barWidth = computed(() => {
        const timeString = currentMinute.value;
        const time = DateTime.fromFormat(timeString, 'HH:mm');
        const totalMinutes = time.hour * 60 + time.minute;
        const percentageOf24Hours = (totalMinutes / (24 * 60)) * 100;
        return `${percentageOf24Hours}%`
    })

    const getCurrentMinute = () => {
        return DateTime.now().toFormat('HH:mm');
    }
    const setActiveDay = (val: string | null) => {
        if(val){
            activeDay.value = DateTime.fromISO(val)
        }
    } 
    const onMouseDown = (ev: MouseEvent) => {
        cursorPos.value = [ev.pageX, ev.pageY];
        window.addEventListener("mousemove", onMouseHold);
    }

    /** @param {MouseEvent} ev */
    const onMouseUp = (ev: MouseEvent) => {
        window.removeEventListener("mousemove", onMouseHold);
        isDragging.value = false;
    }

    /** @param {MouseEvent} ev */
    const onMouseHold = (ev: MouseEvent) => {
        ev.preventDefault();
        if(draggingCalendar.value) return
        requestAnimationFrame(() => {
            const delta = [
                ev.pageX - cursorPos.value[0],
                ev.pageY - cursorPos.value[1],
            ];

            cursorPos.value = [ev.pageX, ev.pageY];
            if (!dayViewRoot) return;
            dayViewRoot.value?.scrollBy({
                left: -delta[0],
                // top: -delta[1],
            });
            
        });
    }
    const orderCreator = (order: number, list: CalendarRecord[], date: string) => {
        let break_point_rear = DateTime.fromFormat(date, 'yyyy-MM-dd')
        let cooked:CalendarRecord[] = [];
        let reserved:CalendarRecord[] = [];
        for (let i = 0; i < list.length; i++) {
            let item = list[i]
            if(i == 0){
                item['order'] = order
                cooked.push(item)
                break_point_rear = DateTime.fromFormat(item.date_end, 'yyyy-MM-dd')
            }else{
                if(DateTime.fromFormat(item.date_start, 'yyyy-MM-dd').diff(break_point_rear, 'days').as('days') >= 1){
                    item['order'] = order
                    cooked.push(item)
                    break_point_rear = DateTime.fromFormat(item.date_end, 'yyyy-MM-dd')
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
    const googleEventOrderCreator = (order:number, list: GoogleEventItem[], date: string) => {
        let break_point_rear = DateTime.fromFormat(`${date} 00:01`, 'yyyy-MM-dd HH:mm')
        let cooked:GoogleEventItem[] = [];
        let reserved:GoogleEventItem[] = [];
        for (let i = 0; i < list.length; i++) {
            let item = list[i]
            if(i == 0){
                item['order'] = order
                cooked.push(item)
                break_point_rear = DateTime.fromFormat(`${item.end_date} ${item.end_time}`, 'yyyy-MM-dd HH:mm')
            }else{
                if(DateTime.fromFormat(`${item.start_date} ${item.start_time}`, 'yyyy-MM-dd HH:mm').diff(break_point_rear, 'minutes').as('minutes') >= 0){
                    item['order'] = order
                    cooked.push(item)
                    break_point_rear = DateTime.fromFormat(`${item.end_date} ${item.end_time}`, 'yyyy-MM-dd HH:mm')
                }
                else{
                    reserved.push(item)
                }
            }
        }
        if(reserved.length){
            let uld = googleEventOrderCreator(order + 1, reserved, date);
            cooked = cooked.concat(uld)
        }
        return cooked       

    }
    const containerScroll = async(day:string) => {
        const index = DateTime.now().minus({ hours: 1 }).startOf('hour').hour
        const el = hourItem.value ? hourItem.value[index] : null      
        el?.$el.scrollIntoView({block : 'start', inline: "start" })        
        const block = dayItems.value?.find(item => {
            return item && item.$el && item.$el.id == `day_val_${day}`
        })
        if(block){
            block.$el?.scrollIntoView({block : 'start'})
            dayViewRoot.value?.scrollBy({
                top: -31,
            });
        }        
    }
    defineExpose({containerScroll})

</script>