<template>
    <div         
        :style="{
            overflow: 'auto',
            scrollSnapType: '',
            cursor: 'grab'
        }" 
        ref="monthLayout" 
        id="cal_week_view"
        class="calendar-day-root"
        @scroll="scrollListen"
        @mouseup="onMouseUp">
        <Transition name="modalFade">
            <div class="cal-day-loader" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="calendar-container-outer-week" :style="{width: `calc((100% / ${responsive.mobile ? 4 : 8}) * ${days.length + 1})`}">
            
            <div class="calendar-header">  
                <div ref="spacer" id="weekSpacer" :style="{ width: hideName ? '45px' : `130px`}" class="left-member-tile"></div>
                <div 
                    :id="`day_val_w_${day.day_full}`"
                    @click="shiftToListView($event,day.day_full)" 
                    @mousedown="onMouseDown"
                    v-for="day in days" 
                    ref="dayHeader"
                    class="w-day-item" 
                    style="cursor: pointer;"
                >
                    <div :class="['top-day-tile', {isPastDay : isPastDay(day)}, {isTodayWeek : isToday(day)}]" >
                        <div :class="['cal-m-day-title', {'special-day': specialDay(day), 'isSaturday' : isSaturday(day)}]">{{ dayTitle(day) }}</div>
                        <p class="pc" style="margin-left: 5px;white-space: nowrap;overflow: hidden;font-size:11px;color:tomato" v-if="day.day_holiday">{{ day.day_holiday }}</p>
                    </div> 
                </div>
            </div>
      
            
            <div >
                <div v-for="user in listMembers" style="display: flex;">
                    <div @click="hideName = false" class="left-member-tile" draggable="false" :style="{ width: hideName ? '45px' : `130px`}">
                        <div style="cursor: pointer;overflow: hidden;">
                            <UserPanel :disableInstant="hideName" :user="user" imgClass="userMidIcon" size="25"/>
                            <div @click.stop="pushInstantUser($event, user.id)" :style="{lineHeight: 1.5, visibility: hideName ? 'hidden' : 'visible'}">{{user.name}}</div>
                        </div>                        
                    </div>
                    <DayBlock 
                        @mousedown="onMouseDown" 
                        @touchstart="handleTouchStart"
                        @touchmove="handleTouchMove"
                        v-for="day in days" 
                        :key="`${user.id}_${day.day_full}`" 
                        :user="user" 
                        :day="day"
                        :beforeState="beforeState"          
                        :google-events="user.id == auth.id ? googleEvents : []"             
                        @addRecord="(type, day, user) => emit('addRecord',type, day, user)"
                        @create="(date, user) => emit('create', date, user)"
                        />
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import DayBlock from './DayBlock.vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import { computed, ComputedRef, inject, onMounted, onUnmounted, Ref, ref, useTemplateRef, watch } from 'vue';
import { useResponsive } from '@/store/responsive';
import { CalendarGroupUser, CalendarRecord, GoogleEventItem, MemberMonthDay } from '@/interface/calendarInterface';
import { DateTime } from 'luxon';
import { useCalendar } from '@/composables/calendar';
import { useAuthUserStore } from '@/store/auth';
    const props = defineProps<{
        records: CalendarRecord[];
        selectedYear: number;
        selectedMonth: number;
        initialLoader: boolean;
        activeMonth: number;
        activeYear: number;
        activeMembers: CalendarGroupUser[];
        appendLock: boolean;
        googleEvents: GoogleEventItem[]
    }>()
    const emit = defineEmits(['addRecord', 'create', 'resetFastCreate', 'setListView', 'scrollHorizontal'])
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const cursorPos = ref([0, 0])
    const beforeState = ref(0)
    const hideName = ref(false)
    const lockScroll = ref(false)
    const scrollCount = ref(0)
    const startX = ref(0)
    const startY = ref(0)
    const isHorizontalScroll = ref(false)
    const {draggingCalendar, setDraggingCalendar} = useCalendar()
    const pushInstantUser = inject<Function>('pushInstantUser') as Function
    const holidays = inject<ComputedRef>('holidays')
    const listMembers = computed(() => {
        const uniqueUserIds = new Set();
        const memberList:CalendarGroupUser[] = [];
        props.activeMembers.forEach((user) => {
            if (!uniqueUserIds.has(user.id)) {
                uniqueUserIds.add(user.id);
                memberList.push(user);
            }
        });
        return memberList;
    })
    const spacer = ref(null)
    const dayHeader = useTemplateRef('dayHeader')
    const days = computed(() => {

        const thisMonth = DateTime.fromObject({year: props.activeYear, month: props.activeMonth});
        const firstDay = thisMonth.startOf("month")
        let index = 0
        const today = DateTime.now()
        if(today.hasSame(thisMonth, 'month')){
            const diff = today.plus({weeks: 1}).diff(thisMonth.endOf('month'), 'days')
            if(diff.days > 0){
                index = diff.days
            }
        }
        const lastDay = thisMonth.endOf("month").plus({days: index});
        let calendar:MemberMonthDay[] = [];
        for (let i = firstDay; i <= lastDay; i = i.plus({days: 1})) {
            const holiday = holidays?.value.find((h: { date: { toISOString: () => any; }; }) => DateTime.fromISO(h.date.toISOString()).hasSame(i, 'day'));
            const records = props.records.filter(ob => DateTime.fromSQL(ob.date_start).hasSame(i, 'day'))
            
            calendar.push({ 
                "day_short" : i.toFormat("d"),
                "day_full" : i.toFormat("yyyy-MM-dd"),
                "day_holiday" : holiday ? holiday.name : '',
                "records" : records
            });
        }
        return calendar
    })   

    onUnmounted(() => {
        window.removeEventListener("mouseup", onMouseUp);
    })
    onMounted(() => {        
        localStorage.setItem('viewType', '2')
        window.addEventListener("mouseup", onMouseUp);
    })
    watch(() => lockScroll, (after) => {
        if(after){
            setTimeout(() => {
                lockScroll.value = false
            }, 300);
        }
    })

    const handleTouchStart = (event: TouchEvent) => {
        startX.value = event.touches[0].clientX;
        startY.value = event.touches[0].clientY;
        isHorizontalScroll.value = false;
    }
    const handleTouchMove = (event: TouchEvent) => {
        if (isHorizontalScroll.value === false) {
            const deltaX = Math.abs(event.touches[0].clientX - startX.value);
            const deltaY = Math.abs(event.touches[0].clientY - startY.value);
            const scrollThreshold = 10;
            if (deltaX > scrollThreshold || deltaY > scrollThreshold) {
            determineScrollDirection(deltaX, deltaY);
            }
        }
    }
    const determineScrollDirection = (deltaX: number, deltaY: number) => {
        const scrollThreshold = 5;
        if (deltaX > deltaY && deltaX > scrollThreshold) {
            isHorizontalScroll.value = true;
        } else if (deltaY > deltaX && deltaY > scrollThreshold) {
            isHorizontalScroll.value = false;
        }
    }
    const scrollListen = () => {
        if(responsive.mobile && !lockScroll.value && !props.appendLock && scrollCount.value > 0 && isHorizontalScroll.value){
            hideName.value = true
            lockScroll.value = true                    
        }        
        scrollCount.value ++   
        emit('resetFastCreate')
        emit('scrollHorizontal', event)
    }
    const shiftToListView = (event: MouseEvent, date: string) => {
        if(Math.abs(event.pageX - beforeState.value) > 15) {
            return
        }
        emit('setListView', date)
        
    }
    const isSaturday = (day: MemberMonthDay) => {
        return DateTime.fromFormat(day.day_full, 'yyyy-MM-dd').weekday === 6
    }
    const specialDay = (day: MemberMonthDay) => {
        return DateTime.fromFormat(day.day_full, 'yyyy-MM-dd').weekday === 7 || day.day_holiday
    }
    const isPastDay = (day: MemberMonthDay) => {
        return DateTime.fromISO(day.day_full).diff(DateTime.now(), 'day').as('days') < 0
    }
    const isToday = (day: MemberMonthDay) => {
        const givenDate = DateTime.fromISO(day.day_full).startOf('day');
        const today = DateTime.now().startOf('day');
        return givenDate.equals(today);
    }
    const dayTitle = (day: MemberMonthDay) => {
        const dayDate = DateTime.fromISO(day.day_full);
        const selectedDate = DateTime.fromObject({year: props.selectedYear, month: props.selectedMonth});
        const format = dayDate.hasSame(selectedDate, 'month')
            ? 'd(EEE)' 
            : dayDate.hasSame(DateTime.now(), 'year') 
                ? 'M/d(EEE)' 
                : 'yyyy/M/d(EEE)';
        return dayDate.toFormat(format);
    }

    const monthLayout = useTemplateRef('monthLayout')
    const onMouseDown = (ev: MouseEvent) => {
        cursorPos.value = [ev.pageX, ev.pageY];
        beforeState.value = ev.pageX
        window.addEventListener("mousemove", onMouseHold);
    }

    /** @param {MouseEvent} ev */
    const onMouseUp = () => {
        window.removeEventListener("mousemove", onMouseHold);
    }

    /** @param {MouseEvent} ev */
    const onMouseHold = (ev:MouseEvent) => {
        ev.preventDefault();
        if(draggingCalendar.value) return

        requestAnimationFrame(() => {
            const delta = [
            ev.pageX - cursorPos.value[0],
            ev.pageY - cursorPos.value[1],
            ];
            
            cursorPos.value = [ev.pageX, ev.pageY];

            if (!monthLayout.value) return;
            monthLayout.value.scrollBy({
                left: -delta[0],
                // top: -delta[1],
            });
            
        });
    }
    const containerScroll = async(day: string) => {
        const block = dayHeader.value?.find(ob => ob.id ==`day_val_w_${day}`)
        const index = dayHeader.value?.findIndex(ob => ob.id ==`day_val_w_${day}`)      
        if(block && monthLayout.value && index && index > -1){
            const rect = block.getBoundingClientRect()
            const offsetX = (rect.width * index)
            monthLayout.value.scrollTo(offsetX + 2,monthLayout.value.scrollTop)
        }
    }
    defineExpose({containerScroll})


</script>