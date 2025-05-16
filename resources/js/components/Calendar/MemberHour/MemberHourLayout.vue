<template>
    <div         
        :style="{
            overflow: 'auto',
            scrollSnapType: '',
            cursor: 'grab'
        }" 
        ref="cal_week_view" 
        id="cal_list_view" 
        class="calendar-day-root"
        @mouseup="onMouseUp"
        @scroll="scrollListen"
        >
        <Transition name="modalFade">
            <div class="cal-day-loader" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div @mousedown="onMouseDown" @touchstart="handleTouchStart" @touchmove="handleTouchMove" class="calendar-container-outer-week" :style="{width: `calc((100% / ${responsive.mobile ? 4 : 13}) * ${24})`, height: '100%', background: 'var(--background-color)'}">
          
            <div class="calendar-header">  
                <div id="listViewSpacer" ref="spacer" :style="{ width: hideName ? '45px' : `130px`}" draggable="false" class="left-member-tile"></div>
                <div :style="{display: 'flex',position: 'relative', width: hideName ? 'calc(100% - 45px)' : `calc(100% - 130px)`}">
                    <div :id="`w_day_${index}`" ref="hourMemberItems" v-for="(hour, index) in hoursOfDay" class="w-day-item" style="border-right: solid thin transparent;background: unset;">
                        <div :class="['top-list-tile']" ><div>{{ hour == '0:00' ? '' : hour }}</div></div> 
                    </div>
                    <div :style="{width: barWidth}" class="hour-bar"></div>
                </div>
            </div>     
            
            <div>
                <MemberTile 
                    v-for="userData in listMembers"
                    :userData="userData"
                    :hideName="hideName"
                    :key="userData.user.id"
                    :orderCreator="orderCreator"
                    @create="(date, user) => emit('create', date, user)"
                    @viewFull="hideName = false"
                />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { DateTime } from 'luxon';
import MemberTile from './MemberTile.vue'
import { computed, inject, onMounted, onUnmounted, Ref, ref, useTemplateRef, watch } from 'vue';
import { useResponsive } from '@/store/responsive';
import { useFocused } from '@/store/focused';
import { CalendarGroupUser, CalendarRecord, MemberHourDay } from '@/interface/calendarInterface';
import { useCalendar } from '@/composables/calendar';
    const responsive = useResponsive()
    const focused = useFocused()
    const props = defineProps<{
        records: CalendarRecord[];
        activeMembers: CalendarGroupUser[];
        selectedDate: string;
        initialLoader: boolean;
    }>()
    const emit = defineEmits(['create', 'resetFastCreate', 'scrollHorizontal'])
    const hideName = ref(false)
    const lockScroll = ref(false)
    const startX = ref(0)
    const startY = ref(0)
    const isHorizontalScroll = ref(false)
    const cursorPos = ref([0,0])
    const hourMemberItems = useTemplateRef('hourMemberItems')
    const cal_week_view = useTemplateRef('cal_week_view')
    const currentMinute = ref<string>('')
    const {draggingCalendar} = useCalendar()
    watch(() => focused.active, () => {
        currentMinute.value = getCurrentMinute()
    })
    watch(() => lockScroll, (after) => {
        if(after){
            setTimeout(() => {
                lockScroll.value = false
            }, 300);
        }
    })
    const orderCreator = (order, list, date, user_id) => {
        
        
        let break_point_rear = DateTime.fromFormat(date, 'yyyy-MM-dd').startOf('day')
        let cooked:CalendarRecord[] = [];
        let reserved:CalendarRecord[] = [];
        for (let i = 0; i < list.length; i++) {            
            let item = { ...list[i] };            
            if(i == 0){
                item['order'] = order
                cooked.push(item)
                break_point_rear = DateTime.fromSQL(item.date_end)
            }else{
                if(DateTime.fromSQL(item.date_start).diff(break_point_rear, 'day').days > 0){
                    
                    item['order'] = order
                    cooked.push(item)
                    break_point_rear = DateTime.fromSQL(item.date_end)
                }else{
                    reserved.push(item)
                }
            }
        }
        if(reserved.length){
            let uld = orderCreator(order + 1, reserved, date, user_id);
            cooked = cooked.concat(uld)
        }
        return cooked
        

    }
    const listMembers = computed(() => {
        const uniqueUserIds = new Set();
        const memberList:MemberHourDay[] = [];
        props.activeMembers.forEach((user) => {
            if (!uniqueUserIds.has(user.id)) {
                uniqueUserIds.add(user.id);
                const user_records = props.records.filter(ob => ob.calendar_users.some(community_user => community_user.id === user.id))
                memberList.push({user: user, records:user_records, date: props.selectedDate});
            }
        });
        
        return memberList;
    })
    const hoursOfDay = computed(() => {
        const hours:string[] = [];
        let currentHour = DateTime.now().startOf('day');
        for (let i = 0; i < 24; i++) {
            hours.push(currentHour.toFormat('H:mm'));
            currentHour = currentHour.plus({ hours: 1 });
        }
        return hours;
    })
    onUnmounted(() => {
        window.removeEventListener("mouseup", onMouseUp);
    })
    onMounted(() => {
        localStorage.setItem('viewType', '3')
        window.addEventListener("mouseup", onMouseUp);
        
        currentMinute.value = getCurrentMinute()
        setInterval(() => {
            currentMinute.value = getCurrentMinute();
        }, 600000);      
    })

    const handleTouchStart = (event) => {
        startX.value = event.touches[0].clientX;
        startY.value = event.touches[0].clientY;
        isHorizontalScroll.value = false;
    }
    const handleTouchMove = (event) => {
        if (isHorizontalScroll.value === false) {
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
    const scrollListen = (event) => {            
        if(responsive.mobile && !lockScroll.value && isHorizontalScroll.value){
            hideName.value = true
            lockScroll.value = true
        }            
        emit('resetFastCreate')
        emit('scrollHorizontal', event)
    }
    const onMouseDown = (ev) => {
        cursorPos.value = [ev.pageX, ev.pageY];
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
            if (!cal_week_view) return;
            cal_week_view.value?.scrollBy({
                left: -delta[0],
                // top: -delta[1],
            });            
        });
    }   
    const barWidth = computed(() => {
        const timeString = currentMinute.value;

        // Parse the time and calculate the total minutes
        const time = DateTime.fromFormat(timeString, 'HH:mm');
        const totalMinutes = time.hour * 60 + time.minute;

        // Calculate the percentage of 24 hours
        const percentageOf24Hours = (totalMinutes / (24 * 60)) * 100;
        return `${percentageOf24Hours}%`
    })
    const getCurrentMinute = () => {
        return DateTime.now().toFormat('HH:mm');
    }
    const containerScroll = async() => {
        const index = DateTime.now().minus({ hours: 1 }).startOf('hour').hour       
        const el = hourMemberItems.value ? hourMemberItems.value[index] : null
        if(el){
            el?.scrollIntoView({block : 'start', inline: "start" })
            setTimeout(() => {
                hideName.value = false
            }, 0);                
        }    
    }
    defineExpose({containerScroll})
</script>