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
                        <div :class="['top-list-tile']" ><div>{{ hour.hour == '0:00' ? '' : hour.hour }}</div></div> 
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
<script setup>
import moment from 'moment';
import MemberTile from './MemberTile.vue'
import { computed, inject, onMounted, onUnmounted, ref, watch } from 'vue';
import { useResponsive } from '@/store/responsive';
import { useFocused } from '@/store/focused';
    const responsive = useResponsive()
    const focused = useFocused()
    const props = defineProps(['records', 'activeMembers', 'selectedDate', 'initialLoader'])
    const emit = defineEmits(['create', 'resetFastCreate'])
    const hideName = ref(false)
    const lockScroll = ref(false)
    const startX = ref(0)
    const startY = ref(0)
    const isHorizontalScroll = ref(null)
    const cursorPos = ref([0,0])
    const hourMemberItems = ref([])
    const cal_week_view = ref(null)
    const currentMinute = ref(null)
    const draggingCalendar = inject('draggingCalendar')
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
        
        
        let break_point_rear = moment(date).startOf('day')
        let cooked = [];
        let reserved = [];
        for (let i = 0; i < list.length; i++) {            
            let item = { ...list[i] };            
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
            let uld = orderCreator(order + 1, reserved, date, user_id);
            cooked = cooked.concat(uld)
        }
        return cooked
        

    }
    const listMembers = computed(() => {
        const uniqueUserIds = new Set();
        const memberList = [];
        props.activeMembers.forEach((user) => {
                if (!uniqueUserIds.has(user.id)) {
                    uniqueUserIds.add(user.id);
                    const user_records = props.records.filter(ob => ob.calendar_users.map(item => item.id).includes(user.id))
                    memberList.push({user: user, records:user_records, date: props.selectedDate});
                }
            });
        
        return memberList;
    })
    const hoursOfDay = computed(() => {
        const hours = [];
        let currentHour = moment().startOf('day');        
        for (let i = 0; i < 24; i++) {
            hours.push({hour: currentHour.format('H:mm')});
            currentHour.add(1, 'hour')
        }
        return hours;
    })

    onUnmounted(() => {
        window.removeEventListener("mouseup", onMouseUp);
    })
    onMounted(() => {
        localStorage.setItem('viewType', 3)
        window.addEventListener("mouseup", onMouseUp);
        
        currentMinute.value = getCurrentMinute()
        setInterval(() => {
            currentMinute.value = getCurrentMinute();
        }, 600000);      
    })

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
            cal_week_view.value.scrollBy({
                left: -delta[0],
                // top: -delta[1],
            });            
        });
    }   
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
    const containerScroll = async() => {
        const index = moment().subtract(1, 'hour').startOf('hour').hour()       
        const el = hourMemberItems.value[index]
        if(el){
            el.scrollIntoView({block : 'start', inline: "start" })
            setTimeout(() => {
                hideName.value = false
            }, 0);                
        }    
    }
    defineExpose({containerScroll})
</script>