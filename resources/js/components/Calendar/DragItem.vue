<template>
    <div v-if="record">
        <div 
            v-if="!mobile"
            ref="draggingCalendarRef" 
            class="calendar-card dragging-calendar" 
            :style="{
                width: `${record['width']}px`, 
                maxHeight: '60px',
                background: background,
                color: color,
                position: 'fixed',
                zIndex: '100',
                top: `${record['y'] + 10}px`,
                left: `${record['x'] + 10}px`
            }"
        >
            <div class="calendar-card-inner">            
                <div style="display: flex;">
                    <UserIcon v-for="user in record.calendar_users.slice(0, 3)" :user="user" :disableInstant="true" imgClass="userSmallIcon" size="15"/>
                    <span style="line-height: 15px;" v-if="record.calendar_users.length > 3">...({{ record.calendar_users.length }})</span>
                </div>
                <div style="white-space: nowrap;">{{ time }}</div>          
                <div>{{ record.title }}</div>
            </div>
        </div>
        <Transition name="slidePop">
            <div v-if="mobile" class="copySuccess">    
                <span>移動先を選択してください</span>
                <div @click="cancelMove" style="margin-left: 10px;" class="commentEditButton">キャンセル</div>
            </div>
        </Transition>  
    </div>
    
</template>
<script setup>
import moment from 'moment';
import { computed, inject, onMounted, onUnmounted, ref } from 'vue';
import colors from '../../../assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useTheme } from '@/store/theme';
import { useResponsive } from '@/store/responsive'
import UserIcon from '../Board/Mixed/UserIcon.vue';
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const theme = useTheme()
    const mobile = ref(false)
    const draggingCalendar = inject('draggingCalendar')
    onMounted(() => {
        console.log(record.value)
        if ('ontouchstart' in window || navigator.msMaxTouchPoints) {
            mobile.value = true;
        }
        document.body.style.userSelect = 'none'
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onReset);
    })
    onUnmounted(() => {
        
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onReset);
    })

        const onReset = (e) => {            
            draggingCalendar.value = null          
        }
        const draggingCalendarRef = ref(null)
        const onMove = (e) => {
            if(!draggingCalendar.value || responsive.mobile) return
            let el = draggingCalendarRef.value              
            el.style.top = e.clientY + 10 + 'px'
            el.style.left = e.clientX + 10 + 'px'
        }   
        const cancelMove = () => {
            draggingCalendar.value = null
        }
    

        const record = computed(() => {
            return draggingCalendar.value ? draggingCalendar.value : null
        })     
        const background = computed(() => {
            
            const me = record.value.calendar_users.filter(ob => ob.id == auth.activeUser.id)
            return me.length ? colors[auth.activeUser.color]?.light : 'var(--task-background)'
        })
        const color = computed(() => {
            const me = record.value.calendar_users.filter(ob => ob.id == auth.activeUser.id)
            return me.length && theme.dark ? 'var(--background-color)' : 'var(--primary-color)'
        })     
               
        const time = computed(() => {
            return `${moment(record.value.date_start).format('H:mm')} ~ ${moment(record.value.date_end).format('H:mm')}`
        })
        

</script>