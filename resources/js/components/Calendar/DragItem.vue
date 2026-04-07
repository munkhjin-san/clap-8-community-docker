<template>
    <div v-if="draggingCalendar">
        <div 
            v-if="!mobile"
            ref="draggingCalendarRef" 
            class="calendar-card dragging-calendar" 
            :style="{
                width: `${draggingCalendar['width']}px`, 
                maxHeight: '60px',
                background: background,
                color: color,
                position: 'fixed',
                zIndex: '100',
                top: `${draggingCalendar['y'] ? draggingCalendar['y'] + 10 : 0}px`,
                left: `${draggingCalendar['x'] ? draggingCalendar['x'] + 10 : 0}px`
            }"
        >
            <div class="calendar-card-inner">            
                <div style="display: flex;">
                    <UserPanel v-for="user in draggingCalendar.calendar_users.slice(0, 3)" :user="user" :disableInstant="true" imgClass="userSmallIcon" size="15"/>
                    <span style="line-height: 15px;" v-if="draggingCalendar.calendar_users.length > 3">...({{ draggingCalendar.calendar_users.length }})</span>
                </div>
                <div style="white-space: nowrap;">{{ time }}</div>          
                <div>{{ draggingCalendar.title }}</div>
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
<script setup lang="ts">
import { computed, inject, onMounted, onUnmounted, Ref, ref, useTemplateRef } from 'vue';
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useTheme } from '@/store/theme';
import { useResponsive } from '@/store/responsive'
import UserPanel from '@/components/Global/UserPanel.vue'
import { DateTime } from 'luxon';
import { useCalendar } from '@/composables/calendar';
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const theme = useTheme()
    const mobile = ref(false)
    const {draggingCalendar, setDraggingCalendar} = useCalendar()
    onMounted(() => {
        if ('ontouchstart' in window || navigator.maxTouchPoints) {
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

        const onReset = () => {        
            if(draggingCalendar.value)    
            setDraggingCalendar(null)         
        }
        const draggingCalendarRef = useTemplateRef('draggingCalendarRef')
        const onMove = (e:MouseEvent) => {
            if(!draggingCalendar.value || responsive.mobile) return
            let el = draggingCalendarRef.value as HTMLElement            
            el.style.top = e.clientY + 10 + 'px'
            el.style.left = e.clientX + 10 + 'px'
        }  
        const cancelMove = () => {
            if(draggingCalendar.value) {
                setDraggingCalendar(null)
            }
        }

 
        const background = computed(() => {
            
            const me = draggingCalendar.value ? draggingCalendar.value.calendar_users.filter(ob => ob.id == auth.id) : []
            const index = auth.user?.color || 0
            return me.length ? colors[index]?.light : 'var(--task-background)'
        })
        const color = computed(() => {
            const me = draggingCalendar.value ? draggingCalendar.value.calendar_users.filter(ob => ob.id == auth.id) : []
            return me.length && theme.dark ? 'var(--background-color)' : 'var(--primary-color)'
        })     
               
        const time = computed(() => {
            return draggingCalendar.value ? `${DateTime.fromSQL(draggingCalendar.value.date_start).toFormat('HH:mm')} ~ ${DateTime.fromSQL(draggingCalendar.value.date_end).toFormat('HH:mm')}` : ''
        })
        

</script>