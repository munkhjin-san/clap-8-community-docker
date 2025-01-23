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
                        @addRecord="(type, day, user) => emit('addRecord',type, day, user)"
                        @create="(date, user) => emit('create', date, user)"
                        />
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import moment from 'moment';
import DayBlock from './DayBlock.vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import { computed, inject, onMounted, onUnmounted, ref, watch } from 'vue';
import { useResponsive } from '@/store/responsive';
    const props = defineProps(["records", "selectedYear", "selectedMonth", 'isSwiperChange', 'initialLoader', 'activeMonth', 'activeYear', 'holidays', 'activeMembers', 'appendLock'])
    const emit = defineEmits(['addRecord', 'create', 'resetFastCreate', 'setListView'])
    const responsive = useResponsive()
    const cursorPos = ref([0, 0])
    const beforeState = ref(0)
    const hideName = ref(false)
    const lockScroll = ref(false)
    const scrollCount = ref(0)
    const startX = ref(0)
    const startY = ref(0)
    const isHorizontalScroll = ref(null)
    const draggingCalendar = inject('draggingCalendar')
    const listMembers = computed(() => {
        const uniqueUserIds = new Set();
        const memberList = [];
        props.activeMembers.forEach((user) => {
            if (!uniqueUserIds.has(user.id)) {
                uniqueUserIds.add(user.id);
                memberList.push(user);
            }
        });
        return memberList;
    })
    const spacer = ref(null)
    const dayHeader = ref([])
    const days = computed(() => {

        const thisMonth = moment([props.activeYear, props.activeMonth]);
        const firstDay = thisMonth.clone().startOf("month")
        let index = 0
        const today = moment()
        if(today.isSame(thisMonth, 'month')){
            const diff = today.add(1, 'week').diff(thisMonth.clone().endOf('month'), 'days')
            if(diff > 0){
                index = diff
            }
        }
        const lastDay = thisMonth.clone().endOf("month").add(index, 'days');
        let calendar = [];
        for (let i = firstDay; i.isBefore(lastDay); i.add(1, "day")) {
            const holiday = props.holidays.find(h => moment(h.date).isSame(i, 'day'));
            const records = props.records.filter(ob => moment(ob.date_start).isSame(moment(i), 'day'))
            calendar.push({ 
                "day_short" : i.locale("ja").format("D"),
                "day_full" : i.locale("ja").format("YYYY-MM-DD"),
                "day_holiday" : holiday ? holiday.name : null,
                "records" : records
            });
        }
        return calendar
    })   

    onUnmounted(() => {
        window.removeEventListener("mouseup", onMouseUp);
    })
    onMounted(() => {        
        localStorage.setItem('viewType', 2)
        window.addEventListener("mouseup", onMouseUp);
        const today = moment().format('YYYY-MM-DD')
        // containerScroll(today)
    })
    watch(() => lockScroll, (after) => {
        if(after){
            setTimeout(() => {
                lockScroll.value = false
            }, 300);
        }
    })

    const pushInstantUser = inject('pushInstantUser')
    const handleTouchStart = (event) => {
        startX.value = event.touches[0].clientX;
        startY.value = event.touches[0].clientY;
        isHorizontalScroll.value = null;
    }
    const handleTouchMove = (event) => {
        if (isHorizontalScroll.value === null) {
            const deltaX = Math.abs(event.touches[0].clientX - startX.value);
            const deltaY = Math.abs(event.touches[0].clientY - startY.value);
            const scrollThreshold = 10;
            if (deltaX > scrollThreshold || deltaY > scrollThreshold) {
            determineScrollDirection(deltaX, deltaY);
            }
        }
    }
    const determineScrollDirection = (deltaX, deltaY) => {
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
    const shiftToListView = (event, date) => {
        if(Math.abs(event.pageX - beforeState.value) > 15) {
            return
        }
        emit('setListView', date)
        
    }
    const isSaturday = (day) => {
        return moment(day.day_full).day() === 6
    }
    const specialDay = (day) => {
        return moment(day.day_full).day() === 0 || day.day_holiday
    }
    const isPastDay = (day) => {
        return moment(day.day_full).isBefore(moment(), 'day')
    }
    const isToday = (day) => {
        return moment(day.day_full).isSame(moment(), 'day')
    }
    const dayTitle = (day) => {
        const format = moment([props.selectedYear, props.selectedMonth]).isSame(moment(day.day_full), 'month') ? 'D(ddd)' : moment().isSame(moment(day.day_full), 'year') ? 'M/D(ddd)' : 'YYYY/M/D(ddd)'
        return moment(day.day_full).format(format)
    }
    const monthLayout = ref(null)
    const onMouseDown = (ev) => {
        cursorPos.value = [ev.pageX, ev.pageY];
        beforeState.value = ev.pageX
        window.addEventListener("mousemove", onMouseHold);
    }

    /** @param {MouseEvent} ev */
    const onMouseUp = (ev) => {
        window.removeEventListener("mousemove", onMouseHold);
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

            if (!monthLayout.value) return;
            monthLayout.value.scrollBy({
                left: -delta[0],
                // top: -delta[1],
            });
            
        });
    }
    const containerScroll = async(day) => {
        const block = dayHeader.value.find(ob => ob.id ==`day_val_w_${day}`)
        const index = dayHeader.value.findIndex(ob => ob.id ==`day_val_w_${day}`)      
        if(block && monthLayout.value){
            const rect = block.getBoundingClientRect()
            const offsetX = (rect.width * index)
            monthLayout.value.scrollTo(offsetX + 2,monthLayout.value.scrollTop)
        }
    }
    defineExpose({containerScroll})


</script>