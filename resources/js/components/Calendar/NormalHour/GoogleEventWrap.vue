<template>
    <div 
        as="div" 
        class="calendar-card" 
        :class="[{'pop-cal-card' : expanded}]"
        :style="{
            minWidth: recordWidth, 
            marginTop: `${record.order !== undefined ? (record.order * 60) + ((record.order + 1) * 10) + (fullDayIndex * 35) : '0'}px`,
            left: recordLeft,
            maxHeight: maxHeight,
            transform: expanded ? `translate(${shiftRight}px, ${shiftBottom}px)` : `translate(0, 0)`,
            maxWidth: expanded ? '400%' : recordWidth, 
            width: 'max-content',
            minHeight: '42px'
        }"
        :id="`dayRecord_${record.id}`"
        @mousedown="setBeforeState"
        @touchstart="setBeforeState"
        ref="dayRecord"
        
    >
        <GoogleEventCard
            :day="record.start_date"
            :record="record"
            :expanded="expanded"    
            :unique-id="unique"
            @selectRecord="selectRecord"
        />

    </div>
</template>
<script setup lang="ts">
import { DateTime } from 'luxon';
import { ComponentPublicInstance, Ref, computed, inject, nextTick, ref } from 'vue';
import { OnLongPress } from '@vueuse/components'
import CalendarCard from '../CalendarCard.vue';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { CalendarRecord, GoogleEventItem } from '@/interface/calendarInterface';
import GoogleEventCard from '../GoogleEventCard.vue';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const props = defineProps<{
        record: GoogleEventItem
        fullDayIndex: number
    }>()
    const emit = defineEmits(['setParentDroppable'])
    const draggingCalendar = inject<Ref<CalendarRecord | null>>('draggingCalendar')
    const shiftRight = ref(0)
    const shiftBottom = ref(0)
    const beforeState = ref(0)
    const beforeLeft = ref(0)
    const dayRecord = ref<ComponentPublicInstance | null>(null)




    const maxHeight = computed(() => {
        return expanded.value ? '100vh' : '60px'
    })
    const unique = computed(() => {
        const u = Math.floor(100000 + Math.random() * 900000).toString()
        const r = props.record.id.toString()
        return `cal_${r}_${u}`
    })
    const expanded = computed(() => {
        return menu.parent == unique.value
    })
    const recordWidth = computed(() => {
        if(expanded.value){
            return '200%'
        }else{
            const startDateTime = DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm');
            const endDateTime = DateTime.fromFormat(`${props.record.end_date} ${props.record.end_time}`, 'yyyy-MM-dd HH:mm');
            const minutesDifference = Math.abs(startDateTime.diff(endDateTime, 'minutes').as('minutes'))
            const steps = Math.ceil(minutesDifference / 15)
            const until_start = Math.abs(startDateTime.startOf('day').diff(startDateTime, 'minutes').as('minutes'))                
            const before_limiter = Math.ceil(until_start / 15) 
            const max_block = 96 - before_limiter
            const computed_width = steps > max_block ? max_block : steps    
            const unit = responsive.mobile ? '500vw' : '120vw'
            return `calc(((${unit} - 30px) / 96 * ${computed_width}) - 3px)`
        }        
    })
    const recordLeft = computed(() => {
        const startDateTime = DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm');
        const diff = Math.abs(startDateTime.diff(startDateTime.startOf('hour'), 'minutes').as('minutes'))
        const steps = Math.floor(diff / 15) 
        const unit = responsive.mobile ? '500vw' : '120vw'
        return `calc(((${unit} - 30px) / 96 * ${steps}) + 1px)`
    })

    const setBeforeState = (event: MouseEvent | TouchEvent) => {
        
        const el = document.getElementById('cal_day_view')
        const left = el ? el.scrollLeft : 0
        beforeLeft.value = left
        beforeState.value = 'clientX' in event ? event.clientX : event.touches[0].clientX
    }
    const selectRecord =(event:MouseEvent) => {
        if(event && Math.abs( event.x - beforeState.value) > 15) {
            return
        }
        menu.setMenu( {parent: unique.value})
        nextTick(() => {
            const el = document.getElementById(`dayRecord_${props.record.id}`)
            if(el){
                const rect = el.getBoundingClientRect();
                const compare_value = responsive.mobile ? 30 : 80
                if(rect.x < compare_value){
                    const startDateTime = DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm');
                    const val = startDateTime.diff(startDateTime.startOf('day').plus({ hours: 1 }), 'hours').as('hours') > 0 ? 1 : 0
                    const time = startDateTime.minus({ hours: val }).startOf('hour').hour
                    
                    if(time <= 1){
                        document.getElementById(`cal_day_view`)?.scrollTo({ left: 0, behavior: 'smooth'})
                    }else{
                        document.getElementById(`d_day_${time}`)?.scrollIntoView({behavior: 'smooth'})
                    }

                }else{
                    const right_check = rect.x + rect.width
                    if(right_check > window.innerWidth){
                        shiftRight.value = window.innerWidth - right_check - 10
                    }
                }
                const bottom_check = rect.y + rect.height
                const value = responsive.mobile && auth.user?.footer_view ? 45 : 0
                if(bottom_check > window.innerHeight - value){
                    shiftBottom.value = window.innerHeight - value - bottom_check - 10
                }
            }
        })

    }
    
</script>